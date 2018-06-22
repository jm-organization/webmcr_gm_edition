<?php

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

require_once MCR_LIBS_PATH . 'array_group_by.php';

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

		if (!$this->core->is_access('sys_adm_menu_adm')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'));
		}

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL,
			$this->l10n->gettext('manucp') => ADMIN_URL . "&do=menu_adm"
		];
		$this->core->bc = $this->core->gen_bc($bc);

		$this->core->header .= $this->core->sp(MCR_THEME_MOD . "admin/menu_adm/header.phtml");
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
				$content = $this->menu_list();
				break;
		}

		return $content;
	}

	private function add()
	{
		if (!$this->core->is_access('sys_adm_menu_adm_add')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=menu_adm');
		}

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL,
			$this->l10n->gettext('manucp') => ADMIN_URL . "&do=menu_adm",
			$this->l10n->gettext('mcp_add') => ADMIN_URL . "&do=menu_adm&op=add",
		];
		$this->core->bc = $this->core->gen_bc($bc);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$title = $this->db->safesql(@$_POST['title']);
			$gid = intval(@$_POST['gid']);
			$text = $this->db->safesql(@$_POST['text']);
			$url = $this->db->safesql(@$_POST['url']);
			$target = (@$_POST['target'] == "_blank") ? "_blank" : "_self";
			$permissions = $this->db->safesql(@$_POST['permissions']);
			$priority = intval(@$_POST['priority']);
			$icon = intval(@$_POST['icon']);
			$page_id = trim(@$_POST['page_id']);

			if (!$this->core->validate_perm($permissions)) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('mcp_perm_not_exist'), 2, '?mode=admin&do=menu_adm');
			}

			// Check exist fields in base
			if (!$this->validate_element($gid, 'mcr_menu_adm_groups')) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_hack'), 2, '?mode=admin&do=menu_adm');
			}
			if (!$this->validate_element($icon, 'mcr_menu_adm_icons')) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_hack'), 2, '?mode=admin&do=menu_adm');
			}

			if (empty($page_id)) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('page_id_mast_be_full'), 2, '?mode=admin&do=menu_adm');
			}

			if (!$this->db->query(
				"INSERT INTO `mcr_menu_adm` (page_id, title, gid, `text`, `url`, `target`, `access`, `priority`, icon)
				VALUES ('$page_id', '$title', '$gid', '$text', '$url', '$target', '$permissions', '$priority', '$icon')"
			)
			) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=menu_adm');
			}

			$id = $this->db->insert_id();

			// Добавляем page_id меню в таблицу грууп, в группу, которая указана при добавлении.
			$this->add_page_id_to_group($gid, $page_id);

			// Последнее обновление пользователя
			$this->db->update_user($this->user);
			// Лог действия
			$this->db->actlog($this->l10n->gettext('log_add_mcp') . " #$id " . $this->l10n->gettext('log_mcp'), $this->user->id);

			$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('mcp_add_success'), 3, '?mode=admin&do=menu_adm');
		}

		$data = [
			"PAGE" => $this->l10n->gettext('mcp_add_page_name'),
			"PAGE_ID" => '',
			"TITLE" => '',
			"TEXT" => '',
			"URL" => '',
			"PERMISSIONS" => $this->core->perm_list(),
			"GROUPS" => $this->groups(),
			"ICONS" => $this->icons(),
			"TARGET" => '',
			"PRIORITY" => 1,
			"BUTTON" => $this->l10n->gettext('save'),
			"EDIT" => false
		];

		return $this->core->sp(MCR_THEME_MOD . "admin/menu_adm/menu-form.phtml", $data);
	}

	private function validate_element($id = 0, $table = '')
	{
		$id = intval($id);

		$query = $this->db->query("SELECT COUNT(*) FROM `$table` WHERE id='$id'");

		if ($query) {
			$ar = $this->db->fetch_array($query);

			if ($ar[0] <= 0) {
				return false;
			}

			return true;
		}

		return false;
	}

	private function add_page_id_to_group($group_id, $page_id)
	{
		$groups = $this->db->query("SELECT `page_ids` FROM `mcr_menu_adm_groups` WHERE `id`='$group_id'");
		$group = $groups->fetch_all(MYSQLI_ASSOC)[0];

		$page_ids_other = explode('|', $group['page_ids']);
		array_push($page_ids_other, $page_id);
		$page_ids_other = implode('|', $page_ids_other);

		if (!$this->db->query("UPDATE `mcr_menu_adm_groups` SET `page_ids`='$page_ids_other' WHERE `id`='$group_id'")) {
			return false;
		}

		return true;
	}

	private function groups($selected = 1)
	{
		$selected = intval($selected);

		$query = $this->db->query("SELECT id, title FROM `mcr_menu_adm_groups`");
		if (!$query || $this->db->num_rows($query) <= 0) {
			return null;
		}

		ob_start();

		while ($ar = $this->db->fetch_assoc($query)) {
			$id = intval($ar['id']);
			$title = $this->db->HSC($ar['title']);

			$select = ($selected == $id) ? 'selected' : '';

			echo "<option value=\"$id\" $select>$title</option>";
		}

		return ob_get_clean();
	}

	private function icons($selected = 1)
	{
		$selected = intval($selected);
		$result = [];
		$query = $this->db->query("SELECT id, title, img FROM `mcr_menu_adm_icons`");

		if ($query && $this->db->num_rows($query) > 0) {
			while ($ar = $this->db->fetch_assoc($query)) {
				$result[] = [
					"ID" => intval($ar['id']),
					"TITLE" => $this->db->HSC($ar['title']),
					"CHECKED" => ($selected == intval($ar['id'])) ? 'checked' : '',
					"IMG" => $this->db->HSC($ar['img'])
				];
			}

			$result = array_chunk($result, 8);
		}

		return $result;
	}

	private function edit()
	{
		if (!$this->core->is_access('sys_adm_menu_adm_edit')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=menu_adm');
		}

		$id = intval($_GET['id']);

		$query = $this->db->query("
			SELECT 
				`id`, title, gid, `text`, 
				`url`, `target`, `access`, 
				`priority`, icon,
				`page_id`
			FROM `mcr_menu_adm`
			
			WHERE id='$id'
		");

		if (!$query || $this->db->num_rows($query) <= 0) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=menu_adm');
		}

		$ar = $this->db->fetch_assoc($query);

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL,
			$this->l10n->gettext('manucp') => ADMIN_URL . "&do=menu_adm",
			$this->l10n->gettext('mcp_edit') => ADMIN_URL . "&do=menu_adm&op=edit&id=$id",
		];
		$this->core->bc = $this->core->gen_bc($bc);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$title = $this->db->safesql(@$_POST['title']);
			$gid = intval(@$_POST['gid']);
			$text = $this->db->safesql(@$_POST['text']);
			$url = $this->db->safesql(@$_POST['url']);
			$target = (@$_POST['target'] == "_blank") ? "_blank" : "_self";
			$permissions = $this->db->safesql(@$_POST['permissions']);
			$priority = intval(@$_POST['priority']);
			$icon = intval(@$_POST['icon']);
			$page_id = $ar['page_id'];

			if (!$this->core->validate_perm($permissions)) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('mcp_perm_not_exist'), 2, '?mode=admin&do=menu_adm');
			}

			// Check exist fields in base
			if (!$this->validate_element($gid, 'mcr_menu_adm_groups')) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_hack'), 2, '?mode=admin&do=menu_adm');
			}
			if (!$this->validate_element($icon, 'mcr_menu_adm_icons')) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_hack'), 2, '?mode=admin&do=menu_adm');
			}

			// ищим группу с page_id %like% this_menu.page_id
			$groups = $this->db->query("SELECT `id`, `page_ids` FROM `mcr_menu_adm_groups` WHERE `page_ids` LIKE '%$page_id%'");
			$group = $groups->fetch_all(MYSQLI_ASSOC)[0];

			// если нашли, проверяем на схожесть id найденной группі с id группы, который пришёл с запроса
			// если разные - обновляем
			if (empty($group) || $group['id'] != $gid) {
				$this->remove_page_id_from_group($group, $page_id);

				// добавляем this_menu.page_id в список айдишников страниц группы, указанной в запросе.
				$this->add_page_id_to_group($gid, $page_id);
			}

			if (!$this->db->query("
				UPDATE `mcr_menu_adm`
				SET 
					title='$title', 
					gid='$gid', 
					`text`='$text', 
					`url`='$url', 
					`target`='$target',
					`access`='$permissions', 
					`priority`='$priority', 
					icon='$icon'
				WHERE id='$id'
			")
			) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=menu_adm&op=edit&id=' . $id);
			}

			// Последнее обновление пользователя
			$this->db->update_user($this->user);
			// Лог действия
			$this->db->actlog($this->l10n->gettext('log_edit_mcp') . " #$id " . $this->l10n->gettext('log_mcp'), $this->user->id);

			$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('mcp_edit_success'), 3, '?mode=admin&do=menu_adm');
		}

		$data = [
			"PAGE" => $this->l10n->gettext('mcp_edit_page_name') . ' #' . $ar['id'],
			"PAGE_ID" => $ar['page_id'],
			"TITLE" => $this->db->HSC($ar['title']),
			"TEXT" => $this->db->HSC($ar['text']),
			"URL" => $this->db->HSC($ar['url']),
			"PERMISSIONS" => $this->core->perm_list($ar['access']),
			"GROUPS" => $this->groups($ar['gid']),
			"ICONS" => $this->icons($ar['icon']),
			"TARGET" => ($ar['target'] == '_blank') ? 'selected' : '',
			"PRIORITY" => intval($ar['priority']),
			"BUTTON" => $this->l10n->gettext('save'),
			"EDIT" => true
		];

		return $this->core->sp(MCR_THEME_MOD . "admin/menu_adm/menu-form.phtml", $data);
	}


	// @param: array $group - модель группы mcr_groups .

	private function remove_page_id_from_group(array $group, $page_id)
	{
		// удаляем id с группы
		$page_ids = explode('|', $group['page_ids']);

		// разворачиваем масив айдишников чтобы удалить page_id
		$page_ids = array_flip($page_ids);
		unset($page_ids[$page_id]);
		// возвращаем прежнее состояние, но без удалёного page_id
		$page_ids = array_flip($page_ids);

		$page_ids = implode('|', $page_ids);

		if (!$this->db->query("UPDATE `mcr_menu_adm_groups` SET `page_ids`='$page_ids' WHERE `id`='{$group['id']}'")) {
			return false;
		}

		return true;
	}

	private function delete()
	{
		if (!$this->core->is_access('sys_adm_menu_adm_delete')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=menu_adm');
		}

		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_hack'), 2, '?mode=admin&do=menu_adm');
		}

		$list = @$_POST['id'];

		if (empty($list)) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('mcp_not_selected'), 2, '?mode=admin&do=menu_adm');
		}

		$list = $this->core->filter_int_array($list);
		$list = array_unique($list);

		// Удаляем каждое выбранное меню из группы, к которой меню привязано.
		foreach ($list as $menu_id) {
			$query = $this->db->query("
				SELECT 
					`m`.`page_id`, `m`.`gid`,
					`mg`.`page_ids` AS `group_page_ids`
				FROM `mcr_menu_adm` AS `m` 
				
				LEFT JOIN `mcr_menu_adm_groups` AS `mg`
					ON `mg`.`id`=`m`.`gid`
				
				WHERE `m`.`id`='$menu_id'
			");

			$data = $query->fetch_all(MYSQLI_ASSOC)[0];

			$page_id = $data['page_id'];
			$group = [
				'id' => $data['gid'],
				'page_ids' => $data['group_page_ids']
			];

			$this->remove_page_id_from_group($group, $page_id);
		}

		$list = $this->db->safesql(implode(", ", $list));

		if (!$this->db->remove_fast("mcr_menu_adm", "id IN ($list)")) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=menu_adm');
		}

		$count = $this->db->affected_rows();

		// Последнее обновление пользователя
		$this->db->update_user($this->user);
		// Лог действия
		$this->db->actlog($this->l10n->gettext('log_del_mcp') . " $list " . $this->l10n->gettext('log_mcp'), $this->user->id);

		$this->core->notify($this->l10n->gettext('error_success'), sprintf($this->l10n->gettext('elements_deleted'), $count), 3, '?mode=admin&do=menu_adm');
	}

	private function menu_list()
	{
		$sql = "SELECT COUNT(*) FROM `mcr_menu_adm`";
		$page = "?mode=admin&do=menu_adm";

		if (isset($_GET['search']) && !empty($_GET['search'])) {
			$search = $this->db->safesql($_GET['search']);
			$sql = "SELECT COUNT(*) FROM `mcr_menu_adm` WHERE title LIKE '%$search%'";
			$search = $this->db->HSC($_GET['search']);
			$page = "?mode=admin&do=menu_adm&search=$search";
		}

		if (isset($_GET['sort']) && !empty($_GET['sort'])) {
			$page .= '&sort=' . $this->db->HSC(urlencode($_GET['sort']));
		}

		$query = $this->db->query($sql);
		$ar = @$this->db->fetch_array($query);

		$data = [
			"PAGINATION" => $this->core->pagination($this->cfg->pagin['adm_menu_adm'], $page . '&pid=', $ar[0]),
			"MENU" => $this->menu_array()
		];

		return $this->core->sp(MCR_THEME_MOD . "admin/menu_adm/menu-list.phtml", $data);
	}

	private function menu_array()
	{
		$start = $this->core->pagination($this->cfg->pagin['adm_menu_adm'], 0, 0); // Set start pagination
		$end = $this->cfg->pagin['adm_menu_adm']; // Set end pagination

		$where = "";
		$sort = "`m`.`id`";
		$sortby = "DESC";

		if (isset($_GET['search']) && !empty($_GET['search'])) {
			$search = $this->db->safesql($_GET['search']);
			$where = "WHERE `m`.title LIKE '%$search%'";
		}

		if (isset($_GET['sort']) && !empty($_GET['sort'])) {
			$expl = explode(' ', $_GET['sort']);

			$sortby = ($expl[0] == 'asc') ? "ASC" : "DESC";

			switch (@$expl[1]) {
				case 'title':
					$sort = "`m`.title";
					break;
				case 'group':
					$sort = "`g`.title";
					break;
			}
		}

		$query = $this->db->query("SELECT 
				`m`.id, `m`.gid, `m`.title,
				`m`.`url`, `m`.`target`, `m`.`fixed`, 
				
				`g`.title AS `group`
			FROM `mcr_menu_adm` AS `m`
			
			LEFT JOIN `mcr_menu_adm_groups` AS `g`
				ON `g`.id=`m`.gid
				
			$where
			
			ORDER BY $sort $sortby
			
			LIMIT $start, $end");

		if (!$query || $this->db->num_rows($query) <= 0) {
			return $this->core->sp(MCR_THEME_MOD . "admin/menu_adm/menu-none.phtml");
		}

		$pages = [];
		while ($ar = $this->db->fetch_assoc($query)) {
			$pages[] = [
				"ID" => intval($ar['id']),
				"GID" => intval($ar['gid']),
				"TITLE" => $this->db->HSC($ar['title']),
				"URL" => $this->db->HSC($ar['url']),
				"TARGET" => $this->db->HSC($ar['target']),
				"GROUP" => $this->db->HSC($ar['group']),
				"FIXED" => $ar['fixed'] ? 'checked' : '',
			];
		}

		//$menus = array_group_by($menus, 'GROUP');

		return $pages;
	}

	private function parents($select = 0, $not = false)
	{
		$select = intval($select);

		$not = ($not === false) ? -1 : intval($not);

		$query = $this->db->query("
			SELECT id, title
			FROM `mcr_menu`
			WHERE id!='$not'
			ORDER BY title ASC
		");

		ob_start();

		$selected = ($select === 0) ? 'selected' : '';

		echo '<option value="0" ' . $selected . '>' . $this->l10n->gettext('mcp_top_lvl') . '</option>';

		if (!$query || $this->db->num_rows($query) <= 0) {
			return ob_get_clean();
		}

		while ($ar = $this->db->fetch_assoc($query)) {
			$id = intval($ar['id']);
			$selected = ($id == $select) ? "selected" : "";

			$title = $this->db->HSC($ar['title']);

			echo "<option value=\"$id\" $selected>$title</option>";
		}

		return ob_get_clean();
	}
}