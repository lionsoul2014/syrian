<?php
/**
 * Cache flush helper
 *
 * @author  chenxin<chenxin619315@gmail.com>
*/

import('CacheHelper', false);

 //------------------------------------
class CacheFlusherHelper extends CacheHelper
{
    //private $conf = NULL;
    private static $localExecutor = NULL;
    private static $distExecutor  = NULL;
    private static $SE = NULL;

    /**
     * construct method
     *
     * @param    $conf the global configuration instance
     *    define in config/sys.conf.php
    */
    public function __construct($conf=NULL)
    {
        parent::__construct($conf);
    }

    /**
     * get and load the executor
     *
     * @param   $dist
    */
    private static function E($dist)
    {
        if ( self::$SE == NULL ) {
            self::$SE = helper('ServiceExecutor');
        }

        if ( $dist ) {
            if (self::$distExecutor == NULL ) {
                self::$distExecutor = self::$SE->load('CacheDistRefresh');
            }

            return self::$distExecutor;
        }

        if ( self::$localExecutor == NULL ) {
            self::$localExecutor = self::$SE->load('CacheLocalRefresh');
        }

        return self::$localExecutor;
    }

    /**
     * stream view cache flusher
     *
     * @param   $input
    */
    public function StreamView($input)
    {
        $attr      = $input[0];
        $stream_id = $input[1];
        $ack_code  = $input[2];

        //attributes define
        $dist     = isset($attr['dist']) ? $attr['dist'] : false;
        $asyn     = isset($attr['asyn']) ? $attr['asyn'] : true;
        $insure   = isset($attr['insure']) ? $attr['insure'] : true;
        $maxtries = isset($attr['maxtries']) ? $attr['maxtries'] : 3;

        $urls = array(
            "stream/view?site=m&stream_id={$stream_id}&_ack={$ack_code}",  
            "stream/view?site=pc&stream_id={$stream_id}&_ack={$ack_code}"
        );

        $bizMapping = config('contentThirdParty');
        foreach ( $bizMapping as $biz_name => $biz ) {
            if ( $biz['is_reflux'] == false ) {
                continue;
            }

            $urls[] = "stream/view?stream_id={$stream_id}&_ack={$ack_code}&biz={$biz_name}";
        }

        unset($bizMapping);

        return self::E($dist)->execute(
            'cache.refresh',
            array(
                'urls'     => $urls,
                'insure'   => $insure,
                'maxtries' => $maxtries
            ), $asyn
        );
    }

}
?>
