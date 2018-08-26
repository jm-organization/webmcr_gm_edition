<?php

namespace mcr\installer\modules;


use mcr\core\configs\config;
use mcr\installer\install;
use mcr\validation\validator;

if (!defined("MCR")) exit("Hacking Attempt!");

class step_1 extends install_step
{
	use validator;

	const tables = __DIR__ . '/../database/tables/';
	const seeds = __DIR__ . '/../database/seeds/';

	/**
	 * @var \mysqli
	 */
	public static $connection;

	/**
	 * Connect and save DB connection data.
	 * Fill DB and tables in DB.
	 *
	 * @throws \mcr\validation\validation_exception
	 */
	public function save()
	{
		$_data = array_trim_value($_POST);
		$this->validate($_data, [
			'host'      => 'required',
			'port'      => 'required',
			'basename'  => 'required',
			'username'  => 'required',
			'passwd'    => 'required'
		]);

		// Получаем соединение с базой и устанвливаем sql_mode
		self::connect_to_db($_data)->query("SET GLOBAL sql_mode='NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
		// Сохраняем конфиги
		config::save($_data, 'db');

		// Заполняем базу таблицами, а затем таблици содержимым.
		self::fill_db();
		self::fill_tables();

		// Закрываем соединение.
		self::$connection->close();

		install::remember_step('step_1');
		install::to_next_step();
	}

	/**
	 * GET db connect data form
	 *
	 * @return string
	 */
	public function create()
	{
		install::$page_title = translate('mod_name') . ' — ' . translate('step_1');

		return tmpl('steps.step_1');
	}

	/**
	 * Возвращает соединение к базе данных
	 *
	 * @param $props
	 *
	 * @return \mysqli
	 */
	private static function connect_to_db($props)
	{
		if (empty(self::$connection)) {
			self::$connection = @new \mysqli($props['host'], $props['username'], $props['passwd'], $props['basename'], $props['port']);

			if (!empty(self::$connection->connect_error)) {
				$message = [ 'title' => translate('e_msg'), 'text' => translate('e_connection') ];

				return redirect()->with('message', $message)->url('/install/index.php?step_1/');
			}
		}

		return self::$connection;
	}

	private static function fill_db()
	{
		$tables = install::$tables;

		foreach ($tables as $index => $table) {
			$table = file_get_contents(self::tables . $table . '.sql');

			self::make_table($table);
		}
	}

	private static function fill_tables()
	{
		$seeds = scandir(self::seeds);

		foreach ($seeds as $seed) {
			if ($seed == '.' || $seed == '..') continue;

			$query = file_get_contents(self::seeds . $seed);

			self::$connection->query($query);
		}
	}

	private static function make_table($sql)
	{
		if (self::$connection->multi_query($sql)) {
			do {
				if ($result = self::$connection->store_result()) mysqli_free_result($result);
			} while (self::$connection->more_results() && self::$connection->next_result());
		}
	}
}