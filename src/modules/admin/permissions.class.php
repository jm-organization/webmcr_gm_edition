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

		if (!$this->core->is_access('sys_adm_permissions')) {
			$this->core->notify($this->l10n->gettext('403'), $this->l10n->gettext('error_403'));
		}

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL,
			$this->l10n->gettext('permissions') => ADMIN_URL . "&do=permissions"
		];
		$this->core->bc = $this->core->gen_bc($bc);

		$this->core->header .= $this->core->sp(MCR_THEME_MOD . "admin/permissions/header.phtml");
	}

	public function content()
	{
		$op = (isset($_GET['op'])) ? $_GET['op'] : 'list';

		$this->core->header .= '<script src="' . LANG_URL . 'js/modules/permissions.js"></script>';
		$this->core->header .= '<script src="' . STYLE_URL . 'js/modules/admin/permissions.js"></script>';

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
				$content = $this->permissions_list();
				break;
		}

		return $content;
	}

	private function add()
	{
		if (!$this->core->is_access('sys_adm_permissions_add')) {
			$this->core->notify($this->l10n->gettext('error_msg'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=permissions');
		}

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL . "",
			$this->l10n->gettext('permissions') => ADMIN_URL . "&do=permissions",
			$this->l10n->gettext('perm_add') => ADMIN_URL . "&do=permissions&op=add",
		];

		$this->core->bc = $this->core->gen_bc($bc);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$title = $this->db->safesql(@$_POST['title']);
			$text = $this->db->safesql(@$_POST['text']);
			$value = $this->db->safesql(@$_POST['value']);

			$filter_type = $this->filter_type(@$_POST['type'], @$_POST['default']);

			$default = $filter_type['default'];
			$type = $filter_type['type'];

			$new_data = [
				"time_create" => time(),
				"time_last" => time(),
				"login_create" => $this->user->login,
				"login_last" => $this->user->login,
			];

			$new_data = $this->db->safesql(json_encode($new_data));

			if (!$this->db->query(
				"INSERT INTO `mcr_permissions` (title, `description`, `value`, `default`, `type`, `data`)
				VALUES ('$title', '$text', '$value', '$default', '$type', '$new_data')"
			)
			) {
				$this->core->notify($this->l10n->gettext('error_msg'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=permissions');
			}

			$id = $this->db->insert_id();

			if (!$this->update_groups()) {
				$this->core->notify($this->l10n->gettext('error_msg'), $this->l10n->gettext('error_sql_critical') . ' #2', 2, '?mode=admin&do=permissions');
			}

			@$this->user->update_default_permissions();

			// Лог действия
			$this->db->actlog($this->l10n->gettext('log_add_perm') . " #$id " . $this->l10n->gettext('log_perm'), $this->user->id);

			$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('perm_add_success'), 3, '?mode=admin&do=permissions');
		}

		$data = [
			"PAGE" => $this->l10n->gettext('perm_add_page_name'),
			"TITLE" => '',
			"TEXT" => '',
			"VALUE" => '',
			"DEFAULT" => $this->get_default_value(),
			"TYPES" => $this->get_types(),
			"BUTTON" => $this->l10n->gettext('save')
		];

		return $this->core->sp(MCR_THEME_MOD . "admin/permissions/perm-form.phtml", $data);
	}

	private function filter_type($type = 'boolean', $default = 'false')
	{
		switch ($type) {
			case 'integer':
				$default = intval($default);
				break;

			case 'float':
				$default = floatval($default);
				break;

			case 'string':
				$default = $this->db->safesql($default);
				break;

			default:
				$type = 'boolean';
				$default = ($default == 'true') ? 'true' : 'false';

				break;
		}

		return [
			"type" => $type,
			"default" => $default
		];
	}

	private function update_groups()
	{
		$def_perm = $this->get_permissions();

		$query = $this->db->query("SELECT `id`, `permissions` FROM `mcr_groups`");
		if (!$query || $this->db->num_rows($query) <= 0) {
			$this->core->notify($this->l10n->gettext('error_msg'), $this->l10n->gettext('error_sql_critical'));
		}

		$return = true;

		while ($ar = $this->db->fetch_assoc($query)) {
			$group_perm = @json_decode($ar['permissions'], true);
			$id = intval($ar['id']);

			$new_perm = [];
			foreach ($def_perm as $key => $perm) {
				$new_perm[$perm['value']] = $this->switch_loop($group_perm, $perm);
			}

			$new_perm = $this->db->safesql(json_encode($new_perm));

			if (!$this->db->obj->query(
				"UPDATE `mcr_groups`
				SET `permissions`='$new_perm'
				WHERE `id`='$id'"
			)
			) {
				$return = false;
			}
		}

		// Последнее обновление пользователя
		$this->db->update_user($this->user);

		return $return;
	}

	private function get_permissions()
	{
		$query = $this->db->query("SELECT `value`, `type`, `default` FROM `mcr_permissions`");

		if (!$query || $this->db->num_rows($query) <= 0) {
			$this->core->notify($this->l10n->gettext('error_msg'), $this->l10n->gettext('error_sql_critical'));
		}

		$array = [];

		while ($ar = $this->db->fetch_assoc($query)) {
			$array[] = [
				"type" => $ar['type'],
				"value" => $ar['value'],
				"default" => $ar['default'],
			];
		}

		return $array;
	}

	private function switch_loop($group_perm, $perm)
	{
		switch ($perm['type']) {
			case 'integer':
				$new_perm[$perm['value']] = (isset($group_perm[$perm['value']])) ? intval($group_perm[$perm['value']]) : intval($perm['default']);
				break;

			case 'float':
				$new_perm[$perm['value']] = (isset($group_perm[$perm['value']])) ? floatval($group_perm[$perm['value']]) : floatval($perm['default']);
				break;

			case 'string':
				$new_perm[$perm['value']] = (isset($group_perm[$perm['value']])) ? $this->db->safesql($group_perm[$perm['value']]) : $this->db->safesql($perm['default']);
				break;

			default:
				if (isset($group_perm[$perm['value']])) {
					$new_perm[$perm['value']] = ($group_perm[$perm['value']] == 'true' || $group_perm[$perm['value']] == 'false') ? $group_perm[$perm['value']] : $perm['default'];
					$new_perm[$perm['value']] = ($new_perm[$perm['value']] == 'true') ? true : false;
				} else {
					$new_perm[$perm['value']] = ($perm['default'] == 'true') ? true : false;
				}
				break;
		}

		return $new_perm[$perm['value']];
	}

	private function get_default_value($value = 'false', $type = 'boolean')
	{
		switch ($type) {
			case 'integer':
				$data['VALUE'] = intval($value);
				$input = $this->core->sp(MCR_THEME_MOD . "admin/permissions/perm-id-integer.phtml", $data);
				break;

			case 'float':
				$data['VALUE'] = floatval($value);
				$input = $this->core->sp(MCR_THEME_MOD . "admin/permissions/perm-id-float.phtml", $data);
				break;

			case 'string':
				$data['VALUE'] = $this->db->HSC($value);
				$input = $this->core->sp(MCR_THEME_MOD . "admin/permissions/perm-id-string.phtml", $data);
				break;

			default:
				$data['VALUE'] = ($value == 'true') ? 'selected' : '';
				$input = $this->core->sp(MCR_THEME_MOD . "admin/permissions/perm-id-boolean.phtml", $data);
				break;
		}

		return $input;
	}

	private function get_types($selected = 'boolean', $check = false)
	{
		$array = [
			"boolean" => $this->l10n->gettext('perm_boolean'),
			"integer" => $this->l10n->gettext('perm_integer'),
			"float" => $this->l10n->gettext('perm_float'),
			"string" => $this->l10n->gettext('perm_string'),
		];

		if ($check) {
			return (isset($array[$selected])) ? true : false;
		}

		ob_start();

		foreach ($array as $value => $title) {
			$select = ($selected == $value) ? 'selected' : '';

			echo "<option value=\"$value\" $select>$title</option>";
		}

		return ob_get_clean();
	}

	private function edit()
	{
		if (!$this->core->is_access('sys_adm_permissions_edit')) {
			$this->core->notify($this->l10n->gettext('error_msg'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=permissions');
		}

		$id = intval($_GET['id']);

		$query = $this->db->query("
			SELECT 
				title, description, `value`, 
				`system`, `default`, `type`, 
				`data`
			FROM `mcr_permissions`
			
			WHERE id='$id'
		");

		if (!$query || $this->db->num_rows($query) <= 0) {
			$this->core->notify($this->l10n->gettext('error_msg'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=permissions');
		}

		$ar = $this->db->fetch_assoc($query);

		$bc = [
			$this->l10n->gettext('mod_name') => ADMIN_URL . "",
			$this->l10n->gettext('permissions') => ADMIN_URL . "&do=permissions",
			$this->l10n->gettext('perm_edit') => ADMIN_URL . "&do=permissions&op=edit&id=$id",
		];

		$this->core->bc = $this->core->gen_bc($bc);

		$data = json_decode($ar['data']);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$title = $this->db->safesql(@$_POST['title']);
			$text = $this->db->safesql(@$_POST['text']);
			$value = $this->db->safesql(@$_POST['value']);

			$filter_type = $this->filter_type(@$_POST['type'], @$_POST['default']);
			$default = $filter_type['default'];
			$type = $filter_type['type'];

			if (intval($ar['system']) === 1 && ($type != $ar['type'] || $value != $ar['value'])) {
				$this->core->notify($this->l10n->gettext('error_msg'), $this->l10n->gettext('perm_change_system'), 2, '?mode=admin&do=permissions&op=edit' . $id);
			}

			$new_data = [
				"time_create" => $data->time_create,
				"time_last" => time(),
				"login_create" => $data->login_create,
				"login_last" => $this->user->login,
			];

			$new_data = $this->db->safesql(json_encode($new_data));

			if (!$this->db->query(
				"UPDATE `mcr_permissions`
				SET 
					title='$title', 
					description='$text', 
					`value`='$value',
					`default`='$default', 
					`type`='$type', 
					`data`='$new_data'
				WHERE id='$id'"
			)
			) {
				$this->core->notify($this->l10n->gettext('error_msg'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=permissions&op=edit&id=' . $id);
			}

			if (!$this->update_groups()) {
				$this->core->notify($this->l10n->gettext('error_msg'), $this->l10n->gettext('error_sql_critical') . ' #2', 2, '?mode=admin&do=permissions&op=edit&id=' . $id);
			}

			@$this->user->update_default_permissions();

			// Лог действия
			$this->db->actlog($this->l10n->gettext('log_edit_perm') . " #$id " . $this->l10n->gettext('log_perm'), $this->user->id);

			$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('perm_edit_success'), 3, '?mode=admin&do=permissions');
		}

		$data = [
			"PAGE" => $this->l10n->gettext('perm_edit_page_name'),
			"TITLE" => $this->db->HSC($ar['title']),
			"TEXT" => $this->db->HSC($ar['description']),
			"VALUE" => $this->db->HSC($ar['value']),
			"DEFAULT" => $this->get_default_value($ar['default'], $ar['type']),
			"TYPES" => $this->get_types($ar['type']),
			"BUTTON" => $this->l10n->gettext('save')
		];

		return $this->core->sp(MCR_THEME_MOD . "admin/permissions/perm-form.phtml", $data);
	}

	private function delete()
	{
		if (!$this->core->is_access('sys_adm_permissions_delete')) {
			$this->core->notify($this->l10n->gettext('error_msg'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=permissions');
		}

		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			$this->core->notify($this->l10n->gettext('error_msg'), $this->l10n->gettext('error_hack'), 2, '?mode=admin&do=permissions');
		}

		$list = @$_POST['id'];

		if (empty($list)) {
			$this->core->notify($this->l10n->gettext('error_msg'), $this->l10n->gettext('perm_not_selected'), 2, '?mode=admin&do=permissions');
		}

		$list = $this->core->filter_int_array($list);

		$list = array_unique($list);

		$list = $this->db->safesql(implode(", ", $list));

		if (!$this->db->remove_fast("mcr_permissions", "id IN ($list) AND `system`='0'")) {
			$this->core->notify($this->l10n->gettext('error_msg'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=permissions');
		}

		$count = $this->db->affected_rows();

		@$this->user->update_default_permissions();

		// Последнее обновление пользователя
		$this->db->update_user($this->user);

		// Лог действия
		$this->db->actlog($this->l10n->gettext('log_del_perm') . " $list " . $this->l10n->gettext('log_perm'), $this->user->id);

		$this->core->notify($this->l10n->gettext('error_success'), sprintf($this->l10n->gettext('elements_deleted'), $count), 3, '?mode=admin&do=permissions');
	}

	private function permissions_list()
	{
		$sql = "SELECT COUNT(*) FROM `mcr_permissions`";
		$page = "?mode=admin&do=permissions";

		if (isset($_GET['search']) && !empty($_GET['search'])) {
			$search = $this->db->safesql($_GET['search']);
			$sql = "SELECT COUNT(*) FROM `mcr_permissions` WHERE title LIKE '%$search%'";
			$search = $this->db->HSC($_GET['search']);
			$page = "?mode=admin&do=permissions&search=$search";
		}

		if (isset($_GET['sort']) && !empty($_GET['sort'])) {
			$page .= '&sort=' . $this->db->HSC(urlencode($_GET['sort']));
		}

		$query = $this->db->query($sql);

		if (!$query) {
			exit("SQL Error");
		}

		$ar = $this->db->fetch_array($query);

		$data = [
			"PAGINATION" => $this->core->pagination($this->cfg->pagin['adm_groups'], $page . '&pid=', $ar[0]),
			"PERMISSIONS" => $this->permissions_array()
		];

		return $this->core->sp(MCR_THEME_MOD . "admin/permissions/perm-list.phtml", $data);
	}

	private function permissions_array()
	{
		$start = $this->core->pagination($this->cfg->pagin['adm_groups'], 0, 0); // Set start pagination
		$end = $this->cfg->pagin['adm_groups']; // Set end pagination

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
				case 'value':
					$sort = "`value`";
					break;
			}
		}

		$query = $this->db->query("
			SELECT 
				id, title, description, 
				`value`, `system`, `data`
			FROM `mcr_permissions`
			
			$where
			
			ORDER BY $sort $sortby
			
			LIMIT $start, $end
		");

		if (!$query || $this->db->num_rows($query) <= 0) {
			return $this->core->sp(MCR_THEME_MOD . "admin/permissions/perm-none.phtml");
		}

		ob_start();

		while ($ar = $this->db->fetch_assoc($query)) {

			$data = json_decode($ar['data'], true);

			$page_data = [
				"ID" => intval($ar['id']),
				"TITLE" => $this->db->HSC($ar['title']),
				"TEXT" => $this->db->HSC($ar['description']),
				"VALUE" => $this->db->HSC($ar['value']),
				"SYSTEM" => (intval($ar['system']) === 1) ? $this->core->sp(MCR_THEME_MOD . "admin/permissions/perm-system.phtml") : '',
				"DATA" => $data
			];

			echo $this->core->sp(MCR_THEME_MOD . "admin/permissions/perm-id.phtml", $page_data);
		}

		return ob_get_clean();
	}
}