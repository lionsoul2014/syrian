<?php
/**
 * data sets paging class
 *
 * @author chenxin<chenxin619315@gmail.com>
*/

//-----------------------------------------------------------

defined('UI_TOTAL') or  define('UI_TOTAL',          1 << 0);
defined('UI_PAGES') or  define('UI_PAGES',          1 << 1);         /*pageno / pages*/
defined('UI_FIRST') or  define('UI_FIRST',          1 << 2);
defined('UI_PREV')  or  define('UI_PREV',           1 << 3);
defined('UI_LEFT')  or  define('UI_LEFT',           1 << 4);
defined('UI_QUICK') or  define('UI_QUICK',          1 << 5);
defined('UI_NEXT')  or  define('UI_NEXT',           1 << 6);
defined('UI_LAST')  or  define('UI_LAST',           1 << 7);
defined('UI_INPUT') or  define('UI_INPUT',          1 << 8);
defined('UI_INPUT_PAGES')   or  define('UI_INPUT_PAGES',    1 << 9);
defined('UI_USE_BOOTSTRAP') or  define('UI_USE_BOOTSTRAP',  1 << 10);
defined('UI_SHOP_STYLE')    or  define('UI_SHOP_STYLE', UI_PREV | UI_LEFT | UI_QUICK | UI_NEXT | UI_INPUT | UI_INPUT_PAGES);
defined('UI_DEFAULT_STYLE') or  define('UI_DEFAULT_STYLE', UI_TOTAL | UI_PAGES | UI_FIRST | UI_PREV | UI_QUICK | UI_NEXT | UI_LAST);

class Page
{
    
    private $_total;
    private $_size;         /*page size*/
    private $_pages;        /*total pages*/
    private $_pageno;

    private $_lang = array(
        'total'     => '总记录：',
        'pages'     => '共{pages}页',
        'first'     => '首页',
        'prev'      => '&lt;&lt;上一页',
        'next'      => '下一页&gt;&gt;',
        'last'      => '尾页',
        'input'     => '到第{input}页',
        'submit'    => '确定'
    );

    /**
     * construct method to initialize the class
     *
     * @param   $_total
     * @param   $_size
     * @param   $_pageno
    */
    public function __construct( $_total, $_size, $_pageno )
    {
        $this->_total   = intval($_total);
        $this->_size    = intval($_size);
        $this->_pages   = ceil($_total / $_size);

        $this->_pageno  = intval($_pageno);
        if ( $this->_pageno > $this->_pages ) $this->_pageno = $this->_pages;
        if ( $this->_pageno == 0 ) $this->_pageno = 1;
    }
    
    /**
     * create a Page instance
     *
     * @param  $_total
     * @param  $_size
     * @param  $_pageno
     * @return Page
    */
    public function create( $_total, $_size, $_pageno )
    {
        return new Page($_total, $_size, $_pageno);
    }
    
    public function setLang( $_key, $_value )
    {
        if ( isset( $this->_lang[$_key] ) )
            $this->_lang[$_key] = $_value;
        return $this;
    }
    
    /**
     * Get the offset of the data sets
     *
     * @return  int
    */
    public function getOffset()
    {
        return ( $this->_pageno - 1 ) * $this->_size;
    }
    
    /**
     * show the page handling menu
     * 
     * @param  $_args
     * @param  $_style
     * @param  $_name
     * @param  $_left
     * @param  $_offset
    */
    public function show($_args = '', $_style = UI_DEFAULT_STYLE,
                        $_name = 'pageno', $_left = 2, $_offset = 2 )
    {
        /*
         yat, get the current request url
        */
        $_link  = $_SERVER['REQUEST_URI'];
        if ( ($args = strpos($_link, '?') ) !== false )
            $_link  = substr($_link, 0, $args + 1);
        else $_link .= '?';

        //make the request arguments
        $_forms = NULL;
        if ( $_args != '' )
        {
            $_link .= $_args.'&';
            
            $_forms = array();
            $_pair = explode('&', $_args);
            foreach ( $_pair as $_value ) 
            {
                $_item = explode('=', $_value);
                if ( count($_item) != 2 ) continue;
                $_forms[$_item[0]] = $_item[1];
            }
        }
        $_link .= $_name.'=';
        
        //---------------------------------------------------------------

        if ( ($_style & UI_USE_BOOTSTRAP) != 0 ) {
            $_html = $this->bootstrapUI($_link, $_name, $_style, $_left, $_offset, $_forms);
        } else {
            $_html = $this->defaultUI($_style, $_name, $_left, $_offset, $_link, $_forms);
        }
        
        return $_html;
    }

