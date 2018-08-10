<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 28.06.2018
 * @Time         : 22:44
 *
 * @Documentation:
 */

namespace mcr\database;


use mcr\core\core_v2;
use mcr\log;

/**
 * Class db
 *
 * @package mcr\database
 *
 * @method  static db_query_builder 	table($name)
 *
 * @see     db_query_builder
 */
class db extends core_v2
{
	/**
	 * @var int
	 */
	public static $count_queries = 0;

	/**
	 * Счётчик реальных запросов к базе данных.
	 * По умолчанию выставленно значение 2 ибо соединение с базой -
	 * первый запрос,
	 * затем следующим запросом установка кодировки.
	 *
	 * @var int
	 */
	public static $count_queries_real = 2;

	/**
	 * @function     : query
	 *
	 * @documentation: Функция запроса к базе данных.
	 * Возвращает экземпляр класса mysql.
	 *
	 * @param $query
	 *
	 * @return db_result
	 * @throws db_exception
	 */
	public static function query($query)
	{
		$query = self::query_validate($query);
		$connection = self::$db_connection->connection;

		if (!empty($connection)) {
			self::$count_queries += 1;
			self::$count_queries_real += 1;

			$result = $connection->query($query);

			if (!$result) {
				global $log;

				$log->write(mysqli_error($connection)." in query: \"".$query."\".", log::MYSQL_ERROR);
			}

			return new db_result($connection, $result);
		}

		throw new db_exception(self::$db_connection->connect_error, log::MYSQL_ERROR);
	}

	/**
	 * @return \stdClass
	 */
	public static function get_queries_count()
	{
		$queries_count = new \stdClass();

		$queries_count->real_queries = self::$count_queries_real;
		$queries_count->queries = self::$count_queries;

		return $queries_count;
	}

	/**
	 * @return int
	 */
	public static function affected_rows()
	{
		return self::$db_connection->connection->affected_rows;
	}

	/**
	 * @return mixed
	 */
	public static function inserted()
	{
		return self::$db_connection->connection->insert_id;
	}

	/**
	 * @param $string
	 *
	 * @return string
	 */
	public static function escape_string($string)
	{
		return self::$db_connection->connection->real_escape_string($string);
	}

	/**
	 * @param $method
	 * @param $arguments
	 *
	 * @return db_query_builder|null
	 * @throws db_exception
	 */
	public static function __callStatic($method, $arguments)
	{
		$query_builder = db_query_builder::get_instance();

		if (method_exists($query_builder, $method)) {
			$query_builder->$method(...$arguments);

			return $query_builder;
		}

		throw new db_exception("db_query_builder: Undefined method '$method'.");
	}

	/**
	 * @param $query
	 *
	 * @return string
	 * @throws db_exception
	 */
	private static function query_validate($query)
	{
		$query = trim($query);

		if (empty($query)) throw new db_exception('db::query(): Empty query', log::MYSQL_WARNING);

		return $query;
	}
}