<?php
/**
 * script merge manger class:
 * 1. merge more script to one
 * 2. self define the cache and Agentcache
 *
 * @author    chenxin<chenxin619315@gmail.com>
 */

class ScriptMerge
{
    //final merged content
    private    $_MEM    = NULL;

    //require base path
    private    $_basedir    = NULL;
    private $_clear        = false;
    private    $_scripts    = array();

    //clear rule
    private    $_rule    = array(
        '/\n\s{0,}\/\/[^\n]{0,}/is'    => "\n",            //line comment
        '/([\w|\s]{1,})\/\/[^\n]{0,}/is' => "$1",        //after comment
        '/\n\s{1,}([^\n]+)/'    => "$1\n",
        '/\n{2,}/'    => "\n"
    );

    //construct method
    //@param    $_basedir - script file base directory path end with '/'
    public function __construct( $_basedir, $_clear = true )
    {
        $this->_basedir = $_basedir;
        $this->_clear    = $_clear;
    }

    /**
     * add new script file to merge
     *
     * @param    script file name
     */
    public function append($_script)
    {
        $this->_scripts[] = &$_script;
        return $this;
    }

    /**
     * append a array
     */
    public function appendArray($_files)
    {
        $this->_scripts = array_merge($this->_scripts, $_files);
        return $this;
    }

    //run
    public function merge()
    {
        ob_start();
        foreach ( $this->_scripts as $val )
            require $this->_basedir.$val;
        $this->_MEM = ob_get_contents();
        ob_end_clean();

        //script clear
        if ( $this->_clear )
            $this->_MEM = preg_replace(array_keys($this->_rule), $this->_rule, $this->_MEM);

        return $this;
    }

    /**
     * set the content clear rule
     *
     * @param    $_rule - Array
     */
    public function setClearRule( $_rule )
    {
        $this->_rule = &$_rule;
        return $this;
    }

    /**
     * get the final merged content
     *
     * @return mixed String or NULL
     */
    public function getContent()
    {
        return $this->_MEM;
    }
}
?>
