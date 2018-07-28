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
 * @e-mail       : 	admin@jm-org.net
 * @Author       : 	Magicmen
 *
 * @Date         : 	29.06.2018
 * @Time         : 	0:36
 *
 * @Documentation: 	Класс-расширение класса \mysqli_result
 * Используетс, как стандартные методы mysqli_result,
 * так и свои собственные.
 *
 * Необходим для испоьзования
 * при вызове sql таких запросов:
 * - SELECT,
 * - SHOW,
 * - DESCRIBE,
 * - EXPLAIN,
 *
 * Обработка DML запросов данным классом не предусмотренна
 *
 */

namespace mcr\database;


use mcr\log;

/**
 * Class db_result
 *
 * @package mcr\database
 *
 * @property 	int 	$current_field;
 * @property 	int 	$field_count;
 * @property 	array 	$lengths;
 * @property 	int 	$num_rows;
 *
 * @method 		bool 	data_seek(int $offset)
 * @method 		mixed 	fetch_all(int $resulttype = MYSQLI_NUM)
 * @method 		mixed 	fetch_array(int $resulttype = MYSQLI_BOTH)
 * @method 		array 	fetch_assoc()
 * @method 		object 	fetch_field_direct(int $fieldnr)
 * @method 		object 	fetch_field()
 * @method 		array 	fetch_fields()
 * @method 		object 	fetch_object(string $class_name = "stdClass", array $params)
 * @method 		mixed 	fetch_row()
 * @method 		bool 	field_seek(int $fieldnr)
 * @method 		void 	free()
 *
 * @see 		\mysqli_result
 */
class db_result
{
	/**
	 * @var \mysqli|null
	 */
	private $connection = null;

	/**
	 * @var \mysqli_result|null
	 */
	private $result = null;

	/**
	 * db_result constructor.
	 *
	 * @param $connection
	 * @param $result
	 */
	public function __construct($connection, $result)
	{
		$this->connection = $connection;

		$this->result = $result;
	}

	/**
	 * @function     : __call
	 *
	 * @documentation: Обработчик события вызова
	 * неизвестного метода $name. Если метод найден в классах:
	 *               - \mysqli_result
	 * то будет вызван метод $name и возвращён результат вызова.
	 *
	 * @param $name
	 * @param $arguments
	 *
	 * @return mixed
	 * @throws db_exception
	 */
	public function __call($name, $arguments)
	{
		/** @var \mysqli_result $result */
		$result = $this->result;

		if (is_bool($result)) {
			throw new db_exception("db::$name(): result is boolean. See logs.", log::WARNING);
		}

		// Проверяем наличие вызываемого метода в классе mysqli_result
		if (method_exists($result, $name)) {

			return $result->$name(...$arguments);

		} else {
			// Если не нашли, то выбрасываем исключение
			throw new db_exception("Unexpected method `$name`", log::WARNING);
		}
	}

	/**
	 * @function     : __get
	 *
	 * @documentation: Возвращает значение
	 * параметров класса \mysqli_result.
	 *
	 * @param $key
	 *
	 * @return null
	 */
	public function __get($key)
	{
		if (is_null($key)) {
			return null;
		}

		if (property_exists($this->result, $key)) {
			return $this->result->$key;
		}

		return null;
	}

	public function result()
	{
		return $this->result;
	}
}
