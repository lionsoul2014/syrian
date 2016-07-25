<?php
/**
 * application common configuration setting
 *
 * @author  chenxin<chenxin619315@gmail.com>
*/

 //---------------------------------------------------------

/**
 * image snapshot size constants (width limit)
*/
defined('CDN_IMGW_X96')     or define('CDN_IMGW_X96',  '!96');
defined('CDN_IMGW_X320')    or define('CDN_IMGW_X320', '!320');
defined('CDN_IMGW_X480')    or define('CDN_IMGW_X480', '!480');
defined('CDN_IMGW_X540')    or define('CDN_IMGW_X540', '!540');
defined('CDN_IMGW_X640')    or define('CDN_IMGW_X640', '!640');
defined('CDN_IMGW_X720')    or define('CDN_IMGW_X720', '!720');
defined('CDN_IMGW_THEME')   or define('CDN_IMGW_THEME', '!theme');

$config = new StdClass();
$config->url     = 'http://www.syrian.org';
$config->img_url = 'http://img.syrian.org';

//script debug conf
$config->script = array(
    'debug'     => false,
    'compress'  => 0
);

$config->flush_key   = 'mqh-flush-key';
$config->session_key = 'File';  //default session key

/*
 * script configuration
 * debug: merge & cache & compress the script ?
 * compress: compress level setting
*/
$config->script = array(
    'debug'     => true,
    'compress'  => 0
);

//distribute servers
// multiples web servers will be use to do the load balancing
//DNS resolution is the current way
//unique domain to the specifield server sets
$config->clusters   = array(
    //'http://node1.lerays.com/',
    //'http://node2.lerays.com/'
    'http://192.168.1.105/'
);

/*
 * App client user agent identifier 
 * currently mainly for Controller#isAndroid Controller#isIOS define
*/
$config->app_ua_identifier = NULL;

/*
 * admin allow access hosts
*/
$config->admin_allow_hosts  = array(
    'localhost'      => true,
    'www.syrian.org' => true,
);

return $config;
?>
