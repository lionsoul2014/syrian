<?php if ( ! defined('BASEPATH') ) exit('No Direct Access Allowed!');
/**
 * Syrian Initialize Script
 * Load the common functons and base classes
 *
 * @author    chenxin <chenxin619315@gmail.com>
 * @link    http://www.lionsoul.org/syrian
 */

 //--------------------------------------------------------------
 
//Syrian Version Number
define('SR_VERSION', '1.2.4');

//sapi mode define
defined('SR_CLI_MODE')      or define('SR_CLI_MODE', strncmp(php_sapi_name(), 'cli', 3)=='cli');
defined('SR_FLUSH_MODE')    or define('SR_FLUSH_MODE',  'flush_mode');
defined('SR_IGNORE_MODE')   or define('SR_IGNORE_MODE', 'ignore_mode');

//check and define the including components
//0x01: Function
//0x02: Loader
//0x04: Helper
//0x08: Input
//0x10: Uri
//0x20: Output
//0x40: Model
//0x80: Controller
//0xFF: all of them
//0x47: cli mode
//0x7F: missing controller
defined('SR_INC_COMPONENTS') or define('SR_INC_COMPONENTS', 0xFF);

//Load the common resource loader

/**
 * global run time resource
*/
if ( ! function_exists('_G') ) {
    function _G($key, $val=NULL)
    {
        static $_GRE = array();
        
        if ( is_array($key) ) {
            foreach ( $key as $k => $v ) {
                $_GRE[$k] = $v;
            }

            return true;
        }

        if ( $val == NULL ) {
            return isset($_GRE["{$key}"]) ? $_GRE["{$key}"] : NULL;
        }

        $_GRE["{$key}"] = &$val;

        return true;
    }
}

/**
 * cli supper global initialize function
*/
if ( ! function_exists('_cli_initialize') ) {
    function _cli_initialize()
    {
        $argv   = &$_SERVER['argv'];
        //1. parse and define the SCRIPT_FILENAME
        $script_name = array_shift($argv);
        $_SERVER['SCRIPT_FILENAME'] = $script_name;
        $_SERVER['REQUEST_URI']     = NULL;
        $_SERVER['QUERY_STRING']    = NULL;

        if ( count($argv) < 1 ) return;

        //2. parse and define the REQUEST_URI and QUERY_STRING
        //  and make sure the REQUEST_URI start with /
        if ( strlen($argv[0]) > 0 && strncmp($argv[0], '-', 1) != 0 ) {
            $request_uri = array_shift($argv);
            if ( $request_uri[0] != '/' ) {
                $request_uri = "/{$request_uri}";
            }

            $_SERVER['REQUEST_URI'] = $request_uri;
            if ( ($queryPos = strpos($request_uri, '?')) !== false ) {
                $query_string = substr($request_uri, $queryPos + 1);
                $_SERVER['QUERY_STRING'] = $query_string;

                $sIdx       = 0;
                $query_len  = strlen($query_string);
                for ( $i = 0; $i < $query_len; $i++ ) {
                    //get argument name
                    $eIdx   = strpos($query_string, '=', $sIdx);
                    if ( $eIdx === false ) break;
                    $args_name = substr($query_string, $sIdx, $eIdx - $sIdx);

                    /**
                     * both '&' and ':' could be as the arguments
                     * separate mark At 2016-01-22
                    */
                    $sIdx   = $eIdx + 1;
                    $eIdx   = strpos($query_string, ':', $sIdx);
                    if ( $eIdx === false ) {
                        $eIdx = strpos($query_string, '&', $sIdx);
                    }

                    if ( $eIdx === false ) {
                        if ( $sIdx >= $query_len ) break;
                        $args_val = substr($query_string, $sIdx);
                    } else {
                        //get the argument value
                        $args_val = substr($query_string, $sIdx, $eIdx - $sIdx);
                        $sIdx     = $eIdx + 1;
                    }

                    //load them to the $_GET and $_POST global
                    $_GET[$args_name]   = $args_val;
                    $_POST[$args_name]  = $args_val;
                }
            }
        }

        //additional _SERVER arguments parse
        $args_num    = count($argv);
        if ( $args_num > 0 ) {
            for ( $i = 0; $i < $args_num; $i++ ) {
                $args_name = $argv[$i];
                if ( strlen($args_name) > 1 && $args_name[0] != '-' ) {
                    continue;
                }

                if ( $i < $args_num ) {
                    $args_name = str_replace('-', '_', substr($args_name, 1));
                    $_SERVER[strtoupper($args_name)] = $argv[++$i];
                }
            }
        }

    }
}

/**
 * import the specifield class
 *
 * @param   $cls_path class path like db.dbFactory
 * @param   $_inc If $_inc is True check the syrian/lib or check under SR_LIBPATH
*/
if ( ! function_exists('import') ) {
    function import($cls_path, $_inc=true)
    {
        static $_loadedClass = array();

        $path = str_replace('.', '/', $cls_path);
        if ( isset($_loadedClass[$path]) ) {
            unset($path);
            return true;
        }
        
        //Look for the class in the SYSPATH/lib folder if $_inc is TRUE
        //Or check the SR_LIBPATH
        $_dir   = (($_inc) ? BASEPATH . 'lib/' : SR_LIBPATH);
        $_dir  .= $path;
        $found  = false;

        foreach ( array("{$_dir}.class.php", "{$_dir}.php") as $_file ) {
            if ( file_exists($_file) ) {
                require $_file;
                $_loadedClass[$path] = true;
                $found = true;
                break;
            }
        }

        unset($path, $_dir);
        if ( $found == true ) {
            return true;
        }
        
        throw new Exception("import#Unable to load class with path {$cls_path}");
    }
}

