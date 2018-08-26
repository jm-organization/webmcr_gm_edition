<?php

namespace mcr\installer\modules;


use mcr\cache\cache;
use mcr\cache\cache_exception;
use mcr\cache\cache_value;
use mcr\core\configs\configs_application_provider;
use mcr\installer\install;
use function mcr\installer\installer;

if (!defined("MCR")) exit("Hacking Attempt!");

class step_3 extends install_step
{
	public function save_settings()
	{
		$connection = new \mysqli(config('db::host'), config('db::username'), config('db::passwd'), config('db::basename'), config('db::port'));

		$default_configs = configs_application_provider::get_default_configs();
		$request = installer('request');

		$default_configs['mcr']['site.name'] = $request->site_name;
		$default_configs['mcr']['site.description'] = $request->site_about;
		$default_configs['mcr']['site.keywords'] = $request->site_keywords;
		$default_configs['mcr']['site.base_full_url'] = $request->site_url;

		self::save_configs($default_configs, $connection);

		self::make_news($connection);
		self::add_phrases_to_language($connection);

		$application_key = '$Installed-in:'.time().'_'.str_random(128);
		file_put_contents(MCR_ROOT.'src/mcr/.installed', $application_key);

		install::remember_step('step_3');
		install::to_next_step();
	}

	public function settings_form()
	{
		install::$page_title = translate('mod_name').' — '.translate('step_3');

		return tmpl('steps.step_3');
	}

	private static function make_news(\mysqli $connection)
	{
		$date = time();
		$connection->query("INSERT INTO `mcr_news` (
			`cid`, 
			`title`, 
			`text_html`, 
			`vote`, 
			`discus`, 
			`attach`, 
			`hidden`, 
			`uid`, 
			`date`, 
			`img`, 
			`data`
		) 
		VALUE 
			(1, 'О проекте', '<h2><strong>MagicMCR</strong></h2><p>powered by WebMCR.&nbsp;</p><h3>О проекте&nbsp;</h3><p>Публичный проект JM Organization для проекта Grand-Mine. Проект носит кодовое название webmcr_gm_edition. Разрабатывается разработчиком Magicfar4 aka Magicmen. Данный проект представляет из себя cms для сайтов проектов игры Minecrfat. Проект основывается уже на готовой cms от разработчиков.&nbsp;</p>{READMORE}<h3>Контакты&nbsp;</h3><p>Сайт официального разработчика: <a href=\"http://webmcr.com/\">http://webmcr.com/</a>&nbsp;</p><p>Официальный Wiki: <a href=\"http://wiki.webmcr.com/\">http://wiki.webmcr.com/</a>&nbsp;</p><p>Mind 42 - <a href=\"http://mind42.com/mindmap/a2e9fdc9-a645-42db-80e0-c338f8a27c2c%20\">http://mind42.com/mindmap/a2e9fdc9-a645-42db-80e0-c338f8a27c2c&nbsp;</a></p><p>Сайт организации, которая адaптировала движок для проекта:&nbsp; <a href=\"http://www.jm-org.net/\">http://www.jm-org.net/</a>&nbsp;</p>', 0, 1, 0, 0, 1, $date, '/themes/default/img/cacke.128.png', '{\"planed_news\":true,\"close_comments\":false,\"time_when_close_comments\":false}')
		;");
	}

	private static function add_phrases_to_language(\mysqli $connection)
	{
		$query = $connection->query("SELECT `phrase_key`, `phrase_value` FROM mcr_l10n_phrases");
		if ($query || $query->num_rows > 0) {
			$result = '{';

			while ($phrase = $query->fetch_assoc()) {
				$result .= '"'.$phrase['phrase_key'].'":"'.mb_ereg_replace('\r\n', '<br>', str_replace('"', '\"', $phrase['phrase_value'])).'",';
			}

			$result = substr($result, 0, -1).'}';
			$result = $connection->real_escape_string($result);

			$connection->query("
				UPDATE `mcr_l10n_languages`
				SET `phrases`='{$result}'
				WHERE `id`='1'
			");
		}
	}

	private static function save_configs(array $configs, \mysqli $connection)
	{
		$compressed_configs = installer('configs')->compress($configs);

		$values = [];
		foreach ($compressed_configs as $config => $value) {
			$values[] = "('$config', '$value')";
		}

		$values = implode(', ', $values);
		$query = "INSERT INTO `mcr_configs` (`option_key`, `option_value`) VALUES $values";

		if (!$connection->query($query)) {
			$message = [
				'title' => translate('e_msg'),
				'text' => translate('e_set_site_configs')
			];

			self::redirect($message);
		}

		self::update_configs_cache($configs);
	}

	private static function redirect(array $message = [])
	{
		return redirect()->with('message', $message)->url('/install/index.php?step_2/');
	}

	private static function update_configs_cache(array $configs)
	{
		try {
			cache::set(
				configs_application_provider::cache_name,
				(new cache_value($configs))->serialize()
			);
		} catch (cache_exception $e) {
			$message = [
				'title' => translate('e_msg'),
				'text' => $e->getMessage()
			];

			self::redirect($message);
		}
	}
}