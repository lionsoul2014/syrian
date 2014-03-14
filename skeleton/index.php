<?php
header('Content-Type:text/html; charset=utf-8');

/**
  * System Base Path
 */
define('BASEPATH', dirname( dirname(__FILE__) ) . '/syrian/');
 
/**
 * Application base dir
*/
define('APPPATH', dirname(__FILE__) . '/');

/**
  * Library directory name
 */
define('LIBDIR',  'lib');

/**
 * Application model directory
*/
define('MODELDIR', 'model');

require(BASEPATH . '/core/Syrian.php');

//Usage demo
Loader::import('Filter');
$input = new Input();
echo $input->get('id') , "\n<br />";
echo $input->get('str'), "\n<br />";
echo $input->get('str',
    array(OP_STRING, OP_LIMIT(3, 11), OP_SANITIZE_INT), $_errno ) , "\n<br />";

//model fetch
$_model = array(
    'id'        => array(OP_NUMERIC, OP_LIMIT(1, 3), OP_SANITIZE_INT),
    'str'       => array(OP_STRING, OP_LIMIT(3, 12), OP_SANITIZE_HTML)
);
$_ret = $input->getModel($_model, $_errno);
var_dump($_ret);
echo '<br />';
echo $input->server('REQUEST_URI'), '<br />';

$_instance = Loader::model('Article', 'article');
echo $_instance->run();
?>
