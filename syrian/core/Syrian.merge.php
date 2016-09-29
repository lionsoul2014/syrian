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
define('SR_VERSION', '2.0');

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
 * global run time resource store or fetch
 *
 * @param   $key
 * @param   $val
 * @return  Mixed
*/
function _G($key, $val=null)
{
    static $_GRE = array();
    
    if ( is_array($key) ) {
        foreach ( $key as $k => $v ) {
            $_GRE[$k] = $v;
        }

        return true;
    }

    if ( $val === null ) {
        return isset($_GRE["{$key}"]) ? $_GRE["{$key}"] : null;
    }

    $_GRE["{$key}"] = &$val;

    return true;
}

/**
 * and this one is for kernel use only
 *
 * @see #_G
*/
function E($key, $val=null)
{
    static $_GRE = array();
    
    if ( is_array($key) ) {
        foreach ( $key as $k => $v ) {
            $_GRE[$k] = $v;
        }

        return true;
    }

    if ( $val === null ) {
        return isset($_GRE["{$key}"]) ? $_GRE["{$key}"] : null;
    }

    $_GRE["{$key}"] = &$val;

    return true;
}

/**
 * cli supper global initialize function
*/
function _cli_initialize()
{
    $argv = &$_SERVER['argv'];
    //1. parse and define the SCRIPT_FILENAME
    $script_name = array_shift($argv);
    $_SERVER['SCRIPT_FILENAME'] = $script_name;
    $_SERVER['REQUEST_URI']     = null;
    $_SERVER['QUERY_STRING']    = null;

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
                $eIdx = strpos($query_string, '=', $sIdx);
                if ( $eIdx === false ) break;
                $args_name = substr($query_string, $sIdx, $eIdx - $sIdx);

                /**
                 * both '&' and ':' could be as the arguments
                 * separate mark At 2016-01-22
                */
                $sIdx = $eIdx + 1;
                $eIdx = strpos($query_string, ':', $sIdx);
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
                $_GET[$args_name]  = $args_val;
                $_POST[$args_name] = $args_val;
            }
        }
    }

    //additional _SERVER arguments parse
    $args_num = count($argv);
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

/**
 * import the specifield class
 *
 * @param   $cls_path class path like db.dbFactory
 * @param   $_inc If $_inc is True check the syrian/lib or check under SR_LIBPATH
*/
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

/**
 * import the specifield configuration
 * and return the return of the included file as the final result
 *
 * @param   $config_path
 * @param   $_inc @see #import
 * @param   $cache cache the file incldue ?
 *
 * @usage: 
 * $conf = config('db.hosts');
 * $mysql = config('db.hosts#mysql');
*/
function config($config_path, $_inc=false, $cache=true)
{
    static $_loadedConf = array();

    if ( ($sIdx = strpos($config_path, '#')) !== false ) {
        $path = str_replace('.', '/', substr($config_path, 0, $sIdx));
        $keys = explode('.', substr($config_path, $sIdx + 1));
    } else {
        $path = str_replace('.', '/', $config_path);
        $keys = null;
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
                unset($_dir);
                break;
            }
        }
        
        unset($_dir);
    }

    if ( $found == false ) {
        unset($path, $keys);
        throw new Exception("config#Unable to load configure with path {$config_path}");
    }

    if ( $keys == null ) {
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

/**
 * load the specified model with the given path
 *
 * @param   $model_path
 * @param   $cache check the cache first ?
 * @return  Object the object of the loaded model 
*/
function model($model_path, $cache=true)
{
    static $_clsLoaded   = array();
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
        $path  = null;
        $model = $model_path;
    }
    
    //model base directory
    $_dir = SR_MODELPATH . "{$path}{$model}";
    foreach ( array("{$_dir}.model.php", "{$_dir}.php") as $_file ) {
        if ( file_exists( $_file ) ) {
            if ( ! isset($_clsLoaded[$model_path]) ) {
                $_clsLoaded[$model_path] = true;
                include $_file;
            }

            $class = "{$model}Model";
            if ( class_exists($class) )  {
                $obj = new $class();
            } else {
                $obj = new $model();
            }

            //mark loaded for the current model
            if ( $cache == true ) {
                $_loadedModel[$model_path] = $obj;
            }

            unset($path, $model, $_dir, $class);
            return $obj;
        }
    }
    
    throw new Exception("model#Unable to load model with path {$model}");
}

