<?php

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class module
{
	private $core, $db, $cfg, $l10n, $user;

	public function __construct(core $core)
	{
		$this->core = $core;
		$this->db = $core->db;
		$this->user = $core->user;
		$this->cfg = $core->cfg;
		$this->l10n = $core->l10n;

		$bc = [$this->l10n->gettext('module_register') => BASE_URL . "?mode=register"];
		$this->core->bc = $this->core->gen_bc($bc);
	}

	public function content()
	{
		$this->core->header = $this->core->sp(MCR_THEME_MOD . "register/header.phtml");

		$op = (isset($_GET['op'])) ? $_GET['op'] : false;

		switch ($op) {

			case 'accept':
				$content = $this->accept();
				break;

			default:
				$content = $this->regmain();
				break;
		}

		return $content;
	}

	private function accept()
	{
		if (!isset($_GET['key'])) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=403');
		}

		$key_string = $_GET['key'];
		$array = explode("_", $key_string);

		if (count($array) !== 2) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=403');
		}

		$uid = intval($array[0]);
		$key = $array[1];

		$query = $this->db->query("SELECT `salt` FROM `mcr_users` WHERE `id`='$uid' AND `gid`='1'");

		if (!$query || $this->db->num_rows($query) <= 0) {
			$this->core->notify($this->l10n->gettext('error_attention'), $this->l10n->gettext('error_sql_critical'), 1, "?mode=register");
		}

		$ar = $this->db->fetch_assoc($query);

		if ($key !== md5($ar['salt'])) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=403');
		}

		if (!$this->db->query(
			"UPDATE `mcr_users`
			SET `gid`='2', `ip_last`='{$this->user->ip}', `time_last`=NOW()
			WHERE `id`='$uid' AND `gid`='1'"
		)
		) {
			$this->core->notify($this->l10n->gettext('error_attention'), $this->l10n->gettext('error_sql_critical'), 1, "?mode=register");
		}

		// Лог действия
		$this->db->actlog("Подтверждение регистрации", $uid);

		$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('registration_accept'), 3);
	}

	private function regmain()
	{
		if (!$this->core->is_access('sys_register')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('reg_off_for_group'), 1, "?mode=403");
		}

		if ($this->user->is_auth) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('auth_already'), 2, '?mode=403');
		}

		return $this->core->sp(MCR_THEME_PATH . "modules/register/main.phtml");
	}
}

?>