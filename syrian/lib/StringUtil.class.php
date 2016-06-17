<?php
/**
 * String Util function class
 * 
 * @author chenxin <chenxin619315@gmail.com>
 */

//--------------------------------------------------------

class StringUtil
{
    /**
     * json encoder: convert the array to json string
     * and the original string will not be encoded like the json_encode do
     *
     * @param   $data
     * @return  String
    */
    public static function array2Json($data)
    {
        if ( ! is_array($data) ) {
            $type = gettype($data);
            switch ( $type[0] ) {
            case 'b':
                return $data ? 'true' : 'false';
            case 'i':
            case 'd':
            case 'N':
                return $data;
            case 's':
                return '"'.self::addslash($data).'"';
            default:
                return NULL;
            }
        }

        //define the associative attribute
        $isAssoc = false;
        foreach ( $data as $key => $val ) {
            if ( is_string($key) ) {
                $isAssoc = true;
                break;
            }
        }

        $buff = [];
        foreach ( $data as $key => $val ) {
            $type = gettype($val);
            switch ( $type[0] ) {
            case 'o':   //object
            case 'r':   //resource
                continue;
                break;
            case 'b':   //boolean
                $val = $val ? 'true' : 'false';
                break;
            case 'i':   //integer
            case 'd':   //double
            case 'N':   //NULL
                //leave it unchange
                break;
            case 's':
                $val = '"'.self::addslash($val, '"').'"';
                break;
            case 'a':
                $val = self::array2Json($val);
                break;
            }

            //check and append the key
            if ( $isAssoc ) {
                $buff[] = "\"{$key}\":{$val}";
            } else {
                $buff[] = $val;
            }
        }

        if ( $isAssoc ) {
            $json = '{'.implode(',', $buff).'}';
        } else {
            $json = '['.implode(',', $buff).']';
        }

        return $json;
    }

    /**
     * filter unprintable characters
     *
     * @param $string         string
     * @param string $encode  utf-8, gbk, gb2312...
     * @param array $reserve  unprintable characters reserved, value: array(9, 10, 13...)
     * @return bool|string
     */
    public static function filterUnprintableChars($string, $encode = 'UTF-8', $reserve = array())
    {
        if ( $string == NULL ) return false;

        $buffer = array();
        $length = strlen($string);

        switch( strtoupper($encode) ) {
            case 'GBK':
            case 'GB2312':
                for ( $i = 0; $i < $length; ) {
                    $code = ord($string[$i]);

                    if ( $code <= 127 ) {
                        if ( $code < 32
                            && ! in_array($code, $reserve) ) {
                            $i++;
                            continue;
                        }

                        array_push($buffer, $string[$i]);

                        $bytes = 1;
                    }
                    else {
                        //gbk, gb2312, skip two bytes
                        array_push($buffer, $string[$i]);
                        array_push($buffer, $string[$i+1]);

                        $bytes = 2;
                    }

                    $i += $bytes;
                }
                break;
            case 'UTF-8':
                for ( $i = 0; $i < $length; ) {
                    $code  = ord($string[$i]);

                    if ( ($code & 0x80) == 0 ) {
                        if ( $code < 32
                            && ! in_array($code, $reserve) ) {
                            $i++;
                            continue;
                        }

                        array_push($buffer, $string[$i]);

                        $bytes = 1;
                    }
                    else {
                        $bytes = 0;

                        //utf-8, skip multiple bytes, no more than 4
                        for ( ; ($code & 0x80) != 0; $code <<= 1 ) {
                            $bytes++;
                        }

                        array_push($buffer, substr($string, $i, $bytes));
                    }

                    $i += $bytes;
                }
                break;
            default:
                return false;
                break;
        }

        return implode('', $buffer);
    }

    /**
     * string slash function, slash the specifield sub-string
     *
     * @param   $str
     * @return  String
    */
    public static function addslash($str, $tchar)
    {
        $sIdx = strpos($str, $tchar);
        if ( $sIdx === false ) {
            return $str;
        }

        $buff   = [];
        $buff[] = substr($str, 0, $sIdx);
        if ( $str[$sIdx-1] != '\\' ) {
            $buff[] = '\\';
        }
        $buff[] = '"';
        $sIdx++;

        while (($eIdx = strpos($str, $tchar, $sIdx)) !== false) {
            $buff[] = substr($str, $sIdx, $eIdx-$sIdx);
            if ( $str[$eIdx-1] != '\\' ) {
                $buff[] = '\\';
            }
            $buff[] = '"';

            $sIdx = $eIdx + 1;
        }

        //check and append the end part
        if ( $sIdx < strlen($str) ) {
            $buff[] = substr($str, $sIdx);
        }

        return implode('', $buff);
    }

	/**
	 * self define substr and make sure the substring will
	 * be correct
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


	/*
	 * get x random letters
	 *
	 * @param	$x
	 * @param	$numberOnly only generate number?
	*/
	public static function randomLetters($x, $numberOnly=false)
	{
		//random seed
		$_letters = $numberOnly ? '0123456789' : '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$length	  = strlen($_letters);

		$CHARS = array();
		for ( $i = 0; $i < $x; $i++ ) {
			$CHARS[] = $_letters[mt_rand()%$length];
		}

		return implode('', $CHARS);
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
