<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 25.05.2018
 * @Time         : 22:30
 *
 * @Documentation:
 */

if (!defined("MCR_ROOT")) {
	define("MCR_ROOT", DIR_ROOT);
}

class log
{
	public $debug = false;

	// Debug levels
	const L_ALL = 9;
	const L_WARNING = 10;
	const L_NOTICE = 11;

	public $debug_level = self::L_ALL;

	// MySQL log-codes 1..5
	const MYSQL_ERROR = 111;
	const MYSQL_QUERY = 112;
	const MYSQL_DELETE = 113;
	const MYSQL_INSERT = 114;
	const MYSQL_UPDATE = 115;

	// Other log-codes 6..8
	const FATAL_ERROR = 1;
	const WARNING = 2;
	const NOTICE = 8;

	private $log_path = MCR_ROOT . '/data/logs/';

	private $file_name = 'log_%s.txt';

	public function __construct($debug, $debug_level)
	{
		$this->debug = $debug;
		$this->debug_level = $debug_level;

		$today = date('d_m_Y', time());

		$this->file_name = sprintf($this->file_name, $today);
	}

	public function write($message, $code, $_file = null, $line = null)
	{
		if ($this->debug) {

			$file = $this->log_path . $this->file_name;

			$before = null;
			if (file_exists($file)) {
				$before = file_get_contents($file) . "\n";
			}

			switch ($this->debug_level) {
				case 9:

					$this->make_log($file, $before, $message, $code, $_file, $line);

				break;
				case 10:

					if ($code == 7 || $code == 6) {
						$this->make_log($file, $before, $message, $code, $_file, $line);
					}

				break;
			}

		}
	}

	protected function make_log($file, $before, $message, $code, $_file, $line) {
		$time = date('H:m:s', time());
		$type = '['.self::get_log_type($code).']';

		$log = $before . "$time $type $message";

		if (!empty($_file)) { $log .= " file: $_file"; }

		if (!empty($line)) { $log .= " on line: $line."; }

		file_put_contents($file, $log);
	}

	public function get_log_type($code)
	{
		$types = [
			111 => 'MYSQL_ERROR',
			112 => 'MYSQL_QUERY',
			113 => 'MYSQL_DELETE',
			114 => 'MYSQL_INSERT',
			115 => 'MYSQL_UPDATE',

			1 => 'FATAL_ERROR',
			2 => 'WARNING',
			8 => 'NOTICE',
		];

		return $types[$code];
	}

	public function get_logs_num($log_type)
	{
		$file = $this->log_path . $this->file_name;
		$count = 0;

		if (file_exists($file)) {
			$file_strs = file($file);

			foreach ($file_strs as $str) {
				switch ($log_type) {
					case 'error':
						$pattern = "/^(#log: )?[0-9]{2}\:[0-9]{2}\:[0-9]{2} \[(MYSQL_ERROR|FATAL_ERROR)\] /";
					break;
					case 'warning':
						$pattern = "/^(#log: )?[0-9]{2}\:[0-9]{2}\:[0-9]{2} \[(WARNING)\] /";
					break;
					case 'notice':
						$pattern = "/^(#log: )?[0-9]{2}\:[0-9]{2}\:[0-9]{2} \[(MYSQL_QUERY|MYSQL_DELETE|MYSQL_INSERT|MYSQL_UPDATE|NOTICE)\] /";
					break;
				}

				if (preg_match($pattern, $str)) {
					$count++;
				}
			}
		}

		return $count;

	}

}