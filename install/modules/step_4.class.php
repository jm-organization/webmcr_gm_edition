<?php
/*
 * Изменения разработчиками JM Organization
 *
 * @contact: admin@jm-org.net
 * @web-site: www.jm-org.net
 *
 * @supplier: Magicmen
 * @script_author: Qexy
 *
 **/

namespace install\modules;


if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class step_4 extends install_step
{
	public function content()
	{
		global $configs;
		$this->title = $this->lng['mod_name'] . ' — ' . $this->lng['step_4'];

		if (!isset($_SESSION['step_3'])) {
			$this->notify('', '', 'install/?do=step_3');
		}
		if (isset($_SESSION['step_4'])) {
			$this->notify('', '', 'install/?do=step_5');
		}

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {

			$_modules_users = config('modules::users');
			$_modules_users['enable_comments'] = (intval(@$_POST['use_comments']) == 1) ? true : false;
			$_modules_users['users_on_page'] = (intval(@$_POST['rop_users']) < 1) ? 1 : intval(@$_POST['rop_users']);
			$_modules_users['comments_on_page'] = (intval(@$_POST['rop_comments']) < 1) ? 1 : intval(@$_POST['rop_comments']);

			if (!$configs->savecfg($_modules_users, 'modules/users.php', 'cfg')) {
				$this->notify($this->lng['e_msg'], $this->lng['e_settings'], 'install/?mode=step_4');
			}

			$_SESSION['step_4'] = true;

			$this->notify($this->lng['mod_name'], $this->lng['step_4'], 'install/?mode=step_5');
		}

		$data = array(
			"COMMENTS" => (config('modules::users.enable_comments')) ? 'selected' : '',
		);

		return $this->sp('step_4.phtml', $data);
	}
}