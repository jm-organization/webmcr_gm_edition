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

		if (!$this->core->is_access('sys_adm_menu_icons')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'));
		}

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL,
			$this->l10n->gettext('icons') => ADMIN_URL."&do=menu_icons"
		];
		$this->core->bc = $this->core->gen_bc($bc);

		$this->core->header .= $this->core->sp(MCR_THEME_MOD."admin/menu_icons/header.phtml");
	}

	private function icon_array()
	{
		$start = $this->core->pagination($this->cfg->pagin['adm_menu_icons'], 0, 0); // Set start pagination
		$end = $this->cfg->pagin['adm_menu_icons']; // Set end pagination

		$where = "";
		$sort = "id";
		$sortby = "DESC";

		if (isset($_GET['search']) && !empty($_GET['search'])) {
			$search = $this->db->safesql($_GET['search']);
			$where = "WHERE title LIKE '%$search%'";
		}

		if (isset($_GET['sort']) && !empty($_GET['sort'])) {
			$expl = explode(' ', $_GET['sort']);

			$sortby = ($expl[0] == 'asc') ? "ASC" : "DESC";

			switch (@$expl[1]) {
				case 'title':
					$sort = "title";
					break;
			}
		}

		$query = $this->db->query(
			"SELECT 
				id, title, img
			FROM `mcr_menu_adm_icons`
			
			$where
			
			ORDER BY $sort $sortby
			
			LIMIT $start, $end"
		);

		if (!$query || $this->db->num_rows($query) <= 0) {
			return $this->core->sp(MCR_THEME_MOD."admin/menu_icons/icon-none.phtml");
		}

		ob_start();

		while ($ar = $this->db->fetch_assoc($query)) {

			$page_data = [
				"ID" => intval($ar['id']),
				"TITLE" => $this->db->HSC($ar['title']),
				"IMG" => $this->db->HSC($ar['img']),
			];

			echo $this->core->sp(MCR_THEME_MOD."admin/menu_icons/icon-id.phtml", $page_data);
		}

		return ob_get_clean();
	}

	private function icon_list()
	{

		$sql = "SELECT COUNT(*) FROM `mcr_menu_adm_icons`";
		$page = "?mode=admin&do=menu_icons";

		if (isset($_GET['search']) && !empty($_GET['search'])) {
			$search = $this->db->safesql($_GET['search']);
			$sql = "SELECT COUNT(*) FROM `mcr_menu_adm_icons` WHERE title LIKE '%$search%'";
			$search = $this->db->HSC($_GET['search']);
			$page = "?mode=admin&do=menu_icons&search=$search";
		}

		if (isset($_GET['sort']) && !empty($_GET['sort'])) {
			$page .= '&sort='.$this->db->HSC(urlencode($_GET['sort']));
		}

		$query = $this->db->query($sql);

		if ($query) {
			$ar = $this->db->fetch_array($query);

			$data = [
				"PAGINATION" => $this->core->pagination($this->cfg->pagin['adm_menu_icons'], $page.'&pid=', $ar[0]),
				"ICONS" => $this->icon_array()
			];

			return $this->core->sp(MCR_THEME_MOD."admin/menu_icons/icon-list.phtml", $data);
		}

		exit("SQL Error");
	}

	private function delete()
	{
		if (!$this->core->is_access('sys_adm_menu_icons_delete')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=menu_icons');
		}

		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_hack'), 2, '?mode=admin&do=menu_icons');
		}

		$list = @$_POST['id'];

		if (empty($list)) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('mi_not_selected'), 2, '?mode=admin&do=menu_icons');
		}

		$list = $this->core->filter_int_array($list);
		$list = array_unique($list);
		$list = $this->db->safesql(implode(", ", $list));

		if (!$this->db->remove_fast("mcr_menu_adm_icons", "id IN ($list)")) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=menu_icons');
		}

		$count = $this->db->affected_rows();

		// Последнее обновление пользователя
		$this->db->update_user($this->user);
		// Лог действия
		$this->db->actlog($this->l10n->gettext('log_del_mi')." $list ".$this->l10n->gettext('log_mi'), $this->user->id);

		$this->core->notify($this->l10n->gettext('error_success'), sprintf($this->l10n->gettext('elements_deleted'), $count), 3, '?mode=admin&do=menu_icons');
	}

	private function add()
	{
		if (!$this->core->is_access('sys_adm_menu_icons_add')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=menu_icons');
		}

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL,
			$this->l10n->gettext('icons') => ADMIN_URL."&do=menu_icons",
			$this->l10n->gettext('mi_add') => ADMIN_URL."&do=menu_icons&op=add",
		];
		$this->core->bc = $this->core->gen_bc($bc);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$title = $this->db->safesql(@$_POST['title']);
			$img = @$_POST['img'];
			$img = (empty($img)) ? 'default.png' : $this->db->safesql($img);

			if (!$this->db->query(
				"INSERT INTO `mcr_menu_adm_icons` (title, img)
				VALUES ('$title', '$img')"
			)) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=menu_icons');
			}

			$id = $this->db->insert_id();

			// Последнее обновление пользователя
			$this->db->update_user($this->user);
			// Лог действия
			$this->db->actlog($this->l10n->gettext('mi_add_page_name')." #$id ".$this->l10n->gettext('log_mi'), $this->user->id);

			$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('mi_add_success'), 3, '?mode=admin&do=menu_icons');
		}

		$data = [
			"PAGE" => $this->l10n->gettext('mi_add_page_name'),
			"TITLE" => '',
			"IMG" => 'default.png',
			"BUTTON" => $this->l10n->gettext('save')
		];

		return $this->core->sp(MCR_THEME_MOD."admin/menu_icons/icon-form.phtml", $data);
	}

	private function edit()
	{
		if (!$this->core->is_access('sys_adm_menu_icons_edit')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=menu_icons');
		}

		$id = intval($_GET['id']);

		$query = $this->db->query(
			"SELECT 
				title, img
			FROM `mcr_menu_adm_icons`
			
			WHERE id='$id'"
		);

		if (!$query || $this->db->num_rows($query) <= 0) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=menu_icons');
		}

		$ar = $this->db->fetch_assoc($query);

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL,
			$this->l10n->gettext('icons') => ADMIN_URL."&do=menu_icons",
			$this->l10n->gettext('mi_edit') => ADMIN_URL."&do=menu_icons&op=edit&id=$id",
		];
		$this->core->bc = $this->core->gen_bc($bc);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$title = $this->db->safesql(@$_POST['title']);
			$img = @$_POST['img'];
			$img = (empty($img)) ? 'default.png' : $this->db->safesql($img);

			if (!$this->db->query(
				"UPDATE `mcr_menu_adm_icons`
				SET title='$title', img='$img'
				WHERE id='$id'"
			)) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=menu_icons&op=edit&id='.$id);
			}

			// Последнее обновление пользователя
			$this->db->update_user($this->user);
			// Лог действия
			$this->db->actlog($this->l10n->gettext('log_edit_mi')." #$id ".$this->l10n->gettext('log_mi'), $this->user->id);

			$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('mi_edit_success'), 3, '?mode=admin&do=menu_icons');
		}

		$data = [
			"PAGE" => $this->l10n->gettext('mi_edit_page_name'),
			"TITLE" => $this->db->HSC($ar['title']),
			"IMG" => $this->db->HSC($ar['img']),
			"BUTTON" => $this->l10n->gettext('save')
		];

		return $this->core->sp(MCR_THEME_MOD."admin/menu_icons/icon-form.phtml", $data);
	}

	public function content()
	{
		$content = '';
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
				$content = $this->icon_list();
				break;
		}

		return $content;
	}
}

?>