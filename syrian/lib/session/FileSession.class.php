<?php
/**
 * user level session handler class and base on file
 *
 * @author chenxin <chenxin619315@gmail.com>
*/

 //----------------------------------------------------------

class FileSession implements ISession
{
	private $_partitions	= 1000;
	private $_save_path		= NULL;
    private $_ttl 			= 0;

	//valud of session_id's hash value
	private $_hval			= -1;
    
	/**
	 * construct method to initialize the class
	 *
	 * @param	$conf
	 */
    public function __construct( &$conf )
	{
		if ( isset( $conf['save_path'] ) ) 
			$this->_save_path = $conf['save_path'];
		if ( isset( $conf['ttl'] ) )
			$this->_ttl	= $conf['ttl'];
		if ( isset( $conf['partitions'] ) )
			$this->_partitions = $conf['partitions'];

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

		//set the session id cookies lifetime
		//and make the cookies last longer than the session, so got a change
		//to clear the session file itself
		session_set_cookie_params($this->_ttl + $_more, '/');

		//start the session
        session_start();
    }
    
	/**
	 * It is the first callback function executed when the session
	 *		is started automatically or manually with session_start().
	 * Return value is TRUE for success, FALSE for failure.
	 */
    function open( $_save_path, $_sessname )
	{
		//use the default _save_path without user define save_path
		if ( $this->_save_path == NULL ) $this->_save_path = $_save_path;
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
		//take the _hval as the partitions number
		if ( $this->_hval == -1 )
		{
			$this->_hval = self::bkdrHash($_sessid, $this->_partitions);
		}

		//make the final session file
		$_file = "{$this->_save_path}/{$this->_hval}/{$_sessid}.ses";

		//check the existence and the lifetime
		if ( ! file_exists($_file) ) return '';

		//atime update maybe closed by filesystem
		$ctime = max(filemtime($_file), fileatime($_file));
		if ( $ctime + $this->_ttl < time() )
		{
			@unlink($_file);
			return '';
		}

		//get and return the content of the session file
		$_txt = file_get_contents($_file);
		return ($_txt == FALSE ? '' : $_txt);
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

		//take the _hval as the partitions number
		if ( $this->_hval == -1 )
		{
			$this->_hval = self::bkdrHash($_sessid, $this->partitions);
		}

		//make the final session file
		$_baseDir = "{$this->_save_path}/{$this->_hval}";
		if ( ! file_exists($_baseDir) )	@mkdir($_baseDir, 0777);

		//write the data to the final session file
		if ( @file_put_contents("{$_baseDir}/{$_sessid}.ses", $_data) != FALSE )
		{
			//chmod the newly created file
			@chmod("{$_baseDir}/{$_sessid}.ses", 0755);
			return TRUE;
		}

		return FALSE;
    }
    
	/**
	 * This callback is executed when a session is destroyed with session_destroy().
	 * Return value should be TRUE for success, FALSE for failure.
	*/
    function destroy( $_sessid )
	{
		//delete the session data
		$_file = "{$this->_save_path}/{$this->_hval}/{$_sessid}.ses";
		if ( file_exists($_file) ) @unlink($_file);

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

	//------------------------------------------------------------
	//bkdr hash function
	private static function bkdrHash( $_str, $_size )
	{
		$_hash	= 0;
		$len 	= strlen($_str);
	
		for ( $i = 0; $i < $len; $i++ ) 
		{
			$_hash = ( int ) ( $_hash * 1331 + ( ord($_str[$i]) % 127 ) );
		}
		
		if ( $_hash < 0 ) 			$_hash *= -1;
		if ( $_hash >= $_size ) 	$_hash = ( int ) $_hash % $_size; 
		
		return ( $_hash & 0x7FFFFFFF );
	}
}
?>
