<?php

namespace mcr\installer\modules;


if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class step_1 extends install_step
{
	public function content()
	{
		global $configs;

		$this->title = $this->lng['mod_name'] . ' — ' . $this->lng['step_1'];

		if (!isset($_SESSION['start'])) {
			$this->notify('', '', 'install/');
		}
		if (isset($_SESSION['step_1'])) {
			$this->notify('', '', 'install/?do=step_2');
		}

		$_SESSION['f_host'] = (isset($_POST['host'])) ? $this->HSC($_POST['host']) : config('db::host');
		$_SESSION['f_port'] = (isset($_POST['port'])) ? intval($_POST['port']) : config('db::port');
		$_SESSION['f_base'] = (isset($_POST['base'])) ? $this->HSC($_POST['base']) : config('db::base');
		$_SESSION['f_user'] = (isset($_POST['user'])) ? $this->HSC($_POST['user']) : config('db::user');
		$_SESSION['f_pass'] = (isset($_POST['pass'])) ? $this->HSC($_POST['pass']) : config('db::pass');

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$db = @new \mysqli($_SESSION['f_host'], $_SESSION['f_user'], $_SESSION['f_pass'], $_SESSION['f_base'], $_SESSION['f_port']);
			$error = $db->connect_error;

			if (!empty($error)) {
				$this->notify($this->lng['e_connection'] . ' | ' . $error, $this->lng['e_msg'], 'install/?do=step_1');
			}

			$_db =  config('db');
			$_db['host'] = $_SESSION['f_host'];
			$_db['port'] = $_SESSION['f_port'];
			$_db['base'] = $_SESSION['f_base'];
			$_db['user'] = $_SESSION['f_user'];
			$_db['pass'] = $_SESSION['f_pass'];

			if (!$configs->savecfg($_db, 'db.php', 'db')) {
				$this->notify($this->lng['e_msg'], $this->lng['e_write'], 'install/?do=step_1');
			}

			$tables = file(DIR_INSTALL.'tables.sql');

			@$db->query("SET GLOBAL sql_mode='NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");

			$string = "";

			foreach ($tables as $key => $value) {

				$value = trim($value);

				if ($value == '#line') {
					$string = trim($string);

					@$db->query($string);

					$string = "";
					continue;
				}

				$value = str_replace('~base_url~', URL_ROOT, $value);

				$string .= $value;
			}

			$query = $db->query("UPDATE `mcr_groups` SET `id`='0' WHERE `id`='4'");
			if (!$query) {
				$this->notify($this->lng['e_upd_group'], $this->lng['e_msg'], 'install/?do=step_1');
			}

			$query = $db->query("ALTER TABLE `mcr_groups` AUTO_INCREMENT=0");
			if (!$query) {
				$this->notify($this->lng['e_upd_group'], $this->lng['e_msg'], 'install/?do=step_1');
			}

			$_SESSION['step_1'] = true;

			$this->notify($this->lng['step_2'], $this->lng['db_settings'], 'install/?do=step_2');
		}

		$data = [];

		return $this->sp('step_1.phtml', $data);
	}
}