<?php
/**
 * dynmaic content file cache class.
 *
 * @author chenxin <chenxin619315@gmail.com>
*/
class FileCache implements ICache
{

    private $_length = 3000;
    public $_cache_dir = NULL;
    
    public function __construct( $_args )
    {
        if ( $_args != NULL ) {
            if ( isset($_args['cache_dir']) )
                $this->_cache_dir = $_args['cache_dir'];
            if ( isset($_args['length']) )
                $this->_length = $_args['length'];
        }
    }
    
    private function getCacheFile($_baseId, $_factor)
    {
        $path = $this->_cache_dir.str_replace('.', '/', $_baseId);
        if ( $_factor != NULL )
        {
            $path = $path.'/'.floor(($_factor / $this->_length));
            $_file = ($_factor % $this->_length).'.cache.html';
        }
        else
        {
            $_file = 'default.cache.html';
        }
        return ($path.'/'.$_file);
    }

    public function get( $_baseId, $_factor, $_time )
    {
        $_cache_file = $this->getCacheFile($_baseId, $_factor);
        //echo $_cache_file,'<br />';
        
        if ( ! file_exists( $_cache_file ) ) return FALSE;
        if ( $_time < 0 ) return file_get_contents($_cache_file);
        if ( filemtime( $_cache_file ) + $_time < time() ) return FALSE;
        
        return file_get_contents($_cache_file);
        //return $_cache_file;
    }
    
    public function set( $_baseId, $_factor = NULL, $_content )
    {
    
        $_cache_file = $this->getCacheFile($_baseId, $_factor);
        
        $path = dirname($_cache_file);
        //check and make the $path dir
        if ( ! file_exists( $path ) )
        {
            $_dir = dirname( $path );
            $_names = array();
            do {
                if ( file_exists( $_dir ) ) break;
                $_names[] = basename($_dir);
                $_dir = dirname( $_dir );
            } while ( true );
            
            for ( $i = count($_names) - 1; $i >= 0; $i-- )
            {
                $_dir = $_dir.'/'.$_names[$i];
                mkdir($_dir, 0777);
            }
            mkdir($path, 0777);
        }
        
        return file_put_contents($_cache_file, $_content);
    }
}
?>