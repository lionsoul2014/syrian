<?php
/**
 * user level session handler class and base on file
 *    
 *    R8C security was append after the session_id
 *        and take the '---' as the delimiter
 *
 * @author chenxin <chenxin619315@gmail.com>
*/

 //----------------------------------------------------------

class FileSession implements ISession
{
    private $_partitions    = 1000;
    private $_save_path     = null;
    private $_ttl           = 0;
    private $_ext           = '.ses';
    private $_sessid        = null;
    private $_R8C           = null;
    private $_session_name  = null;

    //valud of session_id's hash value
    private $_hval  = -1;

    /**
     * @added at 2016/08/29
     * cookie expired strategy
     * 1, request: expired time means the intervals bettween the requests
     * 2, global : expired time means the global intervals and once set 
     *  and can not be changed
    */
    private $_expire_strategy = 'request';
    private $_cookie_extra    = 0;
    private $_cookie_domain   = '';
    
    /**
     * construct method to initialize the class
     *
     * @param   $conf
     */
    public function __construct($conf)
    {
        if ( isset($conf['save_path']) ) 
            $this->_save_path = $conf['save_path'];
        if ( isset($conf['ttl']) )
            $this->_ttl = $conf['ttl'];
        if ( isset($conf['partitions']) )
            $this->_partitions = $conf['partitions'];
        if ( isset($conf['file_ext']) )
            $this->_ext = $conf['file_ext'];
        if ( isset($conf['expire_strategy']) )
            $this->_expire_strategy = $conf['expire_strategy'];
        if ( isset($conf['cookie_extra']) ) {
            $this->_cookie_extra = $conf['cookie_extra'];
        }

        //set use user level session
        session_module_name('user');
        session_set_save_handler(
            array($this, '_open'),
            array($this, '_close'),
            array($this, '_read'),
            array($this, '_write'),
            array($this, '_destroy'),
            array($this, '_gc')
        );

        if ( isset($conf['session_name']) ) {
            session_name($conf['session_name']);
        }

        if ( isset($conf['cookie_domain']) ) {
            $this->_cookie_domain = $conf['cookie_domain'];
        } else if ( isset($conf['domain_strategy']) ) {
            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
            switch ( $conf['domain_strategy'] ) {
            case 'cur_host': $this->cookie_domain = $host; break;
            case 'all_sub_host':
                $pnum = 0;
                $hostLen = min(strlen($host), 255);
                for ( $i = 0; $i < $hostLen; $i++ ) {
                    if ( $host[$i] == '.' ) $pnum++;
                }
                
                //define the sub host ($pnum could be 0 like localhost)
                if ( $pnum == 0 ) $this->_cookie_domain = $host;
                else if ( $pnum == 1 ) $this->_cookie_domain = ".{$host}";
                else $this->_cookie_domain = substr($host, strpos($host, '.'));
                break;
            }
        }

        //set the session id cookies lifetime
        if ( $this->_expire_strategy == 'global' ) {
            session_set_cookie_params(
                $this->_ttl + $this->cookie_extra, '/', $this->_cookie_domain
            );
        }
    }

    //start the session
    public function start()
    {
        if ( $this->_sessid != null ) {
            if ( $this->_R8C == null ) $_sessid = $this->_sessid;
            else $_sessid = "{$this->_sessid}---{$this->_R8C}";

            //set the session id
            session_id($_sessid);
        }

        session_start();

        /*
         * check the expire_strategy and extend the 
         * cookies life time as needed
        */
        if ( $this->_expire_strategy == 'request' ) {
            $r8cVal = $this->_R8C;
            setcookie(
                session_name(),
                $r8cVal == null ? $this->_sessid : "{$this->_sessid}---{$r8cVal}", 
                time() + $this->_ttl + $this->_cookie_extra,
                '/',
                $this->_cookie_domain,
                false,
                true
            );
        }
    }

    /**
     * destroy the current session
    */
    public function destroy()
    {
        //1. clear the session data
        //$_SESSION = array();
        session_unset();    

        //2. destroy the session file or stored data
        if ( $this->_sessid != null ) {
            $this->_destroy($this->_sessid);
        }
        
        //3. destroy the session
        session_destroy();
    }

    //get the current session id
    //invoke it after the invoke the start method
    public function getSessionId()
    {
        return $this->_sessid;
    }

    //set the current session id
    //only a-z,0-9,A-Z is valid chars for the new session id
    //invoke it before the invoke of method start
    public function setSessionId( $_sessid )
    {
        //set the session id
        $this->_sessid = $_sessid;
        return $this;
    }
    
    /**
     * It is the first callback function executed when the session
     *        is started automatically or manually with session_start().
     * Return value is true for success, false for failure.
     */
    function _open( $_save_path, $_sessname )
    {
        //use the default _save_path without user define save_path
        if ( $this->_save_path == null ) $this->_save_path = $_save_path;
        return true;
    }

