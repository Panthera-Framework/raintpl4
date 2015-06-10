<?php
namespace Rain\Tpl;
use Rain\Tpl;
use Rain;

/**
 *  RainTPL
 *  --------
 *  Realized by Federico Ulfo & maintained by the Rain Team
 *  Rewritten by Damian Kęska
 *  Distributed under GNU/LGPL 3 License
 *
 *  @package Rain\Parser
 *  @version 3.1 beta
 */
class Parser
{
    use RainTPLConfiguration;
    use RainTPLEventsHandler;

    // variables
    public $var = array();

    protected $templateInfo = array();

    public $config = array(
        //'ignore_single_quote' => true,
    );

    /**
     * Temporary data for parser when running
     *
     * @var array
     */
    protected $tagData = array(

    );

    // tags registered by the developers
    public $registeredTags = array();

    // tags natively supported
    public $tags = array(
        // @TODO: {block}
        // @TODO: {foreach type="array"} Strict type setting to minimize output code
        // @TODO: {autoescape} for escaping HTML code inside
        // @TODO: Ternary operator on variables

        'variable' => true, // {$} RainTPL4
        'if' => true, // {if}, {elseif} RainTPL4
        'else' => true, // {else} RainTPL4
        'function' => true, // {function} RainTPL4
        'loop' => true, // {loop}, {foreach} RainTPL4
        'loop_break' => true, // RainTPL4
        'include' => true, // RainTPL4
        'capture' => true, // RainTPL4
        'block' => true, // {block} RainTPL4
        'noparse' => true, // {noparse}, {literal} RainTPL4
        'comment' => true, // {*}, {ignore} RainTPL4
        'constant' => true, // {#CONSTANT_NAME#} RainTPL4
        'mark' => true, // {mark a} {goto a} RainTPL4
        'customTags' => false, // Custom defined tags (regexp only), will auto-enable when $registeredTags will contain any function
        // put your custom 'tagName' => true/false // here, and put 'tagName' => function() to $this->blockParserCallbacks to register you own non-regexp block parser
    );

    /**
     * List of attached functions for parsing blocks
     *
     * Example: 'myTag' => array($object, 'methodName')
     *
     * @var array
     */
    public $blockParserCallbacks = array(

    );

    /**
     * List of modifier functions
     *
     * @var array
     */
    public $modifiers = array(

    );

    /**
     * Constructor
     *
     * @param RainTPL4|string|null $tplInstance Pass RainTPL4 instance to use its configuration. If not, then please set $this->config manually.
     *
     * @event parser.__construct $tplInstance
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function __construct($tplInstance = '')
    {
        if ($tplInstance && $tplInstance instanceOf Rain\RainTPL4)
        {
            $this->config = $tplInstance->config;
            $this->__eventHandlers = $tplInstance->__eventHandlers;
            $this->events = $tplInstance->events;
            $this->registeredTags = $tplInstance->registeredTags;
            $this->tags = array_merge($this->tags, $tplInstance->tags);
            $this->blockParserCallbacks = array_merge($this->blockParserCallbacks, $tplInstance->blockParserCallbacks);
            $this->modifiers = array_merge($this->modifiers, $tplInstance->modifiers);
        }

        $this->executeEvent('parser.__construct', $tplInstance);
    }

    /**
     * Compile the file and save it in the cache
     *
     * @param string $templateFilepath Path to template file
     * @param string $parsedTemplateFilepath Cache file path where to save the template
     *
     * @throws Exception
     * @throws Rain\Tpl_Exception
     *
     * @event parser.compileFile.compiled $templateFilepath, $parsedTemplateFilepath, $parsedCode
     * @event parser.compileFile.before $templateFilepath, $parsedTemplateFilepath
     *
     * @return string
     */
    public function compileFile($templateFilepath, $parsedTemplateFilepath)
    {
        list($templateFilepath, $parsedTemplateFilepath) = $this->executeEvent('parser.compileFile.before', array($templateFilepath, $parsedTemplateFilepath));

        // open the template
        $fp = fopen($templateFilepath, "r");

        // lock the file
        if (flock($fp, LOCK_SH))
        {
            // save the filepath in the info
            $this->templateInfo['template_filepath'] = $templateFilepath;

            // read the file
            $this->templateInfo['code'] = $code = fread($fp, filesize($templateFilepath));

            // xml substitution
            $code = preg_replace("/<\?xml(.*?)\?>/s", /*<?*/ "##XML\\1XML##", $code);

            // disable php tag
            if (!$this->getConfigurationKey('php_enabled'))
                $code = str_replace(array("<?", "?>"), array("&lt;?", "?&gt;"), $code);

            // xml re-substitution
            $code = preg_replace_callback("/##XML(.*?)XML##/s", function( $match ) {
                    return "<?php echo '<?xml " . stripslashes($match[1]) . " ?>'; ?>";
                }, $code);

            $parsedCode = "<?php if(!class_exists('Rain\RainTPL4')){exit;}?>" . $this->compileTemplate($code, $templateFilepath, $parsedTemplateFilepath);

            // fix the php-eating-newline-after-closing-tag-problem
            $parsedCode = str_replace("?>\n", "?>\n\n", $parsedCode);

            list($templateFilepath, $parsedTemplateFilepath, $parsedCode) = $this->executeEvent('parser.compileFile.compiled', array($templateFilepath, $parsedTemplateFilepath, $parsedCode));

            // create directories
            if (!is_dir($this->getConfigurationKey('cache_dir')))
                mkdir($this->getConfigurationKey('cache_dir'), 0755, TRUE);

            // check if the cache is writable
            if (!is_writable($this->getConfigurationKey('cache_dir')))
                throw new Exception('Cache directory ' . $this->getConfigurationKey('cache_dir') . 'doesn\'t have write permission. Set write permission or set RAINTPL_CHECK_TEMPLATE_UPDATE to FALSE. More details on http://www.raintpl.com/Documentation/Documentation-for-PHP-developers/Configuration/');

            // write compiled file
            file_put_contents($parsedTemplateFilepath, $parsedCode);

            // release the file lock
            flock($fp, LOCK_EX);
        }

        // close the file
        fclose($fp);
    }

    /**
     * Compile a string and save it in the cache
     *
     * @param string $templateFilepath
     * @param string $parsedTemplateFilepath: cache file where to save the template
     * @param string $code: code to compile
     */
    public function compileString($templateFilepath, $parsedTemplateFilepath, $code)
    {
        // open the template
        $fp = fopen($parsedTemplateFilepath, "w");

        // lock the file
        if (flock($fp, LOCK_SH))
        {
            // xml substitution
            $code = preg_replace("/<\?xml(.*?)\?>/s", "##XML\\1XML##", $code);

            // disable php tag
            if (!$this->getConfigurationKey('php_enabled'))
                $code = str_replace(array("<?", "?>"), array("&lt;?", "?&gt;"), $code);

            // xml re-substitution
            $code = preg_replace_callback("/##XML(.*?)XML##/s", function( $match ) {
                    return "<?php echo '<?xml " . stripslashes($match[1]) . " ?>'; ?>";
                }, $code);

            $parsedCode = "<?php if(!class_exists('Rain\RainTPL4')){exit;}?>" . $this->compileTemplate($code, $isString = true, $templateDirectory = null, $templateFilepath);

            // fix the php-eating-newline-after-closing-tag-problem
            $parsedCode = str_replace("?>\n", "?>\n\n", $parsedCode);

            // create directories
            if (!is_dir($this->getConfigurationKey('cache_dir')))
                mkdir($this->getConfigurationKey('cache_dir'), 0755, true);

            // check if the cache is writable
            if (!is_writable($this->getConfigurationKey('cache_dir')))
                throw new Exception('Cache directory ' . $this->getConfigurationKey('cache_dir') . 'doesn\'t have write permission. Set write permission or set RAINTPL_CHECK_TEMPLATE_UPDATE to false. More details on http://www.raintpl.com/Documentation/Documentation-for-PHP-developers/Configuration/');

            // write compiled file
            fwrite($fp, $parsedCode);

            // release the file lock
            flock($fp, LOCK_UN);
        }

        // close the file
        fclose($fp);
    }

