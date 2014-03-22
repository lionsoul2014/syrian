<?php
/**
 * Html page text extractor base on line block analysis
 *      content extract algorithm
 *      
 * A lot optimization were added to make it works better
 *
 * @author chenxin <chenxin619315@gmail.com>
*/

 //-------------------------------------------------------------
 
 defined('EXTRACTOR_TITLE')      or define('EXTRACTOR_TITLE',    1 << 1);
 defined('EXTRACTOR_PUBTIME')    or define('EXTRACTOR_PUBTIME',  1 << 2);
 defined('EXTRACTOR_AUTHOR')     or define('EXTRACTOR_AUTHOR',   1 << 3);
 defined('EXTRACTOR_TEXT')       or define('EXTRACTOR_TEXT',     1 << 4);
 defined('EXTRACTOR_IMAGES')     or define('EXTRACTOR_IMAGES',   1 << 5);

/**
 * normal extractor, collect just title, text and the publish time
 */
 defined('EXTRACTOR_NORMAL')     or define('EXTRACTOR_NORMAL',   EXTRACTOR_TITLE
    | EXTRACTOR_PUBTIME | EXTRACTOR_TEXT);

/**
 * control mask to analysis all the fileds
 */
 defined('EXTRACTOR_ALL')        or define('EXTRACTOR_ALL',      EXTRACTOR_TITLE
    | EXTRACTOR_PUBTIME | EXTRACTOR_AUTHOR | EXTRACTOR_TEXT | EXTRACTOR_IMAGES);
 
 
class Extractor
{
    /**
     * the source charset of the original page
     *
     * @access private
    */
    private     $_encoding;
    
    /**
     * the output charset (need iconv library)
     *
     * @access private
    */
    private     $_outcharset;
    
    /**
     * the title of the page
     *
     * @access  private
    */
    private     $_title;
    
    /**
     * the publish time of the page
     *  just return it and wont' do any changes
    */
    private     $_pubtime;
    
    /**
     * the author got from the page
     *  usually it won't works very good
    */
    private     $_author;
    
    /**
     * the final text after analysised of the page
    */
    private     $_text;
    
    /**
     * the content images (img markup in the contens)
     *      could be a empty array
    */
    private     $_images;
    
    
    //-------------------------------------------------
    private     $_html;                 //response html string
    private     $_url;                  //request url
    private     $_blockWidth = 3;
    private     $_threshold  = 280;     //
    
    /**
     * the construct method
     *
     * @param   $_outcharset the output charset - default UTF-8
    */
    public function __construct( $_mask = EXTRACTOR_ALL, $_outcharset = 'UTF-8' )
    {
        //set the control mask
        $this->_mask = $_mask;
        $this->_outcharset = $_outcharset;
        $this->reset();
    }
    
    /**
     * Quick method to clear the current
     *      status of the extractor
    */
    public function reset()
    {
        $this->_encoding    = NULL;
        $this->_title       = NULL;
        $this->_pubtime     = NULL;
        $this->_author      = NULL;
        $this->_text        = NULL;
        $this->_images      = NULL;
        
        $this->_html        = NULL;
        $this->_url         = NULL;
    }
    
    /**
     * set the analysis html string
     *
     * @param   $_html
    */
    public function setHtml( $_html )
    {
        $this->_html = $_html;
    }
    
    /**
     * set the current request url
     *
     * @param   $_url
    */
    public function setUrl( $_url )
    {
        $this->_url = $_url;
    }
    