/**
 * import the specifield configuration
 * and return the return of the included file as the final result
 *
 * @param   $config_path
 * @param   $_inc @see #import
 * @param   $cache cache the file incldue ?
 * @usage
 * $conf = config('db.hosts');
 * $mysql = config('db.hosts#mysql');
*/
if ( ! function_exists('config') ) {
    function config($config_path, $_inc=false, $cache=true)
    {
        static $_loadedConf = array();

        if ( ($sIdx = strpos($config_path, '#')) !== false ) {
            $path = str_replace('.', '/', substr($config_path, 0, $sIdx));
            $keys = explode('.', substr($config_path, $sIdx + 1));
        } else {
            $path = str_replace('.', '/', $config_path);
            $keys = NULL;
        }

        //check and load the configure
        $found = true;
        if ( ! isset($_loadedConf[$path]) || $cache == false ) {
            $_dir  = (($_inc) ? BASEPATH . '/config/' : SR_CONFPATH);
            $_dir .= $path;
            $found = false;

            //search the config file and include it
            foreach (array("{$_dir}.conf.php", "{$_dir}.php") as $_file ) {
                if ( file_exists($_file) ) {
                    $found = true;
                    $_loadedConf[$path] = include $_file;
                    break;
                }
            }
            
            unset($_dir);
        }

        if ( $found == false ) {
            unset($path, $keys);
            throw new Exception("config#Unable to load configure with path {$config_path}");
        }

        if ( $keys == NULL ) {
            return $_loadedConf[$path];
        }

        $vals = $_loadedConf[$path];
        foreach ( $keys as $key ) {
            if ( ! isset($vals[$key]) ) {
                throw new Exception("config#Invalid key {$key}");
            }

            $vals = $vals[$key];
        }

        unset($path, $keys);
        return $vals;
    }
}

/**
 * load the specified model with the given path
 *
 * @param   $model_path
 * @param   $cache check the cache first ?
 * @return  Object the object of the loaded model 
*/
if ( ! function_exists('model') ) {
    function model($model_path, $cache=true)
    {
        static $_loadedModel = array();

        if ( $cache == true 
            && isset($_loadedModel[$model_path]) ) {
            return $_loadedModel[$model_path];
        }

        if ( ($sIdx = strrpos($model_path, '.')) !== false ) {
            //@Note: the 3rd arguments set to $sIdx+1
            //so the path will always end with '/'
            $path  = str_replace('.', '/', substr($model_path, 0, $sIdx + 1));
            $model = substr($model_path, $sIdx + 1);
        } else {
            $path  = NULL;
            $model = $model_path;
        }
        
        //model base directory
        $_dir = SR_MODELPATH . "{$path}{$model}";
        foreach ( array("{$_dir}.model.php", "{$_dir}.php") as $_file ) {
            if ( file_exists( $_file ) ) {
                if ( ! isset($_loadedModel[$model_path]) ) {
                    include $_file;
                }

                $class = "{$model}Model";
                if ( class_exists($class) )  {
                    $obj = new $class();
                } else {
                    $obj = new $model();
                }

                //mark loaded for the current model
                $_loadedModel[$model_path] = $obj;
                unset($path, $model, $_dir, $class);
                return $obj;
            }
        }
        
        throw new Exception("model#Unable to load model with path {$model}");
    }
}

/**
 * load and create then return the specified helper
 *
 * @param   $helper_path
 * @param   $conf
 * @param   $_inc @see #import
 * @param   $cache cache the instance ?
 * @return  Object
*/
if ( ! function_exists('helper') ) {
    function helper($helper_path, $conf=NULL, $_inc=false, $cache=true)
    {
        static $_loadedHelper = array();

        if ( $cache == true 
            && isset($_loadedHelper[$helper_path]) ) {
            return $_loadedHelper[$helper_path];
        }

        if ( ($sIdx = strrpos($helper_path, '.')) !== false ) {
            //@Note: see #model
            $path   = str_replace('.', '/', substr($helper_path, 0, $sIdx + 1));
            $helper = substr($helper_path, $sIdx + 1);
        } else {
            $path   = NULL;
            $helper = $helper_path;
        }
        
        //Look for the class in the SYSPATH/lib folder if $_inc is TRUE
        //Or check the APPPATH/lib 
        $_dir  = (($_inc) ? BASEPATH . '/helper/' : SR_HELPERPATH);
        $_dir .= "{$path}{$helper}";
        
        foreach( array("{$_dir}.helper.php", "{$_dir}.php") as $_file ) {
            if ( file_exists($_file) ) {
                if ( ! isset($_loadedHelper[$helper_path]) ) {
                    require $_file;
                }

                $class = "{$helper}Helper";
                $obj   = new $class($conf);
                $_loadedHelper[$helper_path] = $obj;
                unset($path, $helper, $_dir, $class);
                return $obj;
            }
        }
        
        throw new Exception("helper#Unable to load helper with path {$helper_path}");
    }
}

/**
 * abort the current request by the specified http error code
 *
 * @param   $http_code
*/
if ( ! function_exists('abort') ) {
    function abort($http_code)
    {
        http_response_code($http_code);
        exit();
    }
}

/**
 * search the specified template and return the executed dynamic content
 *
 * @param   $tpl_file
 * @param   $variables
 * @param   $sanitize
 * @param   $timer  view compile cache time in seconds
 * @return  string
*/
if ( ! function_exists('view') ) {
    function view($tpl, $vars, $sanitize=false, $timer=0)
    {
        $viewObj = _G('__view_fnt__');
        if ( $viewObj == NULL ) {
            import('view.ViewFactory');
            $conf = array(
                'cache_time' => $timer,
                'tpl_dir'    => SR_VIEWPATH,
                'cache_dir'  => SR_CACHEPATH.'tpl/'
            );

            $viewObj = ViewFactory::create('html', $conf);
            _G('__view_fnt__', $viewObj);
        }

        //load all the variables to the current view
        return $viewObj->load($vars)->getContent($tpl, $sanitize);
    }
}

class Loader
{
    /**
     * all the loaded class
     *
     * @access private
    */
    private static $_loadedClass = array();

    /**
     * all the loaded model object
     *
     * @access private
    */
    private static $_loadedModel = array();

    /**
     * all the loaded helper object
     *
     * @access private
    */
    private static $_loadedHelper = array();


    /**
     * make construct method private
    */   
    private function __construct() {}
    
