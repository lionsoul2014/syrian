<?php if ( ! defined('BASEPATH') ) exit('No Direct Access Allowed!');
/**
 * Json Viewer manager class
 *      base on function json_decode/json_encode
 *
 * change the handler function throught $_config['encode'=>, 'decode'=>]
 *      --Not implemented yet...
 *
 * @author chenxin <chenxin619315@gmail.com>
*/

 //---------------------------------------------------------
 
class JsonView extends AView
{
    public function __construct( $_conf )
    {
        //Do nothing here for now
    }
    
    /**
     * return the content
     *
     * @param   $_tpl_file
     * @param   $sanitize sanitize the content ?
     * @return  string the executed html text
    */
    public function getContent( $_tpl_file=NULL, $sanitize=false )
    {
        $ret = json_encode($this->_symbol);
        return $sanitize ? $this->sanitize($ret) : $ret;
    }
}
?>