    private function defaultUI($_link, $_name, $_style, $_left, $_offset, $_forms)
    {
        $_html = '<div class="ui-page-box">';
        if ( ($_style & UI_TOTAL) != 0 )
            $_html .= '<a class="ui-page-total">'.$this->_lang['total'] . $this->_total.'</a>';
        if ( ($_style & UI_PAGES) )
            $_html .= '<a class="ui-page-info">'.$this->_pageno . '/' . $this->_pages.'</a>';
            
        if ( ( $_style & UI_FIRST ) != 0 ) {
            if ( $this->_pageno > 1 )
                $_html .= '<a href="'.$_link.'1" class="ui-page-first ui-page-able">'.$this->_lang['first'].'</a>';
            else
                $_html .= '<a class="ui-page-first">'.$this->_lang['first'].'</a>';
        }
        if ( ($_style & UI_PREV) != 0 ) {
            if ( $this->_pageno > 1 )
                $_html .= '<a href="'.$_link.($this->_pageno-1).'" class="ui-page-prev ui-page-able">'.$this->_lang['prev'].'</a>';
            else
                $_html .= '<a class="ui-page-prev">'.$this->_lang['prev'].'</a>';
        }
        
        //start quick two handle
        
        $_start = $this->_pageno - $_offset;
        if ( $_start <= 0 ) $_start = 1;
        
        if ( ( $_style & UI_LEFT ) != 0 )
        {
            if ( $_start > 1 )
            {
                $_limit = ($_start == $_left ) ? 1 : $_left;
                for ( $i = 1; $i <= $_limit; $i++ ) 
                    $_html .= '<a href="'.$_link.$i.'" class="ui-page-able">'.$i.'</a>';
                if ( $_limit != 1 ) $_html .= '<a class="ui-page-nobb ui-page-lb">...</a>';
            }
        }
        
        if ( ( $_style & UI_QUICK ) != 0 )
        {
            //prev pages
            for ( $i = $_start; $i < $this->_pageno; $i++  ) 
                $_html .= '<a href="'.$_link.$i.'" class="ui-page-able">'.$i.'</a>';
            
            $_html .= '<a class="ui-page-now">'.$this->_pageno.'</a>';
            
            $_end = $this->_pageno + $_offset;
            if ( $_end > $this->_pages ) $_end = $this->_pages;
            for ( $i = $this->_pageno + 1; $i <= $_end; $i++  ) 
                $_html .= '<a href="'.$_link.$i.'" class="ui-page-able">'.$i.'</a>';
        }
        
        if ( ($_style & UI_NEXT) != 0 )
        {
            if ( $this->_pageno < $this->_pages ) 
                $_html .= '<a href="'.$_link.($this->_pageno+1).'" class="ui-page-next ui-page-able">'.$this->_lang['next'].'</a>';
            else
                $_html .= '<a class="ui-page-first">'.$this->_lang['next'].'</a>';
        }
        
        if ( ($_style & UI_LAST) != 0 )
        {
            if ( $this->_pageno < $this->_pages ) 
                $_html .= '<a href="'.$_link.$this->_pages.'" class="ui-page-last ui-page-able">'.$this->_lang['last'].'</a>';
            else 
                $_html .= '<a class="ui-page-prev">'.$this->_lang['last'].'</a>';
        }
        
        
        if ( ($_style & UI_INPUT) != 0 )
        {
            if ( ($_style & UI_INPUT_PAGES) != 0 )
            $_html .= '<a class="ui-page-pages ui-page-nobb ui-page-lb">'.str_replace('{pages}', $this->_pages, $this->_lang['pages']).'</a>';
            $_html .= '<form name="ui_page_form" action="'.($_SERVER['REQUEST_URI']).'" method="get" id="ui-page-form">';
            $_html .= '<a class="ui-input-box">'.str_replace('{input}', '<input type="text" name="'.$_name.'" class="ui-page-input" value="'.$this->_pageno.'"/>', $this->_lang['input']).'</a>';
            $_html .= '<a class="ui-submit-box"><input type="button" onclick="if(document.ui_page_form.elements[0].value.match(/([0-9]{1,})/)!=null) document.ui_page_form.submit();" value="'.$this->_lang['submit'].'" class="ui-page-button"/></a>';
            if ( $_forms != NULL )
            {
                foreach ( $_forms as $_key => $_val )
                    $_html .= '<input type="hidden" name="'.$_key.'" value="'.$_val.'" />';
            }
            $_html .= '</form>';
        }
        $_html .= '</div>';

        return $_html;
    }

