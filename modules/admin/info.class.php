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

		if (!$this->core->is_access('sys_adm_info')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'));
		}

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL,
			$this->l10n->gettext('info') => ADMIN_URL."&do=statics"
		];
		$this->core->bc = $this->core->gen_bc($bc);

		$this->core->header .= $this->core->sp(MCR_THEME_MOD."admin/info/header.phtml");
	}

	private function main()
	{
		$log_files = [];

		if (file_exists(MCR_ROOT . '/data/logs/')) {
			$log_files = scandir(MCR_ROOT . '/data/logs/');
			$log_files = array_splice($log_files, 2);
		}

//		$logs = "<ul id='logs-list'><li>" . implode('</li><li>', $log_files) . "</li></ul>";

		return $this->core->sp(MCR_THEME_MOD."admin/info/main.phtml", [
			"LOGS" => $log_files
		]);
	}

	private function users_stats()
	{
		$query = $this->db->query(
			"SELECT 
				`g`.`id`, `g`.`title`, COUNT(`u`.`id`) AS `count`
			FROM `mcr_groups` AS `g`
			
			LEFT JOIN `mcr_users` AS `u`
				ON `u`.`gid`=`g`.`id`
				
			GROUP BY `g`.`id`"
		);
		if (!$query || $this->db->num_rows($query) <= 0) {
			return null;
		}

		ob_start();

		while ($ar = $this->db->fetch_assoc($query)) {

			switch (intval($ar['id'])) {
				case 0:
					$class = 'error';
					break;
				case 1:
					$class = 'warning';
					break;
				case 2:
					$class = 'success';
					break;
				case 3:
					$class = 'info';
					break;

				default:
					$class = '';
					break;
			}

			$data = [
				"CLASS" => $class,
				"TITLE" => $this->db->HSC($ar['title']),
				"COUNT" => intval($ar['count'])
			];

			echo $this->core->sp(MCR_THEME_MOD."admin/info/group-id.html", $data);
		}

		return ob_get_clean();
	}

	private function stats()
	{
		$query = $this->db->query("
			SELECT COUNT(*) AS `users`,
				(SELECT COUNT(*) FROM `mcr_news`) AS `news`,
				(SELECT COUNT(*) FROM `mcr_news_cats`) AS `categories`,
				(SELECT COUNT(*) FROM `mcr_users_comments`) AS `comments`,
				(SELECT COUNT(*) FROM `mcr_statics`) AS `statics`,
				(SELECT COUNT(*) FROM `mcr_groups`) AS `groups`,
				(SELECT COUNT(*) FROM `mcr_news_views`) AS `views`,
				(SELECT COUNT(*) FROM `mcr_news_votes`) AS `votes`,
				(SELECT COUNT(*) FROM `mcr_permissions`) AS `permissions`
			FROM `{$this->cfg->tabname('users')}`
		");
		if (!$query || $this->db->num_rows($query) <= 0) {
			return null;
		}

		$ar = $this->db->fetch_assoc($query);

		$data = [
			"COUNT_USERS" => intval($ar['users']),
			"COUNT_GROUPS" => intval($ar['groups']),
			"COUNT_NEWS" => intval($ar['news']),
			"COUNT_COMMENTS" => intval($ar['comments']),
			"COUNT_CATEGORIES" => intval($ar['categories']),
			"COUNT_STATICS" => intval($ar['statics']),
			"COUNT_VIEWS" => intval($ar['views']),
			"COUNT_VOTES" => intval($ar['votes']),
			"COUNT_PERMISSIONS" => intval($ar['permissions']),
			"USERS_STATS" => $this->users_stats()
		];

		return $this->core->sp(MCR_THEME_MOD."admin/info/stats.phtml", $data);
	}

	private function extensions()
	{
		return $this->core->sp(MCR_THEME_MOD."admin/info/extensions.phtml");
	}

	private function update_center() {
		return $this->core->sp(MCR_THEME_MOD."admin/info/update_center.phtml");
	}

	public function content()
	{
		$op = (isset($_GET['op']))
			? $_GET['op']
			: 'list';

		switch ($op) {
			case 'stats':
				$content = $this->stats();
				break;
			case 'extensions':
				$content = $this->extensions();
				break;
			case 'update_center':
				$content = $this->update_center();
				break;

			default:
				$content = $this->main();
				break;
		}

		return $content;
	}
}