/**
 * load and create then return the specified helper
 *
 * @param   $helper_path
 * @param   $args
 * @param   $_inc @see #import
 * @param   $cache cache the instance ?
 * @return  Object
 *
 * Usage: 
 * helper('ServiceExecutor#StreamAccess', array('a', 'b'));
*/
function helper($helper_path, $args=null, $_inc=false, $cache=true)
{
    static $_clsLoaded    = array();
    static $_loadedHelper = array();

    if ( ($sIdx = strpos($helper_path, '#')) !== false ) {
        $package = str_replace('.', '/', substr($helper_path, 0, $sIdx));
        $method  = substr($helper_path, $sIdx + 1);
    } else {
        $package = str_replace('.', '/', $helper_path);
        $method  = null;
    }

    if ( $cache == true && isset($_loadedHelper[$package]) ) {
        $helperObj = $_loadedHelper[$package];
    } else {
        if ( ($sIdx = strrpos($package, '/')) !== false ) {
            //@Note: see #model
            $path   = str_replace('.', '/', substr($package, 0, $sIdx + 1));
            $helper = substr($package, $sIdx + 1);
        } else {
            $path   = null;
            $helper = $package;
        }

        //Look for the class in the SYSPATH/lib folder if $_inc is TRUE
        //Or check the APPPATH/lib 
        $_dir  = (($_inc) ? BASEPATH . '/helper/' : SR_HELPERPATH);
        $_dir .= "{$path}{$helper}";
        $found = false;
        
        foreach ( array("{$_dir}.helper.php", "{$_dir}.php") as $_file ) {
            if ( file_exists($_file) ) {
                if ( ! isset($_clsLoaded[$package]) ) {
                    $_clsLoaded[$package] = true;
                    require $_file;
                }

                $found = true;
                $class = "{$helper}Helper";
                $helperObj = new $class(null);
                if ( $cache == true ) {
                    $_loadedHelper[$package] = $helperObj;
                }

                unset($path, $helper, $_dir, $class);
                break;
            }
        }

        if ( $found == false ) {
            unset($package, $method);
            throw new Exception("helper#Unable to load helper with path {$helper_path}");
        }
    }

    unset($package);
    if ( $method == null ) {
        return $helperObj;
    }

    if ( ! method_exists($helperObj, $method) ) {
        unset($helperObj);
        throw new Exception("helper#Undefined method {$method} for helper {$helper_path}");
    }

    return $helperObj->{$method}(is_array($args) ? $args : array($args));
}

/**
 * abort the current request by the specified http error code
 *
 * @param   $http_code
*/
function abort($http_code)
{
    http_response_code($http_code);
    exit();
}

/**
 * return the default view instance
 *
 * @return  Object of AView
 * @see     syrian.lib.view.ViewFactory#AView
*/
function build_view($type='html')
{
    $e_name = "{$type}_view_obj";
    if ( ($viewObj = E($e_name)) == null ) {
        import('view.ViewFactory');
        $conf = $type == 'html' ? array(
            'cache_time' => 0,
            'tpl_dir'    => SR_VIEWPATH,
            'cache_dir'  => SR_CACHEPATH.'tpl/'
        ) : null;

        $viewObj = ViewFactory::create($type, $conf);
        E($e_name, $viewObj);
    }

    return $viewObj;
}


