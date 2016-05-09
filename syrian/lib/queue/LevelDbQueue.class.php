<?php
/**
 * leveldb queue
 *
 * @author will<530911044@qq.com>
 */
class LevelDbQueue implements IQueue
{
    private $path;
    private $options      = NULL;
    private $readoptions  = NULL;
    private $writeoptions = NULL;

    private $_leveldb = NULL;

    private static $_iterator = NULL;
    private static $_batch    = NULL;

    public function __construct(&$conf)
    {
        if ( ! isset($conf['path']) || empty($conf['path']) ) {
            throw new Exception('Leveldb Data path should not be empty');
        }

        $this->path = $conf['path'];

        if ( isset($conf['options']) && ! empty($conf['options']) ) {
            $this->options= $conf['options'];
        }

        if ( isset($conf['readoptions']) && ! empty($conf['readoptions']) ) {
            $this->readoptions = $conf['readoptions'];
        }

        if ( isset($conf['writeoptions']) && ! empty($conf['writeoptions']) ) {
            $this->writeoptions = $conf['writeoptions'];
        }

        $this->_leveldb = new LevelDB($this->path, $this->options, $this->readoptions, $this->writeoptions);
        if ( $this->_leveldb == false ) {
            /**
             * if it's corrupted, you could use LevelDB::repair('/path/to/db') to repair it.
             * it will try to recover as much data as possible.
             */
            throw new Exception("Can't open this database, locked or other error");
        }
    }

    public function put($key, $value)
    {
        return $this->_leveldb->put($key, $value, $this->writeoptions);
    }

    public function set($key, $value)
    {
        return $this->_leveldb->set($key, $value, $this->writeoptions);
    }

    public function get($key)
    {
        return $this->_leveldb->get($key, $this->readoptions);
    }

    /**
     * snapshots provide consistent read-only views over the entire state of the key-value store.
     *
     * @return mixed
     */
    public function getSnapshot()
    {
        return $this->_leveldb->getSnapshot();
    }

    /**
     * $read_options['snapshot'] may be non-NULL to indicate that a read should operate on a particular version of the DB state.
     * if $read_options['snapshot'] is NULL, the read will operate on an implicit snapshot of the current state.
     *
     * @param $key
     * @param null $snapshot
     * @return mixed
     */
    public function getBySnapshot($key, $snapshot)
    {
        if ( is_array($this->readoptions) ) {
            $this->readoptions["snapshot"] = $snapshot;
            return $this->_leveldb->get($key, $this->readoptions);
        }
        else {
            $readoptions = array("snapshot" => $snapshot);
            return $this->_leveldb->get($key, $readoptions);
        }
    }

    /**
     * forward and backward iteration is supported over the data
     * base method: valid()
     * And you could seek with: rewind(), next(), prev(), seek(), last()
     *
     * @return LevelDBIterator
     */
    public function getIterator()
    {
        if ( is_null(self::$_iterator) ) {
            self::$_iterator = new LevelDBIterator($this->_leveldb);
        }

        return self::$_iterator;
    }

    public function first()
    {
        $iterator = $this->getIterator();
        $iterator->rewind();

        if ( $iterator->valid() ) {
            $value = $iterator->current();

            return $value;
        }

        return NULL;
    }

    /**
     * shifts the first value of the queue off and returns it
     *
     * @return mixed|null
     */
    public function shift()
    {
        $iterator = $this->getIterator();
        $iterator->rewind();

        if ( $iterator->valid() ) {
            $value = $iterator->current();
            $this->delete($iterator->key());

            return $value;
        }

        return NULL;
    }

    /**
     * shifts the last value of the queue off and returns it
     *
     * @return mixed|null
     */
    public function pop()
    {
        $iterator = $this->getIterator();
        $iterator->last();

        if ( $iterator->valid() ) {
            $value = $iterator->current();
            $this->delete($iterator->key());

            return $value;
        }

        return NULL;
    }

    public function last()
    {
        $iterator = $this->getIterator();
        $iterator->last();

        if ( $iterator->valid() ) {
            $value = $iterator->current();

            return $value;
        }

        return NULL;
    }

    public function delete($key)
    {
        return $this->_leveldb->delete($key, $this->writeoptions);
    }

    /**
     * through the data
     *
     * @param bool|false $reverse
     * @return array
     */
    public function loop($reverse = false)
    {
        $iterator = $this->getIterator();

        $data = array();

        // loop in iterator style
        if ( $reverse === false ) {
            $iterator->rewind();
            while( $iterator->valid() ) {
                $data[$iterator->key()] = $iterator->current();

                $iterator->next();
            }
        }
        else {
            $iterator->last();
            while( $iterator->valid() ) {
                $data[$iterator->key()] = $iterator->current();

                $iterator->prev();
            }
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
        if ( is_null(self::$_batch) ) {
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

    public function setOptions($options)
    {
        $this->options = $options;
    }

    public function setReadoptions($readoptions)
    {
        $this->readoptions = $readoptions;
    }

    public function setWriteoptions($writeoptions)
    {
        $this->writeoptions = $writeoptions;
    }

    /**
     * after database been closed, you can't do anything
     */
    public function close()
    {
        return $this->_leveldb->close();
    }

    /**
     * be careful with this.
     * @NOTE before you destroy a database, please make sure it was closed. or an exception will thrown. (LevelDB >= 1.7.0)
     *
     * @param bool|false $force
     * @return bool
     */
    public function destroy($force = false)
    {
        if ( $force === true ) {
            $this->close();
            return LevelDB::destroy($this->path);
        }

        return false;
    }
}