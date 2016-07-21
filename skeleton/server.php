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

/*
 * require the framework kernel file
 * set the SR_INC_COMPONENTS to controll the parts to load
*/
define('SR_INC_COMPONENTS', 0xFF);
//require(BASEPATH . 'core/Syrian.merge.min.php');
require(BASEPATH . 'core/Syrian.php');

//-----------------------------------------------------------------

import('core.Function', false);
date_default_timezone_set('PRC');

/*
 * create the request input and output 
 * then register the output & uri to the global environment variables
*/
$input  = new Input(NULL);
$output = new Output();
E('output', $output);

/*
 * parse the current request uri that fetched througth $_SERVER['REQUEST_URI']
 * then locate the controller througth the parsed result
 * finally invoke the controller#run method to handler the request
 *
 * @see syrian.core.kerner.Function#parse_uri
 * @see syrian.core.kerner.Function#controller
*/

try {
    $uri = parse_uri($_SERVER['REQUEST_URI'], '/', array('article', 'index'));
    $ret = controller($uri, $input, $output);
    if ( ! is_null($ret) ) {
        $output->display(is_array($ret) ? json_encode($ret) : $ret);
    }
} catch ( Exception $e ) {
    //@Note: You may need to do the error log here
    if ( SR_CLI_MODE ) echo $e, "\n";
    else {
        echo("Sorry, We cannot process the current request with uri=\"{$_SERVER['REQUEST_URI']}\"\n");
    }
}

?>