    /**
     * Import class file from the specified path
     * The function will check script file $_path.class.php first
     *     and then $_path.php
     *
     * @param   $_class
     * @param   $_section
     * @param   $_inc   If $_inc is TRUE check the syrian/lib  or check APPPATH/lib
     * @param   $_exit  exit for not found the specifield class
     * @return  bool    true for loaded successfully and false for not
     */
    public static function import($_class, $_section=NULL, $_inc=true, $_exit=true)
    {
        //$_class = ucfirst($_class);
        $_cls = ($_section == NULL) ? $_class : str_replace('.', '/', $_section).'/'.$_class;
        if ( isset(self::$_loadedClass[$_cls]) ) return true;
        
        //Look for the class in the SYSPATH/lib folder if $_inc is TRUE
        //Or check the APPPATH/lib 
        $_dir  = (($_inc) ? BASEPATH . '/lib/' : SR_LIBPATH);
        $_dir .= $_cls;
        
        foreach( array($_dir . '.class.php', $_dir . '.php') as $_file ) {
            if ( file_exists($_file) ) {
                require $_file;
                self::$_loadedClass[$_cls] = true;
                return true;
            }
        }
        
        if ( $_exit ) {
            throw new Exception('Syrian:Loader#import: Unable to load class ' . $_class);
        }

        return false;
    }
    
    
    /**
     * function to load data from the specified file
     *     and return the return of the included file as the final result
     *
     * @param   $_config
     * @param   $_section
     * @param   $_inc   True for seach files in syrian/config
     * @param   $_key   specifield key
     * @Param   $_exit  exit for not found the config file ? 
     * @return  mixed(Array, Object, Bool)
     */
    public static function config($_config, $_section=NULL, $_inc=false, $key=NULL, $_exit=true)
    {
        //make the included file name
        $_dir = (($_inc) ? BASEPATH . '/config/' : SR_CONFPATH);
        
        //append the section
        if ( $_section != NULL ) {
            $_dir .= str_replace('.', '/', $_section) . '/';
        }

        $_dir .= $_config;
        
        //search the config file and include it
        foreach ( array($_dir . '.conf.php', $_dir . '.php' ) as $_file ) {
            if ( file_exists($_file) ) {
                //return include $_file;
                $conf = include $_file;
                if ( $key != NULL ) {
                    return isset($conf["{$key}"]) ? $conf["{$key}"] : NULL;
                }

                return $conf;
            }
        }
        
        //throw new Exception('No such file or directory');
        if ( $_exit ) {
            throw new Exception('Syrian:Loader#config: Unable to load config ' . $_config);
        }

        return false;
    }
    
    /**
     * function to load the specifile model maybe from the
     *         specifile path and return the instance of the model
     *
     * @param   $_model
     * @param   $_section
     * @param   $_exit exit for not found the model
     * @return  Object
    */
    public static function model($_model, $_section=NULL, $_exit=true)
    {
        $_model = ucfirst($_model);
        
        //check the loaded of the class
        $_cls = ($_section == NULL) ? $_model : str_replace('.', '/', $_section).'/'.$_model;
        if ( isset(self::$_loadedModel[$_cls] ) ) {
            return self::$_loadedModel[$_cls];
        }
        
        //model base directory
        $_dir = SR_MODELPATH . $_cls;
            
        foreach ( array( $_dir . '.model.php', $_dir . '.php' ) as $_file ) {
            if ( file_exists( $_file ) ) {
                include $_file;                //include the model class file
                
                $o = NULL;
                $_class = $_model.'Model';
                if ( class_exists($_class) )  {
                    $o = new $_class();
                } else {
                    $o = new $_model();
                }

                //mark loaded for the current class
                self::$_loadedModel[$_cls] = $o;

                return $o;
            }
        }
        
        if ( $_exit ) {
            throw new Exception('Syrain:Loader#model: Unable to load model ' . $_model);
        }

        return false;
    }

    /**
     * function to load and create helper instance
     *
     * @param   $_helper
     * @param   $_section
     * @param   $_inc   True for seach files in syrian/helper
     * @param   $_conf  configuration to create the instance
     * @param   $_exit  exit for not found the helper
     * @return  mixed(Array, Object, Bool)
     */
    public static function helper($_helper, $conf=NULL, $_section=NULL, $_inc=false, $_exit=true)
    {
        //$_class = ucfirst($_class);
        $_cls = ($_section == NULL) ? $_helper : str_replace('.', '/', $_section).'/'.$_helper;
        if ( isset(self::$_loadedHelper[$_cls]) ) {
            return self::$_loadedHelper[$_cls];
        }
        
        //Look for the class in the SYSPATH/lib folder if $_inc is TRUE
        //Or check the APPPATH/lib 
        $_dir  = (($_inc) ? BASEPATH . '/helper/' : SR_HELPERPATH);
        $_dir .= $_cls;
        
        foreach( array($_dir . '.helper.php', $_dir . '.php') as $_file ) {
            if ( file_exists($_file) ) {
                require $_file;
                $_class = $_helper.'Helper';
                $obj    = new $_class($conf);
                self::$_loadedHelper[$_cls] = &$obj;
                return $obj;
            }
        }
        
        if ( $_exit ) {
            throw new Exception('Syrian:Loader#helper: Unable to load helper ' . $_helper);
        }

        return false;
    }
}

class Helper
{
    /**
     * Construct method to create new instance of the Helper
     *
     * @param    $conf
    */
    public function __construct($conf)
    {
    }

    /**
     * load the specifield method by name
     *
     * @param    $args
    */
    public function load()
    {
        $_argv = func_get_args();
        $_args = func_num_args();
        if ( $_args > 0 && method_exists($this, $_argv[0]) ) {
            $cacher    = array_shift($_argv);
            return $this->{$cacher}($_argv);
        }

        exit("Error: Unable to load cacher {$_argv[0]}\n");
    }
}

