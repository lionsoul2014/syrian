<?php
/**
 * Standart Uri manager class extended from Uri parent boss
 *
 * @author chenxin <chenxin619315@gmail.com>
*/

class STDUri extends Uri
{
    public function __construct( $_rewrite, $_style )
    {
        parent::__construct( $_rewrite, $_style );
    }
    
    /**
     * Rewrite the parseUrl of Uri
     *
     * @see Uri#parseUrl()
    */
    public function parseUrl()
    {
        parent::parseUrl();
        
        //make the section, module and the page
        $_len = count($this->_parts);
        if ( $_len == 0 ) return false;
        
        /*
         * STD Uri ask the module/page must be there
        */
        if ( $_len == 1 ) {
            $this->module = strtolower($this->_parts[0]);
            return false;
        }
        
        //fetch the module and the page
        $this->module = strtolower($this->_parts[$_len - 2]);
        $this->page   = strtolower($this->_parts[$_len - 1]);
        
        //initialize the section
        if ( $_len == 3  ) {
            $this->section = $this->_parts[0];
        } else {
            $this->section = implode('/', array_slice($this->_parts, 0, $_len - 2));
        }
    }
    
    /**
     * Rewrite the getController of Uri
     *
     * @param   $_module    default module
     * @see Uri#getController()
    */
    public function getController( $_module )
    {
        //get the module main file
        if ( $this->module == NULL ) $this->module = $_module;
        
        $_ctrl_file = SR_CTRLPATH;
        if ( $this->section != NULL ) $_ctrl_file .= $this->section . '/';
        $_ctrl_file .= $this->module . '/main.php';
        
        //check the existence of the module main file
        if ( ! file_exists($_ctrl_file) ) {
            return NULL;
        }

        switch ( $this->_parts[0] ) {
        case 'cli':
            import('core.Cli_Controller', false);
            break;
        #add more case here
        default:
            import('core.C_Controller', false);
            break;
        }

        require $_ctrl_file;
        
        //get the controller class
        $_class = ucfirst($this->module) . 'Controller';
        if ( ! class_exists($_class) ) {
            return NULL;
        }
        
        $_CTRL = new $_class();
        
        //clear the temp variable
        unset($_ctrl_file, $_class);

        return $_CTRL;
    }

}
?>
