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


use InvalidArgumentException;

class db_query_builder
{

	const simple_query = /** @lang text */ "select %s from `%s`";

	/**
	 * @var db_query_builder|null
	 */
	public static $instance = null;

	/**
	 * @var string
	 */
	private $query = '';

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
	 * All of the available clause operators.
	 *
	 * @var array
	 */
	protected $operators = [
		'=', '<', '>', '<=', '>=', '<>', '!=',
		'like', 'like binary', 'not like', 'between', 'ilike',
		'&', '|', '^', '<<', '>>',
		'rlike', 'regexp', 'not regexp',
		'~', '~*', '!~', '!~*', 'similar to',
		'not similar to', 'not ilike', '~~*', '!~~*',
	];

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

	/**
	 * Метод для Smart-выборки.
	 *
	 * @param        $column
	 * @param null   $operator
	 * @param null   $value
	 * @param string $boolean
	 *
	 * @return db_query_builder|null
	 */
	public function where($column, $operator = null, $value = null, $boolean = 'and')
	{
		if ($column instanceof \Closure) {
			call_user_func($column, $this);
		} else {
			// Определяем по какой колонке будет производится выборка.
			// Если колонка переданое значение строка - записываем, как имя колонки
			// TODO: предусмотреть поддержку передачи масивов чтобы делать одинаковые условия для разных колонок (?)
			$column = is_string($column) ? $column : null;

			// В зависимости от кол-ва параметров определяем оператор
			// Если два парметра - значит оператор по умолчанию "=", а оператор - значение,
			// по которому будет произодится выборка.
			// Иначе проверяем параметр на валидность. Если валиден, то регистрируем выборку.
			if (func_num_args() == 2) {
				list($value, $operator) = [$operator, '='];
			} elseif ($this->invalid_operator_and_value($operator, $value)) {
				throw new InvalidArgumentException('Illegal operator and value combination.');
			}

			$this->wheres[] = compact('column', 'operator', 'value', 'boolean');
		}

		return self::$instance;
	}

	protected function invalid_operator_and_value($operator, $value)
	{
		$is_operator = in_array($operator, $this->operators);

		return is_null($value) && $is_operator && !in_array($operator, ['=', '<>', '!=']);
	}

	/**
	 * Строит запрос на основании данных,
	 * которые были указаны при linq выборке данных из базы.
	 *
	 *
	 *
	 */
	private function build_query()
	{
		// Определяем из каких колонок необходимо выбрать данные.
		// Указывается методами pluck, select
		$columns = implode("`, `", $this->columns);
		if (count($this->columns) == 0) {
			$columns = '*';
		}

		// Генерируем базовый запрос
		// Подставляем колонки и таблицу,
		// из которой необходимо произвести выборку данных.
		$this->query = sprintf(
			self::simple_query,
			"`$columns`",
			$this->table
		);

		// Если указаны where`s, то добавляем их в запрос.
		if (!empty($this->wheres)) {
			$_where = 'where ';

			foreach ($this->wheres as $index => $where) {
				$value = is_array($where['value']) ? implode("', '", $where['value']) : $where['value'];

				if ($index == 0) {
					$_where .= "`{$where['column']}` {$where['operator']} '$value'";
				} else {
					$_where .= " {$where['boolean']} `{$where['column']}` {$where['operator']} '$value'";
				}
			}

			$this->query = $this->query . " $_where";
		}
	}

	/**
	 * @return array
	 * @throws db_exception
	 */
	public function first()
	{
		$this->build_query();

		return db::query($this->query)->fetch_assoc();
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

	/**
	 * @param      $column
	 * @param null $_
	 *
	 * @return array
	 * @throws db_exception
	 */
	public function pluck($column, $_ = null)
	{
		$assoc = false;
		if ($_ != null) {
			list($value, $key) = func_get_args();

			$this->select($key, $value);
			$assoc = true;
		} else {
			$this->select($column);
		}

		$_result = $this->get();
		$result = [];

		foreach ($_result as $item) {
			if ($assoc) {
				$result[$item[$_]] = $item[$column];
			} else {
				$result[] = $item[$column];
			}
		}

		return $result;
	}
}