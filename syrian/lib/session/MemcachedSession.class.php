<?php
/**
 * user level session handler class and base on memcached
 *	
 *	R8C security was append after the session_id
 *		and take the '__' as the dilimiter
 *
 * @author dongyado<dongydao@gmail.com>
*/

 //----------------------------------------------------------
//require('SessionFactory.class.php');

class MemcachedSession implements ISession
{
    private $_ttl 			= 0;
	private	$_prefix		= '';
	private	$_sessid		= NULL;
	private $_R8C			= NULL;
    private $_session_name  = NULL;
    private $_mem            = NULL;
    private $_hash          = Memcached::HASH_DEFAULT;
    public static $_hash_opts     = array(
        'default'   => Memcached::HASH_DEFAULT,
        'md5'       => Memcached::HASH_MD5,
        'crc'       => Memcached::HASH_CRC,
        'fnv1_64'   => Memcached::HASH_FNV1_64,
        'fnv1a_64'  => Memcached::HASH_FNV1A_64,
        'fnv1_32'   => Memcached::HASH_FNV1_32,
        'fnv1a_32'  => Memcached::HASH_FNV1A_32,
        'hsieh'     => Memcached::HASH_HSIEH,
        'murmur'    => Memcached::HASH_MURMUR
    );


	/**
	 * construct method to initialize the class
     * 
     * demo config data:
     *  $_conf = array(
     *       'servers'       => array(
     *           array('localhost', 11211, 60), // host, port, weight
     *           array('localhost', 11212, 40),
     *       ),
     *       'ttl'           => 60, // time to live
     *       // default: standard,  consistent was recommended,
     *       // for more infomation,  search 'consistent hash'
     *       'hash_strategy' => 'consistent',
     *       'hash'          => 'default', // hash function,  empty for default
     *       'prefix'        => 'ses_'
     *   );
     *  
	 * @param	$conf
	 */
    public function __construct( &$conf )
	{
        if (!isset($conf['servers']) || empty($conf['servers'])){
           throw new Exception('Memcached server should not be empty'); 
        }
            
        $this->_mem  = new Memcached();

        // hash distribute strategy, 
        // default: Memcached::DISTRIBUTION_MODULA
        if (isset($conf['hash_strategy']) 
            && $conf['hash_strategy'] == 'consistent') 
        {
            $this->_mem->setOption(Memcached::OPT_DISTRIBUTION,
                     Memcached::DISTRIBUTION_CONSISTENT); 
            $this->_mem->setOPtion(Memcached::OPT_LIBKETAMA_COMPATIBLE, TRUE);
        }

        if (isset($conf['hash']) 
            && $conf['hash'] != 'default' 
            && array_keys(self::$_hash_opts, $conf['hash']))
        {
            $this->_hash = self::$_hash_opts[$conf['hash']];
            $this->_mem->setOption(Memcached::OPT_HASH, $this->_hash); 
        }


        if (isset($conf['prefix']))
        {
            $this->_prefix = $conf['prefix'];
            $this->_mem->setOption(Memcached::OPT_PREFIX_KEY, $this->_prefix);
        }



        if (empty($this->_mem->getServerList())){
            $this->_mem->addServers($conf['servers']);
        } else {
           //throw new  Exception('Use Old Memcached server'); 
        }


        if (isset($conf['ttl']) &&($ttl = intval($conf['ttl'])) > 0)
        {
            $this->_ttl = $ttl;
        }

        //set use user level session
        session_module_name('user');
        session_set_save_handler(
            array($this, 'open'),
            array($this, 'close'),
            array($this, 'read'),
            array($this, 'write'),
            array($this, 'destroy'),
            array($this, 'gc')
        );

		$_more	= 86400;
		if ( isset( $conf['more_for_cookie'] ) )
		{
			$_more = $conf['more_for_cookie'];
		}

        if (isset($conf['session_name']) && $conf['session_name']){
            $this->_session_name = $conf['session_name']; 
            session_name($this->_session_name);
        }
		//set the session id cookies lifetime
		//and make the cookies last longer than the session, so got a change
		//to clear the session file itself
        if (isset($conf['cookie_domain']) && $conf['cookie_domain'])
            session_set_cookie_params($this->_ttl + $_more, '/', $conf['cookie_domain']);
        else 
            session_set_cookie_params($this->_ttl + $_more, '/');
       
    }

	//start the session
	public function start()
	{
		if ( $this->_sessid != NULL ) 
		{
			if ( $this->_R8C == NULL ) $_sessid = $this->_sessid;
			else $_sessid = $this->_sessid.'---'.$this->_R8C;

			//set the session id
			session_id($_sessid);
		}

		session_start();
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
	 *		is started automatically or manually with session_start().
	 * Return value is TRUE for success, FALSE for failure.
	 */
    function open( $_save_path, $_sessname )
	{
        return TRUE;
    }

	/**
	 * It is also invoked when session_write_close() is called.
	 * Return value should be TRUE for success, FALSE for failure.
	*/
    function close()
	{
        return TRUE;
    }
    
	/**
	 * The read callback must always return a session encoded (serialized) string,
	 * 		or an empty string if there is no data to read.
	 * This callback is called internally by PHP when the session starts or when session_start()
	 * 		is called. Before this callback is invoked PHP will invoke the open callback.
	*/
    function read( $_sessid )
	{

		//check if the R8C is appended
		if ( ($pos = strpos($_sessid, '---')) !== false )
		{
			$this->_R8C = substr($_sessid, $pos+3);
			$_sessid = substr($_sessid, 0, $pos);
		}
		
		//set the global session id when it is null
		if ( $this->_sessid == NULL ) $this->_sessid = $_sessid;

        $ret = $this->_mem->get($_sessid);
        return $ret == FALSE ? '' : $ret;
    }
    
	/**
	 * The write callback is called when the session needs to be saved and closed.
	 * 	The serialized session data passed to this callback should be stored against
	 * 		the passed session ID. When retrieving this data, the read callback must
	 * 		return the exact value that was originally passed to the write callback.
	*/
    function write( $_sessid, $_data )
	{
		if ( $_data == NULL || $_data == '' ) return FALSE;
		$_sessid = $this->_sessid;

        $ret = $this->_mem->set($_sessid, $_data, $this->_ttl);
        return $ret;
    }
    
	/**
	 * This callback is executed when a session is destroyed with session_destroy().
	 * Return value should be TRUE for success, FALSE for failure.
	*/
    function destroy( $_sessid )
	{
        $this->_mem->delete($_sessid);

		//delete the PHPSESSId cookies
		$sessname = session_name();
		if ( isset($_COOKIE[$sessname]) ) 
		{
			setcookie($sessname, '', time() - 42000, '/');
		}

        return TRUE;
    }
    
	/**
	 * The garbage collector callback is invoked internally by PHP periodically
	 * 		in order to purge old session data.
	 * The frequency is controlled by session.gc_probability and session.gc_divisor. 
	*/
    function gc( $_lifetime )
	{
        return TRUE;
    }

	//check the specifield mapping is exists or not
	public function has( $key )
	{
		return isset($_SESSION[$key]);
	}

	//get the value mapping with the specifield key
	public function get( $key )
	{
		if ( ! isset($_SESSION[$key]) ) return NULL;
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
	//@param	$r8c
	public function setR8C( $r8c )
	{
		$this->_R8C	= $r8c;
		return $this;
	}
}
?>
