<?php

namespace mcr\installer\modules;


if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class reinstall extends install_step
{
	public function content()
	{
		global $configs;

		$this->title = $this->lng['mod_name'] . ' — ' . $this->lng['reinstall'];
		
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			if (intval(@$_POST['type']) != 1) {
				$this->notify();
			}

			$tables = array(
				'mcr_users_comments', 'mcr_l10n_phrases', 'mcr_l10n_languages', 'mcr_news_comments', 'mcr_logs_of_edit',
				'mcr_files', 'mcr_iconomy', 'mcr_logs', 'mcr_menu', 'mcr_menu_adm', 'mcr_menu_adm_icons',
				'mcr_monitoring', 'mcr_news_views', 'mcr_news_votes', 'mcr_online', 'mcr_permissions', 'mcr_statics',
				'mcr_news', 'mcr_users', 'mcr_news_cats', 'mcr_menu_adm_groups', 'mcr_groups'
			);

			$db = new \mysqli(config('db::host'), config('db::user'), config('db::pass'), config('db::base'), config('db::port'));
			$error = $db->connect_error;

			if (empty($error)) {
				$tables = '`' . implode('`, `', $tables) . '`';
				if (!$db->query("DROP TABLE IF EXISTS $tables")) {
					$this->notify($this->lng['e_sql'] . ' | ' . $error, $this->lng['e_msg'], 'install/?do=reinstall');
				}

				$_main = config('main');
				$_main['install'] = false;
				$_main['debug'] = true;

				$configs->savecfg($_main, 'main.php', 'main');

				$_db = config('db');
				$_db['host'] = '127.0.0.1';
				$_db['user'] = 'root';
				$_db['pass'] = '';
				$_db['base'] = 'database';
				$_db['port'] = 3306;

				session_destroy();

				$configs->savecfg($_db, 'db.php', 'db');
			}

			$this->notify('', '', 'install/');

		}

		return $this->sp('reinstall.phtml');
	}

}