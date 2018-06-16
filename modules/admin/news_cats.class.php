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

		if (!$this->core->is_access('sys_adm_news_cats')) {
			$this->core->notify($this->l10n->gettext('403'), $this->l10n->gettext('error_403'));;
		}

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL,
			$this->l10n->gettext('categories') => ADMIN_URL."&do=news_cats"
		];

		$this->core->bc = $this->core->gen_bc($bc);

		$this->core->header .= $this->core->sp(MCR_THEME_MOD."admin/news_cats/header.html");
	}

	private function cats_array()
	{
		$start = $this->core->pagination($this->cfg->pagin['adm_news_cats'], 0, 0); // Set start pagination
		$end = $this->cfg->pagin['adm_news_cats']; // Set end pagination

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

			if (@$expl[1] == 'title') {
				$sort = "title";
			}
		}

		$query = $this->db->query(
			"SELECT
				id, title, `data`
			FROM `mcr_news_cats`
			
			$where
			
			ORDER BY $sort $sortby
			
			LIMIT $start, $end"
		);

		if (!$query || $this->db->num_rows($query) <= 0) {
			return $this->core->sp(MCR_THEME_MOD."admin/news_cats/cat-none.html");
		}

		ob_start();

		while ($ar = $this->db->fetch_assoc($query)) {

			$page_data = [
				"ID" => intval($ar['id']),
				"TITLE" => $this->db->HSC($ar['title']),
				"DATA" => json_decode($ar['data'])
			];

			echo $this->core->sp(MCR_THEME_MOD."admin/news_cats/cat-id.html", $page_data);
		}

		return ob_get_clean();
	}

	private function cats_list()
	{
		$sql = "SELECT COUNT(*) FROM `mcr_news_cats`";
		$page = "?mode=admin&do=news_cats";

		if (isset($_GET['search']) && !empty($_GET['search'])) {
			$search = $this->db->safesql($_GET['search']);
			$sql = "SELECT COUNT(*) FROM `mcr_news_cats` WHERE title LIKE '%$search%'";
			$search = $this->db->HSC($_GET['search']);
			$page .= "&search=$search";
		}

		if (isset($_GET['sort']) && !empty($_GET['sort'])) {
			$page .= '&sort='.$this->db->HSC(urlencode($_GET['sort']));
		}

		$query = $this->db->query($sql);

		if ($query) {
			$ar = $this->db->fetch_array($query);

			$data = [
				"PAGINATION" => $this->core->pagination($this->cfg->pagin['adm_news_cats'], $page.'&pid=', $ar[0]),
				"CATEGORIES" => $this->cats_array()
			];

			return $this->core->sp(MCR_THEME_MOD."admin/news_cats/cat-list.html", $data);
		}

		exit("SQL Error");
	}

	private function delete()
	{
		if (!$this->core->is_access('sys_adm_news_cats_delete')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=news_cats');
		}

		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_hack'), 2, '?mode=admin&do=news_cats');
		}

		$list = @$_POST['id'];

		if (empty($list)) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('cn_not_selected'), 2, '?mode=admin&do=news_cats');
		}

		$list = $this->core->filter_int_array($list);
		$list = array_unique($list);
		$list = $this->db->safesql(implode(", ", $list));

		if (!$this->db->remove_fast("mcr_news_cats", "id IN ($list)")) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=news_cats');
		}

		$count = $this->db->affected_rows();

		// Последнее обновление пользователя
		$this->db->update_user($this->user);
		// Лог действия
		$this->db->actlog($this->l10n->gettext('log_del_cn')." $list ".$this->l10n->gettext('log_cn'), $this->user->id);

		$this->core->notify($this->l10n->gettext('error_success'), sprintf($this->l10n->gettext('elements_deleted'), $count), 3, '?mode=admin&do=news_cats');
	}

	private function add()
	{
		if (!$this->core->is_access('sys_adm_news_cats_add')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=news_cats');
		}

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL."",
			$this->l10n->gettext('categories') => ADMIN_URL."&do=news_cats",
			$this->l10n->gettext('cn_add') => ADMIN_URL."&do=news_cats&op=add",
		];
		$this->core->bc = $this->core->gen_bc($bc);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$title = $this->db->safesql(@$_POST['title']);
			$text = $this->db->safesql(@$_POST['text']);

			$new_data = [
				"time_create" => time(),
				"time_last" => time(),
				"user" => $this->user->login
			];

			$new_data = $this->db->safesql(json_encode($new_data));

			$insert = $this->db->query("INSERT INTO `mcr_news_cats`
											(title, description, `data`)
										VALUES
											('$title', '$text', '$new_data')");
			if (!$insert) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=news_cats');
			}

			$id = $this->db->insert_id();

			// Последнее обновление пользователя
			$this->db->update_user($this->user);

			// Лог действия
			$this->db->actlog($this->l10n->gettext('log_add_cn')." #$id ".$this->l10n->gettext('log_cn'), $this->user->id);

			$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('cn_add_success'), 3, '?mode=admin&do=news_cats');
		}

		$data = [
			"PAGE" => $this->l10n->gettext('cn_add_page_name'),
			"TITLE" => "",
			"TEXT" => "",
			"BUTTON" => $this->l10n->gettext('save')
		];

		return $this->core->sp(MCR_THEME_MOD."admin/news_cats/cat-add.html", $data);
	}

	private function edit()
	{
		if (!$this->core->is_access('sys_adm_news_cats_edit')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=news_cats');
		}

		$id = intval($_GET['id']);

		$query = $this->db->query("SELECT title, description, `data`
									FROM `mcr_news_cats`
									WHERE id='$id'");

		if (!$query || $this->db->num_rows($query) <= 0) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=news_cats');
		}

		$ar = $this->db->fetch_assoc($query);

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL."",
			$this->l10n->gettext('categories') => ADMIN_URL."&do=news_cats",
			$this->l10n->gettext('cn_edit') => ADMIN_URL."&do=news_cats&op=edit&id=$id",
		];

		$this->core->bc = $this->core->gen_bc($bc);

		$data = json_decode($ar['data']);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$title = $this->db->safesql(@$_POST['title']);
			$text = $this->db->safesql(@$_POST['text']);

			$new_data = [
				"time_create" => $data->time_create,
				"time_last" => time(),
				"user" => $data->user
			];

			$new_data = $this->db->safesql(json_encode($new_data));

			$update = $this->db->query("UPDATE `mcr_news_cats`
										SET title='$title', description='$text', `data`='$new_data'
										WHERE id='$id'");

			if (!$update) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=news_cats&op=edit&id='.$id);
			}

			// Последнее обновление пользователя
			$this->db->update_user($this->user);

			// Лог действия
			$this->db->actlog($this->l10n->gettext('log_edit_cn')." #$id ".$this->l10n->gettext('log_cn'), $this->user->id);

			$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('cn_edit_success'), 3, '?mode=admin&do=news_cats&op=edit&id='.$id);
		}

		$data = [
			"PAGE" => $this->l10n->gettext('cn_edit_page_name'),
			"TITLE" => $this->db->HSC($ar['title']),
			"TEXT" => $this->db->HSC($ar['description']),
			"BUTTON" => $this->l10n->gettext('save')
		];

		return $this->core->sp(MCR_THEME_MOD."admin/news_cats/cat-add.html", $data);
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
				$content = $this->cats_list();
				break;
		}

		return $content;
	}
}

?>