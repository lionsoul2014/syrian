<?php
/**
 * dynamic content redis NoSQL cache class.
 *
 * @author chenxin <chenxin619315@gmail.com>
*/
class RedisCache implements ICache
{

    public function __construct( $_args = NULL )
    {
    
    }

   public function baseKey( $_baseKey ){}
   public function factor ( $_factor ){}
   public function fname  ( $_fname ){}
   public function get    ( $_time ){}
   public function set    ( $_content, $_ttl=NULL, $mode=NULL){}
   public function exists () {}
   public function remove (){}
    
}
?>
