<?php
/*
 * smtp class. <br />
 * 
 * @author	chenxin<chenxin619315@gmail.com>
 */
class Smtp
{
	
	private $Smtp_Host;
	private $Smtp_user;	
	private $Smtp_Pass;
	private $Smtp_Port;
	
	private $Host_Name = 'localhost';
	private $Smtp_Auth;  //auth or not
	private $Time_Out;
	private $_debug = false;
	
	private $Smtp_Socket = NULL;
	
	public function __construct($_host, $_port, $_auth = false,
		 $_user = '', $_pass = '', $time_out = 30)
	{
		$this->Smtp_Host = $_host;
		$this->Smtp_Port = $_port;
		$this->Smtp_Auth = $_auth;
		$this->Smtp_user = $_user;
		$this->Smtp_Pass = $_pass;
		
		if ( $this->_debug )
		{
			echo 'host：'.$this->Smtp_Host.'<br />';
			echo 'port：'.$this->Smtp_Port.'<br />';
			echo 'user：'.$this->Smtp_Pass.'<br />';
		} 
		$this->Time_Out  = $time_out;
	}
	
	/*Main Send Mail*/
	public function Send_Mail($_to, $_from, $_subject = '', $_body = '',
		 $mile_Type = '', $_cc = '', $_bcc = '', $additional_headers = '')
	{
		$from = $this->Get_Address( $this->Strip_Comment($_from) );
		$_body = preg_replace('/(^|(\r\n))(\.)/', "$1.$3", $_body);
		$_header = "MIME-Version: 1.0\r\n";
		
		switch ( strtoupper($mile_Type) )
		{
			case 'HTML':
				$_header .= "Content-Type: text/html\r\n";
				break;
		}
		$_header .= "TO: ".$_to."\r\n";
		if ( $_cc != '' ) $_header .= "Cc: ".$_cc."\r\n";
		$_header .= "From: $_from<".$_from.">\r\n";
		$_header .= "Subject: ".$_subject."\r\n";
		$_header .= $additional_headers;
		$_header .= "Date: ".date('r')."\r\n";
		$_header .= "X-Mailer: By ".PHP_OS." (PHP/".phpversion().")\r\n";
		list($msec, $sec) = explode(' ', microtime());
		$_header .= "Message-ID: <".date('YmdHis', $sec).".".($msec*1000000).".".$from.">\r\n";
		
		$toArr = explode(';', $_to);
		if ( empty($toArr) ) return FALSE;
		
		$_sended = TRUE;
		foreach ( $toArr as $smtp_to )
		{
			$smtp_to = $this->Get_Address($smtp_to);
			$this->Open_Smtp_Socket($smtp_to);
			if ( $this->Smtp_Socket == NULL )
			{
				$_sended = FALSE;
				continue;
			}	
			if ( ! $this->Smtp_Send($this->Host_Name, $from, $smtp_to, $_header, $_body) )
			{
				$_sended = FALSE;
			}
			fclose($this->Smtp_Socket);
		}
		return $_sended;
	}
	
	//send message
	private function Smtp_Send($_helo, $_from, $_to, $_header, $_body = '')
	{
		if ( ! $this->Run_Cmd('HELO', $_helo) ) return FALSE;
		//auth
		if ( $this->Smtp_Auth )
		{
			if ( ! $this->Run_Cmd('AUTH LOGIN', base64_encode($this->Smtp_user)) ) return FALSE;
			if ( $this->_debug ) echo fgets($this->Smtp_Socket, 512);
			if ( ! $this->Run_Cmd("", base64_encode($this->Smtp_Pass)) ) return FALSE;
		}
		
		if ( ! $this->Run_Cmd('MAIL', 'FROM:<'.$_from.'>') ) return false;
		if ( ! $this->Run_Cmd('RCPT', 'TO:<'.$_to.'>') ) return FALSE;
		if ( ! $this->Run_Cmd('DATA') ) return FALSE; 
		fputs($this->Smtp_Socket, $_header."\r\n".$_body);
		fputs($this->Smtp_Socket, "\r\n.\r\n");
		if ( ! $this->Smtp_Ok() ) return FALSE;
		if ( ! $this->Run_Cmd('QUIT') ) return FALSE;
		return TRUE;
	}
	
	/*
	 * start the socket
	 */
	private function Open_Smtp_Socket( $_address = '' )
	{
		if ( $this->Smtp_Host == '' )
		{
			$_domain = preg_replace("/^.+@([^@]+)$/", "$1", $_address);
			if ( ! @getmxrr($_domain, $MXHOSTS))  return FALSE;
			foreach ($MXHOSTS as $s_Host)
			{
				$this->Smtp_Socket = @fsockopen($s_Host, $this->Smtp_Port,
					$err_no, $err_str, $this->Time_Out);
				if ( $this->Smtp_Socket && $this->Smtp_Ok() ) break;
			}
		}
		else
		{
			$this->Smtp_Socket = @fsockopen($this->Smtp_Host, $this->Smtp_Port,
			 	 $err_no, $err_str, $this->Time_Out);	
		}
		//debug #err_no and #err_str
	}
	
	//check the response status code
	private function Smtp_Ok()
	{
        $_res = str_replace("\r\n", '', fgets($this->Smtp_Socket, 512));
        if ( ! preg_match('/^[23]/', $_res))
		{
            fputs($this->Smtp_Socket, "QUIT\r\n");
            fgets($this->Smtp_Socket, 512);
            return FALSE;
        }
        return TRUE;
    }
	
	//send a cmd to smtp server
	private function Run_Cmd($_cmd, $_args = '')
	{
		if ( $_args != '' )
		{
			if ( $_cmd == '' ) $_cmd = $_args;
			else $_cmd = $_cmd." ".$_args;
			//$_cmd == ''?$_cmd = $_args:$_cmd." ".$_args;
		}
		
		fputs($this->Smtp_Socket, $_cmd."\r\n");
		return $this->Smtp_Ok();  //return the response code
	}
		
	private function Get_Address($_address)
	{
        $_address = preg_replace("/([ \t\r\n])+/", "", $_address);
        $_address = preg_replace("/^.*<(.+)>.*$/", "$1", $_address);
        return $_address;
    }
	
	//filter all the comment content
	private function Strip_Comment($_address)
	{
        $_pattern = "/\([^()]*\)/";
        while ( preg_match($_pattern, $_address) ) 
            $_address = preg_replace($_pattern, '', $_address);
        return $_address;
    }
}
?>