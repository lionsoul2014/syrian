<?php
/**
 * Opert router main class:
 * 1. it offer base interface to parser the 3 kinds of request url:
 *      (1). OPT_DIR_URL: style like index.php/article/list.html
 *      (2). OPT_CON_URL: style like index.php/article-list.html
 *      (3). OPT_GET_URL: style like index.php?module=admin-article&page=list
 * 2. build the request url with the current style.
 *      @see Opert#makeStyleUrl
 * 3. manager the site config items.
 * 4. forwarding the request to the valid script file.
 * 5. quick method to import specified class.
 * 
 * @author chenxin <chenxin619315@gmail.com>
 */

//opert home constants
defined('OPT_HOME')     or  define('OPT_HOME', dirname(dirname(__FILE__)));

//URL style constants
defined('OPT_DIR_URL')  or  define('OPT_DIR_URL', 1);
defined('OPT_CON_URL')  or  define('OPT_CON_URL', 2);
defined('OPT_GET_URL')  or  define('OPT_GET_URL', 3);
//add more here...

class Opert
{
    public static   $_home      = NULL;     //home directory.
    public static   $_sysInfo   = NULL;     //system defiend configure array.
    public static   $_usrInfo   = NULL;     //user defined configure array
    public static   $_url       = NULL;     //absolute URL for current request(without query string)
    public static   $_ctrl      = NULL;     //module controller instance.
    
    private static  $_classes   = array();  //loaded classes.
    private static  $_request   = array();  //current request info.
    private static  $_ustyle    = NULL;     //url style.
    
    public static function init( $_home, $_ustyle, &$_cfg )
    {
        self::$_home    = $_home;
        self::$_ustyle  = $_ustyle;
        
        #set the system config
        if ( isset($_cfg['sys_cfg']) )
        {
            self::$_sysInfo =
                is_string($_cfg['sys_cfg']) ? include $_cfg['sys_cfg'] : $_cfg['sys_cfg'];
        }
        
        #set the usr config
        if ( isset($_cfg['usr_cfg']) )
        {
            self::$_usrInfo =
                is_string($_cfg['usr_cfg']) ? include $_cfg['usr_cfg'] : $_cfg['usr_cfg'];
        }
        
        self::$_request[0] = NULL;
        self::$_request[1] = NULL;
    }

    /**
     * import the class under the specified directory,
     *      and this method will make sure the same class be required once only.
     *
     * @param   $_class
     * @param   $_inc (true for search classes in opert lib)
     *          (false for search classes in skeleton lib)
     */
    public static function import( $_class, $_inc = true )
    {
        #if ( ! isset( self::$_classes[$_class] ) )
        #{
        #    self::$_classes[$_class] = true;
        #    require (($_inc) ? OPT_HOME : self::$_home) .
        #        '/' . str_replace('.', '/', $_class) . '.class.php';
        #}
        require_once (($_inc) ? OPT_HOME : self::$_home) . '/' . str_replace('.', '/', $_class) . '.class.php';
    }
    
    /**
     * parse and load the specified php source file into a buffer.
     *  also, the aim file must have style like:
     *      return array()....;
     *
     * @param   $_file  path of the file to load.
     * @param   $_inc   true for search the file in opert.
    */
    public static function load($_file, $_inc = false)
    {
        return require ( ($_inc) ? OPT_HOME : self::$_home ) . '/' . str_replace('.', '/', $_file) . '.php';
    }

    /**
     * parse the specified request url .
     *
     * @param   $_url (usually get from $_SERVER['REQUEST_URI'])
     */
    private static function parseUrl( $_url )
    {
        switch ( self::$_ustyle )
        {
            case OPT_DIR_URL:       /*style like index.php/article/list.html*/
            case OPT_CON_URL:       /*style like index.php/article-list.html*/
                $_delit = ( self::$_ustyle == OPT_DIR_URL ) ? '/' : '-';
                $s = stripos($_url, '.php/'); $slen = strlen($_url);
                if ( $s === FALSE || $s >= ($slen - 1) )
                    self::$_request = self::$_sysInfo['dft_url'];
                else
                {  //found '.php/' mark
                    if ( ($e = strpos($_url, '.html')) === FALSE )
                    {
                        $len = $slen - $s - 5;
                        if ( $_url[$slen - 1] ) $len--;
                        self::$_request[0] = substr($_url, $s + 5, $len);
                    }
                    else if ( ($lmark = strrpos($_url, $_delit, $s + 5)) === FALSE )
                    {
                        if ( self::$_ustyle == OPT_CON_URL )
                            self::$_request[0] = substr($_url, $s + 5, $e - $s - 5);
                        else die('Error: Invalid request for ' . $_url); 
                    }
                    else
                    {
                        self::$_request[0] = substr($_url, $s + 5, $lmark - $s - 5);
                        self::$_request[1] = substr($_url, $lmark + 1, $e - $lmark - 1);
                    }
                }
                break;
            case OPT_GET_URL:   /*complex one: index.php/module=admin-article&page=list*/
                if ( ! isset($_GET['module']) )
                    self::$_request = self::$_sysInfo['dft_url'];
                else
                {
                    self::$_request[0] = str_replace('-', '/', $_GET['module']);
                    if ( isset($_GET['page']) ) self::$_request[1] = $_GET['page'];
                }
                break;
            default : die('Error: Invalid URL style constants ' . self::$_ustyle);
        }
    }
    
