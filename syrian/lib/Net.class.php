<?php
/**
 * Net class offer usefull net serial about function
 *
 * @author	chenxin<chenxin619315@gmail.com>
 */

 //----------------------------------------------------

class Net
{
	/**
	 * download the specifield image file with a validate url
	 * 		and save it to the specifield local file.
	 *
	 * curl extension needed for runing this function
	 *
	 * @param	$url
	 * @param	$toFile without extension
	 * @param	$thumb image info
	 */
	public static function saveRemoteImage($url, $toFile, $thumb=NULL, $conf=array())
	{
		$timeout	= isset($conf['timeout']) ? $conf['timeout'] : 30;
		$useragent	= isset($conf['useragent']) ? $conf['useragent'] : 'Mozilla/5.0 (X11; Linux i686) AppleWebKit/535.19 (KHTML, like Gecko) Ubuntu/10.10 Chromium/18.0.1025.151 Chrome/18.0.1025.151 Safari/535.19';

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPGET, 1); 
		curl_setopt($ch, CURLOPT_USERAGENT, $useragent);  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout );
		curl_setopt($ch, CURLOPT_HEADER, 0);

		//execute the curl to get the response
		$R 		= curl_exec($ch);
		$info	= curl_getinfo($ch);
		$ret	= false;
		if ( isset($info['http_code']) && $info['http_code'] == 200 )
		{
			$ext 			= NULL;
			$contentType	= strtolower($info['content_type']);

			static $EXTS	= array(
				'image/jpeg'		=> 'jpg',
				'image/jpg'			=> 'jpg',
				'image/pjpeg'		=> 'jpg',
				'image/png'			=> 'png',
				'image/gif'			=> 'gif',
				'image/x-xbitmap'	=> 'bmp'
			);

			if ( isset($EXTS[$contentType]) ) $ext	= $EXTS[$contentType];

			//check the extension and save the image
			$toFile	= "{$toFile}.{$ext}";
			if ( $ext != NULL && file_put_contents($toFile, $R) != false )
				$ret 	= basename($toFile);

			//make a thumb image for the downloaded images
			if ( $ret != false && $thumb != NULL )
			{
				Loader::import('Thumb', 'image');
				Thumb::_getInstance()->write(array($thumb['width'], 
					$thumb['height']), $toFile, $toFile, $thumb['style']);
			}
		}

		//close the curl
		curl_close($ch);

		return $ret;
	}
}
?>
