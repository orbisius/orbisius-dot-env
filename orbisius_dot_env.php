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
	 * @var array
	 */
	private $params = [];
	private $processed = [];

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

	public function init($params = []) {
		$this->params = $params;
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

		$prefix = '';

		if (!empty($this->params['prefix'])) {
			$prefix = $this->formatPrefix($this->params['prefix']);
		}

		foreach ($data as $k => $v) {
			if (!$this->hasPrefix($prefix, $k)) {
				$k = $prefix . $k;
			}

			$v = is_scalar($v) ? $v : json_encode($v);

			$this->processed[$k] = $v;

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
	 * @param string $inp_key
	 * @return string
	 */
	public function get($inp_key, $prefix = '') {
		if (empty($prefix) && !empty($this->params['prefix'])) {
			$prefix = $this->params['prefix'];
		}

		$key = $this->formatPrefix($inp_key);
		$var_name = $this->formatPrefix($prefix . $key);
		$key_no_pref = str_replace($prefix, '', $key);

		foreach ([ $key, $var_name, $key_no_pref, ] as $v) {
			$v = trim($v, '_-'); // sometimes keys have leading/trailing chars

			if ( defined( $v ) ) {
				return constant( $v );
			}

			if ( ! empty( $_ENV[ $v ] ) ) {
				return $_ENV[ $v ];
			}

			if ( ! empty( $_SERVER[ $v ] ) ) {
				return $_SERVER[ $v ];
			}

			$val = getenv( $v );

			if ( ! empty( $val ) ) {
				return $val;
			}
		}

		return '';
	}

	/**
	 * @param string $prefix
	 * @return bool
	 */
	public function hasPrefix($prefix, $str) {
		return !empty( $prefix) && strcasecmp($prefix, substr( $str, 0, strlen($prefix) ) ) == 0;
	}

	/**
	 * Defines php consts based on the names. If prefix is passed it will be PREPENDED to each const
	 * @param string $prefix
	 */
	public function formatPrefix($prefix = '') {
		$prefix = empty($prefix) ? '' : trim($prefix);

		if (empty($prefix)) {
			return '';
		}

		if (!empty($prefix) && substr($prefix, -1, 1) != '_') { // Let's append underscore automatically if not passed.
			$prefix .= '_';
		}

		$prefix = preg_replace('#[^\w]#si', '_', $prefix);
		$prefix = preg_replace('#\_+#si', '_', $prefix);
		$prefix = strtoupper($prefix);

		return $prefix;
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

		if (empty($prefix) && !empty($this->params['prefix'])) {
			$prefix = $this->params['prefix'];
		}

		$prefix = $this->formatPrefix($prefix);

		foreach ($data as $k => $v) {
			if (!$this->hasPrefix($prefix, $k)) {
				$k = $prefix . $k;
			}

			if (defined($k)) {
				continue;
			}

			$v = is_scalar($v) ? $v : json_encode($v);
			define($k, $v);
            $this->processed[$k] = $v;
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
		    $found = 0;
		    $files = [];

		    // We're checking 1 devel above doc root
            if ( ! empty( $_SERVER['DOCUMENT_ROOT'] ) ) {
                $files[] = dirname( $_SERVER['DOCUMENT_ROOT'] ) . '/.env';
            }

            if ( defined('ABSPATH') ) { // WordPress set up.
                $files[] = dirname(ABSPATH) . '/.env';
                $files[] = ABSPATH . '/.env';
            }

            if ( ! empty( $_SERVER['DOCUMENT_ROOT'] ) ) {
                $files[] = $_SERVER['DOCUMENT_ROOT'] . '/.env';
            }

            $files[] = __DIR__ . '/.env';

			foreach ($files as $checked_file) {
			    if (file_exists($checked_file)) {
			        $file = $checked_file;
			        $found = 1;
			        break;
                }
            }
        }

		if ( empty($file) || empty($found) || ! @file_exists($file) ) { // could produce warnings if outside of open base dir
			return $data;
		}

		$buff = file_get_contents($file, LOCK_SH);
		$lines = explode("\n", $buff);
		$lines = array_map('trim', $lines);
		$lines = array_unique($lines); // no dups
		$lines = array_filter($lines); // rm empty lines

		foreach ($lines as $line) {
			$first_char = substr($line, 0, 1);

			// empty or single line comments
			if (empty($line) || $first_char == '#' || $first_char == ';' || ($first_char == '/' && substr($line, 1, 1) == '/')) {
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

	/**
	 * Loads, updates env and defines php consts
	 * @param array $inp_data
	 * @return bool
	 */
	public function run($inp_data = []) {
		$data = $this->read();
		$data = array_replace_recursive($inp_data, $data);

		if (empty($data)) {
			return false;
		}

		$this->updateEnv( $data );
		$this->defineConsts( $data );

		return true;
	}
}
