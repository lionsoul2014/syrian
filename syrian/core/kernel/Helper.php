<?php if ( ! defined('BASEPATH') ) exit('No Direct Access Allowed!');
/**
 * Syrian Application Helper super Class.
 *
 * @author    chenxin <chenxin619315@gmail.com>
 * @link    http://www.lionsoul.org/syrian
 */

 //-----------------------------------------------------------------
 
class Helper
{
    /**
     * Construct method to create new instance of the Helper
     *
     * @param    $conf
    */
    public function __construct($conf)
    {
    }

    /**
     * load the specifield method by name
     *
     * @param    $args
    */
    public function load()
    {
        $_argv = func_get_args();
        $_args = func_num_args();
        if ( $_args > 0 && method_exists($this, $_argv[0]) ) {
            $cacher    = array_shift($_argv);
            return $this->{$cacher}($_argv);
        }

        exit("Error: Unable to load cacher {$_argv[0]}\n");
    }
}
?>
