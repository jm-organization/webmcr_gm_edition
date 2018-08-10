<?php
/**
 * Copyright (c) 2018.
 * MagicMCR является отдельным и независимым продуктом.
 * Исходный код распространяется под лицензией GNU General Public License v3.0.
 *
 * MagicMCR не является копией оригинального движка WebMCR, а лишь его подверсией.
 * Разработка MagicMCR производится исключительно в частных интересах. Разработчики, а также лица,
 * участвующие в разработке и поддержке, не несут ответственности за проблемы, возникшие с движком.
 */

/**
 * Created in JM Organization.
 *
 * @e-mail: admin@jm-org.net
 * @Author: Magicmen
 *
 * @Date  : 07.08.2018
 * @Time  : 21:52
 */

namespace mcr\database;


class db_query_builder
{
	/**
	 * @var db_query_builder|null
	 */
	public static $instance = null;

	/**
	 * @var string
	 */
	private $query = "SELECT `%s` FROM `%s`";

	/**
	 * @var string
	 */
	private $table = '';

	/**
	 * @var array
	 */
	private $columns = [];

	/**
	 * @var array
	 */
	private $wheres = [];

	/**
	 * @return db_query_builder|null
	 */
	public static function get_instance()
	{
		if (empty(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * db_query_builder constructor.
	 */
	private function __construct()
	{

	}

	private function __clone() { }

	private function __wakeup() { }

	/**
	 * @param        $name
	 * @param string $prefix
	 */
	public function table($name, $prefix = 'mcr_')
	{
		$this->table = $prefix . $name;
	}

	/**
	 * @param $columns
	 *
	 * @return db_query_builder|null
	 */
	public function select($columns)
	{
		$this->columns = (is_array($columns)) ?: func_get_args();

		return self::$instance;
	}

	private function build_query()
	{
		$columns = implode("`, `", $this->columns);

		$this->query = sprintf($this->query,
			$columns,
			$this->table
		);
	}

	/**
	 * @return mixed
	 * @throws db_exception
	 */
	public function get()
	{
		$this->build_query();

		return db::query($this->query)->fetch_all(MYSQLI_ASSOC);
	}
}