<?php
/**
 * Extend String class with some great string handler
 *
 * @author    chenxin<chenxin619315@gmail.com>
*/

 //----------------------------------------------------

class Utf8String
{
    /**
     * get the bytes of the speicifield UTF-8 character
     *
     * @param   $char
     * @return  int the bytes that this char takes
    */
    public static function charBytes($char)
    {
        $char = ord($char);
        $byts = 0;

        if  ( ($char & 0x80) == 0 ) return 1;
        for ( ; ($char & 0x80) != 0; $char <<= 1 ) {
            $byts++;
        }

        return $byts;
    }

    public static function getCharBytes($char)
    {
        return self::charBytes($char);
    }

    /**
     * utf-8 string filter
     *    remove the character that takes more than specifield bytes
     *
     * @param   $bytes
     * @return  String the filtered string
    */
    public static function filter($string, $mbytes)
    {
        if ( $string == null ) return null;

        $ret = "";
        $len = strlen($string);
        
        //do the string filter
        for ( $i = 0; $i < $len; ) {
            $bytes = self::getCharBytes($string[$i]);
            if ( $bytes <= $mbytes ) {
                $ret .= substr($string, $i, $bytes);
            }

            $i += $bytes;
        }

        return $ret;
    }
    
    /*
     * get the unicode serial of a utf-8 char.
     * 
     * @param  $char
     * @return int
    */
    public static function unicode($char) 
    {
        $code  = 0;
        $bytes = self::charBytes($char[0]);

        switch ( $bytes ) {
        case 1:
            $code = ord($char);
            break;
        case 2:
            $b1   = ord($char[0]);
            $b2   = ord($char[1]);

            $by_0 = ( (($b1 << 6) + ($b2 & 0x3F)) & 0x7F );
            $by_1 = ( (($b1 >> 2) & 0x07) & 0x7F );
            return (($by_1 << 8) | $by_0);
        case 3:
            $b1   = ord($char[0]);
            $b2   = ord($char[1]);
            $b3   = ord($char[2]);

            $by_0 = ( (($b2 << 6) + ($b3 & 0x3F)) & 0x7F);
            $by_1 = ( (($b1 << 4) + (($b2 >> 2) & 0x0F)) & 0x7F);
            return (($by_1 << 8) | $by_0);
            //ignore the ones that are larger than 3 bytes;
        }

        return $code;
    }

    //turn the unicode serial to a utf-8 string.
    public static function utf8($u, &$bytes=0) 
    {
        if ( $u <= 0x0000007F ) {
            //U-00000000 - U-0000007F
            //0xxxxxxx
            $str    = '0';
            $str[0] = chr($u & 0x7F);
            $bytes  = 1;
        } else if ( $u >= 0x00000080 && $u <= 0x000007FF ) {
            //U-00000080 - U-000007FF
            //110xxxxx 10xxxxxx
            $str    = '00';
            $str[1] = chr(( $u & 0x3F) | 0x80);
            $str[0] = chr((($u >> 6) & 0x1F) | 0xC0);
            $bytes  = 2;
        } else if ( $u >= 0x00000800 && $u <= 0x0000FFFF ) {
            //U-00000800 - U-0000FFFF
            //1110xxxx 10xxxxxx 10xxxxxx
            $str    = '000';
            $str[2] = chr(( $u & 0x3F) | 0x80);
            $str[1] = chr((($u >> 6) & 0x3F) | 0x80);
            $str[0] = chr((($u >> 12) & 0x0F) | 0xE0);
            $bytes  = 3;
        } else if ( $u >= 0x00010000 && $u <= 0x001FFFFF ) {
            //U-00010000 - U-001FFFFF
            //11110xxx 10xxxxxx 10xxxxxx 10xxxxxx
            $str    = '0000';
            $str[3] = chr(( $u & 0x3F) | 0x80);
            $str[2] = chr((($u >>  6) & 0x3F) | 0x80);
            $str[1] = chr((($u >> 12) & 0x3F) | 0x80);
            $str[0] = chr((($u >> 18) & 0x07) | 0xF0);
            $bytes  = 4;
        } else if ( $u >= 0x00200000 && $u <= 0x03FFFFFF ) {
            //U-00200000 - U-03FFFFFF
            //111110xx 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx
            $str    = '00000';
            $str[4] = chr(( $u & 0x3F) | 0x80);
            $str[3] = chr((($u >>  6) & 0x3F) | 0x80);
            $str[2] = chr((($u >> 12) & 0x3F) | 0x80);
            $str[1] = chr((($u >> 18) & 0x3F) | 0x80);
            $str[0] = chr((($u >> 24) & 0x03) | 0xF8);
            $bytes  = 5;
        } else if ( $u >= 0x04000000 && $u <= 0x7FFFFFFF ) {
            //U-04000000 - U-7FFFFFFF
            //1111110x 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx
            $str    = '000000';
            $str[5] = chr(( $u & 0x3F) | 0x80);
            $str[4] = chr((($u >>  6) & 0x3F) | 0x80);
            $str[3] = chr((($u >> 12) & 0x3F) | 0x80);
            $str[2] = chr((($u >> 18) & 0x3F) | 0x80);
            $str[1] = chr((($u >> 24) & 0x3F) | 0x80);
            $str[0] = chr((($u >> 30) & 0x01) | 0xFC);
            $bytes  = 6;
        }

        return $str;
    }
    
}
