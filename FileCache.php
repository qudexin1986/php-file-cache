<?
class FileCache {
	function __construct($dir) {
		$this->last_memoized_function_call_hit_cache = false;
		$this->last_memoized_function_call_time = 0;
		$this->dir = trim($dir, '/');
		$this->debug = false;
		if (!is_dir($dir)) {
			mkdir($dir, 0777, true);
		}
		$testpath = $this->join_paths(array($dir));
		if (!is_dir($dir) or ! is_writeable($testpath)) {
			throw new Exception("Directory $dir isn't writeable.");
		}
	}

	function set_debug($debug) {
		$this->debug = $debug;
	}

	function debug($message) {
		if ($this->debug) {
			echo $message . '<br/>';
		}
	}

	function save($key, $value) {
		$path = $this->filepath_for_key($key);
		if (is_file($path)) {
			return;
		}

		$fh = fopen($path, 'w');
		$serialized_value = serialize($value);
		fwrite($fh, $serialized_value);
		fclose($fh);

		if (!is_file($path)) {
			throw new Exception("Saved file wasn't created.");
		}
	}

	function load($key) {
		$path = $this->filepath_for_key($key);
		$this->debug("loading ".$path);
		if (!is_file($path)) {
			$this->debug("not found");
			return null;
		}

		$fh = fopen($path, 'r');
		$serialized_value = fread($fh, filesize($path));
		fclose($fh);
		return unserialize($serialized_value);
	}

	function filepath_for_key($key) {
		$key = md5(serialize($key));
		$path = $this->join_paths(array($this->dir, md5($key)));
		$this->debug("filepath for ".print_r($key, true)." is ".$path);
		return $path;
	}

	function purge() {
		$dh = opendir($this->dir);
		while (false !== ($filename = readdir($dh))) {
			$path = $this->join_paths(array($this->dir, $filename));
			if (is_file($path)) {
				unlink($path);
			}
		}
	}

	function join_paths() {
		$args = func_get_args();
		$paths = array();
		foreach ($args as $arg) {
			$paths = array_merge($paths, (array)$arg);
		}

		$paths = array_map(create_function('$p', 'return trim($p, "/");'), $paths);
		$paths = array_filter($paths);
		return join('/', $paths);
	}

	function count() {
		return count($this->file_list());
	}

	function file_list() {
		return glob($this->join_paths(array($this->dir, '*')));
	}

	function filesize() {
		$file_list = $this->file_list();
		$total_size = 0;
		foreach ($file_list as $filepath) {
			$total_size += filesize($filepath);
		}
		return $total_size;
	}

	function memoized_function_call($function, array $args) {
		$this->debug("memoized function call: <i>$function</i> with arguments:, <pre>".print_r($args, true)."</pre>");
		$t1 = microtime(true);
		# if one of the args is an unserializable object, well, just strip it out
		$serialized_args = array();
		foreach ($args as $key=>$arg) {
			try {
				$serialized_args[$key] = serialize($arg);
			} catch (Exception $e) {
			}
		}
		$key = array($function, $serialized_args);
		if (null===($result = $this->load($key))) {
			$result = call_user_func_array($function, $args);
			$this->save($key, $result);
			$this->last_memoized_function_call_hit_cache = false;
			$this->debug('cache miss');
		} else {
			$this->last_memoized_function_call_hit_cache = true;
			$this->debug('cache hit');
		}
		$t2 = microtime(true);
		$this->last_memoized_function_call_time = $t2-$t1;
		return $result;
	}
}