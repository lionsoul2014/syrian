<?php
header('Content-Type: text/html; charset=utf-8');

//System Base Path
define('BASEPATH',      dirname( dirname(__FILE__) ) . '/syrian/');
 
//Application base dir
define('APPPATH',       dirname(__FILE__) . '/');

define('SR_LIBDIR',        'lib');      //library directory name
define('SR_CONFIGDIR',     'config');   //config directory name
define('SR_MODELDIR',      'model');    //model directory name
define('SR_CTRLDIR',       'app');      //controller directory name
define('SR_DEFAULT_CTRL',  'article');  //default controller
define('SR_TEMPDIR',       'template'); //template directory name
define('SR_CACHEDIR',      'cache');    //cache directory name

//system link style constants 1 for STD style, 0 for DIR style
define('SR_LINK_STYLE',    1);
define('SR_URI_REWRITE',   false);

//require the framework entrance file
require(BASEPATH . '/core/Syrian.php');
?>
