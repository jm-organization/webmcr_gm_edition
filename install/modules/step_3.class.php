<?php

namespace install\modules;


if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class step_3 extends install_step
{
	public function content()
	{
		global $configs;
		$this->title = $this->lng['mod_name'] . ' — ' . $this->lng['step_3'];

		if (!isset($_SESSION['step_2'])) {
			$this->notify('', '', 'install/?do=step_2');
		}
		if (isset($_SESSION['step_3'])) {
			$this->notify('', '', 'install/?do=step_4');
		}

		$_SESSION['fs_name'] = config('main::s_name');
		$_SESSION['fs_about'] = config('main::s_about');
		$_SESSION['fs_keywords'] = config('main::s_keywords');
		$_SESSION['fs_from'] = config('mail::from');
		$_SESSION['fs_from_name'] = config('mail::from_name');
		$_SESSION['fs_reply'] = config('mail::reply');
		$_SESSION['fs_reply_name'] = config('mail::reply_name');
		$_SESSION['fs_smtp'] = (config('mail::smtp')) ? 'selected' : '';
		$_SESSION['fs_smtp_host'] = config('mail::smtp_host');
		$_SESSION['fs_smtp_user'] = config('mail::smtp_user');
		$_SESSION['fs_smtp_pass'] = config('mail::smtp_pass');
		$_SESSION['fs_smtp_tls'] = (config('mail::smtp_tls')) ? 'selected' : '';

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {

			$_main = config('main');
			$_main['s_name'] = $this->HSC(@$_POST['s_name']);
			$_main['s_about'] = $this->HSC(@$_POST['s_about']);
			$_main['s_keywords'] = $this->HSC(@$_POST['s_keywords']);
			$_main['s_root'] = URL_ROOT;
			$_main['s_root_full'] = URL_ROOT_FULL;
			$_main['mcr_secury'] = $this->random(20, false);


			$_mail = config('main');
			$_mail['from'] = $this->HSC(@$_POST['from']);
			$_mail['from_name'] = $this->HSC(@$_POST['from_name']);
			$_mail['reply'] = $this->HSC(@$_POST['reply']);
			$_mail['reply_name'] = $this->HSC(@$_POST['reply_name']);
			$_mail['smtp'] = (intval(@$_POST['smtp']) === 1) ? true : false;
			$_mail['smtp_host'] = $this->HSC(@$_POST['smtp_host']);
			$_mail['smtp_user'] = $this->HSC(@$_POST['smtp_user']);
			$_mail['smtp_pass'] = $this->HSC(@$_POST['smtp_pass']);
			$_mail['smtp_tls'] = (intval(@$_POST['smtp_tls']) === 1) ? true : false;

			if (!$configs->savecfg($_main, 'main.php', 'main')) {
				$this->notify($this->lng['e_write'], $this->lng['e_msg'], 'install/?mode=finish');
			}

			if (!$configs->savecfg($_mail, 'mail.php', 'mail')) {
				$this->notify($this->lng['e_write'], $this->lng['e_msg'], 'install/?mode=finish');
			}

			$_SESSION['step_3'] = true;

//			if (!($api = file_get_contents("http://api.webmcr.com/?do=install&domain=" . $_SERVER['SERVER_NAME']))) { /* SUCCESS */
//			}

			$this->notify('', '', 'install/?do=step_4');

		}

		$data = array();

		return $this->sp('step_3.phtml', $data);
	}

}