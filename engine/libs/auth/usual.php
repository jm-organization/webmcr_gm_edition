<?php

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class auth
{
	private $core, $db, $user, $cfg, $l10n;

	public function __construct(core $core)
	{
		$this->core = $core;
		$this->db = $core->db;
		$this->user = $core->user;
		$this->cfg = $core->cfg;
		$this->l10n = $core->l10n;
	}

	public function createTmp()
	{
		return $this->core->random(16);
	}

	public function createHash($password, $salt = '')
	{

		return $this->core->gen_password($password, $salt);
	}

	public function authentificate($post_password, $password, $salt = '')
	{
		$post_password = $this->createHash($post_password, $salt);

		return ($post_password === $password) ? true : false;
	}
}

?>