    //-----------------------------------------------
    //Try to analysis the html
    //@return bool
    public function run()
    {
        //fetch the html content from the specifiled url
        if ( $this->_url != NULL ) $this->_html = file_get_contents($this->_url);
        
        //check the and make sure the html is not null
        if ( $this->_html == NULL && $this->_html == FALSE )
            die('Error: HTML content could not be NULL');
            
        //CHARSET: fetch the charset from the source page
        $_pattern = '/<meta.+?charset=[^\w]{0,}([-\w]+)/i';
        if ( preg_match($_pattern, $this->_html, $_matches) != FALSE )
            $this->_encoding = strtoupper( $_matches[1] );
        else
            $this->_encoding = 'UTF-8';
            
        
        //convert the charset to output charset as needed
        if ( $this->_outcharset != $this->_encoding )
        {
            $this->_html = iconv($this->_encoding, $this->_outcharset.'//ignore', $this->_html);
        }
        
        //TITLE: check and fetch the title from the source page
        //@TODO: We may need to check the hx markup to determine the title
        if ( ($this->_mask & EXTRACTOR_TITLE) != 0 )
        {
            $_pattern = '/<title[^>]*?>(.*?)<\/title[^>]*?>/is';
            if ( preg_match($_pattern, $this->_html, $_matches) != FALSE )
            {
                $this->_title = preg_replace('/\s{2,}/', ' ', $_matches[1]);
            }
        }
        
        //AUTHOR: check and try to find the author value
        if ( ($this->_mask & EXTRACTOR_AUTHOR) != 0 )
            $this->_author = $this->findAuthor();
        
        //PUBTIME: check and try to find the publish time
        if ( ($this->_mask & EXTRACTOR_PUBTIME) != 0 )
            $this->_pubtime = $this->findPubtime();
        
        /**
         * check and try to extract the main text of the
         *  url page or source html string
        */
        if ( ($this->_mask & EXTRACTOR_TEXT) != 0 )
            $this->_text = $this->findText();
    }
    
    /**
     * check and find the author from the source page
     *
     * @return  string
     * @access  private
    */
    private function findAuthor()
    {
        return '';
    }
    
    /**
     * check and try to find the pubtime from the source html
     *
     * @return  string
     * @access  private
    */
    private function findPubtime()
    {
        return '';
    }
    
    /**
     * get the rate of the link words in a string
     *      And it base on the clear up of $this->_html first
     *
     * @param   $_str
     * @return  $_rate
    */
    private function getLinkWordsRate( $_str )
    {
        //clear the markup and the whitespace
        //echo ($_str), '<br />';
        $_nolinkstr = preg_replace(array('/<.*?>/is', '/[\s|　]{1,}/'), '', $_str);
        //echo $_nolinkstr,'<br />';
        //echo "+-------------------------------+\n";
        if ( strlen($_nolinkstr) == 0 ) return 1.0;
        
        //get the all the link words
        $_linkstrlen = 0;
        if ( (preg_match_all('/<a[^>]*?>(.*?)<\/a>/is', $_str, $_matches)) !== FALSE )
        {
            foreach( $_matches[1] as $_val ) $_linkstrlen += strlen($_val);
        }
        
        //echo ((float)($_linkstrlen / strlen($_nolinkstr))), "\n\n";
        return (float)($_linkstrlen / strlen($_nolinkstr));
    }
    