/**
 * search the specified html template and return the executed dynamic content
 *
 * @param   $tpl_file
 * @param   $variables
 * @param   $sanitize
 * @param   $timer  view compile cache time in seconds
 * @return  string
*/
function view($tpl, $vars=null, $sanitize=false, $timer=0)
{
    $viewObj = build_view();
    $viewObj->setCacheTime($timer);
    if ( $vars != null ) $viewObj->load($vars);

    return $viewObj->getContent($tpl, $sanitize);
}

/**
 * assign a variable into the current may comming global view
 *
 * @param   $key could be an Array
 * @param   $val
 * @return  Object return the global view object
*/
function view_assign($key, $val)
{
    $viewObj = build_view();
    if ( is_array($key) ) {
        return $viewObj->load($key);
    }

    return $viewObj->assign($key, $val);
}

/**
 * report the error code and the error message to the default view instance
 *
 * @param   $err_code
 * @param   $err_msg
 * @return  Object the default view instance
*/

defined('VIEW_OK')    or define('VIEW_OK',    0);
defined('VIEW_INFO')  or define('VIEW_INFO',  1);
defined('VIEW_ERROR') or define('VIEW_ERROR', 2);

function view_report($err_code, $err_msg=null)
{
    static $symbol  = null;
    //static $errType = array('success', 'info', 'danger');

    if ( $symbol == null ) {
        $symbol  = array();
        $viewObj = build_view();
        $viewObj->assoc('errors', $symbol);
    }

    $symbol[] = array(
        'code' => $err_code,
        'desc' => $err_msg
    );
}

/**
 * quick paging package define for paging blade component
 *
 * @param   $total
 * @param   $pagesize
 * @param   $pageno
 * @param   $baseUrl
 * @param   $name
 * @param   $style
 * @param   $left
 * @param   $offset
*/

defined('PAGE_STD_STYLE')   or  define('PAGE_STD_STYLE',  0);
defined('PAGE_SHOP_STYLE')  or  define('PAGE_SHOP_STYLE', 1);

function view_page(
    $total, $pagesize, $pageno, 
    $qstr=null, $name='pageno', $style=1, $left=2, $offset=2 )
{
    static $symbol = null;

    if ( $symbol == null ) {
        $viewObj = build_view();
        $viewObj->assoc('page', $symbol);
    }

    if ( $qstr == null 
        && isset($_SERVER['QUERY_STRING']) ) {
        $qstr = $_SERVER['QUERY_STRING'];
    }

    if ( $qstr == null || strlen($qstr) <= 1 ) {
        $url = "?{$name}=";
    } else {
        $pattern = "/(&?){$name}=[^&]*&?/";
        $url = preg_replace($pattern, '$1', $qstr);
        if ( ($len = strlen($url)) < 1 ) {
            $url = "?{$name}=";
        } else if ( $url[$len-1] =='&' ) {
            $url = "?{$url}{$name}=";
        } else {
            $url = "?{$url}&{$name}=";
        }
    }

    //correction the page number
    $pages = ceil($total / $pagesize);
    if ( $pageno < 1      ) $pageno = 1;
    if ( $pageno > $pages ) $pageno = $pages;

    $symbol = array(
        'total'  => $total,
        'pages'  => $pages,
        'pageno' => $pageno,
        'style'  => $style,
        'link'   => $url,
        'left'   => $left,
        'offset' => $offset
    );
}

/**
 * redirect to the specified request
 *
 * @param   $uri
 * @param   $args
 * @param   $exit exit the current request
*/
function redirect($uri, $args=null, $exit=true)
{
    if ( is_array($args) ) {
        $arr = array();
        foreach ( $args as $k => $v ) {
            $arr[] = "{$k}={$v}";
        }
        $args = '?'.implode('&', $arr);
    } else if ( ! is_null($args) ) {
        $args = "?{$args}";
    }

    if ( $uri[0] != '/' 
        && strpos($uri, '://') === false ) {
        $uri = "/{$uri}";
    }

    header("Location: {$uri}{$args}");
    if ( $exit ) exit();
}

