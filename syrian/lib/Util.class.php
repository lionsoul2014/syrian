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

	/**
	 * implode a hash array's specifield key by specifield letter
	 *
	 * @param 	$array
	 * @param 	$key
	 * @param 	$glue
	 * @param 	$dup 	remove the dupliate value when it's true
	 * @return 	string
	*/
	public static function implode(&$array, $key, $glue, $dup = false)
	{
		$str 	= NULL;

		$idx 	= NULL;
		if ( $dup ) $idx 	= array();

		foreach ( $array as $value ) 
		{
			if ( $str == NULL ) 
			{
				$str = $value[$key];
				if ( $dup ) $idx["{$value[$key]}"] = true;
				continue;
			}

			if ( $dup == false ) 
				$str .= "{$glue}{$value[$key]}";
			else 
			{
				//remove the duplicate
				$v 		= $value[$key];
				if ( isset( $idx["{$v}"] ) ) continue;
				$str   .= "{$glue}{$v}";
				$idx["{$v}"] = true;
			}
		}

		return $str;
	}

	/**
	 * make a hash index for the specifield key of a specifield hash array
	 *
	 * @param 	$array
	 * @param 	$key
	 * @param 	$quote 		wether to quote its original array value
	 * @param 	Array
	*/
	public static function makeIndex( &$arr, $key, $quote = false )
	{
		if ( $arr == false ) return array();
		
		$index 	= array();
		$length = count($arr);
		for ( $i = 0; $i < $length; $i++ )
		{
			if ( $quote == false )
			{
				$index["{$arr[$i][$key]}"] = true;
				continue;
			}

			$index["{$arr[$i][$key]}"] = &$arr[$i];
		}

		return $index;
	}
}
?>
