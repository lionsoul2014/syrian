<?php
/**
 * cache configuration array
 * 
 * @author  chenxin<chenxin619315@gmail.com>
 * @date    2014-12-13
*/

return array(
    /*
     * default local file cache:
     * 1. tpl cache
     * 2. some database search data
    */
    'NFile' => array(
        'key'   => 'NFile',
        'conf'  => array(
            'cache_dir' => '/Code/php/cache/app/',
            'length'    => 3000
        )
    ),

    /*
     * script local file cache:
    */
    'ScriptMerge'   => array(
        'key'   => 'NFile',
        'conf'  => array(
            'cache_dir' => APPPATH.'www/cache/',
            'length'    => 1000,
            'file_ext'  => '.js'
        )
    ),

    /*
     * stream/view static file cache configuration
    */
    'StreamViewStatic' => array(
        'key'   => 'NFile',
        'conf'  => array(
            'cache_dir' => APPPATH.'www/cache/',
            'length'    => 3000,        //@Note: cannot be changed
            'file_ext'  => '.html'
        )
    ),

    /*
     * default ditributed memory cache:
     * 1. database search data with high concurrency requests
     * 2. page execution accelerate
    */
    'Memcached' => array(
        'key'   => 'Memcached',
        'conf'  => array(
            'servers'   => array(
                array('localhost', 11211, 100)    // host, port, weight
            ),
            'ttl'       => 600, // time to live, default not expired
            // default: standard,  consistent was recommended,
            // for more infomation,  search 'consistent hash'
            'hash_strategy' => 'consistent',
            'prefix'        => ''
        )
    ),
    /*
     * @added at 2015-08-24
     * for all kinds of verification service
    */
    'Verify_dmem'   => array(
        'key'   => 'Memcached',
        'conf'  => array(
            'servers'   => array(
                array('localhost', 11211, 100)    // host, port, weight
            ),
            'ttl'       => 600, // time to live, default not expired
            // default: standard,  consistent was recommended,
            // for more infomation,  search 'consistent hash'
            'hash_strategy' => 'consistent',
            'prefix'        => ''
        )
    )

);
?>
