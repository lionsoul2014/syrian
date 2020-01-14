<?php if ( ! defined('BASEPATH') ) exit('No Direct Access Allowed!');
/**
 * Syrian output manager class
 * 
 * section:
 * @header (will be the header of http response)
 * @content: standart http content
 *
 * Note: For cascade invoke a lot of method has return the instance itself
 *
 * @author  chenxin <chenxin619315@gmail.com>
 * @link    http://www.lionsoul.org/syrian
*/

//-----------------------------------------------------------------

class Output
{
    /**
     * self added http header for the output
     *
     * @access  private
    */
    private $_header = array();
    
    /**
     * output content - http data section
     *
     * @access  private
    */
    private $_final_output = '';
    
    /**
     * use zlib to compress the transfer content
     *      when the bandwidth limit the performance of your system
     *  and you should start this
     *
     * @access  private
    */
    private $_zlib_oc = false;
    private $_gzip_oc = -1;
    
    
    public function __construct()
    {
        $this->_zlib_oc = @ini_get('zlib.output_compression');

        if ( SR_CLI_MODE != true ) {
            //check and auto append the charset header
            //@Note: added at 2016/03/20
            if ( defined('SR_CHARSET') ) {
                $this->setHeader('Content-Type', 'text/html; charset= ' . SR_CHARSET);
            }

            $this->setHeader('X-Powered-By', defined(SR_POWERBY) ? SR_POWERBY : 'Syrian/2.0');
        }
    }
    
    /**
     * Enable the content transfer compression
     *  It will do nothing if $this->_zlib_oc is enabled
     *
     * @param   $_level (number between 1 - 9)
    */
    public function compress( $_level )
    {
        if ( $this->_zlib_oc == TRUE  ) return;
        
        //check and set the level
        if ( $_level >= 1 && $_level <= 9 ) {
            $this->_gzip_oc = $_level;
        }
    }
    
    /**
     * set the http response header
     *
     * @param   $_header
     * @param   $_replace
    */
    public function setHeader( $_header, $_replace )
    {
        /* If zlib.output_compress is enabled, php will compress
         *  the output data itself and it will cause bad result for broswer
         * if we modified the content-length with a wrong value
        */
        if ( $this->_zlib_oc 
            && strncasecmp($_header, 'content-length') == 0 ) {
            return;
        }
        
        $this->_header[$_header] = $_replace;
        return $this;
    }
    
    /**
     * set the final output string
     *
     * @param   $_output
    */
    public function setOutput()
    {
        $this->_final_output = $_output;
        
        return $this;
    }
    
    /**
     * Append the specifiled string to the final output string
     *
     * @param   $_str
    */
    public function append( $_str )
    {
        $this->_final_output .= $_str;
        
        return $this;
    }
    
    /**
     * Set the http status code and string
     *
     * @param   $_code
     * @param   $_string
    */
    public function setStatusHeader( $_code, $_string = '' )
    {
        static $_status = array(
            200    => 'OK',
            201    => 'Created',
            202    => 'Accepted',
            203    => 'Non-Authoritative Information',
            204    => 'No Content',
            205    => 'Reset Content',
            206    => 'Partial Content',

            300    => 'Multiple Choices',
            301    => 'Moved Permanently',
            302    => 'Found',
            304    => 'Not Modified',
            305    => 'Use Proxy',
            307    => 'Temporary Redirect',

            400    => 'Bad Request',
            401    => 'Unauthorized',
            403    => 'Forbidden',
            404    => 'Not Found',
            405    => 'Method Not Allowed',
            406    => 'Not Acceptable',
            407    => 'Proxy Authentication Required',
            408    => 'Request Timeout',
            409    => 'Conflict',
            410    => 'Gone',
            411    => 'Length Required',
            412    => 'Precondition Failed',
            413    => 'Request Entity Too Large',
            414    => 'Request-URI Too Long',
            415    => 'Unsupported Media Type',
            416    => 'Requested Range Not Satisfiable',
            417    => 'Expectation Failed',

            500    => 'Internal Server Error',
            501    => 'Not Implemented',
            502    => 'Bad Gateway',
            503    => 'Service Unavailable',
            504    => 'Gateway Timeout',
            505    => 'HTTP Version Not Supported'
        );
        
        if ( ! isset($_status[$_code]) ) exit('Error: Invalid http status code');
        if ( $_string == '' ) $_string = &$_status[$_code];
        
        //get the current server protocol
        $_protocol = isset( $_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : FALSE;
        
        //send the status header (for to replace the old one)
        if ( substr(php_sapi_name(), 0, 3) == 'cgi' ) {
            header("Status: {$_code} {$_string}", true);
        } else if ( $_protocol == 'HTTP/1.0' ) {
            header("HTTP/1.0 {$_code} {$_string}", true, $_code);
        } else {
            header("HTTP/1.1 {$_code} {$_string}", true, $_code);
        }
    }
    
    /**
     * Response the request and display the final output string
     *  with the http header also
     *
     * gzip compression will be use to compress the final output
     *  if $this->_gzip_oc is enabled
    */
    public function display( $_output = '' )
    {
        //define the output string
        if ( $_output == '' ) $_output = &$this->_final_output;

        //Try to send the server header
        if ( count($this->_header) > 0 ) {
            foreach ( $this->_header as $hKey => $hVal ) {
                header("{$hKey}: {$hVal}");
            }
        }
        
        //Try to send the server response content
        // if $this->_gzip_oc is enabled then compress the output
        if ( $this->_gzip_oc != -1 && extension_loaded('zlib') ) {
            $_cond = isset($_SERVER['HTTP_ACCEPT_ENCODING'])
                && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE);
                
            if ( $_cond ) {
                $_output = gzencode($_output, $this->_gzip_oc);
                header('Content-Encoding: gzip');  
                header('Vary: Accept-Encoding');  
                header('Content-Length: '.strlen($_output));
            }
        }
        
        echo $_output;
    }
}