    /**
     * Split code into parts that should contain {code} tavar_dump($templateFilepath);gs and HTML as separate elements
     *
     * @param string $code Input TPL code
     *
     * @event parser.prepareCodeSplit.before $code
     * @event parser.prepareCodeSplit.after $split, $blockPositions
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return array
     */
    protected function prepareCodeSplit($code)
    {
        $split = array();
        $cursor = 0;
        $current = 0;
        $blockPositions = array();
        $arrIndex = -1;
        $lastBlockType = '';

        $code = $this->executeEvent('parser.prepareCodeSplit.before', $code);

        while ($current !== false)
        {
            $current = strpos($code, '{', $cursor);

            if ($current === false)
                break;

            $sChar = substr($code, $current + 1, 1);
            $sCharMatch = (substr($code, $current + 1, 1) === ' ' || $sChar === "\t" || $sChar === "\n" || $sChar === "\r" /*|| ($this->getConfigurationKey('ignore_single_quote') && $sChar === "'")*/); // condition that check if there is any space or special character after "{"

            if (!$sCharMatch)
            {
                /**
                 * Template tags
                 */
                $currentEnding = strpos($code, '}', $current) + 1;

                // before our {code} block
                $split[] = substr($code, $cursor, ($current - $cursor)); $arrIndex++;

                // our {code} block
                $split[] = substr($code, $current, ($currentEnding - $current)); $arrIndex++;

                $blockPositions[$arrIndex] = $current. '|' .($currentEnding - $current);
                $cursor = $currentEnding;
                $lastBlockType = 1;

            } else {
                /**
                 * HTML & Javascript & JSON code
                 */
                $next = strpos($code, '{', $current + 1);

                if (!$next)
                    break;

                // take all data to "{"
                if ($sCharMatch)
                {
                    // divide into bigger blocks
                    if ($lastBlockType === 1 || $arrIndex === -1) {
                        $split[] = substr($code, $cursor, ($current - $cursor));
                        $arrIndex++;
                    } else
                        $split[$arrIndex] .= substr($code, $cursor, ($current - $cursor)); // append to existing block
                }

                // take all data after "{" until next "{"
                if ($lastBlockType === 1)
                {
                    $split[] = substr($code, $current, ($next - $current));
                    $arrIndex++;
                } else
                    $split[$arrIndex] .= substr($code, $current, ($next - $current)); // append to existing block

                $cursor = $next;
                $lastBlockType = 0;
            }
        }

        // the rest of code
        $split[] = substr($code, $cursor, (strlen($code) - $cursor));

        // remove empty entry from beginning if exists
        if (isset($split[0]) && !$split[0])
            unset($split[0]);

        // uncomment to see how the template is divided into parts
        //print_r($split);

        return $this->executeEvent('parser.prepareCodeSplit.after', array($split, $blockPositions));
    }

    /**
     * Compile template
     * @access protected
     *
     * @param string $code Code to compile
     * @param string $templateFilepath
     * @param string $parsedTemplateFilepath
     *
     * @event parser.compileTemplate.unknownTag $pos, $part, $templateFilepath
     * @event parser.compileTemplate.notClosedTag $tag
     * @event parser.compileTemplate.before $code, $templateFilepath
     * @event parser.compileTemplate.after $code, $templateFilepath, $parsedCode
     *
     * @throws \Rain\Tpl_Exception
     * @throws string
     * @return null|string
     */
    protected function compileTemplate($code, $templateFilepath, $parsedTemplateFilepath = '')
    {
        $parsedCode = '';
        $templateEnding = '';

        list($code, $templateFilepath, $parsedCode) = $this->executeEvent('parser.compileTemplate.unknownTag', array($code, $templateFilepath, $parsedCode));

        // statistics
        $compilationTime = microtime(true);
        $blockIterations = 0;

        // @TODO: Use class/methods inheritance to implement backwards compatibility
        /*if ($this->getConfigurationKey('raintpl3_plugins_compatibility'))
        {
            // execute plugins, before parse
            $context = static::getPlugins()->createContext(array(
                'code' => $code,
                'template_filepath' => $templateFilepath,
                'conf' => $this->config,
            ));

            static::getPlugins()->run('beforeParse', $context);
            $code = $context->code;
        }*/

        // custom tags
        if ($this->registeredTags)
            $this->tags['customTags'] = true;

        // remove comments
        if ($this->getConfigurationKey('remove_comments'))
        {
            $code = preg_replace('/<!--(.*)-->/Uis', '', $code);
        }

        // Testing parser configuration:
        //$this->setConfigurationKey('ignore_single_quote', true);
        //$this->setConfigurationKey('ignore_unknown_tags', true);

        list($codeSplit, $blockPositions) = $this->prepareCodeSplit($code);

        // new code
        if ($codeSplit)
        {
            // pre-detect option (could speed up compilation time)
            $preDetect = $this->getConfigurationKey('pre_detect');
            $profiler = $this->getConfigurationKey('profiler');

            // pass all blocks to this parser
            $passAllBlocksTo = '';
            $this->tagData = array(
                'loop' => array(
                    'level' => 0,
                    'count' => 0,
                    'totalParseTime' => 0,
                    'profile' => array(),
                ),
            );
            $tags = $this->tags;

            // uncomment line below to take a look what we have to parse
            // var_dump($codeSplit);

            /**
             * Loop over all code parts and execute actions on tags found in code
             *
             * Every code part begins with a HTML code or with a "{" that should be our TAG
             * For every found tag there is a callback executed to parse TPL -> PHP code
             *
             * Places where we are looking for callbacks are in order:
             * 1. $this->{tagName}BlockParser()
             * 2. $this->blockParserCallbacks[{tagName}]()
             * 3. {tagName}()
             *
             * @author Damian Kęska <damian@pantheraframework.org>
             */
            foreach ($codeSplit as $index => $part)
            {
                // run tag parsers only on tags, exclude "{ " from parsing
                $starts = substr($part, 1, 1);

                if (!$passAllBlocksTo && (substr($part, 0, 1) !== '{' || $starts == ' ' || $starts == "\n" || $starts == "\t"/* || ($this->getConfigurationKey('ignore_single_quote') && $starts == "'") */|| strpos($part, "\n") !== false))
                    continue;

                // tag parser found?
                $found = false;

                /**
                 * Try to read tag name to choose best block parser quickly as possible
                 *
                 * @author Damian Kęska <damian@pantheraframework.org>
                 */
                if ($preDetect)
                {
                    $tags = $this->tags;
                    $preDetectTag = substr($part, self::strposa($part, array('/', '{'), 0, 'max') + 1);
                    $preDetectTag = substr($preDetectTag, 0, self::strposa($preDetectTag, array(
                        ' ', '=', '}',
                    )));

                    if ($preDetectTag == '$') $preDetectTag = 'variable';
                    if ($preDetectTag == '#') $preDetectTag = 'constant';

                    if (isset($this->tags[$preDetectTag]) && $this->tags[$preDetectTag])
                    {
                        unset($tags[$preDetectTag]);

                        $tags = array(
                            $preDetectTag => true,
                        ) + $tags;
                    }
                }

                /**
                 * Go through all block parsers
                 *
                 * @author Damian Kęska <damian@pantheraframework.org>
                 */
                foreach ($tags as $tagName => $tag)
                {
                    $blockIterations++;

                    if ($passAllBlocksTo)
                        $tagName = $passAllBlocksTo;

                    $method = null;

                    // select a method source that will parse selected tag
                    if (method_exists($this, $tagName. 'BlockParser'))
                        $method = array($this, $tagName. 'BlockParser');

                    elseif (isset($this->blockParserCallbacks[$tagName]) && is_callable($this->blockParserCallbacks[$tagName]))
                        $method = $this->blockParserCallbacks[$tagName];

                    elseif(function_exists($tagName. 'BlockParser'))
                        $method = $tagName. 'BlockParser';


                    if ($method)
                    {
                        $originalPart = $part;

                        if (!isset($this->tagData[$tagName]))
                        {
                            $this->tagData[$tagName] = array(
                                'level' => 0,
                                'count' => 0,
                                'totalParseTime' => 0,
                                'profile' => array(),
                            );
                        }

                        $parseTime = microtime(true);
                        $result = call_user_func_array($method, array(
                            &$this->tagData[$tagName], &$part, &$tag, $templateFilepath, $index, $blockPositions, $code, &$passAllBlocksTo, strtolower($part), &$codeSplit, &$templateEnding,
                        ));

                        $codeSplit[$index] = $part;

                        if ($codeSplit[$index] !== $originalPart || $result === true)
                        {
                            $this->tagData[$tagName]['totalParseTime'] += (microtime(true) - $parseTime);

                            if ($profiler)
                                $this->tagData[$tagName]['profile'][$index] = (microtime(true) - $parseTime);

                            $found = true;
                            break;
                        }
                    }
                }

                if ($found === false && !$this->getConfigurationKey('ignore_unknown_tags'))
                {
                    $pos = $this->findLine($index, $blockPositions, $code);

                    if ($this->executeEvent('parser.compileTemplate.unknownTag', array($pos, $part, $templateFilepath)) !== true)
                    {
                        $e = new SyntaxException('Error! Unknown tag "' .$part. '", loaded by ' .$templateFilepath. ' at line ' .$pos['line']. ', offset ' .$pos['offset'], 1, null, $pos['line'], $templateFilepath);
                        throw $e->templateFile($templateFilepath);
                    }
                }
            }

            if ($this->tagData)
            {
                foreach ($this->tagData as $tag => $data)
                {
                    if (isset($data['level']) && intval($data['level']) > 1)
                    {
                        if ($this->executeEvent('parser.compileTemplate.notClosedTag', $tag) === true)
                            continue;

                        $e = new SyntaxException("Error! You need to close an {' .$tag. '} tag, in file ".$templateFilepath, 2, null, 'unknown', $templateFilepath);
                        throw $e->templateFile($templateFilepath);
                    }
                }
            }

            $parsedCode = join('', $codeSplit);
        }

        // add code that should be at the end of template
        $parsedCode .= $templateEnding;

        // optimize output
        $parsedCode = str_replace('?><?php', '', $parsedCode);

        // debugging
        if ($this->getConfigurationKey('print_parsed_code'))
        {
            print($parsedCode);
            exit;
        }

        // execute plugins
        list($parsedCode, $templateFilepath, $parsedTemplateFilepath) = $this->executeEvent('parser.compileTemplate.after', array($parsedCode, $templateFilepath, $parsedTemplateFilepath, $this));

        // execute plugins, after_parse
        /*if ($this->getConfigurationKey('raintpl3_plugins_compatibility'))
        {
            $context = static::getPlugins()->createContext(array(
                'code' => $code,
                'template_filepath' => $templateFilepath,
                'conf' => $this->config,
            ));

            $context->code = $parsedCode;
            static::getPlugins()->run('afterParse', $context);
            $compilationTime = (microtime(true) - $compilationTime);

            $parsedCode = $context->code;
        }*/

        return $parsedCode;
    }

