<?php if ( ! defined('BASEPATH') ) exit('No Direct Access Allowed!');
/**
 * Application common functions
 *
 * @author    chenxin <chenxin619315@gmail.com>
 * @link    http://www.lionsoul.org/syrian
 */

 //-----------------------------------------------------------------
 
/**
 * global run time resource
*/
if ( ! function_exists('_G') ) {
    function _G($key, $val=NULL)
    {
        static $_GRE = array();

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

                    $sIdx   = $eIdx + 1;
                    $eIdx   = strpos($query_string, '&', $sIdx);
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
?>
