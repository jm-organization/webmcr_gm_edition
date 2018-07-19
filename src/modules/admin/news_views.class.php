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

		if (!$this->core->is_access('sys_adm_news_views')) {
			$this->core->notify($this->l10n->gettext('403'), $this->l10n->gettext('error_403'));
		}

		$bc = array(
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL,
			$this->l10n->gettext('views') => ADMIN_URL . "&do=news_views"
		);
		$this->core->bc = $this->core->gen_bc($bc);

		$this->core->header .= $this->core->sp(MCR_THEME_MOD . "admin/news_views/header.phtml");
	}

	public function content()
	{
		$op = (isset($_GET['op'])) ? $_GET['op'] : 'list';

		switch ($op) {
			case 'delete':
				$this->delete();
				break;

			default:
				$content = $this->views_list();
				break;
		}

		return $content;
	}

	private function delete()
	{
		if (!$this->core->is_access('sys_adm_news_views_delete')) {
			$this->core->notify($this->l10n->gettext("e_msg"), $this->l10n->gettext('e_403'), 2, '?mode=admin&do=news_views');
		}

		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			$this->core->notify($this->l10n->gettext("e_msg"), $this->l10n->gettext('e_hack'), 2, '?mode=admin&do=news_views');
		}

		$list = @$_POST['id'];

		if (empty($list)) {
			$this->core->notify($this->l10n->gettext("e_msg"), $this->l10n->gettext('nv_not_selected'), 2, '?mode=admin&do=news_views');
		}

		$list = $this->core->filter_int_array($list);

		$list = array_unique($list);

		$list = $this->db->safesql(implode(", ", $list));

		if (!$this->db->remove_fast("mcr_news_views", "id IN ($list)")) {
			$this->core->notify($this->l10n->gettext("e_msg"), $this->l10n->gettext("e_sql_critical"), 2, '?mode=admin&do=news_views');
		}

		$count1 = $this->db->affected_rows();

		// Последнее обновление пользователя
		$this->db->update_user($this->user);

		// Лог действия
		$this->db->actlog($this->l10n->gettext('log_del_nv') . " $list " . $this->l10n->gettext('log_nv'), $this->user->id);

		$this->core->notify($this->l10n->gettext("e_success"), sprintf($this->l10n->gettext('elements_deleted'), $count1), 3, '?mode=admin&do=news_views');

	}

	private function views_list()
	{

		$page = "?mode=admin&do=news_views";

		if (isset($_GET['sort']) && !empty($_GET['sort'])) {
			$page .= '&sort=' . $this->db->HSC(urlencode($_GET['sort']));
		}

		$query = $this->db->query("SELECT COUNT(*) FROM `mcr_news_views`");

		$ar = @$this->db->fetch_array($query);

		$data = array(
			"PAGINATION" => $this->core->pagination($this->cfg->pagin['adm_news_views'], $page . "&pid=", $ar[0]),
			"VIEWS" => $this->views_array()
		);

		return $this->core->sp(MCR_THEME_MOD . "admin/news_views/view-list.phtml", $data);
	}

	private function views_array()
	{

		$start = $this->core->pagination($this->cfg->pagin['adm_news_views'], 0, 0); // Set start pagination
		$end = $this->cfg->pagin['adm_news_views']; // Set end pagination

		$sort = "`v`.id";
		$sortby = "DESC";

		if (isset($_GET['sort']) && !empty($_GET['sort'])) {
			$expl = explode(' ', $_GET['sort']);

			$sortby = ($expl[0] == 'asc') ? "ASC" : "DESC";

			switch (@$expl[1]) {
				case 'news':
					$sort = "`n`.title";
					break;
				case 'user':
					$sort = "`u`.`login`";
					break;
				case 'date':
					$sort = "`v`.`time`";
					break;
			}
		}

		$query = $this->db->query("
			SELECT 
				`v`.id, `v`.nid, `v`.uid, `v`.`time`, 
				
				`n`.title,
				
				`u`.`login`, `g`.`color` AS `color`
			FROM `mcr_news_views` AS `v`
			
			LEFT JOIN `mcr_news` AS `n`
				ON `n`.id=`v`.nid
				
			LEFT JOIN `mcr_users` AS `u`
				ON `u`.`id`=`v`.uid
				
			LEFT JOIN `mcr_groups` AS `g`
				ON `g`.`id`=`u`.`gid`
				
			ORDER BY $sort $sortby
			
			LIMIT $start, $end
		");

		if (!$query || $this->db->num_rows($query) <= 0) {
			return $this->core->sp(MCR_THEME_MOD . "admin/news_views/view-none.phtml");
		}

		ob_start();

		while ($ar = $this->db->fetch_assoc($query)) {

			if (is_null($ar['title'])) {
				$status_class = 'error';
				$new = $this->l10n->gettext('nv_news_deleted');
			} else {
				$status_class = '';
				$new = $this->db->HSC($ar['title']);
			}

			$login = (is_null($ar['login'])) ? 'Пользователь удален' : $this->db->HSC($ar['login']);
			$color = $ar['color'];

			$page_data = array(
				"ID" => intval($ar['id']),
				"NID" => intval($ar['nid']),
				"NEW" => $new,
				"LOGIN" => $this->core->colorize($login, $color),
				"UID" => intval($ar['uid']),
				"TIME_CREATE" => date("d.m.Y в H:i", $ar['time']),
				"STATUS_CLASS" => $status_class
			);

			echo $this->core->sp(MCR_THEME_MOD . "admin/news_views/view-id.phtml", $page_data);
		}

		return ob_get_clean();
	}
}

?>