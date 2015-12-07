<?php
/**
 * HashMap class
 *
 * @author    chenxin<chenxin619315@gmail.com>
*/

 //----------------------------------------------------------

class HashMap
{
    /**
     * hash table
    */
    private $table    = NULL;

    /**
     * table rebuild for unseted index limit
    */
    private $maxAllowRemove    = 0;

    /**
     * the key that has removed
    */
    private $removedKeys    = 0;

    /**
     * current size of the table
    */
    private $size    = 0;

    /*
     * construct method
     *
     * @param    maxAllowRemove
    */
    public function __construct($maxAllowRemove=10)
    {
        $this->table            = array();
        $this->maxAllowRemove    = $maxAllowRemove;
    }

    //get the size of the table
    public function size()
    {
        return $this->size;
    }

    /**
     * put a new mapping to/replace the old mapping from the table
     *
     * @param    key
     * @param    val
    */
    public function put($key, $val)
    {
        $this->table["{$key}"] = &$val;
        $this->size++;

        return $this;
    }

    /**
     * remove the mapping associate with the specifield key
     *
     * @param    key
     * @return    bool
    */
    public function remove($key)
    {
        if ( ! isset($this->table["{$key}"]) )
        {
            return false;
        }

        unset($this->table["{$key}"]);
        $this->removedKeys++;
        $this->size--;

        //check and rebuild the table
        if ( $this->removedKeys >= $this->maxAllowRemove )
        {
            $tmpArr    = &$this->table;
            unset($this->table);

            //create a new table copy and replace the old one
            $this->table = array_combine(
                array_keys($tmpArr),
                array_values($tmpArr)
            );

            $this->removedKeys = 0;
        }

        return true;
    }

    /**
     * get the mapping associated with the specifield key
     *
     * @param    key
    */
    public function get($key)
    {
        if ( ! isset($this->table["{$key}"]) )
        {
            return NULL;
        }

        return $this->table["{$key}"];
    }
}
?>
