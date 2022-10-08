<?php if ( ! defined('BASEPATH') ) exit('No Direct Access Allowed!');
/**
 * Application common functions
 *
 * @author  chenxin <chenxin619315@gmail.com>
 * @link    http://www.lionsoul.org/syrian
 */

 //-----------------------------------------------------------------
 
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
        # take ? or # as the query arguments delimiter
        if ( ($queryPos = strpos($request_uri, '?')) !== false 
            || ($queryPos = strpos($request_uri, '#')) !== false ) {
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
 * @return  RouterShardingModel
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
 * @return  Helper
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
 * check and execute the specified script
 *
 * @param   $tpl
 * @param   $param
 * @return  Mixed
*/
function script($file, $argv=null)
{
    $scrip_file = SR_SCRIPTPATH.$file;
    if ( ! file_exists($scrip_file) ) {
        return null;
    }

    return include($scrip_file);
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

function view_report_register($err_code, $err_msg=null)
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

function view_paging_register(
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
 * data scroll page data register
 *
 * @param   $total
 * @param   $n_cursor
 * @param   $p_cursor
 * @param   $prevsign
 * @param   $n_cursor
 * @param   $nextsign
 * @param   $qstr
*/
function view_scroll_register(
    $total, $c_cursor, 
    $p_cursor, $prevsign, 
    $n_cursor, $nextsign, $qstr=null, $name='cursor')
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

    $symbol = array(
        'total'     => $total,
        'c_cursor'  => $c_cursor,
        'p_cursor'  => $p_cursor,
        'prevsign'  => $prevsign,
        'n_cursor'  => $n_cursor,
        'nextsign'  => $nextsign,
        'link'      => $url
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
    } else if ( SR_CLI_MODE && ($argsIdx = strpos($uri, '#')) !== false ) {
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
        throw new Exception("Unable to locate the controller with request uri {$uri->uri}", 404);
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
        throw new Exception("Undefined class {$_class} with request uri {$uri->uri}", 404);
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
 * @return  Service
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
 * @param   $hash  the hash function to use
 * @return  String
*/
function build_signature(array $factors, $timer=null, $hash_algo=null)
{
    $seeds = '=~!@#$%^&*()_+{}|\;:\',./<>"%%`~?~';
    $s_len = 33;
    $random = "0000";
    $hval = 0;
    $slen = 0;

    $encrypt = array('^');
    foreach ( $factors as $val ) {
        $encrypt[] = $val;
        $slen = strlen($val);
        if ($slen == 0) {
            continue;
        }

        // $hval = 0;
        // for ( $i = 0; $i < $slen; $i++ ) {
        //     $hval = $hval * 131 + ord($val[$i]);
        // }
        $v = "{$val}";
        $hval = ord($v[0]);
        $hval = $hval * 131  + ord($v[intval($slen/2)]);
        $hval = $hval * 1331 + ord($v[$slen-1]);
        $hval = ($hval & 0x7FFFFFFF);

        $sIdx = $hval % $s_len;
        for ( $i = 0; $i < 3; $i++ ) {
            $random[$i] = $seeds[$sIdx++];
            if ( $sIdx >= $s_len ) {
                $sIdx = 0;
            }
        }
        $random[3] = '|';
        $encrypt[] = $random;
    }

    if ( $timer != null ) {
        $encrypt[] = "@{$timer}";
    }

    $encrypt[] = '$';
    $sign_val = hash($hash_algo ?? 'sha1', implode('', $encrypt));
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
 * @param   $hash the hash function to use
 * @return  bool
 * @see     #build_signature
*/
function valid_signature($factors, $signature, $expired=-1, $hash_alg=null)
{
    $sign_len = strlen($signature);
    if ( $sign_len != 40 && $sign_len != 48 ) {
        return false;
    }

    $timer = $sign_len == 48 ? hexdec(substr($signature, 40)) : null;
    $sign_val = build_signature($factors, $timer, $hash_alg);
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

/**
 * get the raw data from the http post request
 *
 * @param   String
*/
function get_post_raw_data()
{
    return file_get_contents("php://input");
}

/**
 * get the raw data from the http post request and convert it to json format
 *
 * @param   String
*/
function get_post_raw_data_json($assoc=false)
{
    return json_decode(file_get_contents("php://input"), $assoc);
}
