<?php

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class submodule
{
	private $core, $db, $cfg, $user, $l10n, $cfg_m;

	public function __construct(core $core)
	{
		$this->core = $core;
		$this->db = $core->db;
		$this->cfg = $core->cfg;
		$this->user = $core->user;
		$this->l10n = $core->l10n;

		require_once(MCR_CONF_PATH.'modules/users.php');
		$this->cfg_m = $cfg;
	}

	public function content()
	{

		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			$this->core->js_notify($this->l10n->gettext('error_hack'));
		}

		if (!$this->core->is_access('mod_users_comment_add') || !$this->cfg_m['enable_comments']) {
			$this->core->js_notify($this->l10n->gettext('error_403'));
		}

		$ctables = $this->core->cfg->db['tables'];
		$us_f = $ctables['users']['fields'];

		$login = $this->db->safesql(@$_POST['login']);

		$query = $this->db->query("SELECT `id` FROM `mcr_users` WHERE `login`='$login'");

		if (!$query || $this->db->num_rows($query) <= 0) {
			$this->core->js_notify($this->l10n->gettext('error_403'));
		}

		$ar = $this->db->fetch_assoc($query);
		$uid = intval($ar[$us_f['id']]);
		$message = @$_POST['message'];
		$message_trim = trim($message);

		if (empty($message_trim)) {
			$this->core->js_notify($this->l10n->gettext('com_msg_empty'));
		}

		if (isset($_SESSION['add_comment'])) {
			if (intval($_SESSION['add_comment']) > time()) {
				$expire = intval($_SESSION['add_comment']) - time();
				$this->core->js_notify(sprintf($this->l10n->gettext('com_wait'), $expire));
			} else {
				$_SESSION['add_comment'] = time() + 30;
			}
		} else {
			$_SESSION['add_comment'] = time() + 30;
		}

		$bb = $this->core->load_bb_class(); // Object

		$text_html = $bb->parse($message);
		$safe_text_html = $this->db->safesql($text_html);

		$text_bb = $this->db->safesql($message);

		$message_strip = trim(strip_tags($text_html, "<img>"));

		if (empty($message_strip)) {
			$this->core->js_notify($this->l10n->gettext('com_msg_empty'));
		}

		$newdata = [
			"date_create" => time(),
			"date_update" => time()
		];

		$safedata = $this->db->safesql(json_encode($newdata));

		if (!$this->db->query(
			"INSERT INTO `mod_users_comments` (uid, `from`, text_html, text_bb, `data`)
			VALUES ('$uid', '{$this->user->id}', '$safe_text_html', '$text_bb', '$safedata')"
		)) {
			$this->core->js_notify($this->l10n->gettext('error_sql_critical'));
		}

		$id = $this->db->insert_id();

		// Последнее обновление пользователя
		$this->db->update_user($this->user);

		// Лог действия
		$this->db->actlog($this->l10n->gettext('log_com_add')." #$login", $this->user->id);

		$admin = '';

		if ($this->core->is_access('mod_users_comment_del') || $this->core->is_access('mod_users_comment_del_all')) {
			$admin = $this->core->sp(MCR_THEME_MOD."users/comments/comment-admin.html");
		}

		$com_data = [
			"ID" => $id,
			"TEXT" => $text_html,
			"DATE_CREATE" => date('d.m.Y '.$this->l10n['in'].' H:i'),
			"LOGIN" => $this->user->login_v2,
			'ADMIN' => $admin,
		];

		$content = $this->core->sp(MCR_THEME_MOD."users/comments/comment-id-self.html", $com_data);

		$this->core->js_notify($this->l10n->gettext('com_add_success'), $this->l10n->gettext('error_success'), true, $content);
	}
}