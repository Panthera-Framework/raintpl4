<?php
/**
 * Simple sandboxing plugin, uses PHP Parser (Lexer)
 *
 * List of extra tokens that could be blocked:
 * - @closure: Closure functions
 * - @eval: Code evaluation
 * - @include: Includes, requires etc.
 * - shell_exec: Includes both function shell_exec() and shell commands inside ` quotes
 *
 * @package Rain\Plugins
 * @author Damian Kęska <damian@pantheraframework.org>
 */
class pseudoSandboxing extends Rain\Tpl\RainTPL4Plugin
{
    protected $parser = null;
    protected $defaultConfig = array(
        'sandboxMode' => 'blacklist',
        'sandboxWhitelist' => array(
            'strpos', 'str_replace', 'str_ireplace', 'strpad', 'strtolower', 'ucfirst', 'strtoupper',
            'in_array', 'array_reverse', 'join', 'explode', 'strlen', 'substr', 'substr_replace', 'is_array',
            'sizeof', 'count', 'range', 'is_string', 'is_int', 'is_object', 'time', 'date', 'strtotime',
            'ob_start', 'ob_get_clean', 'ob_end_flush', 'stripslashes', 'strip_tags', 'trim', 'ltrim', 'rtrim',
            'htmlspecialchars', 'nl2br', 'print', 'echo', 'md5', 'sha256', 'sha512', 'hash', 'str_repeat', 'str_word_count',
            'strchr', 'chr', 'stripos', 'stristr', 'strstr', 'strcmp', 'strnatcasecmp', 'strncasecmp', 'substr_count',
            'substr_compare', 'strtok', 'soundex', 'quotemeta', 'addslashes', 'addcslashes', 'count_chars',
            'reset', 'sort', 'ksort', 'usort', 'array_multisort', 'uksort', 'rsort', 'shuffle', 'reset', 'pos',
            'prev', 'next', 'natsort', 'natcasesort', 'key_exists', 'array_diff', 'array_flip', 'array_map',
            'isset', 'array_merge', 'array_pop', 'array_push', 'array_sum', 'array_slice', 'is_long', 'is_real',
            'is_resource', 'is_null', 'json_encode', 'serialize', 'is_scalar', 'is_real', 'is_double', 'gettype',
            'empty', 'intval', 'floatval', 'boolval', 'doubleval', 'is_bool',
        ),

        'sandboxBlacklist' => array(
            'exec', 'shell_exec', 'pcntl_exec', 'passthru', 'proc_open', 'system',
            'posix_kill', 'posix_setsid', 'pcntl_fork', 'posix_uname', 'php_uname',
            'phpinfo', 'popen', 'file_get_contents', 'file_put_contents', 'rmdir',
            'mkdir', 'unlink', 'highlight_contents', 'symlink',
            'apache_child_terminate', 'apache_setenv', 'define_syslog_variables',
            'escapeshellarg', 'escapeshellcmd', 'eval', 'fp', 'fput',
            'ftp_connect', 'ftp_exec', 'ftp_get', 'ftp_login', 'ftp_nb_fput',
            'ftp_put', 'ftp_raw', 'ftp_rawlist', 'highlight_file', 'ini_alter',
            'ini_get_all', 'ini_restore', 'inject_code', 'mysql_pconnect',
            'openlog', 'passthru', 'php_uname', 'phpAds_remoteInfo',
            'phpAds_XmlRpc', 'phpAds_xmlrpcDecode', 'phpAds_xmlrpcEncode',
            'posix_getpwuid', 'posix_kill', 'posix_mkfifo', 'posix_setpgid',
            'posix_setsid', 'posix_setuid', 'posix_uname', 'proc_close',
            'proc_get_status', 'proc_nice', 'proc_open', 'proc_terminate',
            'syslog', 'xmlrpc_entity_decode',
        ),
    );

    public function init()
    {
        $libDir = null;

        if (is_dir(__DIR__. '/../../../../vendor/nikic/php-parser'))
            $libDir = __DIR__. '/../../../../vendor/nikic/php-parser';
        elseif (is_dir(__DIR__. '/../../../../vendor/PHP-Parser'))
            $libDir = __DIR__. '/../../../../vendor/PHP-Parser';
        elseif (is_dir(__DIR__. '../../share/PHP-Parser'))
            $libDir = __DIR__. '../../share/PHP-Parser';
        elseif ($this->engine->getConfigurationKey('PHP-ParserPath') && is_dir($this->engine->getConfigurationKey('PHP-ParserPath')))
            $libDir = $this->engine->getConfigurationKey('PHP-ParserPath');

        if ($libDir)
        {
            require_once $libDir . '/lib/bootstrap.php';
            require_once __DIR__. '/pseudoSandboxingVisitor.php';
        }

        if (!class_exists('PhpParser\Autoloader'))
        {
            throw new \Rain\Tpl\NotFoundException('pseudoSandboxing plugin turned on, but could not find PHP-Parser library. Please clone a "https://github.com/nikic/PHP-Parser" repository and point to it using "PHP-ParserPath" configuration key in RainTPL', 1);
        }

        $this->engine->connectEvent('parser.compileTemplate.after', array($this, 'afterCompile'));
    }

    /**
     * Execute a code review after compilation
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
        $collector = new NodeVisitor_pseudoSandboxing;
        $traverser = new PhpParser\NodeTraverser;
        $traverser->addVisitor($collector);

        $this->parser = new PhpParser\Parser(new PhpParser\Lexer\Emulative);
        $stmts = $this->parser->parse($input[0]);
        $stmts = $traverser->traverse($stmts);

        /**
         * Whitelist support
         */
        if ($this->engine->getConfigurationKey('sandboxMode', 'whitelist') == 'whitelist')
        {
            $whitelist = $this->engine->getConfigurationKey('sandboxWhitelist');

            if (!is_array($whitelist))
            {
                throw new Rain\InvalidConfiguration('Missing configuration key "sandboxWhitelist", please set it using setConfigurationKey in RainTPL', 2);
            }

            foreach ($collector->calledFunctions as $functionName => $count)
            {
                if (!in_array($functionName, $whitelist))
                {
                    throw new Rain\RestrictedException('Method "' .$functionName. '" was blocked from use in this template', 1);
                }
            }
        }

        /**
         * Blacklist support
         */
        elseif ($this->engine->getConfigurationKey('sandboxMode') == 'blacklist')
        {
            $blacklist = $this->engine->getConfigurationKey('sandboxBlacklist');

            if (!is_array($blacklist))
                throw new Rain\InvalidConfiguration('Missing configuration key "sandboxBlacklist", please set it using setConfigurationKey in RainTPL', 2);

            foreach ($collector->calledFunctions as $functionName => $count)
            {
                if (in_array($functionName, $blacklist))
                {
                    throw new Rain\RestrictedException('Method "' .$functionName. '" was blocked from use in this template', 1);
                }
            }
        }

        return $input;
    }
}