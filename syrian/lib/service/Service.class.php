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
     * the entrance of the current Service.
     * default to invoke the $this->{$method}() to handler
     * the request, you may need to rewrite this method to define the handler youself
     *
     * @param   $handler
     * @param   $args
     * @access  public
    */
    public function run($handler, $args=null)
    {
        if ( method_exists($this, $handler) == false ) {
            throw new Exception("{$handler} not found at class " . __CLASS__);
        }

        if ( $args != null && is_array($args) == false) {
            throw new Exception('Arguments must be an array');
        }

        //invoke the handler
        //return $this->{$handler}(new ServiceInputBean($args));

        $input = new ServiceInputBean($args);
        $ret   = $this->{$handler}($input);
        unset($input);

        return $ret;
    }

    /**
     * gc method
     * do the resource clean up for the current service
     * will be invoked after each invoke of the service
    */
    public function gc()
    {
        /*
         * check and release all the db connections resource
        */
        if ( class_exists('DbFactory') ) {
            DbFactory::releaseAll();
        }

        //@TODO: check and release all the memcached connection resource
    }

}

/**
 * service input class
 *
 * @author  chenxin<chenxin619315@gmail.com>
*/
class ServiceInputBean
{
    /**
     * input source
     *
     * @access  private
    */
    private $args = null;

    /**
     * construct method
     *
     * @param   $args
    */
    public function __construct($args)
    {
        $this->args = $args==null ? array() : $args;
    }

    /**
     * get the value with the specifield key
     *
     * @param   $key
     * @param   $default
     * @return  Mixed
    */
    public function get($key, $default=null)
    {
        return isset($this->args[$key]) ? $this->args[$key] : $default;
    }

    /**
     * set the value mapping with the specifield key
     *
     * @param   $key
     * @param   $val
     * @return  Boolean
    */
    public function set($key, $val, $override=true)
    {
        if ( $override || ! isset($this->args[$key]) ) {
            $this->args[$key] = $val;
            return true;
        }

        return false;
    }

    public function __toString()
    {
        if ( $this->args == null ) {
            return null;
        }

        $str = array();
        foreach ( $this->args as $k => $v ) {
            $str[] = "{$k}={$v}";
        }

        return implode(',', $str);
    }

    public function __destruct()
    {
        if ( $this->args != null ) {
            unset($this->args);
        }
    }

}

?>
