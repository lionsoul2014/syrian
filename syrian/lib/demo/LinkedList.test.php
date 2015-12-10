<?php
/**
 * Linked List test script
 *
 * @author    chenxin<chenxin619315@gmail.com>
 */

require dirname(dirname(__FILE__)).'/util/LinkedList.class.php';

$data    = array(
    'C', 'Java', 'PHP', 'Python', 'JavaScript', 'CSS', 'HTML'
);

$list = new LinkedList();
echo "+--Appending elements: \n";
foreach ( $data as $val )
{
    $list->addLast($val);
}

echo '----size: '.$list->size()."\n";

echo "+--Testing the iterator: \n";
$it    = $list->iterator();
while ( $it->hasNext() )
{
    echo $it->next(),"\n";
    $it->remove();
}
echo '----size: '.$list->size()."\n";
?>