    /**
     * Find a line number and byte offset of {code} tag in compiled file
     *
     * @param int $partIndex Code part index
     * @param array $blockPositions Index of positions of all splitted code parts
     * @param string $code Complete source code of a template
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return array
     */
    protected function findLine($partIndex, $blockPositions, $code)
    {
        if (!isset($blockPositions[$partIndex]))
        {
            return array(
                'line' => 'unknown',
                'offset' => 'unknown',
            );
        }

        $blockPosition = explode('|', $blockPositions[$partIndex]);
        $codeString = substr($code, 0, $blockPosition[0]);
        $lines = explode("\n", $codeString);

        return array(
            'line' => count($lines),
            'offset' => $blockPosition[0],
        );
    }

    /**
     * Check if character is between quotes in a string
     *
     * @param array $quotePositions List of quotes positions - result of self::getQuotesPositions()
     * @param int $start String/character position
     * #@param null|int $end String/character ending position (if 1 byte character then it could be possibly $start = $end)
     *
	 * @see static::getQuotesPositions()
     * @todo Ignore escaped quotes eg. \"
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return array Exact position of a quote set that is containing our search
     */
    public static function isInQuotes($quotePositions, $start/*, $end = null*/)
    {
        if (!intval($start))
            return array();

        //if ($end === null)
        //    $start = $end;

        foreach ($quotePositions as $quotePos)
        {
            // quoteStartPosition < $element < quoteEndPosition
            if ($start > $quotePos[1] && $start < $quotePos[2])
                return $quotePos;
        }

        return array();
    }

    /**
     * @param $html
     * @param null $loopLevel
     * @param bool $escape
     * @param bool $echo
     * @param bool $updateModifier
     * @return mixed|string
     */
    protected function varReplace($html, $loopLevel = NULL, $escape = TRUE, $echo = FALSE, $updateModifier = TRUE)
    {
        if ($loopLevel === 'auto' && isset($this->tagData['loop']) && isset($this->tagData['loop']['level']))
        {
            $loopLevel = $this->tagData['loop']['level'];
        }

        // change variable name if loop level
        if (!empty($loopLevel) && $loopLevel !== 'auto')
            $html = preg_replace(array('/(\$key)\b/', '/(\$value)\b/', '/(\$counter)\b/'), array('${1}' . $loopLevel, '${1}' . $loopLevel, '${1}' . $loopLevel), $html);

        preg_match_all('/\$([a-z_A-Z.0-9$]+)/', $html, $variables);

        // if no variables found
        if (!$variables || !$variables[0])
            return $html;

        $pos = 0;
        $variablePositions = array();

        foreach ($variables[0] as $varName)
        {
            $variablePositions[$varName] = array();

            do
            {
                $pos = strpos($html, $varName, $pos);

                if ($pos !== false)
                {
                    $variablePositions[$varName][] = $pos;
                    $pos++;
                }

            } while ($pos !== false);
        }

        $mappedVariablePositions = array();

        foreach ($variablePositions as $varName => $var)
        {
            foreach ($var as $varPos)
            {
				$length = strlen($varName);
				$modifier = '';
				$modifierPos = null;
				$modifierEnds = null;

				/**
				 * Lookup for modifiers
				 *
				 * @keywords modifiers
				 */
				if ($updateModifier && substr($html, ($varPos + $length), 1) === '|')
				{
					$modifierPos = ($varPos + $length + 1);
					$modifierEnds = self::strposa($html, array(
						'(', ')',
					), ($varPos + $length));

                    if ($modifierEnds === false)
                        $modifierEnds = strlen($html);

					$modifier = substr($html, ($varPos + $length), ($modifierEnds - ($varPos + $length)));
					$length = strlen($varName.$modifier);
				}

                $mappedVariablePositions[] = array(
                    'pos' => $varPos,
                    'varName' => $varName,
                    'parsed' => $varName,
					'length' => $length, // includes modifier length
					'modifier' => $modifier,
					'modifierPos' => $modifierPos,
					'modifierEnds' => $modifierEnds,
                );
            }
        }

        // all variables
        foreach ($variablePositions as $varName => $var)
        {
            $dotPositions = self::strposAll($varName, '.', 0);
            $arrayModificatorString = '';

            foreach ($dotPositions as $key => $position)
            {
                $endChar = '';
                if (($key+1) < count($dotPositions))
                    $endPosition = ($dotPositions[$key + 1] - $position - 1);
                else
                    $endPosition = strlen($varName);

                $arrayPart = trim(substr($varName, $position + 1, $endPosition));

                if (!strlen($arrayPart))
                    continue;

                if (is_numeric($arrayPart))
                {
                    $arrayModificatorString .= '[' . (string)intval($arrayPart) . ']';
                    continue;
                }

                $arrayModificatorString .= '["' .$arrayPart. '"]' .$endChar;
            }

			// restore a dot at the end of string if there was any but wasnt used as an array operator
			if (substr($varName, -1) === '.')
				$arrayModificatorString .= '.';

			// if any array modificator was found
			if ($arrayModificatorString)
				$parsedVariableString = substr($varName, 0, $dotPositions[0]). $arrayModificatorString;
			else
				$parsedVariableString = $varName;

			foreach ($mappedVariablePositions as &$pos)
			{
				if ($pos['varName'] == $varName)
					$pos['parsed'] = $parsedVariableString;
			}
        }

        usort($mappedVariablePositions, function ($a, $b) {
            return ($a['pos'] - $b['pos']);
        });

        // replace all occurrences
        $diff = 0; $i=0; $startPosDiff = 0;
        foreach ($mappedVariablePositions as $position)
        {
            if ((!$position['parsed'] || $position['parsed'] === $position['varName']) && !$updateModifier)
                continue;

            $i++;

            if ($i > 1)
                $startPosDiff = $diff;

			$replacement = $position['parsed'];

			if ($updateModifier)
			{
				$replacement = $this->parseModifiers($replacement . $position['modifier']);
			}

            $html = substr_replace($html, $replacement, ($position['pos'] + $startPosDiff), $position['length']);
            $diff += (strlen($replacement) - strlen($position['varName']));
        }

        return $html;
    }

