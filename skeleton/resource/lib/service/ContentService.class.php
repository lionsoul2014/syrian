<?php
/**
 * CommonContent service class for data model content handler
 *
 * @author  chenxin<chenxin619315@gmail.com>
 * @date    2016/08/13
*/

import('Util');
import('service.Service');

class ContentService extends Service
{
    # data model path
    protected $dataModel   = null;

    # index model path
    protected $indexModel  = null;

    # search model path
    protected $searchModel = null;

    /**
     * query a data list througth the specified query condition
     *
     * @param   $input
     * @param   $model
     * @param   $where
     * @param   $order
     * @param   $limit
     * @param   $shift do the result shitf
     * @return  Mixed(Array or false for empty data sets)
    */
    protected function getDataSets(
        $input, $model, $where, $order=null, $limit=null, $shift=false)
    {
        return false;
    }

    /**
     * query a data item with specified primary key
     *
     * @param   $input
     * @param   $model
     * @param   $where
     * @return  Mixed(Array or false for failed)
    */
    protected function getData($input, $model, $where)
    {
        return false;
    }

    /**
     * get the application base where
     *
     * @param   $input
     * @return  Array
    */
    protected function pre_where($input)
    {
        return array();
    }

    /**
     * get the application base order
     *
     * @param   $input
     * @param   $pKey
     * @return  Array
    */
    protected function pre_order($input, $pKey)
    {
        $order_by = $input->get('order_by');
        $sort_by  = $input->get('sort_by', 'desc');

        /*
         * check and append the primary key sorting
         * cuz the cursor_time won't not be unique and 
         * for mysql this may cuz unknow debug
        */
        $order = $order_by == false ? array() : array($order_by => $sort_by);
        if ( $order_by != $pKey ) {
            $order[$pKey] = $sort_by;
        }

        return $order;
    }

    /**
     * list the data sets
     * @Note this one is just a wrapper for #getDataSets
     *
     * @param   $input
     * @param   Mixed(Array or false for empty data sets)
    */
    public function _list($input)
    {
        $where = $input->get('where');
        $order = $input->get('order');
        $limit = $input->get('limit');
        $shift = $input->get('shift', false);

        $model = model($this->dataModel);
        $data  = $this->getDataSets(
            $input, $model, $where, $order, $limit, $shift
        );

        unset($where, $order, $limit, $shift, $model);
        return $data;
    }

    /**
     * model data sets default scroll handler
     *
     * @param   $input
     * @param   Mixed(Array or false for empty data sets)
    */
    public function scroll($input)
    {
        $cursor    = $input->get('cursor');
        $direction = $input->get('direction', 'forward');
        $pagesize  = $input->get('pagesize', 20);
        $order_by  = $input->get('order_by');
        $sort_by   = strtolower($input->get('sort_by', 'desc'));

        $operator = $sort_by == 'desc' ? '<=' : '>=';
        # got to reverse the sort way for the backward operation
        if ( $direction[0] == 'b' ) {
            $sort_by = $sort_by == 'desc' ? 'asc' : 'desc';
        }

        $where = $this->pre_where($input);
        $shift = false;
        if ( $cursor != false ) {
            $pagesize++; 
            $shift = true;
            $where[$order_by] = "{$operator}{$cursor}";
        }

        $model = model($this->dataModel);
        $order = $this->pre_order($input, $model->getPrimaryKey());
        $limit = array(0, $pagesize);
        $data  = $this->getDataSets(
            $input, $model, $where, $order, $limit, $shift
        );

        unset($operator, $pKey, $model);
        unset($where, $order, $limit);

        # check and to the result reverse
        if ( $direction[0] == 'b' ) {
            $list = array_reverse($data);
            unset($data);
            $data = $list;
        }

        return $data;
    }

    /**
     * model data sets default paging handler
     *
     * @param   $input
     * @return  Mixed(Array or false for empty data sets)
    */
    public function paging($input)
    {
        $pageno   = $input->get('pageno', 1);
        $pagesize = $input->get('pagesize', 20);

        $where = $this->pre_where($input);
        $model = model($this->dataModel);
        $order = $this->pre_order($input, $model->getPrimaryKey());
        $limit = array(($pageno - 1) * $pagesize, $pagesize);
        $data  = $this->getDataSets($input, $model, $where, $order, $limit);
        unset($where, $model, $pKey, $order, $limit);

        return $data;
    }

    /**
     * model data sets default search handler
     *
     * @param   $input
     * @return  Mixed(Array or false for empty data sets)
    */
    public function search($input)
    {
        return false;
    }

    /**
     * model data view default handler
     *
     * @param   $input
     * @param   Mixed(Array or false for failed)
    */
    public function view($input)
    {
        $id = $input->get('id');
        if ( $id == null ) {
            return null;
        }

        $model = model($this->dataModel);
        $data  = $this->getData(
            $input, $model, array($model->getPrimaryKey() => "={$id}")
        );

        unset($model);
        return $data;
    }
    
}
?>
