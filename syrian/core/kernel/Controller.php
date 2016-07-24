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
     * method prefix and it default to '_'
    */
    protected $method_prefix = 'action';

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
     * basic initialize method, if the class extends this
     *  need to rewrite the run method(self-define the router) invoke this to
     * do the initialize work
     *
     * @param   $input
     * @param   $output
     * @param   $uri
    */
    protected function __init($input, $output, $uri)
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
    }
    
    /**
     * the entrance of the current controller
     * default to invoke the uri->page.logic.php to handler
     *  the request, you may need to rewrite this method to self define
     *
     * @param   $input
     * @param   $output
     * @param   $uri (standart parse_uri result)
     * @access  public
    */
    public function run($input, $output, $uri)
    {
        $this->__init($input, $output, $uri);

        $ret = NULL;

        /*
         * check and invoke the before method
         * basically you could do some initialize work here
        */
        if ( method_exists($this, '__before') ) {
            $this->__before($input, $output, $uri);
        }

        /*
         * check and invoke the main function to handler the current request
        */
        $method = "{$this->method_prefix}{$uri->page}";
        if ( method_exists($this, $method) ) {
            $ret = $this->{$method}($input, $output, $uri);
        } else {
            throw new Exception("Undefined handler \"{$method}\" for " . __class__);
        }

        /*
         * check and invoke the after method here
         * basically you could do some destroy work here
        */
        if ( method_exists($this, '__after') ) {
            $this->__after($input, $output, $uri);
        }

        return $ret;
    }

}
?>
