<?php
/**
 * Library for dump variables and profiling PHP code
 * The idea and the look was taken from http://krumo.sourceforge.net/
 *
 * PHP version 5.2 or higher
 *
 * @package     JBDump
 * @version     1.2.10
 * @author      admin@joomla-book.ru
 * @link        http://joomla-book.ru/
 * @copyright   Copyright (c) 2009-2011 Joomla-book.ru
 * @license     GNU General Public License version 2 or later; see LICENSE
 *
 */


/**
 * Class for debug and dump PHP variables
 *
 */
class JBDump
{

    /**
     * Flag enable or disable the debugger
     *
     * @var bool
     */
    public $enabled = true;

    /**
     * Default configurations
     *
     * @var array
     */
    private $_config = array(
        // paths
        'root' => false,
        'path' => false,

        // file logger
        'logPath' => false,
        'logFile' => false,
        'logFormat' => "{DATETIME}\t{CLIENT_IP}\t\t{FILE}\t\t{NAME}\t\t{TEXT}",
        'serialize' => 'json',

        // profiler
        'profileToFile' => false,
        'autoProfile' => true,

        // sorting
        'sort' => array(
            'array' => false,
            'object' => true,
            'methods' => true,
        ),

        // personal dump
        'ip' => false,
        'requestParam' => false,
        'requestValue' => false,

        // handlers
        'handler' => array(
            'error' => true,
            'exception' => true,
            'context' => false,
        ),

        // others
        'lite_mode' => false,
        'stringLength' => 50,
        'maxDepth' => 3,
        'showMethods' => true,
        'allToLog' => false,
        'showArgs' => false,
    );

    /**
     * Library version
     *
     * @var string
     */
    const VERSION = '1.2.10';

    /**
     * Library version
     *
     * @var string
     */
    const DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * Site url
     *
     * @var string
     */
    private $_site = 'http://joomla-book.ru/projects/jbdump';

    /**
     * Last backtrace
     *
     * @var array
     */
    private $_trace = array();

    /**
     * Absolute path current log file
     *
     * @var string|resource
     */
    private $_logfile = null;

    /**
     * Absolute path for all log files
     *
     * @var string
     */
    private $_logpath = null;

    /**
     * Current depth in current dumped object or array
     *
     * @var integer
     */
    private $_currentDepth = 0;

    /**
     * Prefix used to distinguish profiler objects,
     * debug context
     *
     * @var string
     */
    protected $_prefix = '';

    /**
     * Profiler buffer
     *
     * @var array
     */
    protected $_buffer = array();

    /**
     * Start microtime
     *
     * @var float
     */
    protected $_start = 0.0;

    /**
     * Previous microtime for profiler
     *
     * @var float
     */
    protected $_prevTime = 0.0;

    /**
     * Previous memory value for profiler
     *
     * @var float
     */
    protected $_prevMemory = 0.0;

    /**
     * Flag, current system is WIN OS
     *
     * @var float
     */
    protected $_iswin = false;

    /**
     * Constructor, set internal variables and self configuration
     *
     * @param string $prefix    OPTIONAL  Set debug context
     * @param array $options    OPTIONAL  Initialization parameters
     */
    private function __construct( $prefix = 'jbdump', array $options = array())
    {
        $this->setParams($options);
        $this->_iswin = (substr(PHP_OS, 0, 3) == 'WIN');
        $this->_buffer = array();
        $this->_prefix = $prefix;

        if (!$this->_config['root']) {
            $this->_config['root'] = $_SERVER['DOCUMENT_ROOT'];
        }

        if ($this->_config['logPath']) {
            $this->_logpath = $this->_config['logPath'];
        } else {
            $this->_logpath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'logs';
        }

        // set log filename
        if ($this->_config['logFile']) {
            $this->_logfile = $this->_logpath . DIRECTORY_SEPARATOR . $this->_config['logFile'] . '.php';
        } else {
            $this->_logfile = $this->_logpath . DIRECTORY_SEPARATOR
                              . ($this->_prefix ? $this->_prefix . '_' : '')
                              . date('Y.m.d')
                              . '.log.php';
        }

        if ($prefix == 'jbdump') {
            if ($this->_config['handler']['error']) {
                set_error_handler(array($this, 'errorHandler'));
            }

            if ($this->_config['handler']['exception']) {
                set_exception_handler(array($this, 'exceptionHandler'));
            }
        }

        $this->_start = $this->_microtime();
        return $this;
    }

    /**
     * Destructor, call _shutdown method
     *
     */
    function __destruct()
    {
        $this->_shutdown();
        return $this;
    }

    /**
     * Shutdown method
     *  - close opened log file
     *  - print profiler result
     *
     * @return  JBDump
     */
    function _shutdown()
    {
        if ($this->_config['autoProfile']) {
            $this->profiler($this->_config['profileToFile']);
        }
        $this->_closeLog();
        return $this;
    }

    /**
     * Returns the global JBDump object, only creating it
     * if it doesn't already exist
     *
     * @static
     * @param   string  $prefix     OPTIONAL  Set debug context
     * @param   array   $options    OPTIONAL  Initialization parameters
     * @return  JBDump
     */
    public static function i($prefix = 'jbdump', $options = array())
    {
        static $instances;

        $prefix = trim($prefix);
        if (empty($prefix)) {
            $prefix = 'jbdump';
        }

        if (!isset($instances)) {
            $instances = array();
        }

        if (!isset($instances['_' . $prefix])) {
            $instances['_' . $prefix] = new self($prefix, $options);
        }

        return $instances['_' . $prefix];
    }

    /**
     * Check permissions for show all debug messages
     *  - check ip, it if set in config
     *  - check requestParam, if it set in config
     *  - else return $_enabled
     *
     * @return  bool
     */
    public function isDebug()
    {
        if ($this->enabled) {
            if ($this->_config['ip']) {
                if ($this->_getClientIP() === $this->_config['ip']) {
                    return true;
                } else {
                    return false;
                }
            }

            if ($this->_config['requestParam']) {
                if (isset($_REQUEST[$this->_config['requestParam']])
                    && $_REQUEST[$this->_config['requestParam']] == $this->_config['requestValue']
                ) {
                    return true;
                } else {
                    return false;
                }
            }

        }

        return $this->enabled;
    }

    /**
     * Force show PHP error messages
     *
     * @param   bool $strict  OPTIONAL  Show E_STRICT errors
     * @return  JBDump
     */
    public static function showErrors($strict = false)
    {
        if (self::i()->isDebug()) {
            return false;
        }
                
        if ($strict) {
            error_reporting(E_ALL);
        } else {
            error_reporting(E_ALL | E_STRICT);
        }
        
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        
        return self::i();
    }

    /**
     * Set max execution time
     *
     * @param   integer $time  OPTIONAL  Time limit in seconds
     * @return  JBDump
     */
    public static function maxTime($time = 600)
    {
        if (self::i()->isDebug()) {
            return false;
        }
        
        ini_get('max_execution_time', $time);
        
        set_time_limit($time);
        
        return self::i();
    }

    /**
     * Enable debug
     *
     * @param   string $prefix  OPTIONAL  Set debug context
     * @return  JBDump
     */
    public static function on($prefix = '')
    {
        self::i($prefix)->enabled = true;
        return self::i();
    }

    /**
     * Disable debug
     *
     * @param   string $prefix  OPTIONAL  Set debug context
     * @return  JBDump
     */
    public static function off($prefix = '')
    {
        self::i($prefix)->enabled = false;
        return self::i();
    }

    /**
     * Set debug parameters
     *
     * @param   array  $data  Params for debug, see $_config vars
     * @return  JBDump
     */
    public function setParams($data)
    {
        $this->_config = array_merge($this->_config, $data);
    }

    /**
     * Show client IP
     *
     * @return  JBDump
     */
    public static function ip()
    {
        if (!self::i()->isDebug()) {
            return false;
        }
        $ip = self::i()->_getClientIP();
        $ip_source = self::i()->_getClientIP(true);
        return self::i()->dump($ip, '! my IP (' . $ip_source . ') !');
    }

    /**
     * Show $_GET array
     *
     * @return  JBDump
     */
    public static function get()
    {
        if (!self::i()->isDebug()) {
            return false;
        }
        return self::i()->dump($_GET, '! $_GET !');
    }

