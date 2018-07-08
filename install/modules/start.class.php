<?php

namespace install\modules;


if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class start extends install_step
{
	public function content()
	{
		$this->title = $this->lng['mod_name'].' — '.$this->lng['step_1'];

		if (isset($_SESSION['start'])) {
			$this->notify('', '', 'install/?do=step_1');
		}

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			if (phpversion() < 5.5) {
				$this->notify($this->lng['e_msg'], $this->lng['e_php_version'], 'install/');
			}

			if (@ini_get('register_globals') == 'off') {
				$this->notify($this->lng['e_msg'], $this->lng['e_register_globals'], 'install/');
			}

			if (@ini_get('allow_url_fopen') == '0' || @ini_get('allow_url_fopen') == 'false') {
				$this->notify($this->lng['e_msg'], $this->lng['e_fopen'], 'install/');
			}

			if (!class_exists('Locale')) {
				$this->notify($this->lng['e_msg'], 'l10n', 'install/');
			}

			if (!function_exists('ImageCreateFromJpeg')) {
				$this->notify($this->lng['e_msg'], $this->lng['e_gd'], 'install/');
			}

			if (!function_exists('mysql_query') && !function_exists('mysqli_query')) {
				$this->notify($this->lng['e_msg'], $this->lng['e_mysql_not_found'], 'install/');
			}

			if (!function_exists('ob_start')) {
				$this->notify($this->lng['e_msg'], $this->lng['e_buffer'], 'install/');
			}

			if (!$this->check_write_all(DIR_ROOT.'configs')) {
				$this->notify($this->lng['e_msg'], $this->lng['e_perm_configs'], 'install/');
			}

			if (!$this->check_write_all(DIR_ROOT.'configs/modules')) {
				$this->notify($this->lng['e_msg'], $this->lng['e_perm_modules'], 'install/');
			}

			if (!is_writable(DIR_ROOT.'configs/modules/users.php') || !is_readable(DIR_ROOT.'configs/modules/users.php')) {
				$this->notify($this->lng['e_msg'], $this->lng['e_perm_modules'], 'install/');
			}

			if (!$this->check_write_all(DIR_ROOT.'data')) {
				$this->notify($this->lng['e_msg'], $this->lng['e_perm_cache'], 'install/');
			}

			if (!is_writable(DIR_ROOT.'data/uploads') || !is_readable(DIR_ROOT.'data/uploads')) {
				$this->notify($this->lng['e_msg'], $this->lng['e_perm_uploads'], 'install/');
			}

			if (!is_writable(DIR_ROOT.config('main::cloak_path')) || !is_readable(DIR_ROOT.config('main::cloak_path'))) {
				$this->notify($this->lng['e_msg'], $this->lng['e_perm_cloaks'], 'install/');
			}

			if (!is_writable(DIR_ROOT.'data/uploads/panel-icons') || !is_readable(DIR_ROOT.'data/uploads/panel-icons')) {
				$this->notify($this->lng['e_msg'], $this->lng['e_perm_icons'], 'install/');
			}

			if (!is_writable(DIR_ROOT.config('main::skin_path')) || !is_readable(DIR_ROOT.config('main::skin_path'))) {
				$this->notify($this->lng['e_msg'], $this->lng['e_perm_skins'], 'install/');
			}

			if (!is_writable(DIR_ROOT.config('main::skin_path').'interface') || !is_readable(DIR_ROOT.config('main::skin_path').'interface')) {
				$this->notify($this->lng['e_msg'], $this->lng['e_perm_intf'], 'install/');
			}

			if (!is_writable(DIR_ROOT.'data/uploads/smiles') || !is_readable(DIR_ROOT.'data/uploads/smiles')) {
				$this->notify($this->lng['e_msg'], $this->lng['e_perm_smiles'], 'install/');
			}

			$_SESSION['start'] = true;

			$this->notify('', '', 'install/?do=step_1');
		}

		$data = [
			"PHP" => (phpversion() < 5.5) ? '<b class="red">'.phpversion().'</b>' : '<b class="green">'.phpversion().'</b>',

			"REG_GLOB" => (@ini_get('register_globals') == 'on') ? '<b class="red">'.$this->lng['on'].'</b>' : '<b class="green">Выкл.</b>',

			"LOCALIZATION" => (class_exists('Locale')) ? '<b class="green">'.$this->lng['on'].'</b>' : '<b class="red">'.$this->lng['off'].'</b>',

			"URL_FOPEN" => (@ini_get('allow_url_fopen') == '1' || @ini_get('allow_url_fopen') == 'true') ? '<b class="green">'.$this->lng['on'].'</b>' : '<b class="red">'.$this->lng['off'].'</b>',

			"GD" => (function_exists('ImageCreateFromJpeg')) ? '<b class="green">'.$this->lng['yes'].'</b>' : '<b class="red">'.$this->lng['no'].'</b>',

			"MYSQL" => (function_exists("mysql_query") || function_exists("mysqli_query")) ? '<b class="green">'.$this->lng['yes'].'</b>' : '<b class="red">'.$this->lng['no'].'</b>',

			"BUFER" => (function_exists("ob_start")) ? '<b class="green">'.$this->lng['yes'].'</b>' : '<b class="red">'.$this->lng['no'].'</b>',

			"FOLDER_CONFIGS" => ($this->check_write_all(DIR_ROOT.'configs')) ? '<b class="green">'.$this->lng['yes'].'</b>' : '<b class="red">'.$this->lng['no'].'</b>',

			"FOLDER_MODULES" => ($this->check_write_all(DIR_ROOT.'configs/modules')) ? '<b class="green">'.$this->lng['yes'].'</b>' : '<b class="red">'.$this->lng['no'].'</b>',

			"USERS" => (is_writable(DIR_ROOT.'configs/modules/users.php') && is_readable(DIR_ROOT.'configs/modules/users.php')) ? '<b class="green">'.$this->lng['yes'].'</b>' : '<b class="red">'.$this->lng['no'].'</b>',

			"FOLDER_DATA" => ($this->check_write_all(DIR_ROOT.'data')) ? '<b class="green">'.$this->lng['yes'].'</b>' : '<b class="red">'.$this->lng['no'].'</b>',

			"FOLDER_UPLOADS" => (is_writable(DIR_ROOT.'data/uploads') && is_readable(DIR_ROOT.'data/uploads')) ? '<b class="green">'.$this->lng['yes'].'</b>' : '<b class="red">'.$this->lng['no'].'</b>',

			"FOLDER_CLOAKS" => (is_writable(DIR_ROOT.config('main::cloak_path')) && is_readable(DIR_ROOT.config('main::cloak_path'))) ? '<b class="green">'.$this->lng['yes'].'</b>' : '<b class="red">'.$this->lng['no'].'</b>',

			"FOLDER_SKINS" => (is_writable(DIR_ROOT.config('main::skin_path')) && is_readable(DIR_ROOT.config('main::skin_path'))) ? '<b class="green">'.$this->lng['yes'].'</b>' : '<b class="red">'.$this->lng['no'].'</b>',

			"FOLDER_ICONS" => (is_writable(DIR_ROOT.'data/uploads/panel-icons') && is_readable(DIR_ROOT.'data/uploads/panel-icons')) ? '<b class="green">'.$this->lng['yes'].'</b>' : '<b class="red">'.$this->lng['no'].'</b>',

			"FOLDER_INTERF" => (is_writable(DIR_ROOT.config('main::skin_path').'interface') && is_readable(DIR_ROOT.config('main::skin_path'))) ? '<b class="green">'.$this->lng['yes'].'</b>' : '<b class="red">'.$this->lng['no'].'</b>',

			"FOLDER_SMILES" => (is_writable(DIR_ROOT.'data/uploads/smiles') && is_readable(DIR_ROOT.'data/uploads/smiles')) ? '<b class="green">'.$this->lng['yes'].'</b>' : '<b class="red">'.$this->lng['no'].'</b>',
		];

		//dd($this);
		return $this->sp('start.phtml', $data);
	}

	private function check_write_all($folder)
	{
		if (!is_writable($folder) || !is_readable($folder)) {
			return false;
		}

		$scan = scandir($folder);

		$result = true;

		foreach ($scan as $key => $value) {
			if ($value == '.' || $value == '..') {
				continue;
			}

			$path = $folder.'/'.$value;

			if (!is_writable($path) || !is_readable($path)) {
				$result = false;
			}
		}

		return $result;
	}
}