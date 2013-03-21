php-file-cache
==============

Disk file-based cache for those times when you have super slow operations, but no memcached or redis.

Usage:

$cache = new FileCache('mytmpdir');

$cache->load('test');
# returns null

$cache->save('test', ['a'=>'apple', 'b'=>'banana']);
$array = $cache->load('test');
# returns the fruit array

$result = $cache->memoized_function_call('big_io_function', ['arg1', 2, ['three'=>3]]);
# thinks for a while
$result = $cache->memoized_function_call('big_io_function', ['arg1', 2, ['three'=>3]]);
# thinks not very long at all

$cache->count();
# returns 2 at this point

$cache->filesize();
# returns current size on disk of all cache files

$cache->purge();
# deletes files in the cache folder.  leaves containing folder alone.
