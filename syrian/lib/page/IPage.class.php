<?php
/**
 * big data sets paging class
 *      and offers no page menu, for json output actully
 *
 * @author chenxin<chenxin619315@gmail.com>
*/

//-----------------------------------------------------------

class IPage
{
    private $_total;
    private $_pagesize;         /*page size*/
    private $_pages;            /*total pages*/
    private $_pageno;

    public function __construct( $_total, $_pagesize, $_pageno )
    {
        $this->_total       = $_total;
        $this->_pagesize    = $_pagesize;
        $this->_pages       = ceil($_total / $_pagesize);
        $this->_pageno      = intval($_pageno);

        if ( $this->_pageno == 0 )              $this->_pageno = 1;
        if ( $this->_pageno > $this->_pages )   $this->_pageno = $this->_pages;
    }
    
    /**
     * create a Page instance
     *
     * @param  $_total
     * @param  $_pagesize
     * @param  $_pageno
     * @return Page
    */
    public function create( $_total, $_pagesize, $_pageno )
    {
        return new Page($_total, $_pagesize, $_pageno);
    }
    
    /**
     * Get the search start offset
     *
     * @return  int
    */
    public function getOffset()
    {
        return ( $this->_pageno - 1 ) * $this->_pagesize;
    }
    
    //get the
    public function getSize()
    {
        return $this->_pagesize;
    }
    
    public function getPages()
    {
        return $this->_pages;
    }
}
?>