    //@NOTE this is need admin/common.css
    private function bootstrapUI($_link, $_name, $_style, $_left, $_offset, $_forms)
    {
        $_html = '<nav><ul class="pagination">';
        if ( ($_style & UI_TOTAL) != 0 )
            $_html .= '<li><a>'.$this->_lang['total'].$this->_total.'</a></li>';
        if ( ($_style & UI_PAGES) )
            $_html .= '<li><a>'.$this->_pageno.'/'.$this->_pages.'</a></li>';
            
        if ( ( $_style & UI_FIRST ) != 0 ) {
            if ( $this->_pageno > 1 )
                $_html .= '<li><a href="'.$_link.'1">'.$this->_lang['first'].'</a></li>';
            else
                $_html .= '<li class="disabled"><a>'.$this->_lang['first'].'</a></li>';
        }
        if ( ($_style & UI_PREV) != 0 ) {
            if ( $this->_pageno > 1 )
                $_html .= '<li><a href="'.$_link.($this->_pageno-1).'">'.$this->_lang['prev'].'</a></li>';
            else
                $_html .= '<li class="disabled"><a>'.$this->_lang['prev'].'</a></li>';
        }
        
        //start quick two handle
        $_start = $this->_pageno - $_offset;
        if ( $_start <= 0 ) $_start = 1;
        
        if ( ( $_style & UI_LEFT ) != 0 )
        {
            if ( $_start > 1 )
            {
                $_limit = ($_start == $_left ) ? 1 : $_left;
                for ( $i = 1; $i <= $_limit; $i++ ) 
                    $_html .= '<li><a href="'.$_link.$i.'">'.$i.'</a></li>';
                if ( $_limit != 1 ) $_html .= '<li><a>...</a></li>';
            }
        }
        
        if ( ( $_style & UI_QUICK ) != 0 )
        {
            //prev pages
            for ( $i = $_start; $i < $this->_pageno; $i++  ) 
                $_html .= '<li><a href="'.$_link.$i.'">'.$i.'</a></li>';
            
            $_html .= '<li class="active"><a href="#">'.$this->_pageno.'</a></li>';
            
            $_end = $this->_pageno + $_offset;
            if ( $_end > $this->_pages ) $_end = $this->_pages;
            for ( $i = $this->_pageno + 1; $i <= $_end; $i++  ) 
                $_html .= '<li><a href="'.$_link.$i.'">'.$i.'</a></li>';
        }
        
        if ( ($_style & UI_NEXT) != 0 )
        {
            if ( $this->_pageno < $this->_pages ) 
                $_html .= '<li><a href="'.$_link.($this->_pageno+1).'">'.$this->_lang['next'].'</a></li>';
            else
                $_html .= '<li class="disabled"><a>'.$this->_lang['next'].'</a></li>';
        }
        
        if ( ($_style & UI_LAST) != 0 )
        {
            if ( $this->_pageno < $this->_pages ) 
                $_html .= '<li><a href="'.$_link.$this->_pages.'">'.$this->_lang['last'].'</a></li>';
            else 
                $_html .= '<li class="disabled"><a>'.$this->_lang['last'].'</a></li>';
        }
        
        
        if ( ($_style & UI_INPUT) != 0 )
        {
            if ( ($_style & UI_INPUT_PAGES) != 0 )
            $_html .= '<li><a>'.str_replace('{pages}', $this->_pages, $this->_lang['pages']).'</a></li>';
            $_html .= '<li><a style="width: 180px; padding: 0; height: 31px;">';
            $_html .= '<form name="ui_page_form" action="'.($_SERVER['REQUEST_URI']).'" method="get" id="ui-page-form">';
            $_html .= '<div class="input-group input-group-sm syrian-page-form">';
            $_html .= '<span class="input-group-addon page-msg-start">到第</span>';
            $_html .= '<input type="text" class="form-control page-msg-input" name="'.$_name.'" value="'.$this->_pageno.'">';
            $_html .= '<span class="input-group-addon page-msg-end">页</span>';
            $_html .= '<span class="input-group-btn">';
            $_html .= '<button class="btn btn-default page-submit-btn" type="button" onclick="if(document.ui_page_form.elements[0].value.match(/([0-9]{1,})/)!=null) document.ui_page_form.submit();">'.$this->_lang['submit'].'</button>';
            $_html .= '</span>';
            $_html .= '</div>';
            if ( $_forms != NULL )
            {
                foreach ( $_forms as $_key => $_val )
                    $_html .= '<input type="hidden" name="'.$_key.'" value="'.$_val.'" />';
            }
            $_html .= '</form>';
            $_html .= '</a></li>';
        }
        $_html .= '</ul></nav>';
        
        return $_html;
    }
    
    public function getSize()
    {
        return $this->_size;
    }
    
    /**
     * return the total pages
     *
     * @return  int
    */
    public function getPages()
    {
        return $this->_pages;
    }
}
?>