    /**
     * Add message to log file
     *
     * @param   mixed   $entry    OPTIONAL  Text to log file
     * @param   string  $markName OPTIONAL  Name of log record
     * @param   array   $params   OPTIONAL  Additional params
     * @return  bool
     */
    public function log($entry, $markName = '---', $params = array())
    {
        if (!self::i()->isDebug()) {
            return false;
        }

        if (!isset($this) || !($this instanceof JBDump)) {
            return self::i()->log($entry, $markName, $params);
        }

        // check var type
        if (is_string($entry) || is_float($entry) || is_int($entry)) {
            $entry = array('text' => $entry);

        } else if (is_bool($entry)) {
            $entry = ($entry) ? 'TRUE' : 'FALSE';
            $entry = array('text' => $entry);

        } else if (is_null($entry)) {
            $entry = array('text' => 'NULL');

        } else {
            if ($this->_config['serialize'] == 'none') {
                $entry = array('text' => $entry);

            } else if ($this->_config['serialize'] == 'json') {
                $entry = array('text' => @json_encode($entry));

            } else if ($this->_config['serialize'] == 'serialize') {
                $entry = array('text' => serialize($entry));

            } else if ($this->_config['serialize'] == 'print_r') {
                $entry = array('text' => print_r($entry, true));

            } else if ($this->_config['serialize'] == 'var_dump') {
                ob_start();
                var_dump($entry);
                $entry = ob_get_clean();
                $entry = array('text' => var_dump($entry, true));

            } else {
                $entry = array('text' => @json_decode($entry));
            }

        }

        if (isset($params['trace'])) {
            $this->_trace = $params['trace'];
        } else {
            $this->_trace = debug_backtrace();
        }
        
        $entry['name'] = $markName;
        $entry['datetime'] = date(self::DATE_FORMAT);
        $entry['client_ip'] = $this->_getClientIP();
        $entry['file'] = $this->_getSourcePath($this->_trace, true);
        $entry = array_change_key_case($entry, CASE_UPPER);

        $fields = array();
        $regex = "/{(.*?)}/i";
        preg_match_all($regex, $this->_config['logFormat'], $fields);

        // Fill in the field data
        $line = $this->_config['logFormat'];
        for ($i = 0; $i < count($fields[0]); $i++) {
            $line = str_replace($fields[0][$i], (isset ($entry[$fields[1][$i]])) ? $entry[$fields[1][$i]] : "-", $line);
        }

        // Write the log entry line
        if ($this->_openLog()) {
            if (!fputs($this->_logfile, "\n" . $line)) {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * Open log file
     *
     * @return  bool
     */
    function _openLog()
    {
        // Only open if not already opened...
        if (is_resource($this->_logfile)) {
            return true;
        }

        if (!@file_exists($this->_logfile)) {

            if (!is_dir($this->_logpath)) {
                mkdir($this->_logpath, 0777, true);
            }

            $header[] = "#<?php die('Direct Access To Log Files Not Permitted'); ?>";
            $header[] = "#Date: " . date(DATE_RFC822, time());
            $header[] = "#Software: JBDump v" . self::VERSION;
            $fields = str_replace("{", "", $this->_config['logFormat']);
            $fields = str_replace("}", "", $fields);
            $fields = strtolower($fields);
            $header[] = '#' . str_replace("\t", "\t", $fields);

            $head = implode("\n", $header);
        } else {
            $head = false;
        }

        if (!$this->_logfile = @fopen($this->_logfile, "a")) {
            return false;
        }

        if ($head) {
            if (!fputs($this->_logfile, $head)) {
                return false;
            }
        }

        // If we opened the file lets make sure we close it
        register_shutdown_function(array($this, '_shutdown'));

        return true;
    }

    /**
     * Close the log file pointer
     *
     * @return  bool
     */
    function _closeLog()
    {
        if (is_resource($this->_logfile)) {
            fclose($this->_logfile);
        }

        return true;
    }

    /**
     * Show $_FILES array
     *
     * @return  JBDump
     */
    public static function files()
    {
        if (!self::i()->isDebug()) {
            return false;
        }
        return self::i()->dump($_FILES, '! $_FILES !');
    }

    /**
     * Show current usage memory in filesize format
     *
     * @return  JBDump
     */
    public static function memory()
    {
        if (!self::i()->isDebug()) {
            return false;
        }
        $memory = self::i()->_getMemory();
        $memory = self::i()->_formatSize($memory);
        return self::i()->dump($memory, '! memory !');
    }

    /**
     * Show declared interfaces
     *
     * @return  JBDump
     */
    public static function interfaces()
    {
        if (!self::i()->isDebug()) {
            return false;
        }
        return self::i()->dump(get_declared_interfaces(), '! interfaces !');
    }

    /**
     * Parse url
     *
     * @param   string  $url      URL string
     * @param   string  $varname  OPTIONAL URL name
     *
     * @return  JBDump
     */
    public static function url($url, $varname = '...')
    {
        if (!self::i()->isDebug()) {
            return false;
        }

        $parsed = parse_url($url);

        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $parsed['query_parsed']);
        }

        return self::i()->dump($parsed, $varname);
    }

    /**
     * Show included files
     *
     * @return  JBDump
     */
    public static function includes()
    {
        if (!self::i()->isDebug()) {
            return false;
        }
        return self::i()->dump(get_included_files(), '! includes files !');
    }

    /**
     * Show defined functions
     *
     * @param   bool $showInternal OPTIONAL Get only internal functions
     * @return  JBDump
     */
    public static function functions($showInternal = true)
    {
        if (!self::i()->isDebug()) {
            return false;
        }

        $functions = get_defined_functions();
        if ($showInternal) {
            $functions = $functions['internal'];
            $type = 'internal';
        } else {
            $functions = $functions['user'];
            $type = 'user';
        }

        return self::i()->dump($functions, '! functions (' . $type . ') !');
    }

    /**
     * Show defined constants
     * @static
     * @param bool $showAll Get only user defined functions
     * @return bool|JBDump
     */
    public static function defines($showAll = false)
    {
        if (!self::i()->isDebug()) {
            return false;
        }

        $defines = get_defined_constants(true);
        if (!$showAll) {
            $defines = (isset($defines['user'])) ? $defines['user'] : array();
        }

        return self::i()->dump($defines, '! defines !');
    }

    /**
     * Show loaded PHP extensions
     *
     * @param   bool $zend  Get only Zend extensions
     * @return  JBDump
     */
    public static function extensions($zend = false)
    {
        if (!self::i()->isDebug()) {
            return false;
        }
        return self::i()->dump(get_loaded_extensions($zend), '! extensions ' . ($zend ? '(Zend)' : '') . ' !');
    }

    /**
     * Show HTTP headers
     *
     * @return  JBDump
     */
    public static function headers()
    {
        if (!self::i()->isDebug()) {
            return false;
        }

        $data = array(
            'Request' => apache_request_headers(),
            'Response' => apache_response_headers(),
            'List' => headers_list()
        );

        if (headers_sent($filename, $linenum)) {
            $data['Sent'] = 'Headers already sent in ' . self::i()->_getRalativePath($filename) . ':' . $linenum;
        } else {
            $data['Sent'] = false;
        }

        return self::i()->dump($data, '! headers !');
    }

    /**
     * Show php.ini content (open php.ini file)
     *
     * @return  JBDump
     */
    public static function phpini()
    {
        if (!self::i()->isDebug()) {
            return false;
        }

        $data = get_cfg_var('cfg_file_path');
        if (!@file($data)) {
            return false;
        }
        $ini = parse_ini_file($data, true);
        return self::i()->dump($ini, '! php.ini !');
    }

    /**
     * Show php.ini content (PHP API)
     *
     * @param   string  $extension  Extension name
     * @param   bool    $details    Retrieve details settings or only the current value for each setting
     * @return  bool|JBDump
     */
    public static function conf($extension = '', $details = true)
    {
        if (!self::i()->isDebug()) {
            return false;
        }

        if ($extension == '') {
            $label = '';
            $data = ini_get_all();
        } else {
            $label = ' (' . $extension . ') ';
            $data = ini_get_all($extension, $details);
        }

        return self::i()->dump($data, '! configuration settings' . $label . ' !');
    }

    /**
     * Show included and system paths
     *
     * @return  JBDump
     */
    public static function path()
    {
        if (!self::i()->isDebug()) {
            return false;
        }

        $result = array(
            'get_include_path' => explode(PATH_SEPARATOR, trim(get_include_path(), PATH_SEPARATOR)),
            '$_SERVER[PATH]' => explode(PATH_SEPARATOR, trim($_SERVER['PATH'], PATH_SEPARATOR))
        );

        return self::i()->dump($result, '! paths !');
    }

    /**
     * Show $_REQUEST array or dump $_GET, $_POST, $_COOKIE
     *
     * @static
     * @param bool $notReal Get real $_REQUEST array
     * @return bool|JBDump
     */
    public static function request($notReal = false)
    {
        if (!self::i()->isDebug()) {
            return false;
        }

        if ($notReal) {
            self::get();
            self::post();
            self::cookie();
            return self::files();
        } else {
            return self::i()->dump($_REQUEST, '! $_REQUEST !');
        }
    }

    /**
     * Show $_POST array
     *
     * @return  JBDump
     */
    public static function post()
    {
        if (!self::i()->isDebug()) {
            return false;
        }
        return self::i()->dump($_POST, '! $_POST !');
    }

    /**
     * Show $_SERVER array
     *
     * @return  JBDump
     */
    public static function server()
    {
        if (!self::i()->isDebug()) {
            return false;
        }
        return self::i()->dump($_SERVER, '! $_SERVER !');
    }

    /**
     * Show $_COOKIE array
     *
     * @return  JBDump
     */
    public static function cookie()
    {
        if (!self::i()->isDebug()) {
            return false;
        }
        return self::i()->dump($_COOKIE, '! $_COOKIE !');
    }

    /**
     * Show parsed JSON data
     *
     * @static
     * @param $jsonData  JSON data
     * @param string $name  OPTIONAL Variable name
     * @return bool|JBDump
     */
    public static function json($jsonData, $name = '...')
    {
        if (!self::i()->isDebug()) {
            return false;
        }
        $data = json_decode($jsonData);
        return self::i()->dump($data, $name);
    }

    /**
     * Show $_ENV array
     *
     * @return  JBDump
     */
    public static function env()
    {
        if (!self::i()->isDebug()) {
            return false;
        }
        return self::i()->dump($_ENV, '! $_ENV !');
    }

    /**
     * Show $_SESSION array
     *
     * @return  JBDump
     */
    public static function session()
    {
        $sessionId = session_id();
        if (!$sessionId) {
            $_SESSION = 'PHP session don\'t start';
            $sessionId = '';
        } else {
            $sessionId = ' (' . $sessionId . ') ';
        }

        return self::i()->dump($_SESSION, '! $_SESSION ' . $sessionId . ' !');
    }

    /**
     * Show $GLOBALS array
     *
     * @return  JBDump
     */
    public static function globals()
    {
        if (!self::i()->isDebug()) {
            return false;
        }
        return self::i()->dump($GLOBALS, '! $GLOBALS !');
    }

    /**
     * Convert timestamp to normal date, in DATE_RFC822 format
     *
     * @static
     * @param   null|integer $timestamp Time in Unix timestamp format
     * @return  bool|JBDump
     */
    public static function timestamp($timestamp = null)
    {
        if (!self::i()->isDebug()) {
            return false;
        }
        $date = date(DATE_RFC822, $timestamp);
        return self::i()->dump($date, $timestamp . ' sec = ');
    }

    /**
     * Find all locale in system
     * list - only for linux like systems
     *
     * @return  JBDump
     */
    public static function locale()
    {
        if (!self::i()->isDebug()) {
            return false;
        }

        ob_start();
        @system('locale -a');
        $locale = explode("\n", trim(ob_get_contents()));
        ob_end_clean();

        $result = array(
            'list' => $locale,
            'conv' => @localeconv(),
        );

        return self::i()->dump($result, '! locale info !');
    }

    /**
     * Show date default timezone
     *
     * @return  JBDump
     */
    public static function timezone()
    {
        if (!self::i()->isDebug()) {
            return false;
        }
        $data = date_default_timezone_get();
        return self::i()->dump($data, '! timezone !');
    }

    /**
     * Wrapper for PHP print_r function
     *
     * @static
     * @param   $var    The variable to dump
     * @param   bool    $isDie OPTIONAL Label to prepend to output
     * @param   string  $name OPTIONAL Echo output if true
     * @return  bool|JBDump
     */
    public static function print_r($var, $isDie = true, $name = '...')
    {
        if (!self::i()->isDebug()) {
            return false;
        }

        self::i()->dump(print_r($var, true), $name);

        if ($isDie) {
            die('JBDump_die');
        }

        return self::i();
    }

    /**
     * Wrapper for PHP var_dump function
     * @see     Zend_debug::dump()
     *
     * @static
     * @param   mixed   $var    The variable to dump
     * @param   bool    $isDie  OPTIONAL Label to prepend to output
     * @param   string  $name   OPTIONAL Echo output if true
     * @return bool|JBDump
     */
    public static function var_dump($var, $isDie = true, $name = '...')
    {
        if (!self::i()->isDebug()) {
            return false;
        }

        // var_dump the variable into a buffer and keep the output
        ob_start();
        var_dump($var);
        $output = ob_get_clean();

        $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);

        $header = "\"<span style=\\\"color: #000;font-weight:bold;\\\">\$matches[1]</span><span style=\\\"color: red\\\">\$matches[2]</span><span style=\\\"color: black\\\">\$matches[3]</span>\"";
        $function = create_function('$matches', '
            $count = count($matches);
            if($count == 7 && $matches[4] === $matches[5] && $matches[6]) {
                $ret = ' . $header . '."<span style=\"color: green\">$matches[5]</span>";
            } else if($count == 7) {
                $ret = ' . $header . '."<span style=\"color: green\">$matches[5]</span><span style=\"color: blue\">$matches[6]</span>";
            } else if($count == 4){
                $ret = ' . $header . ';
            } else if($count == 10) {
                $ret = ' . $header . '."<span style=\"color: green\">$matches[6]</span><span style=\"color: black\">$matches[8]</span><span style=\"color: green\">$matches[9]</span>";
            } else if($count == 11) {
                // strings
                $ret = ' . $header . '."<span style=\"color: green\">$matches[5]</span><span style=\"color: blue\">$matches[10]</span>";
            } else {
                $ret = $matches[0];
            }
            return $ret;
        ');
        $output = preg_replace_callback('~(\[[^\]]+\]|[\d]+)(\s=>\s+)([a-zA-Z_\d]+)(((\([^)]+\))((#[\d]+)(\s\([\d]+\)))?)(\s".*")?)?~', $function, $output);
        $output = ltrim(substr($output, strpos($output, "\n ")));
        
        self::i()->dump($output, $name . '::html');
        
        if ($isDie) {
            die('JBDump_die');
        }

        return self::i();
    }

    /**
     * Get system backtrace in formated view
     *
     * @param   bool $addObject  Show objects in result
     * @return  JBDump
     */
    public static function trace($addObject = false)
    {
        if (!self::i()->isDebug()) {
            return false;
        }

        $_this = self::i();

        $trace = debug_backtrace($addObject);
        unset($trace[0]);

        $result = $_this->convertTrace($trace);

        return $_this->dump($result, '! backtrace !');
    }

    /**
     * Show declared classes
     *
     * @return  JBDump
     */
    public static function classes()
    {
        if (!self::i()->isDebug()) {
            return false;
        }
        return self::i()->dump(get_declared_classes(), '! classes !');
    }

    /**
     * Show declared classes
     *
     * @static
     * @param $object
     * @return bool|JBDump
     */
    public static function methods($object)
    {
        if (!self::i()->isDebug()) {
            return false;
        }
        $methodst = self::i()->_getMethods($object);
        if (is_string($object)) {
            $className = $object;
        } else {
            $className = get_class($object);
        }

        return self::i()->dump($methodst, '&lt;! methods of "' . $className . '" !&gt;');
    }

    /**
     * Dump info about class (object)
     *
     * @param   string|object  $data    Object or class name
     * @return  JBDump
     */
    public static function classInfo($data)
    {
        $result = self::_getClass($data);
        if ($result) {
            $data = $result['name'];
        }
        return self::i()->dump($result, $data);
    }

    /**
     * Dump all info about extension
     *
     * @static
     * @param   string  $extensionName  Extension name
     * @return  JBDump
     */
    public static function extInfo($extensionName)
    {
        $result = self::_getExtension($extensionName);
        if ($result) {
            $extensionName = $result['name'];
        }
        return self::i()->dump($result, '! extension (' . $extensionName . ') !');
    }

    /**
     * Dump all file info
     *
     * @param   string  $file   path to file
     * @return  JBDump
     */
    public function pathInfo($file)
    {
        $result = self::_pathInfo($file);
        return self::i()->dump($result, '! pathInfo (' . $file . ') !');
    }

    /**
     * Dump all info about function
     *
     * @static
     * @param   string|Closure $functionName    Closure or function name
     * @return  JBDump
     */
    public static function funcInfo($functionName)
    {
        $result = self::_getFunction($functionName);
        if ($result) {
            $functionName = $result['name'];
        }
        return self::i()->dump($result, '! function (' . $functionName . ') !');
    }

    /**
     * Show current microtime
     *
     * @return  JBDump
     */
    public static function microtime()
    {
        if (!self::i()->isDebug()) {
            return false;
        }
        $data = self::i()->_microtime();
        return self::i()->dump($data, '! current microtime !');
    }

    /**
     * Output a time mark
     * The mark is returned as text current profiler status
     *
     * @param   string  $label OPTIONAL A label for the time mark
     * @return  JBDump
     */
    public function mark($label = '')
    {
        if (!self::i()->isDebug()) {
            return false;
        }

        if (!isset($this) || !($this instanceof JBDump)) {
            return self::i()->mark($label);
        }

        if ($this->isDebug()) {
            $current = $this->_microtime() - $this->_start;
            $memory = $this->_getMemory() / 1048576;
            $trace = debug_backtrace();
            $mark = sprintf(
                "%.3f sec (+%.3f); %0.2f MB (%s%0.3f) - %s",
                $current,
                $current - $this->_prevTime,
                $memory,
                ($memory > $this->_prevMemory) ? '+' : '',
                $memory - $this->_prevMemory,
                $label
            );

            $this->_prevTime = $current;
            $this->_prevMemory = $memory;
            $this->_buffer[] = $mark;
        }

        return self::i();
    }

    /**
     * Show profiler result
     *
     * @param   bool    $toFile Print result to log file
     * @return  JBDump
     */
    public function profiler($toFile = false)
    {
        if ($this->isDebug() && count($this->_buffer) > 0) {
            $profilerName = ($this->_prefix) ? ' (' . $this->_prefix . ')' : '';
            
            if ($toFile) {
                
                $this->log('-------------------------------------------------------', 'Profiler start');
                foreach ($this->_buffer as $key => $logText) {
                    $this->log($logText, 'Mark #' . $key);
                }
                $this->log('-------------------------------------------------------', 'Profiler finish');
                
            } else {
                $this->_dumpLite($this->_buffer, '! profiler ' . $profilerName . ' !');
            }
            
        }
        
        return $this;
    }

    /**
     * Lite dump template
     *
     * @param   mixed   $data     Mixed data for dump
     * @param   string  $varname  OPTIONAL Variable name
     * @return  JBDump
     */
    private function _dumpLite($data, $varname = '...')
    {
        if (!self::i()->isDebug()) {
            return false;
        }

        if (is_bool($data)) {
            $data = $data ? 'TRUE' : 'FALSE';
        } else if (is_null($data)) {
            $data = 'NULL';
        }

        echo "\n<pre style=\"text-align:left;\">".$varname.' = '.print_r($data, true)."</pre>\n";

        return self::i();
    }

    /**
     * Dumper variable
     *
     * @param   mixed   $data     Mixed data for dump
     * @param   string  $varname  OPTIONAL Variable name
     * @param   array   $params   OPTIONAL Additional params
     * @return  JBDump
     */
    public function dump($data, $varname = '...', $params = array())
    {
        if (!self::i()->isDebug()) {
            return false;
        }

        if (!isset($this) || !($this instanceof JBDump)) {
            return self::i()->dump($data, $varname, $params);
        }
        
        if ($this->_config['allToLog']) {
            $this->_dump($data, $varname);

        } else if ($this->_isLiteMode()) {
            return self::i()->_dumpLite($data, $varname);

        } else {
            $this->_currentDepth = 0;
            $this->_initAssets();

            if (isset($params['trace'])) {
                $this->_trace = $params['trace'];
            } else {
                $this->_trace = debug_backtrace();
            }
            
            $text = $this->_getSourceFunction($this->_trace);
            $path = $this->_getSourcePath($this->_trace);
        ?>
        <div class="krumo-root">
            <ul class="krumo-node krumo-first">
                <?php $this->_dump($data, $varname);?>
                <li class="krumo-footnote">
                    <div class="copyrights">
                        <a href="<?php echo $this->_site;?>" target="_blank">JBDump v<?php echo self::VERSION;?></a>
                    </div>
                    <span class="krumo-call"><?php echo $text . ' ' . $path; ?></span>
                </li>
            </ul>
        </div>
        <?php
        }

        return $this;
    }

    /**
     * Get all available hash from data
     *
     * @param   string  $data   Data from get hash
     * @return  JBDump
     */
    public function hash($data)
    {
        $result = array();
        foreach (hash_algos() as $algoritm) {
            $result[$algoritm] = hash($algoritm, $data, false);
        }
        return self::i()->dump($result, '! hash !');
    }

    /**
     * Get current usage memory
     *
     * @return  integer
     */
    private function _getMemory()
    {
        if (!self::i()->isDebug()) {
            return false;
        }

        if (function_exists('memory_get_usage')) {
            return memory_get_usage();
        } else {
            $output = array();
            $pid = getmypid();

            if ($this->_iswin) {
                @exec('tasklist /FI "PID eq ' . $pid . '" /FO LIST', $output);
                if (!isset($output[5])) {
                    $output[5] = null;
                }
                return substr($output[5], strpos($output[5], ':') + 1);
            } else {
                @exec("ps -o rss -p $pid", $output);
                return $output[1] * 1024;
            }
        }
    }

    /**
     * Get current microtime
     *
     * @static
     * @return float
     */
    public static function _microtime()
    {
        list($usec, $sec) = explode(' ', microtime());
        return ((float)$usec + (float)$sec);
    }

    /**
     * Maps type variable to a function
     *
     * @param   mixed   $data  Mixed data for dump
     * @param   string  $name  OPTIONAL Variable name
     * @return  JBDump
     */
    private function _dump($data, $name = '...')
    {
        if ($this->_config['allToLog']) {
            $this->log($data, $name);

        } else {
            $varType = strtolower(getType($data));

            $advType = false;
            if (preg_match('#(.*)::(.*)#', $name, $matches)) {
                $matches[2] = trim(strToLower($matches[2]));
                if (strlen($matches[2]) > 0) {
                    $advType = $matches[2];
                }
                $name = $matches[1];
            }

            switch ($varType) {
                case 'null'     :
                    $this->_null($name);
                    break;
                case 'boolean'  :
                    $this->_boolean($data, $name, $advType);
                    break;
                case 'integer'  :
                    $this->_integer($data, $name, $advType);
                    break;
                case 'double'   :
                    $this->_float($data, $name, $advType);
                    break;
                case 'string'   :
                    $this->_string($data, $name, $advType);
                    break;
                case 'array':
                    if ($this->_currentDepth <= $this->_config['maxDepth']) {
                        $this->_currentDepth++;
                        $this->_array($data, $name, $advType);
                        $this->_currentDepth--;
                    } else {
                        $this->_maxDepth($data, $name, $advType);
                    }
                    break;

                case 'object':
                    if ($this->_currentDepth <= $this->_config['maxDepth']) {
                        $this->_currentDepth++;

                        if (get_class($data) == 'Closure') {
                            $this->_closure($data, $name, $advType);
                        } else {
                            $this->_object($data, $name, $advType);
                        }

                        $this->_currentDepth--;
                    } else {
                        $this->_maxDepth($data, $name);
                    }
                    break;

                case 'resource' :
                    $this->_resource($data, $name, $advType);
                    break;
                default:
                    $this->_undefined($data, $name, $advType);
            }
        }
        return $this;
    }

    /**
     * Render HTML for object and array
     *
     * @param   array|object $data Variablevalue
     * @return  void
     */
    private function _vars($data)
    {
        $_is_object = is_object($data);

        ?>
        <div class="krumo-nest" style="display:none;">
            <ul class="krumo-node">
                <?php
                $keys = ($_is_object) ? array_keys(get_object_vars($data)) : array_keys($data);

                // sorting
                if ($this->_config['sort']['object'] && $_is_object) {
                    sort($keys);
                } else if ($this->_config['sort']['array']) {
                    sort($keys);
                }

                // get entries
                foreach ($keys as $key) {
                    $value = NULL;
                    if ($_is_object) {
                        $value = $data->$key;
                    } else {
                        if (array_key_exists($key, $data)) {
                            $value = $data[$key];
                        }
                    }
                    $this->_dump($value, $key);
                }

                // get methods
                if ($_is_object && $this->_config['showMethods']) {
                    $methods = $this->_getMethods($data);
                    $this->_dump($methods, '&lt;! methods of "' . get_class($data) . '" !&gt;');
                }
                ?>
            </ul>
        </div>
        <?php

    }

    /**
     * Render HTML for NULL type
     *
     * @param   string  $name  Variable name
     * @return  void
     */
    private function _null($name)
    {
        ?>
        <li class="krumo-child">
            <div class="krumo-element" onMouseOver="krumo.over(this);" onMouseOut="krumo.out(this);">
                <a class="krumo-name"><?php echo $name;?></a> (<em class="krumo-type krumo-null">NULL</em>)
            </div>
        </li>
        <?php
    }

    /**
     * Render HTML for Boolean type
     *
     * @param   bool    $data  Variable
     * @param   string  $name  Variable name
     * @return  void
     */
    private function _boolean($data, $name)
    {
        $data = $data ? 'TRUE' : 'FALSE';
        $this->_renderNode('Boolean', $name, $data);
    }

    /**
     * Render HTML for Integer type
     *
     * @param   integer $data   Variable
     * @param   string  $name   Variable name
     * @return  void
     */
    private function _integer($data, $name)
    {
        $this->_renderNode('Integer', $name, (int)$data);
    }

    /**
     * Render HTML for float (double) type
     *
     * @param   float   $data   Variable
     * @param   string  $name   Variable name
     * @return  void
     */
    private function _float($data, $name)
    {
        $this->_renderNode('Float', $name, (float)$data);
    }

    /**
     * Render HTML for resource type
     *
     * @param   resource $data   Variable
     * @param   string   $name   Variable name
     * @return  void
     */
    private function _resource($data, $name)
    {
        $data = get_resource_type($data);
        $this->_renderNode('Resource', $name, $data);
    }

    /**
     * Render HTML for string type
     *
     * @param   string $data Variable
     * @param   string $name Variable name
     * @param   string $advType String type (parse mode)
     * @return  void
     */
    private function _string($data, $name, $advType = '')
    {
        $dataLength = strlen($data);

        $_extra = false;
        if ($advType == 'html') {
            $_extra = true;
            $_ = 'HTML Code';

            $data = '<pre>' . $data . '</pre>';

        } else if ($advType == 'source') {
            $_extra = true;
            $_ = 'PHP Code';

            $data = trim($data);
            if (strpos($data, '<?') !== 0) {
                $data = "<?php\n" . $data;
            }

            $data = highlight_string($data, true);

        } else {
            $_ = $data;
            if (strlen($data)) {
                if (strLen($data) > $this->_config['stringLength']) {
                    if (function_exists('mb_substr')) {
                        $_ = mb_substr($data, 0, $this->_config['stringLength'] - 3) . '...';
                    } else {
                        $_ = substr($data, 0, $this->_config['stringLength'] - 3) . '...';
                    }
                    $_extra = true;
                }
                $_ = htmlSpecialChars($_);
                $data = '<pre>' . htmlSpecialChars($data) . '</pre>';
            }

        }
        ?>
        <li class="krumo-child">
            <div class="krumo-element <?php echo $_extra ? ' krumo-expand' : '';?>"
                <?php if ($_extra) { ?> onClick="krumo.toggle(this);"<?php }?> onMouseOver="krumo.over(this);" onMouseOut="krumo.out(this);">
                <a class="krumo-name"><?php echo $name;?></a>
                (<em class="krumo-type">String, <strong class="krumo-string-length"><?php echo $dataLength; ?></strong></em>)
                <strong class="krumo-string"><?php echo $_;?></strong>
            </div>
            <?php if ($_extra) { ?>
                <div class="krumo-nest" style="display:none;">
                    <ul class="krumo-node">
                        <li class="krumo-child">
                            <div class="krumo-preview"><?php echo $data;?></div>
                        </li>
                    </ul>
                </div>
            <?php } ?>
        </li>
        <?php

    }

    /**
     * Render HTML for array type
     *
     * @param   array   $data  Variable
     * @param   string  $name  Variable name
     * @return  void
     */
    private function _array(array $data, $name)
    {
        ?>
        <li class="krumo-child">
            <div class="krumo-element<?php echo count($data) > 0 ? ' krumo-expand' : '';?>"
                <?php if (count($data) > 0) { ?> onClick="krumo.toggle(this);"<?php }?> onMouseOver="krumo.over(this);" onMouseOut="krumo.out(this);">
                <a class="krumo-name"><?php echo $name;?></a>
                (<em class="krumo-type">Array, <strong class="krumo-array-length"><?php echo count($data);?></strong></em>)
            </div>
            <?php if (count($data)) {
                $this->_vars($data);
            } ?>
        </li>
        <?php
    }

    /**
     * Render HTML for object type
     *
     * @param   object  $data  Variable
     * @param   string  $name  Variable name
     * @return  void
     */
    private function _object($data, $name)
    {
        ?>
        <li class="krumo-child">
            <div class="krumo-element<?php echo count($data) > 0 ? ' krumo-expand' : '';?>"
                <?php if (count($data) > 0) { ?> onClick="krumo.toggle(this);"<?php }?> onMouseOver="krumo.over(this);" onMouseOut="krumo.out(this);">
                <a class="krumo-name"><?php echo $name;?></a>
                (<em class="krumo-type">Object, <?php echo count(get_object_vars($data)); ?></em>)
                <strong class="krumo-class"><?php echo get_class($data);?></strong>
            </div>
            <?php if (count($data)) {
                $this->_vars($data);
            } ?>
        </li>
        <?php
    }

    /**
     * Render HTML for closure type
     *
     * @param   object  $data  Variable
     * @param   string  $name  Variable name
     * @return  void
     */
    private function _closure($data, $name)
    {
        ?>
        <li class="krumo-child">
            <div class="krumo-element<?php echo count($data) > 0 ? ' krumo-expand' : '';?>"
                <?php if (count($data) > 0) { ?> onClick="krumo.toggle(this);"<?php }?>
                 onMouseOver="krumo.over(this);" onMouseOut="krumo.out(this);">
                <a class="krumo-name"><?php echo $name;?></a>
                (<em class="krumo-type">Closure</em>)
                <strong class="krumo-class"><?php echo get_class($data);?></strong>
            </div>
            <?php $this->_vars($this->_getFunction($data)); ?>
        </li>
        <?php
    }

    /**
     * Render HTML for max depth message
     * @param $var
     * @param $name
     * @return void
     */
    private function _maxDepth($var, $name)
    {
        unset($var);
        $this->_renderNode('max depth', $name, '(<span style="color:red">!</span>) Max depth');
    }

    /**
     * Render HTML for undefined variable
     *
     * @param   mixed   $var   Variable
     * @param   string  $name  Variable name
     * @return  void
     */
    private function _undefined($var, $name)
    {
        $this->_renderNode('undefined', $name, '(<span style="color:red">!</span>) getType = ' . gettype($var));
    }

    /**
     * Render HTML for undefined variable
     *
     * @param   string  $type   Variable type
     * @param   mixed   $data   Variable
     * @param   string  $name   Variable name
     * @return  void
     */
    private function _renderNode($type, $name, $data)
    {
        ?>
        <li class="krumo-child">
            <div class="krumo-element" onMouseOver="krumo.over(this);" onMouseOut="krumo.out(this);">
                <a class="krumo-name"><?php echo $name;?></a>
                (<em class="krumo-type"><?php echo $type;?></em>)
                <strong class="krumo-<?php echo strtolower($type);?>"><?php echo $data;?></strong>
            </div>
        </li>
        <?php
    }

    /**
     * Get the IP number of differnt ways
     *
     * @param   bool $getSource
     * @return  string
     */
    private function _getClientIP($getSource = false)
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
            $source = 'HTTP_CLIENT_IP';

        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            $source = 'HTTP_X_FORWARDED_FOR';

        } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
            $source = 'HTTP_X_REAL_IP';

        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
            $source = 'REMOTE_ADDR';
        }

        if ($getSource) {
            return $source;
        } else {
            return $ip;
        }
    }

    /**
     * Get relative path from absolute
     *
     * @param   string  $path   Absolute filepath
     * @return  string
     */
    private function _getRalativePath($path)
    {
        if ($path) {
            $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
            $path = str_replace($this->_config['root'], '/', $path);
            $path = str_replace('//', '/', $path);
            $path = trim($path, '/');
        }
        return $path;
    }

    /**
     * Get formated one trace info
     *
     * @param   array   $info       One trace element
     * @param   bool    $addObjects OPTIONAL Add object to result (low perfomance)
     * @return  array
     */
    private function _getOneTrace($info, $addObjects = false)
    {
        $_this = self::i();

        $_tmp = array();
        if (isset($info['file'])) {
            $_tmp['file'] = $_this->_getRalativePath($info['file']) . ' : ' . $info['line'];
        } else {
            $info['file'] = false;
        }

        if (
            $info['function'] != 'include' &&
            $info['function'] != 'include_once' &&
            $info['function'] != 'require' &&
            $info['function'] != 'require_once'
        ) {
            if (isset($info['type']) && isset($info['class'])) {
                $_tmp['func'] = $info['class'] . ' ' . $info['type'] . ' ' . $info['function'] . '('.@count($_tmp['args']).')';
            } else {
                $_tmp['func'] = $info['function'] . '('.@count($_tmp['args']).')';
            }
            
            $args = isset($info['args']) ? $info['args'] : array();
            if ($_this->_config['showArgs']) {
                $_tmp['args'] = isset($info['args']) ? $info['args'] : array();
                
            } else {
                $_tmp['count_args'] = count($args);
                
            }

        } else {
            $_tmp['func'] = $info['function'];

        }

        if ($addObjects && isset($info['object']) && $_this->_config['showArgs']) {
            //$_tmp['obj'] = $info['object'];
        }

        return $_tmp;
    }

    /**
     * Convert filesize to formated string
     *
     * @param   integer $bytes  Count bytes
     * @return  string
     */
    private function _formatSize($bytes)
    {
        $exp = 0;
        $value = 0;
        $symbol = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');

        if ($bytes > 0) {
            $exp = floor(log($bytes) / log(1024));
            $value = ($bytes / pow(1024, floor($exp)));
        }

        return sprintf('%.2f ' . $symbol[$exp], $value);
    }

    /**
     * Include css and js files in document
     *
     * @param bool $force
     * @return void
     */
    private function _initAssets($force = false)
    {
        static $loaded;
        if (!isset($loaded) || $force) {
            $loaded = true;

            echo '
            <script type="text/javascript">
                function krumo(){}
                krumo.reclass=function(el,className){if(el.className.indexOf(className)<0)el.className+=" "+className};
                krumo.unclass=function(el,className){if(el.className.indexOf(className)>-1)el.className=el.className.replace(className,"")};
                krumo.toggle=function(el){var ul=el.parentNode.getElementsByTagName("ul");for(var i=0;i<ul.length;i++)if(ul[i].parentNode.parentNode==el.parentNode)ul[i].parentNode.style.display=ul[i].parentNode.style.display=="none"?"block":"none";if(ul[0].parentNode.style.display=="block")krumo.reclass(el,"krumo-opened");else krumo.unclass(el,"krumo-opened")};
                krumo.over=function(el){krumo.reclass(el,"krumo-hover")};
                krumo.out=function(el){krumo.unclass(el,"krumo-hover")};
            </script>';

            echo '
            <style type="text/css">
                ul.krumo-node{background-color:#fff!important;color:#333!important;list-style:none;text-align:left!important;margin:0!important;padding:0;}
                ul.krumo-node ul.krumo-node{margin-left:15px!important;}
                ul.krumo-node pre{font-size:92%;font-family:Courier,Monaco,"Lucida Console";width:100%;background:inherit!important;color:#000;border:none;margin:0;padding:0;}
                ul.krumo-node ul{margin-left:20px;}
                ul.krumo-node li{list-style:none;line-height:12px;margin-left:5px;min-height:12px;}
                div.krumo-root{border:solid 1px #000;position:relative;z-index:10101;min-width:400px;margin:5px 0 20px;}
                ul.krumo-first{font:normal 10px tahoma, verdana;border:solid 1px #FFF;}
                li.krumo-child{display:block;list-style:none;overflow:hidden;margin:0;padding:0;}
                div.krumo-element{cursor:default;display:block;clear:both;white-space:nowrap;background-color:#FFF;background-image:url(data:;base64,R0lGODlhCQAJALMAAP////8AAICAgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAAAAALAAAAAAJAAkAAAQSEAAhq6VWUpx3n+AVVl42ilkEADs=);background-repeat:no-repeat;background-position:6px 5px;padding:2px 0 3px 20px;}
                div.krumo-expand{background-image:url(data:;base64,R0lGODlhCQAJALMAAP///wAAAP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAAAAALAAAAAAJAAkAAAQTEIAna33USpwt79vncRpZgpcGRAA7);cursor:pointer;}
                div.krumo-hover{background-color:#BFDFFF;}
                div.krumo-opened{background-image:url(data:;base64,R0lGODlhCQAJALMAAP///wAAAP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAAAAALAAAAAAJAAkAAAQQEMhJ63w4Z6C37JUXWmQJRAA7);}
                a.krumo-name{color:#a00;font:14px courier new;line-height:12px;text-decoration:none;}
                a.krumo-name big{font:bold 14px Georgia;line-height:10px;position:relative;top:2px;left:-2px;}
                em.krumo-type{font-style:normal;margin:0 2px;}
                div.krumo-preview{font:normal 13px courier new;background:#F9F9B5;border:solid 1px olive;overflow:auto;margin:5px 1em 1em 0;padding:5px;}
                li.krumo-footnote{background:#FFF url(data:;base64,R0lGODlhCgACALMAAP///8DAwP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAAAAALAAAAAAKAAIAAAQIEMhJA7D4gggAOw==) repeat-x;list-style:none;cursor:default;padding:4px 5px 3px;}
                li.krumo-footnote h6{font:bold 10px verdana;color:navy;display:inline;margin:0;padding:0;}
                li.krumo-footnote a{font:bold 10px arial;color:#434343;text-decoration:none;}
                li.krumo-footnote a:hover{color:#000;}
                li.krumo-footnote span.krumo-call{font-size:11px;font-family:Courier,Monaco,"Lucida Console";position:relative;top:1px;}
                li.krumo-footnote span.krumo-call code{font-weight:700;}
                div.krumo-title{font:normal 11px Tahoma, Verdana;position:relative;top:9px;cursor:default;line-height:2px;}
                strong.krumo-array-length,strong.krumo-string-length{font-weight:400;color:#009;}
                .krumo-footnote .copyrights a{color:#ccc;font-size:8px;}
                div.krumo-version,.krumo-footnote .copyrights{float:right;}
            </style>';

        }
    }

    /**
     * Get last funtcion name and it params from backtarce
     *
     * @param   array  $trace  Backtrace
     * @return  string
     */
    private function _getSourceFunction($trace)
    {
        $lastTrace = $this->_getLastTrace($trace);
        ;

        if (isset($lastTrace['function']) || isset($lastTrace['class'])) {

            $args = '';
            if (isset($lastTrace['args'])) {
                $args = '( ' . count($lastTrace['args']) . ' args' . ' )';
            }

            if (isset($lastTrace['class'])) {
                $function = $lastTrace['class'] . ' '
                            . $lastTrace['type'] . ' '
                            . $lastTrace['function'] . ' '
                            . $args;
            } else {
                $function = $lastTrace['function'] . ' ' . $args;
            }

            return 'Function: ' . $function . '<br />';
        }

        return '';
    }

    /**
     * Get last source path from backtrace
     *
     * @param   array  $trace    Backtrace
     * @param   bool   $fileOnly Show filename only
     * @return  string
     */
    private function _getSourcePath($trace, $fileOnly = false)
    {
        $path = '';
        $currentTrace = $this->_getLastTrace($trace);

        if (isset($currentTrace['file'])) {
            $path = $this->_getRalativePath($currentTrace['file']);

            if ($fileOnly && $path) {
                $path = pathinfo($path, PATHINFO_BASENAME);
            }

            if (isset($currentTrace['line']) && $path) {
                $path = $path . ':' . $currentTrace['line'];
            }
        }

        return $path;
    }

    /**
     * Get Last trace info
     *
     * @param   array   $trace Backtrace
     * @return  array
     */
    private function _getLastTrace($trace)
    {

        // current filename info
        $curFile = pathinfo(__FILE__, PATHINFO_BASENAME);
        $curFileLength = strlen($curFile);

        $meta = array();
        $j = 0;
        for ($i = 0; $trace && $i < sizeof($trace); $i++) {
            $j = $i;
            if (isset($trace[$i]['class'])
                && isset($trace[$i]['file'])
                && ($trace[$i]['class'] == 'JBDump')
                && (substr($trace[$i]['file'], -$curFileLength, $curFileLength) == $curFile)
            ) {

            } else if (isset($trace[$i]['class']) && isset($trace[$i + 1]['file'])
                && isset($trace[$i]['file'])
                && $trace[$i]['class'] == 'JBDump'
                && (substr($trace[$i]['file'], -$curFileLength, $curFileLength) == $curFile)
            ) {

            } else if (isset($trace[$i]['file'])
                && (substr($trace[$i]['file'], -$curFileLength, $curFileLength) == $curFile)
            ) {

            } else {
                // found!
                $meta['file'] = isset($trace[$i]['file']) ? $trace[$i]['file'] : '';
                $meta['line'] = isset($trace[$i]['line']) ? $trace[$i]['line'] : '';
                break;
            }
        }

        // get functions
        if (isset($trace[$j + 1])) {
            $result = $trace[$j + 1];
            $result['line'] = $meta['line'];
            $result['file'] = $meta['file'];
        } else {
            $result = $meta;
        }

        return $result;
    }

    /**
     * Get object methods
     *
     * @param   object  $object    Backtrace
     * @return  array
     */
    private function _getMethods($object)
    {
        if (is_string($object)) {
            $className = $object;
        } else {
            $className = get_class($object);
        }
        $methods = get_class_methods($className);

        if ($this->_config['sort']['methods']) {
            sort($methods);
        }
        return $methods;
    }

    /**
     * Get all info about class (object)
     *
     * @param   string|object  $data    Object or class name
     * @return  JBDump
     */
    private static function _getClass($data)
    {
        // check arg
        if (is_object($data)) {
            $className = get_class($data);
        } else if (is_string($data)) {
            $className = $data;
        } else {
            return false;
        }

        if (!class_exists($className) && !interface_exists($className)) {
            return false;
        }

        // create ReflectionClass object
        $class = new ReflectionClass($data);

        // get basic class info
        $result['name'] = $class->name;
        $result['type'] = ($class->isInterface() ? 'interface' : 'class');
        if ($classComment = $class->getDocComment()) {
            $result['comment'] = $classComment;
        }
        if ($classPath = $class->getFileName()) {
            $result['path'] = $classPath . ' ' . $class->getStartLine() . '/' . $class->getEndLine();
        }
        if ($classExtName = $class->getExtensionName()) {
            $result['extension'] = $classExtName;
        }
        if ($class->isAbstract()) {
            $result['abstract'] = true;
        }
        if ($class->isFinal()) {
            $result['final'] = true;
        }

        // get all parents of class
        $class_tmp = $class;
        $result['parents'] = array();
        while ($parent = $class_tmp->getParentClass()) {
            if (isset($parent->name)) {
                $result['parents'][] = $parent->name;
                $class_tmp = $parent;
            }
        }
        if (count($result['parents']) == 0) {
            unset($result['parents']);
        }

        // reflecting class interfaces
        $interfaces = $class->getInterfaces();
        if (is_array($interfaces)) {
            foreach ($interfaces as $property) {
                $result['interfaces'][] = $property->name;
            }
        }

        // reflection class constants
        $constants = $class->getConstants();
        if (is_array($constants)) {
            foreach ($constants as $key => $property) {
                $result['constants'][$key] = $property;
            }
        }

        // reflecting class properties 
        $properties = $class->getProperties();
        if (is_array($properties)) {
            foreach ($properties as $key => $property) {

                if ($property->isPublic()) {
                    $visible = "public";
                } elseif ($property->isProtected()) {
                    $visible = "protected";
                } elseif ($property->isPrivate()) {
                    $visible = "private";
                } else {
                    $visible = "public";
                }

                $propertyName = $property->getName();
                $result['properties'][$visible][$property->name]['comment'] = $property->getDocComment();
                $result['properties'][$visible][$property->name]['static'] = $property->isStatic();
                $result['properties'][$visible][$property->name]['default'] = $property->isDefault();
                $result['properties'][$visible][$property->name]['class'] = $property->class;
            }
        }

        // get source
        $source = false;
        if (isset($result['path']) && $result['path']) {
            $source = @file($class->getFileName());
        }

        // reflecting class methods 
        foreach ($class->getMethods() as $key => $method) {

            if ($method->isPublic()) {
                $visible = "public";
            } elseif ($method->isProtected()) {
                $visible = "protected";
            } elseif ($method->isPrivate()) {
                $visible = "private";
            } else {
                $visible = "public";
            }

            $result['methods'][$visible][$method->name]['name'] = $method->getName();

            if ($method->isAbstract()) {
                $result['methods'][$visible][$method->name]['abstract'] = true;
            }
            if ($method->isFinal()) {
                $result['methods'][$visible][$method->name]['final'] = true;
            }
            if ($method->isInternal()) {
                $result['methods'][$visible][$method->name]['internal'] = true;
            }
            if ($method->isStatic()) {
                $result['methods'][$visible][$method->name]['static'] = true;
            }
            if ($method->isConstructor()) {
                $result['methods'][$visible][$method->name]['constructor'] = true;
            }
            if ($method->isDestructor()) {
                $result['methods'][$visible][$method->name]['destructor'] = true;
            }
            $result['methods'][$visible][$method->name]['declaringClass'] = $method->getDeclaringClass()->name;

            if ($comment = $method->getDocComment()) {
                $result['methods'][$visible][$method->name]['comment'] = $comment;
            }

            $startLine = $method->getStartLine();
            $endLine = $method->getEndLine();
            if ($startLine && $source) {
                $from = (int)($startLine - 1);
                $to = (int)($endLine - $startLine + 1);
                $slice = array_slice($source, $from, $to);
                $phpCode = implode('', $slice);
                $result['methods'][$visible][$method->name]['source::source'] = $phpCode;
            }

            if ($params = self::_getParams($method->getParameters(), $method->isInternal())) {
                $result['methods'][$visible][$method->name]['parameters'] = $params;
            }
        }
        
        // get all methods
        $result['all_methods'] = get_class_methods($className);
        sort($result['all_methods']);

        // sorting properties and methods
        if (isset($result['properties']['protected'])) {
            ksort($result['properties']['protected']);
        }
        if (isset($result['properties']['private'])) {
            ksort($result['properties']['private']);
        }
        if (isset($result['properties']['public'])) {
            ksort($result['properties']['public']);
        }
        if (isset($result['methods']['protected'])) {
            ksort($result['methods']['protected']);
        }
        if (isset($result['methods']['private'])) {
            ksort($result['methods']['private']);
        }
        if (isset($result['methods']['public'])) {
            ksort($result['methods']['public']);
        }

        return $result;
    }

    /**
     * Get function/method params info
     * @param $params Array of ReflectionParameter
     * @param bool $isInternal
     * @return array
     */
    private function _getParams($params, $isInternal = true)
    {

        if (!is_array($params)) {
            $params = array($params);
        }

        $result = array();
        foreach ($params as $param) {
            $optional = $param->isOptional();
            $paramName = (!$optional ? '*' : '') . $param->name;
            $result[$paramName]['name'] = $param->getName();
            if ($optional && !$isInternal) {
                $result[$paramName]['default'] = $param->getDefaultValue();
            }
            if ($param->allowsNull()) {
                $result[$paramName]['null'] = true;
            }
            if ($param->isArray()) {
                $result[$paramName]['array'] = true;
            }
            if ($param->isPassedByReference()) {
                $result[$paramName]['reference'] = true;
            }
        }

        return $result;
    }

    /**
     * Get all info about function
     * @static
     * @param   string|function $functionName Function or function name
     * @return  array|bool
     */
    private static function _getFunction($functionName)
    {
        if (is_string($functionName) && !function_exists($functionName)) {
            return false;

        } else if (empty($functionName)) {
            return false;

        }

        // create ReflectionFunction instance
        $func = new ReflectionFunction($functionName);

        // get basic function info
        $result = array();
        $result['name'] = $func->getName();
        $result['type'] = $func->isInternal() ? 'internal' : 'user-defined';
        if ($namespace = $func->getNamespaceName()) {
            $result['namespace'] = $namespace;
        }
        if ($func->isDeprecated()) {
            $result['deprecated'] = true;
        }
        if ($static = $func->getStaticVariables()) {
            $result['static'] = $static;
        }
        if ($reference = $func->returnsReference()) {
            $result['reference'] = $reference;
        }
        if ($path = $func->getFileName()) {
            $result['path'] = $path . ' ' . $func->getStartLine() . '/' . $func->getEndLine();
        }
        if ($parameters = $func->getParameters()) {
            $result['parameters'] = self::_getParams($parameters, $func->isInternal());
        }

        // get function source
        if (isset($result['path']) && $result['path']) {
            $result['comment'] = $func->getDocComment();

            $startLine = $func->getStartLine();
            $endLine = $func->getEndLine();
            $source = @file($func->getFileName());

            if ($startLine && $source) {
                $from = (int)($startLine - 1);
                $to = (int)($endLine - $startLine + 1);
                $slice = array_slice($source, $from, $to);
                $result['source::source'] = implode('', $slice);
            }
        }

        return $result;
    }

    /**
     * Get all info about function
     *
     * @static
     * @param string|function  $extensionName    Function or function name
     * @return array|bool
     */
    private static function _getExtension($extensionName)
    {
        if (!extension_loaded($extensionName)) {
            return false;
        }

        $ext = new ReflectionExtension($extensionName);
        $result = array();

        $result['name'] = $ext->name;
        $result['version'] = $ext->getVersion();
        if ($constants = $ext->getConstants()) {
            $result['constants'] = $constants;
        }
        if ($classesName = $ext->getClassNames()) {
            $result['classesName'] = $classesName;
        }
        if ($functions = $ext->getFunctions()) {
            $result['functions'] = $functions;
        }
        if ($dependencies = $ext->getDependencies()) {
            $result['dependencies'] = $dependencies;
        }
        if ($INIEntries = $ext->getINIEntries()) {
            $result['INIEntries'] = $INIEntries;
        }

        $functions = $ext->getFunctions();
        if (is_array($functions) && count($functions) > 0) {
            $result['functions'] = array();
            foreach ($functions as $function) {
                $funcName = $function->getName();
                $result['functions'][$funcName] = self::_getFunction($funcName);
            }
        }

        return $result;
    }

    /**
     * Get all file info
     *
     * @param   string  $path
     * @return  array|bool
     */
    private function _pathInfo($path)
    {
        $result = array();

        $filename = realpath($path);

        $result['realpath'] = $filename;
        $result = array_merge($result, pathinfo($filename));

        $result['type'] = filetype($filename);
        $result['exist'] = file_exists($filename);
        if ($result['exist']) {

            $result['time created'] = filectime($filename).' / '.date(self::DATE_FORMAT, filectime($filename));
            $result['time modified'] = filemtime($filename).' / '.date(self::DATE_FORMAT, filemtime($filename));
            $result['time access'] = fileatime($filename).' / '.date(self::DATE_FORMAT, fileatime($filename));

            $result['group'] = filegroup($filename);
            $result['inode'] = fileinode($filename);
            $result['owner'] = fileowner($filename);
            $perms = fileperms($filename);

            if (($perms & 0xC000) == 0xC000) { // Socket
                $info = 's';
            } elseif (($perms & 0xA000) == 0xA000) { // Symbolic Link
                $info = 'l';
            } elseif (($perms & 0x8000) == 0x8000) { // Regular
                $info = '-';
            } elseif (($perms & 0x6000) == 0x6000) { // Block special
                $info = 'b';
            } elseif (($perms & 0x4000) == 0x4000) { // Directory
                $info = 'd';
            } elseif (($perms & 0x2000) == 0x2000) { // Character special
                $info = 'c';
            } elseif (($perms & 0x1000) == 0x1000) { // FIFO pipe
                $info = 'p';
            } else { // Unknown
                $info = 'u';
            }

            // owner
            $info .= (($perms & 0x0100) ? 'r' : '-');
            $info .= (($perms & 0x0080) ? 'w' : '-');
            $info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x') : (($perms & 0x0800) ? 'S' : '-'));

            // group
            $info .= (($perms & 0x0020) ? 'r' : '-');
            $info .= (($perms & 0x0010) ? 'w' : '-');
            $info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x') : (($perms & 0x0400) ? 'S' : '-'));

            // other
            $info .= (($perms & 0x0004) ? 'r' : '-');
            $info .= (($perms & 0x0002) ? 'w' : '-');
            $info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x') : (($perms & 0x0200) ? 'T' : '-'));

            $result['perms'] = $perms . ' / ' . $info;

            if ($result['type'] == 'file') {
                $size = filesize($filename);
                $result['size'] = $size . ' / ' . self::_formatSize($size);
            }
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * Convert trace infomation to readable
     *
     * @param   array   $trace Standart debug backtrace data
     * @return  array
     */
    public function convertTrace($trace)
    {
        $result = array();
        foreach ($trace as $key => $info) {
            $oneTrace = self::i()->_getOneTrace($info, false);
            $result['#' . ($key - 1) . ' ' . $oneTrace['func']] = $oneTrace;
        }

        return $result;
    }

    /**
     * Get PHP error types
     *
     * @return  array
     */
    private function _getErrorTypes()
    {
        $errType = array(
            E_ERROR => 'Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parsing Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Runtime Notice',
            E_RECOVERABLE_ERROR => 'Catchable Fatal Error',
        );

        if (defined('E_DEPRECATED')) {
            $errType[E_DEPRECATED] = 'Deprecated';
            $errType[E_USER_DEPRECATED] = 'User Deprecated';
        }

        return $errType;
    }

    /**
     * Error handler for PHP errors
     *
     * @param   integer $errNo
     * @param   string  $errMsg
     * @param   string  $errFile
     * @param   integer $errLine
     * @param   array   $errCont
     * @return  bool
     */
    function errorHandler($errNo, $errMsg, $errFile, $errLine, $errCont)
    {
        if (!($errNo & error_reporting())) {
            return false;
        }

        $errType = $this->_getErrorTypes();

        $errFile = $this->_getRalativePath($errFile);
        $result = array(
            'file' => $errFile . ' : ' . $errLine,
            'type' => $errType[$errNo] . ' (' . $errNo . ')',
            'message' => $errMsg,
        );

        if ($this->_config['handler']['context']) {
            $result['context'] = $errCont;
        }

        $trace = debug_backtrace();
        unset($trace[0]);
        $result['backtrace'] = $this->convertTrace($trace);

        if ($this->_isLiteMode()) {

            $errorInfo = array(
                'message' => $result['type'].' / '.$result['message'],
                'file'    => $result['file']
            );

            $this->_dumpLite($errorInfo, '* '.$errType[$errNo]);

        } else {
            $this->dump($result, '<b style="color:red;">*</b> ' . $errType[$errNo] . ' / ' . htmlSpecialChars($result['message']));
        }

        return true;
    }

    /**
     * Exception handler
     *
     * @param   Exception   $exception PHP exception object
     * @return  boolean
     */
    function exceptionHandler($exception)
    {
        $result['message'] = $exception->getMessage();
        $result['backtrace'] = $this->convertTrace($exception->getTrace());
        $result['string'] = $exception->getTraceAsString();
        $result['code'] = $exception->getCode();

        if ($this->_isLiteMode()) {
            $this->_dumpLite($result['string'], 'EXCEPTION / ' . htmlSpecialChars($result['message']));

        } else {
            $this->_initAssets(true);
            $this->dump($result, '<b style="color:red;">**</b> EXCEPTION / ' . htmlSpecialChars($result['message']));
        }

        return true;
    }

    /**
     * Information about current PHP reporting
     *
     * @return  JBDump
     */
    public function errors()
    {
        $result = array();
        $result['error_reporting'] = error_reporting();
        $errTypes = self::_getErrorTypes();

        foreach ($errTypes as $errTypeKey => $errTypeName) {
            if ($result['error_reporting'] & $errTypeKey) {
                $result['show_types'][] = $errTypeName . ' (' . $errTypeKey . ')';
            }
        }

        return self::i()->dump($result, '! errors info !');
    }

    /**
     * Is current request ajax or lite mode is enabled
     * 
     * @return  bool
     */
    private function _isLiteMode()
    {
        $headers = apache_request_headers();
        
        if ($this->_config['lite_mode']) {
            return true;

        } else {
            return self::isAjax();

        }

        return false;
    }

    /**
     * Check is current HTTP request is ajax
     *
     * @static
     * @return  bool
     */
    public static function isAjax()
    {
        if (function_exists('apache_request_headers')) {
            
            $headers = apache_request_headers();
            foreach ($headers as $key => $value) {
                if (strtolower($key) == 'x-requested-with' && strtolower($value) == 'xmlhttprequest') {
                    return true;
                }
            }
        
        } else if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            return true;
            
        } else if (isset($_REQUEST['ajax']) && $_REQUEST['ajax']) {
            return true;

        } else if (isset($_REQUEST['AJAX']) && $_REQUEST['AJAX']) {
            return true;

        }
        
        return false;
    }
    
    /**
     * Enable or disable lite mode
     *
     * @static
     * @param bool $enabled
     * @return  JBDump
     */
    public static function lite($enabled = false)
    {
        return self::i()->_config['lite_mode'] = (bool)$enabled;
    }

}

/**
 * Alias for JBDump::dump() with additions params
 *
 * @param   mixed   $var    Variable
 * @param   string  $name   Variable name
 * @param   bool    $isDie  Die after dump
 * @return  JBDump
 */
function JBDump($var = 'JBDump::variable no set', $isDie = true, $name = '...')
{
    $_this = JBDump::i();

    if ($var !== 'JBDump::variable no set') {
        if ($_this->isDebug()) {
            $_this->dump($var, $name);
            if ($isDie) {
                die('JBDump_die');
            }
        }
    }

    return $_this;
}
