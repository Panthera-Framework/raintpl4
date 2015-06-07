<?php
/**
 * Adds support for LESS and SASS using external compilers
 * Requires a shell access to the server
 *
 * @package Rain\Plugins
 * @author Damian Kęska <damian@pantheraframework.org>
 */
class CSSLess extends Rain\Tpl\RainTPL4Plugin
{
    public $dom = null;
    public $cacheDir = '/tmp/';
    public $sass = 'sassc';
    public $less = 'lessc';
    public $baseDirectory = './';

    public function init()
    {
        // shared HTML parser object between plugins
        if (isset($this->engine->__eventHandlers['Coffescript']))
        {
            $this->dom = &$this->engine->__eventHandlers['Coffescript']->dom;
        }

        // default configuration
        $this->less = $this->engine->getConfigurationKey('CSSLess.less.executable', 'lessc');
        $this->sass = $this->engine->getConfigurationKey('CSSLess.sass.executable', 'sassc');
        $this->baseDirectory = $this->engine->getConfigurationKey('CSSLess.baseDir', './');

        $this->cacheDir = $this->engine->getConfigurationKey('cache_dir');
        $this->engine->connectEvent('parser.compileTemplate.after', array($this, 'afterCompile'));
    }

    /**
     * Execute a LESS and/or SASS code compilation after template compilation
     *
     * @param array $input array($parsedCode, $templateFilepath)
     *
     * @throws \Rain\InvalidConfiguration
     * @throws \Rain\RestrictedException
     *
     * @author Damian Kęska <damian.keska@fingo.pl>
     * @return array
     */
    public function afterCompile($input)
    {
        $pos = -1;

        /**
         * Parse all <style></style> tags
         */
        do
        {
            // find <style, >, and </style> positions
            $pos = stripos($input[0], '<style', ($pos + 1));
            $posEnd = strpos($input[0], '>', $pos);
            $endingTagPos = stripos($input[0], '</style>', $posEnd);

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

            $headerDiff = strlen($newHeader) - strlen($header);
            $input[0] = substr_replace($input[0], $newHeader, ($pos + 1), ($posEnd - $pos - 1));
            $input[0] = substr_replace($input[0], $newBody, ($posEnd + $headerDiff) + 1, (($endingTagPos - $posEnd - 1) - $headerDiff - 1));

            if ($pos !== false)
            {
                $pos = ($endingTagPos + 8);

                if (($pos + 1) > strlen($input[0]))
                    $pos = false;
            }

        } while ($pos !== false);

        return $input;
    }

    public function getCompiledCode($code, $type)
    {
        if (!trim($code))
        {
            return '';
        }

        if ($type == 'text/sass' && basename($this->sass) == 'sassc')
            return self::pipeToProc($this->sass. ' -t expanded', $code);

        elseif ($type == 'text/less' && basename($this->less) == 'lessc')
            return self::pipeToProc($this->less. ' -', $code);

        throw new Exception('Unrecognized <style> language', 456);
    }

    public static function pipeToProc($cmd, $stdin)
    {
        $streams = array(array('pipe', 'r'), array('pipe', 'w'));
        defined('STDERR') and $streams[] = STDERR;

        $proc = proc_open($cmd, $streams, $pipes);
        is_resource($proc) or die("Cannot start [$cmd].");

        fwrite($pipes[0], $stdin);
        fclose(array_shift($pipes));

        $stdout = stream_get_contents($pipes[0]);
        fclose(array_shift($pipes));

        $exitCode = proc_close($proc);

        if ($exitCode !== 0)
        {
            throw new Exception ("Failed to call [$cmd] - exit code $exitCode.");
        }

        return $stdout;
    }
}