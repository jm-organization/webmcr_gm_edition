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

		if (!$this->core->is_access('sys_adm_monitoring')) {
			$this->core->notify($this->l10n->gettext('403'), $this->l10n->gettext('error_403'));
		}

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL,
			$this->l10n->gettext('monitoring') => ADMIN_URL."&do=monitoring"
		];

		$this->core->bc = $this->core->gen_bc($bc);

		$this->core->header .= $this->core->sp(MCR_THEME_MOD."admin/monitoring/header.phtml");
	}

	private function monitor_array()
	{

		$start = $this->core->pagination($this->cfg->pagin['adm_monitoring'], 0, 0); // Set start pagination
		$end = $this->cfg->pagin['adm_monitoring']; // Set end pagination

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
				case 'address':
					$sort = "CONCAT(`ip`, `port`)";
					break;
			}
		}

		$query = $this->db->query(
			"SELECT 
				id, title, ip, `port`
			FROM `mcr_monitoring`
			
			$where
			
			ORDER BY $sort $sortby
			
			LIMIT $start, $end"
		);

		if (!$query || $this->db->num_rows($query) <= 0) {
			return $this->core->sp(MCR_THEME_MOD."admin/monitoring/monitor-none.phtml");
		}

		ob_start();

		while ($ar = $this->db->fetch_assoc($query)) {

			$page_data = [
				"ID" => intval($ar['id']),
				"TITLE" => $this->db->HSC($ar['title']),
				"IP" => $this->db->HSC($ar['ip']),
				"PORT" => intval($ar['port'])
			];

			echo $this->core->sp(MCR_THEME_MOD."admin/monitoring/monitor-id.phtml", $page_data);
		}

		return ob_get_clean();
	}

	private function monitor_list()
	{
		$sql = "SELECT COUNT(*) FROM `mcr_monitoring`";
		$page = "?mode=admin&do=monitoring";

		if (isset($_GET['search']) && !empty($_GET['search'])) {
			$search = $this->db->safesql($_GET['search']);
			$sql = "SELECT COUNT(*) FROM `mcr_monitoring` WHERE title LIKE '%$search%'";
			$search = $this->db->HSC($_GET['search']);
			$page = "?mode=admin&do=monitoring&search=$search";
		}

		if (isset($_GET['sort']) && !empty($_GET['sort'])) {
			$page .= '&sort='.$this->db->HSC(urlencode($_GET['sort']));
		}

		$query = $this->db->query($sql);

		if ($query) {
			$ar = $this->db->fetch_array($query);

			$data = [
				"PAGINATION" => $this->core->pagination($this->cfg->pagin['adm_monitoring'], $page.'&pid=', $ar[0]),
				"SERVERS" => $this->monitor_array()
			];

			return $this->core->sp(MCR_THEME_MOD."admin/monitoring/monitor-list.phtml", $data);
		}

		exit("SQL Error");
	}

	private function delete()
	{
		if (!$this->core->is_access('sys_adm_monitoring_delete')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=monitoring');
		}

		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_hack'), 2, '?mode=admin&do=monitoring');
		}

		$list = @$_POST['id'];

		if (empty($list)) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('mon_not_selected'), 2, '?mode=admin&do=monitoring');
		}

		$list = $this->core->filter_int_array($list);
		$list = array_unique($list);
		$list = $this->db->safesql(implode(", ", $list));

		if (!$this->db->remove_fast("mcr_monitoring", "id IN ($list)")) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=monitoring');
		}

		$count = $this->db->affected_rows();

		// Последнее обновление пользователя
		$this->db->update_user($this->user);
		// Лог действия
		$this->db->actlog($this->l10n->gettext('log_del_mon')." $list ".$this->l10n->gettext('log_mon'), $this->user->id);

		$this->core->notify($this->l10n->gettext('error_success'), sprintf($this->l10n->gettext('elements_deleted'), $count), 3, '?mode=admin&do=monitoring');
	}

	private function add()
	{
		if (!$this->core->is_access('sys_adm_monitoring_add')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=monitoring');
		}

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL."",
			$this->l10n->gettext('monitoring') => ADMIN_URL."&do=monitoring",
			$this->l10n->gettext('mon_add') => ADMIN_URL."&do=monitoring&op=add",
		];
		$this->core->bc = $this->core->gen_bc($bc);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$title = $this->db->safesql(@$_POST['title']);
			$text = $this->db->safesql(@$_POST['text']);
			$ip = $this->db->safesql(@$_POST['ip']);
			$port = intval(@$_POST['port']);
			$updater = intval(@$_POST['cache']);
			$type = $this->db->safesql(@$_POST['type']);

			if (!file_exists(MCR_MON_PATH.$type.'.php')) {
				$type = 'MineToolsAPIPing';
			}

			if (!$this->db->query(
				"INSERT INTO `mcr_monitoring` (title, `text`, ip, `port`, `players`, `motd`, `plugins`, last_error, `type`, updater)
				VALUES ('$title', '$text', '$ip', '$port', '', '', '', '', '$type', '$updater')"
			)) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=monitoring');
			}

			$id = $this->db->insert_id();

			// Последнее обновление пользователя
			$this->db->update_user($this->user);
			// Лог действия
			$this->db->actlog($this->l10n->gettext('log_add_mon')." #$id ".$this->l10n->gettext('log_mon'), $this->user->id);

			$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('mon_add_success'), 3, '?mode=admin&do=monitoring');
		}

		$data = [
			"PAGE" => $this->l10n->gettext('mon_add_page_name'),
			"TITLE" => "",
			"TEXT" => "",
			"IP" => "127.0.0.1",
			"PORT" => "25565",
			"TYPES" => $this->types(),
			"CACHE" => 60,
			"ERROR" => "",
			"BUTTON" => $this->l10n->gettext('save')
		];

		return $this->core->sp(MCR_THEME_MOD."admin/monitoring/monitor-form.phtml", $data);
	}

	private function edit()
	{
		if (!$this->core->is_access('sys_adm_monitoring_edit')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=monitoring');
		}

		$id = intval($_GET['id']);

		$query = $this->db->query(
			"SELECT 
				title, `text`, ip, `port`, 
				last_error, updater, last_error, `type`
			FROM `mcr_monitoring`
			
			WHERE id='$id'"
		);

		if (!$query || $this->db->num_rows($query) <= 0) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=monitoring');
		}

		$ar = $this->db->fetch_assoc($query);

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL."",
			$this->l10n->gettext('monitoring') => ADMIN_URL."&do=monitoring",
			$this->l10n->gettext('mon_edit') => ADMIN_URL."&do=monitoring&op=edit&id=$id",
		];
		$this->core->bc = $this->core->gen_bc($bc);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$title = $this->db->safesql(@$_POST['title']);
			$text = $this->db->safesql(@$_POST['text']);
			$ip = $this->db->safesql(@$_POST['ip']);
			$port = intval(@$_POST['port']);
			$updater = intval(@$_POST['cache']);
			$type = $this->db->safesql(@$_POST['type']);

			if (!file_exists(MCR_MON_PATH.$type.'.php')) {
				$type = 'MineToolsAPIPing';
			}

			if (!$this->db->query(
				"UPDATE `mcr_monitoring`
				SET title='$title', `text`='$text', ip='$ip', `port`='$port', `type`='$type', updater='$updater'
				WHERE id='$id'"
			)) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=monitoring&op=edit&id='.$id);
			}

			// Последнее обновление пользователя
			$this->db->update_user($this->user);
			// Лог действия
			$this->db->actlog($this->l10n->gettext('log_edit_mon')." #$id ".$this->l10n->gettext('log_mon'), $this->user->id);

			$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('mon_edit_success'), 3, '?mode=admin&do=monitoring');
		}

		$data = [
			"PAGE" => $this->l10n->gettext('mon_edit_page_name'),
			"TITLE" => $this->db->HSC($ar['title']),
			"TEXT" => $this->db->HSC($ar['text']),
			"IP" => $this->db->HSC($ar['ip']),
			"PORT" => intval($ar['port']),
			"CACHE" => intval($ar['updater']),
			"TYPES" => $this->types($ar['type']),
			"ERROR" => $this->db->HSC($ar['last_error']),
			"BUTTON" => $this->l10n->gettext('save')
		];

		return $this->core->sp(MCR_THEME_MOD."admin/monitoring/monitor-form.phtml", $data);
	}

	private function types($selected = '')
	{
		$list = scandir(MCR_MON_PATH);

		if (empty($list)) {
			return false;
		}

		ob_start();

		foreach ($list as $key => $file) {
			$name = substr($file, 0, -4);

			if ($file == '.' || $file == '..' || substr($file, -4) != '.php') {
				continue;
			}

			$select = ($selected == $name) ? 'selected' : '';

			echo '<option value="'.$name.'" '.$select.'>'.$name.'</option>';
		}

		return ob_get_clean();
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
				$content = $this->monitor_list();
				break;
		}

		return $content;
	}
}

?>