<?php
/**
 * Syrian Template Manager Class
 *      This class is the view part of the so-called MVC
 *  It is simple but it is effcient and powerful
 *
 * @author  chenxin <chenxin619315@gmail.com>
 * @link    http://www.lionsoul.org/syrian
 */

 //-------------------------------------------------------------------

class HtmlView extends AView
{
    public  $_tpl_dir       = NULL;         //template directory. end with '/'
    public  $_cache_dir     = NULL;         //cache directory, end with '/'
    public  $_cache_time    = 0;            //template compile cache time in second
    
    /**
     * Template symtab hold all the mapping
     *  added from interface assign or assoc
     *
     * @access  private
    */
    private $_symbol        = array();      
    
    /**
     * Template compile regex rules
     *
     * @access  private
    */
    private $_rules         = array(
        '/<\?([^=])/'           => '<?php $1',
        //<?=
        '/<\?=/'                => '<?php echo ',
        //for ( val : {array} )
        '/for\s*\(\s*\$([a-z0-9_]+)\s*:\s*\$\{([a-z0-9_]+)\}\s*\)/i'
                                => 'foreach ( \$this->_symbol[\'$2\'] as \$$1 )',
        
        //${arr}
        '/\$\{(\$?[a-z0-9_]+)\}/i'
                                => '\$this->_symbol["$1"]',
        //${arr.key}
        '/\$\{(\$?[a-z0-9_]+)\.(\$?[a-z0-9_]+)\}/i'
                                => '\$this->_symbol["$1"]["$2"]',
        //${arr.key1.key2}
        '/\$\{(\$?[a-z0-9_]+)\.(\$?[a-z0-9_]+)\.(\$?[a-z0-9_]+)\}/i'
                                => '\$this->_symbol["$1"]["$2"]["$3"]',
        
        //#{class:method(args)}
        '/#\{([a-z0-9_]+):([a-z0-9_]+)\((.*?)\)\s*\}/i'
                                => '$1::$2($3)',
        //#{${object}.method(args)}
        //#{$object.method(args)}
        '/#\{\$(.*?)\.([a-z0-9_]+)\((.*?)\)\s*\}/i'
                                => '\$$1->$2($3)',
        //#{func(args)}, ${$func(args)}, #{${func}(args)}
        '/#\{(.*?)\((.*?)\)\}/' => '$1($2)',
        
        //$arr.key1.key2
        '/(\$[a-z0-9_]+)\.(\$?[a-z0-9_]+)\.(\$?[a-z0-9_]+)/i'
                                => '$1["$2"]["$3"]',
        //$arr.key
        '/(\$[a-z0-9_]+)\.(\$?[a-z0-9_]+)/i'
                                => '$1["$2"]',
                        
        //include|require file
        '/(include|require)\s+([a-z0-9_\.\/-]+)/i'
                                => '$1 \$this->getIncludeFile(\'$2\')'
    );

    /**
     * construct method to intialize the class
     *
     * @param   $_conf
    */
    public function __construct( &$_conf )
    {
        ///check and initialize the global item
        if ( isset( $_conf['cache_time'] ) ) $this->_cache_time = $_conf['cache_time'];
        if ( isset( $_conf['tpl_dir'] ) )    $this->_tpl_dir    = $_conf['tpl_dir'];
        if ( isset( $_conf['cache_dir'] ) )  $this->_cache_dir  = $_conf['cache_dir'];
    }

