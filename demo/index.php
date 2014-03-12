<?php
header('Content-Type:text/html; charset=utf-8');

define('APPPATH', dirname(__FILE__) . '/');

require '../syrian/core/Syrian.php';

import('util.filter.Filter');
$input = new Input();
echo $input->get->getInt('id') , "\n<br />";
echo $input->get->getString('str'), "\n<br />";
echo $input->get->getModel('str', array(OP_STRING, OP_LIMIT(3, 11), OP_SANITIZE_INT) ) , "\n<br />";
echo $input->server->getString('REQUEST_URI');
?>