    /**
     * It is also invoked when session_write_close() is called.
     * Return value should be true for success, false for failure.
    */
    function _close()
    {
        return true;
    }
    
    /**
     * The read callback must always return a session encoded (serialized) string,
     *  or an empty string if there is no data to read.
     * This callback is called internally by PHP when the session starts or when session_start()
     *  is called. Before this callback is invoked PHP will invoke the open callback.
    */
    function _read( $_sessid )
    {
        //check if the R8C is appended
        if ( ($pos = strpos($_sessid, '---')) !== false ) {
            $this->_R8C = substr($_sessid, $pos+3);
            $_sessid = substr($_sessid, 0, $pos);
        }
        
        //set the global session id when it is null
        if ( $this->_sessid == null ) $this->_sessid = $_sessid;

        //take the _hval as the partitions number
        if ( $this->_hval == -1 ) {
            $this->_hval = self::bkdrHash($_sessid, $this->_partitions);
        }

        //make the final session file
        $_file = "{$this->_save_path}/{$this->_hval}/{$_sessid}{$this->_ext}";

        //check the existence and the lifetime
        if ( ! file_exists($_file) ) return '';

        //@Note: atime update maybe closed by filesystem
        $ctime = max(filemtime($_file), fileatime($_file));
        if ( $ctime + $this->_ttl < time() ) {
            @unlink($_file);
            return '';
        }

        //get and return the content of the session file
        $_txt = file_get_contents($_file);
        return ($_txt == false ? '' : $_txt);
    }
    
    /**
     * The write callback is called when the session needs to be saved and closed.
     * The serialized session data passed to this callback should be stored against
     *  the passed session ID. When retrieving this data, the read callback must
     *  return the exact value that was originally passed to the write callback.
    */
    function _write( $_sessid, $_data )
    {
        $_sessid = $this->_sessid;

        //take the _hval as the partitions number
        if ( $this->_hval == -1 ) {
            $this->_hval = self::bkdrHash($_sessid, $this->partitions);
        }

        //make the final session file
        $_baseDir = "{$this->_save_path}/{$this->_hval}";
        if ( ! file_exists($_baseDir) ) @mkdir($_baseDir, 0777);

        /*
         * @added at 2016/08/09
         * when the data is empty like the invoke of session close
         * we choose to clear the session storage file rather than
         * write an empty string into it.
         * Also, we we should return true for this or u will receive a
         * warning from php session module say that: "Fail to write session data ..."
        */
        $_sfile = "{$_baseDir}/{$_sessid}{$this->_ext}";
        if ( strlen($_data) < 1 ) {
            if ( file_exists($_sfile) ) @unlink($_sfile);
            return true;
        }

        //write the data to the final session file
        if ( @file_put_contents($_sfile, $_data) !== false ) {
            //chmod the newly created file
            @chmod($_sfile, 0755);
            return true;
        }

        return false;
    }
    
    /**
     * This callback is executed when a session is destroyed with session_destroy().
     * Return value should be true for success, false for failure.
    */
    function _destroy( $_sessid )
    {
        //delete the session data
        $_file = "{$this->_save_path}/{$this->_hval}/{$this->_sessid}{$this->_ext}";
        if ( file_exists($_file) ) @unlink($_file);

        //delete the PHPSESSId cookies
        $sessname = session_name();
        if ( isset($_COOKIE[$sessname]) ) {
            setcookie($sessname, '', time() - 42000, '/');
        }

        return true;
    }
    
    /**
     * The garbage collector callback is invoked internally by PHP periodically
     *  in order to purge old session data.
     * The frequency is controlled by session.gc_probability and session.gc_divisor. 
    */
    function _gc( $_lifetime )
    {
        return true;
    }

    //check the specifield mapping is exists or not
    public function has( $key )
    {
        return isset($_SESSION[$key]);
    }

    //get the value mapping with the specifield key
    public function get( $key )
    {
        if ( ! isset($_SESSION[$key]) ) return null;
        return $_SESSION[$key];
    }

    //set the value mapping with the specifield key
    public function set( $key, $val )
    {
        $_SESSION[$key] = &$val;
        return $this;
    }

    //get the R8C
    public function getR8C()
    {
        return $this->_R8C;
    }

    //set the current R8C invoke it before invoke start
    //@param    $r8c
    public function setR8C( $r8c )
    {
        $this->_R8C = $r8c;
        return $this;
    }

    //------------------------------------------------------------
    //bkdr hash function
    private static function bkdrHash( $_str, $_size )
    {
        $_hash = 0;
        $len   = strlen($_str);
    
        for ( $i = 0; $i < $len; $i++ ) {
            $_hash = (int) ($_hash * 1331 + (ord($_str[$i]) % 127));
        }
        
        if ( $_hash < 0 )       $_hash *= -1;
        if ( $_hash >= $_size ) $_hash = ( int ) $_hash % $_size; 
        
        return ( $_hash & 0x7FFFFFFF );
    }

}
?>