/**
 * parse the specified request url and return the parsed info
 *
 * @param   $uri (the relative request uri only with the path part)
 * @param   $separator and default to '/' it could be '.' or '-'
 * @return  Mixed (Object or null)
 * {
 *  uri    : "",        //the original uri string
 *  path   : "",        //the original path
 *  parts  : array(),   //splited parts
 *  package: "",        //package part of the controller
 *  module : "",        //module name
 *  page   : "",        //page name
 * }
*/
function parse_uri($uri, $separator='/', $default=null)
{
    /*
     * move the arguments to get the path
     * and make sure it start with the '/'
    */
    if ( ($argsIdx = strpos($uri, '?')) !== false ) {
        $path = substr($uri, 0, $argsIdx);
    } else {
        $path = $uri;
    }

    if ( strlen($path) < 1 ) return null;
    if ( $path[0] == '/'   ) $path = substr($path, 1);

    //-----------------------------------------------

    $uriBean = new StdClass();
    $uriBean->uri     = $uri;
    $uriBean->path    = $path;
    $uriBean->parts   = null;
    $uriBean->package = null;
    $uriBean->module  = null;
    $uriBean->page    = null;

    if ( strlen($path) >= 1 ) {
        $parts  = explode($separator, $path);
        $length = count($parts);
        $uriBean->parts = $parts;
        switch ( $length ) {
        case 1:
            $uriBean->module  = $parts[0];
            break;
        case 2:
            $uriBean->module  = $parts[0];
            $uriBean->page    = $parts[1];
            break;
        case 3:
            $uriBean->page    = $parts[$length-1];
            $uriBean->module  = $parts[$length-2];
            $uriBean->package = $parts[$length-3];
            break;
        default:
            $uriBean->page    = array_pop($parts);
            $uriBean->module  = array_pop($parts);
            $uriBean->package = implode('/', $parts);
        }

        unset($parts, $length);
    }

    if ( $uriBean->module == null ) {
        if ( is_string($default) ) {
            $uriBean->module = $default;
        } else if ( is_array($default) 
            && isset($default[0]) ) {
            $uriBean->module = $default[0];
        }
    }

    if ( $uriBean->page == null ) {
        if ( is_array($default) 
            && isset($default[1]) ) {
            $uriBean->page = $default[1];
        }
    }

    unset($path);

    return $uriBean;
}

/**
 * search and invoke the specified controller through the 
 * specified request uri by passing the request input and output object
 * it will finally return the executed result
 *
 * @param   $uri (a uri parsed object return by parse_uri or a standart http request uri)
 * @param   $input
 * @param   $output
 * @param   $res_preload_callback resource preload callback
 * @return  Object uri bean with only attributes
 * @see     #parse_uri
*/
function controller(
    $uri, $input, $output, $res_preload_callback=null, &$ctrl=null)
{
    /*
     * check and parse the uri if it is a request uri string
     * make this function directly runnable from a standart request uri
    */
    if ( is_string($uri) ) $uri = parse_uri($uri);

    /*
     * get and check the existence of the controller main file
    */
    $_ctrl_file = SR_CTRLPATH;
    if ( $uri->package != null ) $_ctrl_file .= "{$uri->package}/";
    $_ctrl_file .= "{$uri->module}/main.php";

    if ( ! file_exists($_ctrl_file) ) {
        throw new Exception("Unable to locate the controller with request uri {$uri->uri}");
    }

    /*
     * check and invoke the request dynamic resource pre load callback
    */
    if ( $res_preload_callback != null ) {
        $res_preload_callback($uri);
    }

    require $_ctrl_file;
    
    /*
     * search and check the existence of the controller class
     * then create the controller instance
     * and invoke its run method to process the current request
    */
    $_class = ucfirst($uri->module) . 'Controller';
    if ( ! class_exists($_class) ) {
        throw new Exception("Undefined class {$_class} with request uri {$uri->uri}");
    }

    $ctrl = new $_class();
    $ret  = $ctrl->run($input, $output, $uri);

    //let gc do its work
    unset($_ctrl_file, $_class);

    return $ret;
}

