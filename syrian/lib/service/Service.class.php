<?php
/**
 * Syrian Application Service super Class.
 * So, what is a service?
 *
 * Defination:
 * Service is a controller actually, but it is lighter
 * the logic it will processed could be shared by main logic container
 * or it could be executed through a distributed way.
 *
 * @author  chenxin <chenxin619315@gmail.com>
 * @link    http://www.lionsoul.org/syrian
 */

 //-----------------------------------------------------------------
 
class Service
{
    /**
     * Construct method to create new instance of the controller
    */
    public function __construct()
    {
    }

    /**
     * the entrance of the current Service.
     * default to invoke the $this->{$method}() to handler
     * the request, you may need to rewrite this method to define the handler youself
     *
     * @param   $instance
     * @param   $args
     * @access  public
    */
    public function run($handler, $args=NULL)
    {
        if ( method_exists($this, $handler) == false ) {
            throw new Exception("{$handler} not found at class " . __CLASS__);
        }

        if ( $args != NULL && is_array($args) == false) {
            throw new Exception('Arguments must be an array');
        }

        //invoke the handler
        return $this->{$handler}($args);
    }

}
?>
