<?php
/**
 * HashMap util test script
 *
 * @author	chenxin<chenxin619315@gmail.com>
*/

require dirname(dirname(__FILE__)).'/util/HashMap.class.php';

$values	= array('C', 'Java', 'PHP', 'Lua', 'Python', 'JavaScript', 'CSS', 'HTML');
$map	= new HashMap(5);

//test add
echo "+-Testing add: \n";
foreach ( $values as $lang )
{
	$val = "value for lang#{$lang}";
	echo "---Put({$lang}, {$val})\n";
	$map->put($lang, $val);
}
echo "size: " . $map->size() . "\n\n";

//test fetch
echo "+-Testing get: \n";
foreach ( $values as $lang )
{
	echo "---get({$lang}): " . $map->get($lang) . "\n";
}
echo "size: " . $map->size() . "\n\n";

//test remove
echo "+-Testing remove: \n";
foreach ( $values as $lang )
{
	echo "---remove({$lang}): " . $map->remove($lang) . "\n";
}
echo "size: " . $map->size() . "\n\n";
?>
