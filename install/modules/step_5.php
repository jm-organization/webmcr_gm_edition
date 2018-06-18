<?php
/*
 * Изменения разработчиками JM Organization
 *
 * @contact: admin@jm-org.net
 * @web-site: www.jm-org.net
 *
 * @supplier: Magicmen
 * @script_author: Qexy
 *
 **/

if(!defined("MCR")){ exit("Hacking Attempt!"); }

class module{
	private $install, $cfg, $lng, $db;

	public function __construct($install){
		$this->install = $install;
		$this->cfg = $install->cfg;
		$this->lng = $install->lng;

		$db = $this->cfg['db'];

		require_once DIR_ROOT.'engine/db/'.$db['backend'].'.class.php';

		$this->db = new db($db['host'], $db['user'], $db['pass'], $db['base'], $db['port']);

		$error = $this->db->error();

		if(!empty($error)){
			$this->install->notify($this->lng['e_connection'].' | '.$error, $this->lng['e_msg'], 'install/?do=step_5');
		}

		$this->install->title = $this->lng['mod_name'].' — '.$this->lng['step_5'];
	}

	public function content() {
		if(!isset($_SESSION['step_4'])){ $this->install->notify('', '', 'install/?do=step_4'); }
		if(isset($_SESSION['step_5'])){ $this->install->notify('', '', 'install/?do=finish'); }

		if($_SERVER['REQUEST_METHOD'] == 'POST'){
			if(!$this->db->query("
				INSERT INTO `mcr_menu` (`title`, `parent`, `url`, `target`, `permissions`) 
				VALUES ('Пользователи', 0, '/?mode=users', '_self', 'mod_users_list');
			")) {
				$this->install->notify($this->lng['e_msg'], $this->lng['e_add_menu'], '?mode=step_5');
			}

			if(!$this->db->query("
				INSERT INTO `mcr_menu_adm_icons` (`title`, `img`)
				VALUES ('Модуль пользователей', 'us.png');
			")) {
				$this->install->notify($this->lng['e_msg'], $this->lng['e_add_icon'], '?mode=step_5');
			}

			$icon_id = $this->db->insert_id();

			if(!$this->db->query("
				INSERT INTO `mcr_menu_adm` (`page_id`, `gid`, `title`, `text`, `url`, `target`, `access`, `priority`, `icon`)
				VALUES ('us', 5, 'Модуль пользователей', 'Управление модулем пользователей', '/?mode=admin&do=us', '_self', 'mod_adm_m_i_us', 4, '$icon_id');
			")){
				$this->install->notify($this->lng['e_msg'], $this->lng['e_add_menu_adm'], '?mode=step_5');
			}

			$groups = array();

			$query = $this->db->query("SELECT `id`, `permissions` FROM `mcr_groups`");
			if(!$query || $this->db->num_rows($query)<=0){ $this->install->notify($this->lng['e_msg'], $this->lng['e_msg'], '?mode=step_5'); }

			while($ar = $this->db->fetch_assoc($query)){
				$groups[] = array(
					'id' => intval($ar['id']),
					'permissions' => json_decode($ar['permissions'], true),
				);
			}

			foreach($groups as $group){
				$gid = intval($group['id']);

				$group['permissions']['mod_users_list'] = ($gid==3 || $gid==2) ? true : false;
				$group['permissions']['mod_users_full'] = ($gid==3 || $gid==2) ? true : false;
				$group['permissions']['mod_users_comments'] = ($gid==3 || $gid==2) ? true : false;
				$group['permissions']['mod_users_comment_add'] = ($gid==3 || $gid==2) ? true : false;
				$group['permissions']['mod_users_comment_del'] = ($gid==3 || $gid==2) ? true : false;
				$group['permissions']['mod_users_comment_del_all'] = ($gid==3) ? true : false;
				$group['permissions']['mod_adm_m_i_us'] = ($gid==3) ? true : false;
				$group['permissions']['mod_users_adm_settings'] = ($gid==3) ? true : false;

				$newperm = $this->db->safesql(json_encode($group['permissions']));

				$this->db->query("UPDATE `mcr_groups` SET `permissions`='$newperm' WHERE id='$gid'");
			}

			$this->cfg['modules']['users']['install'] = false;

			if(!$this->install->savecfg($this->cfg['modules']['users'], 'modules/users.php', 'cfg')){
				$this->install->notify($this->lng['e_msg'], $this->lng['e_settings'], '?mode=step_5');
			}

			$_SESSION['step_5'] = true;

			$this->install->notify($this->lng['mod_name'], $this->lng['finish'], 'install/?mode=finish');

		}

		$data = array();

		return $this->install->sp('step_5.phtml', $data);
	}
}