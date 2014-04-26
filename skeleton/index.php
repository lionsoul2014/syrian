<?php
header('Content-Type: text/html; charset=utf-8');

//System Base Path
define('BASEPATH',      dirname(dirname(__FILE__)) . '/syrian/');
 
//Application base path
define('APPPATH',       dirname(__FILE__) . '/');

define('SR_LIBPATH',    APPPATH.'lib/');      //library directory name
define('SR_CONFPATH',   APPPATH.'config/');   //config directory name
define('SR_MODELPATH',  APPPATH.'model/');    //model directory name
define('SR_CTRLPATH',   APPPATH.'app/');      //controller directory name
define('SR_VIEWPATH',   APPPATH.'template/'); //template directory name
define('SR_CACHEPATH',  APPPATH.'cache/');    //cache directory name

//require the framework entrance file
require(BASEPATH . 'core/Syrian.php');

//system link style constants 1 for STD style, 0 for DIR style
define('SR_LINK_STYLE',    URI_STD_STYLE);
define('SR_URI_REWRITE',   true);


//---------------------------------------------------

/*
 * Intiailze the system and fetch the controller of the
 *  current request and then invoke the it#run method to handler the request
*/
Loader::import('SQLModel', 'core');
Loader::import('C_Controller', NULL, false);
Loader::import('STDUri', 'core');

$URI = new STDUri(SR_URI_REWRITE, SR_LINK_STYLE);
$URI->parseUrl();
$_CTRL = $URI->getController('article');
if ( $_CTRL == NULL ) $URI->redirect('error/404');

$_CTRL->run();      //run the project
?>
