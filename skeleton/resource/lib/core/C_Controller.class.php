<?php
/**
 * Common Controller supper class for common application module
 *
 * @author chenxin <chenxin619315@gmail.com>
*/

 //-----------------------------------------------------

class C_Controller extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * check the current request is logged in or not
     * and if it is logged in we return its session object
     *
     * @param   $errno (the error number)
     * @return  Mixed(boolean or lib.Session Object)
     * @see     app.lib.Session#validate
    */
    public function isLoggedIn(&$errno=null)
    {
        $sessKey = isset($this->conf->session_key) ? $this->conf->session_key : 'File';
        $sessObj = build_session($sessKey);
        if ( $sessObj->validate($errno) == false ) {
            return false;
        }

        return $sessObj;
    }
}
?>
