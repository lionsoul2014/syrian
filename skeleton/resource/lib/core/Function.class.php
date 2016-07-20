<?php
/**
 * Application layer common functions
 *
 * @author  chenxin<chenxin619315@gmail.com>
 * @link    http://www.lionsoul.org/syrian
 * @see     framewor/core/kerner/Function.php
 */

//-----------------------------------------------------------------

//standart error no define
defined('STATUS_OK')            or define('STATUS_OK',           0);    //everything is fine
defined('STATUS_INVALID_ARGS')  or define('STATUS_INVALID_ARGS', 1);    //invalid arguments
defined('STATUS_NO_SESSION')    or define('STATUS_NO_SESSION',   2);    //no session
defined('STATUS_EMPTY_SETS')    or define('STATUS_EMPTY_SETS',   3);    //query empty sets
defined('STATUS_FAILED')        or define('STATUS_FAILED',       4);    //operation failed
defined('STATUS_DUPLICATE')     or define('STATUS_DUPLICATE',    5);    //operation duplicate
defined('STATUS_ACCESS_DENY')   or define('STATUS_ACCESS_DENY',  6);    //privileges deny

/**
 * quick way to response the data througth json view
 * and it will return the whole json encoded data string
 *
 * @param   $errno
 * @param   $data
 * @param   $ext    extension data
 * @return  string (json encoded)
*/
function json_view($errno, $data, $ext=NULL)
{
    //set the output content type
    E('output')->setHeader('Content-Type', 'application/json');

    $json = array(
        'errno'  => $errno,
        'data'   => $data
    );

    if ( $ext != NULL ) {
        $json['ext'] = $ext;
    }

    return json_encode($json);
}

/**
 * quick way to response the data througth json view
 * and it just return the encoded data string part not the whole encoded string
 * @Note: and the data could be a json encoded string
 *
 * @param   $errno
 * @param   $data
 * @param   $ext    extension data
 * @return  string (data json encoded string)
*/
function json_define_view($errno, $data, $ext=NULL)
{
    //set the output content type
    E('output')->setHeader('Content-Type', 'application/json');

    if ( is_array($data) ) {
        $data = json_encode($data);
    }

    if ( $ext == NULL ) $ext = 'false';
    else if ( is_array($ext) ) $ext = json_encode($ext);

    $CC = <<<EOF
    {
        "errno": $errno,
        "data": $data,
        "ext": $ext
    }
EOF;

    return $data;
}

?>
