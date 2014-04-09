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

    public function __construct( $_total, $_size, $_pageno )
    {
        $this->_total = $_total;
        $this->_size = $_size;
        $this->_pages = ceil($_total / $_size);
        
        $this->_pageno = intval($_pageno);
        if ( $this->_pageno == 0 ) $this->_pageno = 1;
        if ( $this->_pageno > $this->_pages ) $this->_pageno = $this->_pages;
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
     * for database query
     *
     * @param  $_query
    */
    public function limit( &$_query )
    {
        $_query .= ' limit '.(($this->_pageno - 1) * $this->_size) . ', ' . $this->_size;
    }
    
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
        $_link = $_SERVER['PHP_SELF'] . '?';
        $_forms = NULL;
        if ( $_args != '' )
        {
            $_link .= $_args.'&';
            
            $_forms = array();
            $_pair = explode('&', $_args);
            foreach ( $_pair as $_value ) {
                $_item = explode('=', $_value);
                if ( count($_item) != 2 ) continue;
                $_forms[$_item[0]] = $_item[1];
            }
        }
        $_link .= $_name.'=';
        
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
            $_html .= '<form name="ui_page_form" action="'.$_SERVER['PHP_SELF'].'" method="get">';
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
    
    public function getSize()
    {
        return $this->_size;
    }
    
    public function getPages()
    {
        return $this->_pages;
    }
}
?>