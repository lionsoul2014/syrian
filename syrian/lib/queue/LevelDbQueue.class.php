<?php
/**
 * leveldb queue
 *
 * @author will<530911044@qq.com>
 */
class LevelDbQueue implements IQueue
{
    private $_path;

    private $_leveldb = NULL;

    private static $_iterator = NULL;
    private static $_batch    = NULL;

    public function __construct(&$conf)
    {
        if( ! isset($conf['path']) || empty($conf['path']) ) {
            throw new Exception('Leveldb Data path should not be empty');
        }

        $this->_path = $conf['path'];

        $options = NULL;
        if( isset($conf['options']) && ! empty($conf['options']) ) {
            $options = $conf['options'];
        }

        $readoptions = NULL;
        if( isset($conf['readoptions']) && ! empty($conf['readoptions']) ) {
            $readoptions = $conf['readoptions'];
        }

        $writeoptions = NULL;
        if( isset($conf['writeoptions']) && ! empty($conf['writeoptions']) ) {
            $writeoptions = $conf['writeoptions'];
        }

        $this->_leveldb = new LevelDb($this->_path, $options, $readoptions, $writeoptions);
    }

    public function put($key, $value)
    {
        return $this->_leveldb->put($key, $value);
    }

    public function set($key, $value)
    {
        return $this->_leveldb->set($key, $value);
    }

    public function get($key)
    {
        return $this->_leveldb->get($key);
    }

    public function getSnapshot()
    {
        return $this->_leveldb->getSnapshot();
    }

    public function getBySnapshot($key, $snapshot)
    {
        $read_opstions = array("snapshot" => $snapshot);

        return $this->_leveldb->get($key, $read_opstions);
    }

    public function delete($key)
    {
        return $this->_leveldb->delete($key);
    }

    /**
     * forward and backward iteration is supported over the data
     *
     * @return LevelDBIterator
     */
    public function getIterator()
    {
        if( is_null(self::$_iterator) ) {
            self::$_iterator = new LevelDBIterator($this->_leveldb);
        }

        return self::$_iterator;
    }

    /**
     * through the data
     *
     * @return array
     */
    public function loop()
    {
        $iterator = $this->getIterator();

        $data = array();
        // loop in iterator style
        while( $iterator->valid() ) {
            $data[$iterator->key()] = $iterator->current();
        }

        return $data;
    }

    /**
     * multiple changes can be made in one atomic batch.
     *
     * @return LevelDBWriteBatch
     */
    public function getAtomicBatch()
    {
        if( is_null(self::$_batch) ) {
            self::$_batch = new LevelDBWriteBatch();
        }

        return self::$_batch;
    }

    /**
     * write once
     *
     * @param $batch
     * @return bool
     */
    public function writeAtomicBatch($batch)
    {
        return $this->_leveldb->write($batch);
    }

    /**
     * after database been closed, you can't do anything
     */
    public function close()
    {
        $this->_leveldb->close();
    }

    /**
     * if you can't open a database, neither been locked or other error,
     * if it's corrupted, you could use LevelDB::repair('/path/to/db') to repair it.
     * it will try to recover as much data as possible.
     */
    public function repair()
    {
        LevelDb::repair($this->_path);
    }

    /**
     * be careful with this.
     * @NOTE before you destroy a database, please make sure it was closed. or an exception will thrown. (LevelDB >= 1.7.0)
     *
     * @param bool|false $force
     */
    public function destroy($force = false)
    {
        if($force === true) {
            LevelDb::destroy($this->_path);
        }
    }
}