<?php

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class submodule
{
	private $core, $db, $cfg, $user, $l10n;

	public function __construct(core $core)
	{
		$this->core = $core;
		$this->db = $core->db;
		$this->cfg = $core->cfg;
		$this->user = $core->user;
		$this->l10n = $core->l10n;
	}

	public function content()
	{
		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			$this->core->js_notify($this->l10n->gettext('error_hack'));
		}

		if (!$this->core->is_access('sys_comment_add')) {
			$this->core->js_notify($this->l10n->gettext('com_perm_add'));
		}

		$nid = intval(@$_POST['id']);
		$message = trim(@$_POST['message']);

		if (!$this->is_discus($nid)) {
			$this->core->js_notify($this->l10n->gettext('com_disabled'));
		}

		if (empty($message)) {
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

		$message_strip = trim(strip_tags($text_html, "<img><hr><iframe>"));

		if (empty($message_strip)) {
			$this->core->js_notify($this->l10n->gettext('com_msg_incorrect'));
		}

		$time = time();

		$newdata = [
			"time_create" => $time,
			"time_last" => $time
		];

		$safedata = $this->db->safesql(json_encode($newdata));
		if (!$this->db->query(
			"INSERT INTO `mcr_news_comments`
				(nid, text_html, text_bb, uid, `data`, `date`)
			VALUES
				('$nid', '$safe_text_html', '$text_bb', '{$this->user->id}', '$safedata', $time)"
		)
		) {
			$this->core->js_notify($this->l10n->gettext('error_sql_critical'));
		}

		$id = $this->db->insert_id();

		// Последнее обновление пользователя
		$this->db->update_user($this->user);
		// Лог действия
		$this->db->actlog($this->l10n->gettext('log_com_add') . " #$id", $this->user->id);
		$act_del = $act_edt = $act_get = '';

		$data = [
			"ID" => $id,
			"LNG" => $this->l10n
		];

		if ($this->core->is_access('sys_comment_del') || $this->core->is_access('sys_comment_del_all')) {
			$act_del = $this->core->sp(MCR_THEME_MOD . "news/comments/comment-act-del.html", $data);
		}

		if ($this->core->is_access('sys_comment_edt') || $this->core->is_access('sys_comment_edt_all')) {
			$act_edt = $this->core->sp(MCR_THEME_MOD . "news/comments/comment-act-edt.html", $data);
		}

		if ($this->user->is_auth) {
			$act_get = $this->core->sp(MCR_THEME_MOD . "news/comments/comment-act-get.html", $data);
		}

		$com_data = [
			"ID" => $id,
			"NID" => $nid,
			"TEXT" => $text_html,
			"UID" => $this->user->id,
			"DATA" => $newdata,
			"LOGIN" => $this->user->login_v2,
			"ACTION_DELETE" => $act_del,
			"ACTION_EDIT" => $act_edt,
			"ACTION_QUOTE" => $act_get
		];

		$content = $this->core->sp(MCR_THEME_MOD . "news/comments/comment-id.html", $com_data);

		$this->core->js_notify($this->l10n->gettext('com_add_success'), $this->l10n->gettext('error_success'), true, $content);
	}

	private function is_discus($nid = 1)
	{
		$query = $this->db->query("SELECT COUNT(*) FROM `mcr_news` WHERE id='$nid' AND discus='1'");

		if ($query) {
			$ar = $this->db->fetch_array($query);

			if ($ar[0] <= 0) return false;

			return true;
		}

		return false;
	}
}