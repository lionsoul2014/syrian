<?php
/**
 * opert user define session .
 *
 * @author chenxin <chenxin619315@gmail.com>
*/
class FileSession
{

    private $_sessname = 'OPT_SESSID';
    private $_data = array();
    
    private $_sessid = NULL;
    private $_sessfile = NULL;
    public $_save_path = NULL;
    
    public function __construct( $_save_path = NULL )
	{
        if ( $_save_path != NULL ) $this->_save_path = $_save_path;
        if ( isset( $_GET[$this->_sessname] ) )
		{
            $this->_sessid = $_GET[$this->_sessname];
            $this->_sessfile = $this->_save_path.'opt_'.$this->_sessid;
            if ( file_exists( $this->_sessfile ) )
			{
                if ( ( $_txt = file_get_contents($this->_sessfile) ) !== FALSE )
                    $this->_data = unserialize($_txt);
            }
        }
		else
		{
            $this->_sessid = md5( self::getClientIP().$_SERVER['HTTP_USER_AGENT'].time() );
            $this->_sessfile = $this->_save_path.'opt_'.$this->_sessid;
            //header('Set-cookies: '.$this->_sessname.'='.$_this->_sessid);
        }
    }
    
    //get the client IP
	public static function getClientIP()
	{
		$ip = ''; 
		if (getenv('HTTP_CLIENT_IP')) 				$ip = getenv('HTTP_CLIENT_IP'); 
		//获取客户端用代理服务器访问时的真实ip 地址
		else if (getenv('HTTP_X_FORWARDED_FOR')) 	$ip = getenv('HTTP_X_FORWARDED_FOR');
		else if (getenv('HTTP_X_FORWARDED')) 		$ip = getenv('HTTP_X_FORWARDED');
		else if (getenv('HTTP_FORWARDED_FOR')) 		$ip = getenv('HTTP_FORWARDED_FOR'); 
		else if (getenv('HTTP_FORWARDED')) 			$ip = getenv('HTTP_FORWARDED');
		else  										$ip = $_SERVER['REMOTE_ADDR'];
		return $ip;
	}
    
    public function getSessid()
	{
        return $this->_sessid;
    }
    
    public function set( $_key, $_val )
	{
        $this->_data[$_key] = &$_val;
    }
    
    public function get( $_key )
	{
        return (isset($this->_data[$_key]) ? $this->_data[$_key] : FALSE);
    }
    
    public function save()
	{
		if ( empty($this->_data) ) return FALSE;
        return file_put_contents($this->_sessfile, serialize($this->_data));
    }
}
?>