    /**
     * internal method to compile the specifiled template
     *      to a specifield cache file
     *
     * @param   $_tpl_file
     * @param   $_cache_file
    */
    private function compile( $_tpl_file, $_cache_file )
    {
        //1.get the cotent of the template file
        $_TPL = file_get_contents($_tpl_file);
        if ( $_TPL === FALSE )
            die('Error: Unable to get the content of the template file '.$_tpl_file);
            
        //2. regex replace
        $_TPL = preg_replace( array_keys($this->_rules), $this->_rules, $_TPL);
        
        if ( ! file_exists( $_cache_file ) )
        {
            $_path = dirname($_cache_file);
            $_names = array();
            do {
                if ( file_exists( $_path ) ) break;
                $_names[] = basename( $_path );
                $_path = dirname($_path);
            } while ( true );
            
            for ( $i = count($_names) - 1; $i >= 0; $i-- )
            {
                $_path .= '/'.$_names[$i];
                mkdir($_path, 0777);
            }
        }
        
        //3. put the replaced content into the cache file
        if ( file_put_contents($_cache_file, $_TPL, LOCK_EX) != strlen($_TPL) )
            die('Error: Unable to write the content to the cache file '.$_cache_file);
    }
    
    /**
     * Internal method to check the specifield cache file
     *      is still valid or not
     *
     * @param   $_cache_file
     * @return  bool
    */
    private function isCached( $_cache_file )
    {
        if ( ! file_exists( $_cache_file ) ) return false;
        if ( $this->_cache_time < 0 ) return true;
        if ( time() - filemtime( $_cache_file ) > $this->_cache_time ) return false;
        return true;
    }
    
    /**
     * Assign a mapping to the view
     *
     * @param   $_name
     * @param   $_value
    */
    public function assign( $_name, $_value )
    {
        $this->_symbol[$_name] = &$_value;
        
        return $this;
    }
    
    /**
     * associate a mapping with the specifield name
     *  to $_value, and $_name is just a another quote of $_value
     *
     * @param   $_name
     * @param   $_value
    */
    public function assoc( $_name, &$_value )
    {
        $this->_symbol[$_name] = &$_value;
        
        return $this;
    }
    
    /**
     * Load data from a array, take the key as the new key
     *      and the value as the new value.
     *
     * @param   $_array
    */
    public function load( $_array )
    {
        if ( ! empty($_array) )
            $this->_symbol = array_merge($this->_symbol, $_array);
            
        return $this;
    }
    
    /**
     * Intermal method to handler include|require markup
     *      analysis and require the speicifled file
     *
     * @param   $_inc_file
     * @return  String the cache file to include
    */
    private function getIncludeFile( $_inc_file )
    {
        $_tpl_dir = $this->_tpl_dir;
        $_cache_dir = $this->_cache_dir;
        
        if ( strpos($_inc_file, '../') !== FALSE )
        {
            $_tarr = explode('/', $_inc_file);
            
            foreach ( $_tarr as $_val )
            {
                if ( $_val != '..' ) break;
                $_tpl_dir = dirname($_tpl_dir);
                $_cache_dir = dirname($_cache_dir);
                $_inc_file = str_replace('../', '', $_inc_file);
            }
        }

        $_tpl_file = $_tpl_dir.'/'.$_inc_file;
        $_cache_file = $_cache_dir.'/'.$_inc_file.'.php';
        
        //echo $_tpl_file,'<br />';
        //echo $_cache_file,'<br />';
        if ( ! $this->isCached( $_cache_file ) )
        {
            $this->compile( $_tpl_file, $_cache_file );
        }
        
        return $_cache_file;
    }
    
    /**
     * return the executed html content
     *      $this->display will be invoke to finish the job
     *
     * @param   $_tpl_file
     * @param    $sanitize sanitize the content ?
     * @return  string the executed html text
    */
    public function getContent( $_tpl_file = NULL, $sanitize = false )
    {
        $_cache_file = $this->_cache_dir.$_tpl_file.'.php';
        $_tpl_file = $this->_tpl_dir.$_tpl_file;
        
        //check the cache file is valid or not
        if ( ! $this->isCached( $_cache_file ) )
        {
            $this->compile($_tpl_file, $_cache_file);
        }
        
        //create a buffer and fetch the executed html content
        ob_start();
        require $_cache_file;
        $ret = ob_get_contents();
        ob_end_clean();
        
        //return the executed html string
        return $sanitize ? $this->sanitize($ret) : $ret;
    }
}
?>
