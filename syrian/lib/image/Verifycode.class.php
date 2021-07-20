<?php
/**
 * Verify code class
 * this will need the extension of the gb library.
 *
 * @author    chenxin <chenxin619315@gmail.com>
*/

//--------------------------------------------------------------------

class Verifycode
{
    
    /**letters generator array.*/
    private static $_letters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    
    //width, height, x, y, fontsize
    private $_config = array(130, 50, 0, 0, 20);
    
    /*image resource pointer*/
    private $_image  = NULL;
    private $_codes  = NULL;
    
    /*instance pointer.*/
    private static $_instance = NULL;
    
    private function __construct() {}
    
    /**
     * get the instance of the Verifycode.
     *
     * @return    $_instance
    */
    public static function getInstance()
    {
        if ( self::$_instance == NULL ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }
    
    public function setWidth( $_width )
    {
        $this->_config[0] = $_width;
        return $this;
    }
    
    public function setHeight( $_height )
    {
        $this->_config[1] = $_height;
        return $this;
    }
    
    public function setX( $_x )
    {
        $this->_config[2] = $_x;
        return $this;
    }
    
    public function setY( $_y )
    {
        $this->_config[3] = $_y;
        return $this;
    }
    
    public function setFontSize( $_size )
    {
        $this->_config[4] = $_size;
        return $this;
    }
    
    /**
     * generate some new verify code chars
     *    and draw them on the image. <br />
     *
     * @param   $_size number of chars to generate
     * @return  $this
     */
    public function generate($_size=4)
    {
        $_code_buff = [];
        $_length = strlen(self::$_letters);
        for($i = 0; $i < $_size; $i++) {
            $_code_buff[] = self::$_letters[mt_rand() % $_length];
        }
        $this->_codes = implode('', $_code_buff);
        # $this->_codes = '....';

        # create an image and draw the codes on it
        if ( $this->_image == NULL ) {
            $this->_image = imagecreatetruecolor($this->_config[0], $this->_config[1]);
        }
        
        switch ( mt_rand() % 4 ) {
        case 0: $_bg = imagecolorallocate($this->_image, 250, 250, 250); break;
        case 1: $_bg = imagecolorallocate($this->_image, 255, 252, 232); break;
        case 2: $_bg = imagecolorallocate($this->_image, 254, 245, 243); break;
        case 3: $_bg = imagecolorallocate($this->_image, 233, 255, 242); break;
        }
        
        # $_bg = imagecolorallocate($this->_image, 255, 255, 255);
        imagefilledrectangle($this->_image, 0, 0, $this->_config[0], $this->_config[1], $_bg);
        # //imagefilter($this->_image, IMG_FILTER_EMBOSS);
        
        switch ( mt_rand() % 5 ) {
        case 0: $_color = imagecolorallocate($this->_image, 128, 128, 128); break; // gray
        case 1: $_color = imagecolorallocate($this->_image,  16,   9, 140); break; // blue
        case 2: $_color = imagecolorallocate($this->_image,  65, 125,   0); break; // green
        case 3: $_color = imagecolorallocate($this->_image, 255,  75,  45); break; // read
        case 4: $_color = imagecolorallocate($this->_image, 238, 175,   7); break; // orange
        }

        //$_color = imagecolorallocate($this->_image, 128, 128, 128);
        $_font = dirname(__FILE__) . DIRECTORY_SEPARATOR. 'fonts' . DIRECTORY_SEPARATOR . 'ariblk.ttf';
        
        //draw the code chars
        $_xstep = ($this->_config[0] - 2 * $this->_config[2]) / $_size;
        $_angle = 0;
        for ($i = 0; $i < $_size; $i++) {
            $_angle = mt_rand() % 30;
            imagettftext(
                $this->_image, $this->_config[4], 
                ($_angle & 0x01) == 0 ? $_angle: -$_angle,
                $this->_config[2] + $i * $_xstep, $this->_config[3],
                $_color, $_font, $this->_codes[$i]
            );
        }

        # draw the interference line
        imagesetthickness($this->_image, 2);
        for ($i=0; $i < 10 ; $i++) {
            $x = mt_rand(0,1024) % $this->_config[0];
            $y = mt_rand(0,1024) % $this->_config[1];
            imageline($this->_image, $x, $y, $x + 10, $y + 10, $_color);
        }

        # imagesetthickness($this->_image, 2);
        for ($i=0; $i < 10 ; $i++) {
            $x = mt_rand(0,1024) % $this->_config[0];
            $y = mt_rand(0,1024) % $this->_config[1];
            imagearc($this->_image, $x, $y,
                mt_rand(2,30),  mt_rand(2,30), 
                mt_rand(0,360), mt_rand(0,360), 
                $_color
            );
        }

        return $this;
    }
    
    /**
     * create and show the image resource to the browser
     *    by send a header message.
     *
     * @param   $_suffix image suffix
     */
    public function show($_suffix='gif')
    {
        if ($this->_image == NULL) {
            throw new Exception("call the generate before this");
        }

        switch ($_suffix) {
        case 'gif':
            header("Content-type: image/gif");
            imagegif($this->_image);
            break;
        case 'jpg':
        case 'jpeg':
            header("Content-type: image/jpeg");
            imagejpeg($this->_image, "", 1);
            break;
        case 'png':
            header("Content-type: image/png");
            imagepng($this->_image);
            break;
        case 'bmp':
            header("Content-type: image/vnd.wap.wbmp");
            imagewbmp($this->_image);
            break;
        default:
            throw new Exception("unknow image type " . $_suffix);
        }
    }

    /* base64 encode the current image and return it */
    public function base64Encode($_suffix='gif')
    {
        if ($this->_image == NULL) {
            throw new Exception("call the generate before this");
        }

        ob_start();
        $output = ['data:'];
        switch ($_suffix) {
        case 'gif':
            $output[] = 'image/gif';
            imagegif($this->_image);
            break;
        case 'jpg':
        case 'jpeg':
            $output[] = 'image/jpeg';
            imagejpeg($this->_image, "", 1);
            break;
        case 'png':
            $output[] = 'image/png';
            imagepng($this->_image);
            break;
        case 'bmp':
            $output[] = 'image/bmp';
            imagewbmp($this->_image);
            break;
        default:
            throw new Exception("unknow image type " . $_suffix);
        }

        $output[] = ';base64,';
        $output[] = base64_encode(ob_get_contents());
        ob_end_clean();

        return implode('', $output);
    }
    
    public function getImage()
    {
        return $this->_image;
    }

    public function getCode()
    {
        return $this->_codes;
    }
    
    /**destruct method*/
    public function __destruct()
    {
        if ( $this->_image != NULL ) {
            imagedestroy($this->_image);
        }
    }
}
?>
