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

		if (!$this->core->is_access('sys_adm_groups')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'));
		}

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL,
			$this->l10n->gettext('module_groups') => ADMIN_URL."&do=groups"
		];
		$this->core->bc = $this->core->gen_bc($bc);

		$this->core->header .= $this->core->sp(MCR_THEME_MOD."admin/groups/header.html");
	}

	private function group_array()
	{
		$start = $this->core->pagination($this->cfg->pagin['adm_groups'], 0, 0); // Set start pagination
		$end = $this->cfg->pagin['adm_groups']; // Set end pagination
		$where = "";
		$sort = "id";
		$sortby = "DESC";

		if (isset($_GET['search']) && !empty($_GET['search'])) {
			$search = $this->db->safesql(urldecode($_GET['search']));
			$where = "WHERE `title` LIKE '%$search%'";
		}

		if (isset($_GET['sort']) && !empty($_GET['sort'])) {
			$expl = explode(' ', $_GET['sort']);

			$sortby = ($expl[0] == 'asc')
				? "ASC"
				: "DESC";

			switch (@$expl[1]) {
				case 'title':
					$sort = "`title`";
					break;
				case 'desc':
					$sort = "`description`";
					break;
			}
		}

		$query = $this->db->query(
			"SELECT 
				`id`, `title`, `color`, `description`
			FROM `mcr_groups`
			
			$where
			
			ORDER BY $sort $sortby
			
			LIMIT $start, $end"
		);

		if (!$query || $this->db->num_rows($query) <= 0) {
			return $this->core->sp(MCR_THEME_MOD."admin/groups/group-none.html");
		}

		ob_start();

		while ($ar = $this->db->fetch_assoc($query)) {
			$color = $this->db->HSC($ar['color']);

			$page_data = [
				"ID" => intval($ar['id']),
				"TITLE" => $this->core->colorize($this->db->HSC($ar['title']), $color),
				"TEXT" => $this->db->HSC($ar['description']),
			];

			echo $this->core->sp(MCR_THEME_MOD."admin/groups/group-id.html", $page_data);
		}

		return ob_get_clean();
	}

	private function group_list()
	{
		$sql = "SELECT COUNT(*) FROM `mcr_groups`";
		$page = "?mode=admin&do=groups";

		if (isset($_GET['search']) && !empty($_GET['search'])) {
			$search = $this->db->safesql(urldecode($_GET['search']));
			$sql = "SELECT COUNT(*) FROM `mcr_groups` WHERE `title` LIKE '%$search%'";
			$search = $this->db->HSC(urldecode($_GET['search']));
			$page = "?mode=admin&do=groups&search=$search";
		}

		if (isset($_GET['sort']) && !empty($_GET['sort'])) {
			$page .= '&sort='.$this->db->HSC(urlencode($_GET['sort']));
		}

		$query = $this->db->query($sql);

		if ($query) {
			$ar = $this->db->fetch_array($query);

			$data = [
				"PAGINATION" => $this->core->pagination($this->cfg->pagin['adm_groups'], $page.'&pid=', $ar[0]),
				"GROUPS" => $this->group_array()
			];

			return $this->core->sp(MCR_THEME_MOD."admin/groups/group-list.html", $data);
		}

		exit("SQL Error");
	}

	private function delete()
	{
		if (!$this->core->is_access('sys_adm_groups_delete')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=groups');
		}

		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_hack'), 2, '?mode=admin&do=groups');
		}

		$list = @$_POST['id'];

		if (empty($list)) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('grp_not_selected'), 2, '?mode=admin&do=groups');
		}

		$list = $this->core->filter_int_array($list);
		$list = array_unique($list);
		$list = $this->db->safesql(implode(", ", $list));

		if (!$this->db->remove_fast('mcr_groups', "`id` IN ($list)")) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=groups');
		}

		$count = $this->db->affected_rows();

		// Последнее обновление пользователя
		$this->db->update_user($this->user);
		// Лог действия
		$this->db->actlog($this->l10n->gettext('log_del_grp')." $list ".$this->l10n->gettext('log_grp'), $this->user->id);

		$this->core->notify($this->l10n->gettext('error_success'), sprintf($this->l10n->gettext('elements_deleted'), $count), 3, '?mode=admin&do=groups');
	}

	private function get_default_value($name = 'false', $value, $type = 'boolean')
	{
		$data = [
			'NAME' => $name,
			'VALUE' => ''
		];

		switch ($type) {
			case 'integer':
				$data['VALUE'] = intval($value);
				$input = $this->core->sp(MCR_THEME_MOD."admin/groups/perm-id-integer.html", $data);
				break;

			case 'float':
				$data['VALUE'] = floatval($value);
				$input = $this->core->sp(MCR_THEME_MOD."admin/groups/perm-id-float.html", $data);
				break;

			case 'string':
				$data['VALUE'] = $this->db->HSC($value);
				$input = $this->core->sp(MCR_THEME_MOD."admin/groups/perm-id-string.html", $data);
				break;

			default:
				$data['VALUE'] = ($value == 'true')
					? 'selected'
					: '';
				$input = $this->core->sp(MCR_THEME_MOD."admin/groups/perm-id-boolean.html", $data);
				break;
		}

		return $input;
	}

	private function perm_list($perm = '')
	{
		$json = [];
		$query = $this->db->query("SELECT title, `value`, `default`, `type` FROM `mcr_permissions`");
		if (!$query || $this->db->num_rows($query) <= 0) return null;

		if (!empty($perm)) {
			$json = json_decode($perm, true);
		}

		ob_start();

		while ($ar = $this->db->fetch_assoc($query)) {
			$data["TITLE"] = $this->db->HSC($ar['title']);
			$data["VALUE"] = $this->db->HSC($ar['value']);

			$value = (!isset($json[$ar['value']]))
				? $ar['default']
				: $json[$ar['value']];

			$data['DEFAULT'] = @$this->get_default_value($ar['value'], $value, $ar['type']);

			echo $this->core->sp(MCR_THEME_MOD."admin/groups/perm-id.html", $data);
		}

		return ob_get_clean();
	}

	private function gen_permissions($data)
	{
		if (empty($data)) {
			exit("System permissions error");
		}

		foreach ($data as $key => $value) {
			if ($value == 'true' || $value == 'false') {
				$data[$key] = ($value == 'true')
					? true
					: false;
			} else {
				$data[$key] = intval($value);
			}
		}

		return json_encode($data);
	}

	private function add()
	{
		if (!$this->core->is_access('sys_adm_groups_add')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=groups');
		}

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL,
			$this->l10n->gettext('module_groups') => ADMIN_URL."&do=groups",
			$this->l10n->gettext('grp_add_page_name') => ADMIN_URL."&do=groups&op=add",
		];
		$this->core->bc = $this->core->gen_bc($bc);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$title = $this->db->safesql(@$_POST['title']);
			$text = $this->db->safesql(@$_POST['text']);
			$color = $this->db->safesql(@$_POST['color']);

			if (!empty($color) && !preg_match("/^\#[a-f0-9]{6}|[a-f0-9]{3}$/i", $color)) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('grp_e_color_format'), 2, '?mode=admin&do=groups&op=add');
			}

			$perm_data = $_POST;
			unset($perm_data['submit'], $perm_data['mcr_secure'], $perm_data['title'], $perm_data['text']);
			$new_permissions = $this->db->safesql($this->gen_permissions($perm_data));

			if (!$this->db->query(
				"INSERT INTO `mcr_groups` (`title`, `description`, `color`, `permissions`)
				VALUES ('$title', '$text', '$color', '$new_permissions')"
			)) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=groups&op=add');
			}

			$id = $this->db->insert_id();

			// Последнее обновление пользователя
			$this->db->update_user($this->user);

			// Лог действия
			$this->db->actlog($this->l10n->gettext('log_add_grp')." #$id ".$this->l10n->gettext('log_grp'), $this->user->id);

			$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('grp_del_success'), 3, '?mode=admin&do=groups');
		}

		$data = [
			"PAGE" => $this->l10n->gettext('grp_add_page_name'),
			"TITLE" => '',
			"TEXT" => '',
			"COLOR" => '',
			"PERMISSIONS" => $this->perm_list(),
			"BUTTON" => $this->l10n->gettext('save')
		];

		return $this->core->sp(MCR_THEME_MOD."admin/groups/group-add.html", $data);
	}

	private function edit()
	{
		if (!$this->core->is_access('sys_adm_groups_edit')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=groups');
		}

		$id = intval($_GET['id']);

		$query = $this->db->query(
			"SELECT `title`, `description`, `color`, `permissions`
			FROM `mcr_groups`
			WHERE `id`='$id'"
		);

		if (!$query || $this->db->num_rows($query) <= 0) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=groups');
		}

		$ar = $this->db->fetch_assoc($query);

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL,
			$this->l10n->gettext('module_groups') => ADMIN_URL."&do=groups",
			$this->l10n->gettext('grp_edit_page_name ') => ADMIN_URL."&do=groups&op=edit&id=$id",
		];
		$this->core->bc = $this->core->gen_bc($bc);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$title = $this->db->safesql(@$_POST['title']);
			$text = $this->db->safesql(@$_POST['text']);
			$color = $this->db->safesql(@$_POST['color']);

			if (!empty($color) && !preg_match("/^\#[a-f0-9]{6}|[a-f0-9]{3}$/i", $color)) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('grp_e_color_format'), 2, '?mode=admin&do=groups&op=edit&id='.$id);
			}

			$perm_data = $_POST;
			unset($perm_data['submit'], $perm_data['mcr_secure'], $perm_data['title'], $perm_data['text']);
			$new_permissions = $this->db->safesql($this->gen_permissions($perm_data));

			if (!$this->db->query(
				"UPDATE `mcr_groups`
				SET `title`='$title', `color`='$color', `description`='$text', `permissions`='$new_permissions'
				WHERE `id`='$id'"
			)) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=groups&op=edit&id='.$id);
			}

			// Последнее обновление пользователя
			$this->db->update_user($this->user);
			// Лог действия
			$this->db->actlog($this->l10n->gettext('log_edit_grp')." #$id ".$this->l10n->gettext('log_grp'), $this->user->id);

			$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('grp_edit_success'), 3, '?mode=admin&do=groups');
		}

		$data = [
			"PAGE" => $this->l10n->gettext('grp_edit_page_name'),
			"TITLE" => $this->db->HSC($ar['title']),
			"COLOR" => $this->db->HSC($ar['color']),
			"TEXT" => $this->db->HSC($ar['description']),
			"PERMISSIONS" => $this->perm_list($ar['permissions']),
			"BUTTON" => $this->l10n->gettext('save')
		];

		return $this->core->sp(MCR_THEME_MOD."admin/groups/group-add.html", $data);
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
				$content = $this->group_list();
				break;
		}

		return $content;
	}
}