    /**
     * try to extract the main text of the source html string
     *  And this is the core part of the lines block algorithm
     *
     * @return  string
     * @access  private
    */
    private function findText()
    {
        $_patterns = array(
            '/<!DOCTYPE.*?>/si'				            => '',	    //line head
			'/<!--.*?-->/s'					            => '',      //all the comments
			'/<script[^>]*?>.*?<\/script[^>]*?>/si'	    => '',      //script
			'/<style[^>]*?>.*?<\/style[^>]*?>/si'	    => '',      //style sheet
			'/<textarea>.*?<\/textarea>/si'	            => '',      //clear the textarea
			'/<input[^>]*?>.*?<\/input[^>]*?>/si'	    => '',      //clear the form
            '/<iframe[^>]*?>.*?<\/iframe[^>]*?>/si'     => '',      //clear the iframe 
			'/&.{1,5};|&#.{1,5};/i'			            => ' ',     //clear the specials chars
            /*'/<.*?>/si'                               => ''*/
            '/<br[^>].*?>/is'                           => "\n",
			'/\<\/?(?!img|a|\/).(.*?)\>/is'	            => ''
        );
        
        //CLEAR: pre-process the html to clear the useless chars
        $_html = preg_replace(array_keys($_patterns), $_patterns, $this->_html);
        $_html = str_replace("\n\r", "\n", $_html);
        
        //IMAGE: replace the img markup to [IMG:$num]
        //TODO:
        
        //SPLIT: split the html after the pre-process
        $_lines = explode("\n", $_html);
        $_blocks = count($_lines) - $this->_blockWidth;
        unset($_html);
        
        $_indexdist = array();          //word num index array
        $wordNums   = 0;                //word num temp variable
        
        /*
         * make the line block, we take $this->_blocklines
         *      line as a line block and count the word number
         *  of the line block after clear up all its whitespace
        */
        $_clear = array('/<.*?>/s', '/[\s|　]{1,}/');
        for ( $i = 0; $i <= $_blocks; $i++ )
        {
            $wordNums = 0;      //clear the word counter
            $_str = '';
            for ( $j = 0; $j < $this->_blockWidth; $j++ )
            {
                $_str = preg_replace($_clear, '', $_lines[$i+$j]);
                $wordNums += strlen($_str);
            }
            
            $_str = NULL;
            
            //store the word num
            $_indexdist[] = $wordNums;
        }
        
        
        /*
         * Try to analysis the word number distribution
         *      and determinte the start & end line number,
         *  take the text bettween the start and the end as the final text
        */
        $_start = -1; $_end = -1;
        $_bstart = false; $_bend = false;
        $_ftext = '';
        
        for ( $i = 0; $i < $_blocks - 1; $i++ )
        {            
            //determinate the start line number
            if ( ! $_bstart
                && $_indexdist[$i] >= $this->_threshold
                && ($i + 3) < $_blocks && $_indexdist[$i+1] != 0
                && $_indexdist[$i+2] != 0 /*&& $_indexdist[$i+3] != 0*/ )
            {
                $_bstart = true;
                $_start = $i;
                continue;
            }
            
            //determinate the end line number
            if ( $_bstart
                && $_indexdist[$i] == 0 && $_indexdist[$i+1] == 0 )
            {
                $_end = $i;
                $_bend = true;
            }
            
            if ( ! $_bend ) continue;
            
            //get the final text
            for ( $t = $_start; $t < $_end; $t++ )
            {
                //count the bit of the link text length and the the whole length
                $_rate = $this->getLinkWordsRate($_lines[$t]);
                //echo $_lines[$t], '------------------', $_rate, "\n";
                if ( $_rate > 0.48 ) continue;
                $_ftext .= ( $_lines[$t] . "\n" );
                
                //clear the start and end mark
                $_bstart = $_bend = false;
            }
        }
        
        return $_ftext;
    }
    
    /**
     * get the charset
     *
     * @return  string
    */
    public function getEncoding()
    {
        return $this->_encoding;
    }
    
    /**
     * set the output charset
     *
     * @param   $_outcharset
    */
    public function setOutcharset( $_outcharset )
    {
        $this->_outcharset = strtoupper($_outcharset);
    }
    
    /**
     * get the analysis title
     *
     * @return  string
    */
    public function getTitle()
    {
        return $this->_title;
    }
    
    /**
     * get the final pubtime
     *
     * @return  string
    */
    public function getPubtime()
    {
        return $this->_pubtime;
    }
    
    /**
     * get the final author
     *
     * @return  string
    */
    public function getAuthor()
    {
        return $this->_author;
    }
    
    /**
     * get the final text
     *
     * @return  string
    */
    public function getText()
    {
        return $this->_text;
    }
    
    /**
     * get the final content images
     *
     * @return  Array
    */
    public function getImages()
    {
        return $this->_images;
    }
}
?>