<?php
/**
 * session configuration array
 * 
 * @added:  2014-12-13
 * @author  chenxin<chenxin619315@gmail.com>
*/

//save_path: the session file store path
//ttl: to to live for the session
return array(
    'File' => array(
        'save_path'     => '/session/',
        'file_ext'      => '.ses',
        'ttl'           => 604800, // session 过期时间
        //'cookie_domain' => '.xjxh.pro',     // must start with dot to support all sub share the cookies
        'session_name'  => 'SR_SESSID',
        'domain_strategy' => 'all_sub_host'     //domain strategy cur_host | all_sub_host
    ),
    
    'Memcached' => array(
        'servers'       => array(
            array('localhost', 11211, 100)      // host, port, weight
        ),
        'ttl'           => 604800,              // time to live
        // default: standard,  consistent was recommended,
        // for more infomation,  search 'consistent hash'
        'hash_strategy' => 'consistent',
        //'hash'          => 'default', // default| md5 | crc | fnv1_32 | fnv1a_32 | fnv1a_64 | fnv1_64 | hsieh | murmur
        'prefix'        => '',
        //'cookie_domain' => '.lerays.com',     // must start with dot to support all sub share the cookies  
        'session_name'  => 'SR_SESSID',
        'domain_strategy' => 'all_sub_host'     //domain strategy cur_host | all_sub_host
    ),
    
    'Mempure' => array(
        'key'   => 'Mempure',
        'conf'  => array(
            'servers'         => array(
                array('localhost', 11211, 100)      // host, port, weight
            ),
            'ttl'             => 3600,              // time to live
            'expire_strategy' => 'request',         //request | global
            'cookie_extra'    => 0,                 //extra alive seconds for cookies
            'reuse_strategy'  => array(            //single client reuse strategy
                'max_num' => 30,
                'helper'  => 'FrameworkCache#SessionMempure'
            ),
            // default: standard,  consistent was recommended,
            // for more infomation,  search 'consistent hash'
            'hash_strategy'   => 'consistent',
            //'hash'          => 'default', // default| md5 | crc | fnv1_32 | fnv1a_32 | fnv1a_64 | fnv1_64 | hsieh | murmur
            'prefix'          => null,
            //'cookie_domain' => '.lerays.com',     // must start with dot to support all sub share the cookies  
            'session_name'    => 'MEMPURE_ID',
            'domain_strategy' => 'all_sub_host'     //domain strategy cur_host | all_sub_host
        )
    )
);
?>
