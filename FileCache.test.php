<?
include 'FileCache.php';

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
error_reporting(E_ALL);
ini_set('display_errors', 1);

$cache = new FileCache('cachedir');

$cache->set_debug(false);
$cache->purge();

assert($cache->count()===0);

$cache->save('name', 'Bob Jones');

assert($cache->load('name')==='Bob Jones');

assert($cache->count()===1);

assert($cache->load('color')===null);

$array  = ['a'=>'apple', 'b'=>'banana'];
$array2 = ['a'=>'apple', 'b'=>'bananas'];

$cache->save('testarray', $array);

assert($cache->load('testarray')===$array);

assert($cache->load('testarray')!==$array2);

assert($cache->count()===2);

$cache->purge();

$tmp_cache_count = $cache->count();
assert($tmp_cache_count===0,
	"Cache count should be 0, was ".$tmp_cache_count.
	" with first item being ".print_r(current($cache->file_list()), true));

function add_two_numbers($a, $b) {
	return $a + $b;
}

assert($cache->load(['add_two_numbers', [1, 5]])===null);
assert($cache->memoized_function_call('add_two_numbers', [1, 5])===6);
assert($cache->last_memoized_function_call_hit_cache===false);
assert($cache->memoized_function_call('add_two_numbers', [1, 5])===6);
assert($cache->last_memoized_function_call_hit_cache===true);
assert($cache->filesize()===4);

$cache->purge();

rmdir('cachedir');
