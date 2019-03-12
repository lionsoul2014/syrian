<?php
/**
 * tar packing class
 *
 * @author  chenxin<chenxin619315@gmail.com>
*/

class TarPacking
{
    /**
     * temporary file path
    */
    private $tmpFile = null;

    /**
     * tar handler
    */
    private $tarer = null;
    
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
            '.tar.gz'
        ));

        $this->tarer = new PharData($this->tmpFile);
    }

    /**
     * add a new file to the tar package
     *
     * @param   $path
     * @param   $filename
     * @return  boolean
    */
    public function addFile($path, $filename)
    {
        return $this->tarer->addFile($path, $filename);
    }

    /**
     * add a directory to the tar package
     *  add all the files in the directory
     *
     * @param   $dir
     * @return  TarPacking
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
                    $this->tarer->addFile("{$dir}/{$file}", $file);
                }
            }
        }

        closedir($handler);
        return true;
    }
    
    /**
     * set the http header and 
     *  start the download of this zip file
     *
     * @param   $name
    */
    public function download($name)
    {
        // force close the tar archive ZipArchive
        $this->close();
        header("Content-Type: application/x-gzip");
        header('Content-Length: ' . filesize($this->tmpFile));
        header("Content-disposition: attachment; filename={$name}"); 
        header("Content-Transfer-Encoding: binary");
        ob_clean();     // clear the buffer for the may coming error
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
        return $this->tarer->count();
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
        // do nothing here for now
    }

}
