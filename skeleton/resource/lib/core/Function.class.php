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
 * and it return the whole encoded data string 
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

    return $CC;
}

/**
 * quick interface to build or fetch the cache
 *
 * @param   $key cache service logic name defined in config/cache.conf.php
 * @return  Object specified cache object
 * @see     app/config/cache.config.php
*/
function build_cache($key='NFile')
{
    static $_loaded = array();

    if ( isset($_loaded[$key]) ) {
        return $_loaded[$key];
    }

    import('cache.CacheFactory');
    $conf  = config("cache#{$key}");
    $cache = CacheFactory::create($conf['key'], $conf['conf']);

    //cache the current cache instance
    $_loaded[$key] = $cache;

    return $cache;
}

/**
 * quick interface to build or fetch the session
 *
 * @param   $key session service logic name defined in the config/session.conf.php
 * @param   $sessid user-define session id
 * @return  Object specified session object
 * @see     app/config/session.conf.php
*/
function build_session($key='File', $gen=false, $sessid=null)
{
    static $_loaded = array();

    if ( isset($_loaded[$key]) ) {
        return $_loaded[$key];
    }

    import('Session', false);
    $conf = config("session#{$key}");
    if ( $sessid != null ) {
        $conf['sessid'] = $sessid;
    }

    //create and cache the session instance
    $sess = new Session($key, $conf);
    $_loaded[$key] = $sess;

    return $sess->start($gen, $sessid);
}

/**
 * quick way to start and handler the mempure session
 * As a totally rewrite session implements and this has no
 * conflicts with the original php internal session
 *
 * @param   $key
 * @param   $val
 * @return  Mixed(The value mapping with the key or the session Object)
*/
function pure_session($key, $val=null)
{
    $e_name = 'pure_session_start';
    if ( ($sessObj = E($e_name)) == null ) {
        import('session.SessionFactory');
        $config  = config("session#Mempure");
        $sessObj = SessionFactory::create(
            $config['key'], $config['conf']
        );

        $sessObj->start();
        E($e_name, $sessObj);
    }

    if ( $val === null ) {
        return $sessObj->get($key);
    }

    $sessObj->set($key, $val);
    return $sessObj;
}

/**
 * application layer dynamic request resource pre-load
 * main for #controller function, cuz:
 * import('core.Cli_Controller') will cause the singal not working
 * and pre-load is the current way i choose to solve this problem
 *
 * @param   $uri (standart parse_uri result)
*/
function resource_preload_callback($uri)
{
    switch ( $uri->parts[0] ) {
    case 'cli':
        import('core.Cli_Controller', false);
        break;
    case 'script':
        import('core.S_Controller', false);
        break;
    #add more case here
    default:
        import('core.C_Controller', false);
        break;
    }
}
