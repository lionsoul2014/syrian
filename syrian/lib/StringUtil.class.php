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
     * get the bytes of the specified char
     *
     * @param   $char
     * @param   $charset (utf8,gbk,gb2312 support)
     * @return  integer
    */
    public static function getCharBytes($char, $charset='UTF8')
    {
        $ord_val = ord($char);
        if ( strncmp($charset, 'GB', 2) == 0 ) {
            return $ord_val > 127 ? 2 : 1;
        }

        # default it to utf8 charset
        if ( ($ord_val & 0x80) == 0 ) {
            return 1;
        }

        $bytes = 1;
        $ord_val <<= 1;
        for ( ; ($ord_val & 0x80) != 0; $ord_val <<= 1 ) {
            $bytes++;
        }

        return $bytes;
    }

    /**
     * do the string max bytes substr
     *
     * @param   $str
     * @param   $offset
     * @param   $bytes
     * @param   $charset
     * @return  string
    */
    public static function bytes_substr($str, $offset, $bytes, $charset='UTF8')
    {
        $buffer  = array();
        $length  = strlen($str);
        $l_bytes = 0;
        $charset = strtoupper($charset);

        for ( $i = $offset; $i < $length; ) {
            $byte_v = self::getCharBytes($str[$i], $charset);
            if ( $l_bytes + $byte_v > $bytes ) {
                break;
            }

            $buffer[] = $byte_v > 1 ? substr($str, $i, $byte_v) : $str[$i];
            $i += $byte_v;
            $l_bytes += $byte_v;
        }

        return implode('', $buffer);
    }

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
            $togo = true;
            switch ( $type[0] ) {
            case 'o':   //object
            case 'r':   //resource
                $togo = false;
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

            # check and continue the loop
            if ($togo == false) {
                continue;
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
    public static function filterUnprintableChars(
        $string, $encode = 'UTF-8', $reserve = array())
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
                } else {
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
                } else {
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

    public static function esIndexFilter($str)
    {
        $str = strip_tags($str);
        $str = preg_replace('/&[a-zA-Z]+;/', ' ', $str);
        $str = preg_replace('/\s{2,}/', ' ', $str);
        $str = str_replace(array("\n", "\t", "\r", "\\"), ' ', $str);
        $str = self::filterUnprintableChars($str, 'utf-8');
        return $str;
    }

    public static function summaryFilter($str, $bytes, $charset='UTF8')
    {
        $str = strip_tags($str);
        $str = preg_replace('/&[a-zA-Z]+;/', ' ', $str);
        $str = preg_replace('/\s{2,}/', ' ', $str);
        $str = str_replace(array("\n", "\t", "\r", "\\"), ' ', $str);
        return self::bytes_substr($str,0,$bytes,$charset);
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
     * @param    $str
     * @param    $len
     * @param    $charset
     */
    public static function substr($str, $len, $charset='UTF-8') 
    { 
        if ( strlen($str) <= $len ) return $str;
        $CH = strtolower(str_replace('-', '', $charset));

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
     * @param    $x
     * @param    $numberOnly only generate number?
    */
    public static function randomLetters($x, $numberOnly=false)
    {
        //random seed
        $_letters = $numberOnly ? '0123456789' : '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $length   = strlen($_letters);

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

    public static function genNodeUUID()
    {
        $prefix = NULL;
        if ( defined('SR_NODE_NAME') ) {
            $prefix = substr(md5(SR_NODE_NAME), 0, 4);
        } else {
            $prefix = sprintf("%04x", mt_rand(0, 0xffff));
        }

        $tArr = explode(' ', microtime());
        $tsec = $tArr[1];
        $msec = $tArr[0];
        if ( ($sIdx = strpos($msec, '.')) !== false ) {
            $msec = substr($msec, $sIdx + 1);
        }

        return sprintf(
            "%08x-%04x-%04s-%04x-%08x%04x",
            $tsec,
            $msec,
            $prefix,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffffffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * generate a universal unique identifier
     *
     * @param   $factor factor id
     * @return  String
    */
    public static function genGlobalUid($factor=null)
    {
        /*
         * 1, create a guid
         * check and append the node name to
         *  guarantee the basic server unique
        */
        $prefix = NULL;
        if ( defined('SR_NODE_NAME') ) {
            $prefix = substr(md5(SR_NODE_NAME), 0, 4);
        } else {
            $prefix = sprintf("%04x", mt_rand(0, 0xffff));
        }

        $tArr = explode(' ', microtime());
        $tsec = $tArr[1];
        $msec = $tArr[0];
        if ( ($sIdx = strpos($msec, '.')) !== false ) {
            $msec = substr($msec, $sIdx + 1);
        }

        if ( $factor == null ) {
            $format = "%08x%08x%0s%08x%04x";
            $factor = mt_rand(0, 0x7fffffff);
            $random = mt_rand(0, 0xffff);
        } else if ( is_integer($factor) ) {
            if ( $factor <= 0 ) {
                $format = "%08x%08x%0s%08x%04x";
                $factor = mt_rand(0, 0x7fffffff);
                $random = mt_rand(0, 0xffff);
            } if ( $factor <= 0xffffffff ) {
                $format = "%08x%08x%0s%08x%04x";
                $random = mt_rand(0, 0xffff);
            } else if ( $factor <= pow(2, 40) - 1 ) {
                $format = "%08x%08x%0s%010x%02x";
                $random = mt_rand(0, 0xff);
            } else {
                $format = "%08x%08x%0s%012x";
                $random = 0;
            }
        } else {
            $format = "%08x%08x%0s%0s%04x";
            $factor = substr(md5($factor), 0, 8);
            $random = mt_rand(0, 0xffff);
        }

        return sprintf(
            $format,
            $tsec,
            $msec,
            $prefix,
            $factor,
            $random
        );
    }

    /**
     * clear the html tags except the need to kept sets
     *
     * @param   $str
     * @param   $tags like array('img', 'a')
     * @return  String
    */
    public static function clearHtml($str, $tags=null)
    {
        if ( $tags == null ) {
            return strip_tags($str);
        }

        $olen = strlen($str);
        $tstr = is_array($tags) ? implode('|', $tags) : $tags;
        $str  = preg_replace('/<(\/?(' . $tstr . ')[^>]*)>/i', '{~[$1]~}', $str);
        $alen = strlen($str);
        $str  = strip_tags($str);

        if ( $alen == $olen ) {
            return $str;
        }

        return preg_replace('/\{~\[(.*?)\]~\}/', '<$1>', $str);
    }

    /**
     * clear the punctuation and whitespace of the specifield string
    */
    public static function clearEnPunc($str, $replace='')
    {
        return preg_replace("/[[:punct:]]/i", $replace, $str);
    }

    public static function clearCnPunc($str, $replace='', $charset='utf-8')
    {
        // Filter 中文标点符号
        mb_regex_encoding($charset);
        $puncs = "，。、！？：；﹑•＂…‘’“”〝〞∕¦‖—　〈〉﹞﹝「」‹›〖〗】【»«』『〕〔》《﹐¸﹕︰﹔！¡？¿﹖﹌﹏﹋＇´ˊˋ―﹫︳︴¯＿￣﹢﹦﹤‐­˜﹟﹩﹠﹪﹡﹨﹍﹉﹎﹊ˇ︵︶︷︸︹︿﹀︺︽︾ˉ﹁﹂﹃﹄︻︼（）";
        return mb_ereg_replace("[{$puncs}]", $replace, $str, $charset);
    }

    public static function clearPunc($str, $replace='', $charset='utf-8')
    {
        $str = preg_replace("/\s+/", $replace, $str);
        $str = self::clearEnPunc($str, $replace);
        $str = self::clearCnPunc($str, $replace, $charset);
        return $str;
    }

    public static function trim($str)
    {
        if (function_exists("mb_trim")) {
            $nStr = mb_trim($str);
        } else {
            $nStr = preg_replace("/(^\s+)|(\s+$)/u", "", $str);
        }

        return trim($nStr);
    }

}
