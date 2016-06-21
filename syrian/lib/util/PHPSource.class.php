<?php
/**
 * PHP source util class
 *
 * @author  chenxin<chenxin619315@gmail.com>
*/

//------------------------------------------

class PHPSource 
{
    private static $match = array(
        '/\s+([\(\)\?:\+{}=\.,<>;])/' => '$1',
        '/([\(\)\?:\+{}=\.,<>;])\s+/' => '$1'
    );

    /**
     * minify the specifield php source file
     *  and write the process result to the dist file
     *
     * @param   $srcFile
     * @param   $dstFile
     * @param   bool
    */
    public static function minify($srcFile, $dstFile)
    {
        $src = php_strip_whitespace($srcFile);
        if ( strlen($src) < 1 ) {
            return false;
        }

        $src = self::compress($src);
        return file_put_contents($dstFile, $src, LOCK_EX) == strlen($src);
    }

    /**
     * php source string compress
     *
     * @param   $srcStr
     * @return  String
    */
    private static function compress($srcStr)
    {
        $keys = array_keys(self::$match);
        $len  = strlen($srcStr);
        $sIdx = 0;
        $buff = array();
        while ( ($info = self::detectNextString($srcStr, $sIdx)) !== false ) {
            $start  = $info[0];
            $end    = $info[1];
            $length = $info[2];

            $subsrc = substr($srcStr, $sIdx, $start - $sIdx);
            $buff[] = preg_replace($keys, self::$match, $subsrc);
            $buff[] = substr($srcStr, $start, $length);
            $sIdx   = $end + 1;
        }

        if ( $sIdx < $len ) {
            $buff[] = preg_replace($keys, self::$match, substr($srcStr, $sIdx));
        }

        return implode('', $buff);
    }

    /**
     * find a string(quoted with "" or '') start from the specfied offset
     *
     * @param   $str
     * @param   $start
     * @return  Mixed(false or Array)
    */
    public static function detectNextString($str, $start=0)
    {
        $sIdx = -1;
        $char = NULL;
        if ( ($dsIdx = strpos($str, "'", $start)) !== false ) {
            $sIdx = $dsIdx;
            $char = '\'';
        }

        if ( ($ssIdx = strpos($str, '"', $start)) !== false ) {
            if ( $sIdx == -1 || $ssIdx < $sIdx ) {
                $sIdx = $ssIdx;
                $char = '"';
            }
        }

        if ( $sIdx == -1 ) {
            return false;
        }

        //detect the end of the string token
        $eIdx = 0;
        $len  = strlen($str);
        for ( $i = $sIdx + 1; $i < $len; $i++ ) {
            if ( $str[$i] == $char && $str[$i-1] != '\\' ) {
                $eIdx = $i;
                break;
            }
        }

        return array($sIdx, $eIdx, $eIdx - $sIdx + 1);
    }

}
?>
