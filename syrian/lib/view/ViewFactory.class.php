<?php
/*
 * View parser class common interface.
 * 
 * @author	chenxin<chenxin619315@gmail.com>
 */
interface IView
{
	public function assign( $_name, $_value );
	public function assoc( $_name, &$_value );
	public function getContent( $_tpl_file = NULL );
}

 //------------------------------------------------------------------

/**
 * View parse factory
 *  Quick way to lanch all kinds of view with just a key
 * like: Html, Json, Xml, eg..
 *
 * @author chenxin <chenxin619315@gmail.com>
*/

 //------------------------------------------------------------------
 
class ViewFactory
{
    private static $_classes = NULL;
	
	/**
     * Load and create the instance of a specifield db class
     *      with a specifield key, then return the instance
     *  And it will make sure the same class will only load once
     *
     * @param   $_class class key
     * @param   $_args  arguments to initialize the instance
    */
	public static function create( $_class, &$_conf )
	{
		if ( self::$_classes == NULL ) self::$_classes = array();
		
		//Fetch the class
		$_class = ucfirst( $_class ) . 'View';
		if ( ! isset( self::$_classes[$_class] ) )
		{
			require dirname(__FILE__).'/'.$_class.'.class.php';
			self::$_classes[$_class] = true;
		}
        
		//return the newly created instance
		return new $_class($_conf);
	}
}
?>