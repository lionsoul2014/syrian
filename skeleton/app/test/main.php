<?php
/**
 * TestController
 * 
 * @author chenxin<chenxin619315@gmail.com>
*/

 //------------------------------------------------------
 
class TestController extends C_Controller
{    
    public function _helper($input)
    {
        $stream_id = 13564;
        $ack_code  = '8KacYuPl';
        $biz       = 'mobile';
        var_dump(helper('ServiceExecutor#Test', array($stream_id, $ack_code, $biz)));
    }

    public function _service($input)
    {
        //return service('test.hello', array('name' => 'liroe'));
        return service(
            'test.greeting', 
            array(
                'target'    => 'i mean everybody here',
                'include'   => 'a,b,c,d,e,f,g, and you of course'
            )
        );
    }

}
?>
