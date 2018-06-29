<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 29.06.2018
 * @Time         : 0:36
 *
 * @Documentation:
 */

namespace mcr\database;


use mcr\core_v2;
use mcr\log;
use mysqli_result;

/**
 * Class db_result
 *
 * @package mcr\database
 *
 *
 * @method 	bool 	data_seek(int $offset)
 * @method 	mixed 	fetch_all(int $resulttype = MYSQLI_NUM)
 * @method 	mixed 	fetch_array(int $resulttype = MYSQLI_BOTH)
 * @method 	array 	fetch_assoc()
 * @method 	object 	fetch_field_direct(int $fieldnr)
 * @method 	object 	fetch_field()
 * @method 	array 	fetch_fields()
 * @method 	object 	fetch_object(string $class_name = "stdClass", array $params)
 * @method 	mixed 	fetch_row()
 * @method 	bool 	field_seek(int $fieldnr)
 * @method 	void 	free()
 *
 * @see 	mysqli_result
 */
class db_result extends core_v2
{
	/**
	 * @var null
	 */
	private $connection = null;

	/**
	 * @var null
	 */
	public $result = null;

	/**
	 * db_result constructor.
	 *
	 * @param $connection
	 * @param $result
	 */
	public function __construct($connection, $result)
	{
		global $configs;

		$this->connection = $connection;

		$this->result = $result;

		parent::__construct($configs);
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
		/** @var mysqli_result $result */
		$result = $this->result;

		// Проверяем наличие вызываемого метода в классе mysqli_result
		if (method_exists($result, $name)) {

			return $result->$name(...$arguments);

		} else {
			// Если не нашли, то выбрасываем исключение
			throw new db_exception("Unexpected method `$name`", log::WARNING);
		}
	}
}