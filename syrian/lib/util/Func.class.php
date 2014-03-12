<?php
//transfer compress function
function ob_gzip( $_buffer )
{
	if ( extension_loaded('zlib') && isset( $_SERVER['HTTP_ACCEPT_ENCODING'] )
		&& strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE ) {
		$_buffer = gzencode($_buffer, 9);
		header("Content-Encoding: gzip");  
		header("Vary: Accept-Encoding");  
		header("Content-Length: ".strlen($_buffer));
	}
	return $_buffer;
}
?>