//Load the input class manage the input of the controller/
if ( (SR_INC_COMPONENTS & 0x08) != 0 ) {
    defined('OP_NULL')      or define('OP_NULL',        1 <<  0);
    defined('OP_LATIN')     or define('OP_LATIN',       1 <<  1);
    defined('OP_URL')       or define('OP_URL',         1 <<  2);
    defined('OP_EMAIL')     or define('OP_EMAIL',       1 <<  3);
    defined('OP_QQ')        or define('OP_QQ',          1 <<  4);
    defined('OP_DATE')      or define('OP_DATE',        1 <<  5);
    defined('OP_NUMERIC')   or define('OP_NUMERIC',     1 <<  6);
    defined('OP_STRING')    or define('OP_STRING',      1 <<  7);
    defined('OP_ZIP')       or define('OP_ZIP',         1 <<  8);
    defined('OP_CELLPHONE') or define('OP_CELLPHONE',   1 <<  9);
    defined('OP_TEL')       or define('OP_TEL',         1 << 10);
    defined('OP_IDENTIRY')  or define('OP_IDENTIRY',    1 << 11);

    //santilize type
    defined('OP_SANITIZE_TRIM')     or define('OP_SANITIZE_TRIM',   1 << 0);
    defined('OP_SANITIZE_SCRIPT')   or define('OP_SANITIZE_SCRIPT', 1 << 1);
    defined('OP_SANITIZE_HTML')     or define('OP_SANITIZE_HTML',   1 << 2);
    defined('OP_MAGIC_QUOTES')      or define('OP_MAGIC_QUOTES',    1 << 3);
    defined('OP_SANITIZE_INT')      or define('OP_SANITIZE_INT',    1 << 4);
    defined('OP_LOWERCASE')         or define('OP_LOWERCASE',       1 << 5);
    defined('OP_UPPERCASE')         or define('OP_UPPERCASE',       1 << 6);

    if ( ! function_exists('OP_LIMIT') )
    {
        function OP_LIMIT( $s, $e = -1 ) {return ( $e == -1 ? array(0, $s) : array(0, $s, $e) );}
    }

    if ( ! function_exists('OP_SIZE') )
    {
        function OP_SIZE( $s, $e = -1 ) {return ($e == -1 ? array(1, $s) : array(1, $s, $e));}
    }

    class Input
    {
        private static $_loaded = false;
        
        public function __construct()
        {
           //Do nothing here
        }
        
        /**
         * check and load the Filter class if it is not load
         *
         * @see    lib.util.filter.Filter
        */
        private static function checkAndLoadFilter()
        {
            //check the load status of Filter class
            if ( self::$_loaded == false ) {
                Loader::import('Filter');
                self::$_loaded = true;
            }
        }

        /**
         * string to boolean
         * string true, integer larger than 0 will be consider to true or false 
         *
         * @param   $val
         * @return  boolean
        */
        public static function string2Boolean($val)
        {
            if ( is_bool($val) ) {
                return $val;
            } else if ( is_string($val) ) {
                $val = strtolower($val);
                if ( $val == 'true' ) return true;
                else if ( $val == 'false' ) return false;
                else {
                    $i = intval($val);
                    return $i > 0 ? true : false;
                }
            } else if ( is_integer($val) ) {
                return $val > 0 ? true : false;
            }

            return false;
        }
       
       /**
        * fetch item from $_GET data source
        *
        * @param    $_key
        * @param    $_model
        * @param    $_default
        * @param    $_errno
        * @return    Mixed(Array, String, Bool)
       */
        public function get( $_key, $_model=NULL, $_default=false, &$_errno=NULL )
        {
            if ( ! isset( $_GET[$_key] ) ) return $_default;
            
            //apply the model if it is not null
            if ( $_model != NULL ) {
                //check the load status of Filter class
                self::checkAndLoadFilter();
                
                return Filter::get( $_GET, $_key, $_model, $_errno );
            }
            
            //normal string fetch
            return $_GET[$_key];
        }

        /**
         * fetch and item from $_GET data source
         * if the mapping is not exists or not match the filter rules 
         * the default value will be returned
         *
         * @param   $_key
         * @param   $_model
         * @param   $_must
         * @return  Mixed
        */
        public function getMust($_key, $_model, $_must)
        {
            $v = $this->get($_key, $_model, $_must);
            return $v===false ? $_must : $v;
        }
        
        /**
         * Fetch an integer form $_GET global array
         *
         * @param    $_key
         * @param    $_default
         * @param    $allow_nagative
         * @return    Mixed(Integer or false)
        */
        public function getInt( $_key, $_default=false, $allow_nagative=false )
        {
            if ( ! isset( $_GET[$_key] ) ) return $_default;
            
            $v = intval($_GET[$_key]);
            if ( $v < 0 && $allow_nagative == false ) {
                return false;
            }

            return $v;
        }

        /**
         * Fetch an boolean from $_GET global array
         *
         * @param   $_key
         * @return  boolean
        */
        public function getBoolean($_key)
        {
            if ( ! isset($_GET[$_key]) ) return false;
            return self::string2Boolean($_GET[$_key]);
        }

        /**
         * Fetch an Id from $_GET global array
         * 24-chars and 32 chars unique id
         *
         * @param   $_key
         * @param   $compatible compatible with the xxxInt ?
         * @param   $default
         * @return  Mixed String or false
        */
        public function getUID($_key, $compatible=false, $default=false)
        {
            if ( ! isset($_GET[$_key]) ) return $default;

            $v   = $_GET[$_key];
            $len = strlen($v);
            if ( $len == 24 || $len == 32 ) {
                return self::isValidUid($v) ? $v : false;
            } else if ( $compatible && $len <= 19 ) {
                return intval($v);
            }

            return false;
        }
        
        /**
         * fetch item from $_GET with a specifiel model
         *
         * @param    $_model
         * @param    $_errno
         * @return    Mixed
        */
        public function getModel( $_model, &$_errno=NULL )
        {
            //check the load status of Filter class
            self::checkAndLoadFilter();
                
            return Filter::loadFromModel($_GET, $_model, $_errno);
        }
        
        //----------------------------------------------------------
        
        /**
         * fetch item from $_POST data source
         *
         * @param    $_key
         * @param    $_model
         * @param    $_default
         * @param    $_errno
         * @return    Mixed
        */
        public function post( $_key, $_model=NULL, $_default=false, &$_errno=NULL )
        {
            if ( ! isset($_POST[$_key]) ) return $_default;
            
            //apply the model if it is not null
            if ( $_model != NULL ) {
                //check the load status of Filter class
                self::checkAndLoadFilter();
                
                return Filter::get( $_POST, $_key, $_model, $_errno );
            }
            
            //normal string fetch
            return $_POST[$_key];
        }

        /**
         * fetch and item from $_POST data source
         * if the mapping is not exists or not match the filter rules 
         * the default value will be returned
         *
         * @param   $_key
         * @param   $_model
         * @param   $_must
         * @return  Mixed
        */
        public function postMust($_key, $_model, $_must)
        {
            $v = $this->post($_key, $_model, $_must);
            return $v===false ? $_must : $v;
        }
        
        /**
         * Fetch an integer form $_POST global array
         *
         * @param    $_POST
         * @param    $_default
         * @param    $allow_nagative
         * @return    Mixed(Integer or false)
        */
        public function postInt( $_key, $_default=false, $allow_nagative=false )
        {
            if ( ! isset( $_POST[$_key] ) ) return $_default;
            
            $v = intval($_POST[$_key]);
            if ( $v < 0 && $allow_nagative == false ) {
                return false;
            }

            return $v;
        }

        /**
         * Fetch an boolean from $_POST global array
         *
         * @param   $_key
         * @return  boolean
        */
        public function postBoolean($_key)
        {
            if ( ! isset($_POST[$_key]) ) return false;
            return self::string2Boolean($_POST[$_key]);
        }
        
        /**
         * Fetch an Id from $_POST global array
         * 24-chars and 32 chars unique id
         *
         * @param   $_key
         * @param   $compatible
         * @return  Mixed String or false
        */
        public function postUID($_key, $compatible=false, $default=false)
        {
            if ( ! isset($_POST[$_key]) ) return $default;

            $v   = $_POST[$_key];
            $len = strlen($v);
            if ( $len == 24 || $len == 32 ) {
                return self::isValidUid($v) ? $v : false;
            } else if ( $compatible && $len <= 19 ) {
                return intval($v);
            }

            return false;
        }
        
        /**
         * fetch item from $_POST with a specifiel model
         *
         * @param    $_model
         * @param    $_errno
         * @return    Mixed
        */
        public function postModel( $_model, &$_errno=NULL )
        {
            //check the load status of Filter class
            self::checkAndLoadFilter();
            
            return Filter::loadFromModel($_POST, $_model, $_errno);
        }
        
        //----------------------------------------------------------
        
        /**
         * fetch item from $_COOKIE data source
         *
         * @param    $_key
         * @param    $_model
         * @param    $_default
         * @param    $_errno
         * @return    Mixed
        */
        public function cookie( $_key, $_model=NULL, $_default=false, &$_errno=NULL )
        {
            if ( ! isset($_COOKIE[$_key]) ) return $_default;
            
            //apply the model if it is not null
            if ( $_model != NULL ) {
                //check the load status of Filter class
                self::checkAndLoadFilter();
                
                return Filter::get( $_COOKIE, $_key, $_model, $_errno );
            }
            
            //normal string fetch
            return $_COOKIE[$_key];
        }

        /**
         * fetch and item from $_COOKIE data source
         * if the mapping is not exists or not match the filter rules 
         * the default value will be returned
         *
         * @param   $_key
         * @param   $_model
         * @param   $_must
         * @return  Mixed
        */
        public function cookieMust($_key, $_model, $_must)
        {
            $v = $this->cookie($_key, $_model, $_must);
            return $v===false ? $_must : $v;
        }
        
        /**
         * Fetch an integer form $_COOKIE global array
         *
         * @param    $_key
         * @param    $_default
         * @param    $allow_nagative
         * @return    Mixed(Integer or false)
        */
        public function cookieInt( $_key, $_default=false, $allow_nagative=false )
        {
            if ( ! isset( $_COOKIE[$_key] ) ) return $_default;
            
            $v = intval($_COOKIE[$_key]);
            if ( $v < 0 && $allow_nagative == false ) {
                return false;
            }

            return $v;
        }

        /**
         * Fetch an boolean from $_COOKIE global array
         *
         * @param   $_key
         * @return  boolean
        */
        public function cookieBoolean($_key)
        {
            if ( ! isset($_COOKIE[$_key]) ) return false;
            return self::string2Boolean($_COOKIE[$_key]);
        }

        /**
         * Fetch an Id from $_COOKIE global array
         * 24-chars and 32 chars unique id
         *
         * @param   $_key
         * @param   $compatible
         * @return  Mixed String or false
        */
        public function cookieUID($_key, $compatible=false, $default=false)
        {
            if ( ! isset($_COOKIE[$_key]) ) return $default;

            $v   = $_COOKIE[$_key];
            $len = strlen($v);
            if ( $len == 24 || $len == 32 ) {
                return self::isValidUid($v) ? $v : false;
            } else if ( $compatible && $len <= 19 ) {
                return intval($v);
            }

            return false;
        }
        
        /**
         * fetch item from $_COOKIE with a specifiel model
         *
         * @param    $_model
         * @param    $_errno
         * @return    Mixed
        */
        public function cookieModel( $_model, &$_errno=NULL )
        {
            //check the load status of Filter class
            self::checkAndLoadFilter();
                
            return Filter::loadFromModel($_COOKIE, $_model, $_errno);
        }
        
        //----------------------------------------------------------
        
        /**
         * fetch item from $_SESSION data source
         *
         * @param    $_key
         * @param    $_model
         * @param    $_default
         * @param    $_errno
         * @return    Mixed
        */
        public function session( $_key, $_model=NULL, $_default=false, &$_errno=NULL )
        {
            if ( ! isset($_SESSION[$_key]) ) return $_default;
            
            //apply the model if it is not null
            if ( $_model != NULL ) {
                //check the load status of Filter class
                self::checkAndLoadFilter();
                
                return Filter::get( $_SESSION, $_key, $_model, $_errno );
            }
            
            //normal string fetch
            return $_SESSION[$_key];
        }
        
        //----------------------------------------------------------
        
        /**
         * fetch item from $_REQUEST data source
         *
         * @param    $_key
         * @param    $_model
         * @param    $_default
         * @param    $_errno
         * @return    Mixed
        */
        public function request( $_key, $_model=NULL, $_default=false, &$_errno=NULL )
        {
            if ( ! isset($_REQUEST[$_key]) ) return $_default;
            
            //apply the model if it is not null
            if ( $_model != NULL ) {
                //check the load status of Filter class
                self::checkAndLoadFilter();
                
                return Filter::get( $_REQUEST, $_key, $_model, $_errno );
            }
            
            //normal string fetch
            return $_REQUEST[$_key];
        }

        /**
         * fetch and item from $_REQUEST data source
         * if the mapping is not exists or not match the filter rules 
         * the default value will be returned
         *
         * @param   $_key
         * @param   $_model
         * @param   $_must
         * @return  Mixed
        */
        public function requestMust($_key, $_model, $_must)
        {
            $v = $this->request($_key, $_model, $_must);
            return $v===false ? $_must : $v;
        }
        
        /**
         * Fetch an integer form $_REQUEST global array
         *
         * @param    $_key
         * @param    $_default
         * @param    $allow_nagative
         * @return    Mixed(Integer or false)
        */
        public function requestInt( $_key, $_default=false, $allow_nagative=false )
        {
            if ( ! isset( $_REQUEST[$_key] ) ) return $_default;
            
            $v = intval($_REQUEST[$_key]);
            if ( $v < 0 && $allow_nagative == false ) {
                return false;
            }

            return $v;
        }

        /**
         * Fetch an boolean from $_REQUEST global array
         *
         * @param   $_key
         * @return  boolean
        */
        public function requestBoolean($_key)
        {
            if ( ! isset($_REQUEST[$_key]) ) return false;
            return self::string2Boolean($_REQUEST[$_key]);
        }

        /**
         * Fetch an Id from $_REQUEST global array
         * 24-chars and 32 chars unique id
         *
         * @param   $_key
         * @param   $compatible
         * @return  Mixed String or false
        */
        public function requestUID($_key, $compatible=false, $default=false)
        {
            if ( ! isset($_REQUEST[$_key]) ) return $default;

            $v   = $_REQUEST[$_key];
            $len = strlen($v);
            if ( $len == 24 || $len == 32 ) {
                return self::isValidUid($v) ? $v : false;
            } else if ( $compatible && $len <= 19 ) {
                return intval($v);
            }

            return false;
        }
        
        /**
         * fetch item from $_REQUEST with a specifiel model
         *
         * @param    $_model
         * @param    $_errno
         * @return    Mixed
        */
        public function requestModel( $_model, &$_errno=NULL )
        {
            //check the load status of Filter class
            self::checkAndLoadFilter();
                
            return Filter::loadFromModel($_REQUEST, $_model, $_errno);
        }
        
        //----------------------------------------------------------
        
        /**
         * fetch item from $_SERVER data source
         *
         * @param    $_key
         * @param    $_model
         * @param    $_default
         * @param    $_errno
         * @return   Mixed
        */
        public function server( $_key, $_model=NULL, $_default=false, &$_errno=NULL )
        {
            if ( ! isset($_SERVER[$_key]) ) return $_default;
            
            //apply the model if it is not null
            if ( $_model != NULL ) {
                //check the load status of Filter class
                self::checkAndLoadFilter();
                
                return Filter::get( $_SERVER, $_key, $_model, $_errno );
            }
            
            //normal string fetch
            return $_SERVER[$_key];
        }
        
        //---------------------------------------------------------
        
        /**
         * fetch item from $_SERVER data source
         *
         * @param    $_key
         * @param    $_model
         * @param    $_default
         * @param    $_errno
         * @return    Mixed
        */
        public function env( $_key, $_model=NULL, $_default=false, &$_errno=NULL )
        {
            if ( ! isset($_ENV[$_key]) ) return $_default;
            
            //apply the model if it is not null
            if ( $_model != NULL ) {
                //check the load status of Filter class
                self::checkAndLoadFilter();
                
                return Filter::get( $_ENV, $_key, $_model, $_errno );
            }
            
            //normal string fetch
            return $_ENV[$_key];
        }


        //-----------------------------------------------------

        /**
         * check the specifiled string is made of ascii char
         * Only chars from a-z or 0-9
         *
         * @param   $string
         * @return  boolean
        */
        private static function isValidUid($string)
        {
            $len = strlen($string);
            for ( $i = 0; $i < $len; $i++ ) {
                $ascii = ord($string[$i]);
                if ( ($ascii >= 48 && $ascii <= 57) 
                    || ($ascii >= 97 && $ascii <= 122) ) {
                    continue;
                }

                return false;
            }

            return true;
        }

    }
}

