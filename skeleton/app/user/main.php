<?php
/**
 * UserController handler basic user request
 * 
 * @author chenxin <chenxin619315@gmail.com>
*/

class UserController extends C_Controller
{    
    public function _index($input)
    {

    }

    public function _profile()
    {
        $user_id = $input->getInt('user_id');
        if ( $user_id == false ) {
            return json_view(STATUS_INVALID_ARGS, 'Invalid Arguments');
        }

        $data = array(
            'user_id'   => $user_id,
            'head_img'  => 'http://git.oschina.net/uploads/87/5187_lionsoul.jpg',
            'nickname'  => 'lionsoul',
            'signature' => '平凡 | 执着'
        );

        return json_view(STATUS_OK, $data);
    }
    
    public function _signIn($input)
    {
        $data = array(
            'head_img'  => 'http://git.oschina.net/uploads/87/5187_lionsoul.jpg',
            'nickname'  => 'lionsoul',
            'signature' => '平凡 | 执着'
        );

        return json_view(STATUS_OK, $data);
    }

    public function _signUp($input)
    {
        return json_view(STATUS_OK, 'Ok');
    }
    
}
?>
