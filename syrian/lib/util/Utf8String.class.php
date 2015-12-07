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
     * @param    $char
     * @return    int    the bytes that this char takes
    */
    public static function getCharBytes($char)
    {
        $char = ord($char);
        $byts = 0;

        if  ( ($char & 0x80) == 0 ) return 1;
        for ( ; ($char & 0x80) != 0; $char <<= 1 )
        {
            $byts++;
        }

        return $byts;
    }

    /**
     * utf-8 string filter
     *    remove the character that takes more than specifield bytes
     *
     * @param    $bytes
     * @return    String    the filtered string
    */
    public static function filter($string, $mbytes)
    {
        if ( $string == NULL ) return NULL;
        $ret    = "";
        $length    = strlen($string);
        
        //do the string filter
        for ( $i = 0; $i < $length; )
        {
            $bytes    = self::getCharBytes($string[$i]);
            if ( $bytes <= $mbytes )
            {
                $ret .= substr($string, $i, $bytes);
            }

            $i += $bytes;
        }

        return $ret;
    }
}
?>