//Load the Uri class offer qucik interface to access the request uri
if ( (SR_INC_COMPONENTS & 0x10) != 0 ) {
     //link style constants
     defined('URI_DIR_STYLE')    or define('URI_DIR_STYLE', 0);
     defined('URI_STD_STYLE')    or define('URI_STD_STYLE', 1);

     //---------------------------------------------------------
     
    abstract class Uri
    {
        //request url
        public $url         = NULL;
        public $self        = NULL;
        
        //request module/page
        public $section     = NULL;
        public $module      = NULL;
        public $page        = NULL;
        
        /**
         * request base part of the uri before the script file
         *      like    /syrian/skeleton/ of /syrian/skeleton/index.php
         *
         * @access  protected
        */
        protected $_base      = '/';
        
        /**
         * require rounter part of the url, eg: article/list
         *      of syrian/skeleton/index.php/article/list.html
         *
         * @access  protected
        */
        protected $_request   = NULL;
        
        //link style (@see Uri#style constants)
        protected $_style     = NULL;
        
        //request script file extension
        protected $_ext       = NULL;
        
        //use url rewrite, hide the 'index.php' in request url
        protected $_rewrite   = false;
        protected $_parts     = NULL;
        
        /**
         * construct method to initialize the class
         *
         * @param   $_rewrite   start the url rewrite?
         * @param   $_style get http request link style
        */
        public function __construct( $_rewrite = false, $_style = URI_STD_STYLE )
        {
            //copy
            $this->url  = $_SERVER['REQUEST_URI'];
            $this->self = $_SERVER['REQUEST_URI'];
            if ( ($args = strpos($this->self, '?') ) !== false ) {
                $this->self = substr($this->self, 0, $args);
            }
             
            //normalized the url and make sure it start with /
            if ( $this->url[0] != '/' )     $this->url  = "/{$this->url}";
            if ( $this->self[0] != '/' )    $this->self = "/{$this->self}";
            
            /*
             * Analysis and initialize the base
            */
            $self = $_SERVER['PHP_SELF'];
            if ( $self[0] != '/' ) $self = "/{$self}";

            if ( ($pos = stripos($self, '.php')) !== FALSE ) {
                while ( $self[$pos] != '/' ) $pos--;
                 //get the base part, include the '/' mark at $i
                if ( $pos > 0 ) $this->_base = substr($self, 0, $pos + 1);
            }

            $this->_rewrite = $_rewrite;
            $this->_style   = $_style;
        }
        
        /**
         * parse the current request url to find the module
         *  page arguments, also handler the arguments as need
         *
         * @return  bool
        */
        protected function parseUrl()
        {
            $_spos  = 0;     //start position to determine the request
            $_epos  = 0;     //end position to determine the request
            $pos    = 0;     //temp variable
            
            $_url   = substr_replace($this->url, '/', 0, strlen($this->_base));
            $_len   = strlen($_url);
            
            if ( ($pos = stripos($_url, '.php')) !== FALSE ) {
                $_spos = $pos + 4;
            }
            
            /*
             * move forward the start position, cause:
             * 1. exclude the '/' punctuation at 0 when match no '.php'
             * 2. skip the '/' punctuation after '.php' if available
            */
            if ( $_spos < $_len ) $_spos++;
            
            //check and find the end index
            $_args = stripos($_url, '?', $_spos);
            $_extp = stripos($_url, '.', $_spos);
            
            //determine the end index
            if ( $_args !== FALSE && $_extp !== FALSE ) {
                $_epos = min($_args, $_extp);
            } else {
                $_epos = max($_args, $_extp);
            }
            
            /*
             * mark the final end position
             *  And clear the last '/' punctuation if it is
             *
             * @Note: fixed at 2016/06/27
             * demo: /script/pc/download/
             * we consider the page to be NULL
            */
            if ( $_epos == FALSE ) $_epos = $_len;
            //if ( $_url[$_len - 1] == '/' ) $_epos--;
            $this->_request = substr($_url, $_spos, $_epos - $_spos);
            if ( $this->_request == '' ) return false;
            
            /*
             * split the request and parse to get the module and page info
             *      also, initialize the _parts globals variable here
            */
            $_ret = explode('/', $this->_request);
            $this->_parts = &$_ret;
            
            return true;
        }
        
        /**
         * Exit the current process and tell the client
         *      to goto the speicifled url by http location header
         *
         * @param   $_url   request url, 'module/page'
         * @param   $_args  request arguments
         * @param   $_ext   url file extension
        */
        public function redirect( $_url, $_args = NULL )
        {
            $_url = $this->makeStyleUrl($_url, $_args);
            header("Location: {$_url}");
            exit();
        }
        
        /**
         * Make a valid http get requst arguments with the specifiled
         *      request link style
         *
         * @param   $_args quote
         * @return  String
         * @access  private
        */
        protected function makeStyleArgs( &$_args )
        {
            //dir style arguments, demo: /nid/tid/pageno
            if ( $this->_style == URI_DIR_STYLE ) {
                return ('/'.implode('/', $_args));
            }
            
            if ( is_string($_args) ) return ('?' . $_args);
            
            /*
             * Consider the args as an key=>value array
             *  make a valid http get request arguments string
             * like 'key=val&key2=val2'
            */
            $_str = ''; $item = NULL;
            foreach ( $_args as $_key => $_val ) {
                $item = $_key . '=' . $_val;
                $_str .= ($_str == '') ? $item : '&' . $item;
            }
            
            return ('?' . $_str);
        }
        
        /**
         * Make a valid request url with the specifiled request arguments
         *
         * @param   $_url
         * @param   $_args  key=>val arguments array
         * @param   $_ext   url file extension
         * @return  String  a valid request url
        */
        public function makeStyleUrl($_url, $_args = NULL)
        {
            $_uri  = $this->_base;
            $_uri .= ($this->_rewrite ? '' : 'index.php/') . $_url;
            if ( $this->_ext != NULL ) $_uri .= $this->_ext;
            
            //check and append the arguments as needed
            if ( $_args != NULL ) $_uri .= $this->makeStyleArgs($_args);
            
            return $_uri;
        }
        
        /**
         * set the file extension of the request url
         *
         * @param   $_ext   should start with '.'
        */
        public function setFileExt( $_ext )
        {
            $this->_ext = $_ext;
        }
        
        /**
         * parse the directory style http get arguments
         *      to the global $_GET array with a specifial template
         *
         * Note: argments parse start from the back
         *
         * @param   $_temp style like nid/tid/pageno
         * @return  Integer - number of successfully parsed arguments  or false for failed
        */
        public function parseArgsGet( $_temp )
        {
            //check and make sure the uri style is URI_DIR_STYLE
            if ( $this->_style != URI_DIR_STYLE ) return 0;
            
            $_plen = count($this->_parts);
            $_keys = explode('/', $_temp);
            $_klen = count($_keys);
            
            /*the module and the page part is need so, $_klen + 2*/
            if ( $_klen + 2 > $_plen  ) return false;
            $_idx  = $_plen - $_klen - 1;
            
            foreach ( $_keys as $_key ) {
                //mapping $_counter's value of _parts with key $_key
                //  in global $_GET array
                $_GET[$_key] = $this->_parts[$_idx];
                $_idx++;
            }
            
            return true;
        }
        
        /**
         * get the specifield part of the request url
         *
         * @param   $_idx
         * @return  String
        */
        public function getPartById( $idx )
        {
            if ( $idx >= 0 && $idx < count($idx->_parts) )
                return $this->_parts[$idx];
            return NULL;
        }
        
        /**
         * method to fetch the controll class file
         *  and return a valid instance of the controll class throught
         *  the current request url pattern
         *
         * @param   $_module    default module
         * @return  Object or NULL when failed
        */
        protected abstract function getController( $_module );
    }
}

