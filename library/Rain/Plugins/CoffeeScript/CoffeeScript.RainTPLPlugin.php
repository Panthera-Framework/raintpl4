<?php
/**
 * Adds support for CoffeeScript using external compilers
 * Requires a shell access to the server
 *
 * @package Rain\Plugins
 * @author Mateusz Warzyński <lxnmen@gmail.com>
 * @author Damian Kęska <damian@pantheraframework.org>
 */
class CoffeeScript extends Rain\Tpl\RainTPL4Plugin
{
    public $dom = null;
    public $coffee = 'coffee';
    public $templateDirectory = '';
    public $baseDirectory = './';

    public function init()
    {
        // default configuration
        $this->coffee = $this->engine->getConfigurationKey('CoffeeScript.coffee.executable', 'coffee');
        $this->baseDirectory = $this->engine->getConfigurationKey('CoffeeScript.baseDir', './');
        $this->templateDirectory = $this->engine->getConfigurationKey('tpl_dir');

        if (!is_dir($this->baseDirectory))
        {
            throw new Rain\Tpl\IOException('"' .$this->baseDirectory. '" (' .realpath($this->baseDirectory). '") is not a directory', 1);
        }

        $this->cacheDir = $this->engine->getConfigurationKey('cache_dir');
        $this->engine->connectEvent('parser.compileTemplate.after', array($this, 'afterCompile'));
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
        if (is_file($compiledTemplatePath))
        {
            $contents = file_get_contents($compiledTemplatePath);
            $pos = 0;

            do
            {
                $pos = strpos($contents, '/** @CSSLess-timestamp:', $pos);
                $posEnd = strpos($contents, '/CSSLess-timestamp-ends/', ($pos + 1));

                if ($pos === false || $posEnd === false)
                {
                    break;
                }

                $data = json_decode(base64_decode(substr($contents, ($pos + 23), ($posEnd - $pos - 23))), true);

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

        return true;
    }

    /**
     * Execute a CoffeeScript code compilation after template compilation
     *
     * @param array $input array($parsedCode, $templateFilePath, $parser)
     *
     * @throws \Rain\InvalidConfiguration
     * @throws \Rain\Tpl\SyntaxException
     *
     * @author Mateusz Warzyński <lxnmen@gmail.com>
     * @author Damian Kęska <damian.keska@fingo.pl>
     * @return array
     */
    public function afterCompile($input)
    {
        $pos = 0;

        /**
         * Parse all <script></script> tags
         */
        do
        {
            // find <script, >, and </script> positions
            $pos = stripos($input[0], '<script', $pos);

            if ($pos === false)
            {
                break;
            }

            $posEnd = strpos($input[0], '>', $pos);
            $endingTagPos = stripos($input[0], '</script>', ($posEnd+1));

            if ($posEnd === false || $endingTagPos === false)
            {
                throw new \Rain\Tpl\SyntaxException('Syntax exception in HTML code, not closed <script/> tag', 126);
            }

            // we need a <style > header and innerHTML body
            $body = substr($input[0], ($posEnd + 1), ($endingTagPos - $posEnd - 1));
            $header = substr($input[0], ($pos + 1), ($posEnd - $pos - 1));

            // attributes needs to be rewritten, as we cannot leave "text/coffeescript" in the code
            $attributes = Rain\Tpl\Parser::parseTagArguments($header);

            // not a valid CoffeeScript, but probably just a regular Javascript code
            if (!$attributes || !isset($attributes['type']) || ($attributes['type'] != 'text/coffeescript'))
            {
                $pos = $endingTagPos;
                continue;
            }

            if (isset($attributes['src']))
            {
                $attributes['src'] = $this->compileCoffeeFile($this->baseDirectory."/".$attributes['src'], $attributes['src']);
                $newBody = "";
            } else {
                $newBody = $this->getCompiledCode($body, $attributes['type']);
            }

            /**
             * Replace header
             */
            $attributes['type'] = 'text/javascript';
            $newHeader = 'script';

            foreach ($attributes as $key => $value)
            {
                $newHeader .= ' ' . $key . '="' . $value . '"';
            }

            $newHeader = trim($newHeader);
            $headerDiff = strlen($newHeader) - strlen($header);
            $input[0] = substr_replace($input[0], $newHeader, ($pos + 1), ($posEnd - $pos - 1));
            $input[0] = substr_replace($input[0], $newBody, ($posEnd + $headerDiff) + 1, (($endingTagPos - $posEnd - 1) - $headerDiff - 6));

            if (($pos + 1) > strlen($input[0]))
            {
                $pos = false;
            } else
                $pos = $endingTagPos;

        } while ($pos !== false && $pos < strlen($input[0]));

        return $input;
    }

    /**
     * Compile source code into CSS
     *
     * @param string $code
     * @param string $type mime type text/coffee-script
     *
     * @throws Exception
     * @author Mateusz Warzyński <lxnmen@gmail.com>
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return string
     */
    public function getCompiledCode($code, $type)
    {
        if (!trim($code))
        {
            return '';
        }

        if ($type == 'text/coffeescript' && basename($this->coffee) == 'coffee')
        {
            return self::pipeToProc($this->coffee . ' -sp ', $code);
        }

        throw new Exception('Unrecognized <script/> language', 456);
    }

    /**
     * Compile CoffeeScript file to JavaScript
     *
     * @param string $coffeeFile path to file with coffeescript code
     * @param string $sourceValue path to Coffee file in src tag
     *
     * @throws Exception
     * @author Mateusz Warzyński <lxnmen@gmail.com>
     * @return string
     */
    public function compileCoffeeFile($coffeeFile, $sourceValue)
    {
        if (basename($this->coffee) == 'coffee')
        {
            if (file_exists($coffeeFile))
            {
                $command = $this->coffee.' -cb '.$coffeeFile;
                shell_exec($command);

                if (file_exists(str_replace(".coffee", ".js", $coffeeFile)))
                    return str_replace(".coffee", ".js", $sourceValue, $count);
            }
        }

        throw new Exception('CoffeeScript compiler could not be found.', 456);
    }

    /**
     * Execute a command with piped stdin (pass text to it)
     *
     * @param string $cmd Command
     * @param string $stdin Input text
     *
     * @throws Exception
     * @author Damian Kęska <damian@pantheraframework.org>
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