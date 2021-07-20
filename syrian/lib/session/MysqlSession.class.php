<?php
/**
 * user level session handler class .
 *
 * @author chenxin <chenxin619315@gmail.com>
*/
class DBSession
{
    private $Db     = NULL;
    private $_ttl   = 0;
    
    public static function start( $Db, $_ttl = 1800 )
    {
        new DBSession($Db, $_ttl);
    }
    
    public function __construct( &$Db, $_ttl )
    {
        $this->Db = &$Db;
        $this->_ttl = $_ttl;
        //set use user level session
        if(version_compare(PHP_VERSION,'7.2.0','<')) {
            session_module_name('user');
        }

        session_set_save_handler(
            array(this, 'open'),
            array(this, 'close'),
            array(this, 'read'),
            array(this, 'write'),
            array(this, 'destroy'),
            array(this, 'gc')
        );
        session_start();
    }
    
    function open( $_save_path, $_sessname )
    {
        return TRUE;
    }

    function close()
    {
        return TRUE;
    }
    
    function read( $_sessid )
    {
    
    }
    
    function write( $_sessid, $_data )
    {
        return TRUE;
    }
    
    function destroy( $_sessid )
    {
        return TRUE;
    }
    
    function gc()
    {
        return TRUE;
    }
}
?>
