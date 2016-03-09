<?php
/**
 * Common Util function class
 *     Offer some useful functions
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

		while ($filename != '.' && $filename != '..') {
			if ( file_exists($filename) ) {
				$baseDir = $filename;
				break;	 
			}

			$dirArray[] = basename($filename);   //basename part
			$filename 	= dirname($filename); 
		}

		for ( $i = count($dirArray) - 1; $i >= 0; $i--) {
			if ( strpos($dirArray[$i], '.') !== FALSE ) {
				break;
			}

			$baseDir = $baseDir . '/' .$dirArray[$i];
			@mkdir( $baseDir );
			if ( $mode != NULL ) {
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
		if ( $CH == 'utf8' ) {
			for( $i = 0; $i < $len - 3; $i++ ) {
				$substr .= ord($str[$i])>127 ? $str[$i].$str[++$i].$str[++$i] : $str[$i]; 
            }
		} else if ( $CH == 'gbk' || $CH == 'gb2312' ) {
			for( $i = 0; $i < $len - 2; $i++ ) {
				$substr .= ord($str[$i])>127 ? $str[$i].$str[++$i] : $str[$i]; 
            }
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
		$ip = ""; 
		foreach ( array('HTTP_CLIENT_IP', 
			'HTTP_X_FORWARDED_FOR', 
			'HTTP_X_FORWARDED', 
			'HTTP_FORWARDED_FOR', 
			'HTTP_FORWARDED', 
			'REMOTE_ADDR') as $e ) {
			if ( getenv($e) ) {
				$ip = getenv($e);
				break;
			}
		}

		if ( ($comma=strpos($ip, ',')) !== false ) $ip = substr($ip, 0, $comma);
		if ( $convert ) $ip = sprintf("%u", ip2long($ip));

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
	 * @param 	$dup remove the dupliate value when it's true
	 * @param	$leftQuote left quote string
	 * @param	$rightQuote right quote string
	 * @return 	string
	 */
	public static function implode(
		&$array, $key, $glue, $dup=false, $leftQuote=NULL, $rightQuote=NULL)
	{
		if ( $array == false || empty($array) ) return NULL;

		//$str 	= NULL;
		//$idx 	= NULL;
		//if ( $dup )		$idx = array();

		//foreach ( $array as $value ) 
		//{
		//	if ( $str == NULL ) 
		//	{
		//		$str = $value[$key];
		//		if ( $dup ) $idx["{$value[$key]}"] = true;
		//		continue;
		//	}

		//	if ( $dup == false ) 
		//		$str .= "{$glue}{$value[$key]}";
		//	else 
		//	{
		//		//remove the duplicate
		//		$v 		= $value[$key];
		//		if ( isset( $idx["{$v}"] ) ) continue;
		//		$str   .= "{$glue}{$v}";
		//		$idx["{$v}"] = true;
		//	}
		//}

		$value	= array();
		$idx	= array();
		foreach ( $array as $val ) {
			$v	= $val["{$key}"];
			if ( $dup == false || ! isset($idx["{$v}"]) ) {
				$value[] = "{$leftQuote}{$v}{$rightQuote}";
			}

			$idx["{$v}"] = true;
		}

		unset($idx);

		return implode($glue, $value);
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
	public static function makeIndex( &$arr, $key, $quote=false )
	{
		if ( $arr == false ) return array();

		//$index 	= array();
		//$length = count($arr);
		//for ( $i = 0; $i < $length; $i++ )
		//{
		//	if ( $quote == false )
		//	{
		//		$index["{$arr[$i][$key]}"] = true;
		//		continue;
		//	}

		//	$index["{$arr[$i][$key]}"] = &$arr[$i];
		//}

		$index 	= array();
		foreach ( $arr as $val ) {
			$_m_key = $val["{$key}"];
			$index["{$_m_key}"] = $quote ? $val : true;
		}

		return $index;
	}

	/**
	 * sort array by specified filed
	 *
	 * @param array 
	 * @param string $filed array sort key
	 * @param intger $order sort order, default is SORT_ASC, another is SORT_DESC
	 * 
	 * @return array new array that has been sorted
	**/
	public static function arraySort(&$array, $field, $order = SORT_ASC)
	{
		$ret_array  = array();
		$sort_array = array();

		if ( count($array) > 0 ) {
			foreach ($array as $k => $v) {
				if ( is_array($v) ) {
					foreach ($v as $k1 => $v1) {
						if ( $k1 == $field ) {
							$sort_array[$k] = $v1;
						}
					}
				} else {
					$sort_array[$k] = $v;
				}
			}

			switch ($order) {
				case SORT_ASC:
					echo 1;
					asort($sort_array);
					break;
				case SORT_DESC:
					arsort($sort_array);
					break;
			}

			foreach ($sort_array as $key => $value) {
				$ret_array[$key] = $array[$key];
			}
		}

		return $ret_array;
	}

	/**
	 * group the specifield array by specifield field
	 * 	and take the value of the group key as the key the array of the 
	 * same items with the some group key value.
	 *
	 * @param 	$array
	 * @param 	$key
	 * @param	$count	only count the number of each group
	 * @return 	Array
	 */
	public static function groupBy( &$array, $key, $count=false )
	{
		if ( $array == false ) return array();

		//$index 	= array();		//returning index array
		//$length 	= count($array);
		//for ( $i = 0; $i < $length; $i++ )
		//{
		//	$vkey 	= $array[$i]["{$key}"];
		//	if ( ! isset( $index["{$vkey}"] ) )
		//	{
		//		$index["{$vkey}"] = array();
		//	}

		//	$index["{$vkey}"][]	= &$array[$i];
		//}

		$index	= array();
		if ( $count == false ) {
			foreach ( $array as $val ) {
				$_m_key = $val["{$key}"];
				if ( ! isset($index["{$_m_key}"]) ) $index["{$_m_key}"] = array();
				$index["{$_m_key}"][] = &$val;
				unset($val);
			}
		} else {
			foreach ( $array as $val ) {
				$_m_key = $val["{$key}"];
				if ( ! isset($index["{$_m_key}"]) ) $index["{$_m_key}"] = 1;
				else $index["{$_m_key}"]++;
			}
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
		$t 	= ($ctime == NULL ? time() : $ctime) - $timer;
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
     * @param   setting
	 * @return	Mixed(false or the http response body)
	 */
	public static function httpGet( $url, $_header=NULL, $setting=NULL )
	{
		$curl = curl_init();
		if( stripos($url, 'https://') !==FALSE ) {
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		}

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		if ( $_header != NULL ) {
			curl_setopt($curl, CURLOPT_HTTPHEADER, $_header);
		}

        //check and apply the setting
        if ( $setting != NULL ) {
            foreach ( $setting as $key => $val ) {
                curl_setopt($curl, $key, $val);
            }
        }

		$ret	= curl_exec($curl);
		$info	= curl_getinfo($curl);
		curl_close($curl);

		if( intval( $info["http_code"] ) == 200 ) {
			return $ret;
		}

		return false;
	}

	/**
	 * simple POST post request
	 * @param	string	$url
	 * @param	array	$param
     * @param   array   $setting
	 * @return	Mixed	false or the http response content
	 */
	public static function httpPost( $url, $param, $_header=NULL, $setting=NULL )
	{
		$curl	= curl_init();
		if( stripos( $url, 'https://') !== FALSE ) {
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		}

		$postfields	= NULL;
		if ( is_string($param) ) $postfields = $param;
		else {
			$args	= array();
			foreach ( $param as $key => $val) {
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
		if ( $_header != NULL ) {
			curl_setopt($curl, CURLOPT_HTTPHEADER, $_header);
		}

        //check and apply the setting
        if ( $setting != NULL ) {
            foreach ( $setting as $key => $val ) {
                curl_setopt($curl, $key, $val);
            }
        }

		$ret	= curl_exec($curl);
		$info	= curl_getinfo($curl);
		curl_close($curl);

		if( intval($info['http_code']) == 200 ) {
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

	/**
	 * get the time period for a day
	 * std: am(00-12:00), pm(12:00-24:00)
	 * could be:
	 * am: 06:00 - 12:00(less)
	 * pm: 12:00 - 18:00(less)
	 * ng: 18:00 - 24:00(less)
	 *
	 * @return	string
	*/
	public static function getDayTimeKey($time=NULL)
	{
		if ( $time == NULL ) $time = time();

		$H = date('H', $time);
		if ( $H >= 0  && $H < 6  )	return 'bd';
		if ( $H >= 6  && $H < 12 )	return 'am';
		if ( $H >= 12 && $H < 18 )	return 'pm';
		//if ( $H >= 18 && $H < 24 )	return 'ng';
		return 'ng';
	}

    /**
     * convert an integer to a binary string 
     *
     * @param   intval
     * @param   $bytesort
     * @reteurn String
     */
    public static function int2BinaryString($v, $bytesort = 0)
    {
        return (
            $bytesort == 0 ? 
            (
                 chr(($v>>24)&0xFF)
                .chr(($v>>16)&0xFF)
                .chr(($v>> 8)&0xFF)
                .chr($v&0xFF)
            ) : (chr($v&0xFF)
                .chr(($v>> 8)&0xFF)
                .chr(($v>>16)&0xFF)
                .chr(($v>>24)&0xFF)
            )
        );
    }

    /**
     * Generate a DCE(Distribute Computing Environment) 
     *  UUID(Universally Unique Identifier)
     *
     * @return  String
    */
    public static function generateUUIDV4()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * generate a universally unique identifier
     * base on the prefix the and the seed.
     *
     * @param   $prefix
     * @param   $seed
     * @return  String
    */
    public static function generateUUIDV5($prefix, $seed)
    {
        // Calculate hash value
        $hash = sha1($prefix . $seed);

        return sprintf('%08s-%04s-%04x-%04x-%12s',
            // 32 bits for "time_low"
            substr($hash, 0, 8),
            // 16 bits for "time_mid"
            substr($hash, 8, 4),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 5
            (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
            // 48 bits for "node"
            substr($hash, 20, 12)
        );
    }

}
?>
