<?php if ( ! defined('BASEPATH') ) exit('No Direct Access Allowed!');
/**
 * Super Loader manager class, offer
 *      quick interface to load model/config/class
 *
 * @author chenxin <chenxin619315@gmail.com>
*/

//----------------------------------------------------------

class Loader
{
    /**
     * make construct method private
    */   
    private function __construct() {}
    
    /**
     * Import class file from the specified path
     * The function will check script file $_path.class.php first
     * 	and then $_path.php
     *
     * @param	$_class
     * @param   $_section
     * @param	$_inc	If $_inc is TRUE check the syrian/lib  or check APPPATH/lib
     * @return	bool	true for loaded successfuly and false for not
     */
    public static function import($_class, $_section = NULL, $_inc = true)
    {
        //All the loaded classes.
        static $_loaded = array();
        
        //$_class = ucfirst($_class);
        $_cls = ($_section == NULL) ? $_class : $_section . '/' . $_class;
        if ( isset($_loaded[$_cls]) ) return;
        
        //Look for the class in the SYSPATH/lib folder if $_inc is TRUE
        //Or check the APPPATH/lib 
        $_dir  = (($_inc) ? BASEPATH . '/lib/' : SR_LIBPATH);
        $_dir .= $_cls;
        
        foreach( array($_dir . '.class.php', $_dir . '.php') as $_file )
        {
            if ( file_exists($_file) )
            {
                require $_file;
                $_loaded[$_cls] = true;
                return true;
            }
        }
        
        exit('Syrian:Loader#import: Unable to load class ' . $_class);
    }
    
    
    /**
     * function to load data from the specified file
     * 	and return the return of the included file as the final result
     *
     * @param	$_config
     * @param	$_section
     * @param   $_inc   True for seach files in syrian/config
	 * @param	$_key	specifield key
     * @return	mixed(Array, Object, Bool)
     */
    public static function config( $_config, $_section=NULL, $_inc=false, $key=NULL )
    {
        //make the included file name
        $_dir = (($_inc) ? BASEPATH . '/config/' : SR_CONFPATH);
        
        //append the section
        if ( $_section != NULL ) $_dir .= $_section . '/';
        $_dir .= $_config;
        
        //search the config file and include it
        foreach ( array($_dir . '.conf.php', $_dir . '.php' ) as $_file )
        {
            if ( file_exists($_file) )
            {
                //return include $_file;
                $conf	= include $_file;
				
				if ( $key != NULL )
				{
					return isset($conf["{$key}"]) ? $conf["{$key}"] : NULL;
				}

				return $conf;
            }
        }
        
        //throw new Exception('No such file or directory');
        exit('Syrian:Loader#config: Unable to load config ' . $_config);
    }
    
    /**
     * function to load the specifile model maybe from the
     * 		specifile path and return the instance of the model
     *
     * @param	$_model
     * @param	$_section
     * @return	Object
    */
    public static function model( $_model, $_section = NULL )
    {
        //loaded model
        static $_loaded = array();
        
        $_model = ucfirst($_model);
        
        //check the loaded of the class
        $_cls = ($_section == NULL) ? $_model : $_section . '/' . $_model;
        if ( isset( $_loaded[$_cls] ) )
        {
            return $_loaded[$_cls];
        }
        
        //model base directory
        $_dir = SR_MODELPATH . $_cls;
            
        foreach ( array( $_dir . '.model.php', $_dir . '.php' ) as $_file )
        {
            if ( file_exists( $_file ) )
            {
                include $_file;				//include the model class file
                
                $o = NULL;
                $_class = $_model.'Model';
                if ( class_exists($_class) ) 
                {
                    $o = new $_class();
                }
                else $o = new $_model();

                //mark loaded for the current class
                $_loaded[$_cls] = $o;

                return $o;
            }
        }
        
        exit('Syrain:Loader#model: Unable to load model ' . $_model);
    }

    /**
     * function to load and create helper instance
     *
     * @param	$_helper
     * @param	$_section
     * @param   $_inc   True for seach files in syrian/helper
	 * @param	$_conf	configuration to create the instance
     * @return	mixed(Array, Object, Bool)
     */
	public static function helper($_helper, $conf=NULL, $_section = NULL, $_inc = false)
    {
        //All the loaded helper.
        static $_loaded = array();
        
        //$_class = ucfirst($_class);
        $_cls = ($_section == NULL) ? $_helper : $_section . '/' . $_helper;
        if ( isset($_loaded[$_cls]) ) return $_loaded[$_cls];
        
        //Look for the class in the SYSPATH/lib folder if $_inc is TRUE
        //Or check the APPPATH/lib 
        $_dir  = (($_inc) ? BASEPATH . '/helper/' : SR_HELPERPATH);
        $_dir .= $_cls;
        
        foreach( array($_dir . '.helper.php', $_dir . '.php') as $_file )
        {
            if ( file_exists($_file) )
            {
                require $_file;
				$_class	= $_helper.'Helper';
				$obj = new $_class($conf);
                $_loaded[$_cls] = &$obj;
                return $obj;
            }
        }
        
        exit('Syrian:Loader#helper: Unable to load helper ' . $_helper);
    }
}
?>