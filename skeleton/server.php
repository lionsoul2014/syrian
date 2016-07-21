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
define('SR_POWERBY',        'Syrian/2.0');

//require the framework entrance file
define('SR_INC_COMPONENTS', 0xFF);
//require(BASEPATH . 'core/Syrian.merge.min.php');
require(BASEPATH . 'core/Syrian.php');

//system link style constants 1 for STD style, 0 for DIR style
define('SR_LINK_STYLE',     URI_STD_STYLE);
define('SR_URI_REWRITE',    true);

//-----------------------------------------------------------------

import('core.STDUri',   false);
import('core.Function', false);


/*
 * Intiailze the system and fetch the controller of the
 *  current request and then invoke its#run method to handler the request
*/

$uri  = new STDUri(SR_URI_REWRITE, SR_LINK_STYLE);
$uri->parseUrl();
$ctrl = $uri->getController('article');
if ( $ctrl == NULL ) {
    if ( SR_CLI_MODE ) {
        throw new Exception("Unable to locate the Controller");
    } else {
        $uri->redirect('error/404');
    }
}

//-----------------------------------------------------------------

date_default_timezone_set('PRC');

/*
 * create the request input and output 
 * then register the output & uri to the global environment variables
*/
$input  = new Input(NULL);
$output = new Output();
E(array(
    'uri'    => $uri,
    'output' => $output
));

//get the executed result and display it
try {
    $ret = $ctrl->run($uri, $input, $output);
    $output->display(is_array($ret) ? json_encode($ret) : $ret);
} catch ( Exception $e ) {
    //You may need to do the error log here
}
?>
