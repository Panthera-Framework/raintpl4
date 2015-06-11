<?php
/**
 * Adds support for CoffeeScript using external compilers
 * Requires a shell access to the server
 *
 * @config
 *
 * @package Rain\Plugins
 * @author Mateusz Warzyński <lxnmen@gmail.com>
 * @author Damian Kęska <damian@pantheraframework.org>
 */
class CoffeeScript extends Rain\Tpl\RainTPL4Plugin
{
    public $coffee = 'coffee';
    public $templateDirectory = '';
    public $baseDirectory = './';
    public $recentlyCompiled = null;
    public $content = null;
    public $compilers = array();

    public function init()
    {
        // default configuration
        $this->compilers = $this->engine->getConfigurationKey('CoffeeScript.compilers', array(
            // CoffeeScript, ofically this is supported
            'text/coffeescript' => array(
                'executable' => 'coffee',
                'stdinParams' => '-sp',
                'fileParams' => '-p %s',
            ),

            // Google Dart, just an example how to add a custom language and compiler here # dart2js --out=test.js test.dart
            'text/dart' => array(
                'executable' => 'dart2js',
                'stdinParams' => false, // dart2js does not support read from stdin
                'fileParams' => '--out=%d %s',
            ),
        ));

        $this->baseDirectory = $this->engine->getConfigurationKey('CoffeeScript.baseDir', './');
        $this->templateDirectory = $this->engine->getConfigurationKey('tpl_dir');

        // copy file content from CSSLess plugin's memory
        if (isset($this->engine->__eventHandlers['CSSLess']))
        {
            $this->content = $this->engine->__eventHandlers['CSSLess']->content;
        }

        if (!is_dir($this->baseDirectory))
        {
            throw new Rain\Tpl\IOException('"' .$this->baseDirectory. '" (' .realpath($this->baseDirectory). '") is not a directory', 1);
        }

        $this->cacheDir = $this->engine->getConfigurationKey('cache_dir');

        // hook up to Engine and Parser
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
        if ($compiledTemplatePath == $this->recentlyCompiled)
        {
            return true;
        }

        if (is_file($compiledTemplatePath))
        {
            $this->contents = file_get_contents($compiledTemplatePath);
            $pos = 0;

            do
            {
                $pos = strpos($this->contents, '/** @CoffeeScript-timestamp:', $pos);
                $posEnd = strpos($this->contents, '/CoffeeScript-timestamp-ends/', ($pos + 1));

                if ($pos === false || $posEnd === false)
                {
                    break;
                }

                $data = json_decode(base64_decode(substr($this->contents, ($pos + 23), ($posEnd - $pos - 23))), true);

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
        $this->recentlyCompiled = $input[2];
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
            if (!$attributes || !isset($attributes['type']) || !isset($this->compilers[$attributes['type']]))
            {
                $pos = $endingTagPos;
                continue;
            }

            // normalize string
            $attributes['type'] = trim(strtolower($attributes['type']));

            if (isset($attributes['src']) && isset($attributes['source']))
            {
                // detect source code path
                if (isset($attributes['source']))
                {
                    if (is_file($attributes['source']))
                        $source = $attributes['source'];

                    elseif (is_file($this->baseDirectory. '/' .$attributes['source']))
                        $source = $this->baseDirectory. '/' .$attributes['source'];

                    elseif (is_file(pathinfo($input[1], PATHINFO_DIRNAME). '/css/' .$attributes['source']))
                        $source = pathinfo($input[1], PATHINFO_DIRNAME). '/css/' .$attributes['source'];

                    elseif (is_file(pathinfo($input[1], PATHINFO_DIRNAME). '/' .$attributes['source']))
                        $source = pathinfo($input[1], PATHINFO_DIRNAME). '/' .$attributes['source'];
                }

                $newHeader = 'script<?php /** @CoffeeScript-timestamp:' .base64_encode(json_encode(array('time' => time(), 'href' => $this->baseDirectory. '/' .$attributes['src'], 'source' => $source))). '/CoffeeScript-timestamp-ends/ */?>';
                unset($attributes['source']);
                $this->compileCoffeeFile($source, $attributes['src'], $attributes['type']);
                $newBody = "";
            } else {
                $newBody = $this->getCompiledCode($body, $attributes['type']);
				$newHeader = 'script';
            }

            /**
             * Replace header
             */
            $attributes['type'] = 'text/javascript';

            foreach ($attributes as $key => $value)
            {
                $newHeader .= ' ' . $key . '="' . $value . '"';
            }

            $newHeader = trim($newHeader);
            $headerDiff = strlen($newHeader) - strlen($header);
            $input[0] = substr_replace($input[0], $newHeader, ($pos + 1), ($posEnd - $pos - 1));

            if ($newBody)
            {
                $input[0] = substr_replace($input[0], $newBody, ($posEnd + $headerDiff) + 1, (($endingTagPos - $posEnd - 1) - $headerDiff - 6));
            }

            if (($pos + 1) > strlen($input[0]))
            {
                $pos = false;
            } else
                $pos = $endingTagPos;

        } while ($pos !== false && $pos < strlen($input[0]));

        return $input;
    }

    /**
     * Compile source code into CoffeeScript
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

        if ($this->compilers[$type]['stdinParams'] === false)
        {
            $tempFile = tempnam(sys_get_temp_dir(), hash('md4', $code));
            file_put_contents($tempFile, $code);

            $this->compileCoffeeFile($tempFile, $tempFile. '.js', $type);
            $code = file_get_contents($tempFile. '.js');
            unlink($tempFile); unlink($tempFile. '.js');
            return $code;
        }

        return self::pipeToProc($this->compilers[$type]['executable'] . ' ' .$this->compilers[$type]['stdinParams'], $code);
    }

    /**
     * Compile CoffeeScript file to JavaScript
     *
     * @param string $coffeeFile CoffeeScript source code path
	 * @param string $outputPath Path where to save css file
     *
     * @throws Exception
     * @author Mateusz Warzyński <lxnmen@gmail.com>
     * @return bool
     */
    public function compileCoffeeFile($coffeeFile, $outputPath, $type)
    {
        $params = str_replace('%s', $coffeeFile, $this->compilers[$type]['fileParams']);
        $params = str_replace('%d', $outputPath, $params);

        $compiled = self::pipeToProc($this->compilers[$type]['executable'] . ' ' .$params, false);

        // @todo: Implement it in normal way
        $fp = fopen($outputPath, 'w');
        fwrite($fp, $compiled);
        fclose($fp);
        return true;
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

        if ($stdin !== false)
        {
            if (!is_resource($proc))
            {
                throw new Exception("Cannot start `" . $cmd . "`, please make sure the command is available, a proper tool is installed");
            }

            fwrite($pipes[0], $stdin);
        }

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