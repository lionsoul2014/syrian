<?php
header('Content-Type: text/html; charset=utf-8');

//System Base Path
define('BASEPATH',      dirname(dirname(__FILE__)) . '/syrian/');
 
//Application base path
define('APPPATH',           dirname(__FILE__) . '/');

define('SR_LIBPATH',        APPPATH.'lib/');            //library directory name
define('SR_CONFPATH',       APPPATH.'config/');         //config directory name
define('SR_STATICPATH',     APPPATH.'static/');         //static directory name
define('SR_MODELPATH',      APPPATH.'model/');          //model directory name
define('SR_CTRLPATH',       APPPATH.'app/');            //controller directory name
define('SR_HELPERPATH',     APPPATH.'helper/');         //helper directory name
define('SR_VIEWPATH',       APPPATH.'template/');       //template directory name
define('SR_CACHEPATH',      APPPATH.'cache/');          //cache directory name
define('SR_TMPPATH',        APPPATH.'tmp/');            //tmp directory name
define('SR_SERVICEPATH',    APPPATH.'service/');        //service directory name
define('SR_UPLOADDIR',      'uploadfiles');
define('SR_UPLOADPATH',     APPPATH.SR_UPLOADDIR.'/');
define('SR_NODE_NAME',      'default');                 //define the node name

define('SR_DEBUG',          false);                     //debug mode?
define('SY_DB_DEBUG',       false);                     //database debug mode?
define('SR_SYS_503',        false);                     //system 503 ?    
define('SR_CHARSET',        'utf-8');                   //default charset

//require the framework entrance file
define('SR_INC_COMPONENTS', 0xFF);
require(BASEPATH . 'core/Syrian.merge.min.php');

//system link style constants 1 for STD style, 0 for DIR style
define('SR_LINK_STYLE',    URI_STD_STYLE);
define('SR_URI_REWRITE',   true);


//---------------------------------------------------

/*
 * Intiailze the system and fetch the controller of the
 *  current request and then invoke the it#run method to handler the request
*/
Loader::import('StdModel', 'core');
Loader::import('StdController', 'core');
Loader::import('StdUri', 'core');

$URI = new STDUri(SR_URI_REWRITE, SR_LINK_STYLE);
$URI->parseUrl();
$_CTRL = $URI->getController('article');
if ( $_CTRL == NULL ) {
    if ( SR_CLI_MODE ) {
        exit("Error: Unable to locate the Controller\n");
    } else {
        $URI->redirect('error/404');
    }
}

//---------------------------------------------------
date_default_timezone_set('PRC');            //set the default time zone

$_CTRL->run();      //run the project
?>
