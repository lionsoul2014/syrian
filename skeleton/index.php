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
  *     used in Loader#import
 */
define('LIBDIR',  'lib');

/**
 * Application model directory
 *      used in Loader#model
*/
define('MODELDIR', 'model');

require(BASEPATH . '/core/Syrian.php');

//Usage demo
Loader::import('Filter');
$input = new Input();
echo 'get(id): '.$input->get('id') , "\n<br />";
echo 'get(str): '.$input->get('str'), "\n<br />";
echo 'get(str, model): '.$input->get('str',
    array(OP_STRING, OP_LIMIT(3, 11), OP_SANITIZE_INT), $_errno ) , "\n<br />";

//model fetch
$_model = array(
    'id'        => array(OP_NUMERIC, OP_LIMIT(1, 3), OP_SANITIZE_INT),
    'str'       => array(OP_STRING, OP_LIMIT(3, 12), OP_SANITIZE_HTML)
);
$_ret = $input->getModel($_model, $_errno);
echo 'getModel(model): ';
var_dump($_ret);
echo '<br />';
echo 'server(REQUEST_URI): '.$input->server('REQUEST_URI'), '<br />';

$_instance = Loader::model('Article', 'article');
echo $_instance->run(), '<br />';

/*
foreach ( $_SERVER as $_key => $_val )
{
    echo $_key, '=>', $_val, '<br />';
}
*/
?>
