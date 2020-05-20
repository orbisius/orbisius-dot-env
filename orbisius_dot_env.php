<?php

/**
 * Class Orbisius_Dot_Env - Reads, parses .env files.
 * It can update the $_ENV, $_SERVER and env.
 * It can also define the found values as php constants if they are not defined just yet
 * @author Svetoslav Marinov - https://orbisius.com
 * @license MIT
 */
class Orbisius_Dot_Env {
	/**
	 * Singleton
	 * @staticvar static $instance
	 * @return static
	 */
	public static function getInstance() {
		static $instance = null;

		if (is_null($instance)) {
			$instance = new static();
		}

		return $instance;
	}

	/**
	 * Updates env, $_ENV, $_SERVER with the passed data. If value is non a scalar it will be json encoded.
	 * @param array $data
	 * @param bool $override
	 */
	public function updateEnv($data = [], $override = false) {
		if (empty($data) || !is_array($data)) {
			return false;
		}

		foreach ($data as $k => $v) {
			$v = is_scalar($v) ? $v : json_encode($v);

			if ($override) {
				putenv("$k=" . $v);
				$_ENV[$k] = $v;
				$_SERVER[$k] = $v;
			} else {
				if (getenv($k) == '') {
					putenv("$k=" . $v);
				}

				if (!isset($_ENV[$k])) {
					$_ENV[$k] = $v;
				}

				if (!isset($_SERVER[$k])) {
					$_SERVER[$k] = $v;
				}
			}
		}

		return true;
	}

	/**
	 * Defines php consts based on the names. If prefix is passed it will be PREPENDED to each const
	 * @param array $data
	 * @param string $prefix
	 */
	public function defineConsts($data = [], $prefix = '') {
		if (empty($data) || !is_array($data)) {
			return false;
		}

		$prefix = empty($prefix) ? '' : trim($prefix);

		if (!empty($prefix) && substr($prefix, -1, 1) != '_') { // Let's append underscore automatically if not passed.
			$prefix .= '_';
		}

		foreach ($data as $k => $v) {
			if (defined($prefix . $k)) {
				continue;
			}

			$v = is_scalar($v) ? $v : json_encode($v);
			define($prefix . $k, $v);
		}

		return true;
	}

	/**
	 * Reads the .env file if it finds it. Skips comments and empty lines.
	 * The keys are UPPERCASED.
	 *
	 * @param string $file
	 * @return array
	 */
	public function read($file = '') {
		$data = [];

		if (empty($file)) {
			if ( ! empty( $_SERVER['DOCUMENT_ROOT'] ) ) {
				$file = dirname( $_SERVER['DOCUMENT_ROOT'] ) . '/.env';
			} elseif ( defined('ABSPATH') ) { // WordPress set up.
				$file = dirname( ABSPATH ) . '/.env';
			} else {
				$file = __DIR__ . '/.env';
			}
		}

		if (! @file_exists($file)) { // could produce warnings if outside of open base dir
			return $data;
		}

		$buff = file_get_contents($file, LOCK_SH);
		$lines = explode("\n", $buff);
		$lines = array_filter($lines); // rm empty lines

		foreach ($lines as $line) {
			$line = trim($line);

			if (empty($line) || substr($line, 0, 1) == '#') {
				continue;
			}

			$eq_pos = strpos($line, '=');

			if ($eq_pos === false) {
				continue;
			}

			$key = substr($line, 0, $eq_pos);
			$key = trim($key, '\'" ');
			$key = strtoupper($key);

			$val = substr($line, $eq_pos + 1);
			$val = str_replace('=', '', $val); // jic

			$pos = strpos($val, '#'); // does the value have a comment ?

			// rm comment from value field if not prefixed by a slash \
			if (($pos !== false) && substr($val, $pos - 1, 1) != "\\" ) {
				$val = substr($val, 0, $pos);
			}

			$val = trim($val, '\'" ');

			$data[$key] = $val;
		}

		return $data;
	}
}

