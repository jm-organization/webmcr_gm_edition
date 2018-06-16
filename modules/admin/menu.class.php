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

		if (!$this->core->is_access('sys_adm_menu')) {
			$this->core->notify($this->l10n->gettext('403'), $this->l10n->gettext('e_403'));
		}

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL,
			$this->l10n->gettext('menu') => ADMIN_URL."&do=menu"
		];

		$this->core->bc = $this->core->gen_bc($bc);

		$this->core->header .= $this->core->sp(MCR_THEME_MOD."admin/menu/header.html");
	}

	private function menu_array()
	{

		$start = $this->core->pagination($this->cfg->pagin['adm_menu'], 0, 0); // Set start pagination
		$end = $this->cfg->pagin['adm_menu']; // Set end pagination

		$where = "";
		$sort = "`m`.id";
		$sortby = "DESC";

		if (isset($_GET['search']) && !empty($_GET['search'])) {
			$search = $this->db->safesql($_GET['search']);
			$where = "WHERE `m`.title LIKE '%$search%'";
		}

		if (isset($_GET['sort']) && !empty($_GET['sort'])) {
			$expl = explode(' ', $_GET['sort']);

			$sortby = ($expl[0] == 'asc')
				? "ASC"
				: "DESC";

			switch (@$expl[1]) {
				case 'title':
					$sort = "`m`.title";
					break;
				case 'parent':
					$sort = "`p`.title";
					break;
			}
		}

		$query = $this->db->query(
			"SELECT 
				`m`.id, `m`.title, `m`.`parent`, 
				`m`.`url`, `m`.`target`, 
				
				`p`.title AS `ptitle`
			FROM `mcr_menu` AS `m`
			
			LEFT JOIN `mcr_menu` AS `p`
				ON `p`.id=`m`.`parent`
				
			$where
			
			ORDER BY $sort $sortby
			
			LIMIT $start, $end"
		);

		if (!$query || $this->db->num_rows($query) <= 0) {
			return $this->core->sp(MCR_THEME_MOD."admin/menu/menu-none.html");
		}

		ob_start();

		while ($ar = $this->db->fetch_assoc($query)) {
			$parent = (intval($ar['parent']) === 0)
				? $this->l10n->gettext('menu_top_lvl')
				: $this->db->HSC($ar['ptitle']);

			$page_data = [
				"ID" => intval($ar['id']),
				"TITLE" => $this->db->HSC($ar['title']),
				"PID" => intval($ar['parent']),
				"URL" => $this->db->HSC($ar['url']),
				"TARGET" => $this->db->HSC($ar['target']),
				"PARENT" => $parent
			];

			echo $this->core->sp(MCR_THEME_MOD."admin/menu/menu-id.html", $page_data);
		}

		return ob_get_clean();
	}

	private function menu_list()
	{
		$sql = "SELECT COUNT(*) FROM `mcr_menu`";
		$page = "?mode=admin&do=menu";

		if (isset($_GET['search']) && !empty($_GET['search'])) {
			$search = $this->db->safesql($_GET['search']);
			$sql = "SELECT COUNT(*) FROM `mcr_menu` WHERE title LIKE '%$search%'";
			$search = $this->db->HSC($_GET['search']);
			$page = "?mode=admin&do=menu&search=$search";
		}

		if (isset($_GET['sort']) && !empty($_GET['sort'])) {
			$page .= '&sort='.$this->db->HSC(urlencode($_GET['sort']));
		}

		$query = $this->db->query($sql);

		if ($query) {
			$ar = $this->db->fetch_array($query);

			$data = [
				"PAGINATION" => $this->core->pagination($this->cfg->pagin['adm_menu'], $page.'&pid=', $ar[0]),
				"MENU" => $this->menu_array()
			];

			return $this->core->sp(MCR_THEME_MOD."admin/menu/menu-list.html", $data);
		}

		exit("SQL Error");
	}

	private function delete()
	{
		if (!$this->core->is_access('sys_adm_menu_delete')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=menu');
		}

		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_hack'), 2, '?mode=admin&do=menu');
		}

		$list = @$_POST['id'];

		if (empty($list)) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('menu_not_selected'), 2, '?mode=admin&do=menu');
		}

		$list = $this->core->filter_int_array($list);

		$list = array_unique($list);

		$list = $this->db->safesql(implode(", ", $list));

		if (!$this->db->remove_fast("mcr_menu", "id IN ($list) OR `parent` IN ($list)")) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=menu');
		}

		$count = $this->db->affected_rows();

		// Последнее обновление пользователя
		$this->db->update_user($this->user);
		// Лог действия
		$this->db->actlog($this->l10n->gettext('log_del_menu')." $list ".$this->l10n->gettext('log_menu'), $this->user->id);

		$this->core->notify($this->l10n->gettext('error_success'), sprintf($this->l10n->gettext('elements_deleted'), $count), 3, '?mode=admin&do=menu');
	}

	private function add()
	{
		if (!$this->core->is_access('sys_adm_menu_add')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=menu');
		}

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL,
			$this->l10n->gettext('menu') => ADMIN_URL."&do=menu",
			$this->l10n->gettext('menu_add') => ADMIN_URL."&do=menu&op=add",
		];
		$this->core->bc = $this->core->gen_bc($bc);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$title = $this->db->safesql(@$_POST['title']);
			$url = $this->db->safesql(@$_POST['url']);
			$style = preg_replace("/[^\w\-]+/i", "", @$_POST['style']);
			$parent = intval(@$_POST['parent']);
			$target = (@$_POST['target'] == "_blank") ? "_blank" : "_self";
			$permissions = $this->db->safesql(@$_POST['permissions']);

			if (!$this->core->validate_perm($permissions)) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('menu_perm_not_exist'), 2, '?mode=admin&do=menu');
			}

			if (!$this->db->query(
				"INSERT INTO `mcr_menu` (title, `parent`, `url`, `style`, `target`, `permissions`)
				VALUES ('$title', '$parent', '$url', '$style', '$target', '$permissions')"
			)) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=menu');
			}

			$id = $this->db->insert_id();

			// Последнее обновление пользователя
			$this->db->update_user($this->user);
			// Лог действия
			$this->db->actlog($this->l10n->gettext('log_add_menu')." #$id ".$this->l10n->gettext('log_menu'), $this->user->id);

			$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('menu_add_success'), 3, '?mode=admin&do=menu');
		}

		$data = [
			"PAGE" => $this->l10n->gettext('menu_add_page_name'),
			"TITLE" => '',
			"URL" => '',
			"STYLE" => '',
			"PERMISSIONS" => $this->core->perm_list(),
			"PARENTS" => $this->parents(),
			"TARGET" => '',
			"BUTTON" => $this->l10n->gettext('save')
		];

		return $this->core->sp(MCR_THEME_MOD."admin/menu/menu-add.html", $data);
	}

	private function edit()
	{
		if (!$this->core->is_access('sys_adm_menu_edit')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=menu');
		}

		$id = intval($_GET['id']);

		$query = $this->db->query(
			"SELECT 
				title, `parent`, `url`, `style`, 
				`target`, permissions
			FROM `mcr_menu`
			
			WHERE id='$id'"
		);

		if (!$query || $this->db->num_rows($query) <= 0) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=menu');
		}

		$ar = $this->db->fetch_assoc($query);

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL,
			$this->l10n->gettext('menu') => ADMIN_URL."&do=menu",
			$this->l10n->gettext('menu_edit') => ADMIN_URL."&do=menu&op=edit&id=$id",
		];
		$this->core->bc = $this->core->gen_bc($bc);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$title = $this->db->safesql(@$_POST['title']);
			$url = $this->db->safesql(@$_POST['url']);
			$style = preg_replace("/[^\w\-]+/i", "", @$_POST['style']);
			$parent = intval(@$_POST['parent']);
			$target = (@$_POST['target'] == "_blank") ? "_blank" : "_self";
			$permissions = $this->db->safesql(@$_POST['permissions']);

			if (!$this->core->validate_perm($permissions)) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('menu_perm_not_exist'), 2, '?mode=admin&do=menu');
			}

			if (!$this->db->query(
				"UPDATE `mcr_menu`
				SET 
					title='$title', 
					`parent`='$parent', 
					`url`='$url', 
					`style`='$style', 
					`target`='$target', 
					`permissions`='$permissions'
				WHERE id='$id'"
			)) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=menu&op=edit&id='.$id);
			}

			// Последнее обновление пользователя
			$this->db->update_user($this->user);
			// Лог действия
			$this->db->actlog($this->l10n->gettext('log_edit_menu')." #$id ".$this->l10n->gettext('log_menu'), $this->user->id);

			$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('menu_edit_success'), 3, '?mode=admin&do=menu');
		}

		$data = [
			"PAGE" => $this->l10n->gettext('menu_edit_page_name'),
			"TITLE" => $this->db->HSC($ar['title']),
			"URL" => $this->db->HSC($ar['url']),
			"STYLE" => $this->db->HSC($ar['style']),
			"PERMISSIONS" => $this->core->perm_list($ar['permissions']),
			"PARENTS" => $this->parents($ar['parent'], $id),
			"TARGET" => ($ar['target'] == '_blank')
				? 'selected'
				: '',
			"BUTTON" => $this->l10n->gettext('save')
		];

		return $this->core->sp(MCR_THEME_MOD."admin/menu/menu-add.html", $data);
	}

	private function parents($select = 0, $not = false)
	{
		$select = intval($select);
		$not = ($not === false) ? -1 : intval($not);
		$query = $this->db->query(
			"SELECT 
				id, title
			FROM `mcr_menu`
			
			WHERE id!='$not'
			
			ORDER BY title ASC"
		);

		ob_start();

		$selected = ($select === 0) ? 'selected' : '';

		echo '<option value="0" '.$selected.'>'.$this->l10n->gettext('menu_top_lvl').'</option>';

		if (!$query || $this->db->num_rows($query) <= 0) {
			return ob_get_clean();
		}

		while ($ar = $this->db->fetch_assoc($query)) {
			$id = intval($ar['id']);
			$selected = ($id == $select)
				? "selected"
				: "";

			$title = $this->db->HSC($ar['title']);

			echo "<option value=\"$id\" $selected>$title</option>";
		}

		return ob_get_clean();
	}

	public function content()
	{
		$content = '';
		$op = (isset($_GET['op']))
			? $_GET['op']
			: 'list';

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
				$content = $this->menu_list();
				break;
		}

		return $content;
	}
}