<?php
/**
 * simple key/value db for small data sets.
 *
 * @author chenxin <chenxin619315@gmail.com>
*/
class SDB
{
    
    private $_ifile = NULL;
    private $_dfile = NULL;
    private $_pointer = 0;
    
    //index
    private $_idx = array();
    private $_fspace = array();
    private $_save = false;
    
    public function __construct( $_dir, $_name )
    {
        $_ifile = $_dir.'/'.$_name.'.idx';
        $_dfile = $_dir.'/'.$_name.'.dat';
        
        $_handler = NULL;
        
        //create the index/data file if necessary.
        if ( ! file_exists( $_ifile ) )
        {
            if ( ($_handler = fopen($_ifile, 'w') ) == FALSE )
                exit("Error: Unable to create the index file for database\n");
            fclose($_handler);
            chmod($_ifile, 0755);
        }
        $this->_ifile = $_ifile;
        
        if ( ! file_exists( $_dfile ) )
        {
            if (  ($_handler = fopen($_dfile, 'w')) == FALSE )
                exit("Error: Unable to create the data file for database\n");
            fclose($_handler);
            chmod($_dfile, 0755);
        }
        
        
        //open the index/data file.
        if ( ( $this->_dfile = fopen($_dfile, 'a+', false) ) == FALSE )
            exit("Error: Unable to open the data file for database\n");
        #if ( ( $this->_ifile = fopen($_ifile, 'a+', false) ) == FALSE )
        #    exit('Error: Unable to open the index file for database ' . $_name);
        $this->_pointer = ftell( $this->_dfile );
        
        if ( ($_data = file_get_contents($_ifile)) === FALSE )
            exit("Error: Unable to open the index file for database\n");
        $_data = unpack('A*', $_data);
        $this->_idx = unserialize($_data[1]);
        unset($_data);        //Let gc do its work.
    }
    
    /**
     * fetch the record asscociated with the specified key.
     *
     * @param    $_key
     * @return    NULL or string
    */
    public function fetch( $_key )
    {
        if ( ! isset( $this->_idx[$_key] ) ) return false;
        
        flock($this->_dfile, LOCK_SH);
        //locate the data.
        if ( fseek($this->_dfile, $this->_idx[$_key]) == -1 )
            return false;
        
        $_tmp = NULL;
        //read the data flag and length
        if ( fread( $this->_dfile, 4 ) == FALSE )
            exit('Error: unable to read from data file');
        if ( ( $_tmp = fread( $this->_dfile, 4 ) ) == FALSE )
            exit('Error: unable to read from data file');
            
        //unpack the data and get the length.
        $_bucket = unpack('I', $_tmp);
        $_length = $_bucket[1];
        
        //read the data
        if ( ( $_tmp = fread( $this->_dfile, $_length ) ) == FALSE )
            exit('Error: unable to read from data file');
            
        flock($this->_dfile, LOCK_UN);            //unlock the data file.
        $_data = unpack('A'.$_length, $_tmp);    //unpack the data.
        
        return $_data[1];
    }
    
    /**
     * associated the specified key with the value.
     *     and store them in the data file.
     *
     * @param    $_key
     * @param    $_val
     * @param    $_mode
     * @return    bool
    */
    public function store( $_key, $_val, $_rewrite = false )
    {
        if ( isset($this->_idx[$_key])
                && ! $_rewrite ) return false;
        
        flock($this->_dfile, LOCK_EX);        //lock the file.
        
        //move the file pointer and set the index.
        if ( fseek($this->_dfile, $this->_pointer) == -1 )
            return false;
        
        $this->_idx[$_key] = $this->_pointer;
        
        //write the data
        $_length = strlen($_val);
        //flag
        if ( fwrite($this->_dfile, pack('I', '1')) == FALSE )        
            exit('Error: error write to data file');
        //length
        if ( fwrite($this->_dfile, pack('I', $_length)) == FALSE )
            exit('Error: error write to data file');
        //data
        if ( fwrite($this->_dfile, pack('A'.$_length, $_val)) != $_length )
            exit('Error: error write to data file');
        
        //reset the pointer and unlock the file.
        $this->_pointer = ftell($this->_dfile);
        flock($this->_dfile, LOCK_UN);
        $this->_save = true;
        
        return true;
    }
    
    /**
     * save the change to the disk.
     *     mainly update the index.
    */
    public function save()
    {
        if ( ! $this->_save ) return true;
        $this->_save = false;
        $_data = pack('A*', serialize($this->_idx));
        return file_put_contents($this->_ifile, $_data, LOCK_EX);
    }
    
    /**
     * remove the record from the database
     *     associated with the specified key.
     *
     * @param    $_key
     * @return    bool
    */
    public function remove( $_key )
    {
        //check the existence.
        if ( ! isset( $this->_idx[$_key] ) ) return false;
        
        //located the data.
        $_offset = $this->_idx[$_key];
        if ( fseek($this->_dfile, $_offset) == -1 )
            return false;
        
        //set the flag
        if ( fwrite($this->_dfile, pack('I', '0')) == FALSE )
            exit('Error: error write to data file');
        
        unset($this->_idx[$_key]);    //remove the key from the index.
        $this->_save = true;
        
        if ( ($_data = fread($this->_dfile, 4)) == FALSE )
            exit('Error: Unable to read from the data file.');
        
        //record the free space
        $_data = unpack('I', $_data);
        $_key = ''.$_offset;
        if ( isset( $this->_fspace[$_key] ) ) 
            $this->_fspace[$_key][] = $_data[1];
        else
            $this->_fspace[$_key] = array($_data[1]);
        
        return true;
    }
    
    public function __destruct()
    {
        //close the data file resource.
        if ( $this->_dfile != NULL ) fclose($this->_dfile);    
    }
}
?>
