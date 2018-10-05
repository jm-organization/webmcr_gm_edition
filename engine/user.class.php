<?php

namespace mcr;


if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class user extends core_v2
{
	/**
	 * @var int
	 */
	public $id = 0;

	/**
	 * @var
	 */
	public $uuid;

	/**
	 * @var string
	 */
	public $email = '';

	/**
	 * @var string
	 */
	public $login = '';

	/**
	 * @var string
	 */
	private $password;

	/**
	 * @var string
	 */
	private $salt;

	/**
	 * @var string
	 */
	public $group = '';

	/**
	 * @var string
	 */
	public $group_desc = '';

	/**
	 * @var string|null
	 */
	private $tmp = null;

	/**
	 * @var string
	 */
	public $ip;

	/**
	 * @var mixed|null
	 */
	public $permissions = null;

	/**
	 * @var int|string
	 */
	public $gender = 0;

	/**
	 * @var int
	 */
	public $time_create = 0;

	/**
	 * @var int
	 */
	public $time_last = 0;

	/**
	 * @var bool
	 */
	public $is_auth = false;

	/**
	 * @var bool
	 */
	public $is_skin = false;

	/**
	 * @var bool
	 */
	public $is_cloak = false;

	/**
	 * @var string
	 */
	public $skin = 'default';

	/**
	 * @var string
	 */
	public $cloak = '';

	/**
	 * @var float|int
	 */
	public $money = 0;

	/**
	 * @var float|int
	 */
	public $realmoney = 0;

	/**
	 * @var float|int
	 */
	public $bank = 0;

	/**
	 * @var int
	 */
	public $gid = -1;

	/**
	 * @var auth
	 */
	public $auth;

	/**
	 * user constructor.
	 *
	 * @documentation:
	 */
	public function __construct()
	{
		global $configs;

		$this->group = 'user';
		$this->group_desc = 'default group';

		// Set now ip
		$this->ip = $this->ip();

		$this->auth = $this->load_auth();

		// Check cookies
		if (!isset($_COOKIE['mcr_user'])) {
			$perm_ar = @$this->get_default_permissions();
			$this->permissions = $perm_ar[0];
			$this->permissions_v2 = $perm_ar[1];

			return false;
		}

		$user_cookie = explode("_", $_COOKIE['mcr_user']);

		if (!isset($user_cookie[0], $user_cookie[1])) {
			$this->set_unauth();
			$this->notify();
		}

		$uid = intval($user_cookie[0]);
		//$hash = $user_cookie[1];

		$ctables = $this->cfg->db['tables'];

		$ug_f = $ctables['ugroups']['fields'];
		$us_f = $ctables['users']['fields'];
		$ic_f = $ctables['iconomy']['fields'];

		$query = $this->db->query("
			SELECT 
				`u`.`{$us_f['group']}`, `u`.`{$us_f['login']}`, `u`.`{$us_f['email']}`, 
				`u`.`{$us_f['pass']}`, `u`.`{$us_f['salt']}`, `u`.`{$us_f['tmp']}`, 
				`u`.`{$us_f['date_reg']}`, `u`.`{$us_f['date_last']}`, `u`.`{$us_f['gender']}`,
				`u`.`{$us_f['is_skin']}`, `u`.`{$us_f['is_cloak']}`, `u`.`{$us_f['uuid']}`,
				
				`g`.`{$ug_f['title']}`, `g`.`{$ug_f['text']}`, `g`.`{$ug_f['perm']}`, 
				`g`.`{$ug_f['color']}` AS `gcolor`,
				
				`i`.`{$ic_f['money']}`, `i`.`{$ic_f['rm']}`, `i`.`{$ic_f['bank']}`
			FROM `{$this->cfg->tabname('users')}` AS `u`
			
			INNER JOIN `{$this->cfg->tabname('ugroups')}` AS `g`
				ON `g`.`{$ug_f['id']}`=`u`.`{$us_f['group']}`
				
			LEFT JOIN `{$this->cfg->tabname('iconomy')}` AS `i`
				ON `i`.`{$ic_f['login']}`=`u`.`{$us_f['login']}`
				
			WHERE `u`.`{$us_f['id']}`='$uid'
		");

		if (!$query || $this->db->num_rows($query) <= 0) {
			$this->set_unauth();
			$this->core->notify();
		}

		$ar = $this->db->fetch_assoc($query);

		$tmp = $this->db->HSC($ar[$us_f['tmp']]);
		$password = $this->db->HSC($ar[$us_f['pass']]);

		$new_hash = $uid.$tmp.$this->ip.md5($this->cfg->main['mcr_secury']);

		$ar_hash = $uid.'_'.md5($new_hash);

		// Check security auth
		if ($_COOKIE['mcr_user'] !== $ar_hash) {
			$this->set_unauth();
			$this->core->notify('Warning!');
		}

		$login = $this->db->HSC($ar[$us_f['login']]);

		$color = $this->db->HSC($ar['gcolor']);
		$gcolor = $color;

		$group = $this->db->HSC($ar[$ug_f['title']]);

		// Identificator
		$this->id = $uid;

		// Group identificator
		$this->gid = intval($ar[$us_f['group']]);

		// Username
		$this->login = $login;

		// Username
		$this->login_v2 = $this->core->colorize($login, $color);

		// E-Mail
		$this->email = $this->db->HSC($ar[$us_f['email']]);

		// UUID
		$this->uuid = $this->db->HSC($ar[$us_f['uuid']]);

		// Password hash
		$this->password = $password;

		// Salt of password
		$this->salt = $ar[$us_f['salt']];

		// Temp hash
		$this->tmp = $tmp;

		// Group title
		$this->group = $group;

		// Group title with colorize
		$this->group_v2 = $this->core->colorize($group, $gcolor);

		// Group description
		$this->group_desc = $this->db->HSC($ar[$ug_f['text']]);

		// Permissions
		$this->permissions = @json_decode($ar[$ug_f['perm']]);

		// Permissions
		$this->permissions_v2 = @json_decode($ar[$ug_f['perm']], true);

		// Is auth status
		$this->is_auth = true;

		// Is default skin
		$this->is_skin = (intval($ar[$us_f['is_skin']]) == 1) ? true : false;

		// Is isset cloak
		$this->is_cloak = (intval($ar[$us_f['is_cloak']]) == 1) ? true : false;

		$this->skin = ($this->is_skin || $this->is_cloak) ? $this->login : 'default';

		$this->cloak = ($this->is_cloak) ? $this->login : '';

		// Gender
		$this->gender = ($ar[$us_f['gender']] == 'female') ? $this->l10n->gettext('gender_w') : (($ar[$us_f['gender']] == 'male') ? $this->l10n->gettext('gender_m') : (($ar[$us_f['gender']] == 'no_set') ? $this->l10n->gettext('gender_n') : ''));

		//$format = $this->l10n->get_date_format().' '.$this->l10n->gettext('in').' '.$this->l10n->get_time_format();
		//$this->time_create = $this->l10n->localize($ar[$us_f['date_reg']], 'datetime', $format);
		//$this->time_last = $this->l10n->localize($ar[$us_f['date_last']], 'datetime', $format);

		$this->time_create = $ar[$us_f['date_reg']];
		$this->time_last = $ar[$us_f['date_last']];

		// Game money balance
		$this->money = floatval($ar[$ic_f['money']]);

		// Real money balance
		$this->realmoney = floatval($ar[$ic_f['rm']]);

		// Bank money balance (for plugins)
		$this->bank = floatval($ar[$ic_f['bank']]);

		parent::__construct($configs);

		return $this;
	}

	private function ip()
	{
		if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
			$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
		} elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
			$ip = $_SERVER['HTTP_X_REAL_IP'];
		} elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		return mb_substr($ip, 0, 16, "UTF-8");
	}

	private function load_auth()
	{
		if (!file_exists(MCR_LIBS_PATH.'auth/'.$this->cfg->main['p_logic'].'.php')) {
			exit('Auth Type Error!');
		}

		require_once(MCR_LIBS_PATH.'auth/'.$this->cfg->main['p_logic'].'.php');

		return new auth($this->core);
	}

	public function get_default_permissions()
	{
		if (file_exists(MCR_CACHE_PATH.'permissions')) {
			$json = file_get_contents(MCR_CACHE_PATH.'permissions');
			$array = json_decode($json, true);
			$object = json_decode($json);

			return [
				$object,
				$array
			];
		}

		$permissions = @$this->update_default_permissions();

		return [
			json_decode($permissions),
			json_decode($permissions, true)
		];
	}

	public function update_default_permissions()
	{
		$query = $this->db->query("SELECT `value`, `type`, `default` FROM `mcr_permissions`");

		if (!$query || $this->db->num_rows($query) <= 0) {
			return null;
		}

		$array = [];

		while ($ar = $this->db->fetch_assoc($query)) {

			switch ($ar['type']) {
				case 'integer':
					$array[$ar['value']] = intval($ar['default']);
					break;

				case 'float':
					$array[$ar['value']] = floatval($ar['default']);
					break;

				case 'string':
					$array[$ar['value']] = $this->db->safesql($ar['default']);
					break;

				default:
					$array[$ar['value']] = ($ar['default'] == 'true') ? true : false;
					break;
			}
		}

		$permissions = json_encode($array);

		@file_put_contents(MCR_CACHE_PATH.'permissions', $permissions);

		return $permissions;
	}

	public function set_unauth()
	{
		if (isset($_COOKIE['mcr_user'])) {
			setcookie("mcr_user", "", time() - 3600, '/');
		}

		return true;
	}
}