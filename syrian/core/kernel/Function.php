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
 *
 * @usage: 
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
 * @param   $args
 * @param   $_inc @see #import
 * @param   $cache cache the instance ?
 * @return  Object
 *
 * Usage: 
 * helper('ServiceExecutor#StreamAccess', array('a', 'b'));
*/
if ( ! function_exists('helper') ) {
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
 * search the specified html template and return the executed dynamic content
 *
 * @param   $tpl_file
 * @param   $variables
 * @param   $sanitize
 * @param   $timer  view compile cache time in seconds
 * @return  string
*/
if ( ! function_exists('html_view') ) {
    function html_view($tpl, $vars, $sanitize=false, $timer=0)
    {
        $viewObj = _G('__view_fnt__');
        if ( $viewObj == NULL ) {
            import('view.ViewFactory');
            $conf = array(
                'cache_time' => 0,
                'tpl_dir'    => SR_VIEWPATH,
                'cache_dir'  => SR_CACHEPATH.'tpl/'
            );

            $viewObj = ViewFactory::create('html', $conf);
            _G('__view_fnt__', $viewObj);
        }

        //check and set the tpl cache timer
        if ( $timer > 0 ) {
            $viewObj->setCacheTime($timer);
        }

        return $viewObj->load($vars)->getContent($tpl, $sanitize);
    }
}
 
?>
