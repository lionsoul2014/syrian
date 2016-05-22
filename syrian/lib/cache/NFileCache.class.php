<?php
/**
 * dynmaic content file cache class version 2
 *
 * @author chenxin <chenxin619315@gmail.com>
*/
class NFileCache implements ICache
{
    private $_length    = 2000;
    public  $_file_ext  = '.cache';
    public  $_cache_dir = NULL;
    public  $_cache_f   = NULL;
    public  $upt        = false;

    public $_baseKey    = NULL;
    public $_factor     = NULL;
    public $_fname      = NULL;
    public $_ttl        = 0;
    
    public function __construct( $_args=NULL )
    {
        if ( $_args != NULL ) {
            if ( isset($_args['cache_dir']) )   $this->_cache_dir = $_args['cache_dir'];
            if ( isset($_args['length']) )      $this->_length = $_args['length'];
            if ( isset($_args['file_ext']) )    $this->_file_ext = $_args['file_ext'];
        }
    }

    //set the baseKey
    public function baseKey( $_baseKey )
    {
        if ( $this->_baseKey == $_baseKey ) {
            return $this;
        }

        $this->_baseKey = $_baseKey;
        $this->upt = true;
        return $this;
    }

    //set the Factor
    public function factor( $_factor )
    {
        if ( $_factor == $this->_factor ) {
            return $this;
        }

        $this->_factor = $_factor;
        $this->upt = true;
        return $this;
    }

    //set the file name
    public function fname( $_fname )
    {
        if ( $_fname == $this->_fname ) {
            return $this;
        }

        $this->_fname = $_fname;
        $this->upt = true;
        return $this;
    }

    //set the global time to live seconds
    public function setTtl($_ttl)
    {
        $this->_ttl = $_ttl;
        return $this;
    }
    
    private function getCacheFile()
    {
        if ( $this->upt == false 
            && $this->_cache_f != NULL ) {
            return $this->_cache_f;
        }

        //convert the baseKey to cache path
        $path = $this->_cache_dir.str_replace('.', '/', $this->_baseKey);

        if ( $this->_factor == NULL ) {
            $_file = ($this->_fname==NULL) ? 'default' : $this->_fname;
        } else {
            $path = $path.'/'.floor(($this->_factor / $this->_length));
            if ( $this->_fname == null ) $_file = ($this->_factor % $this->_length);
            else $_file = "{$this->_fname}";
        }

        //return the cache file path
        $this->_cache_f = $path.'/'.$_file.$this->_file_ext;
        return $this->_cache_f;
    }

    public function get( $_time=NULL )
    {
        $_cache_file = $this->getCacheFile();
        if ( $_time === NULL ) $_time = $this->_ttl;
        
        if ( ! file_exists( $_cache_file ) ) return FALSE;
        if ( $_time < 0 ) return file_get_contents($_cache_file);
        if ( filemtime( $_cache_file ) + $_time < time() ) return FALSE;
        
        return file_get_contents($_cache_file);
    }
    
    public function set( $_content, $_ttl = NULL )
    {
        //get the cache file
        $_cache_file = $this->getCacheFile();
        
        $path = dirname($_cache_file);
        //check and make the $path dir
            if ( ! file_exists( $path ) ) {
            $_dir = dirname( $path );
            $_names = array();
            do {
                if ( file_exists( $_dir ) ) break;
                $_names[] = basename($_dir);
                $_dir = dirname( $_dir );
            } while ( true );
            
            for ( $i = count($_names) - 1; $i >= 0; $i-- ) {
                $_dir = $_dir.'/'.$_names[$i];
                mkdir($_dir, 0777);
            }

            mkdir($path, 0777);
        }
        
        //set the cache content
        return file_put_contents($_cache_file, $_content, LOCK_EX);
    }

    //remove the cache
    public function remove()
    {
        //get the cache file
        $_cache_file = $this->getCacheFile();
        if ( ! file_exists($_cache_file) ) return false;

        return @unlink($_cache_file);
    }
}
?>
