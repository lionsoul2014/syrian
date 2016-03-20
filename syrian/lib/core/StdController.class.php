<?php
/**
 * common controller for sketelon
 *
 * @author  chenxin<chenxin619315@gmail.com>
*/

 //--------------------------------------------------

class StdController extends Controller
{
    public function __construc( )
    {
        parent::__construct();
    }
    
    public function run()
    {
        $this->view     = $this->getView();
        $this->sysconf  = Loader::config('sys');
    }
    
    /**
     * internal method to get the common view
     *
     * @param   $_key
     * @return  Bool
    */
    protected function getView( $type = 'html', $_timer = 0 )
    {
        Loader::import('ViewFactory', 'view');
        
        $_conf = NULL;
        if ( strtolower($type) == 'html' ) {
            $_conf  = array(
                'cache_time'    => $_timer,
                'tpl_dir'       => SR_VIEWPATH,
                'cache_dir'     => SR_CACHEPATH.'tpl/'.$this->uri->module.'/'
            );
        }
        
        //return the html view
        return ViewFactory::create($type, $_conf);
    }
    
    /**
     * detect the access device is mobile or not
     * pc(winnt, linux, mac), mobile(android, iphone)
     *
     * @return    boolean
    */
    protected function isMobile()
    {
        if ( ($site = $this->input->get('site')) != false ) {
            return ($site=='m') ? true : false;
        }

        if ( isset($_SERVER['HTTP_X_WAP_PROFILE']) ) {
            return true;
        }

        if ( isset($_SERVER['HTTP_VIA']) 
            && strpos($_SERVER['HTTP_VIA'], 'wap') !== false ) {
            return true;
        }

        //via the http request user agent
        $uAgent    = $this->input->server('HTTP_USER_AGENT');
        if ( $uAgent == NULL ) {
            return false;
        }

        $uAgent     = strtolower($uAgent);
        $mobileOS   = array(
            'phone', 'mobile', 'tablet', 'android', 'iphone', 'blackberry', 'symbian', 'nokia', 'palmos', 'j2me'
        );
        foreach ( $mobileOS as $os ) {
            if ( strpos($uAgent, $os) !== false ) {
                return true;
            }
        }

        return false;
    } 
}
?>
