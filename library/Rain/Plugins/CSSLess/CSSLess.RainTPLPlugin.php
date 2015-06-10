<?php
/**
 * Adds support for LESS and SASS using external compilers
 * Requires a shell access to the server
 *
 * @config CSSLess.less.executable lessc LESS CSS compiler executable
 * @config CSSLess.sass.executable sass SASS/SCSS compiler executable (supported: sass, sassc, pyscss)
 * @config CSSLess.sass.args --scss SASS compiler parameters
 * @config CSSLess.less.args '' LESS compiler parameters
 * @config CSSLess.baseDir ./ Base directory, eg. your web application webroot directory, used in "href" attribute in <link> tags (optionally also in "source" attribute)
 * @package Rain\Plugins
 * @author Damian Kęska <damian@pantheraframework.org>
 */
class CSSLess extends Rain\Tpl\RainTPL4Plugin
{
    public $cacheDir = '/tmp/';
    public $sass = 'sass';
    public $less = 'lessc';
    public $baseDirectory = './';
    public $content = null;
    public $recentlyCompiled = null;

    public function init()
    {
        // default configuration
        $this->less = $this->engine->getConfigurationKey('CSSLess.less.executable', 'lessc');
        $this->sass = $this->engine->getConfigurationKey('CSSLess.sass.executable', 'sass');
        $this->baseDirectory = $this->engine->getConfigurationKey('CSSLess.baseDir', './');

        switch (basename($this->sass))
        {
            case 'sass':
                $this->engine->getConfigurationKey('CSSLess.sass.args', '--scss');
            break;

            case 'sassc':
            case 'pyscss':
            default:
                $this->engine->getConfigurationKey('CSSLess.sass.args', '');
            break;
        }

        // copy file content from CoffeScript plugin's memory
        if (isset($this->engine->__eventHandlers['CoffeeScript']))
        {
            $this->content = $this->engine->__eventHandlers['CoffeeScript']->content;
        }

        if (!is_dir($this->baseDirectory))
        {
            throw new Rain\Tpl\IOException('"' .$this->baseDirectory. '" (' .realpath($this->baseDirectory). '") is not a directory', 1);
        }

        $this->cacheDir = $this->engine->getConfigurationKey('cache_dir');
        $this->engine->connectEvent('parser.compileTemplate.after', array($this, 'afterCompile'));
        $this->engine->connectEvent('engine.checkTemplate.parsedTemplateFilePath', array($this, 'checkTemplate'));
    }

    /**
     * Check template for changes in external resources
     *
     * @param string $compiledTemplatePath
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return bool|string
     */
    public function checkTemplate($compiledTemplatePath)
    {
        if ($this->recentlyCompiled == $compiledTemplatePath)
        {
            return true;
        }

        if (is_file($compiledTemplatePath))
        {
            if (!$this->content)
            {
                $this->content = file_get_contents($compiledTemplatePath);
            }

            $pos = 0;

            do
            {
                $pos = strpos($this->content, '/** @CSSLess-timestamp:', $pos);
                $posEnd = strpos($this->content, '/CSSLess-timestamp-ends/', ($pos + 1));

                if ($pos === false || $posEnd === false)
                {
                    break;
                }

                $data = json_decode(base64_decode(substr($this->content, ($pos + 23), ($posEnd - $pos - 23))), true);

                /**
                 * Check if CSS file was modified, if yes then tell RainTPL4 to recompile the template and it's all resources
                 */
                if (!is_file($data['href']) || filemtime($data['source']) > $data['time'])
                {
                    return false;
                }

                $pos = $posEnd + 1;
            } while ($pos !== false);
        }
    }