    /**
     * build a validate router page address
     *  according to the specified module and page.
     *
     * @param   $_module    module name like: article, admin/article
     *          or an array like array(admin, article)
     * @param   $_page      page name like: list, view
     * @return  string      the rounter url with the specified style.
    */
    public static function makeStyleUrl( $_module, $_page )
    {
        $_url = self::$_usrInfo['url'].'/index.php';
        $_m = is_array($_module) ? implode('/', $_module) : $_module;
        
        switch ( self::$_ustyle )
        {
            case OPT_DIR_URL:
                $_url .= '/' . $_m . '/';
                if ( $_page != NULL ) $_url .= $_page . '.html';
                break;
            case OPT_CON_URL:
                $_url .= '/' . str_replace('/', '-', $_m);
                if ( $_page != NULL ) $_url .= '-' . $_page;
                $_url .= '.html';
                break;
            case OPT_GET_URL:
                $_url .= '?module=' . str_replace('/', '-', $_m );
                if ( $_page != NULL ) $_url .= '&page=' . $_page;
                break;
        }
        
        return $_url;
    }
    
    /**
     * redirect to the specified module and page
     *  base on Location header, this method will terminal the script.
     *
     * @param   $_url
     * @param   $_args
    */
    public static function redirect( $_module, $_page, $_args = NULL )
    {
        $_append = '';
        if ( $_args != NULL )
        {
            foreach ( $_args as $_name => $_value ) 
                $_append .= ($_append=='') ? $_name.'='.$_value : '&'.$_name.'='.$_value;
            $_append = '?'.$_append;
        }
        
        header('Location: ' . self::$_usrInfo['url'] .
                self::makeStyleUrl($_module, $_page) . $_append);
        exit(0);
    }
    
    /**
     * response the specified request .
     *
     * @param   $_url - current request url (better without query)
    */
    public static function response( $_url )
    {
        //parser the request url
        if ( ($_query = strpos($_url, '?')) !== FALSE ) $_url = substr($_url, 0, $_query);
        self::$_url = self::$_usrInfo['url'] . ( ($_url[0]=='/') ? $_url : '/' . $_url );
        self::parseUrl($_url);
        $_file = self::$_home.'/'.self::$_sysInfo['app_dir'].'/'.self::$_request[0].'/main.php';
        
        if ( ! file_exists($_file) )
            self::redirect('error', 'error', NULL);
        else
        {
            self::import('core.Controller', true);      //common base class.
            $_class = NULL;                             //module common class.
            if ( ( $oc = strrpos(self::$_request[0], '/') ) !== FALSE )
                $_class = ucfirst(substr(self::$_request[0], $oc)) . 'Controller';
            else
                $_class = ucfirst(self::$_request[0]) . 'Controller';
            
            $_ctrl = require $_file;          //requrie the module common class file.
            if ( ! ( $_ctrl instanceof Controller ) )
                die('Error: Invalid Common instance, it should be an instance of Opert.core.Common');
            self::$_ctrl = $_ctrl;
            $_ctrl->init();             //initialize the controller
            
            //register the globals variables.
            $_sysReg = array(
                '_MODULE'   => self::$_request[0],
                '_PAGE'     => self::$_request[1],
                '_CTRL'     => $_ctrl
                #'_VIEW'    => $_com->getView()
                #'_CACHE'   => $_ctrl->getCache()
            );
            
            //register the _VIEW and some template globals variables.
            if ( $_ctrl->getMask(CTRL_LOAD_VIEW) )
            {
                $_sysReg['_VIEW'] = $_ctrl->getView();
                $_sysReg['_VIEW']->assoc('SYS', self::$_sysInfo);
                $_sysReg['_VIEW']->assoc('USR', self::$_usrInfo);
                $_sysReg['_VIEW']->assign('SELF', self::$_url);
            }
            
            //check and register the _CACHE if it is set to;
            if ( $_ctrl->getMask(CTRL_LOAD_CACHE) ) $_sysReg['_CACHE'] = $_ctrl->getCache();
            //check and register the _DB if the it is set to.
            if ( $_ctrl->getMask(CTRL_LOAD_DB) )    $_sysReg['_DB'] = $_ctrl->getDatabase();
            extract($_sysReg, EXTR_OVERWRITE);
            $_gvars = $_ctrl->gVars();
            if ( $_gvars != NULL ) extract($_gvars, EXTR_OVERWRITE);
            
            //check the gzip status and compress the content if it is opened.
            if ( ! isset(self::$_sysInfo['gzip']) || ! self::$_sysInfo['gzip'] )
                require $_ctrl->getLogicScript( self::$_request[1] );
            else
            {
                self::import('lib.util.Func');
                ob_start('ob_gzip');
                require $_ctrl->getLogicScript( self::$_request[1] );
                ob_end_flush();
            }
        }
    }
    
    /**
     * get the home directory fo the current app.
     *
     * @return  string
    */
    public static function getHomeDir()
    {
        return self::$_home;
    }
    
    /**
     * return the current request info.
     *
     * @return  Array - self::$_request
    */
    public function getRequest()
    {
        return self::$_request;
    }
}
?>