<?php
/**
 * Common Util function class
 * 	Offer some useful functions
 * 
 * @author chenxin <chenxin619315@gmail.com>
*/
 
 //---------------------------------------------------------
 
class Util
{
	public static function setText( $filename, $text ) 
	{
		if ( ($handle = fopen($filename, 'wb') ) != FALSE ) 
		{
			if ( flock($handle, LOCK_EX) ) 
			{
				if ( ! fwrite($handle, $text) ) 
				{
					flock($handle, LOCK_UN);
					fclose($handle);
					return FALSE;
				}
			}
			
			flock($handle, LOCK_UN);
			fclose($handle);
			return TRUE;
		}
		
		return FALSE;	 
	}
		
	public static function makePath( $filename ) 
	{
		 $dirArray = array();
		 $baseDir = '';
		 
		 while ($filename != '.' && $filename != '..') 
		 {
			 if ( file_exists($filename) ) 
			 {
				 $baseDir = $filename;
				 break;	 
			 }
			 
			 $dirArray[] 	= basename($filename);   //basename part
			 $filename 		= dirname($filename); 
		 }
		 
		 for ( $i = count($dirArray) - 1; $i >= 0; $i--) 
		 {
			 if ( strpos($dirArray[$i], '.') !== FALSE ) 
			 {
				 break;
			 }
			 
			 @mkdir( $baseDir . '/' . $dirArray[$i] );
			 $baseDir = $baseDir . '/' .$dirArray[$i];
		 }
	 }
		 
	public static function utf8_substr($str, $limit) 
	{ 
		if ( strlen($str) <= $limit ) return $str;
		
		$substr = ''; 
		for( $i=0; $i< $limit-3; $i++) 
		{ 
			$substr .= ord($str[$i])>127 ? $str[$i].$str[++$i].$str[++$i] : $str[$i]; 
		} 
		
		return $substr; 
	}
}
?>
