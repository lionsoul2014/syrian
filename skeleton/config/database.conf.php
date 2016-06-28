<?php
return array(
    //normal sql style
    'main'  => array(
        'host'      => 'localhost',
        'port'      => 3306,
        'user'      => 'root',
        'pass'      => '153759',
        'db'        => 'db_opert',
        'charset'   => 'utf8',
        'serial'    => 'main-db'
    ),
    
    'user' => array(
        'host'  => '192.168.1.102',
        'port'  => 3306,
        'user'  => 'root',
        'pass'  => '123456',
        'db'    => 'db_opert',
        'charset'   => 'utf8',
        'serial'    => 'user-db'
    ),
    
    //mongo style
    'userMongo'   => 'mongodb://chenxin:xiaoyanzi@127.0.0.1/syrian',
);
?>
