<?php
if ( substr(php_sapi_name(), 0, 3) !== 'cli' ) 
{
    die('Error: This program can only run in CLI mode');
}

//System Base Path
define('BASEPATH',      dirname(dirname(__FILE__)) . '/syrian/');
 
//Application base path
define('APPPATH',           dirname(__FILE__).'/');

define('SR_LIBPATH',        APPPATH.'lib/');      //library directory name
define('SR_CONFPATH',       APPPATH.'config/');   //config directory name
define('SR_MODELPATH',      APPPATH.'model/');    //model directory name
define('SR_HELPERPATH',       APPPATH.'helper/');      //helper directory name
define('SR_VIEWPATH',       APPPATH.'template/'); //template directory name
define('SR_CACHEPATH',      APPPATH.'cache/');    //cache directory name

//require the framework entrance file
define('SR_INC_COMPONENTS', 0x47);
require(BASEPATH . 'core/Syrian.merge.min.php');

//----------------------------------------------------------
date_default_timezone_set('PRC');            //set the default time zone

//any cli script could request this
?>
