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
	public static function makePath( $filename, $mode=0750 ) 
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

			$baseDir = $baseDir . '/' .$dirArray[$i];
			@mkdir( $baseDir );
			if ( $mode != NULL )
			{
				@chmod($baseDir);
			}
		}
	}

	/**
	 * self define substr and make sure the substring will
	 * 	be good looking
	 *
	 * @param	$str
	 * @param	$len
	 * @param	$charset
	 */
	public static function substr($str, $len, $charset='UTF-8') 
	{ 
		if ( strlen($str) <= $len ) return $str;
		$CH	= strtolower(str_replace('-', '', $charset));

		//get the substring
		$substr = ''; 
		if ( $CH == 'utf8' )
		{
			for( $i = 0; $i < $len - 3; $i++ ) 
				$substr .= ord($str[$i])>127 ? $str[$i].$str[++$i].$str[++$i] : $str[$i]; 
		}
		else if ( $CH == 'gbk' || $CH == 'gb2312' )
		{
			for( $i = 0; $i < $len - 2; $i++ ) 
				$substr .= ord($str[$i])>127 ? $str[$i].$str[++$i] : $str[$i]; 
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

	//get the real client ip
	public static function getRealIp($convert = false)
	{
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
		if ( $array == false || empty($array) ) return NULL;

		$idx 	= NULL;
		if ( $dup )		$idx = array();

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
	 * 	and the late one will rewrite the previous one when face a dupliate key
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

	/**
	 * group the specifield array by specifield field
	 * 	and take the value of the group key as the key the array of the 
	 * same items with the some group key value.
	 *
	 * @param 	$array
	 * @param 	$key
	 * @return 	Array
	 */
	public static function groupBy( &$array, $key )
	{
		if ( $array == false ) return array();

		$index 		= array();		//returning index array
		$length 	= count($array);
		for ( $i = 0; $i < $length; $i++ )
		{
			$vkey 	= $array[$i]["{$key}"];
			if ( ! isset( $index["{$vkey}"] ) )
			{
				$index["{$vkey}"] = array();
			}

			$index["{$vkey}"][]	= &$array[$i];
		}

		return $index;
	}

	/**
	 * convert the unix timestamp to seconds ago, hours ago, days ago
	 * 	month ago or years ago
	 *
	 * @param 	$timer
	 * @param	$ctime	current time 
	 */
	public static function getTimeString( $timer, $ctime = NULL )
	{
		$t 		= ($ctime == NULL ? time() : $ctime) - $timer;
		if ( $t < 0 ) return date('Y年m月d日', $timer);

		if ( $t < 5 )			return '刚刚';							//just now
		if ( $t < 60 )			return $t.'秒前';						//under one minuts
		if ( $t < 3600 )		return floor($t/60).'分钟前';			//under one hour
		if ( $t < 86400 )		return floor($t/3600).'小时前';			//under one day
		if ( $t < 2592000 )		return floor($t/86400).'天前';			//under one month
		if ( $t < 31104000 )	return date('m月d日', $timer);			//under one year
		return 	date('Y年m月d日', $timer);
	}
	
	//get the current system time (microtime)
	public static function getMicroTime() 
	{
		list($msec, $sec) = explode(' ', microtime());	
    	return ((float)$msec + (float)$sec);
	}

	/*
	 * get x random letters
	 *
	 * @param	$x
	 * @param	$numberOnly only generate number?
	*/
	public static function randomLetters($x, $numberOnly=false)
	{
		//random seed
		$_letters	= $numberOnly ? '0123456789' : '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$length		= strlen($_letters);

		$CHARS = array();
		for ( $i = 0; $i < $x; $i++ ) {
			$CHARS[] = $_letters[mt_rand()%$length];
		}

		return implode('', $CHARS);
	}

	/**
	 * simple http GET request
	 *
	 * @param	string $url
	 * @param	Array header
	 * @return	Mixed(false or the http response body)
	 */
	public static function httpGet( $url, $_header = NULL )
	{
		$curl = curl_init();
		if( stripos($url, 'https://') !==FALSE )
		{
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		}

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		if ( $_header != NULL )
		{
			curl_setopt($ch, CURLOPT_HTTPHEADER, $_header);
		}
		$ret	= curl_exec($curl);
		$info	= curl_getinfo($curl);
		curl_close($curl);

		if( intval( $info["http_code"] ) == 200 )
		{
			return $ret;
		}

		return false;
	}

	/**
	 * simple POST post request
	 * @param	string	$url
	 * @param	array	$param
	 * @return	Mixed	false or the http response content
	 */
	public static function httpPost( $url, $param, $_header = NULL )
	{
		$curl	= curl_init();
		if( stripos( $url, 'https://') !== FALSE )
		{
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		}

		$postfields	= NULL;
		if ( is_string($param) ) $postfields = $param;
		else
		{
			$args	= array();
			foreach ( $param as $key => $val)
			{
				$args[]	= $key . '=' . urlencode($val);
			}

			$postfields	= implode('&', $args);
			unset($args);
		}

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postfields);
		if ( $_header != NULL )
		{
			curl_setopt($ch, CURLOPT_HTTPHEADER, $_header);
		}
		$ret	= curl_exec($curl);
		$info	= curl_getinfo($curl);
		curl_close($curl);

		if( intval($info['http_code']) == 200 )
		{
			return $ret;
		}

		return false;
	}

	/**
	 * get the access device code
	 * format: p|m+[a,i,w,l,m,x]
	 * values:
	 * pm: pc max
	 * pl: pc linux
	 * pw: pc window
	 * px: pc unknow
	 * ma: mobile android
	 * ma: mobile ios
	 * mw: mobile window (wp)
	 * mx: mobile unkown
	 * mm: mobile mac (ios tablet)
	*/
	public static function getDevice($uAgent=NULL)
	{
		if ( $uAgent == NULL && isset($_SERVER['HTTP_USER_AGENT']) ) {
			$uAgent = $_SERVER['HTTP_USER_AGENT'];
		}

		if ( $uAgent == NULL ) return 'xx';
		
		//define the mobile source
		$isMobile	= false;
		if ( isset($_SERVER['HTTP_X_WAP_PROFILE']) ) {
			$isMobile = true;
		} else if ( isset($_SERVER['HTTP_VIA']) && strpos($_SERVER['HTTP_VIA'], 'wap') !== false ) {
			$isMobile = true;
		} else {
			$lowerAgent	= strtolower($uAgent);
			$mobileOS	= array(
				'phone', 'mobile', 'tablet', 'android', 'iphone', 'blackberry', 'symbian', 'nokia', 'palmos', 'j2me'
			);
			foreach ( $mobileOS as $os )
			{
				if ( strpos($lowerAgent, $os) !== false )
				{
					$isMobile = true;
					break;
				}
			}
		}

		//define the device part
		$device	= 'x';
		if ( stripos($uAgent, 'Android') !== false )		$device = 'a';	//Android
		else if ( stripos($uAgent, 'iPhone') !== false )	$device = 'i';	//ios
		else if ( stripos($uAgent, 'Linux') !== false )		$device = 'l';	//linux
		else if ( stripos($uAgent, 'Windows') !== false ) 	$device = 'w';	//winnt
		else if ( stripos($uAgent, 'Mac') !== false )		$device = 'm';

		return ($isMobile?'m':'p') . $device;
	}
}
?>
