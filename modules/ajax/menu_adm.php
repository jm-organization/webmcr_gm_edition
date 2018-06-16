<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 16.06.2018
 * @Time         : 19:50
 *
 * @Documentation:
 */

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class submodule
{
	private $core, $user, $l10n, $db;

	public function __construct(core $core)
	{
		$this->core = $core;
		$this->db = $core->db;
		$this->user = $core->user;
		$this->l10n = $core->l10n;

		if (!$this->user->is_auth || !$this->core->is_access('sys_adm_menu_adm')) {
			$this->core->js_notify($this->l10n->gettext('error_403'));
		}
	}

	public function content()
	{
		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			$this->core->js_notify($this->l10n->gettext('error_method'));
		}

		if ($_GET['op'] == 'attach') {
			$fixed = $_POST['fixed'] == 'true' ? 1 : 0;
			$page = intval($_POST['page']);

			if (!$this->db->query("UPDATE `mcr_menu_adm` SET fixed='$fixed' WHERE id='$page'")) {
				$this->core->js_notify($this->l10n->gettext('error_sql_critical'), $this->l10n->gettext('error_message'), 2, null);
			}

			$this->core->js_notify($this->l10n->gettext('attach_success'), $this->l10n->gettext('error_success'), 3, null);
		}
	}
}