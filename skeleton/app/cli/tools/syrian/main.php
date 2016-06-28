<?php
/**
 * ToolSyrianController manager class
 *
 * @author  chenxin <chenxin619315@gmail.com>
*/

//------------------------------------

class SyrianController extends CliController
{

    private $debug  = false;

    public function __construct()
    {
        parent::__construct();
    } 

    /**
     * controller entrace method you could use the default one
     *      by just invoke parent::run() or write you own implementation
     *
     * @see Controller#run()
    */
    public function run()
    {
        parent::run();

        $this->debug = $this->input->getBoolean('debug', false);

        if ( strncmp($this->uri->page, 'minify', 6) == 0 )  $this->_minify();
    }

    /**
     * minify the syrian framework
     * target source file: BASEPATH/core/Syrian.merge.php
     * destination file  : BASEPATH/core/Syrian.merge.min.php
    */
    public function _minify()
    {
        Loader::import('PHPSource', 'util');
        $srcFile = BASEPATH . 'core/Syrian.merge.php';
        $dstFile = BASEPATH . 'core/Syrian.merge.min.php';
        $ret = PHPSource::minify($srcFile, $dstFile);
        echo $ret ? "Ok\n" : "Failed\n";
    }

}
?>
