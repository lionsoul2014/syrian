<?php
/**
 * File upload manage class, offer interface to:
 *
 * 1. security checking
 * 2. self rename the uploaded file
 * 
 * @anthor	chenxin<chenxin619315@gmail.com>
 */

 //------------------------------------------------------
 
class Upload
{
	
	private			$_upl_dir;
	private 		$_refu_ext;
	private 		$_acce_ext;
	
	private 		$_errno 	= 0;
	public static 	$_letters 	= '0123456789';
	private static 	$_length 	= 10;
	private 		$_ext;
	
	/**
	 * the construct method
	 * 
	 * @param 	$_upl_dir	end with '/'
	 * @param	$_size
	*/
	public function __construct( $_upl_dir, $_size = 8388608 )
	{
		if ( $_upl_dir[strlen($_upl_dir)-1] != '/' ) $_upl_dir = $_upl_dir.'/';
		$this->_upl_dir = $_upl_dir;

		//set the upload max filesize
		if( is_numeric($_size) ) @ini_set('upload_max_filesize', intval($_size));
	}
	
	/**
	 * set the refuse file extension
	 *
	 * @param $_refu_ext
	*/
	public function set_refuse_type( $_refu_ext )
	{
		$this->_refu_ext = &$_refu_ext;
	}
	
	/**
	 * set the eccept file extension
	 * 
	 * @param $_acce_ext
	 */
	public function set_accept_type( $_acce_ext )
	{
		$this->_acce_ext = &$_acce_ext;
	}
	
	/**
	 * handling the files from the speicified file input
	 * 
	 * @param 	$input		name for file input
	 * @param 	$overwrite
	 */
	public function upload( $input, $serial = 0, $use_old_name = false )
	{
		$_error = $_FILES[''.$input.'']['error'];
		$_local = $_FILES[''.$input.'']['name'];
		$_temp  = $_FILES[''.$input.'']['tmp_name'];
		//$size = $_FILES[''.$input.'']['size'];
		//$type = $_FILES[''.$input.'']['type'];

		$files = array();
		$_size = count( $_local );

		//---------------------------------------------
		//check the file size and make the upload path
		if ( $_size > 0 )	self::createPath($this->_upl_dir);

        foreach($_local as $key => $val){
            //get the error code number
			$this->_errno = $_error[$key];	
			if ( $this->_errno != 0 ) continue;

			//check wether the file is valid
			$this->isLegal( $_local[$key] );
			if ( $this->_errno != 0 ) continue;

			if(  ! is_uploaded_file($_temp[$key]) ) continue;

            if ($use_old_name) {
                $_file = $_local[$key];
            } else {
                $_file 	= $this->createName($serial);
            }

			$opt 	= move_uploaded_file($_temp[$key], $this->_upl_dir.$_file);

			if( $opt == true ) 
			{
				$files[$key] = $_file;
			}
        }
		
        /*
		for( $i = 0; $i < $_size; $i++ )
		{
			//get the error code number
			$this->_errno = $_error[$i];	
			if ( $this->_errno != 0 ) return false;

			//check wether the file is valid
			$this->isLegal( $_local[$i] );
			if ( $this->_errno != 0 ) return false;

			if(  ! is_uploaded_file($_temp[$i]) ) continue;

            if ($use_old_name) {
                $_file = $_local[$i];
            } else {
                $_file 	= $this->createName($serial);
            }
			$opt 	= move_uploaded_file($_temp[$i], $this->_upl_dir.$_file);
			if( $opt == true ) 
			{
				$files[] = $_file;
			}
		}
       
         */
		return ( empty($files) ? false : $files );
	}
	
	/**
	 * create 16bytes unique name for the  the new upload file
	 *
	 * @param 	$serial
	 * @return 	string
	 */
	private function createName( $serial = 0 )
	{
		$seed 	= self::$_letters[mt_rand()%self::$_length];
		if ( $serial == 0 )
		{
			$parts 	= explode(' ', microtime());
			$seed 	.= $parts[1].substr($parts[0], 2, 2);
		}
		else
		{
			$seedstr = time().'000';
			$serial  = "$serial";
			$seed 	.= $serial;

			$space 	 = 12 - strlen($serial);
			$seed   .= substr($seedstr, 0, $space);
		}

		return $seed.".{$this->_ext}";
	}
	
	/**
	 * check the file extension
	 * 
	 * @param 	$_name
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
	 * get the error number
	 * 
	 * @return 	bool
	 */
	public function getErrorCode()
	{
		return $this->_errno;
	}
	
	/**
	 * check the existence of the upload direcotry, 
	 * 		if it is not exists create it
     *
     * @param 	$path
	 */
	public static function createPath( $path )
	{
		$dirArray = array();
		$baseDir = '';

		while ($path != '.' && $path != '..' ) 
		{
			if ( file_exists($path) ) 
			{
				$baseDir = $path;
				break;	 
			}

			$dirArray[]	= basename($path);   //basename part
			$path 		= dirname($path); 
		}

		for ( $i = count($dirArray) - 1; $i >= 0; $i-- )
		{
			if ( strpos($dirArray[$i], '.') !== FALSE ) 
			{
				break;
			}

			@mkdir( $baseDir . '/' . $dirArray[$i] );
			$baseDir = $baseDir . '/' .$dirArray[$i];
		}
	}
}
?>
