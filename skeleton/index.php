<?php
header('Content-Type:text/html; charset=utf-8');
define('__HOME__', dirname(__FILE__));
require '../opert/core/Opert.class.php';

$_cfg = array(
    'sys_cfg'   => __HOME__.'/config/sys.cfg.php',
    'usr_cfg'   => __HOME__.'/config/usr.cfg.php'
);
Opert::init(__HOME__, OPT_CON_URL, $_cfg);
#Opert::response($_SERVER['PHP_SELF']);
Opert::response($_SERVER['REQUEST_URI']);
?>