<?php
/**
 * image handl class based on imagick
 * 
 * @author	dongyado<dongyado@gmail.com>
 *
 */

 //------------------------------------------------
 
class ImageThumb
{
	
	private $img_src		= NULL;  	//source image
	private $img_dst		= NULL;  	//destination image
    private $image          = NULL;
    private $type           = NULL;
	
	/**
	 * private constrct method
	 */
	public  function __construct()
	{
	}


    /**
     * open source image file
     *
     * */
    public function open($src_path)
    {
		if ( ! file_exists($src_path) ) return false;
        $this->image = new Imagick($src_path);
        $this->img_src = $src_path;

        if ($this->image) 
        {
            $this->type = strtolower($this->image->getImageFormat());
        }
        return $this;
    }


    /**
     * resize image
     *
     * @param $_size array('width', 'height')
     * @param $_resize_type  
     *
     * */

    public function resize($size, $resize_type) {
        switch($resize_type)
        {
            case 0:
                if ($this->type == 'gif')
                {
                    $src_image = $this->image;
                    $new_image = new Imagick();
                    $frames = $src_image->coalesceImages();
                    foreach($frames as $frame)
                    {
                        $f = new Imagick();
                        $f->readImageBlob($frame);
                        $f->thumbnailImage($size[0], $size[1], false);

                        $new_image->addImage($f);
                        $new_iamge->setImageDelay($f->getImageDelay());
                    }
                    $src_image->destroy();
                    $this->image = $new_image;
                } else {
                    $this->image->thumbnailImage($size[0], $size[1], false);
                }
                break;
            case 1:

                break;
            case 2: 
                break;
        }
        return $this;
    }

    public function modulate($brightness, $saturation, $hue) {

        $this->image->modulateImage($brightness, $saturation, $hue);
        return $this;
        
    }
    
    public function contrast($val){
        $this->image->contrastImage($val);
        return $this;
    }

    /**
     * save image to dest path
     *
     * */
    public function save($dst_path){
		if ( ! file_exists($dst_path) ) self::createPath($dst_path);
        if ($this->type == 'gif')
        {
             return  $this->image->writeImages($dst_path, true);
        } 
        return $this->image->writeImage($dst_path);
    }
	
	
	public function __destruct()
	{
        $this->image->destroy();
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

$thumb = new ImageThumb();

$thumb->open('/home/slayer/Desktop/new.jpg')
    ->resize(array(1080, 675), 0)
    ->modulate(100, 0.5, 60)
    ->save('/home/slayer/Desktop/new2.jpg');


?>
