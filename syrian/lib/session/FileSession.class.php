<?php
/**
 * user level session handler class base on posix file system
 *    
 * @author chenxin<chenxin619315@gmail.com>
*/

class FileSession extends SessionBase
{
    private $_save_path  = null;
    private $_ext        = '.ses';
    private $_partitions = 1000;

    /**
     * construct method to initialize the class
     *
     * @param   $conf
     */
    public function __construct($conf)
    {
        if (isset($conf['save_path'])) {
            $this->_save_path = $conf['save_path'];
        }

        if (isset($conf['partitions'])) {
            $this->_partitions = $conf['partitions'];
        }

        if (isset($conf['file_ext'])) {
            $this->_ext = $conf['file_ext'];
        }

        parent::__construct($conf);
    }

    /** @see SessionBase#_add($uid, $val, &$errno=self::OK) */
    protected function _add($uid, $val, &$errno=self::OK)
    {
        return false;
    }

    /** @see SessionBase#_read($uid, $cas_token, &$exists) */
    function _read($uid, &$cas_token, &$exists=true)
    {
        // make the final session file
        $part_num = self::bkdrHash($uid, $this->_partitions);
        $_file = "{$this->_save_path}/{$part_num}/{$uid}{$this->_ext}";

        // check the existence and the lifetime
        if (! file_exists($_file)) {
            $exists = false;
            return '';
        }

        // @Note: atime update maybe closed by filesystem
        $ctime = max(filemtime($_file), fileatime($_file));
        if ($ctime + $this->_ttl < time()) {
            @unlink($_file);
            return '';
        }

        //get and return the content of the session file
        $_txt = file_get_contents($_file);
        return ($_txt == false ? '' : $_txt);
    }
    
    /** @see SessionBase#_update($uid, $val, $cas_token, &$errno=self::OK) */
    function _update($uid, $val, $cas_token, &$errno=self::OK)
    {
        // make the final session file
        $part_num = self::bkdrHash($uid, $this->partitions);
        $_baseDir = "{$this->_save_path}/{$part_num}";
        if (! file_exists($_baseDir)) {
            mkdir($_baseDir, 0755);
        }

        $_sfile = "{$_baseDir}/{$uid}{$this->_ext}";
        if (file_put_contents($_sfile, $_data) !== false) {
            // chmod the newly created file
            @chmod($_sfile, 0755);
            return true;
        }

        return false;
    }
    
    /** @see SessionBase#_delete($uid)*/
    function _delete($uid)
    {
        // check and delete the session file
        $part_num = self::bkdrHash($uid, $this->partitions);
        $_file = "{$this->_save_path}/{$part_num}/{$uid}{$this->_ext}";
        return file_exists($_file) ? unlink($_file) : false;
    }
    
    /* internal static bkdr hash function */
    private static function bkdrHash($_str, $_size)
    {
        $len   = strlen($_str);
        $_hash = 0;
    
        for ($i = 0; $i < $len; $i++) {
            $_hash = (int) ($_hash * 1331 + (ord($_str[$i]) % 127));
        }
        
        if ($_hash < 0) {
            $_hash *= -1;
        }

        if ($_hash >= $_size) {
            $_hash = (int) $_hash % $_size; 
        }
        
        return ($_hash & 0x7FFFFFFF);
    }

}
