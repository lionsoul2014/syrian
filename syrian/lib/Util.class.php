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
	public static function makePath( $filename, $mode=0755 ) 
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
				@chmod($baseDir, $mode);
			}
		}
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

		$ret  = curl_exec($curl);
		$info = curl_getinfo($curl);
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
		$curl = curl_init();
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

		$ret  = curl_exec($curl);
		$info = curl_getinfo($curl);
		curl_close($curl);

		if ( intval($info['http_code']) == 200 ) {
			return $ret;
		}

		return false;
	}

    /**
     * is the current request device a mobile ?
     *
     * @param   $src default to NULL
     * @return  bool
    */
    public static function isMobile($src=NULL)
    {
        $src = ($src == NULL) ? $_SERVER : $src;
        if ( isset($src['HTTP_X_WAP_PROFILE']) ) {
            return true;
        }

        if ( isset($src['HTTP_VIA']) 
            && strpos($src['HTTP_VIA'], 'wap') !== false ) {
            return true;
        }

        //via the http request user agent
        $uAgent = isset($src['HTTP_USER_AGENT']) ? $src['HTTP_USER_AGENT'] : NULL;
        if ( $uAgent == NULL ) {
            return false;
        }

        $uAgent   = strtolower($uAgent);
        $mobileOS = array(
            'phone', 'mobile', 'tablet', 'android', 'iphone', 'blackberry', 'symbian', 'nokia', 'palmos', 'j2me'
        );
        foreach ( $mobileOS as $os ) {
            if ( strpos($uAgent, $os) !== false ) {
                return true;
            }
        }

        return false;
    }

    /**
     * check if the current device is Android
     * 
     * @param   $uAgent
     * @param   $sign
     * @return  boolean
    */
    protected function isAndroid($uAgent=NULL, $sign=NULL)
    {
        if ( $uAgent == NULL ) {
            $uAgent = $this->input->server('HTTP_USER_AGENT');
        }

        if ( $sign == NULL ) {
            return strpos($uAgent, $sign) !== false;
        }

        return (
            strpos($uAgent, 'Android') !== false 
            && strpos($uAgent, $sign) !== false
        );
    }

    /**
     * check if the current device is ios
     * 
     * @param   $uAgent
     * @param   $sign
     * @return  boolean
    */
    protected function isIOS($uAgent=NULL, $sign=NULL)
    {
        if ( $uAgent == NULL ) {
            $uAgent = $this->input->server('HTTP_USER_AGENT');
        }

        $isIOS = (strpos($uAgent, 'iOS') !== false || strpos($uAgent, 'iPhone') !== false);
        if ( $sign == NULL ) {
            return $isIOS;
        }

        return (
            $isIOS && strpos($uAgent, $sign) !== false
        );
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
			foreach ( $mobileOS as $os ) {
				if ( strpos($lowerAgent, $os) !== false ) {
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