/**
 * search and invoke the specified service through the specified service path
 * then return the executed result
 * @Note: this is just a quick lancher for Executor->execute() not mean to take its place
 *
 * @param   $serv_path
 * @param   $args
 * @param   $executor (default to the local executor)
 * @param   $asyn (default to true)
 * @param   $priority
*/
function service($serv_path, $args, $executor=null, $asyn=true, $priority=null)
{
    if ( $executor == null ) {
        import('service.LocalExecutor');
        $executor = new LocalExecutor(null);
    }

    return $executor->execute(
        $serv_path, 
        $args, 
        $asyn, 
        $priority
    );
}

/**
 * quick way to fetch/store the value from the default session
 *
 * @param   $key
 * @param   $val
 * @return  Mixed
*/
function session($key, $val=null)
{
    if ( E('session_start') == false ) {
        session_start();
        E('session_start', true);
    }

    if ( $val === null ) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    $_SESSION[$key] = $val;
    return true;
}

/**
 * quick way to do the session clean up:
 * 1, clear all the data in $_SESSION
 * 2, unlink the session file if it is possible
 * 3, delete the session cookies
*/
function session_close()
{
    session_start();
    session_unset();    
    setcookie(session_name(), '', time() - 42000, '/');

    /*
     * check and try to delete the session file
     * if the session_save_handler is bean set to 'N;/path' and we will just ignore it
    */
    $save_path = session_save_path();
    if ( strpos($save_path, ';') === false ) {
        $sessFile = "{$save_path}/sess_" . session_id();
        if ( file_exists($sessFile) ) {
            @unlink($sessFile);
        }
    }

    E('session_start', false);
    session_destroy();
}


//--------------------------signature about functions--------------------------

/**
 * count the hash value of the specified string with bkdr hash algorithm
 *
 * @param   $str
 * @return  Integer
*/
function bkdr_hash($str)
{
    $hval = 0;
    $len  = strlen($str);

    for ( $i = 0; $i < $len; $i++ ) {
        $hval = (int) ($hval * 1331 + (ord($str[$i]) % 127));
    }
    
    return ($hval & 0x7FFFFFFF);
}

/**
 * application layer signature quick generator
 *
 * @param   $factors all the arguments that will join to the encryption
 * @param   $timer the unix time stamp that will store in the signature
 * @return  String
*/
function build_signature($factors, $timer=null)
{
    $seeds = '=~!@#$%^&*()_+{}|\;:\',./<>"%%`~';
    $s_len = strlen($seeds);

    $encrypt = array('^');
    foreach ( $factors as $val ) {
        $encrypt[] = $val;
        $sIdx = bkdr_hash($val) % $s_len;
        for ( $i = 0; $i < 3; $i++ ) {
            $encrypt[] = $seeds[$sIdx++];
            if ( $sIdx >= $s_len ) {
                $sIdx = 0;
            }
        }
    }

    if ( $timer != null ) {
        $encrypt[] = "@{$timer}";
    }

    $encrypt[] = '$';

    $sign_val = sha1(implode('|', $encrypt));
    if ( $timer == null ) {
        return $sign_val;
    }

    return $sign_val . sprintf('%08x', $timer);
}

/**
 * application layer signature quick validator
 * with expired time in seconds specified and it could check the 
 *  whether the signature is expired or not
 *
 * @param   $factors
 * @param   $signature
 * @param   $expired self-define signature expired time in seconds
 * @return  bool
 * @see     #build_signature
*/
function valid_signature($factors, $signature, $expired=-1)
{
    $sign_len = strlen($signature);
    if ( $sign_len != 40 && $sign_len != 48 ) {
        return false;
    }

    $timer = $sign_len == 48 ? hexdec(substr($signature, 40)) : null;
    $sign_val = build_signature($factors, $timer);
    if ( strncmp($sign_val, $signature, 40) != 0 ) {
        return false;
    }

    if ( $expired > 0 && $timer != null ) {
        if ( time() - $timer > $expired ) {
            return false;
        }
    }

    return true;
}

