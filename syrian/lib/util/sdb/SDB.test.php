<?php
require 'SDB.class.php';

$_sdb = new SDB(dirname(__FILE__), 'user');
#echo 'store(\'jcseg\'): ', $_sdb->store('jcseg', 'jcseg是使用java开发的一款开源中文分词器...', false),"\n";
#echo 'store(\'friso\'): ', $_sdb->store('friso', 'friso是使用C语言开发的一款开源高性能中文分词组建...', false), "\n";
#echo 'save: ', $_sdb->save(), "\n";

echo 'fetch(\'jcseg\'): ', $_sdb->fetch('jcseg'),"\n";
echo 'fetch(\'friso\'): ', $_sdb->fetch('friso'),"\n";
?>
