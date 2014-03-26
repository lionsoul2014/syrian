<?php
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

//require(BASEPATH . 'core/Common.php');

//Load the common resource loader
require(BASEPATH . 'core/Loader.php');

//Load the input class manage the input of the controller/
require(BASEPATH . 'core/Input.php');

//Load the Uri class offer qucik interface to access the request uri
require(BASEPATH . 'core/Uri.php');

//Load the Output class
require(BASEPATH . 'core/Output.php');

//Load the parent Model class
require(BASEPATH . 'core/Model.php');

//Load the parent Controller class
require(BASEPATH . 'core/Controller.php');

/*
 * Intiailze the system and fetch the controller of the
 *  current request and then invoke the it#run method to handler the request
*/
$URI = new Uri(defined('SR_URI_REWRITE') ? true : false,
        defined('SR_LINK_STYLE') ? SR_LINK_STYLE : URI_STD_STYLE);
$URI->parseUrl();

//get the module main file
if ( $URI->module == NULL ) $URI->module = SR_DEFAULT_CTRL;
$_ctrl_file = APPPATH . SR_CTRLDIR . '/' . $URI->module . '/main.php';

//check the existence of the module main file
if ( ! file_exists($_ctrl_file) ) $URI->redirect('error/404');
require $_ctrl_file;

//get the controller class
$_class = ucfirst($URI->module).'Controller';
if ( ! class_exists($_class) ) exit('Error: Controller#' . $_class . ' not exists!');

$_CTRL = new $_class();
$_CTRL->uri     = $URI;
$_CTRL->input   = new Input();
$_CTRL->output  = new Output();

$_ctrl_file = NULL;$_class = NULL;  //clear the temp variable

//run the project
$_CTRL->run();
?>
