<?php

namespace install\modules;


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

			$ctables = config('db::tables');

			$ug_f = $ctables['ugroups']['fields'];
			$ic_f = $ctables['iconomy']['fields'];
			$logs_f = $ctables['logs']['fields'];
			$us_f = $ctables['users']['fields'];

			@$db->query("SET GLOBAL sql_mode='NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");

			$string = "";
			$search = array(
				'~ug~',
				'~ug_id~', '~ug_title~', '~ug_text~', '~ug_color~', '~ug_perm~',

				'~ic~',
				'~ic_id~', '~ic_login~', '~ic_money~', '~ic_rc~', '~ic_bank~',

				'~logs~',
				'~logs_id~', '~logs_uid~', '~logs_msg~', '~logs_date~',

				'~us~',
				'~us_id~', '~us_gid~', '~us_login~', '~us_email~', '~us_pass~', '~us_uuid~', '~us_salt~', '~us_tmp~', '~us_is_skin~', '~us_is_cloak~', '~us_ip_create~', '~us_ip_last~', '~us_date_reg~', '~us_date_last~', '~us_gender~', '~us_ban_server~',

				'~base_url~',
			);

			$replace = array(
				$ctables['ugroups']['name'],
				$ug_f['id'], $ug_f['title'], $ug_f['text'], $ug_f['color'], $ug_f['perm'],

				$ctables['iconomy']['name'],
				$ic_f['id'], $ic_f['login'], $ic_f['money'], $ic_f['rm'], $ic_f['bank'],

				$ctables['logs']['name'],
				$logs_f['id'], $logs_f['uid'], $logs_f['msg'], $logs_f['date'],

				$ctables['users']['name'],
				$us_f['id'], $us_f['group'], $us_f['login'], $us_f['email'], $us_f['pass'], $us_f['uuid'], $us_f['salt'], $us_f['tmp'], $us_f['is_skin'], $us_f['is_cloak'], $us_f['ip_create'], $us_f['ip_last'], $us_f['date_reg'], $us_f['date_last'], $us_f['gender'], $us_f['ban_server'],

				URL_ROOT,
			);

			foreach ($tables as $key => $value) {

				$value = trim($value);

				if ($value == '#line') {
					$string = trim($string);

					@$db->query($string);

					$string = "";
					continue;
				}

				$value = str_replace($search, $replace, $value);

				$string .= $value;
			}

			$query = $db->query("UPDATE `{$ctables['ugroups']['name']}` SET `{$ug_f['id']}`='0' WHERE `{$ug_f['id']}`='4'");

			if (!$query) {
				$this->notify($this->lng['e_upd_group'], $this->lng['e_msg'], 'install/?do=step_1');
			}

			$query = $db->query("ALTER TABLE `{$ctables['ugroups']['name']}` AUTO_INCREMENT=0");

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