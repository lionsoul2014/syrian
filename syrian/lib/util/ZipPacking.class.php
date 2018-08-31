<?php
/**
 * Zip packing class
 *
 * @author  chenxin<chenxin619315@gmail.com>
*/

 //----------------------------------------------------------

class ZipPacking
{
    
    /**
     * temporary file path
    */
    private $tmpFile = null;

    /**
     * zip handler
    */
    private $ziper = null;
    
    /**
     * construct method
     *
     * @param   $tmpDir
    */
    public function __construct($tmpDir='/tmp/')
    {
        $this->tmpFile = implode('', array(
            $tmpDir,
            microtime(true),
            mt_rand(0, 0x7FFF),
            '.zip'
        ));

        $this->ziper = new ZipArchive();
        if ( $this->ziper->open(
            $this->tmpFile, 
            ZipArchive::CREATE | ZipArchive::OVERWRITE ) == false ) {
            throw new Exception('Fail to open the target zip file');
        }
    }

    /**
     * add a new file to the zip package
     *
     * @param   $path
     * @param   $filename
     * @return  boolean
    */
    public function addFile($path, $filename)
    {
        return $this->ziper->addFile($path, $filename);
    }

    /**
     * add a directory to the zip package
     *  add all the files in the directory
     *
     * @param   $dir
     * @return  ZipPacking
    */
    public function addDir($dir)
    {
        $handler = opendir($dir);
        if ( $handler == false ) {
            return false;
        }

        while ( ($file = readdir($handler)) !== false ) {
            if ( $file == '.' || $file == '..' ) {
                if ( ! is_dir($file) ) {
                    $this->ziper->addFile("{$dir}/{$file}", $file);
                }
            }
        }

        closedir($handler);
        return true;
    }
    
    /**
     * Add files from a directory by PCRE pattern
     *
     * @param   $path
     * @param   $pattern
     * @return  boolean
    */
    public function addPattern($path, $pattern)
    {
        $this->ziper->addPattern($pattern, $path);
    }

    /**
     * set the http header and 
     *  start the download of this zip file
     *
     * @param   $name
    */
    public function download($name)
    {
        // force close the zip ZipArchive
        $this->close();
        header("Content-Type: application/zip"); //zip格式的   
        header('Content-Length: ' . filesize($this->tmpFile));
        header("Content-disposition: attachment; filename={$name}"); 
        header("Content-Transfer-Encoding: binary");
        readfile($this->tmpFile);

        // remove the temporary file at the end
        $this->remove();
    }

    /**
     * return the file numbers
     *
     * @return  integer
    */
    public function count()
    {
        return $this->ziper->count();
    }

    /**
     * set the comment
     *
     * @param   $comment
     * @return  boolean
    */
    public function setComment($comment)
    {
        return $this->ziper->setArchiveComment($comment);
    }

    /**
     * remove the zip file
     *
     * @return  boolean
    */
    public function remove()
    {
        return @unlink($this->tmpFile);
    }

    /**
     * close the zip
     *
     * @return  boolean
    */
    public function close()
    {
        return $this->ziper->close();
    }

}
