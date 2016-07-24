<?php
/**
 * Test service class for testing only
 *
 * @author  chenxin<chenxin619315@gmail.com>
*/

 //------------------------------------------------

import('service.Service');

class TestService extends Service
{
    /**
     * construct method
    */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * hello handler
     *
     * @param   $input
     * @param   Mixed
    */
    public function hello($input)
    {
        return "hello, {$input}\n";
    }

    /**
     * greeting handler
     *
     * @param   $input
     * @param   Mixed
    */
    public function greeting($input)
    {
        return "nice to meet you, {$input}\n";
    }
    
}
?>
