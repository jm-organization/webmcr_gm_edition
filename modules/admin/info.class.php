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
		$this->core->header .= $this->core->sp(MCR_THEME_MOD."admin/info/header-log-viewer.phtml");

		$log_files = [];

		if (file_exists(MCR_ROOT . '/data/logs/')) {
			$log_files = scandir(MCR_ROOT . '/data/logs/');

			$log_files = array_flip($log_files);
			unset($log_files['.']);
			unset($log_files['..']);
			unset($log_files['.gitignore']);
			unset($log_files['.htaccess']);
			$log_files = array_flip($log_files);
		}

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

			echo $this->core->sp(MCR_THEME_MOD."admin/info/group-id.phtml", $data);
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
		$this->core->header .= $this->core->sp(MCR_THEME_MOD."admin/info/header-update-center.phtml");

		return $this->core->sp(MCR_THEME_MOD."admin/info/update_center.phtml");
	}

	private function delete() {
		if (file_exists(MCR_ROOT . '/data/logs/' . $_GET['file'])) {
			unlink(MCR_ROOT . '/data/logs/' . $_GET['file']);

			$this->core->notify(
				$this->l10n->gettext('error_success'),
				sprintf($this->l10n->gettext('elements_deleted'), 1),
				3,
				'?mode=admin&do=info&op=main'
			);
		} else {
			$this->core->notify(
				$this->l10n->gettext('fm_file_not_found '),
				$this->l10n->gettext('fm_not_found_error '),
				3,
				'?mode=admin&do=info&op=main'
			);
		}
	}

	private function download() {
		if (file_exists(MCR_ROOT . '/data/logs/' . $_GET['file'])) {
			$file_content = file_get_contents(MCR_ROOT . '/data/logs/' . $_GET['file']);

			header('Content-Description: File Transfer');
			header('Content-Type: application/json');
			header('Content-Disposition: attachment; filename='.$_GET['file']);
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . strlen($file_content));

			echo $file_content;

			exit;
		} else {
			$this->core->notify(
				 $this->l10n->gettext('fm_file_not_found '),
				 $this->l10n->gettext('fm_not_found_error '),
				3,
				'?mode=admin&do=info&op=main'
			);
		}
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
			case 'delete':
				$content = ''; $this->delete();
				break;
			case 'download':
				$content =''; $this->download();
				break;

			default:
				$content = $this->main();
				break;
		}

		return $content;
	}
}