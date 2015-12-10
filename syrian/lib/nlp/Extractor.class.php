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
 defined('EXTRACTOR_IMAGE')      or define('EXTRACTOR_IMAGE',    1 << 5);

/**
 * normal extractor, collect just title, text and the publish time
 */
 defined('EXTRACTOR_NORMAL')     or define('EXTRACTOR_NORMAL',   EXTRACTOR_TITLE
    | EXTRACTOR_PUBTIME | EXTRACTOR_TEXT);

/**
 * control mask to analysis all the fileds
 */
 defined('EXTRACTOR_ALL')        or define('EXTRACTOR_ALL',      EXTRACTOR_TITLE
    | EXTRACTOR_PUBTIME | EXTRACTOR_AUTHOR | EXTRACTOR_TEXT | EXTRACTOR_IMAGE);
 
 
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
    private     $_html;                     //response html string
    private     $_url;                      //request url
    private     $_imgs;                     //all the images markup
    private     $_urlinfo;                  //current request url info
    
    private     $_blockWidth    = 3;
    private     $_threshold     = 225;     //
    private     $_startblocks   = 2;       //start position to determinate check rows
    private     $_linkRate      = 0.30;    //link rate to ignore the line
    private     $_steplines     = 6;       //maximum whitespace line for the next text block
    private     $_terminalrules = array('/^\s{0,}正文已结束/', '/^\s(0,)评论/i', '/^\s{0,}tag:/i');
    private     $_continuerules = array('/^\s{0,}相关阅读/', '/^\s{0,}相关专题/', '/^\s{0,}分享到/');

    //mark to quote the analysis block while analysis to find the text
    //must be an array
    private        $_blockquote    = NULL;
    
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
        $this->_imgs        = NULL;

        return $this;
    }
    
    /**
     * set the analysis html string
     *
     * @param   $_html
    */
    public function setHtml( $_html )
    {
        $this->_html = $_html;
        return $this;
    }
    
    /**
     * set the current request url
     *
     * @param   $_url
    */
    public function setUrl( $_url )
    {
        $this->_url = $_url;
        return $this;
    }
    
    //-----------------------------------------------
    //Try to analysis the html
    //@return bool
    public function run()
    {
        //fetch the html content from the specifiled url
        if ( $this->_url != NULL ) $this->_html = file_get_contents($this->_url);
        
        //check the and make sure the html is not null
        if ( $this->_html == NULL || $this->_html == FALSE )
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
        if ( ($this->_mask & EXTRACTOR_AUTHOR) != 0 )   $this->findAuthor();
        
        //PUBTIME: check and try to find the publish time
        if ( ($this->_mask & EXTRACTOR_PUBTIME) != 0 )  $this->findPubtime();
        
        /**
         * check and try to extract the main text of the
         *  url page or source html string
        */
        if ( ($this->_mask & EXTRACTOR_TEXT) != 0 )     $this->findText();
    }
    
    /**
     * check and find the author from the source page
     *
     * @return  string
     * @access  private
    */
    private function findAuthor()
    {
        return $this->_author = NULL;
    }
    
    /**
     * check and try to find the pubtime from the source html
     *
     * @return  string
     * @access  private
    */
    private function findPubtime()
    {
        return $this->_pubtime = NULL;
    }
    
    /**
     * set the config that will make deffirent result to the extractor
     *  so you could make a self define to fit deffirent data source
     *
     * @param   $_config
    */
    public function config( $_config )
    {
        //set the threshold
        if ( isset($_config['threshold']) ) $this->_threshold = $_config['threshold'];
        //set the block width
        if ( isset($_config['blockwith']) ) $this->_blockWidth = $_config['blockwidth'];
        //set the start deteminate blocks number
        if ( isset($_config['startblocks']) ) $this->_startblocks = $_config['startblocks'];
        //set the link rate
        if ( isset($_config['linkrate']) )  $this->_linkRate = $_config['linkrate'];
        //set the maximum space lines
        if ( isset($_config['steplines']) ) $this->_steplines = $_config['steplines'];
        //set the block quote
        if ( isset($_config['blockquote'])) $this->_blockquote    = $_config['blockquote'];
    }
    
    /**
     * Add a terminal rule to the terminal global rule array
     *
     * @param   $_rule
    */
    public function addTerminalRule( $_rule )
    {
        $this->_terminalrules[] = $_rule;
    }
    
    /**
     * get the rate of the link words in a string
     *      And it base on the clear up of $this->_html first
     *
     * @param   $_str
     * @return  $_rate
    */
    private static function getLinkRates( $_str )
    {
        //clear the markup and the whitespace
        $_nolinkstr = preg_replace(array('/<.*?>/is', '/[\s|　]{1,}/'), '', $_str);
        if ( strlen($_nolinkstr) == 0 ) return 1.0;
        
        //get the all the link words
        $_linkstrlen = 0;
        if ( (preg_match_all('/<a[^>]*?>(.*?)<\/a>/is', $_str, $_matches)) !== FALSE )
        {
            foreach( $_matches[1] as $_val ) $_linkstrlen += strlen($_val);
        }
        
        return (float)($_linkstrlen / strlen($_nolinkstr));
    }
    
    /**
     * internal method to standarlize the
     *      specifield image source url
     *
     * @param   $_url       And you should make sure the $_url
     *              is not a valid request url
     * @return  string
    */
    private function stdImageSourceLink( $_url )
    {
        $_ret = '';
        
        //append the scheme as needed
        //if ( stripos($_url, 'http://') === FALSE )
        $_ret = 'http://' . $this->_urlinfo['host'];
            
        //std the path part of the url
        if ( $_url[0] == '/' ) $_ret .= $_url;
        else if ( isset($this->_urlinfo['path']) )        //relative path
        {
            $_base = dirname($this->_urlinfo['path']);
            $_arr = explode('/', $this->_urlinfo['path']);
            foreach ( $_arr as $v )
                if ( $v == '..' ) $_base = dirname($_base);
           
            if ( $_base[0] != '/' ) $_base = '/'.$_base;
            $_ret .= $_base . '/' . $_url;
        }
        
        return $_ret;
    }
    
    /**
     * Aditional keywords check
     *  internal method to check the specifiled line matches the
     *      words in $_array
     *
     * @param   $_linestr
     * @return  $_array
     * @return  bool
    */
    private static function matches( $_linestr, $_array )
    {
        foreach ( $_array as $_val )
        {
            if ( preg_match($_val, $_linestr ) == 1 ) return true;
        }
        
        return false;
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
        if ( ($this->_mask & EXTRACTOR_IMAGE) == 0 )
            $_imgkey = '/<\/?(?!a|\/).*?>/is';
        else
        {
            $_imgkey = '/<\/?(?!img|a|\/).*?>/is';
            $this->_urlinfo = parse_url($this->_url);
            $this->_imgs = array(); 
        }
            
            
        $_patterns = array(
            '/<head[^>]*?>.*?<\/head>/is'           => '',        //clear the head
            '/<!--.*?-->/s'                            => "\n",      //all the comments
            '/<script[^>]*?>.*?<\/script[^>]*?>/si'    => "\n",      //script
            '/<style[^>]*?>.*?<\/style[^>]*?>/si'    => "\n",      //style sheet
            '/<form[^>]*?>.*?<\/form[^>]*?>/is'     => "\n",
            '/<textarea>.*?<\/textarea>/si'            => '',      //clear the textarea
            '/<iframe[^>]*?>.*?<\/iframe[^>]*?>/si' => "\n",      //clear the iframe 
            '/&.{1,5};|&#.{1,5};/i'                    => ' ',     //clear the specials chars
            '/<\/h\d>/i'                            => "\n",
            '/<p[^>]*?>(<img[^>]*?>)<\/p>/is'       => '$1',
            '/<a[^>]*?>(<img[^>]*?>)<\/a>/is'       => '$1',
            $_imgkey                                => '',
        );
        
        //CLEAR: pre-process the html to clear the useless markup
        $_html = preg_replace(array_keys($_patterns), $_patterns, $this->_html);
        $_html = str_replace("\n\r", "\n", $_html);
        
        //IMAGE: replace the img markup to {IMG:$num:$alt/}
        $_patterns = NULL;
        //TODO: you may have to get its real_src for some sites
        $_patterns = '/<img.*?src=["|\'](.*?)["|\'][^>]*?>/is';
        if ( preg_match_all($_patterns, $_html, $_matches) !== FALSE )
        {
            $_counter = 0;
            foreach ( $_matches[0] as $_val )
            {
                /*
                 * find the value of the alt properties of the image markup
                 *  default to a whitespace
                 * We count the alt in so we could fetch all the images
                 *      markup in the main text of the source page
                */
                $_alt = ' ';
                if ( ($pos = stripos($_val, 'alt=')) !== FALSE )
                {
                    if ( in_array($_val[$pos+4], array('\'', '"')) )
                        $endmark = $_val[$pos+4];
                    else $endmark = ' ';
                    
                    $epos = strpos($_val, $endmark, $pos + 5);
                    $_alt = substr($_val, $pos + 5, $epos - $pos - 5);
                }
                
                //replace the img markup to a specifield self define style
                $_html = self::replace($_val, '{IMG:'.$_counter.':"'.$_alt.'"/}', $_html);
                
                $_imgsrc = $_matches[1][$_counter];
                //Standardize the image markup
                if ( stripos($_imgsrc, 'http://') === FALSE )
                    $_imgsrc = $this->stdImageSourceLink($_imgsrc);
                $this->_imgs[] = $_imgsrc;
                $_counter++;
            }
        }
        
        //LINK: clear the link that it href is # or javascript:;
        $_patterns = '/<a[^>]*?>(.*?)<\/a>/is';
        if ( preg_match_all($_patterns, $_html, $_matches) !== FALSE )
        {
            foreach ( $_matches[0] as $_val )
            {
                 /*
                 * find the value of the href properties of the a markup
                 *  and clear the useless a link markup
                */
                $_href = ' ';
                if ( ($pos = stripos($_val, 'href=')) !== FALSE )
                {
                    if ( in_array($_val[$pos+5], array('\'', '"')) )
                        $endmark = $_val[$pos+5];
                    else $endmark = ' ';
                    
                    $epos = strpos($_val, $endmark, $pos + 6);
                    $_href = substr($_val, $pos + 6, $epos - $pos - 6);
                }
                
                if ( ( isset($_href[0]) && $_href[0] == '#')       //start with '#'
                        || stripos($_href, 'javascript:') !== FALSE )
                    $_html = str_replace($_val, "\n", $_html);
            }
        }
        
        //SPLIT: split the html after the pre-process
        $_lines = explode("\n", $_html);
        $_blocks = count($_lines) - $this->_blockWidth;
        unset($_html);
        unset($_patterns);
        
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
        $_textblocks = array();
        
        for ( $i = 0; $i < $_blocks; $i++ )
        {
            //determinate the start line number
            if ( ! $_bstart
                && $_indexdist[$i] >= $this->_threshold
                    && ( $i + $this->_startblocks ) < $_blocks  )
            {
                $_check = true;
                for ( $x = 0; $x < $this->_startblocks; $x++ )
                {
                    if ( $_indexdist[$i+$x] == 0 )
                    {
                        $_check = false;
                        break;
                    }
                }
                
                if ( $_check )
                {
                    $_bstart = true;
                    $_start = $i;
                    continue;
                }
            }
            
            if ( ! $_bstart ) continue;
            
            //determinate the end line number
            if ( $_indexdist[$i] == 0 && $_indexdist[$i+1] == 0
                    /*&& $_indexdist[$i+2] == 0*/ )
            {
                $_end = $i;
                $_bend = true;
            }
            
            if ( ! $_bend ) continue;
            
            //store the block start and end offset
            $_textblocks[] = array($_start, $_end);
            $_bstart = $_bend = false;
        }
        
        
         //FINAL: Now try to get the final text
        $_textblockscount = count($_textblocks);
        $_keepgoing = true;
        $_ftext = '';
        for ( $i = 0; $_keepgoing && $i < $_textblockscount; $i++ )
        {
            $_block = $_textblocks[$i];
            $_blockstr = '';
            
            //append the text of the current
            //      text block the final text string
            for ( $t = $_block[0]; $t < $_block[1]; $t++ )
            {
                //check the terminal words
                if ( self::matches($_lines[$t], $this->_terminalrules) )
                {
                    $_keepgoing = false;
                    break;
                }
                
                //check the continue words
                if ( self::matches($_lines[$t], $this->_continuerules) )
                {
                    continue;
                }
                
                $_blockstr .= $_lines[$t] . "\n";
            }
            
            $_rate = self::getLinkRates($_blockstr);
            if ( $_rate > $this->_linkRate ) continue;

            //add the block quotes if was seted
            if ( $this->_blockquote == NULL ) $_ftext .= $_blockstr . "\n";
            else $_ftext .= $this->_blockquote[0].$_blockstr.$this->_blockquote[1]."\n";

            $_blockstr = NULL;
        }
        
        //restore the text images
        if ( ($this->_mask & EXTRACTOR_IMAGE) != 0 )  $this->restoreTextImages($_ftext);
        
        
        $this->_text = &$_ftext;
    }
    
    /**
     * fetch all the <IMG:\d:"alt"/> markup and
     *  replace them the specifield images markup of the source page
     *
     * @param   $_str
     * @return  string
    */
    private function restoreTextImages( &$_str )
    {
        $_pattern = '/{IMG:(\d{1,}):".*?"\/}/is';
        
        if ( preg_match_all($_pattern, $_str, $_matches ) == FALSE ) return;
        
        $_counter = 0;
        foreach ( $_matches[1] as $idx )
        {
            $_img_src = '<IMG src="'.$this->_imgs[$idx].'"/>'."\n";
            $_str = str_replace($_matches[0][$_counter], $_img_src, $_str);
            //take the content images
            $this->_images[] = &$this->_imgs[$idx];
            $_counter++;
        }
    }
    
    /**
     * A static method to:
     *  Looks for the first occurence of $needle in $haystack
     *      and replaces it with $replace
     *
     * @param   $needle
     * @param   $replace
     * @param   $haystack
    */
    public static function replace($needle, $replace, $haystack)
    {
        //Find the first occur of the $needle
        $pos = strpos($haystack, $needle);
        if ($pos === false) return $haystack;
        return substr_replace($haystack, $replace, $pos, strlen($needle));
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
     * @param   $idx    the images index
     * @return  Mixed(Array or the specifled image url)
    */
    public function getImages( $idx = -1 )
    {
        if ( $idx >= 0 && $idx < count($this->_images) )
            return $this->_images[$idx];
        return $this->_images;
    }
}
?>
