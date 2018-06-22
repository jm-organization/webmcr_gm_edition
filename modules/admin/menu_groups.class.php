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

		if (!$this->core->is_access('sys_adm_menu_groups')) {
			$this->core->notify($this->l10n->gettext('403'), $this->l10n->gettext('error_403'));
		}

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL,
			$this->l10n->gettext('menugrp') => ADMIN_URL . "&do=menu_groups"
		];

		$this->core->bc = $this->core->gen_bc($bc);

		$this->core->header .= $this->core->sp(MCR_THEME_MOD . "admin/menu_groups/header.phtml");
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
				$content = $this->group_list();
				break;
		}

		return $content;
	}

	private function add()
	{
		if (!$this->core->is_access('sys_adm_menu_groups_add')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=menu_groups');
		}

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL,
			$this->l10n->gettext('menugrp') => ADMIN_URL . "&do=menu_groups",
			$this->l10n->gettext('mgrp_add') => ADMIN_URL . "&do=menu_groups&op=add",
		];

		$this->core->bc = $this->core->gen_bc($bc);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$title = $this->db->safesql(@$_POST['title']);
			$text = $this->db->safesql(@$_POST['text']);
			$permissions = $this->db->safesql(@$_POST['permissions']);
			$priority = intval(@$_POST['priority']);

			if (!$this->core->validate_perm($permissions)) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('mgrp_perm_not_exist'), 2, '?mode=admin&do=menu_groups&op=add');
			}

			if (!$this->db->query(
				"INSERT INTO `mcr_menu_adm_groups` (title, `text`, `access`, `priority`)
				VALUES ('$title', '$text', '$permissions', '$priority')"
			)
			) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('e_sql_critical'), 2, '?mode=admin&do=menu_groups');
			}

			$id = $this->db->insert_id();

			// Последнее обновление пользователя
			$this->db->update_user($this->user);
			// Лог действия
			$this->db->actlog($this->l10n->gettext('log_add_mgrp') . " #$id " . $this->l10n->gettext('log_mgrp'), $this->user->id);

			$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('mgrp_add_success'), 3, '?mode=admin&do=menu_groups');
		}

		$data = [
			"PAGE" => $this->l10n->gettext('mgrp_add_page_name'),
			"TITLE" => '',
			"TEXT" => '',
			"PERMISSIONS" => $this->core->perm_list(),
			"PRIORITY" => 1,
			"BUTTON" => $this->l10n->gettext('save')
		];

		return $this->core->sp(MCR_THEME_MOD . "admin/menu_groups/group-form.phtml", $data);
	}

	private function edit()
	{
		if (!$this->core->is_access('sys_adm_menu_groups_edit')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=menu_groups');
		}

		$id = intval($_GET['id']);

		$query = $this->db->query("
			SELECT title, `text`, `access`, `priority`
			FROM `mcr_menu_adm_groups`
			WHERE id='$id'
		");

		if ((!$query || $this->db->num_rows($query) <= 0) && $_SERVER['REQUEST_METHOD'] == 'GET') {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=menu_groups');
		}

		$ar = $this->db->fetch_assoc($query);

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL,
			$this->l10n->gettext('menugrp') => ADMIN_URL . "&do=menu_groups",
			$this->l10n->gettext('mgrp_edit') => ADMIN_URL . "&do=menu_groups&op=edit&id=$id",
		];
		$this->core->bc = $this->core->gen_bc($bc);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$title = $this->db->safesql(@$_POST['title']);
			$text = $this->db->safesql(@$_POST['text']);
			$permissions = $this->db->safesql(@$_POST['permissions']);
			$priority = intval(@$_POST['priority']);

			if (!$this->core->validate_perm($permissions)) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('mgrp_perm_not_exist'), 2, '?mode=admin&do=menu_groups');
			}

			if (!$this->db->query(
				"UPDATE `mcr_menu_adm_groups`
				SET title='$title', `text`='$text', `access`='$permissions', `priority`='$priority'
				WHERE id='$id'"
			)
			) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=menu_groups&op=edit&id=' . $id);
			}

			// Последнее обновление пользователя
			$this->db->update_user($this->user);
			// Лог действия
			$this->db->actlog($this->l10n->gettext('log_edit_mgrp') . " #$id " . $this->l10n->gettext('log_mgrp'), $this->user->id);

			$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('mgrp_edit_success'), 3, '?mode=admin&do=menu_groups');
		}

		$data = [
			"PAGE" => $this->l10n->gettext('mgrp_edit_page_name'),
			"TITLE" => $this->db->HSC($ar['title']),
			"TEXT" => $this->db->HSC($ar['text']),
			"PERMISSIONS" => $this->core->perm_list($ar['access']),
			"PRIORITY" => intval($ar['priority']),
			"BUTTON" => $this->l10n->gettext('save')
		];

		return $this->core->sp(MCR_THEME_MOD . "admin/menu_groups/group-form.phtml", $data);
	}

	private function delete()
	{
		if (!$this->core->is_access('sys_adm_menu_groups_delete')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=menu_groups');
		}

		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_hack'), 2, '?mode=admin&do=menu_groups');
		}

		$list = @$_POST['id'];

		if (empty($list)) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('mgrp_not_selected'), 2, '?mode=admin&do=menu_groups');
		}

		$list = $this->core->filter_int_array($list);
		$list = array_unique($list);
		$list = $this->db->safesql(implode(", ", $list));

		if (!$this->db->remove_fast("mcr_menu_adm_groups", "id IN ($list)")) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('e_sql_critical'), 2, '?mode=admin&do=menu_groups');
		}

		$count = $this->db->affected_rows();

		// Последнее обновление пользователя
		$this->db->update_user($this->user);
		// Лог действия
		$this->db->actlog($this->l10n->gettext('log_del_mgrp') . " $list " . $this->l10n->gettext('log_mgrp'), $this->user->id);

		$this->core->notify($this->l10n->gettext('error_success'), sprintf($this->l10n->gettext('elements_deleted'), $count), 3, '?mode=admin&do=menu_groups');
	}

	private function group_list()
	{

		$sql = "SELECT COUNT(*) FROM `mcr_menu_adm_groups`";
		$page = "?mode=admin&do=menu_groups";

		if (isset($_GET['search']) && !empty($_GET['search'])) {
			$search = $this->db->safesql($_GET['search']);
			$sql = "SELECT COUNT(*) FROM `mcr_menu_adm_groups` WHERE title LIKE '%$search%'";
			$search = $this->db->HSC($_GET['search']);
			$page = "?mode=admin&do=menu_groups&search=$search";
		}

		if (isset($_GET['sort']) && !empty($_GET['sort'])) {
			$page .= '&sort=' . $this->db->HSC(urlencode($_GET['sort']));
		}

		$query = $this->db->query($sql);

		if ($query) {
			$ar = $this->db->fetch_array($query);

			$data = [
				"PAGINATION" => $this->core->pagination($this->cfg->pagin['adm_menu_groups'], $page . '&pid=', $ar[0]),
				"GROUPS" => $this->group_array()
			];

			return $this->core->sp(MCR_THEME_MOD . "admin/menu_groups/group-list.phtml", $data);
		}

		exit("SQL Error");
	}

	private function group_array()
	{

		$start = $this->core->pagination($this->cfg->pagin['adm_menu_groups'], 0, 0); // Set start pagination
		$end = $this->cfg->pagin['adm_menu_groups']; // Set end pagination

		$where = "";
		$sort = "`g`.`id`";
		$sortby = "DESC";

		if (isset($_GET['search']) && !empty($_GET['search'])) {
			$search = $this->db->safesql($_GET['search']);
			$where = "WHERE `g`.title LIKE '%$search%'";
		}

		if (isset($_GET['sort']) && !empty($_GET['sort'])) {
			$expl = explode(' ', $_GET['sort']);

			$sortby = ($expl[0] == 'asc') ? "ASC" : "DESC";

			switch (@$expl[1]) {
				case 'title':
					$sort = "`g`.title";
					break;
				case 'perm':
					$sort = "`p`.title";
					break;
			}
		}

		$query = $this->db->query("
			SELECT 
				`g`.id, `g`.title, `g`.`text`, 
				
				`p`.id AS `pid`, `p`.`title` AS `perm`
			FROM `mcr_menu_adm_groups` AS `g`
			
			LEFT JOIN `mcr_permissions` AS `p`
				ON `p`.`value`=`g`.`access`
				
			$where
			
			ORDER BY $sort $sortby
			
			LIMIT $start, $end
		");

		if (!$query || $this->db->num_rows($query) <= 0) {
			return $this->core->sp(MCR_THEME_MOD . "admin/menu_groups/group-none.phtml");
		}

		ob_start();

		while ($ar = $this->db->fetch_assoc($query)) {

			$page_data = [
				"ID" => intval($ar['id']),
				"TITLE" => $this->db->HSC($ar['title']),
				"TEXT" => $this->db->HSC($ar['text']),
				"PERMISSIONS" => $this->db->HSC($ar['perm']),
				"PID" => intval($ar['pid']),
			];

			echo $this->core->sp(MCR_THEME_MOD . "admin/menu_groups/group-id.phtml", $page_data);
		}

		return ob_get_clean();
	}
}

?>