    /**
     * Execute a LESS and/or SASS code compilation after template compilation
     *
     * @param array $input array($parsedCode, $templateFilepath, $parser)
     *
     * @throws \Rain\InvalidConfiguration
     * @throws \Rain\Tpl\SyntaxException
     *
     * @author Damian Kęska <damian.keska@fingo.pl>
     * @return array
     */
    public function afterCompile($input)
    {
        $pos = 0;

        /**
         * Parse all <style></style> tags
         */
        do
        {
            // find <style, >, and </style> positions
            $pos = stripos($input[0], '<style', $pos);

            if ($pos === false)
            {
                break;
            }

            $posEnd = strpos($input[0], '>', $pos);
            $endingTagPos = stripos($input[0], '</style>', ($posEnd+1));

            if ($posEnd === false || $endingTagPos === false)
            {
                throw new \Rain\Tpl\SyntaxException('Syntax exception in HTML code, not closed <style/> tag', 126);
            }

            // we need a <style > header and innerHTML body
            $body = substr($input[0], ($posEnd + 1), ($endingTagPos - $posEnd - 1));
            $header = substr($input[0], ($pos + 1), ($posEnd - $pos - 1));

            // attributes needs to be rewrited, as we cannot leave "text/sass" or "text/less" in the code
            $attributes = Rain\Tpl\Parser::parseTagArguments($header);

            // not a valid SASS/LESS, but propably just a regular CSS style
            if (!$attributes || !isset($attributes['type']) || ($attributes['type'] != 'text/sass' && $attributes['type'] != 'text/less'))
            {
                $pos = $endingTagPos;
                continue;
            }

            $newBody = $this->getCompiledCode($body, $attributes['type']);

            /**
             * Replace header
             */
            $attributes['type'] = 'text/css';
            $newHeader = 'style';

            foreach ($attributes as $key => $value)
            {
                $newHeader .= ' ' . $key . '="' . $value . '"';
            }

            $newHeader = trim($newHeader);
            $headerDiff = strlen($newHeader) - strlen($header);
            $input[0] = substr_replace($input[0], $newHeader, ($pos + 1), ($posEnd - $pos - 1));
            $input[0] = substr_replace($input[0], $newBody, ($posEnd + $headerDiff) + 1, (($endingTagPos - $posEnd - 1) - $headerDiff - 1));

            if (($pos + 1) > strlen($input[0]))
            {
                $pos = false;
            } else
                $pos = $endingTagPos;

        } while ($pos !== false && $pos < strlen($input[0]));

        /**
         * And also parse all external sources (<link/>)
         */
        $pos = -1;

        do {
            $pos = stripos($input[0], '<link', ($pos + 1));

            if ($pos !== false)
            {
                $ending = strpos($input[0], '>', ($pos + 2));

                if ($ending !== false)
                {
                    $header = Rain\Tpl\Parser::parseTagArguments(substr($input[0], $pos + 1, ($ending - $pos - 1)));
                    $source = null;

                    if (!isset($header['type']))
                    {
                        continue;
                    }

                    // detect source code path
                    if (isset($header['source']))
                    {
                        if (is_file($header['source']))
                            $source = $header['source'];

                        elseif (is_file($this->baseDirectory. '/' .$header['source']))
                            $source = $this->baseDirectory. '/' .$header['source'];

                        elseif (is_file(pathinfo($input[1], PATHINFO_DIRNAME). '/css/' .$header['source']))
                            $source = pathinfo($input[1], PATHINFO_DIRNAME). '/css/' .$header['source'];

                        elseif (is_file(pathinfo($input[1], PATHINFO_DIRNAME). '/' .$header['source']))
                            $source = pathinfo($input[1], PATHINFO_DIRNAME). '/' .$header['source'];
                    }

                    if (!$source)
                    {
                        throw new \Rain\Tpl\SyntaxException('Cannot parse tag ' . htmlspecialchars(substr($input[0], $pos, ($ending - $pos))) . ', missing "source" attribute or file does not exists, you have to specify a valid less/sass source file to compile', 42);
                    }

                    if ($header['type'] != 'text/sass' && $header['type'] != 'text/less')
                    {
                        throw new \Rain\Tpl\SyntaxException('Cannot parse tag ' . htmlspecialchars(substr($input[0], $pos, ($ending - $pos))) . ', missing "type" attribute, possible values: text/sass, text/less', 25);
                    }

                    // compile the file
                    $fp = fopen($this->baseDirectory. '/' .$header['href'], 'w');
                    fwrite($fp, $this->getCompiledCode(file_get_contents($source), $header['type']));
                    fclose($fp);

                    $newHeader = 'link <?php /** @CSSLess-timestamp:' .base64_encode(json_encode(array('time' => time(), 'href' => $this->baseDirectory. '/' .$header['href'], 'source' => $source))). '/CSSLess-timestamp-ends/ */?>';
                    $header['type'] = 'text/css';
                    $header['rel'] = 'stylesheet';
                    unset($header['source']);

                    foreach ($header as $key => $value)
                    {
                        $newHeader .= $key . '="' . $value . '" ';
                    }

                    $input[0] = substr_replace($input[0], trim($newHeader), $pos + 1, ($ending - $pos - 1));
                }

                $pos = $ending;
            }

        } while ($pos !== false);

        $this->recentlyCompiled = $input[2];
        return $input;
    }

    /**
     * Compile source code into CSS
     *
     * @param string $code
     * @param string $type mime type eg. text/less, text/sass
     *
     * @throws Exception
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return string
     */
    public function getCompiledCode($code, $type)
    {
        if (!trim($code))
        {
            return '';
        }

        if ($type == 'text/sass' && (basename($this->sass) == 'sassc' || basename($this->sass) == 'sass' || basename($this->sass) == 'pyscss'))
        {
            return self::pipeToProc($this->sass . ' -t expanded ' .$this->engine->getConfigurationKey('CSSLess.sass.args'), $code);
        }
        elseif ($type == 'text/less' && basename($this->less) == 'lessc')
            return self::pipeToProc($this->less. ' -' .$this->engine->getConfigurationKey('CSSLess.less.args', ''), $code);

        throw new Exception('Unrecognized <style> language', 456);
    }

    /**
     * Execute a command with piped stdin (pass text to it)
     *
     * @param string $cmd Command
     * @param string $stdin Input text
     *
     * @throws Exception
     * @return string
     */
    public static function pipeToProc($cmd, $stdin)
    {
        $streams = array(array('pipe', 'r'), array('pipe', 'w'));
        defined('STDERR') and $streams[] = STDERR;

        $proc = proc_open($cmd, $streams, $pipes);

        if (!is_resource($proc))
        {
            throw new Exception("Cannot start `" . $cmd . "`, please make sure the command is available, a proper tool is installed");
        }

        fwrite($pipes[0], $stdin);
        fclose(array_shift($pipes));

        $stdout = stream_get_contents($pipes[0]);
        fclose(array_shift($pipes));

        $exitCode = proc_close($proc);

        if ($exitCode !== 0)
        {
            throw new Exception("Failed to call `" .$cmd. "`, please make sure the command is available, a proper tool is installed. Exit code $exitCode, output: '" . $stdout . "', input: '" .$stdin. "'");
        }

        return $stdout;
    }
}