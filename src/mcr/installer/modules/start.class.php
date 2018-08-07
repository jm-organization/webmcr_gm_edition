<?php

namespace mcr\installer\modules;


use mcr\installer\install;

if (!defined("MCR")) exit("Hacking Attempt!");

class start extends install_step
{
	private static $errors = [
		'e_php_version',
		'e_register_globals',
		'e_l10n',
		'e_fopen',
		'e_gd',
		'e_mysql_not_found',
		'e_buffer',
		'e_perm_data'
	];

	public function validate_requirements()
	{
		$error_code = -1;
		$requirements = [
			"PHP"           => phpversion() >= 5.5 ? true : 0,
			"REG_GLOB"      => @ini_get('register_globals') != 'on' ? true : 0,
			"LOCALIZATION"  => class_exists('Locale') ? true : 2,
			"URL_FOPEN"     => @ini_get('allow_url_fopen') == '1' || @ini_get('allow_url_fopen') == 'true' ? true : 3,
			"GD"            => function_exists('ImageCreateFromJpeg') ? true : 4,
			"MYSQLi"        => function_exists("mysqli_query") ? true : 5,
			"BUFER"         => function_exists("ob_start") ? true : 6,
			"FOLDER_DATA"   => $this->recursive_check_permissions(MCR_ROOT . 'data') ? true : 7,
		];

		foreach ($requirements as $requirement_id => $expression) {
			if (is_integer($expression)) {
				$error_code = $expression;
			}
		}

		if ($error_code != -1) {
			return redirect()->with('message', [ 'title' => translate('e_msg'), 'text' => translate(self::$errors[$error_code]) ])->url('/install/index.php?start/');
		} else {

			install::remember_step('start');
			install::to_next_step();

		}
	}

	public function get_requirements()
	{
		install::$page_title = translate('mod_name') . ' — ' . translate('step_1');

		$requirements = [
			"PHP"           => phpversion() >= 5.5,
			"REG_GLOB"      => @ini_get('register_globals') != 'on',
			"LOCALIZATION"  => class_exists('Locale'),
			"URL_FOPEN"     => @ini_get('allow_url_fopen') == '1' || @ini_get('allow_url_fopen') == 'true',
			"GD"            => function_exists('ImageCreateFromJpeg'),
			"MYSQLi"        => function_exists("mysqli_query"),
			"BUFER"         => function_exists("ob_start"),
			"FOLDER_DATA"   => $this->recursive_check_permissions(MCR_ROOT . 'data'),
		];

		return tmpl('steps/start', compact('requirements'));
	}

	private function recursive_check_permissions($folder)
	{
		if (!is_writable($folder) || !is_readable($folder)) return false;

		$scan = scandir($folder);
		$result = true;

		foreach ($scan as $key => $value) {
			if ($value == '.' || $value == '..') continue;

			$path = $folder.'/'.$value;

			if (!is_writable($path) || !is_readable($path)) {
				$result = false;
			}
		}

		return $result;
	}
}