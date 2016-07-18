<?php
/**
 * Application Controller common Class.
 * And this is the super class of all module controller class.
 *
 * @author  chenxin <chenxin619315@gmail.com>
 * @link    http://www.lionsoul.org/syrian
 */

 //-----------------------------------------------------------------
 
class Controller
{
    public      $uri    = NULL;        //request uri
    public      $input  = NULL;        //request input
    public      $output = NULL;        //request output
    public      $_G     = NULL;        //global resource

    protected   $jsonView   = NULL;
    protected   $dataType   = NULL;
    protected   $view       = NULL;
    
    /**
     * Construct method to create new instance of the controller
     *
     * @param    $uri
     * @param    $input
     * @param    $output
    */
    public function __construct()
    {
        $this->_G = new stdClass();
        $this->_G->flush_mode = false;
        $this->_G->ignore_mode = false;
        $this->appconf = config('app');
    }
    
    /**
     * the entrance of the current controller
     * default to invoke the uri->page.logic.php to handler
     *     the request, you may need to rewrite this method to self define
     *
     * @access    public
    */
    public function run()
    {
        //@Added at 2015-05-29
        //define the flush mode global sign
        //@Assoc the algorithm assocatied with the cache flush
        //    define in the helper/CacheFlusher#Refresh
        $flushMode = $this->input->getInt('__flush_mode__', 0);
        if ( $flushMode == 1 ) {
            $flushKey = $this->input->get('__flush_key__');
            if ( strcmp($flushKey, $this->sysconf->flush_key) == 0 ) {
                $this->_G->flush_mode = true;
            }
        }

        //@Added at 2015-07-21
        // for cache flush need to ignore the balance redirecting...
        $ignoreMode = $this->input->getInt('__ignore_mode__', 0);
        if ( $ignoreMode == 1 ) {
            $this->_G->ignore_mode = true;
        }

        //get the response dataType and register the view
        $this->dataType = $this->input->get('dataType');
        if ( $this->dataType != 'json' ) {
            $this->view = $this->getView('html', 0);
        }
    }

    /**
     * internal method to get the common view
     *
     * @param   $_key
     * @return  Bool
    */
    protected function getView( $type = 'html', $_timer = 0 )
    {
        import('view.ViewFactory');
        
        $conf = NULL;
        if ( strtolower($type) == 'html' ) {
            $conf = array(
                'cache_time'    => $_timer,
                'tpl_dir'       => SR_VIEWPATH,
                'cache_dir'     => SR_CACHEPATH.'tpl/'.$this->uri->module.'/'
            );
        }
        
        //return the html view
        return ViewFactory::create($type, $conf);
    }

}
?>
