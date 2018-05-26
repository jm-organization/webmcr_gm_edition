<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 26.05.2018
 * @Time         : 20:56
 *
 * @Documentation:
 */

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class submodule
{
	private $core, $user, $l10n;

	public function __construct(core$core)
	{
		$this->core = $core;
		$this->user = $core->user;
		$this->l10n = $core->l10n;

		if (!$this->user->is_auth || !$this->core->is_access('sys_adm_info')) {
			$this->core->js_notify($this->l10n->gettext('error_403'));
		}
	}

	public function content()
	{
		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			$this->core->js_notify($this->l10n->gettext('error_method'));
		}

		$file = $_POST['file'];

		$data = null;
		if (file_exists(MCR_ROOT . '/data/logs/' . $file)) {
			$data = file_get_contents(MCR_ROOT . '/data/logs/' . $file);
		}

		$this->core->js_notify(null, $file, true, $data);
	}
}
