<?php
/**
 * TestController
 * 
 * @author chenxin<chenxin619315@gmail.com>
*/

 //------------------------------------------------------
 
class UtilController extends C_Controller
{
    /**
     * @param Input $input
     * @throws Exception
     */
    public function helper($input)
    {
        $stream_id = 13564;
        $ack_code  = '8KacYuPl';
        $biz       = 'mobile';
        var_dump(helper('ServiceExecutor#Test', array($stream_id, $ack_code, $biz)));
    }

    /**
     * @param Input $input
     * @return mixed
     */
    public function service($input)
    {
        $name = $input->get('name', null, 'Rock');
        return service(
            "TestService.greeting",
            array(
                'target'    => 'i mean everybody here',
                'include'   => 'a,b,c,d,e,f,g, and you of course',
                'name' => $name
            )
        );
    }

    /**
     * @param Input $input
     */
    public function login(Input $input)
    {
        return service("user.UserService.login", array('username' => 'xxxxx', 'password' => 'yyyy'));
    }
}