//Load the Output class
if ( (SR_INC_COMPONENTS & 0x20) != 0 ) {
    //-----------------------------------------------------------------
    class Output
    {
        /**
         * self added http header for the output
         *
         * @access  private
        */
        private     $_header = array();
        
        /**
         * output content - http data section
         *
         * @access  private
        */
        private     $_final_output = '';
        
        /**
         * use zlib to compress the transfer content
         *      when the bandwidth limit the performance of your system
         *  and you should start this
         *
         * @access  private
        */
        private     $_zlib_oc = false;
        private     $_gzip_oc = -1;
        
        
        public function __construct()
        {
            $this->_zlib_oc = @ini_get('zlib.output_compression');

            //check and auto append the charset header
            //@Note: added at 2016-03-20
            if ( defined('SR_CHARSET') ) {
                $this->_header['Content-Type'] = 'text/html; charset=' . SR_CHARSET;
            }
        }
        
        /**
         * Enable the content transfer compression
         *  It will do nothing if $this->_zlib_oc is enabled
         *
         * @param   $_level (number between 1 - 9)
        */
        public function compress( $_level )
        {
            if ( $this->_zlib_oc == TRUE  ) return;
            
            //check and set the level
            if ( $_level >= 1 && $_level <= 9 ) {
                $this->_gzip_oc = $_level;
            }
        }
        
        /**
         * set the http response header
         *
         * @param   $_header
         * @param   $_replace
        */
        public function setHeader( $_header, $_replace )
        {
            /* If zlib.output_compress is enabled, php will compress
             *  the output data itself and it will cause bad result for broswer
             * if we modified the content-length with a wrong value
            */
            if ( $this->_zlib_oc 
                && strncasecmp($_header, 'content-length') == 0 ) {
                return;
            }
            
            $this->_header[$_header] = $_replace;
            return $this;
        }
        
        /**
         * set the final output string
         *
         * @param   $_output
        */
        public function setOutput()
        {
            $this->_final_output = $_output;
            
            return $this;
        }
        
        /**
         * Append the specifiled string to the final output string
         *
         * @param   $_str
        */
        public function append( $_str )
        {
            $this->_final_output .= $_str;
            
            return $this;
        }
        
        /**
         * Set the http status code and string
         *
         * @param   $_code
         * @param   $_string
        */
        public function setStatusHeader( $_code, $_string = '' )
        {
            static $_status = array(
                200    => 'OK',
                201    => 'Created',
                202    => 'Accepted',
                203    => 'Non-Authoritative Information',
                204    => 'No Content',
                205    => 'Reset Content',
                206    => 'Partial Content',

                300    => 'Multiple Choices',
                301    => 'Moved Permanently',
                302    => 'Found',
                304    => 'Not Modified',
                305    => 'Use Proxy',
                307    => 'Temporary Redirect',

                400    => 'Bad Request',
                401    => 'Unauthorized',
                403    => 'Forbidden',
                404    => 'Not Found',
                405    => 'Method Not Allowed',
                406    => 'Not Acceptable',
                407    => 'Proxy Authentication Required',
                408    => 'Request Timeout',
                409    => 'Conflict',
                410    => 'Gone',
                411    => 'Length Required',
                412    => 'Precondition Failed',
                413    => 'Request Entity Too Large',
                414    => 'Request-URI Too Long',
                415    => 'Unsupported Media Type',
                416    => 'Requested Range Not Satisfiable',
                417    => 'Expectation Failed',

                500    => 'Internal Server Error',
                501    => 'Not Implemented',
                502    => 'Bad Gateway',
                503    => 'Service Unavailable',
                504    => 'Gateway Timeout',
                505    => 'HTTP Version Not Supported'
            );
            
            if ( ! isset($_status[$_code]) ) exit('Error: Invalid http status code');
            if ( $_string == '' ) $_string = &$_status[$_code];
            
            //get the current server protocol
            $_protocol = isset( $_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : FALSE;
            
            //send the status header (for to replace the old one)
            if ( substr(php_sapi_name(), 0, 3) == 'cgi' ) {
                header("Status: {$_code} {$_string}", true);
            } else if ( $_protocol == 'HTTP/1.0' ) {
                header("HTTP/1.0 {$_code} {$_string}", true, $_code);
            } else {
                header("HTTP/1.1 {$_code} {$_string}", true, $_code);
            }
        }
        
        /**
         * Response the request and display the final output string
         *  with the http header also
         *
         * gzip compression will be use to compress the final output
         *  if $this->_gzip_oc is enabled
        */
        public function display( $_output = '' )
        {
            //define the output string
            if ( $_output == '' ) $_output = &$this->_final_output;

            //Try to send the server heaer
            if ( count($this->_header) > 0 ) {
                foreach ( $this->_header as $hKey => $hVal ) {
                    header("{$hKey}: {$hVal}");
                }
            }
            
            //Try to send the server response content
            // if $this->_gzip_oc is enabled then compress the output
            if ( $this->_gzip_oc != -1 && extension_loaded('zlib') ) {
                $_cond = isset($_SERVER['HTTP_ACCEPT_ENCODING'])
                    && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE);
                    
                if ( $_cond ) {
                    $_output = gzencode($_output, $this->_gzip_oc);
                    header('Content-Encoding: gzip');  
                    header('Vary: Accept-Encoding');  
                    header('Content-Length: '.strlen($_output));
                }
            }
            
            echo $_output;
        }
    }
}

