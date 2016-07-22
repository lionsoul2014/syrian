<?php
/**
 * Authorize checking class of light app, current base on session
 *
 * @author chenxin <chenxin619315@gmail.com>
*/

 //---------------------------------------------------------
 
class Auth
{
    /*
     * The only instance to this class
     *
     * @access  private
    */
    private static $_instance = NULL;
    
    private $_mapping = array(
        'UID',                  //user id
        'UAGENT'                //user agent
    );

    //session instance
    private $_SESS    = null;
    
    /**
     * get the only instance of UserAuth
     *
     * @param    $_conf
     * @return  $_instance
    */
    public static function create($_key, $_conf)
    {
        if ( self::$_instance == NULL )
            self::$_instance = new self($_key, $_conf);
            
        return self::$_instance;
    }
    
    /**
     * method to initialize the class
     *  start the session here
    */
    public function __construct($_key, &$_conf)
    {
        //load and create the user file session
        //we set the expire time to 3y, yat, that's crazy
        Loader::import('SessionFactory', 'session');
        $this->_SESS = SessionFactory::create($_key, $_conf);

        //specifial setting
        if ( isset($_conf['R8C']) )     $this->_SESS->setR8C($_conf['R8C']);
        if ( isset($_conf['sessid']) )     $this->_SESS->setSessionId($_conf['sessid']);
        $this->_SESS->start();
    }
      
    /**
     * register the authorize item
     *
     * @param   $_cfg
    */
    public function register( $_cfg )
    {
        //set the UID
        if ( isset($_cfg['UID']) )        $this->_SESS->set('UID', $_cfg['UID']);
        
        //set the user agent
        if ( isset($_cfg['UAGENT']) )    $this->_SESS->set('UAGENT', $_cfg['UAGENT']);

        //self register the R8C
        $_R8C    = $this->_SESS->getR8C();
        if ( $_R8C != NULL )            $this->_SESS->set('R8C', $_R8C);
            
        return $this;
    }
    
    /**
     * check the current request is authorized or not
     *
     * @return  bool
    */
    public function authorize($uAgent)
    {
        //check the setting of all the item
        foreach ( $this->_mapping as $_key )
        {
            if ( ! $this->_SESS->has($_key) ) return false;
        }
        
        //make sure the user agent is still the same
        if ( strcmp($this->_SESS->get('UAGENT'), $uAgent) != 0 )
            return false;

        //compare the random 8 chars when it exists
        //when R8C was find in the session but match nothing in getR8C
        //    absolutely is it not a valid request.
        $R8C = $this->_SESS->get('R8C');
        if ( $R8C != NULL && strcmp($R8C, $this->_SESS->getR8C()) != 0 )
            return false;
        
        return true;
    }

    /**
     * destroy or logout the current session
    */
    public function destroy()
    {
        $this->_SESS->destroy();
    }
    
    /**
     * get value mapping the specifiled key
     *
     * @param   $_key
     * @return  String
    */
    public function get( $_key )
    {
        if ( ! $this->_SESS->has($_key) ) return false;
        return $this->_SESS->get($_key);
    }
    
    /**
     * set the value mapping with the specifiled key
     *
     * @param   $_key
     * @param   $_val
     * @return  bool
    */
    public function set( $_key, $_val )
    {
        $this->_SESS->set($_key, $_val);
        return $this;
    }
}
?>