/**
 * decode the json to array
 *
 * @param   $str
 * @return  Mixed (Object for null for failed)
*/
function json_decode_array($str)
{
    return json_decode($str, true);
}

/**
 * set the value of the specifield query string argument
 *
 * @param   $key
 * @param   $val
 * @param   $src
 * @return  string
*/
function set_query_args($key, $val=null, $src=null)
{
    if ( $src == null ) {
        if ( ! isset($_SERVER['QUERY_STRING']) ) return null;
        $src = $_SERVER['QUERY_STRING'];
    }

    if ( ! is_array($key) ) {
        $keys = array($key => $val);
    } else {
        $keys = $key;
    }

    foreach ( $keys as $k => $v ) {
        $len = strlen($src);
        if ( preg_match("/([\?#&]?){$k}=[^&#]*([&#]?)/", $src, $m) != 1 ) {
            $src = $len > 0 ? "{$src}&{$k}={$v}" : "{$src}{$k}={$v}";
        } else {
            $src = str_replace($m[0], "{$m[1]}{$k}={$v}{$m[2]}", $src);
        }
    }

    return $src;
}

/**
 * remove the specifield key from the specifield query string
 *
 * @param   $key
 * @param   $src
 * @return  string
*/
function clear_query_args($key, $src=null)
{
    if ( $src == null ) {
        if ( ! isset($_SERVER['QUERY_STRING']) ) return null;
        $src = $_SERVER['QUERY_STRING'];
    }

    if ( ! is_array($key) ) {
        $keys = array($key);
    } else {
        $keys = $key;
    }

    foreach ( $keys as $k ) {
        $src = preg_replace("/([\?#&]?){$k}=[^&#]*([&#]?)/", "$1$2", $src);
    }

    $src = preg_replace('/&{2,}/', '&', $src);
    $src = preg_replace('/^&|&$/', '',  $src);

    return $src;
}

/**
 * get the value of the specified argument from the query string
 *
 * @param   $key
 * @param   $src
 * @return  string | empty string
*/
function get_query_args($key, $src=null)
{
    if ( $src == null ) {
        if ( ! isset($_SERVER['QUERY_STRING']) ) return null;
        $src = $_SERVER['QUERY_STRING'];
    }

    $pattern = "/[#&\?]?{$key}=([^&#]*)/";
    return preg_match($pattern, $src, $m) == 1 ? $m[1] : null;
}

class Helper
{
    /**
     * Construct method to create new instance of the Helper
     *
     * @param   $conf
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
            $method = array_shift($_argv);
            return $this->{$method}($_argv);
        }

        exit("Error: Helper unable to load {$_argv[0]}\n");
    }
}

//Load the input class manage the input of the controller/
if ( (SR_INC_COMPONENTS & 0x08) != 0 ) {
    //--------------------------------------------------------------
    //normal data type
    defined('OP_NULL')      or define('OP_NULL',        1 <<  0);
    defined('OP_LATIN')     or define('OP_LATIN',       1 <<  1);
    defined('OP_URL')       or define('OP_URL',         1 <<  2);
    defined('OP_EMAIL')     or define('OP_EMAIL',       1 <<  3);
    defined('OP_QQ')        or define('OP_QQ',          1 <<  4);
    defined('OP_DATE')      or define('OP_DATE',        1 <<  5);
    defined('OP_NUMERIC')   or define('OP_NUMERIC',     1 <<  6);
    defined('OP_STRING')    or define('OP_STRING',      1 <<  7);
    defined('OP_ZIP')       or define('OP_ZIP',         1 <<  8);
    defined('OP_MOBILE')    or define('OP_MOBILE',      1 <<  9);
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
        public function __construct()
        {
           //Do nothing here
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
                import('Filter');
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
            import('Filter');
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
                import('Filter');
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
            import('Filter');
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
                import('Filter');
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
            import('Filter');
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
                import('Filter');
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
                import('Filter');
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
            import('Filter');
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
                import('Filter');
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
                import('Filter');
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

//Load the Output class
if ( (SR_INC_COMPONENTS & 0x20) != 0 ) {
    class Output
    {
        /**
         * self added http header for the output
         *
         * @access  private
        */
        private $_header = array();
        
