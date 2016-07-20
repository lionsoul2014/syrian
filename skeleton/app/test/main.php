<?php
/**
 * TestController
 * 
 * @author chenxin<chenxin619315@gmail.com>
*/

 //------------------------------------------------------
 
class TestController extends C_Controller
{    
    public function run()
    {
        parent::run();

        $stream_id = 13564;
        $ack_code  = '8KacYuPl';
        $biz       = 'mobile';
        var_dump(helper('ServiceExecutor#Test', array($stream_id, $ack_code, $biz)));
    }

}
?>
