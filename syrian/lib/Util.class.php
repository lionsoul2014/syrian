<?php
/**
 * Common Util function class
 * 	Offer some useful functions
 * 
 * @author chenxin <chenxin619315@gmail.com>
*/
 
 //--------------------------------------------------------
 
class Util
{
	public static function setText( $filename, $text ) 
	{
		if ( ($handle = fopen($filename, 'wb') ) != FALSE ) 
		{
			if ( flock($handle, LOCK_EX) ) 
			{
				if ( ! fwrite($handle, $text) ) 
				{
					flock($handle, LOCK_UN);
					fclose($handle);
					return FALSE;
				}
			}
			
			flock($handle, LOCK_UN);
			fclose($handle);
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * create the given path
	 *		like the unix command 'mkdir -p'
	 *
	 * @param 	$filename
	*/	
	public static function makePath( $filename ) 
	{
		 $dirArray = array();
		 $baseDir = '';
		 
		 while ($filename != '.' && $filename != '..') 
		 {
			 if ( file_exists($filename) ) 
			 {
				 $baseDir = $filename;
				 break;	 
			 }
			 
			 $dirArray[] 	= basename($filename);   //basename part
			 $filename 		= dirname($filename); 
		 }
		 
		 for ( $i = count($dirArray) - 1; $i >= 0; $i--) 
		 {
			 if ( strpos($dirArray[$i], '.') !== FALSE ) 
			 {
				 break;
			 }
			 
			 @mkdir( $baseDir . '/' . $dirArray[$i] );
			 $baseDir = $baseDir . '/' .$dirArray[$i];
		 }
	 }
		 
	public static function utf8_substr($str, $limit) 
	{ 
		if ( strlen($str) <= $limit ) return $str;
		
		$substr = ''; 
		for( $i=0; $i< $limit-3; $i++) 
		{ 
			$substr .= ord($str[$i])>127 ? $str[$i].$str[++$i].$str[++$i] : $str[$i]; 
		} 
		
		return $substr; 
	}


	/**
	 * common method to get the access internet address
	 *		of the access client
	 *
	 * @param 	$convert
	 * @return 	mixed(int or string)
	*/
	public static function getIpAddress( $convert = false ) 
	{
		$ip = ''; 
		if (getenv('HTTP_CLIENT_IP')) 				$ip = getenv('HTTP_CLIENT_IP'); 
		//获取客户端用代理服务器访问时的真实ip 地址
		else if (getenv('HTTP_X_FORWARDED_FOR')) 	$ip = getenv('HTTP_X_FORWARDED_FOR');
		else if (getenv('HTTP_X_FORWARDED')) 		$ip = getenv('HTTP_X_FORWARDED');
		else if (getenv('HTTP_FORWARDED_FOR')) 		$ip = getenv('HTTP_FORWARDED_FOR'); 
		else if (getenv('HTTP_FORWARDED')) 			$ip = getenv('HTTP_FORWARDED');
		else  										$ip = $_SERVER['REMOTE_ADDR'];

		if ( $convert ) 	$ip = sprintf("%u", ip2long($ip));

		return $ip;
	}
}
?>