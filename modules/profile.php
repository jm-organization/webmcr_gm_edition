<?php

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class module
{
	private $core, $db, $cfg, $user, $l10n;

	public function __construct(core $core)
	{
		$this->core = $core;
		$this->db = $core->db;
		$this->cfg = $core->cfg;
		$this->user = $core->user;
		$this->l10n = $core->l10n;

		$bc = [$this->l10n->gettext('module_profile') => BASE_URL."?mode=profile"];
		$this->core->bc = $this->core->gen_bc($bc);
	}

	private function delete_skin()
	{
		if (!$this->user->is_skin) {
			$this->core->notify("", $this->l10n->gettext('skin_not_set'), 1, '?mode=profile');
		}

		$skin = MCR_SKIN_PATH.$this->user->skin.'.png';
		if (file_exists($skin)) {
			unlink($skin);
		}

		$interface_skin = MCR_SKIN_PATH.'interface/'.$this->user->skin.'.png';
		if (file_exists($interface_skin)) {
			unlink($interface_skin);
		}

		$interface_skin_min = MCR_SKIN_PATH.'interface/'.$this->user->skin.'_mini.png';
		if (file_exists($interface_skin_min)) {
			unlink($interface_skin_min);
		}

		if ($this->user->is_cloak) {
			$cloak = [
				"tmp_name" => MCR_CLOAK_PATH.$this->user->cloak.'.png',
				"size" => filesize(MCR_CLOAK_PATH.$this->user->cloak.'.png'),
				"error" => 0,
				"name" => $this->user->cloak.'.png'
			];

			require_once(MCR_TOOL_PATH.'cloak.class.php');
			new cloak($this->core, $cloak);
		}

		$ctables = $this->cfg->db['tables'];
		$us_f = $ctables['users']['fields'];

		if (!$this->db->query("
			UPDATE `{$this->cfg->tabname('users')}` 
			SET `{$us_f['is_skin']}`='0' 
			WHERE `{$us_f['id']}`='{$this->user->id}'
		")) {
			$this->core->notify($this->l10n->gettext('error_attention'), $this->l10n->gettext('error_sql_critical'));
		}

		// Лог действия
		$this->db->actlog($this->l10n->gettext('log_delete_skin'), $this->user->id);

		$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('skin_success_del'), 3, '?mode=profile');

		unset($skin, $interface_skin, $interface_skin_min);
	}

	private function delete_cloak()
	{
		if (!$this->user->is_cloak) {
			$this->core->notify("", $this->l10n->gettext('cloak_not_set'), 1, '?mode=profile');
		}

		if (file_exists(MCR_CLOAK_PATH.$this->user->login.'.png')) {
			unlink(MCR_CLOAK_PATH.$this->user->login.'.png');
		}

		if (!$this->user->is_skin) {
			unlink(MCR_SKIN_PATH.'interface/'.$this->user->login.'.png');
			unlink(MCR_SKIN_PATH.'interface/'.$this->user->login.'_mini.png');
		} else {
			$skin = [
				"tmp_name" => MCR_SKIN_PATH.$this->user->login.'.png',
				"size" => filesize(MCR_SKIN_PATH.$this->user->login.'.png'),
				"error" => 0,
				"name" => $this->user->login.'.png'
			];

			require_once(MCR_TOOL_PATH.'skin.class.php');
			new skin($this->core, $skin);
		}

		$ctables = $this->cfg->db['tables'];
		$us_f = $ctables['users']['fields'];

		if (!$this->db->query("
			UPDATE `mcr_users` 
			SET `is_cloak`='0' 
			WHERE `{$us_f['id']}`='{$this->user->id}'
		")) {
			$this->core->notify($this->l10n->gettext('error_attention'), $this->l10n->gettext('error_sql_critical'));
		}

		// Лог действия
		$this->db->actlog($this->l10n->gettext('log_delete_cloak'), $this->user->id);

		$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('cloak_success_del'), 3, '?mode=profile');
	}

	private function upload_skin()
	{
		require_once(MCR_TOOL_PATH.'skin.class.php');
		new skin($this->core, $_FILES['skin']);

		if ($this->user->is_cloak) {
			$cloak = [
				"tmp_name" => MCR_CLOAK_PATH.$this->user->login.'.png',
				"size" => (!file_exists(MCR_CLOAK_PATH.$this->user->login.'.png'))
					? 0
					: filesize(MCR_CLOAK_PATH.$this->user->login.'.png'),
				"error" => 0,
				"name" => $this->user->login.'.png'
			];

			require_once(MCR_TOOL_PATH.'cloak.class.php');
			new cloak($this->core, $cloak);
		}

		$ctables = $this->cfg->db['tables'];
		$us_f = $ctables['users']['fields'];

		if (!$this->db->query("
			UPDATE `{$this->cfg->tabname('users')}` 
			SET `{$us_f['is_skin']}`='1' 
			WHERE `{$us_f['id']}`='{$this->user->id}'
		")) {
			$this->core->notify($this->l10n->gettext('error_attention'), $this->l10n->gettext('error_sql_critical'));
		}

		// Лог действия
		$this->db->actlog($this->l10n->gettext('log_edit_sk'), $this->user->id);

		$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('skin_success_edit'), 3, '?mode=profile');
	}

	private function upload_cloak()
	{
		require_once(MCR_TOOL_PATH.'cloak.class.php');
		new cloak($this->core, $_FILES['cloak']);

		$ctables = $this->cfg->db['tables'];
		$us_f = $ctables['users']['fields'];

		if (!$this->db->query("
			UPDATE `{$this->cfg->tabname('users')}` 
			SET `{$us_f['is_cloak']}`='1' 
			WHERE `{$us_f['id']}`='{$this->user->id}'
		")) {
			$this->core->notify($this->l10n->gettext('error_attention'), $this->l10n->gettext('error_sql_critical'));
		}

		// Лог действия
		$this->db->actlog($this->l10n->gettext('log_edit_cl'), $this->user->id);

		$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('cloak_success_edit'), 3, '?mode=profile');
	}

	private function settings()
	{
		$newpass = $this->user->password;
		$newsalt = $this->user->salt;

		if (isset($_POST['newpass']) && !empty($_POST['newpass'])) {
			$old_pass = @$_POST['oldpass'];
			$old_pass = $this->core->gen_password($old_pass, $this->user->salt);

			if ($old_pass !== $this->user->password) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('e_valid_oldpass'), 2, '?mode=profile');
			}

			if ($_POST['newpass'] !== @$_POST['repass']) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('e_valid_repass'), 2, '?mode=profile');
			}

			$newsalt = $this->db->safesql($this->core->random());
			$newpass = $this->db->safesql($this->core->gen_password($_POST['newpass'], $newsalt));
		}

		$ctables = $this->cfg->db['tables'];
		$us_f = $ctables['users']['fields'];

		if (!$this->db->query("
			UPDATE `{$this->cfg->tabname('users')}`
			SET `{$us_f['pass']}`='$newpass', `{$us_f['salt']}`='$newsalt', `{$us_f['ip_last']}`='{$this->user->ip}', `{$us_f['date_last']}`=NOW()
			WHERE `{$us_f['id']}`='{$this->user->id}'
		")) {
			$this->core->notify($this->l10n->gettext('error_attention'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=profile');
		}

		// Лог действия
		$this->db->actlog($this->l10n->gettext('log_settings'), $this->user->id);

		$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('set_success_save'), 3, '?mode=profile');
	}

	private function is_access($rule, $callback, $else_callback)
	{
		if ($this->core->is_access($rule)) {
			$callback();
		}

		$else_callback();
	}

	public function content()
	{

		if (!$this->user->is_auth) {
			$this->core->notify($this->l10n->gettext('error_403'), $this->l10n->gettext('auth_required'), 1, "?mode=403");
		}

		if (!$this->core->is_access('sys_profile')) {
			$this->core->notify($this->l10n->gettext('error_403'), $this->l10n->gettext('access_by_admin'), 1, "?mode=403");
		}

		$this->core->header = $this->core->sp(MCR_THEME_MOD."profile/header.html");

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {

			// Последнее обновление пользователя
			$this->db->update_user($this->user);

			switch (true) {
				case isset($_POST['del-skin']):
					$this->is_access('sys_profile_del_skin', function() {
						$this->delete_skin();
					}, function() {
						$this->core->notify($this->l10n->gettext('error_403'), $this->l10n->gettext('skin_access_by_admin'), 1, "?mode=403");
					});
					break;
				case isset($_POST['del-cloak']):
					$this->is_access('sys_profile_del_cloak', function() {
						$this->delete_cloak();
					}, function() {
						$this->core->notify($this->l10n->gettext('error_403'), $this->l10n->gettext('cloak_access_by_admin'), 1, "?mode=403");
					});
					break;
				case isset($_FILES['skin']):
					$this->is_access('sys_profile_skin', function() {
						$this->upload_skin();
					}, function() {
						$this->core->notify($this->l10n->gettext('error_403'), $this->l10n->gettext('skin_edit_by_admin'), 1, "?mode=403");
					});
					break;
				case isset($_FILES['cloak']):
					$this->is_access('sys_profile_cloak', function() {
						$this->upload_cloak();
					}, function() {
						$this->core->notify($this->l10n->gettext('error_403'), $this->l10n->gettext('cloak_edit_by_admin'), 1, "?mode=403");
					});
					break;
				case isset($_POST['settings']):
					$this->is_access('sys_profile_settings', function() {
						$this->settings();
					}, function() {
						$this->core->notify($this->l10n->gettext('error_403'), $this->l10n->gettext('set_save_by_admin'), 1, "?mode=403");
					});
					break;

				default:
					$this->core->notify('', '', 3, '?mode=profile');
					break;
			}
		}

		return $this->core->sp(MCR_THEME_MOD."profile/profile.html");
	}
}