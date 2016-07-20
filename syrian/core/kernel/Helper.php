<?php if ( ! defined('BASEPATH') ) exit('No Direct Access Allowed!');
/**
 * Syrian Application Helper super Class.
 * helper defination:
 * quick way to create and initialize the logic class
 * that will be show up at many logic, so
 * it could help reduce the uneccessary logic code.
 * 
 * Loader::import(xxxx)
 * $conf = Loader::config(xxxx):
 * $obj = new XXXX($conf);
 * //serials of initialize working
 * //return ready to fly(well initialized) object
 * return $obj;
 *
 * @author  chenxin <chenxin619315@gmail.com>
 * @link    http://www.lionsoul.org/syrian
 */

 //-----------------------------------------------------------------
 
class Helper
{
    /**
     * Construct method to create new instance of the Helper
     *
     * @param   $conf
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
            $method = array_shift($_argv);
            return $this->{$method}($_argv);
        }

        exit("Error: Helper unable to load {$_argv[0]}\n");
    }
}
?>
