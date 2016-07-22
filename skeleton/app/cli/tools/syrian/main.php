<?php
/**
 * ToolSyrianController manager class
 *
 * @author  chenxin <chenxin619315@gmail.com>
*/

class SyrianController extends Cli_Controller
{
    public function __construct()
    {
        parent::__construct();
    } 

    /**
     * minify the syrian framework
     * target source file: BASEPATH/core/Syrian.merge.php
     * destination file  : BASEPATH/core/Syrian.merge.min.php
    */
    public function _minify($input)
    {
        Loader::import('PHPSource', 'util');
        $srcFile = BASEPATH . 'core/Syrian.merge.php';
        $dstFile = BASEPATH . 'core/Syrian.merge.min.php';
        $ret = PHPSource::minify($srcFile, $dstFile);
        echo $ret ? "Ok\n" : "Failed\n";
    }

}
?>
