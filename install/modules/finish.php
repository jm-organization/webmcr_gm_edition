<?php

if(!defined("MCR")){ exit("Hacking Attempt!"); }

class module{
	private $install, $cfg, $lng;

	public function __construct(install $install){
		$this->install = $install;
		$this->cfg = $install->cfg;
		$this->lng = $install->lng;

		$db = $this->cfg['db'];

		require_once DIR_ROOT.'engine/db/'.$db['backend'].'.class.php';

		$this->db = new db($db['host'], $db['user'], $db['pass'], $db['base'], $db['port']);

		$error = $this->db->error();

		if(!empty($error)){
			$this->install->notify($this->lng['e_connection'].' | '.$error, $this->lng['e_msg'], 'install/?do=step_1');
		}

		$this->install->title = $this->lng['mod_name'].' — '.$this->lng['finish'];
	}

	public function content(){
		if(!isset($_SESSION['step_5'])){ $this->install->notify('', '', 'install/?do=step_5'); }

		if ($this->cfg['main']['install'] == false) {
			$date = time();

			$this->db->query(
				"INSERT INTO `mcr_news` (`cid`, `title`, `text_html`, `vote`, `discus`, `attach`, `hidden`, `uid`, `date`, `img`, `data`) 
				VALUE (1, 'О проекте', '<h2><strong>MagicMCR&nbsp;</strong></h2><p>powered by WebMCR.&nbsp;</p><h3>О проекте&nbsp;</h3><p>Публичный проект JM Organization для проекта Grand-Mine. Проект носит кодовое название webmcr_gm_edition. Разрабатывается разработчиком Magicfar4 aka Magicmen. Данный проект представляет из себя cms для сайтов проектов игры Minecrfat. Проект основывается уже на готовой cms от разработчиков.&nbsp;</p>{READMORE}<h3>Контакты&nbsp;</h3><p>Сайт официального разработчика: <a href=\"http://webmcr.com\">http://webmcr.com</a>&nbsp;</p><p>Официальный Wiki: <a href=\"http://wiki.webmcr.com/\">http://wiki.webmcr.com/&nbsp;</a></p><p>Mind 42 - <a href=\"http://mind42.com/mindmap/a2e9fdc9-a645-42db-80e0-c338f8a27c2c%20\">http://mind42.com/mindmap/a2e9fdc9-a645-42db-80e0-c338f8a27c2c&nbsp;</a></p><p>Сайт организации, которая адaптировала движок для проекта:&nbsp; <a href=\"http://www.jm-org.net/\">http://www.jm-org.net/</a>&nbsp;</p>', 0, 1, 0, 0, 1, $date, '/themes/default/img/cacke.128.png', '{\"planed_news\":true,\"close_comments\":false,\"time_when_close_comments\":false}');"
			);

			$query = $this->db->query("SELECT `phrase_key`, `phrase_value` FROM mcr_l10n_phrases");
			if ($query || $this->db->num_rows($query) > 0) {
				$result = '{';

				while ($phrase = $this->db->fetch_assoc($query)) {
					$result .= '"'.$phrase['phrase_key'].'":"'.str_replace('"', '\"', $phrase['phrase_value']).'",';
				}

				$result = substr($result, 0, -1).'}';
				$result = $this->db->safesql($result);

				$this->db->query("
					UPDATE `mcr_l10n_languages`
					SET `phrases`='{$result}'
					WHERE `id`='1'
				");
			}

			$this->cfg['main']['install'] = true;
			$this->install->savecfg($this->cfg['main'], 'main.php', 'main');
		}

		$data = array();

		return $this->install->sp('finish.html', $data);
	}

}