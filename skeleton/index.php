<?php
header('Content-Type:text/html; charset=utf-8');

/**
 * Application base dir
*/
define('APPPATH', dirname(__FILE__) . '/');

/**
 * Application model directory
*/
define('MODELDIR', 'model');

require '../syrian/core/Syrian.php';

import('util.filter.Filter');
$input = new Input();
echo $input->get('id') , "\n<br />";
echo $input->get('str'), "\n<br />";
echo $input->get('str', array(OP_NUMERIC, OP_LIMIT(3, 11), OP_SANITIZE_INT), $_errno ) , "\n<br />";

$_model = array(
    'id'        => array(OP_NUMERIC, OP_LIMIT(1, 3), OP_SANITIZE_INT),
    'str'       => array(OP_STRING, OP_LIMIT(3, 12), OP_SANITIZE_HTML)
);
$_ret = $input->getModel($_model, $_errno);
var_dump($_ret);
echo '<br />';
echo $input->server('REQUEST_URI');

$_instance = loadModel('Article', 'article');
echo $_instance->run();
?>
