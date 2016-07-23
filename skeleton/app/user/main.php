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
        $sess = $this->isLoggedIn();
        if ( $sess == false ) {
            return json_view(STATUS_NO_SESSION, 'No session');
        }

        $data = array(
            'user_id'   => $sess->getUid(),
            'head_img'  => $sess->get('head_img'),
            'nickname'  => $sess->get('nickname'),
            'signature' => $sess->get('signature')
        );

        return json_view(STATUS_OK, $data);
    }
    
    public function _signIn($input)
    {
        import('Session', false);

        $sessKey = isset($this->session_key) ? $this->session_key : 'File';
        $conf    = config("session#{$sessKey}");
        $conf['sessid'] = '1707ydlteVx9VPM9W0LBMHKCRWT648m9';

        $sessObj = Session::start($sessKey, $conf, true);
        $sessObj->register(7);

        $sessObj->set('user_id',  7);
        $sessObj->set('head_img', 'http://git.oschina.net/uploads/87/5187_lionsoul.jpg');
        $sessObj->set('nickname', 'lionsoul');
        $sessObj->set('signature', '平凡 | 执着');

        return json_view(STATUS_OK, 'Ok');
    }

    public function _logOut()
    {
        $sess = $this->isLoggedIn();
        if ( $sess != false ) {
            $sess->destroy();
        }

        return json_view(STATUS_OK, 'Ok');
    }

    public function _signUp($input)
    {
        return json_view(STATUS_OK, 'Ok');
    }
    
}
?>
