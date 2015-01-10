<?php
/**
 * image thumb class
 * 
 * @author	chenxin<chenxin619315@gmail.com>
 */

 //------------------------------------------------
 
class Thumb
{
	
	private $img_src			= NULL;  	//source image
	private $img_dst			= NULL;  	//destination image
	private $_resize;         		// 1 resize percentage
	private $_size 				= array();
	private $_extension 		= NULL;
	private static $_instance 	= NULL;
	
	//true for check the size
	private $_ = NULL;
	
	/**
	 * private constrct method
	 */
	private function __construct($_)
	{
		$this->$_ = $_;
	}

	private function __clone() {}
	
	public static function _getInstance($_ = true)
	{
		if ( ! self::$_instance instanceof self )
		{
			self::$_instance = new self($_);
		}

		return self::$_instance;
	}

    /**
     * write image to dest path
     * 
     * @param $_size        dimension
     * @param $_src_path    source path of image file
     * @param $dst_path     path to write
     * @param $_resize      resize type
     * @param $_trans       transparent background
     * */
	
	public function write($_size, $src_path, $dst_path, $_resize = 0, $_trans = false)
	{
		if ( ! file_exists($src_path) ) return;
		if ( ! file_exists($dst_path) ) self::createPath($dst_path);
			
		//load the args
		$this->_Load_Args($_size, $_resize);
		$this->img_src = $this->getImageSource($src_path);
		if ($this->img_src == NULL) return;
		$w_src = imagesx( $this->img_src );
		$h_src = imagesy( $this->img_src );
		
		$_dst_posi = array();
		$_dst_size = array();
		switch ( $this->_resize )
		{
			case 0:
				$_dst_posi[0] = 0; $_dst_posi[1] = 0;
				$_dst_size[0] = $this->_size[0];
				$_dst_size[1] = $this->_size[1];
				$this->img_dst = imagecreatetruecolor($this->_size[0], $this->_size[1]);
				break;
			case 1:
				if ( $w_src < $this->_size[0] && $h_src < $this->_size[1]  )
				{
					$this->_size[0] = $w_src;
					$this->_size[1] = $h_src;
					$_dst_size[0] = $this->_size[0]; $_dst_size[1] = $this->_size[1];
					$_dst_posi[0] = 0; $_dst_posi[1] = 0;
				}
				else
				{
					$_per = $w_src / $h_src;
					$aim_w = $this->_size[1] * $_per;   //aim width
					if ( $aim_w < $this->_size[0] )
					{
						$_dst_size[0] = $aim_w;
						$_dst_size[1] = $this->_size[1];
						$_dst_posi[0] = ($this->_size[0] - $aim_w) / 2;
						$_dst_posi[1] = 0;
					}
					else
					{
						$_dst_size[0] = $this->_size[0];
						$_dst_size[1] = $this->_size[0] / $_per;
						$_dst_posi[0] = 0;
						$_dst_posi[1] = ($this->_size[1] - $_dst_size[1]) / 2;	
					}
				}
				$this->img_dst = imagecreatetruecolor($this->_size[0], $this->_size[1]);
				break;
			case 2:
				
				if ( max($this->_size) == 0 ) return;
				$_ac = $this->_size[0] == 0 ? 1 : 0;
				//echo $w_src.', '.$h_src.', '.$_ac;
				
				if ( $_ac == 0 )
				{
					$_dst_size[0] = min($this->_size[0], $w_src);
					$_dst_size[1] = $_dst_size[0] * $h_src / $w_src;
					//echo $_dst_size[0].', '.$_dst_size[1];
				}
				else
				{
					$_dst_size[1] = min($this->_size[1], $h_src);
					$_dst_size[0] = $_dst_size[1] * $w_src / $h_src;
					//echo $_dst_size[0].', '.$_dst_size[1];	
				}
				$_dst_posi[0] = 0; $_dst_posi[1] = 0;
				$this->img_dst = imagecreatetruecolor($_dst_size[0], $_dst_size[1]);
				break;
		}
		
		if ( $this->img_dst == NULL ) return;
		//build a white background for the resource  

        if ($_trans){
            $bg = imagecolorallocatealpha($this->img_dst, 255, 255, 255, 127);
            imagefill($this->img_dst, 0, 0, $bg);
        } else {
            $bg = imagecolorallocate($this->img_dst, 255, 255, 255);
            imagefill($this->img_dst, 0, 0, $bg);
        }
		/*
		bool imagecopyresampled ( resource dst_image, resource src_image, 
		int dst_x, int dst_y, int src_x, int src_y, int dst_w, int dst_h, int src_w, int src_h )
		*/
		$_copy = imagecopyresampled($this->img_dst, $this->img_src, $_dst_posi[0], $_dst_posi[1], 0, 0,
			$_dst_size[0], $_dst_size[1], $w_src, $h_src);
		$_out = true;
		//imagejpeg($this->img_dst, $dst_path, 100);
		switch ($this->_extension)
		{
			case 'GIF':
				$_out = imagegif($this->img_dst, $dst_path);
				break;
			case 'JPG':
			case 'JPEG':
				$_out = imagejpeg($this->img_dst, $dst_path, 100);
				break;
			case 'PNG':
                // support transparent background
                // add by dongyado<dongaydo@gmail.com>
                if ($_trans){
                    imagealphablending( $this->img_dst, false  );
                    imagesavealpha( $this->img_dst, true  );    
                }
				$_out = imagepng($this->img_dst, $dst_path);
				break;
			case 'WBMP':
				$_out = imagewbmp($this->img_dst, $dst_path);
		}
		
		return $_copy && $_out;	
	}	
	
