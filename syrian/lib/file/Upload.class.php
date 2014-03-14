<?php
/**
 * class for file upload
 * 
 * @anthor	chenxin<chenxin619315@gmail.com>
 */

 //------------------------------------------------------
 
class Upload
{
	
	private	$_upl_dir;
	private $_refu_ext;
	private $_acce_ext;
	
	private $_errno = 0;
	private $_serial;         
	public static $_letters = '0123456789abcdefghijklmnopqrstuvwxyz_ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	private static $_length = 63;
	private $_ext;
	
	/**
	 * the construct method . <br />
	 * 
	 * @param $_upl_dir
	 * @param	$_size
	*/
	function __construct( $_upl_dir = '../upload', $_size = 8388608 )
	{
		$this->_upl_dir = $_upl_dir;
		if( is_numeric($_size) ) @ini_set('upload_max_filesize', intval($_size));
		$this->_serial = mt_rand() % self::$_length;
		$this->check_upload_dir();
	}
	
	/**
	set the refuse file extension
	
	@param $_refu_ext;
	*/
	public function set_refuse_type( $_refu_ext )
	{
		$this->_refu_ext = $_refu_ext;
	}
	
	/**
	 * set the eccept file extension
	 * 
	 * @param $_acce_ext;
	 */
	public function set_accept_type( $_acce_ext )
	{
		$this->_acce_ext = $_acce_ext;
	}
	
	/**
	 * handling the files from the speicified file input . <br />
	 * 
	 * @param $name	name for file input
	 * @param $overwrite
	 */
	public function upload( $name, $_prefix = '', $_over = TRUE )
	{
		$_error = $_FILES[''.$name.'']['error'];
		$_local = $_FILES[''.$name.'']['name'];
		$_temp  = $_FILES[''.$name.'']['tmp_name'];
		//$size = $_FILES[''.$name.'']['size'];
		//$type = $_FILES[''.$name.'']['type'];
		$_files = array();
		$_size = count( $_local );
		
		for( $i = 0; $i < $_size; $i++ )
		{
			//get the code number
			$this->_errno = $_error[$i];	
			if ( $this->_errno != 0 ) return $_files;
			$this->isLegal( $_local[$i] );
			if ( $this->_errno != 0 ) return $_files;

			if(  $_over && is_uploaded_file($_temp[$i]) ) {
			$_file = $this->generate( $_prefix );
			if( move_uploaded_file($_temp[$i], $this->_upl_dir.'/'.$_file) ) $_files[] = $_file;
			}
		}
		
		return $_files;
	}
	
	/**
	 * rename the new upload file . <br />
	 *
	 * @param  $_prefix
	 * @return $_name
	 */
	private function generate( $_prefix = '' )
	{
		$_ret = self::$_letters[$this->_serial++];
		if ( $this->num > self::$_length - 1 ) $this->num = 0;
		if ( $_prefix != '' ) $_ret = $_prefix . $_ret;
		return md5( uniqid($_ret, true) ) . '.' . $this->_ext;
	}
	
	/**
	 * check the file extension . <br />
	 * 
	 * @param $_name
	 */
	private function isLegal( $_name )
	{
		$_arr = explode('.', $_name);
		$_size = count( $_arr );
		if ( $_size < 2 ) $this->_errno = -3;			//ilegal file extension
		else
		{
			$_ext = $_arr[$_size - 1];
			//refused file extension
			if( stripos( $this->_refu_ext, $_ext ) !== FALSE ) $this->_errno = -1;
			else if ( stripos( $this->_acce_ext, $_ext ) !== FALSE ) $this->_ext = $_ext;
			else $this->_errno = -2;
		}	
	}
	
	/**
	 * get the error number <br />
	 * 
	 * @return bool
	 */
	public function getErrorCode()
	{
		return $this->_errno;
	}
	
	/**
	 * check the existence of the upload direcotry, 
	 * 			if it is not exists create it. <br />
	 */
	private function check_upload_dir()
	{
		if( ! file_exists( $this->_upl_dir ) ) @mkdir( $this->_upl_dir );
	}
}
?>