<?php
/**
 * Agent(Browser) cache control class.
 *
 * @author chenxin <chenxin619315@gmail.com>
*/
class AgentCache
{
    /**
     * invoke the angent cache analysis progrm.
     *
     * @param   $_cache_time (unit second)
    */
    public static function invoke( $_cache_time = 0 )
    {
        if ( isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] )
            && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) + $_cache_time > time() ) {
            header('HTTP/1.1 304');
            exit();
        }
        //send the last modified time
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time() + $_cache_time) . ' GMT');
        header('Cache-Control: max-age=' . $_cache_time);
        //header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $_cache_time) . ' GMT');
    }
}
?>