//Load the parent Model class
class Model
{
    public function __construct()
    {
        
    }
}

//Load the parent Controller class
if ( (SR_INC_COMPONENTS & 0x80) != 0 ) {
    class Controller
    {
        public       $uri    = NULL;        //request uri
        public       $input  = NULL;        //request input
        public       $output = NULL;        //request output
        public        $_G    = NULL;        //global resource
        
        /**
         * Construct method to create new instance of the controller
         *
         * @param    $uri
         * @param    $input
         * @param    $output
        */
        public function __construct()
        {
            $this->_G = new stdClass();
        }
        
        /**
         * the entrance of the current controller
         * default to invoke the uri->page.logic.php to handler
         *     the request, you may need to rewrite this method to self define
         *
         * @access    public
        */
        public function run()
        {
            //user logic file to handler the request
            $_logicScript = $this->uri->page . '.logic.php';
            if ( file_exists($_logicScript) ) {
                include $_logicScript;
            } else {
                $this->uri->redirect('/error/404');
            }
        }
    }
}

/**
 * framework initialize
 * 1. parse and load the arguments for cli mode
 * 2. check and set the process title
 * @date: 2015-12-25
*/
if ( SR_CLI_MODE ) {
    _cli_initialize();

    if ( isset($_SERVER['PROCESS_TITLE']) ) {
        @cli_set_process_title($_SERVER['PROCESS_TITLE']);
    }
}
?>
