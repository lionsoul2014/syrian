<?php if ( ! defined('BASEPATH') ) exit('No Direct Access Allowed!');
/**
 * Framework Controller common Class.
 * And this is the super class of all module controller class.
 *
 * @author  chenxin <chenxin619315@gmail.com>
 * @link    http://www.lionsoul.org/syrian
 */

 //-----------------------------------------------------------------

class Controller
{
    /**
     * Construct method to create and initialize the controller
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
     * @param   $uri could be a string or URI parser object
     * @param   $input
     * @param   $output
     * @access  public
    */
    public function run($uri, $input, $output)
    {
        //@Added at 2015-05-29
        //define the flush mode global sign
        //@Assoc the algorithm assocatied with the cache flush
        // define in the helper/CacheFlusher#Refresh
        $flushMode = $input->getInt('__flush_mode__', 0);
        if ( $flushMode == 1 
            && strcmp($this->conf->flush_key, $input->get('__flush_key__')) == 0 ) {
            _G(SR_FLUSH_MODE, true);
        }

        //@Added at 2015-07-21
        // for cache flush need to ignore the balance redirecting...
        $ignoreMode = $input->getInt('__ignore_mode__', 0);
        if ( $ignoreMode == 1 ) {
            _G(SR_IGNORE_MODE, true);
        }

        $ret = NULL;

        /*
         * check and invoke the before method
         * basically you could do some initialize work here
        */
        if ( method_exists($this, '_before') ) {
            $this->_before($input, $output);
        }

        /*
         * invoke the main function to handler the current request
        */
        $method = is_object($uri) ? $uri->page : $uri;
        if ( strlen($method) < 1 ) $method = 'index';
        if ( method_exists($this, $method) ) {
            $ret = $this->{$method}($input, $output);
        } else {
            //throw new Exception("Undefined handler \"{$method}\" for " . __class__);
            abort(404);
        }

        /*
         * check and invoke the after method here
         * basically you could do some destroy work here
        */
        if ( method_exists($this, '_after') ) {
            $this->_after($input, $output);
        }

        return $ret;
    }

}
?>
