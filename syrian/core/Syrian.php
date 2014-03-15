<?php
/**
 * Syrian Initialize Script
 * Load the common functons and base classes
 *
 * @author	chenxin <chenxin619315@gmail.com>
 * @link	http://www.lionsoul.org/syrian
 */

 //--------------------------------------------------------------
 
/**
 * Syrian Version Number
 */
define('VERSION', '1.0.0');

/**
 * Load The Common Global Functions
 */
//require(BASEPATH . 'core/Common.php');

/**
 * Load the common resource loader
*/
require(BASEPATH . 'core/Loader.php');

/**
 * Load the input class
 *     manage the input of the controller
*/
require(BASEPATH . 'core/Input.php');

/**
 * Load the Uri class
 *     offer qucik interface to access the request uri
*/
require(BASEPATH . 'core/Uri.php');

$URI = new Uri();
$URI->parse_url();
echo $URI->makeStyleUrl('article', 'list');

/**
 * Load the parent Model class
*/
require(BASEPATH . 'core/Model.php');
?>