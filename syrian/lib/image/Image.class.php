<?php
/**
 * image handl class based on imagick
 * 
 * @author    dongyado<dongyado@gmail.com>
 *
 */

//------------------------------------------------
 
class Image
{
    
    private $img_src        = NULL;      //source image
    private $img_dst        = NULL;      //destination image
    private $image          = NULL;
    private $type           = NULL;
    
    public  function __construct(){}


    /**
     * open source image file
     *
     * @param $src_path source image path 
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
     * crop 
     *
     * @param $width
     * @param $height
     * @param $x
     * @param $y
     * */

    public function crop($width, $height, $x = 0, $y = 0) 
    {
        $width  = intval($width);
        $height = intval($height);
        
        $src_size = $this->image->getImageGeometry();
        if ($width  == 0 || $width  > $src_size['width']) $width = $src_size['width']; 
        if ($height == 0 || $height > $src_size['height']) $height = $src_size['height']; 

        if ($width <= 0 || $height <= 0)
            throw new Exception( 'Invalid widht or height' );

        if ($this->type == 'gif')
        {
            $src_image = $this->image;
            $new_image = new Imagick();
            $frames = $src_image->coalesceImages();
            foreach($frames as $frame)
            {
                $f = new Imagick();
                $f->readImageBlob($frame);
                $f->cropImage($width, $height, $x, $y);

                $new_image->addImage($f);
                $new_image->setImageDelay($f->getImageDelay());
                $new_image->setImagePage($width, $height, 0, 0);
            }
            $src_image->destroy();
            $this->image = $new_image;
        } 
        else 
        {
            $this->image->cropImage($width, $height, $x, $y);
        }
        return $this;
    }


    /**
     * resize image
     *
     * @param $width
     * @param $height
     * @param $_resize_type  
     *
     * */

    public function resize( $width = 0, $height = 0, $resize_type = 0 ) 
    {
        $width  = intval($width);
        $height = intval($height);
        $resize_type = intval($resize_type);

        switch($resize_type)
        {
            case 0: // force resize to specified size
                if ($width == 0 || $height == 0)
                    throw new Exception('Width or Height invalid');
                $this->_thumb( $width, $height);

                break;
            case 1: // resize based on width
                // get src width
                // caculate dst size based on src_width and dist_width
                if ( $width == 0 ) 
                    throw new Exception('Width value invalid');

                $src_image = $this->image;
                $src_size  = $src_image->getImageGeometry(); //get image size
                $src_radio = $src_size['height'] / $src_size['width'];
                
                if ($src_size['width'] < $width) $width = $src_size['width'];
                
                $height = $src_radio * $width; 
                
                $this->_thumb($width, $height);
                    
                break;
            case 2: 
                if ( $height == 0 )
                    throw new Exception( 'Height value invalid' );

                $src_image = $this->image;
                $src_size  = $src_image->getImageGeometry();
                $src_radio = $src_size['width'] / $src_size['height'];

                if ( $src_size['height'] < $height ) $height = $src_size['height'];

                $width = $src_radio * $height;
                $this->_thumb( $width, $height );

                break;

           //case 3: // automatic get the best size
               

        }
        return $this;
    }


    private function _thumb( $width, $height )
    {
        if ($this->type == 'gif')
        {
            $src_image = $this->image;
            $new_image = new Imagick();
            $frames = $src_image->coalesceImages();
            foreach($frames as $frame)
            {
                $f = new Imagick();
                $f->readImageBlob($frame);
                $f->thumbnailImage($width, $height, false);

                $new_image->addImage($f);
                $new_image->setImageDelay($f->getImageDelay());
            }
            $src_image->destroy();
            $this->image = $new_image;
        } 
        else 
        {
            $this->image->thumbnailImage($width, $height, false);
        }
    }

    /**
     * 调节图片的亮度，饱和度，色调 
     *
     * @param $brightness 亮度
     * @param $saturation 饱和度
     * @param $hue 色调
     * */

    public function modulate($brightness, $saturation, $hue) 
    {
        $this->image->modulateImage($brightness, $saturation, $hue);
        return $this;
    }
    


    /** 
     * 调整对比度
     * */
    public function contrast($val)
    {
        $this->image->contrastImage($val);
        return $this;
    }



    /**
     * save image to dest path
     *
     * */
    public function save($dst_path)
    {
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
     *         if it is not exists create it
     *
     * @param     $path
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

            $dirArray[]    = basename($path);   //basename part
            $path         = dirname($path); 
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

//$thumb = new Image();
//
//$thumb->open('/home/slayer/Desktop/gif.gif')
//    ->resize(480, 300, 1)
//    ->save('/home/slayer/Desktop/gif1.gif');
?>
