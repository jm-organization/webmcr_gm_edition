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

		if (!$this->core->is_access('sys_adm_statics')) {
			$this->core->notify($this->l10n->gettext('403'), $this->l10n->gettext('error_403'));
		}

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL,
			$this->l10n->gettext('module_statics') => ADMIN_URL."&do=statics"
		];
		$this->core->bc = $this->core->gen_bc($bc);

		$this->core->header .= $this->core->sp(MCR_THEME_MOD."admin/statics/header.html");
	}

	private function static_array()
	{
		$start = $this->core->pagination($this->cfg->pagin['adm_statics'], 0, 0); // Set start pagination
		$end = $this->cfg->pagin['adm_statics']; // Set end pagination

		$where = "";
		$sort = "`s`.`id`";
		$sortby = "DESC";

		if (isset($_GET['search']) && !empty($_GET['search'])) {
			$search = $this->db->safesql(urldecode($_GET['search']));
			$where = "WHERE `s`.title LIKE '%$search%'";
		}

		if (isset($_GET['sort']) && !empty($_GET['sort'])) {
			$expl = explode(' ', $_GET['sort']);

			$sortby = ($expl[0] == 'asc') ? "ASC" : "DESC";

			switch (@$expl[1]) {
				case 'title':
					$sort = "`s`.title";
					break;
				case 'perm':
					$sort = "`p`.title";
					break;
			}
		}

		$query = $this->db->query(
			"SELECT 
				`s`.id, `s`.`uniq`, `s`.title, `s`.uid,
				
				`p`.title AS `perm`
			FROM `mcr_statics` AS `s`
			
			LEFT JOIN `mcr_permissions` AS `p`
				ON `p`.`value`=`s`.`permissions`
				
			$where
			
			ORDER BY $sort $sortby
			
			LIMIT $start, $end"
		);
		if (!$query || $this->db->num_rows($query) <= 0) {
			return $this->core->sp(MCR_THEME_MOD."admin/statics/static-none.html");
		}

		ob_start();

		while ($ar = $this->db->fetch_assoc($query)) {

			$perm = (is_null($ar['perm'])) ? $this->l10n->gettext('stc_perm_not_exist') : $this->db->HSC($ar['perm']);

			$page_data = [
				"ID" => intval($ar['id']),
				"UID" => intval($ar['uid']),
				"UNIQ" => $this->db->HSC($ar['uniq']),
				"TITLE" => $this->db->HSC($ar['title']),
				"PERMISSIONS" => $perm
			];

			echo $this->core->sp(MCR_THEME_MOD."admin/statics/static-id.html", $page_data);
		}

		return ob_get_clean();
	}

	private function static_list()
	{

		$sql = "SELECT COUNT(*) FROM `mcr_statics`";
		$page = "?mode=admin&do=statics";

		if (isset($_GET['search']) && !empty($_GET['search'])) {
			$search = $this->db->safesql(urldecode($_GET['search']));
			$sql = "SELECT COUNT(*) FROM `mcr_statics` WHERE title LIKE '%$search%'";
			$search = $this->db->HSC(urldecode($_GET['search']));
			$page = "?mode=admin&do=statics&search=$search";
		}

		if (isset($_GET['sort']) && !empty($_GET['sort'])) {
			$page .= '&sort='.$this->db->HSC(urlencode($_GET['sort']));
		}

		$query = $this->db->query($sql);

		$ar = @$this->db->fetch_array($query);

		$data = [
			"PAGINATION" => $this->core->pagination($this->cfg->pagin['adm_statics'], $page.'&pid=', $ar[0]),
			"STATICS" => $this->static_array()
		];

		return $this->core->sp(MCR_THEME_MOD."admin/statics/static-list.html", $data);
	}

	private function delete()
	{
		if (!$this->core->is_access('sys_adm_statics_delete')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=statics');
		}

		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_hack'), 2, '?mode=admin&do=statics');
		}

		$list = @$_POST['id'];

		if (empty($list)) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('stc_not_selected'), 2, '?mode=admin&do=statics');
		}

		$list = $this->core->filter_int_array($list);

		$list = array_unique($list);

		$list = $this->db->safesql(implode(", ", $list));

		if (!$this->db->remove_fast("mcr_statics", "id IN ($list)")) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=statics');
		}

		$count1 = $this->db->affected_rows();

		// Последнее обновление пользователя
		$this->db->update_user($this->user);

		// Лог действия
		$this->db->actlog($this->l10n->gettext('log_del_stc')." $list ".$this->l10n->gettext('log_del_stc'), $this->user->id);

		$this->core->notify($this->l10n->gettext('error_success'), sprintf($this->l10n->gettext('elements_deleted'), $count1), 3, '?mode=admin&do=statics');
	}

	private function add()
	{
		if (!$this->core->is_access('sys_adm_statics_add')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=statics');
		}

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL."",
			$this->l10n->gettext('module_statics') => ADMIN_URL."&do=statics",
			$this->l10n->gettext('stc_add_page_name') => ADMIN_URL."&do=statics&op=add",
		];
		$this->core->bc = $this->core->gen_bc($bc);

		$preview = '';
		$title = '';
		$uniq = '';
		$text = '';
		$permissions = $this->core->perm_list();

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$title = $this->db->safesql(@$_POST['title']);

			$uniq = $this->db->safesql(@$_POST['uniq']);

			$permissions = $this->db->safesql(@$_POST['permissions']);

			// Обработка описания +
			$text = trim(@$_POST['text']);

			if (empty($text)) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('stc_e_text_empty'), 2, '?mode=admin&do=statics&op=add');
			}

			$to_validate_text = trim(str_replace('&nbsp;', '', strip_tags($text, "<img>")));
			if (empty($to_validate_text)) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('stc_e_text_incorrect'), 2, '?mode=admin&do=statics&op=add');
			}

			$safe_text = $this->db->safesql($text); // in base
			// Обработка описания -

			if (isset($_POST['preview'])) {
				$preview = $this->get_preview($title, $text);
				$title = $this->db->HSC($title);
				$uniq = $this->db->HSC($uniq);
				$text = $this->db->HSC($text);
				$permissions = $this->core->perm_list($permissions);
			} else {
				$new_data = [
					"time_create" => time(),
					"time_last" => time(),
					"login_create" => $this->user->login,
					"login_last" => $this->user->login
				];

				$new_data = $this->db->safesql(json_encode($new_data));

				if (!$this->db->query(
					"INSERT INTO `mcr_statics` (`uniq`, title, text_html, uid, `permissions`, `data`)
					VALUES ('$uniq', '$title', '$safe_text', '{$this->user->id}', '$permissions', '$new_data')"
				)) {
					$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=statics&op=add');
				}

				$id = $this->db->insert_id();

				// Последнее обновление пользователя
				$this->db->update_user($this->user);

				// Лог действия
				$this->db->actlog($this->l10n->gettext('log_add_stc')." #$id ".$this->l10n->gettext('log_add_stc'), $this->user->id);

				$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('stc_add_success'), 3, '?mode=admin&do=statics');
			}
		}

		$data = [
			"PAGE" => $this->l10n->gettext('stc_add_page_name'),
			"TITLE" => $title,
			"UNIQ" => $uniq,
			"TEXT" => $text,
			"PERMISSIONS" => $permissions,
			"BUTTON" => $this->l10n->gettext('add'),
			"PREVIEW" => $preview,
		];

		return $this->core->sp(MCR_THEME_MOD."admin/statics/static-add.html", $data);
	}

	private function get_preview($title = '', $text = '')
	{
		$data = [
			"TITLE" => $this->db->HSC($title),
			"TEXT" => $text
		];

		return $this->core->sp(MCR_THEME_MOD."admin/statics/static-preview.html", $data);
	}

	private function edit()
	{
		if (!$this->core->is_access('sys_adm_statics_edit')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=statics');
		}

		$id = intval($_GET['id']);

		$query = $this->db->query(
			"SELECT 
				`uniq`, title, `text_html` as `text`, `permissions`, `data`
			FROM `mcr_statics`
			
			WHERE id='$id'"
		);
		if (!$query || $this->db->num_rows($query) <= 0) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=statics');
		}

		$ar = $this->db->fetch_assoc($query);

		$preview = '';
		$title = $this->db->HSC($ar['title']);
		$uniq = $this->db->HSC($ar['uniq']);
		$text = $this->db->HSC($ar['text']);
		$permissions = $this->core->perm_list($ar['permissions']);

		$data = json_decode($ar['data']);

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL."",
			$this->l10n->gettext('module_statics') => ADMIN_URL."&do=statics",
			$this->l10n->gettext('stc_edit') => ADMIN_URL."&do=statics&op=edit&id=$id"
		];
		$this->core->bc = $this->core->gen_bc($bc);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$title = $this->db->safesql(@$_POST['title']);

			$uniq = $this->db->safesql(@$_POST['uniq']);

			$permissions = $this->db->safesql(@$_POST['permissions']);

			// Обработка описания +
			$text = trim(@$_POST['text']);

			if (empty($text)) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('stc_e_text_empty'), 2, '?mode=admin&do=statics&op=add');
			}

			$to_validate_text = trim(str_replace('&nbsp;', '', strip_tags($text, "<img>")));
			if (empty($to_validate_text)) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('stc_e_text_incorrect'), 2, '?mode=admin&do=statics&op=add');
			}

			$safe_text = $this->db->safesql($text); // in base
			// Обработка описания -

			if (isset($_POST['preview'])) {
				$preview = $this->get_preview($title, $text);
				$title = $this->db->HSC($title);
				$uniq = $this->db->HSC($uniq);
				$text = $this->db->HSC($text);
				$permissions = $this->core->perm_list($permissions);
			} else {
				$new_data = [
					"time_create" => $data->time_create,
					"time_last" => time(),
					"login_create" => $data->login_create,
					"login_last" => $this->user->login
				];

				$new_data = $this->db->safesql(json_encode($new_data));

				if (!$this->db->query(
					"UPDATE `mcr_statics`
					SET 
						`uniq`='$uniq', 
						title='$title',  
						text_html='$safe_text',
						`permissions`='$permissions', `data`='$new_data'
					WHERE id='$id'"
				)) {
					$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=statics&op=edit&id='.$id);
				}

				// Последнее обновление пользователя
				$this->db->update_user($this->user);

				// Лог действия
				$this->db->actlog($this->l10n->gettext('log_edit_stc')." #$id ".$this->l10n->gettext('log_edit_stc'), $this->user->id);

				$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('stc_edit_success'), 3, '?mode=admin&do=statics');
			}
		}

		$data = [
			"PAGE" => $this->l10n->gettext('stc_edit_page_name'),
			"TITLE" => $title,
			"UNIQ" => $uniq,
			"TEXT" => $text,
			"PERMISSIONS" => $permissions,
			"BUTTON" => $this->l10n->gettext('save'),
			"PREVIEW" => $preview,
		];

		return $this->core->sp(MCR_THEME_MOD."admin/statics/static-add.html", $data);
	}

	public function content()
	{

		$op = (isset($_GET['op'])) ? $_GET['op'] : 'list';

		switch ($op) {
			case 'add':
				$content = $this->add();
				break;
			case 'edit':
				$content = $this->edit();
				break;
			case 'delete':
				$this->delete();
				break;

			default:
				$content = $this->static_list();
				break;
		}

		return $content;
	}
}

?>