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
 define('SR_VERSION', '1.0.0');
 
 /**
  * Base Path.
 */
 define('BASEPATH', dirname( dirname(__FILE__) ) . '/');
 
 /**
  * Library directory name
 */
 define('LIBDIR',  'lib');

 /**
  * Load The Common Global Functions
  */
 require(BASEPATH . 'core/Common.php');
 
 /**
  * Load the input class
 */
 require(BASEPATH . 'core/Input.php');
?>