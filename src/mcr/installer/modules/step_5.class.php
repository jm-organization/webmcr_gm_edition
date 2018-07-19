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

namespace mcr\installer\modules;


if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class step_5 extends install_step
{
	public function content()
	{
		global $configs;
		$this->title = $this->lng['mod_name'] . ' — ' . $this->lng['step_5'];
		
		if (!isset($_SESSION['step_4'])) {
			$this->notify('', '', 'install/?do=step_4');
		}
		if (isset($_SESSION['step_5'])) {
			$this->notify('', '', 'install/?do=finish');
		}

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$db = @new \mysqli(config('db::host'), config('db::user'), config('db::pass'), config('db::base'), config('db::port'));
			$error = $db->connect_error;

			if (!empty($error)) {
				$this->notify($this->lng['e_connection'] . ' | ' . $error, $this->lng['e_msg'], 'install/?do=step_2');
			}
			
			if (!$db->query("
				INSERT INTO `mcr_menu` (`title`, `parent`, `url`, `target`, `permissions`) 
				VALUES ('Пользователи', 0, '/?mode=users', '_self', 'mod_users_list');
			")
			) {
				$this->notify($this->lng['e_msg'], $this->lng['e_add_menu'], '?mode=step_5');
			}

			if (!$db->query("
				INSERT INTO `mcr_menu_adm_icons` (`title`, `img`)
				VALUES ('Модуль пользователей', 'us.png');
			")
			) {
				$this->notify($this->lng['e_msg'], $this->lng['e_add_icon'], '?mode=step_5');
			}

			$icon_id = $db->insert_id;

			if (!$db->query("
				INSERT INTO `mcr_menu_adm` (`page_id`, `gid`, `title`, `text`, `url`, `target`, `access`, `priority`, `icon`)
				VALUES ('us', 5, 'Модуль пользователей', 'Управление модулем пользователей', '/?mode=admin&do=us', '_self', 'mod_adm_m_i_us', 4, '$icon_id');
			")
			) {
				$this->notify($this->lng['e_msg'], $this->lng['e_add_menu_adm'], '?mode=step_5');
			}

			$groups = array();

			$query = $db->query("SELECT `id`, `permissions` FROM `mcr_groups`");
			if (!$query || $query->num_rows <= 0) {
				$this->notify($this->lng['e_msg'], $this->lng['e_msg'], '?mode=step_5');
			}

			while ($ar = $query->fetch_assoc()) {
				$groups[] = array(
					'id' => intval($ar['id']),
					'permissions' => json_decode($ar['permissions'], true),
				);
			}

			foreach ($groups as $group) {
				$gid = intval($group['id']);

				$group['permissions']['mod_users_list'] = ($gid == 3 || $gid == 2) ? true : false;
				$group['permissions']['mod_users_full'] = ($gid == 3 || $gid == 2) ? true : false;
				$group['permissions']['mod_users_comments'] = ($gid == 3 || $gid == 2) ? true : false;
				$group['permissions']['mod_users_comment_add'] = ($gid == 3 || $gid == 2) ? true : false;
				$group['permissions']['mod_users_comment_del'] = ($gid == 3 || $gid == 2) ? true : false;
				$group['permissions']['mod_users_comment_del_all'] = ($gid == 3) ? true : false;
				$group['permissions']['mod_adm_m_i_us'] = ($gid == 3) ? true : false;
				$group['permissions']['mod_users_adm_settings'] = ($gid == 3) ? true : false;

				$newperm = $db->real_escape_string(json_encode($group['permissions']));

				$db->query("UPDATE `mcr_groups` SET `permissions`='$newperm' WHERE id='$gid'");
			}

			$_modules_users = config('modules::users');
			$_modules_users['install'] = false;

			if (!$configs->savecfg($_modules_users, 'modules/users.php', 'cfg')) {
				$this->notify($this->lng['e_msg'], $this->lng['e_settings'], '?mode=step_5');
			}

			$_SESSION['step_5'] = true;

			$this->notify($this->lng['mod_name'], $this->lng['finish'], 'install/?mode=finish');

		}

		$data = array();

		return $this->sp('step_5.phtml', $data);
	}
}