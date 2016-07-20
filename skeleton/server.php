<?php
//Framework Base Path
define('BASEPATH',          dirname(__DIR__).'/syrian/');
 
//Application base path
define('APPPATH',           dirname(__FILE__).'/');

define('SR_LIBPATH',        APPPATH.'resource/lib/');       //library directory name
define('SR_CONFPATH',       APPPATH.'config/');             //config directory name
define('SR_STATICPATH',     APPPATH.'www/static/');         //static directory name
define('SR_MODELPATH',      APPPATH.'resource/model/');     //model directory name
define('SR_CTRLPATH',       APPPATH.'app/');                //controller directory name
define('SR_HELPERPATH',     APPPATH.'resource/helper/');    //helper directory name
define('SR_VIEWPATH',       APPPATH.'resource/template/');  //template directory name
define('SR_CACHEPATH',      APPPATH.'storage/cache/');      //cache directory name
define('SR_TMPPATH',        APPPATH.'storage/tmp/');        //tmp directory name
define('SR_SERVICEPATH',    APPPATH.'resource/service/');   //service directory name
define('SR_UPLOADDIR',      'uploadfiles');
define('SR_UPLOADPATH',     APPPATH.'www/'.SR_UPLOADDIR.'/');
define('SR_NODE_NAME',      'node1');                       //define the node name
define('SR_CHARSET',        'utf-8');   //default charset

//require the framework entrance file
define('SR_INC_COMPONENTS', 0x7F);
//require(BASEPATH . 'core/Syrian.merge.min.php');
require(BASEPATH . 'core/Syrian.php');

//system link style constants 1 for STD style, 0 for DIR style
define('SR_LINK_STYLE',     URI_STD_STYLE);
define('SR_URI_REWRITE',    true);

//-----------------------------------------------------------------

/*
 * Intiailze the system and fetch the controller of the
 *  current request and then invoke its#run method to handler the request
*/
import('core.STDUri', false);
$URI = new STDUri(SR_URI_REWRITE, SR_LINK_STYLE);
$URI->parseUrl();
$ctrl = $URI->getController('article');
if ( $ctrl == NULL ) {
    if ( SR_CLI_MODE ) {
        throw new Exception("Unable to locate the Controller");
    } else {
        $URI->redirect('error/404');
    }
}

date_default_timezone_set('PRC');   //set the default time zone

//-----------------------------------------------------------------

/*
 * create the request input and output then register the output to the global environment pool
 * load the application layer common function library
*/
$input  = new Input(NULL);
$output = new Output();
E('output', $output);
import('core.Function', false);

//get the executed result and display it
$ctrl->uri = $URI;
$ret = $ctrl->run($input, $output);
$output->display(is_array($ret) ? json_encode($ret) : $ret);
?>
