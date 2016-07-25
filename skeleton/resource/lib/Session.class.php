<?php
/**
 * Application session manager class
 *
 * @author chenxin <chenxin619315@gmail.com>
*/

import('session.SessionFactory');
 
class Session
{
    private $_SESS  = null;

    /**
     * method to initialize the class and start the session here
     *
     * @param   $key
     * @param   $conf
    */
    public function __construct($key, $conf)
    {
        //create the user level session
        $this->_SESS = SessionFactory::create($key, $conf);
    }

    /**
     * start the current session
     *
     * @param   $sessid user defined session id
     * @param   $gen
     * @return  Object
    */
    public function start($gen, $sessid=null)
    {
        /*
         * check and set the session id and the r8c value
         * @Note: 
         * we only need to do this for the first time to create the session
        */
        if ( $gen == true ) {
            import('StringUtil');
            if ( $sessid == null ) {
                $sessid = StringUtil::genGlobalUid() ;
            }

            $this->_SESS->setSessionId($sessid);
            $this->_SESS->setR8C(StringUtil::randomLetters(8));
        }

        $this->_SESS->start();
        return $this;
    }
      
    /**
     * register the basic item data
     * @Note: invoke the #start before invoke this
     *
     * @param   $uid
    */
    public function register($uid)
    {
        $r8cVal = $this->_SESS->getR8C();
        if ( $r8cVal != NULL ) {
            $this->_SESS->set('r8c', $r8cVal);
        }

        $this->_SESS->set('uid', $uid);
        $this->_SESS->set(
            'uAgent',
            isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Syrian/2.0'
        );

        return $this;
    }
    
    /**
     * Validate the current session
     * and you could get the error info througth passing the errno:
     * 1: means missing value items
     * 2: user agent not match (user has changed the user agent ? )
     * 3: r8c value error
     *
     * @param   $errno
     * @param   $check_ua
     * @return  bool
    */
    public function validate(&$errno=null, $check_ua=false)
    {
        foreach ( array('uid', 'uAgent') as $key ) {
            if ( ! $this->_SESS->has($key) ) {
                $errno = 1;
                return false;
            }
        }

        if ( $check_ua && ( ! isset($_SERVER['HTTP_USER_AGENT']) 
            || strcmp($_SERVER['HTTP_USER_AGENT'],  $this->_SESS->get('uAgent')) != 0 ) ) {
            $errno = 2;
            return false;
        }
        
        /* compare the random 8 chars
         * when R8C was find in the session but match nothing in getR8C
         * definitely is it not a valid request 
         * often mean the second sign in override the previous one
        */
        $r8cVal = $this->_SESS->getR8C();
        if ( $r8cVal != NULL 
            && strcmp($r8cVal, $this->_SESS->get('r8c')) != 0 ) {
            $errno = 3;
            return false;
        }
        
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
     * get the unique id or (user id)
     *
     * @return  string
    */
    public function getUid()
    {
        return $this->_SESS->get('uid');
    }
    
    /**
     * get value mapping the specified key
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
     * set the value mapping with the specified key
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
