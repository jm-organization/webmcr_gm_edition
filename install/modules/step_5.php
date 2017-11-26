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
			$this->install->notify($this->lng['e_connection'].' | '.$error, $this->lng['e_msg'], 'install/?do=step_1');
		}

		$this->install->title = $this->lng['mod_name'].' — '.$this->lng['step_4'];
	}

	public function content() {
		if(!isset($_SESSION['step_4'])){ $this->install->notify('', '', 'install/?do=step_4'); }
		if(isset($_SESSION['step_5'])){ $this->install->notify('', '', 'install/?do=finish'); }

		$tables = $this->cfg['db']['tables'];
		$ug_fields = $tables['ugroups']['fields'];

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!$this->db->query("
				INSERT INTO `mcr_menu` (`title`, `parent`, `url`, `target`, `permissions`) 
				VALUES ('Пользователи', 0, '/?mode=users', '_self', 'mod_users_list');
			")) {
				$this->install->notify($this->lng['e_msg'], $this->lng['e_add_menu'], '?mode=step_3');
			}

			if(!$this->db->query("
				INSERT INTO `mcr_menu_adm_icons` (`title`, `img`)
				VALUES ('Модуль пользователей', 'us.png');
			")) {
				$this->install->notify($this->lng['e_msg'], $this->lng['e_add_icon'], '?mode=step_3');
			}

			$icon_id = $this->db->insert_id();

			if(!$this->db->query("
				INSERT INTO `mcr_menu_adm` (`gid`, `title`, `text`, `url`, `target`, `access`, `priority`, `icon`)
				VALUES (5, 'Модуль пользователей', 'Управление модулем пользователей', '/?mode=admin&do=us', '_self', 'mod_adm_m_i_us', 4, '$icon_id');
			")){
				$this->install->notify($this->lng['e_msg'], $this->lng['e_add_menu_adm'], '?mode=step_3');
			}

			$groups = array();

			$query = $this->db->query("SELECT `{$ug_fields['id']}`, `{$ug_fields['perm']}` FROM `{$this->cfg['db']['tables']['ugroups']['name']}`");

			if(!$query || $this->db->num_rows($query)<=0){ $this->install->notify($this->lng['e_msg'], $this->lng['e_msg'], '?mode=step_5'); }

			while($ar = $this->db->fetch_assoc($query)){
				$groups[] = array(
					'id' => intval($ar[$ug_fields['id']]),
					'permissions' => json_decode($ar[$ug_fields['perm']], true),
				);
			}

			foreach($groups as $key => $value){
				$gid = intval($value['id']);

				$value['permissions']['mod_users_list'] = ($gid==3 || $gid==2) ? true : false;
				$value['permissions']['mod_users_full'] = ($gid==3 || $gid==2) ? true : false;
				$value['permissions']['mod_users_comments'] = ($gid==3 || $gid==2) ? true : false;
				$value['permissions']['mod_users_comment_add'] = ($gid==3 || $gid==2) ? true : false;
				$value['permissions']['mod_users_comment_del'] = ($gid==3 || $gid==2) ? true : false;
				$value['permissions']['mod_users_comment_del_all'] = ($gid==3) ? true : false;
				$value['permissions']['mod_adm_m_i_us'] = ($gid==3) ? true : false;
				$value['permissions']['mod_users_adm_settings'] = ($gid==3) ? true : false;

				$newperm = json_encode($value['permissions']);

				$newperm = $this->db->safesql($newperm);

				$this->db->query("UPDATE `{$this->cfg['db']['tables']['ugroups']['name']}` SET `{$ug_fields['perm']}`='$newperm' WHERE id='$gid'");
			}

			$this->cfg['modules']['users']['install'] = false;

			if(!$this->install->savecfg($this->cfg['modules']['users'], 'modules/users.php', 'cfg')){
				$this->install->notify($this->lng['e_msg'], $this->lng['e_settings'], '?mode=step_5');
			}

			$_SESSION['step_5'] = true;

			$this->install->notify($this->lng['mod_name'], $this->lng['finish'], '?mode=finish');

		}

		$data = array();

		return $this->install->sp('step_5.html', $data);
	}
}

?>