	private function getImageSource($img_path)
	{
		$_path = explode(".", strtolower($img_path));
		$_bak = strtoupper( $_path[ sizeof($_path) -1 ] );
		$img_source = NULL;
		$this->_extension = $_bak;
		
		switch($_bak)
		{
			case "GIF":
				$img_source = @imagecreatefromgif($img_path);
				break;
			case "JPG":
			case "JPEG":
				$img_source = @imagecreatefromjpeg($img_path);
				break;
			case "PNG":
				$img_source = @imagecreatefrompng($img_path);
				break;
			case "WBMP":
				$img_source = @imagecreatefromwbmp($img_path);
				break;
		}
		return $img_source;
	}
	
	
	private function _Load_Args( $_size = array(0, 0), $_resize = 0 )
	{
		isset($_size[0]) ? $this->_size[0] = $_size[0] : $this->_size[0] = 0;
		isset($_size[1]) ? $this->_size[1] = $_size[1] : $this->_size[1] = 0;
		$this->_resize = $_resize;
		//echo $this->_size[0].', '.$this->_size[1].', '.$this->_resize;
	}
	
	public function __destruct()
	{
		if ($this->img_src != NULL) imagedestroy($this->img_src);
		if ($this->img_dst != NULL) imagedestroy($this->img_dst);
	}


	/**
	 * check the existence of the upload direcotry, 
	 * 		if it is not exists create it
     *
     * @param 	$path
	 */
	public static function createPath( $path )
	{
		$dirArray = array();
		$baseDir = '';

		while ($path != '.' && $path != '..' ) 
		{
			if ( file_exists($path) ) 
			{
				$baseDir = $path;
				break;	 
			}

			$dirArray[]	= basename($path);   //basename part
			$path 		= dirname($path); 
		}

		for ( $i = count($dirArray) - 1; $i >= 0; $i-- )
		{
			if ( strpos($dirArray[$i], '.') !== FALSE ) 
			{
				break;
			}

			@mkdir( $baseDir . '/' . $dirArray[$i] );
			$baseDir = $baseDir . '/' .$dirArray[$i];
		}
	} 
}
?>
