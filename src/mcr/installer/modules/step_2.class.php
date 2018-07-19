<?php

namespace mcr\installer\modules;


if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class step_2 extends install_step
{
	public function content()
	{
		global  $configs;
		$this->title = $this->lng['mod_name'] . ' — ' . $this->lng['step_2'];

		if (!isset($_SESSION['step_1'])) {
			$this->notify('', '', 'install/?do=step_1');
		}
		if (isset($_SESSION['step_2'])) {
			$this->notify('', '', 'install/?do=step_3');
		}

		$_SESSION['f_login'] = (isset($_POST['login'])) ? $this->HSC(@$_POST['login']) : 'admin';
		$_SESSION['f_adm_pass'] = @$_POST['password'];
		$_SESSION['f_repass'] = $this->HSC(@$_POST['repassword']);
		$_SESSION['f_email'] = (isset($_POST['email'])) ? $this->HSC(@$_POST['email']) : 'admin@' . $_SERVER['SERVER_NAME'];

		$method = intval(@$_POST['method']);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {

			if (!preg_match("/^[\w\-]{3,}$/i", @$_POST['login'])) {
				$this->notify($this->lng['e_login_format'], $this->lng['e_msg'], 'install/?do=step_2');
			}

			if (mb_strlen(@$_POST['password'], "UTF-8") < 6) {
				$this->notify($this->lng['e_pass_len'], $this->lng['e_msg'], 'install/?do=step_2');
			}

			if (@$_POST['password'] !== @$_POST['repassword']) {
				$this->notify($this->lng['e_pass_match'], $this->lng['e_msg'], 'install/?do=step_2');
			}

			if (!filter_var(@$_POST['email'], FILTER_VALIDATE_EMAIL)) {
				$this->notify($this->lng['e_email_format'], $this->lng['e_msg'], 'install/?do=step_2');
			}

			$_main = config('main');

			if (!$configs->savecfg($_main, 'main.php', 'main')) {
				$this->notify($this->lng['e_settings'], $this->lng['e_msg'], 'install/?do=step_2');
			}

			$db = @new \mysqli(config('db::host'), config('db::user'), config('db::pass'), config('db::base'), config('db::port'));
			$error = $db->connect_error;

			if (!empty($error)) {
				$this->notify($this->lng['e_connection'] . ' | ' . $error, $this->lng['e_msg'], 'install/?do=step_2');
			}

			$login = $db->real_escape_string(@$_POST['login']);
			$email = $db->real_escape_string(@$_POST['email']);

			$salt = $db->real_escape_string($this->random());
			$password = $this->gen_password(@$_POST['password'], $salt);
			$ip = $this->ip();

			$query = $db->query("
				INSERT INTO `mcr_users`
					(`gid`, `login`, `email`, `password`, `uuid`, `salt`, `ip_last`, `time_create`)
				VALUES
					('3', '$login', '$email', '$password', UNHEX(REPLACE(UUID(), '-', '')), '$salt', '$ip', NOW())
			");

			if (!$query) {
				$this->notify($this->lng['e_add_admin'], $this->lng['e_msg'], 'install/?do=step_2');
			}

			$query = $db->query("
				INSERT INTO `mcr_iconomy`
					(`login`, `money`, `realmoney`, `bank`)
				VALUES
					('$login', 0, 0, 0)
			");

			if (!$query) {
				$this->notify($this->lng['e_add_economy'], $this->lng['e_msg'], 'install/?do=step_2');
			}

			$_SESSION['step_2'] = true;

			$this->notify('', '', 'install/?do=step_3');

		}

		return $this->sp('step_2.phtml');
	}

	private function encrypt_methods($selected = 0)
	{
		ob_start();

		foreach ($this->methods as $key => $title) {
			$select = ($key == $selected) ? 'selected' : '';
			echo '<option value="' . $key . '" ' . $select . '>' . $title . '</option>';
		}

		return ob_get_clean();
	}

}