<?php

namespace mcr\installer\modules;


use mcr\core\configs\config;
use mcr\installer\install;

if (!defined("MCR")) exit("Hacking Attempt!");

class reinstall extends install_step
{
	public function confirm()
	{
		install::$page_title = translate('mod_name') . ' — ' . translate('reinstall');

		return tmpl('steps/reinstall');
	}

	public function reinstall()
	{
		$connection = new \mysqli(config('db::host'), config('db::username'), config('db::passwd'), config('db::basename'), config('db::port'));
		$error = $connection->connect_error;

		if (empty($error)) {
			// Разворачиваем спсиок таблиц чтобы их удалить с внешними
			$tables = array_reverse(install::$tables);
			$tables = '`' . implode('`, `', $tables) . '`';

			// Генерируем ошибку, если удаление не удалось
			if (!$connection->query("DROP TABLE IF EXISTS $tables")) {
				$error_message = '[' . translate('e_sql') . ']: ' . mysqli_error($connection);

				return redirect()->with('message', ['title' => translate('e_msg'), 'text' => $error_message])->url('/install/index.php?reinstall/');
			}

			$connection->close();

			// Удаляем вайл успешной установки.
			if (file_exists(MCR_ROOT . 'src/mcr/.installed')) unlink(MCR_ROOT . 'src/mcr/.installed');

			// Збрасываем конфиги соединения с базой
			config::save([
				'host'      => '127.0.0.1',
				'port'      => '3306',
				'basename'  => '',
				'username'  => 'root',
				'passwd'    => ''
			], 'db');

			// Удаляем сессию
			session_destroy();
		}

		install::forget_steps();

		return redirect('/install/');
	}

}