        /**
         * output content - http data section
         *
         * @access  private
        */
        private $_final_output = '';
        
        /**
         * use zlib to compress the transfer content
         *      when the bandwidth limit the performance of your system
         *  and you should start this
         *
         * @access  private
        */
        private $_zlib_oc = false;
        private $_gzip_oc = -1;
        
        
        public function __construct()
        {
            $this->_zlib_oc = @ini_get('zlib.output_compression');

            if ( SR_CLI_MODE != true ) {
                //check and auto append the charset header
                //@Note: added at 2016/03/20
                if ( defined('SR_CHARSET') ) {
                    $this->setHeader('Content-Type', 'text/html; charset= ' . SR_CHARSET);
                }

                $this->setHeader('X-Powered-By', defined(SR_POWERBY) ? SR_POWERBY : 'Syrian/2.0');
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

            //Try to send the server header
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

//Load the parent Controller class
if ( (SR_INC_COMPONENTS & 0x80) != 0 ) {
    class Controller
    {
        /**
         * method prefix and it default to '_'
        */
        protected $method_prefix = 'action';

        /**
         * Construct method to create and initialize the controller
        */
        public function __construct()
        {
            _G(array(
                SR_FLUSH_MODE  => false,
                SR_IGNORE_MODE => false
            ));

            $this->conf = config('app');
        }

        /**
         * basic initialize method, if the class extends this
         *  need to rewrite the run method(self-define the router) invoke this to
         * do the initialize work
         *
         * @param   $input
         * @param   $output
         * @param   $uri
        */
        protected function __init($input, $output, $uri)
        {
            //@Added at 2015-05-29
            //define the flush mode global sign
            //@Assoc the algorithm assocatied with the cache flush
            // define in the helper/CacheFlusher#Refresh
            $flushMode = $input->getInt('__flush_mode__', 0);
            if ( $flushMode == 1 
                && strcmp($this->conf->flush_key, $input->get('__flush_key__')) == 0 ) {
                _G(SR_FLUSH_MODE, true);
            }

            //@Added at 2015-07-21
            // for cache flush need to ignore the balance redirecting...
            $ignoreMode = $input->getInt('__ignore_mode__', 0);
            if ( $ignoreMode == 1 ) {
                _G(SR_IGNORE_MODE, true);
            }
        }
        
        /**
         * the entrance of the current controller
         * default to invoke the uri->page.logic.php to handler
         *  the request, you may need to rewrite this method to self define
         *
         * @param   $input
         * @param   $output
         * @param   $uri (standart parse_uri result)
         * @access  public
        */
        public function run($input, $output, $uri)
        {
            $this->__init($input, $output, $uri);

            $ret = NULL;

            /*
             * check and invoke the before method
             * basically you could do some initialize work here
            */
            if ( method_exists($this, '__before') ) {
                $this->__before($input, $output, $uri);
            }

            /*
             * check and invoke the main function to handler the current request
            */
            $method = "{$this->method_prefix}{$uri->page}";
            if ( method_exists($this, $method) ) {
                $ret = $this->{$method}($input, $output, $uri);
            } else {
                throw new Exception("Undefined handler \"{$method}\" for " . __class__);
            }

            /*
             * check and invoke the after method here
             * basically you could do some destroy work here
            */
            if ( method_exists($this, '__after') ) {
                $this->__after($input, $output, $uri);
            }

            return $ret;
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