    /**
     * Get positions of starting and ending of quotes
     *
     * @param string $code
     * @param array|null $charList (Optional) List of characters eg. ", ', `
     *
     * @todo Ignore escaped quotes - \"
     * @author Damian Kęska <damian.keska@pantheraframework.org>
     * @return array
     */
    public static function getQuotesPositions($code, $charList = null)
    {
        $pos = 0;
        $found = array();

        if ($charList === null)
        {
            $charList = array(
                '"', "'",
            );
        }

        do
        {
            $char = '';
            $pos = self::strposa($code, $charList, $pos, null, $char);

            if ($pos !== false)
            {
                $ending = self::strposa($code, array($char), ($pos + 1));

                if ($ending !== false)
                {
                    $found[] = array(
                        $char,
                        $pos,
                        $ending,
                    );

                    $pos = ($ending + 1);
                } else
                    $pos++; // what to do if something is not closed properly?
            }

        } while ($pos !== false);

        return $found;
    }

    /**
     * Determine if the tag is selected tag ending
     *
     * @param string $tagBody Tag body string
     * @param array|string $endings Possible endings, or ending (if passed a string)
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return bool
     */
    public function parseTagEnding($tagBody, $endings)
    {
        $tagBody = strtolower($tagBody);

        if (substr($tagBody, 0, 2) !== '{/' /*|| substr($tagBody, 0, 4) == '{end'*/)
        {
            return false;
        }

        if (!is_array($endings))
            $endings = array($endings);

        if (!$endings) return false;

        foreach ($endings as $endingKeyword)
        {
            if ($tagBody === '{/' .$endingKeyword. '}')
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse tag arguments
     *
     * Example input:
     * {foreach from="$test123" item="i" key="k"}
     *
     * Output:
     * from: $test123
     * item: i
     * key: k
     *
     * @param string $tagBody
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return array
     */
    public static function parseTagArguments($tagBody)
    {
        // strip tag out of "{" and "}"
        if (substr($tagBody, 0, 1) == '{')
            $tagBody = substr($tagBody, 1, (strlen($tagBody) - 2));

        // this not includes a value with spaces inside as we will be passing mostly variables here
        $args = self::joinStrings(explode(' ', $tagBody), ' ');

        $argsAssoc = array();

        foreach ($args as $arg)
        {
            $equalsPos = strpos($arg, '=');

            if ($equalsPos === false)
                continue;

            $value = substr($arg, ($equalsPos + 2), strlen($arg));
            $argsAssoc[trim(substr($arg, 0, $equalsPos))] = substr($value, 0, (strlen($value) - 1));
        }

        return $argsAssoc;
    }

    /**
     * Parse variables {$var}
     *
     * Example input:
     * {$test}
     * {$test|trim}
     * {$test|str_replace:"a":"b"|trim|ucfirst}
     *
     * @param $tagData
     * @param $part
     * @param $tag
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return null
     */
    protected function variableBlockParser(&$tagData, &$part, &$tag)
    {
        if (substr($part, 0, 2) !== '{$')
            return false;

        $var = substr($part, 1, (strlen($part) - 2));

        // check if variable is begin assigned
        $equalsPos = strpos($part, '=');
        $firstModificator = strpos($part, "|");

        if ($equalsPos !== false && ($firstModificator === false || $equalsPos < $firstModificator))
        {
            $part = "<?php " . $this->parseModifiers($var, true) . ";?>";
            return true;
        }

        // variables substitution (eg. {$title})
        $part = "<?php echo " . $this->parseModifiers($var, true) . ";?>";
        return true;
    }

    /**
     * Parse a simple constant tag {#CONSTANT_NAME#}
     *
     * @param $tagData
     * @param $part
     * @param $tag
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return bool
     */
    protected function constantBlockParser(&$tagData, &$part, &$tag)
    {
        if (substr($part, 0, 2) !== '{#')
            return false;

        $constantName = $result = trim(substr($part, 2, -2));
        $hasModifiers = strpos($constantName, '|');

        if ($hasModifiers !== false)
        {
            $constantName = substr($constantName, 0, $hasModifiers);
            $result = $this->parseModifiers($result);
        }

        $part = '<?php if(defined("' .$constantName. '")){echo ' .$result. ';};?>';
        return true;
    }

    /**
     * {capture} tag block parser
     *
     * Examples:
     * {capture name="test"}This is a test HTML part of code{/capture}
     * {capture assign="test"}This is a test HTML part of code{/capture}
     * {capture append="test"}, and this text was appended to existing capture{/capture}
     *
     * Test this example with: {var_dump($test)}
     *
     * @param $tagData
     * @param $part
     * @param $tag
     * @param $templateFilePath
     * @param $blockIndex
     * @param $blockPositions
     * @param $code
     * @param $passAllBlocksTo
     * @throws SyntaxException
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return bool
     */
    protected function captureBlockParser(&$tagData, &$part, &$tag, $templateFilePath, $blockIndex, $blockPositions, $code, &$passAllBlocksTo, $lowerPart)
    {
        $char = '';
        $ending = $this->parseTagEnding($lowerPart, array(
            'capture',
            'print',
            'autoescape',
        ));

        $tagRealName = self::strposa($lowerPart, array(
            '{capture',
            '{print',
            '{autoescape',
        ), 0, 'min', $char);

        if ($tagRealName === false && !$ending)
            return false;

        /**
         * {/capture} => ob_get_clean();
         */
        if ($ending)
        {
            if ($tagData['level'] < 1)
            {
                $context = $this->findLine($blockIndex, $blockPositions, $code);
                throw new SyntaxException('{capture} tag closed before it was opened, in "' .$templateFilePath. '" on line ' .$context['line']. ', offset ' .$context['offset'], 5, null, $context['line'], $templateFilePath);
            }

            $body = 'ob_get_clean()';

            /**
             * Support for filter argument and it's arguments
             *
             * Examples:
             * {capture name="test" filter="str_replace" arg1="replacement-string"}...{/capture}
             * {capture name="test" filter="trim"}
             */
            if (isset($tagData['filters']))
            {
                $filtersCount = count($tagData['filters']);
                $filterStartingBody = '';
                $filterEndingBody = '';

                foreach (array_reverse($tagData['filters']) as $filter)
                {
                    $filterStartingBody .= $filter['value'] . '(';
                }

                foreach ($tagData['filters'] as $filter)
                {
                    foreach ($filter['arguments'] as $argument)
                    {
                        $filterEndingBody .= ',"' .$argument. '"';
                    }

                    $filterEndingBody .= ')';
                }

                $body = $filterStartingBody . $body . $filterEndingBody;
            }

            // allow automaticaly printing block right after running filters and assigning to a variable
            if (isset($tagData['print']) && $tagData['print'])
                $body .= ';echo $' .$tagData['assign'];

            $tagData['level']--;
            $part = '<?php $' .$tagData['assign'] . $tagData['operator']. $body. ';?>';

            // clean up
            unset($tagData['assign']); unset($tagData['operator']);
            if (isset($tagData['filters'])) unset($tagData['filters']);
            if (isset($tagData['operator'])) unset($tagData['operator']);
            if (isset($tagData['print'])) unset($tagData['print']);
            return true;
        }

        $arguments = self::parseTagArguments($part);
        $tagData['operator'] = '=';

        /**
         * Filtering mode support - previously known as {autoescape}
         */
        $filters = array();

        if ($arguments)
        {
            foreach ($arguments as $argumentName => $value)
            {
                $argumentName = strtolower($argumentName);

                if (strpos($argumentName, 'filter') === 0)
                {
                    $argNamePos = self::strposa($argumentName, array('arg', 'argument'));

                    /**
                     * Save filter's arguments
                     */
                    if ($argNamePos !== false)
                    {
                        $tmpFilterName = substr($argumentName, 0, $argNamePos);

                        if (!isset($filters[$tmpFilterName]))
                            $filters[$tmpFilterName] = array();

                        $filters[$tmpFilterName][$argumentName] = $value;

                    } else {
                        /**
                         * Save filters list
                         */
                        $tagData['filters'][$argumentName] = array(
                            'value' => $value,
                            'arguments' => array(),
                        );
                    }
                }
            }

            if (isset($tagData['filters']) && $tagData['filters'])
            {
                foreach ($filters as $filterName => $value)
                {
                    $tagData['filters'][$filterName]['arguments'] = $value;
                }
            }
        }

        /**
         * For {print} and {autoescape} tags it's not necessary to specify an assign/name/append argument
         */
        if ((!isset($arguments['assign']) && !isset($arguments['append']) && !isset($arguments['name'])) && ($char === '{print' || $char === '{autoescape'))
        {
            $arguments['assign'] = 'capture' .($tagData['level'] + 1);
            $arguments['print'] = 1;
        }

        /**
         * Directly print block text
         */
        if (isset($arguments['print']) && (strtolower($arguments['print']) == 'true' || intval($arguments['print'])))
        {
            $tagData['print'] = true;
        }

        /**
         * Look for name, assign or append tag
         * {capture name="asd"}
         * {capture assign="asd"}
         * {capture append="asd"}
         */
        if (isset($arguments['assign']))
            $tagData['assign'] = $arguments['assign'];
        elseif (isset($arguments['name']))
            $tagData['assign'] = $arguments['name'];
        elseif (isset($arguments['append'])) {
            $tagData['assign'] = $arguments['append'];
            $tagData['operator'] = '.=';
        } else {
            $context = $this->findLine($blockIndex, $blockPositions, $code);
            throw new SyntaxException('{assign} tag requires at least one of properties to be used: assign, name or append. In "' .$templateFilePath. '" on line ' .$context['line']. ', offset ' .$context['offset'], 6, null, $context['line'], $templateFilePath);
        }

        $tagData['count']++;
        $tagData['level']++;
        $part = '<?php ob_start();?>';
        return true;
    }

    /**
     * {block} tag parser
     *
     * @param $tagData
     * @param $part
     * @param $tag
     * @param $templateFilePath
     * @param $blockIndex
     * @param $blockPositions
     * @param $code
     * @param $passAllBlocksTo
     * @param $lowerPart
     * @param $codeSplit
     *
     * @throws SyntaxException
     * @author Damian Kęska <damian@pantheraframework.org>
     *
     * @return bool
     */
    protected function blockBlockParser(&$tagData, &$part, &$tag, $templateFilePath, $blockIndex, $blockPositions, $code, &$passAllBlocksTo, $lowerPart, &$codeSplit)
    {
        $ending = $this->parseTagEnding($lowerPart, 'block');

        if (substr($lowerPart, 0, 6) !== '{block' && !$ending)
            return false;

        if ($ending)
        {
            // if tag body is empty eg. {block name="testBlockName"}{/block} then just call existing block
            $startPos = explode('|', $blockPositions[$tagData['lastOpenIndex']]);
            $endPos = explode('|', $blockPositions[$blockIndex]);
            $codeBetween = substr($code, ($startPos[0] + $startPos[1]), (($endPos[0] - $startPos[0]) - $startPos[1]));

            if (!strlen(trim($codeBetween)))
            {
                $codeSplit[$tagData['lastOpenIndex']] = '';
                $part = '<?php if(isset($this->definedBlocks["' .$tagData['args']['name']. '"])){echo $this->definedBlocks["' .$tagData['args']['name']. '"](' .var_export($tagData['args'], true). ');}?>';
                return true;
            }

            $part = '<?php };}';
            $isExtended = isset($this->tagData['include']) && isset($this->tagData['include']['extends']) &&  $this->tagData['include']['extends'];

            if (!$isExtended && (!isset($tagData['args']['quiet']) || $tagData['args']['quiet'] != 'true'))
                $part .= 'echo $this->definedBlocks["' .$tagData['args']['name']. '"](' .var_export($tagData['args'], true). ');';

            $part .= '?>';
            return true;

        } else {
            $args = self::parseTagArguments($part);

            if (!isset($args['name']) || !$args['name'])
            {
                $context = $this->findLine($blockIndex, $blockPositions, $code);
                throw new SyntaxException('{block} tag requires "name" argument, in file "' .$templateFilePath. '" on line ' .$context['line']. ', offset ' .$context['offset'], 7, null, $context['line'], $templateFilePath);
            }

            $tagData['args'] = $args;
            $tagData['lastOpenIndex'] = $blockIndex;

            $part = '<?php if(!isset($this->definedBlocks["' .$args['name']. '"])){$this->definedBlocks["' .$args['name']. '"]=function($args){$_blockMeta = ' .var_export($args, true). ' + $args;?>';
            return true;
        }
    }

    /**
     * {if} code block parser
     *
     * Examples:
     * {if="$test > 5"} then here{/if}
     * {if $test > 5} then something here{/if}
     *
     * @param $tagData
     * @param $part
     * @param $tag
     *
     * @regex /{if="([^"]*)"}/
     * @regex ({if.*?})
     * @author Damian Kęska <damian@pantheraframework.org>
     *
     * @return null|bool
     */
    protected function ifBlockParser(&$tagData, &$part, &$tag, $templateFilePath, $blockIndex, $blockPositions, $code, &$passAllBlocksTo, $lowerPart)
    {
        $ending = $this->parseTagEnding($lowerPart, array(
            'if', 'elseif'
        ));

        /**
         * {/if} - closing
         */
        if ($ending === true)
        {
            $tagData['level']--;
            $part = '<?php }?>';
            return true;
        }

        /**
         * {if} - opening
         */
        $tagType = substr($lowerPart, 0, 4);
        $elseTagType = substr($lowerPart, 0, 8);

        if ($tagType === '{if=')
        {
            $type = 'if';
            $posX = 2; // include =" at beginning
            $posY = 2; // and " at ending
            $len = 3;

        } elseif ($tagType === '{if ') {
            $type = 'if';
            $posX = 1;
            $posY = 1;
            $len = 3;

        } elseif ($elseTagType === '{elseif=') {
            $type = '}elseif';
            $posX = 2;
            $posY = 2;
            $len = 7;

        } elseif ($elseTagType === '{elseif ') {
            $type = '}elseif';
            $posX = 1;
            $posY = 1;
            $len = 7;

        } else
            return false;


        if ($type === 'if')
        {
            $tagData['level']++;
            $tagData['count']++;
        }

		$body = $this->varReplace(substr($part, $len + $posX, (strlen($part) - ($posY + $len + $posX))), $this->tagData['loop']['level'], $escape = FALSE);
		$body = $this->parseStrings($body, $blockIndex, $blockPositions, $code, $templateFilePath);

        $part = '<?php ' .$type. '(' .$body. '){?>';
        return true;
    }


	/**
	 * Find all strings in the code and replace modifiers
	 *
	 * @param string $body Input code
	 * @param string $blockIndex
	 * @param string $blockPositions
	 * @param string $code
	 * @param string $templateFilePath
	 *
	 * @throws SyntaxException
	 * @return string
	 */
	public function parseStrings($body, $blockIndex = '', $blockPositions = '', $code = '', $templateFilePath = '')
	{
		$quotePos = 0;

		// let's search for a pair of quotes, $quotePos and $endingQuotePos
		do
		{
            // include paths applied in this loop in previous iterations
            if ($quotePos > strlen($body))
                break;

			$char = null;
			$quotePos = self::strposa($body, array('"', "'"), $quotePos, 'min', $char);

			if ($quotePos === false)
				break;

            $endingQuotePos = self::strposa($body, array($char), ($quotePos + 1));

			if ($endingQuotePos === false)
			{
				$context = $this->findLine($blockIndex, $blockPositions, $code);
				throw new SyntaxException('Unclosed ' . $char . ' quote string in fragment "' . $body . '"', 64, null, $context['line'], $templateFilePath);
			}

            $quotesContent = substr($body, $quotePos, (($endingQuotePos - $quotePos) + 1));

            /**
             * In operator support for strings
             */
            if (stripos($body, $quotesContent. ' in ') !== false)
            {
                // $quotesContent + " in "
                $inChar = substr($body, $endingQuotePos + 5, 1);

                /**
                 * Detect haystack type - string or variable
                 */
                if ($inChar == '"' or $inChar == "'")
                {
                    $haystackClosing = strpos($body, $inChar, $endingQuotePos + 6);

                } elseif ($inChar == '$') {

                    $haystackClosing = self::strposaNotInQuotes($body, array(
                        ' ', '}', '&', '||', '(', ')',
                    ), $endingQuotePos + 6);

                } else {
                    $context = $this->findLine($blockIndex, $blockPositions, $code);
                    throw new SyntaxException('Invalid syntax, expected $ or quote for "in" syntax, found in "' .$code. '"', 65, null, $context['line'], $templateFilePath);
                }

                // if the closing position is at the end of the string
                if ($haystackClosing === false)
                    $haystackClosing = strlen($body);

                $haystack = substr($body, $endingQuotePos + 5, ($haystackClosing - ($endingQuotePos + 4)));

                if ($inChar == '$')
                {
                    $haystack = $this->varReplace($haystack, 'auto', false);
                }

                $replacement = '($this->modifiers["in"](' .$quotesContent. ', ' .$haystack. '))';
                $body = substr_replace($body, $replacement, $quotePos, (($haystackClosing - $quotePos) + 1));

                // include difference made by substr_replace
                // $replacement - new
                // ($haystackClosing - $quotePos) - old content
                $quotePos = $endingQuotePos = ($quotePos + strlen($replacement));
            }

			/**
			 * Modificators support
			 *
			 * "dupa"|in:$array and 5 > 6 and "te|st" != "test123 |"
			 */
			$modificatorPos = strpos($body, '|', $endingQuotePos); // NOTE: this does not means that it's this string modificator

			if ($modificatorPos !== false)
			{
				// if "|" character found right after quotes
				if ($modificatorPos === ($endingQuotePos + 1))
				{
					$space = '';
					$spaceSize = 1;
				}
				else
				{
					// check if space between quotes and modificator is empty (could only include spaces)
					$spaceSize = (($modificatorPos) - $endingQuotePos); // "this is a test"______|modificator # space means that "____" characters, we could have spaces here
					$space = trim(substr($body, ($endingQuotePos + 1), ($spaceSize - 1)));
				}

				if (!$space) // check if our space contains only blank spaces
				{
					// {"test"|replace:"aaa":"bbb ccc"|| 1 > 2}
					// {"test"|replace:"aaa":"bbb)ccc" and (1 > 2)}
					// {"test"|replace:"aaa":"bbbccc" and true != false}

					$endingChar = self::strposaNotInQuotes($body, array(
						' ', '(', ')', '}', '&', '||',
					), $modificatorPos);

					// if we don't have space at the end, but maybe a operator, closing character etc.
					if ($endingChar === false)
					{
						$endingChar = strlen($body);
					}

					$modificator = substr($body, $quotePos, ($endingChar - $quotePos));
                    $replacement = $this->parseModifiers($modificator, false);
					$body = substr_replace($body, $replacement, $quotePos, ($endingChar - $quotePos));

                    // include substr_replace difference
                    $quotePos = $endingQuotePos = ($quotePos + strlen($replacement));
				}
			}

			// move outside of our position to find next occurrences
			$quotePos = $endingQuotePos + 1;

		} while ($quotePos !== false);

		return $body;
	}

	/**
	 * Find characters that are NOT placed inside of quotes
	 *
	 * @param string $haystack Base string
	 * @param string|array $needle Character/string list
	 * @param int $pos Starting position
	 * @param bool $multiple Search for multiple occurrences
	 *
	 * @author Damian Kęska <damian.keska@fingo.pl>
	 * @return bool|int
	 */
	public static function strposaNotInQuotes($haystack, $needle, $pos = 0, $multiple = false)
	{
		$allQuotes = self::getQuotesPositions($haystack);
		$multipleResults = array();

		while (true)
		{
			$char = null;
			$pos = self::strposa($haystack, $needle, $pos, 'min', $char);

			if ($pos === false)
			{
				break;
			}

			if (!self::isInQuotes($allQuotes, $pos, strlen($char)))
			{
				if ($multiple)
					$multipleResults[] = $pos;
				else
					return $pos;
			}

			$pos++;
		}

		if ($multiple)
			return $multipleResults;

		return false;
	}


    /**
     * {else} instruction, could be used only inside of {if} block
     *
     * @param $tagData
     * @param $part
     * @param $tag
     *
     * @throws SyntaxException
     * @author Damian Kęska <damian@pantheraframework.org>
	 *
     * @return null|void
     */
    protected function elseBlockParser(&$tagData, &$part, &$tag, $templateFilePath, $blockIndex, $blockPositions, $code)
    {
        $p = strtolower($part);

        if ($p === '{else}')
        {
            if ((isset($this->tagData['if']['level']) || $this->tagData['if']['level'] < 1) && (isset($this->tagData['loop']['level']) || $this->tagData['loop']['level'] < 1))
            {
                $context = $this->findLine($blockIndex, $blockPositions, $code);
                $e = new SyntaxException('Trying to use {else} outside of a loop', 3, null, $context['line'], $templateFilePath);
            }

            $part = '<?php }else{?>';
            return true;
        }

        return null;
    }

    /**
     * Parse a template comment - {*}, {/*}, {*, *}, {ignore}, {/ignore}
     *
     * @param $tagData
     * @param $part
     * @param $tag
     * @param $templateFilePath
     * @param $blockIndex
     * @param $blockPositions
     * @param $code
     * @param $passAllBlocksTo
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return bool
     */
    protected function commentBlockParser(&$tagData, &$part, &$tag, $templateFilePath, $blockIndex, $blockPositions, $code, &$passAllBlocksTo, $lowerPart)
    {
        if (substr($lowerPart, 0, 2) === '{*' || $lowerPart === '{*}' || $lowerPart === '{ignore}') {
            $tagData['level']++;
            $tagData['count']++;
            $passAllBlocksTo = 'comment';
            $part = '';

            $asd1 = substr($lowerPart, -2);
            $asd2 = strlen($lowerPart);

            if (substr($lowerPart, -2) === '*}' && strlen($lowerPart) !== 3)
                $passAllBlocksTo = '';

            return true;
        } elseif (substr($lowerPart, -2) === '*}' || $lowerPart === '{/*}' || $lowerPart === '{/ignore}')
        {
            $tagData['level']--;
            $passAllBlocksTo = '';
            $part = '';
            return true;

        }

        // erase all inside a comment block
        else if ($passAllBlocksTo === 'comment' && $tagData['level'] > 0)
        {
            $part = '';
            return true;
        }

        return null;
    }

    /**
     * Parse template marks
     *
     * Example:
     * {goto a}
     * {mark a}
     *
     * @param $tagData
     * @param $part
     * @param $tag
     * @param $templateFilePath
     * @param $blockIndex
     * @param $blockPositions
     * @param $code
     * @param $passAllBlocksTo
     * @param $lowerPart
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return bool
     */
    public function markBlockParser(&$tagData, &$part, &$tag, $templateFilePath, $blockIndex, $blockPositions, $code, &$passAllBlocksTo, $lowerPart)
    {
        $zeroFive = substr($lowerPart, 0, 5);

        if ($zeroFive == '{mark')
        {
            $part = "<?php " .trim(substr($lowerPart, 5, -1)). ":?>";
            return true;
        } elseif ($zeroFive == '{goto') {
            $part = '<?php goto ' .trim(substr($lowerPart, 5, -1)). ';?>';
            return true;
        }

        return false;
    }

    /**
     * Code parsing ignore block {noparse}, {literal}
     *
     * @param $tagData
     * @param $part
     * @param $tag
     * @param $templateFilePath
     * @param $blockIndex
     * @param $blockPositions
     * @param $code
     * @param $passAllBlocksTo
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return bool
     */
    protected function noparseBlockParser(&$tagData, &$part, &$tag, $templateFilePath, $blockIndex, $blockPositions, $code, &$passAllBlocksTo, $lowerPart)
    {
        if ($lowerPart == '{/noparse}' || $lowerPart == '{/literal}')
        {
            $tagData['level']--;
            $passAllBlocksTo = '';
            $part = '';
            return true;
        }

        elseif ($lowerPart == '{noparse}' || $lowerPart == '{literal}')
        {
            $tagData['level']++;
            $tagData['count']++;
            $passAllBlocksTo = 'noparse';
            $part = '';
            return true;
        }

        // inside {noparse} ... {/noparse} block ignore all data
        else if ($passAllBlocksTo === 'noparse' && $tagData['level'] > 0)
            return true;
    }

    /**
     * {function} instruction, handles also strings and it's modificators.
	 *
	 * Features:
	 * - Raw function calls eg. {var_dump($a)}
	 * - Modificators support {test()|trim}
	 * - Ternary operator {test() ? $a : b}
     *
     * Examples:
     * {function="test()"}
     * {test()}
     * {test()|trim}
     * {"TEST"|strtolower}
     *
     * @param array $tagData
     * @param string $part
     * @param string $tag
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return null|void
     */
    protected function functionBlockParser(&$tagData, &$part, &$tag, $templateFilePath, $blockIndex, $blockPositions, $code, &$passAllBlocksTo, $lowerPart)
    {
		$isString = in_array(substr($part, 1, 1), array("'", '"'));

		// Exception: isset is a part of PHP syntax, so it's not detected as callable
		$isDirectFunction = substr($part, 1, strpos($part, '(') - 1);
        $isDirectFunction = (is_callable($isDirectFunction) || $isDirectFunction === 'isset');

        if (substr(strtolower($part), 0, 9) !== '{function' && !$isDirectFunction && !$isString)
            return false;

		$ternary = '';

        if ($isDirectFunction || $isString)
        {
			// {function()}
            $function = substr($part, 1, strlen($part) - 2);

			$ternaryStarts = self::strposa($function, array(
				'?"', '? "', "?'", "? '",
			));

			/**
			 * Ternary operator support for functions
			 *
			 * Example:
			 * {test() ? $a : $b}
			 */
			if ($ternaryStarts !== false)
			{
				$body = $function;
				$function = substr($function, 0, $ternaryStarts);
				$ternary = substr($body, $ternaryStarts);
			}

            // integration with embedded JSON in Javascript eg. $(this).css({'color': '#343e4a'});
            $char = '';
            $quote = self::strposa(trim($function), array('"', "'"), 1, null, $char);

            if ($quote !== false && substr(str_replace(' ', '', $function), $quote + 1, 1) == ':')
                return true;

        } else {
			// {function="..."}
            $count = 2;

            if (substr($part, -2) !== '"}' && substr($part, -2) !== "'}")
                $count = 1;

            // get function
            $function = str_replace(')"|', ')|', substr($part, 11, ((strlen($part) - 11) - $count)));

            // create function from string - eg. {function="time"} => {function="time()"}
            if (strpos($function, '(') === false)
                $function .= '()';
        }

        // for {"string"|test} syntax there is simpler way
        if ($isString)
            $body = $this->parseStrings($function, $blockIndex, $blockPositions, $code, $templateFilePath);
        else
            $body = $this->varReplace($function, 'auto', false, false, true);

        // function
        $part = "<?php echo ".$body. $ternary . ";?>";
    }

    /**
     * {include}, {extends} block parser
     *
     * @param $tagData
     * @param $part
     * @param $tag
     * @param $templateFilePath
     * @param $blockIndex
     * @param $blockPositions
     * @param $code
     *
     * @throws NotFoundException
     * @throws SyntaxException
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return bool
     */
    public function includeBlockParser(&$tagData, &$part, &$tag, $templateFilePath, $blockIndex, $blockPositions, $code, &$passAllBlocksTo, $lowerPart, &$codeSplit, &$templateEnding)
    {
        $pTag = substr($lowerPart, 0, 8);

        if($pTag !== '{include' && $pTag !== '{extends')
            return false;

        $tagBody = substr($part, 8, strlen($part) - 9);

        // {include="/path/to/template/file"}
        if (substr($tagBody, 0, 1) == '=')
            $includeTemplate = trim(substr($tagBody, 1, strlen($tagBody) - 1), '"' . "'");
        else {
            $args = self::parseTagArguments($tagBody);

            // smarty-like {include file="/path/to/template/file"}
            if (isset($args['file']))
                $includeTemplate = $args['file'];
            elseif (isset($args['path']))
                $includeTemplate = $args['path'];
            else {

                // {include test.tpl}, {include "test.tpl"}
                if (self::strposa($tagBody, array('"', "'", '/', '.tpl')) !== false)
                    $includeTemplate = trim($tagBody, '"\' ');
                else {
                    $context = $this->findLine($blockIndex, $blockPositions, $code);
                    throw new SyntaxException('Cannot find path attribute for {include} tag, expecting "file" or "path" attribute. Example: {include file="/path/to/file"}', 4, null, $context['line'], $templateFilePath);
                }
            }
        }

        // resolved path
        $path = '';
        $context = $this->findLine($blockIndex, $blockPositions, $code);
        $found = false;

        // select in all include paths
        if ($this->getConfigurationKey('tpl_dir'))
        {
            if (substr($includeTemplate, 0, 1) == '$' || defined($includeTemplate))
            {
                $part = '<?php require $this->checkTemplate(' . $includeTemplate . ', "' .$templateFilePath. '", ' .intval($context['line']). ', ' .intval($context['offset']). ');?>';
                $found = true;
            } else {
                $part = '<?php require $this->checkTemplate("' . $includeTemplate . '", "' .$templateFilePath. '", ' .intval($context['line']). ', ' .intval($context['offset']). ');?>';
                $found = true;
            }

            if ($found)
            {
                if ($pTag === '{extends')
                {
                    $templateEnding .= $part;
                    $part = '';

                    // add to counter
                    if (!isset($tagData['extends'])) $tagData['extends'] = 0;
                    $tagData['extends']++;
                }

                return true;
            }

            $context = $this->findLine($blockIndex, $blockPositions, $code);
            throw new NotFoundException('Cannot find template "' . $includeTemplate . '" that was tried to be included from ' .$templateFilePath. ' at line ' .$context['line']. ', offset ' .$context['offset']);
        } else
            throw new \InvalidArgumentException('tpl_dir not specified in configuration. Please set include paths by using setConfigurationKey() method');

        return false;
    }

    /**
     * {loop} and {foreach}
     *
     * Usage examples:
     * {loop="$fromVariable" as $key => $value}
     * {loop="$fromVariable"}
     * {foreach="$fromVariable" as $key => $value}
     * {foreach="$fromVariable"}
     * {foreach from="$fromVariable" item="i" key="k"}
     *
     * @param $tagData
     * @param $part
     * @param $tag
     *
     * @throws SyntaxException
     * @author Damian Kęska <damian@pantheraframework.org>
     * @author Mateusz Warzyński <lxnmen@gmail.com>
     * @return null
     */
    protected function loopBlockParser(&$tagData, &$part, &$tag, $templateFilePath, $blockIndex, $blockPositions, $code, &$passAllBlocksTo, $lowerPart)
    {
        $ending = $this->parseTagEnding($lowerPart, array(
            'loop', 'foreach',
        ));

        /**
         * Previous it was loop_close
         *
         * @keywords loop_close, {/loop}, {/foreach}
         */
        if ($ending === true)
        {
            $tagData['level']--;
            $part = '<?php }?>';
            return true;
        }

        // validate if its a {loop} or {foreach} tag
        if (substr($part, 0, 5) != '{loop' && substr($part, 0, 8) != '{foreach')
            return false;

        // increase the loop counter
        $tagData['count']++;
        $tagData['level']++;

        $arguments = self::parseTagArguments($part);
        $var = null;

        // "from"
        if (isset($arguments['from']))
            $var = $arguments['from'];
        elseif (isset($arguments['foreach']))
            $var = $arguments['foreach'];
        else
            $var = $arguments['loop'];

        if (!$var)
            throw new SyntaxException("Syntax error in foreach/loop, there is no array given to iterate on. Code: " . $part, 6, null);

        // prefix, example: $value1, $value2 etc. by default should be just $value
        $valuesPrefix = intval($tagData['level']);

        // replace array modificators eg. $array.test to $array["test"]
        $newvar = $this->varReplace($var, ($valuesPrefix - 1), false, false, true);

        // loop variables
        $counter = "\$counter".$valuesPrefix;

        // RainTPL3/PHP syntax support: {loop="$array" as $key => $value} (only if there are no key and value arguments - to gain performance)
        if (!isset($arguments['key']) && !isset($arguments['value']) && !isset($arguments['item']))
        {
            $asSyntax = strpos($part, 'as $');

            if ($asSyntax !== false)
            {
                // the key is between "as $" and "=>"
                $keyEnds = self::strposa($part, array(
                    '=>',
                    ' ',
                ), ($asSyntax + 4));

                if ($keyEnds !== false)
                {
                    $arguments['key'] = trim(substr($part, ($asSyntax+4), ($keyEnds-$asSyntax) - 4));

                    // between: $ and [space] or } (tag ending or arguments separator)
                    $valueStarts = strpos($part, '$', $keyEnds);

                    if ($valueStarts !== false)
                    {
                        $valueEnds = self::strposa($part, array(' ', '}'), $valueStarts);
                        $arguments['value'] = substr($part, ($valueStarts + 1), ($valueEnds - $valueStarts) - 1);
                    } else {

                        // {foreach from="item" as $value}
                        $arguments['value'] = $arguments['key'];
                        unset($arguments['key']);
                    }
                }
            }
        }

        // RainTPL3/PHP syntax support: {loop="$array" as $value} (only if there are no key and value arguments - to gain performance)
        if (!isset($arguments['key']) && !isset($arguments['value']) && !isset($arguments['item']))
        {
            $asSyntax = strpos($part, 'as $');

            if ($asSyntax !== false)
            {
                // the key is between "as $" and "}"
                $keyEnds = self::strposa($part, array(
                    ' ',
                    '}'
                ), ($asSyntax + 4));

                if ($keyEnds !== false)
                {
                    $arguments['value'] = trim(substr($part, ($asSyntax+4), ($keyEnds-$asSyntax) - 4));
                }
            }
        }

        // key
        if (isset($arguments['key']) && $arguments['key'])
            $key = "\$".$arguments['key'];
        else
            $key = "\$key".$valuesPrefix;

        if (isset($arguments['value']))
            $value = "\$".$arguments['value'];
        elseif (isset($arguments['item']))
            $value = "\$".$arguments['item'];
        else
            $value = "\$value".$valuesPrefix;

        // result code passed by reference
        $part = "<?php $counter=-1; \$newVar=".$newvar."; if(isset(\$newVar)&&(is_array(\$newVar)||\$newVar instanceof Traversable)&& sizeof(\$newVar))foreach(\$newVar as ".$key." => ".$value."){ $counter++; ?>";
    }

    /**
     * {break} and {continue} instructions, could be used only inside of {foreach} or {loop} blocks
     *
     * @param $tagData
     * @param $part
     * @param $tag
     *
     * @throws SyntaxException
     * @author Damian Kęska <damian@pantheraframework.org>
     * @author Mateusz Warzyński <lxnmen@gmail.com>
     * @return null|void
     */
    protected function loop_breakBlockParser(&$tagData, &$part, &$tag, $templateFilePath, $blockIndex, $blockPositions, $code, &$passAllBlocksTo, $lowerPart)
    {
        $lowerPart = str_replace(" ", '', $lowerPart);

        $recognizedSyntax = null;

        if (substr($lowerPart, 0, 6) == '{break')
        {
            $recognizedSyntax = 'break';
            $syntaxLength = 6;
        } elseif (substr($lowerPart, 0, 9) == '{continue') {
            $recognizedSyntax = 'continue';
            $syntaxLength = 9;
        }

        if ($recognizedSyntax)
        {
            if (!isset($this->tagData['loop']['level']) || $this->tagData['loop']['level'] < 1)
            {
                $context = $this->findLine($blockIndex, $blockPositions, $code);
                throw new SyntaxException('Trying to use {break} outside of a loop', $syntaxLength, null, $context['line'], $templateFilePath);
            }

            $endChar = strpos($lowerPart, "}");

            if ($endChar === $syntaxLength)
            {
                $part = '<?php ' .$recognizedSyntax. ';?>';
            }
            elseif ($endChar > $syntaxLength)
            {
                $breakLevel = intval(substr($lowerPart, $syntaxLength, strlen($lowerPart)-($syntaxLength + 1)));

                if (!$breakLevel)
                {
                    $context = $this->findLine($blockIndex, $blockPositions, $code);
                    throw new SyntaxException('Trying to use {' .$recognizedSyntax. ' $level} with non-numerical level value', $syntaxLength, null, $context['line'], $templateFilePath);
                }
                elseif ($breakLevel > $this->tagData['loop']['level'])
                {
                    $context = $this->findLine($blockIndex, $blockPositions, $code);
                    throw new SyntaxException('Trying to {' .$recognizedSyntax. '} too many loops that does not exists', $syntaxLength, null, $context['line'], $templateFilePath);
                }

                $part = '<?php ' .$recognizedSyntax. ' '.strval($breakLevel).';?>';
            }
            else {
                $context = $this->findLine($blockIndex, $blockPositions, $code);
                throw new SyntaxException('Trying to use {' .$recognizedSyntax. '} in invalid way - cannot find ending char `}`', $syntaxLength, null, $context['line'], $templateFilePath);
            }
        }
    }

    /**
     * Implements possibility to create regexp tag parsers
     *
     * @param $tagData
     * @param $part
     * @param $tag
     * @param $templateFilePath
     * @param $blockIndex
     * @param $blockPositions
     * @param $code
     * @param $passAllBlocksTo
     * @param $lowerPart
     *
     * @author Damian Kęska <damian.keska@fingo.pl>
     * @return bool|null
     */
    protected function customTagsBlockParser(&$tagData, &$part, &$tag, $templateFilePath, $blockIndex, $blockPositions, $code, &$passAllBlocksTo, $lowerPart)
    {
        foreach ($this->registeredTags as $pattern => $customTag)
        {
            $matches = null;
            preg_match($customTag['parse'], $part, $matches);

            if ($matches && is_callable($customTag['function']))
            {
                $return = $customTag['function']($tagData, $part, $tag, $templateFilePath, $blockIndex, $blockPositions, $code, $passAllBlocksTo, $lowerPart, $matches);

                if ($return === true)
                    return true;
            }
        }

        return null;
    }

    /**
     * Fix array after using explode, connect broken strings syntax
     *
     * @param array $array Input array
     * @param string $delimiter Delimiter string
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return array
     */
    public static function joinStrings(array $array, $delimiter = ' ')
    {
        /*$stringSyntax = array(
            '"' => 0,
            "'" => 0,
            //'`' => 0,
        );*/

        $openPos = 0;
        $open = false;
        $newArray = array();

        foreach ($array as $key => $value)
        {
            $index = $key;
            //var_dump($value . ' = ' .count(self::strposAll($value, '"')));

            $matches = count(self::strposAll($value, '"'), COUNT_RECURSIVE);

            // (no string syntax || is string syntax && not in pair) && we are not iterating first element in array
            if ($key > 0 && $open)
            {
                $index = $openPos;
            }

            if ($matches && $matches % 2)
            {
                $open = !$open;

                // on $openPos there is stored beginging position of string syntax
                if ($open)
                {
                    $openPos = $key;
                }
            }

            if (isset($newArray[$index]))
                $newArray[$index] .= $delimiter . $value;
            else
                $newArray[$index] = $value;
        }

        return $newArray;
    }

	/**
	 * Parse modifiers on a string or variable, function
	 *
	 * @param string $var Variable/string/function input string
	 * @param bool $useVarReplace
	 *
	 * @return string Output
	 * @author Damian Kęska <damian@pantheraframework.org>
	 */
    public function parseModifiers($var, $useVarReplace = false)
    {
        $functions = explode('|', $var);
        $result = $functions[0];

        if ($useVarReplace === true)
            $result = $functions[0] = $this->varReplace($result, $this->tagData['loop']['level'], true, false, false);

        foreach ($functions as $function)
        {
            if ($function === $result)
                continue;

            // arguments
            $args = explode(':', str_replace('::', '\@;;', $function));

            // our result string
            if (isset($this->modifiers[$args[0]]))
                $args[0] = '$this->modifiers["' .$args[0]. '"]';

            $result = $args[0]. '(' .$result;

            foreach ($args as $i => $arg)
            {
                if ($i === 0)
                    continue;

				/**
				 * Extract variable from string
				 */
				if (strlen($arg) > 2 && $arg[1] == '$')
				{
					$arg = substr($arg, 1, strlen($arg) - 2);
				}

                $result .= ', ' .str_replace('\@;;', '::', $arg);
            }

            $result .= ')';
        }

        return $result;
    }

    /**
     * Strpos function that handles list of needles
     *
     * @param string $haystack Input string
     * @param array $needles List of needles in array
     * @param int $offset (Optional) Offset we are starting from
     * @param callable $f (Optional) Function that selects best option (Defaults to min)
     * @param string &$char (Optional) Here could be set a best found result
     * @param array &$chr (Optional) List of found needles
     *
     * @author Damian Kęska <damian.keska@fingo.pl>
     * @return bool|int
     */
    public static function strposa($haystack, $needles = array(), $offset = 0, $f = 'min', &$char = null, &$chr = null)
    {
        if ($f === null) $f = 'min';

        $chr = array();
        $chrPos = array();
        $haystackLen = strlen($haystack);

        foreach($needles as $needle)
        {
            // ignore escaped characters
            if (strlen($needle) === 1)
            {
                $from = $offset;

                while (true)
                {
                    $res = strpos($haystack, $needle, $from);

                    // found / not found / out of range
                    if ($res === 0 || $res === false || $from + 1 > $haystackLen)
                        break;

                    if ($res > 0)
                    {
                        $from = $res + 1;

                        // if escaped character not found then current position would be ok, if not search for next
                        if ($haystack[$res - 1] != "\\")
                            break;
                    }
                }

            } else
                $res = strpos($haystack, $needle, $offset);

            if ($res !== false) {$chr[$needle] = $res; $chrPos[$res] = $needle;};
        }

        if(empty($chr)) return false;
        $pos = $f($chr);
        $char = $chrPos[$pos];
        return $pos;
    }

    /**
     * Find all substring occurrences in a string
     *
     * @param string $haystack Input string
     * @param string $needle Search string
     * @param int $offset (Optional) Starting offset
     *
     * @author Damian Kęska <damian.keska@fingo.pl>
     * @return int[]
     */
    public static function strposAll($haystack, $needle, $offset = 0)
    {
        $positions = array();

        do
        {
            $offset = strpos($haystack, $needle, $offset);

            if ($offset !== false)
            {
                $positions[] = $offset;
                $offset++;
            }

        } while ($offset !== false);

        return $positions;
    }
}
