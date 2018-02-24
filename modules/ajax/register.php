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
	}

	private function is_post()
	{
		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			return (object)[
				'status' => false,
				'error' => $this->l10n->gettext('error_hack'),
			];
		}

		return (object)[
			'status' => true,
			'error',
		];
	}

	private function is_unauth()
	{
		if ($this->user->is_auth) {
			return (object)[
				'status' => false,
				'error' => $this->l10n->gettext('auth_already'),
			];
		}

		return (object)[
			'status' => true,
			'error',
		];
	}

	private function is_agree_with_rules()
	{
		if (intval($_POST['rules']) !== 1) {
			return (object)[
				'status' => false,
				'error' => $this->l10n->gettext('rules_error'),
			];
		}

		return (object)[
			'status' => true,
			'error',
		];
	}

	private function is_vl($login)
	{
		if (preg_match("/^[\w\-]{3,}$/i", $login) != 1) {
			return (object)[
				'status' => false,
				'error' => $this->l10n['e_login_regexp'],
			];
		} elseif (preg_match("/user|default|admin/i", $login) == 1) {
			return (object)[
				'status' => false,
				'error' => $this->l10n['e_exist'],
			];
		}

		return (object)[
			'status' => true,
			'error',
			'data' => $login,
		];
	}

	private function is_ve($email)
	{
		//TODO: Email Validation
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return (object)[
				'status' => false,
				'error' => $this->l10n->gettext('email-format_error'),
			];
		}

		return (object)[
			'status' => true,
			'error',
			'data' => $email,
		];
	}

	private function is_vp($password)
	{
		if (mb_strlen($password, "UTF-8") < 6) {
			return (object)[
				'status' => false,
				'error' => $this->l10n->gettext('pass-lenght_error'),
			];
		} elseif ($password !== @$_POST['repassword']) {
			return (object)[
				'status' => false,
				'error' => $this->l10n->gettext('pass-match_error'),
			];
		}

		return (object)[
			'status' => true,
			'error',
			'data' => $password,
		];
	}

	private function is_captcha()
	{
		if (!$this->core->captcha_check()) {
			return (object)[
				'status' => false,
				'error' => $this->l10n->gettext('error_captcha'),
			];
		}

		return (object)[
			'status' => true,
			'error',
		];
	}

	private function notify($message)
	{
		$this->core->js_notify($message);
	}

	public function content()
	{
		if ($this->is_post()->status) {
		if ($this->is_unauth()->status) {
		if ($this->is_agree_with_rules()->status) {
			$raw_login = $this->db->safesql(@$_POST['login']);
			$raw_email = $this->db->safesql(@$_POST['email']);
			$raw_password = @$_POST['password'];
			$tmp = $this->db->safesql($this->core->random(16));
			$salt = $this->db->safesql($this->core->random());
			$ip = $this->user->ip;
			$gid = ($this->cfg->main['reg_accept'])
				? 1
				: 2;
			$gender_enum = [
				'no_set',
				'male',
				'female'
			];

			$login = ($this->is_vl($raw_login)->status)
				? ($this->is_vl($raw_login)->data)
				: false;
			$email = ($this->is_ve($raw_email)->status)
				? ($this->is_ve($raw_email)->data)
				: false;
			$password = ($this->is_vp($raw_password)->status)
				? ($this->db->safesql($this->core->gen_password($raw_password, $salt)))
				: false;
			$gender = $gender_enum[intval($_POST['gender'])];

			if ($login) {
			if ($email) {
			if ($password) {
			if ($this->is_captcha()) {
				$ctables = $this->cfg->db['tables'];
				$us_f = $ctables['users']['fields'];
				$ic_f = $ctables['iconomy']['fields'];
				$time = time();

				if (!$this->db->query(
					"INSERT INTO `{$this->cfg->tabname('users')}` (
						`{$us_f['group']}`, `{$us_f['login']}`, `{$us_f['email']}`, `{$us_f['pass']}`, `{$us_f['uuid']}`,
						`{$us_f['salt']}`, `{$us_f['tmp']}`, `{$us_f['ip_last']}`, `{$us_f['date_reg']}`, `{$us_f['gender']}`
					) VALUES (
						'$gid', '$login', '$email', '$password', UNHEX(REPLACE(UUID(), '-', '')), 
						'$salt', '$tmp', '$ip', $time, '$gender'
					)"
				)) {
					// Говорим юзверю, что такой логин, мыло или ююайди уже занят
					$this->notify($this->l10n->gettext('login-email_exist_error'));
				}

				$id = $this->db->insert_id();

				if (!$this->db->query(
					"INSERT INTO `{$this->cfg->tabname('iconomy')}` (`{$ic_f['login']}`) 
					VALUES ( '$login')"
				)) {
					$this->notify($this->l10n->gettext('error_sql_critical'));
				}

				// Лог действия
				$this->db->actlog($this->l10n->gettext('log_reg'), $id);

				if ($this->cfg->main['reg_accept']) {
					$data_mail = [
						"LINK" => $this->cfg->main['s_root_full'].BASE_URL.'?mode=register&op=accept&key='.$id.'_'.md5($salt),
						"SITENAME" => $this->cfg->main['s_name'],
						"SITEURL" => $this->cfg->main['s_root_full'].BASE_URL,
						"LNG" => $this->l10n,
					];

					$message = $this->core->sp(MCR_THEME_PATH."modules/register/body.mail.html", $data_mail);

					if (!$this->core->send_mail($email, $this->l10n->gettext('msg_title'), $message)) {
						$this->core->js_notify($this->l10n->gettext('e_mail_send'));
					}

					$this->core->js_notify('', $this->l10n->gettext('sended_mail'), true);
				}

				$this->core->js_notify($this->l10n->gettext('error_success'), $this->l10n->gettext('registratin_success'), true);
			} else { $this->notify($this->is_captcha()->error); }
			} else { $this->notify($this->is_vp($raw_password)->error); }
			} else { $this->notify($this->is_ve($raw_email)->error); }
			} else { $this->notify($this->is_vl($raw_login)->error); }
		} else { $this->notify($this->is_agree_with_rules()->error); }
		} else { $this->notify($this->is_auth()->error); }
		} else { $this->notify($this->is_post()->error); }
	}
}
