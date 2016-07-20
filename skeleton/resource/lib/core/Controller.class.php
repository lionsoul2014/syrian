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
    public  $uri    = NULL;        //request uri
    public  $input  = NULL;        //request input
    public  $output = NULL;        //request output

    /**
     * Construct method to create new instance of the controller
     *
     * @param    $uri
     * @param    $input
     * @param    $output
    */
    public function __construct()
    {
        _G(array(
            SR_FLUSH_MODE  => false,
            SR_IGNORE_MODE => false
        ));

        $this->conf = config('app');
    }
    
    /**
     * the entrance of the current controller
     * default to invoke the uri->page.logic.php to handler
     *  the request, you may need to rewrite this method to self define
     *
     * @access  public
    */
    public function run()
    {
        //@Added at 2015-05-29
        //define the flush mode global sign
        //@Assoc the algorithm assocatied with the cache flush
        // define in the helper/CacheFlusher#Refresh
        $flushMode = $this->input->getInt('__flush_mode__', 0);
        if ( $flushMode == 1 
            && strcmp($this->conf->flush_key, $this->input->get('__flush_key__')) == 0 ) {
            _G(SR_FLUSH_MODE, true);
        }

        //@Added at 2015-07-21
        // for cache flush need to ignore the balance redirecting...
        $ignoreMode = $this->input->getInt('__ignore_mode__', 0);
        if ( $ignoreMode == 1 ) {
            _G(SR_IGNORE_MODE, true);
        }
    }

}
?>
