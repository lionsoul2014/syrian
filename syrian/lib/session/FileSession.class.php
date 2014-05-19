<?php
/**
 * user level session handler class and base on file
 *
 * @author chenxin <chenxin619315@gmail.com>
*/

 //----------------------------------------------------------

class FileSession
{
	private $_partitions	= 1000;
	private $_save_path		= NULL;
    private $_ttl 			= 0;
    
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
		$_file = $this->_save_path.'/optsess_'.$_sessid;
		if ( ! file_exists($_file) ) return '';
		$_txt = file_get_contents($_file);
		return ($_txt == FALSE ? '' : $_txt);
    }
    
	/**
	 * The write callback is called when the session needs to be saved and closed.
	 * 	The serialized session data passed to this callback should be stored against
	 * 		the passed session ID. When retrieving this data, the read callback must
	 * 		return the exact value that was originally passed to the write callback.
	 * The serialized session data passed to this callback should be stored against
	 * 		the passed session ID. When retrieving this data, the read callback must return
	 *		the exact value that was originally passed to the write callback.
	*/
    function write( $_sessid, $_data )
	{
		$_file = $this->_save_path.'/optsess_'.$_sessid;
		if ( file_put_contents($_file, $_data) != FALSE )
			return TRUE;
		return FALSE;
    }
    
	/**
	 * This callback is executed when a session is destroyed with session_destroy().
	 * Return value should be TRUE for success, FALSE for failure.
	*/
    function destroy( $_sessid )
	{
		$_file = $this->_save_path.'/optsess_'.$_sessid;
		@unlink($_file);
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

	//------------------------------------------------------------
	private function bkdr()
	{
	}
}
?>
