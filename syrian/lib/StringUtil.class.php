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
