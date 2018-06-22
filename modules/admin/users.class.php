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

		if (!$this->core->is_access('sys_adm_users')) {
			$this->core->notify($this->l10n->gettext('403'), $this->l10n->gettext('error_403'));
		}

		$bc = [
			$this->l10n->gettext('mod_name') => ADMIN_URL,
			$this->l10n->gettext('users') => ADMIN_URL . "&do=users"
		];

		$this->core->bc = $this->core->gen_bc($bc);

		$this->core->header .= $this->core->sp(MCR_THEME_MOD . "admin/users/header.phtml");
	}

	public function content()
	{

		$op = (isset($_GET['op'])) ? $_GET['op'] : 'list';

		switch ($op) {
			case 'add':
				$content = $this->add();
				break;
			case 'edit':
				$content = $this->edit();
				break;
			case 'delete':
				$this->delete();
				break;
			case 'ban':
				$this->delete();
				break;

			default:
				$content = $this->user_list();
				break;
		}

		return $content;
	}

	private function add()
	{
		if (!$this->core->is_access('sys_adm_users_add')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=users');
		}

		$bc = [
			$this->l10n->gettext('mod_name') => ADMIN_URL . "",
			$this->l10n->gettext('users') => ADMIN_URL . "&do=users",
			$this->l10n->gettext('user_add') => ADMIN_URL . "&do=users&op=add",
		];
		$this->core->bc = $this->core->gen_bc($bc);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$login = $this->db->safesql(@$_POST['login']);
			$color = $this->db->safesql(@$_POST['color']);
//			$uuid = $this->db->safesql($this->user->logintouuid(@$_POST['login']));

			$salt = $this->db->safesql($this->core->random());
			$password = $this->core->gen_password($_POST['password'], $salt);
			$password = $this->db->safesql($password);

			if (!empty($color) && !preg_match("/^\#[a-f0-9]{6}|[a-f0-9]{3}$/i", $color)) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('user_e_color_format'), 2, '?mode=admin&do=users&op=add');
			}

			if (mb_strlen($_POST['password'], "UTF-8") < 6) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('user_e_reg_pass_length'), 2, '?mode=admin&do=users&op=add');
			}

			$email = $this->db->safesql(@$_POST['email']);
			$gid = intval(@$_POST['gid']);
			$gender = (intval(@$_POST['gender']) == 1) ? 1 : 0;

			if (!$this->exist_group($gid)) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('user_group_not_found'), 1, '?mode=admin&do=users&op=add');
			}

			$money = floatval(@$_POST['money']);
			$realmoney = floatval(@$_POST['realmoney']);

			if (!$this->db->query(
				"INSERT INTO `mcr_users` (`gid`, `login`, `email`, `password`, `uuid`, `salt`, `ip_last`, `time_create`, `gender`)
				VALUES ('$gid', '$login', '$email', '$password', UNHEX(REPLACE(UUID(), '-', '')), '$salt', '{$this->user->ip}', NOW(), '$gender')"
			)
			) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=users');
			}

			$id = $this->db->insert_id();

			if (!$this->db->query(
				"INSERT INTO `mcr_iconomy` (`login`, `money`, `realmoney`)
				VALUES ('$login', '$money', '$realmoney')"
			)
			) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=users');
			}

			// Последнее обновление пользователя
			$this->db->update_user($this->user);

			// Лог действия
			$this->db->actlog($this->l10n->gettext('log_add_user') . " #$id " . $this->l10n->gettext('log_user'), $this->user->id);

			$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('user_add_success'), 3, '?mode=admin&do=users');
		}

		$data = [
			'PAGE' => $this->l10n->gettext('user_add_page_name'),
			'LOGIN' => '',
			'EMAIL' => '',
			'FIRSTNAME' => '',
			'LASTNAME' => '',
			'COLOR' => '',
			'BIRTHDAY' => date("d-m-Y"),
			'GENDER' => '',
			'GROUPS' => $this->groups(),
			'MONEY' => 0,
			'REALMONEY' => 0,
			'BUTTON' => $this->l10n->gettext('add')
		];

		return $this->core->sp(MCR_THEME_MOD . "admin/users/user-form.phtml", $data);
	}

	private function exist_group($id)
	{
		$id = intval($id);

		$query = $this->db->query("SELECT COUNT(*) FROM `mcr_groups` WHERE `id`='$id'");
		if (!$query) {
			return false;
		}

		$ar = $this->db->fetch_array($query);

		if ($ar[0] <= 0) return false;

		return true;
	}

	private function groups($select = 1)
	{
		$select = intval($select);

		$query = $this->db->query(
			"SELECT `id`, `title`
			FROM `mcr_groups`
			ORDER BY `title` ASC"
		);
		if (!$query || $this->db->num_rows($query) <= 0) {
			return null;
		}

		ob_start();

		while ($ar = $this->db->fetch_assoc($query)) {
			$id = intval($ar['id']);
			$selected = ($id == $select) ? "selected" : "";

			$title = $this->db->HSC($ar['title']);

			echo "<option value=\"$id\" $selected>$title</option>";
		}

		return ob_get_clean();
	}

	private function edit()
	{
		if (!$this->core->is_access('sys_adm_users_edit')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=users');
		}

		$id = intval($_GET['id']);

		$query = $this->db->query("
			SELECT 
				`u`.`login`, `u`.`gid`, `u`.`email`, 
				`u`.`time_create`, `u`.`time_last`, `u`.`gender`,
				
				`i`.`money`, `i`.`realmoney`
			FROM `mcr_users` AS `u`
			
			LEFT JOIN `mcr_iconomy` AS `i`
				ON `i`.`login`=`u`.`login`
				
			WHERE `u`.`id`='$id'
		");

		if (!$query || $this->db->num_rows($query) <= 0) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('e_sql_critical'), 2, '?mode=admin&do=users');
		}

		$ar = $this->db->fetch_assoc($query);

		$bc = [
			$this->l10n->gettext('mod_name') => ADMIN_URL . "",
			$this->l10n->gettext('users') => ADMIN_URL . "&do=users",
			$this->l10n->gettext('user_edit') => ADMIN_URL . "&do=users&op=edit&id=$id",
		];
		$this->core->bc = $this->core->gen_bc($bc);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$login = $this->db->safesql(@$_POST['login']);
			$color = $this->db->safesql(@$_POST['color']);

			$password = "`password`";
			$salt = "`salt`";

			if (!empty($color) && !preg_match("/^\#[a-f0-9]{6}|[a-f0-9]{3}$/i", $color)) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('user_e_color_format'), 2, '?mode=admin&do=users&op=edit&id=' . $id);
			}

			if (isset($_POST['password']) && !empty($_POST['password'])) {
				$salt = $this->db->safesql($this->core->random());
				$salt = "'$salt'";

				if (mb_strlen($_POST['password'], "UTF-8") < 6) {
					$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('user_e_reg_pass_length'), 2, '?mode=admin&do=users&op=edit&id=' . $id);
				}

				$password = $this->core->gen_password($_POST['password'], $salt);
				$password = $this->db->safesql($password);
				$password = "'$password'";
			}

			$email = $this->db->safesql(@$_POST['email']);
			$gid = intval(@$_POST['gid']);
			$gender = (intval(@$_POST['gender']) == 1) ? 1 : 0;

			if (!$this->exist_group($gid)) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('user_group_not_found'), 1, '?mode=admin&do=users&op=edit&id=' . $id);
			}

			$money = floatval(@$_POST['money']);
			$realmoney = floatval(@$_POST['realmoney']);

			if (!$this->db->query(
				"UPDATE `mcr_users`
				SET 
					`gid`='$gid', 
					`login`='$login', 
					`email`='$email',
					`password`=$password, 
					`uuid`=UNHEX(REPLACE(UUID(), '-', '')), 
					`salt`=$salt, 
					`time_last`=NOW(),
					`gender`='$gender'					
				WHERE `id`='$id'"
			)
			) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('e_sql_critical') . ' ' . mysqli_error($this->db->obj), 2, '?mode=admin&do=users&op=edit&id=' . $id);
			}

			$old_login = $this->db->safesql($ar['login']);

			if (file_exists(MCR_SKIN_PATH . $old_login . '.png')) {
				if (!rename(MCR_SKIN_PATH . $old_login . '.png', MCR_SKIN_PATH . $login . '.png')) {
					$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('user_e_skin_name'), 2, '?mode=admin&do=users&op=edit&id=' . $id);
				}
			}

			if (file_exists(MCR_CLOAK_PATH . $old_login . '.png')) {
				if (!rename(MCR_CLOAK_PATH . $old_login . '.png', MCR_CLOAK_PATH . $login . '.png')) {
					$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('user_e_cloak_name'), 2, '?mode=admin&do=users&op=edit&id=' . $id);
				}
			}

			if (!$this->db->query(
				"UPDATE `mcr_iconomy`
				SET `login`='$login', `money`='$money', `realmoney`='$realmoney'
				WHERE `login`='$old_login'"
			)
			) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('e_sql_critical'), 2, '?mode=admin&do=users&op=edit&id=' . $id);
			}

			// Последнее обновление пользователя
			$this->db->update_user($this->user);

			// Лог действия
			$this->db->actlog($this->l10n->gettext('log_edit_user') . " #$id " . $this->l10n->gettext('log_user'), $this->user->id);

			$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('user_edit_success'), 3, '?mode=admin&do=users');
		}

		$gender = (intval($ar['gender']) == 1 || $ar['gender'] == 'female') ? "selected" : "";

		$data = [
			"PAGE" => $this->l10n->gettext('user_edit_page_name'),
			"LOGIN" => $this->db->HSC($ar['login']),
			"EMAIL" => $this->db->HSC($ar['email']),
			"GENDER" => $gender,
			"GROUPS" => $this->groups($ar['gid']),
			"MONEY" => floatval($ar['money']),
			"REALMONEY" => floatval($ar['realmoney']),
			"BUTTON" => $this->l10n->gettext('save')
		];

		return $this->core->sp(MCR_THEME_MOD . "admin/users/user-form.phtml", $data);
	}

	private function delete()
	{
		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_hack'), 2, '?mode=admin&do=users');
		}

		$list = @$_POST['id'];

		if (empty($list)) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('user_not_selected'), 2, '?mode=admin&do=users');
		}

		$list = $this->core->filter_int_array($list);

		$list = array_unique($list);

		$list = $this->db->safesql(implode(", ", $list));

		$logins = $this->get_logins($list);

		if ($logins === false) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('user_not_found'), 2, '?mode=admin&do=users');
		}

		// Если режим блокировки юзера, то вызываем метод блокировки.
		// В результате нас перекинет обратно к списку юзеров
		// с сообщением об удочной/неудачной блокировки.
		if (isset($_POST['ban'])) {
			$this->ban($list);
		} elseif (isset($_POST['unban'])) {
			$this->ban($list, 0);
		}

		if (!$this->core->is_access('sys_adm_users_delete')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=users');
		}

		if (!isset($_POST['delete'])) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_hack'), 2, '?mode=admin&do=users');
		}

		if (!$this->db->remove_fast('mcr_users', "`id` IN ($list)")) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=users');
		}

		$count = $this->db->affected_rows();

		foreach ($logins as $key => $value) {
			if (file_exists(MCR_SKIN_PATH . $value . '.png')) {
				@unlink(MCR_SKIN_PATH . $value . '.png');
			}
			if (file_exists(MCR_SKIN_PATH . 'interface/' . $value . '.png')) {
				@unlink(MCR_SKIN_PATH . 'interface/' . $value . '.png');
			}
			if (file_exists(MCR_SKIN_PATH . 'interface/' . $value . '_mini.png')) {
				@unlink(MCR_SKIN_PATH . 'interface/' . $value . '_mini.png');
			}
			if (file_exists(MCR_CLOAK_PATH . $value . '.png')) {
				@unlink(MCR_CLOAK_PATH . $value . '.png');
			}
		}

		// Последнее обновление пользователя
		$this->db->update_user($this->user);

		// Лог действия
		$this->db->actlog($this->l10n->gettext('log_del_user') . " $list " . $this->l10n->gettext('log_user'), $this->user->id);

		$this->core->notify($this->l10n->gettext('error_success'), sprintf($this->l10n->gettext('elements_deleted'), $count), 3, '?mode=admin&do=users');
	}

	private function get_logins($list)
	{
		$query = $this->db->query("SELECT `login` FROM `mcr_users` WHERE `id` IN ($list)");

		if ($query || $this->db->num_rows($query) > 0) {
			$logins = [];

			while ($ar = $this->db->fetch_assoc($query)) {
				$logins[] = $ar['login'];
			}

			return $logins;
		}

		return false;
	}

	private function ban($list, $ban = 1)
	{
		if (!$this->core->is_access('sys_adm_users_ban')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=users');
		}

		if (!$this->db->query("UPDATE `mcr_users` SET `ban_server`='$ban' WHERE `id` IN ($list)")) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=users');
		}

		$message = ($ban == 1) ? $this->l10n->gettext('user_ban') : $this->l10n->gettext('user_unban');

		// Последнее обновление пользователя
		$this->db->update_user($this->user);

		// Лог действия
		$this->db->actlog($this->l10n->gettext('log_ban_user') . " $list " . $this->l10n->gettext('log_user'), $this->user->id);

		$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('user_success') . " " . $message, 3, '?mode=admin&do=users');
	}

	private function user_list()
	{
		$sql = "SELECT COUNT(*) FROM `mcr_users`";
		$page = "?mode=admin&do=users";

		if (isset($_GET['search']) && !empty($_GET['search'])) {
			$search = $this->db->safesql($_GET['search']);
			if (preg_match("/[а-яА-ЯёЁ]+/iu", $search)) {
				$search = "";
			}
			$table = (preg_match("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/i", $search)) ? 'ip_last' : 'login';
			$sql = "SELECT COUNT(*) FROM `mcr_users` WHERE `$table` LIKE '%$search%'";
			$search = $this->db->HSC($_GET['search']);
			$page = "?mode=admin&do=users&search=$search";
		}

		if (isset($_GET['sort']) && !empty($_GET['sort'])) {
			$page .= '&sort=' . $this->db->HSC(urlencode($_GET['sort']));
		}

		$query = $this->db->query($sql);

		$ar = @$this->db->fetch_array($query);

		$data = [
			"PAGINATION" => $this->core->pagination($this->cfg->pagin['adm_users'], $page . '&pid=', $ar[0]),
			"USERS" => $this->user_array()
		];

		return $this->core->sp(MCR_THEME_MOD . "admin/users/user-list.phtml", $data);
	}

	private function user_array()
	{
		$start = $this->core->pagination($this->cfg->pagin['adm_users'], 0, 0); // Set start pagination
		$end = $this->cfg->pagin['adm_users']; // Set end pagination

		$where = "";
		$sort = "`u`.`id`";
		$sortby = "DESC";

		if (isset($_GET['search']) && !empty($_GET['search'])) {
			$search = $this->db->safesql($_GET['search']);
			if (preg_match("/[а-яА-ЯёЁ]+/iu", $search)) {
				$search = "";
			}
			$table = (preg_match("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/i", $search)) ? 'ip_last' : 'login';
			$where = "WHERE `u`.`$table` LIKE '%$search%'";
		}

		if (isset($_GET['sort']) && !empty($_GET['sort'])) {
			$expl = explode(' ', $_GET['sort']);

			$sortby = ($expl[0] == 'asc') ? "ASC" : "DESC";

			switch (@$expl[1]) {
				case 'user':
					$sort = "`u`.`login`";
					break;
				case 'group':
					$sort = "`g`.`title`";
					break;
				case 'email':
					$sort = "`u`.`email`";
					break;
				case 'ip':
					$sort = "`u`.`ip_last`";
					break;
			}
		}

		$query = $this->db->query("
			SELECT 
				`u`.`id`, `u`.`gid`, `u`.`login`, 
				`u`.`email`, `u`.`ip_last`,
				
				`g`.`title` AS `group`, `g`.`color` AS `gcolor`
			FROM `mcr_users` AS `u`
			
			LEFT JOIN `mcr_groups` AS `g`
				ON `g`.`id`=`u`.`gid`
				
			$where
			
			ORDER BY $sort $sortby
			
			LIMIT $start, $end
		");

		if (!$query || $this->db->num_rows($query) <= 0) {
			return $this->core->sp(MCR_THEME_MOD . "admin/users/user-none.phtml");
		}

		ob_start();

		while ($ar = $this->db->fetch_assoc($query)) {

			$color = $ar['gcolor'];

			$page_data = [
				"ID" => intval($ar['id']),
				"GID" => intval($ar['group']),
				"LOGIN" => $this->core->colorize($this->db->HSC($ar['login']), $color),
				"EMAIL" => $this->db->HSC($ar['email']),
				"GROUP" => $this->core->colorize($this->db->HSC($ar['group']), $color),
				"IP_LAST" => $this->db->HSC($ar['ip_last']),
			];

			echo $this->core->sp(MCR_THEME_MOD . "admin/users/user-id.phtml", $page_data);
		}

		return ob_get_clean();
	}
}