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
     * @param   $str
     * @param   $i
     * @return  int the bytes that this char takes
    */
    public static function charBytes($str, $i)
    {
        $char = ord($str[$i]);
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
            $bytes = self::charBytes($string, $i);
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
    public static function unicode($str, $i, &$bytes=-1) 
    {
        if ( $bytes == -1 ) {
            $bytes = self::charBytes($str, $i);
        }

        switch ( $bytes ) {
        case 1:
            return ord($str[$i]);
        case 2:
            $b1   = ord($str[$i]);
            $b2   = ord($str[$i+1]);

            $by_0 = ( (($b1 << 6) + ($b2 & 0x3F)) & 0xFF );
            $by_1 = ( (($b1 >> 2) & 0x07) & 0xFF );
            return (($by_1 << 8) | $by_0);
        case 3:
            $b1   = ord($str[$i]);
            $b2   = ord($str[$i+1]);
            $b3   = ord($str[$i+2]);

            $by_0 = ( (($b2 << 6) + ($b3 & 0x3F)) & 0xFF);
            $by_1 = ( (($b1 << 4) + (($b2 >> 2) & 0x0F)) & 0xFF);
            return (($by_1 << 8) | $by_0);
            //ignore the ones that are larger than 3 bytes;
        }

        return 3;
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

    /**
     * check the specifield char is punctuation or not
     * both english and chinese punctuation will be ok
     *
     * @param   $u
     * @return  boolean
    */
    public static function isPunctuation($u)
    {
        if ( $u > 65280 ) {
            $u -= 65248;
        }

        return ( 
            ($u > 32 && $u  < 48 ) || 
            ($u > 57 && $u  < 65 ) || 
            ($u > 90 && $u  < 97 ) || 
            ($u > 122 && $u < 127)
        );
    }

    /**
     * check if the specifield is a whitespace

     * @param   $u
     * @return  boolean
    */
    public static function isWhitespace($u)
    {
        if ( $u > 65280 ) {
            $u -= 65248;
        }

        return ($u == 32);
    }

    /*
     * check the given char is a english punctuation.
     * 
     * @param   $u
     * @return  boolean
    */
    public static function isENPunctuation($u) 
    {
        return ( 
            ($u > 32 && $u  < 48 ) || 
            ($u > 57 && $u  < 65 ) || 
            ($u > 90 && $u  < 97 ) || 
            ($u > 122 && $u < 127)
        );
    }

    /*
     * check the given char is a chinese punctuation.
     * @date    2013-08-31 added.
     *
     * @param   $u
     * @return  boolean
    */
    public static function isCNPunctuation($u) 
    {
        return (
            ($u > 65280 && $u < 65296 ) || 
            ($u > 65305 && $u < 65312 ) || 
            ($u > 65338 && $u < 65345 ) || 
            ($u > 65370 && $u < 65382 ) ||
                //cjk symbol and p$unct$uation.(added 2013-09-06)
                //from http://www.$unicode.org/charts/PDF/U3000.pdf
            ($u >= 12289 && $u <= 12319)
        );
    }

    /**
     * check if the current char is chinese letter or not
     * 
     * @param   $u
     * @param   $numeric
     * @return  boolean
    */
    public static function isEnglishLetter($u)
    {
        if ( $u > 65280 ) {
            $u -= 65248;
        }

        return (
            ($u >= 48 && $u <= 57 ) ||
            ($u >= 65 && $u <= 90 ) ||
            ($u >= 97 && $u <= 122)
        );
    }

    /**
     * check if the current char is chinese letter or not
     * 
     * @param   $u
     * @return  boolean
    */
    public static function isChineseLetter($u)
    {
        $c = ( 
            ($u >= 0x4E00 && $u <= 0x9FBF) 
            || ($u >= 0x2E80 && $u <= 0x2EFF) 
            || ($u >= 0x2F00 && $u <= 0x2FDF) 
            || ($u >= 0x31C0 && $u <= 0x31EF) 
            //|| ( $u >= 0x3200 && $u <= 0x32FF )
            || ($u >= 0x3300 && $u <= 0x33FF) 
            //|| ( $u >= 0x3400 && $u <= 0x4DBF )
            || ($u >= 0x4DC0 && $u <= 0x4DFF) 
            || ($u >= 0xF900 && $u <= 0xFAFF)
            || ($u >= 0xFE30 && $u <= 0xFE4F)
        ); 

        $j = (
            ($u >= 0x3040 && $u <= 0x309F)
            || ($u >= 0x30A0 && $u <= 0x30FF) 
            || ($u >= 0x31F0 && $u <= 0x31FF)
        );

        $k = (
            ($u >= 0xAC00 && $u <= 0xD7AF)
            || ($u >= 0x1100 && $u <= 0x11FF) 
            || ($u >= 0x3130 && $u <= 0x318F)
        );

        return ($c || $j || $c);
    }

}
