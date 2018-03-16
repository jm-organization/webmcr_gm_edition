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

		if (!$this->core->is_access('sys_adm_logs')) {
			$this->core->notify($this->l10n->gettext('403'), $this->l10n->gettext('e_403'));
		}

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL,
			$this->l10n->gettext('logs') => ADMIN_URL."&do=logs"
		];

		$this->core->bc = $this->core->gen_bc($bc);

		$this->core->header .= $this->core->sp(MCR_THEME_MOD."admin/logs/header.html");
	}

	private function logs_array()
	{
		$start = $this->core->pagination($this->cfg->pagin['adm_logs'], 0, 0); // Set start pagination
		$end = $this->cfg->pagin['adm_logs']; // Set end pagination
		$where = "";
		$sort = "`l`.`id`";
		$sortby = "DESC";

		if (isset($_GET['search']) && !empty($_GET['search'])) {
			$search = $this->db->safesql(urldecode($_GET['search']));
			$where = "WHERE `l`.`message` LIKE '%$search%'";
		}

		if (isset($_GET['sort']) && !empty($_GET['sort'])) {
			$expl = explode(' ', $_GET['sort']);

			$sortby = ($expl[0] == 'asc')
				? "ASC"
				: "DESC";

			switch (@$expl[1]) {
				case 'user':
					$sort = "`u`.`login`";
					break;
				case 'msg':
					$sort = "`l`.`message`";
					break;
				case 'date':
					$sort = "`l`.`date`";
					break;
			}
		}

		$query = $this->db->query(
			"SELECT 
				`l`.`id`, `l`.`uid`, `l`.`message`, 
				`l`.`date`,
				
				`u`.`login`, 
				
				`g`.`color` AS `gcolor`
			FROM `mcr_logs` AS `l`
			
			LEFT JOIN `mcr_users` AS `u`
				ON `u`.`id`=`l`.`uid`
				
			LEFT JOIN `mcr_groups` AS `g`
				ON `g`.`id`=`u`.`id`
				
			$where
			
			ORDER BY $sort $sortby
			
			LIMIT $start, $end"
		);

		if (!$query || $this->db->num_rows($query) <= 0) {
			return $this->core->sp(MCR_THEME_MOD."admin/logs/log-none.html");
		}

		ob_start();

		while ($ar = $this->db->fetch_assoc($query)) {
			$color = $this->db->HSC($ar['gcolor']);
			$login = (!is_null($ar['login']))
				? $this->db->HSC($ar['login'])
				: $this->l10n->gettext('guest');

			$page_data = [
				"ID" => intval($ar['id']),
				"UID" => intval($ar['uid']),
				"MESSAGE" => $this->db->HSC($ar['message']),
				"DATE" => date("d.m.Y в H:i:s", $ar['date']),
				"LOGIN" => $this->core->colorize($login, $color),
			];

			echo $this->core->sp(MCR_THEME_MOD."admin/logs/log-id.html", $page_data);
		}

		return ob_get_clean();
	}

	public function content()
	{
		$sql = "SELECT COUNT(*) FROM `mcr_logs`";
		$page = "?mode=admin&do=logs";

		if (isset($_GET['search']) && !empty($_GET['search'])) {
			$search = $this->db->safesql(urldecode($_GET['search']));
			$sql = "SELECT COUNT(*) FROM `mcr_logs` WHERE `message` LIKE '%$search%'";
			$search = $this->db->HSC(urldecode($_GET['search']));
			$page = "?mode=admin&do=logs&search=$search";
		}

		if (isset($_GET['sort']) && !empty($_GET['sort'])) {
			$page .= '&sort='.$this->db->HSC(urlencode($_GET['sort']));
		}

		$query = $this->db->query($sql);
		$ar = @$this->db->fetch_array($query);

		return $this->core->sp(MCR_THEME_MOD."admin/logs/log-list.html", [
			"PAGINATION" => $this->core->pagination($this->cfg->pagin['adm_logs'], $page.'&pid=', $ar[0]),
			"LOGS" => $this->logs_array()
		]);
	}
}