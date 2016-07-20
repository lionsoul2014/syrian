<?php
/**
 * super class for Helper
 *
 * @author  chenxin<chenxin619315@gmail.com>
*/

 //-----------------------------------------------------

class CacheHelper extends Helper
{
    /**
     * construct method
     *
     * @param   $conf
    */
    public function __construct($conf=NULL)
    {
        parent::__construct($conf);
    }

    /**
     * quick interface to create the cache instance
     *
     * @param   $key service cache name
     */
    protected static function getCacher( $key='NFile' )
    {
        static $_loaded = array();

        if ( isset($_loaded[$key]) ) {
            return $_loaded[$key];
        }

        $conf = config("cache#{$key}", false);
        Loader::import('CacheFactory', 'cache');

        //@Added at 2015-07-29 with service logic cache support
        //    compatible with the old cache setting style
        $_loaded[$key] = CacheFactory::create(
            isset($conf['key'])  ? $conf['key']  : $key,
            isset($conf['conf']) ? $conf['conf'] : $conf
        );

        return $_loaded[$key];
    }
    
}
?>
