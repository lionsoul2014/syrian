<?php
/**
 * Common Controller supper class for common application module
 *
 * @author chenxin <chenxin619315@gmail.com>
*/

 //-----------------------------------------------------
 
class C_Controller extends Controller
{
    protected $dataType = 'html';
    
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Rewrite the run method
     *      to add some basic initialize
    */
    public function run()
    {
        parent::run();
    }

    /**
     * check if the current device is Android
     * 
     * @param  app
     * @return boolean
    */
    protected function isAndroid($app=false)
    {
        $uAgent = $this->input->server('HTTP_USER_AGENT');
        $isAnd  = stripos($uAgent, 'Android') !== false;
        return $app ? ((stripos($uAgent, $this->appconf->app_ua_identifier)!==false) && $isAnd) : $isAnd;
    }

    /**
     * check if the current device is ios
     * 
     * @param  app
     * @return boolean
    */
    protected function isIOS($app=false)
    {
        $uAgent = $this->input->server('HTTP_USER_AGENT');
        $isIOS  = (stripos($uAgent, 'iOS') !== false || stripos($uAgent, 'iPhone') !== false);
        return $app ? ((strpos($uAgent, $this->appconf->app_ua_identifier)!==false) && $isIOS) : $isIOS;
    }

}
?>
