<?php
/**
 * Test service class for testing only
 *
 * @author  chenxin<chenxin619315@gmail.com>
*/

 //------------------------------------------------

import('service.Service');

/**
 * Class UserService
 * @author yangjian
 */
class UserService extends Service
{

    /**
     * @param ServiceInputBean $input
     * @return array
     */
    public function login(ServiceInputBean $input)
    {
        return array(
            'username' => $input->get('username'),
            'password' => $input->get('password'),
            'email' => 'yangjian102621@gmail.com'
        );
    }
    
}