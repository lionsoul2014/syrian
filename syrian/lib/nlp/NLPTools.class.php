<?php
/**
 * basic NLP support.
 * this need to install the robbe extension.
 * @see http://code.google.com/p/robbe
 *
 * @author chenxin <chenxin619315@gmail.com>
*/
class NLPTools
{
    
    /**
     * get the keywords of the speicified string or article.
     *     for a better result, you may have to maintain the
     *             lex-stopwords.lex dictionary file of friso <http://code.google.com/p/friso>
     * 
     * @param    $_num    number of keywords to take
     * @return    array
    */
    public static function getKeywords( &$_str, $_num )
    {
        //1.tokenizer
        $_splits = rb_split($_str, array('mode'=>RB_CMODE));
        //2.count the term frequency
        $_ret = array();
        foreach ( $_splits as $item )
        {
            $_val = $item['word'];
            if ( is_numeric($_val) ) continue;
            if ( ord($_val) > 127 && strlen($_val) == 3 ) continue;
            if ( isset($_ret[$_val]) ) $_ret[$_val] = $_ret[$_val] + 1;
            else $_ret[$_val] = 1;
        }
        unset($_splits);
        
        //3.sort by the term frequency
        arsort($_ret, SORT_NUMERIC);
        
        //4.get the slice result
        $_length = min( $_num, count($_ret) );
        return array_slice( $_ret, 0, $_length );
    }
}
?>
