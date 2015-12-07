<?php
require dirname(dirname(__FILE__)).'/db/DbFactory.class.php';

//Mysql handling class test program
$_host = array(
    'pconnect'    => false,
    'host'        => 'localhost',
    'user'        => 'root',
    'pass'        => '153759',
    'port'        => 3306,
    'db'        => 'test',
    'charset'    => 'utf8');
$Db = Dbfactory::create('mysql', $_host);
//insert
/*$_array = array(
    'user'=>'syrian',
    'pass'=>md5('syrian'), 'addtime'=>date('Y-m-d H:i:s'));

$_ret = $Db->insert('sy_admin', $_array);
if ( $_ret != FALSE ) echo 'insert ok.';
else echo 'insert fail.';*/

//getOneRow
/*$_query = 'select * from sy_admin where Id = 7';
if ( ( $_ret = $Db->getOneRow( $_query, $_ret ) ) != FALSE ) {
    echo 'user: '.$_ret['user'] . ', pass: ' . $_ret['pass'] . ', time: ' . $_ret['addtime'] . '<br />' ;
}*/

//getList
$_query = 'select * from sy_admin';
if ( ( $_ret = $Db->getList( $_query ) ) != FALSE ) {
    foreach ( $_ret as $_value ) {
        echo 'user: '.$_value['user'] . ', pass: ' . $_value['pass'] . ', time: ' . $_value['addtime'] . '<br />';
    }
}

//update
/*$_array = array('pass'=>md5('friso'));
if ( $Db->update('sy_admin', $_array, "user='jcseg'") != FALSE ) echo 'update ok.';
else echo 'update fail.';*/

//delete
/*$_ret = $Db->delete('sy_admin', "user='test'");
if ( $_ret != FALSE ) echo 'delete ok.';
else echo 'delete fail.';*/
?>
