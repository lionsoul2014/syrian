<?php if ( ! defined('BASEPATH') ) exit('No Direct Access Allowed!');
/**
 * Syrian Initialize Script
 * Load the common functons and base classes
 *
 * @author    chenxin <chenxin619315@gmail.com>
 * @link    http://www.lionsoul.org/syrian
 */

 //--------------------------------------------------------------
 
//Syrian Version Number
define('SR_VERSION', '2.0');

//sapi mode define
defined('SR_CLI_MODE')      or define('SR_CLI_MODE', strncmp(php_sapi_name(), 'cli', 3)=='cli');
defined('SR_FLUSH_MODE')    or define('SR_FLUSH_MODE',  'flush_mode');
defined('SR_IGNORE_MODE')   or define('SR_IGNORE_MODE', 'ignore_mode');

//check and define the including components
//0x01: Function
//0x02: Loader
//0x04: Helper
//0x08: Input
//0x10: Uri
//0x20: Output
//0x40: Model
//0x80: Controller
//0xFF: all of them
//0x47: cli mode
//0x7F: missing controller
defined('SR_INC_COMPONENTS') or define('SR_INC_COMPONENTS', 0xFF);

//Load the common resource loader
require(BASEPATH . 'core/kernel/Function.php');
//require(BASEPATH . 'core/kernel/Loader.php');
require(BASEPATH . 'core/kernel/Helper.php');

//Load the input class manage the input of the controller/
if ( (SR_INC_COMPONENTS & 0x08) != 0 ) require(BASEPATH . 'core/kernel/Input.php');

//Load the Uri class offer qucik interface to access the request uri
//if ( (SR_INC_COMPONENTS & 0x10) != 0 ) require(BASEPATH . 'core/kernel/Uri.php');

//Load the Output class
if ( (SR_INC_COMPONENTS & 0x20) != 0 ) require(BASEPATH . 'core/kernel/Output.php');

//Load the parent Model class
require(BASEPATH . 'core/kernel/Model.php');

//Load the parent Controller class
if ( (SR_INC_COMPONENTS & 0x80) != 0 ) require(BASEPATH . 'core/kernel/Controller.php');

/**
 * framework initialize
 * 1. parse and load the arguments for cli mode
 * 2. check and set the process title
 * @date: 2015-12-25
*/
if ( SR_CLI_MODE ) {
    _cli_initialize();

    if ( isset($_SERVER['PROCESS_TITLE']) ) {
        @cli_set_process_title($_SERVER['PROCESS_TITLE']);
    }
}
?>
