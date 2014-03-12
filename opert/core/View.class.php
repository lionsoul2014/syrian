<?php
/**
 * Opert template manage class.
 *
 * @author chenxin<chenxin619315@gmail.com>
 */
class View {

    public  $_tpl_dir       = NULL;         //template directory. end with '/'
    public  $_cache_dir     = NULL;         //cache directory, end with '/'
    public  $_cache_time    = 0;            //template compile cache time in second
    private $_symbol        = array();      //template symtab
    
    private $_rules         = array(
        '/<\?([^=])/' => '<?php $1',
        //<?=
        '/<\?=/'    => '<?php echo ',
        //for ( val : {array} )
        '/for\s*\(\s*\$([a-z0-9_]+)\s*:\s*\$\{([a-z0-9_]+)\}\s*\)/i'
                        => 'foreach ( \$this->_symbol[\'$2\'] as \$$1 )',
        
        //${arr}
        '/\$\{(\$?[a-z0-9_]+)\}/i'    => '\$this->_symbol["$1"]',
        //${arr.key}
        '/\$\{(\$?[a-z0-9_]+)\.(\$?[a-z0-9_]+)\}/i'  => '\$this->_symbol["$1"]["$2"]',
        //${arr.key1.key2}
        '/\$\{(\$?[a-z0-9_]+)\.(\$?[a-z0-9_]+)\.(\$?[a-z0-9_]+)\}/i'
                        => '\$this->_symbol["$1"]["$2"]["$3"]',
        
        //#{class:method(args)}
        '/#\{([a-z0-9_]+):([a-z0-9_]+)\((.*?)\)\s*\}/i' => '$1::$2($3)',
        //#{${object}.method(args)}
        //#{$object.method(args)}
        '/#\{\$(.*?)\.([a-z0-9_]+)\((.*?)\)\s*\}/i' => '\$$1->$2($3)',
        //#{func(args)}, ${$func(args)}, #{${func}(args)}
        '/#\{(.*?)\((.*?)\)\}/' => '$1($2)',
        
        //$arr.key1.key2
        '/(\$[a-z0-9_]+)\.(\$?[a-z0-9_]+)\.(\$?[a-z0-9_]+)/i' => '$1["$2"]["$3"]',
        //$arr.key
        '/(\$[a-z0-9_]+)\.(\$?[a-z0-9_]+)/i' => '$1["$2"]',
                        
        //include|require file
        '/(include|require)\s+([a-z0-9\.\/-]+)/i'  => '$1 \$this->getIncludeFile(\'$2\')'
    );

    public function __construct( $_cache_time = 0,
                    $_tpl_dir = NULL, $_cache_dir = NULL )
    {
        $this->_cache_time = $_cache_time;
        if ( $_tpl_dir != NULL ) $this->_tpl_dir = $_tpl_dir;
        if ( $_cache_dir != NULL ) $this->_cache_dir = $_cache_dir;
    }

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
    
    private function isCached( $_cache_file )
    {
        if ( ! file_exists( $_cache_file ) ) return false;
        if ( $this->_cache_time < 0 ) return true;
        if ( time() - filemtime( $_cache_file ) > $this->_cache_time ) return false;
        return true;
    }
    
    public function assign( $_name, $_value )
    {
        $this->_symbol[$_name] = &$_value;
    }
    
    public function assoc( $_name, &$_value )
    {
        $this->_symbol[$_name] = &$_value;
    }
    
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
     * display the data to the specified view template .
     *
     * @param   $_tpl_file
    */
    public function display( $_tpl_file )
    {
        $_cache_file = $this->_cache_dir.$_tpl_file.'.php';
        $_tpl_file = $this->_tpl_dir.$_tpl_file;
        //check the cache file is valid or not
        if ( ! $this->isCached( $_cache_file ) )
        {
            $this->compile($_tpl_file, $_cache_file);
        }
        require $_cache_file;
    }
    
    public function getExecutedHtml( $_tpl_file )
    {
        ob_start();     //create a buffer.
        $this->display($_tpl_file);
        $_ret = ob_get_contents();
        ob_end_clean();
        return $_ret;
    }
}
?>