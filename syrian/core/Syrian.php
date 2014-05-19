<?php if ( ! defined('BASEPATH') ) exit('No Direct Access Allowed!');
/**
 * Syrian Initialize Script
 * Load the common functons and base classes
 *
 * @author	chenxin <chenxin619315@gmail.com>
 * @link	http://www.lionsoul.org/syrian
 */

 //--------------------------------------------------------------
 
//Syrian Version Number
define('VERSION', '1.0.0');

//Load the common resource loader
require(BASEPATH . 'core/Loader.php');

//Load the input class manage the input of the controller/
if ( defined('SR_CLI_MODE') )	;
else require(BASEPATH . 'core/Input.php');

//Load the Uri class offer qucik interface to access the request uri
if ( defined('SR_CLI_MODE') ) 	;
else require(BASEPATH . 'core/Uri.php');

//Load the Output class
if ( defined('SR_CLI_MODE') ) 	;
else require(BASEPATH . 'core/Output.php');

//Load the parent Model class
require(BASEPATH . 'core/Model.php');

//Load the parent Controller class
if ( defined('SR_CLI_MODE') )	;
else require(BASEPATH . 'core/Controller.php');
?>
