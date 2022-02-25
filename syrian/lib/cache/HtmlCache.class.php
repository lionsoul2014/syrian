<?php
/**
 * static html cache class . <br />
 *
 * @author chenxin <chenxin619315@gmail.com>
*/
class HtmlCache
{
    
    public $_html_dir = NULL;
    
    public function __construct( $_html_dir = NULL )
    {
        if ( $_html_dir != NULL ) $this->_html_dir = $_html_dir;
    }
    
    public function invoke( $_url, $_args = NULL, $_cache_file )
    {
        if ( is_array( $_args ) ) {
            $_str = '';
            foreach ( $_args as $_key => $_val )
                $_str .= $_str == '' ? $_key.'='.$_val : '&'.$_key.'='.$_val;
            unset($_args);
            $_args = $_str;
        }
        if ( $_args != NULL ) $_url .= '?'.$_args;
        $_ret = Http::get($_url);
        
        $_html_file = $this->_html_dir.str_replace('.', '/', $_cache_file).'.html';
        //check and make the file directory
        if ( ! file_exists( $_html_file ) )
        {
            $_dir = dirname( $_html_file );
            $_names = array();
            do {
                if ( file_exists( $_dir ) ) break;
                $_names[] = basename($_dir);
                $_dir = dirname( $_dir );
            } while ( true );
            
            for ( $i = count($_names) - 1; $i >= 0; $i-- )
            {
                $_dir = $_dir.'/'.$_names[$i];
                mkdir($_dir, 0x777);
            }
        }
        
        //self directory make
        return file_put_contents($_html_file, $_ret['body']);
    }
}