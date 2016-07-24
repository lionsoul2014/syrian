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

/**
 * and this one is for kernel use only
 *
 * @see #_G
*/
function E($key, $val=NULL)
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

/**
 * cli supper global initialize function
*/
function _cli_initialize()
{
    $argv = &$_SERVER['argv'];
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

/**
 * load the specified model with the given path
 *
 * @param   $model_path
 * @param   $cache check the cache first ?
 * @return  Object the object of the loaded model 
*/
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
function helper($helper_path, $args=NULL, $_inc=false, $cache=true)
{
    static $_loadedHelper = array();

    if ( ($sIdx = strpos($helper_path, '#')) !== false ) {
        $package = str_replace('.', '/', substr($helper_path, 0, $sIdx));
        $method  = substr($helper_path, $sIdx + 1);
    } else {
        $package = str_replace('.', '/', $helper_path);
        $method  = NULL;
    }

    $found = true;
    if ( ! isset($_loadedHelper[$package]) || $cache == false ) {
        if ( ($sIdx = strrpos($package, '/')) !== false ) {
            //@Note: see #model
            $path   = str_replace('.', '/', substr($package, 0, $sIdx + 1));
            $helper = substr($package, $sIdx + 1);
        } else {
            $path   = NULL;
            $helper = $package;
        }

        //Look for the class in the SYSPATH/lib folder if $_inc is TRUE
        //Or check the APPPATH/lib 
        $_dir  = (($_inc) ? BASEPATH . '/helper/' : SR_HELPERPATH);
        $_dir .= "{$path}{$helper}";
        $found = false;
        
        foreach ( array("{$_dir}.helper.php", "{$_dir}.php") as $_file ) {
            if ( file_exists($_file) ) {
                if ( ! isset($_loadedHelper[$package]) ) {
                    require $_file;
                }

                $found = true;
                $class = "{$helper}Helper";
                $obj   = new $class(NULL);
                $_loadedHelper[$package] = $obj;
                unset($path, $helper, $_dir, $class);
                break;
            }
        }
    }

    if ( $found == false ) {
        unset($package, $method);
        throw new Exception("helper#Unable to load helper with path {$helper_path}");
    }

    if ( $method == NULL ) {
        return $_loadedHelper[$package];
    }

    $helperObj = $_loadedHelper[$package];
    if ( ! method_exists($helperObj, $method) ) {
        unset($helperObj, $package, $method);
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
    $viewObj = E('view_obj');
    if ( $viewObj == NULL ) {
        import('view.ViewFactory');
        $conf = array(
            'cache_time' => 0,
            'tpl_dir'    => SR_VIEWPATH,
            'cache_dir'  => SR_CACHEPATH.'tpl/'
        );

        $viewObj = ViewFactory::create('html', $conf);
        E('view_obj', $viewObj);
    }

    //check and set the tpl cache timer
    if ( $timer > 0 )    $viewObj->setCacheTime($timer);
    if ( $vars != null ) $viewObj->load($vars);

    return $viewObj->getContent($tpl, $sanitize);
}

/**
 * redirect to the specified request
 *
 * @param   $uri
 * @param   $args
 * @param   $exit exit the current request
*/
function redirect($uri, $args=NULL, $exit=true)
{
    if ( is_array($args) ) {
        $arr = array();
        foreach ( $args as $k => $v ) {
            $arr[] = "{$k}={$v}";
        }
        $args = '?'.implode('&', $arr);
    } else {
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
 * @return  Mixed (Object or NULL)
 * {
 *  uri    : "",        //the original uri string
 *  path   : "",        //the original path
 *  parts  : array(),   //splited parts
 *  package: "",        //package part of the controller
 *  module : "",        //module name
 *  page   : "",        //page name
 * }
*/
function parse_uri($uri, $separator='/', $default=NULL)
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

    if ( strlen($path) < 1 ) return NULL;
    if ( $path[0] == '/'   ) $path = substr($path, 1);

    //-----------------------------------------------

    $uriBean = new StdClass();
    $uriBean->uri     = $uri;
    $uriBean->path    = $path;
    $uriBean->parts   = NULL;
    $uriBean->package = NULL;
    $uriBean->module  = NULL;
    $uriBean->page    = NULL;

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

    if ( $uriBean->module == NULL ) {
        if ( is_string($default) ) {
            $uriBean->module = $default;
        } else if ( is_array($default) 
            && isset($default[0]) ) {
            $uriBean->module = $default[0];
        }
    }

    if ( $uriBean->page == NULL ) {
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
    $uri, $input, $output, $res_preload_callback=NULL, &$ctrl=NULL)
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
    if ( $uri->package != NULL ) $_ctrl_file .= "{$uri->package}/";
    $_ctrl_file .= "{$uri->module}/main.php";

    if ( ! file_exists($_ctrl_file) ) {
        throw new Exception("Unable to locate the controller with request uri {$uri->uri}");
    }

    /*
     * check and invoke the request dynamic resource pre load callback
    */
    if ( $res_preload_callback != NULL ) {
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
    if ( $executor == NULL ) {
        import('service.LocalExecutor');
        $executor = new LocalExecutor(NULL);
    }

    return $executor->execute(
        $serv_path, 
        $args, 
        $asyn, 
        $priority
    );
}

?>
