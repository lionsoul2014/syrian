<?php
/**
 * Application Controller common Class.
 * And this is the super class of all module controller class.
 *
 * @author  chenxin <chenxin619315@gmail.com>
 * @link    http://www.lionsoul.org/syrian
 */

 //-----------------------------------------------------------------

//standart error no define
defined('STATUS_OK')            or define('STATUS_OK',           0);    //everything is fine
defined('STATUS_INVALID_ARGS')  or define('STATUS_INVALID_ARGS', 1);    //invalid arguments
defined('STATUS_NO_SESSION')    or define('STATUS_NO_SESSION',   2);    //no session
defined('STATUS_EMPTY_SETS')    or define('STATUS_EMPTY_SETS',   3);    //query empty sets
defined('STATUS_FAILED')        or define('STATUS_FAILED',       4);    //operation failed
defined('STATUS_DUPLICATE')     or define('STATUS_DUPLICATE',    5);    //operation duplicate
defined('STATUS_ACCESS_DENY')   or define('STATUS_ACCESS_DENY',  6);    //privileges deny
 
class Controller
{
    public  $uri    = NULL;        //request uri
    public  $input  = NULL;        //request input
    public  $output = NULL;        //request output
    public  $_G     = NULL;        //global resource

    /**
     * Construct method to create new instance of the controller
     *
     * @param    $uri
     * @param    $input
     * @param    $output
    */
    public function __construct()
    {
        _G(array(
            'flush_mode'  => false,
            'ignore_mode' => false
        ));

        $this->conf = config('app');
    }
    
    /**
     * the entrance of the current controller
     * default to invoke the uri->page.logic.php to handler
     *  the request, you may need to rewrite this method to self define
     *
     * @access  public
    */
    public function run()
    {
        //@Added at 2015-05-29
        //define the flush mode global sign
        //@Assoc the algorithm assocatied with the cache flush
        //    define in the helper/CacheFlusher#Refresh
        $flushMode = $this->input->getInt('__flush_mode__', 0);
        if ( $flushMode == 1 
            && strcmp($this->conf->flush_key, $this->input->get('__flush_key__')) == 0 ) {
            _G('flush_mode', true);
        }

        //@Added at 2015-07-21
        // for cache flush need to ignore the balance redirecting...
        $ignoreMode = $this->input->getInt('__ignore_mode__', 0);
        if ( $ignoreMode == 1 ) {
            _G('ignore_mode', true);
        }
    }

    /**
     * Quick method to response the current request
     *
     * @param   $errno
     * @param   $data
     * @param   $exit whether to exit the process after the output
     * @param   $ext extension value
     */
    protected function response( $errno, $data, $exit=false, $ext=NULL )
    {
        $json = array(
            'errno'  => $errno,
            'data'   => $data
        );

        if ( $ext != NULL ) {
            $json['ext'] = $ext;
        }

        $CC = json_encode($json);
        $this->output->setHeader('Content-Type', 'application/json')->display($CC);

        if ( $exit ) {
            exit();
        }

        return $CC;
    }

    /**
     * define output
     *
     * @param   $errno
     * @param   $data
     * @param   $ext
     * @return  the json encoded core data
     */
    protected function defineEcho( $errno, $data, $ext=NULL )
    {
        if ( is_array($data) ) {
            $data = json_encode($data);
        }

        if ( $ext == NULL ) $ext = 'false';
        else if ( is_array($ext) ) $ext = json_encode($ext);

        $CC = <<<EOF
        {
            "errno": $errno,
            "data": $data,
            "ext": $ext
        }
EOF;
        $this->output->setHeader('Content-Type', 'application/json')->display($CC);
        return $data;
    }
    
}
?>
