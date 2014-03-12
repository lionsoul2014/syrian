<?php
/**
 * dynamic content cache common interface .
 *
 * @author chenxin<chenxin619315@gmail.com>
*/
interface ICache
{
    public function get( $_baseId, $_factor, $_time );
    public function set( $_baseId, $_factor, $_content );
}
?>