<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 10.06.2018
 * @Time         : 16:59
 *
 * @Documentation:
 */

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class submodule
{
	private $core, $user, $l10n;

	public function __construct(core$core)
	{
		$this->core = $core;
		$this->user = $core->user;
		$this->l10n = $core->l10n;

		if (!$this->user->is_auth || !$this->core->is_access('sys_adm_main')) {
			$this->core->js_notify($this->l10n->gettext('error_403'));
		}
	}

	private function get_theme($theme_cod_name)
	{
		$theme_root = MCR_ROOT . 'themes/' . $theme_cod_name . '/theme.php';

		require_once $theme_root;

		return $theme;
	}

	public function content()
	{
		$theme_cod_name = $_GET['theme'];
		$theme = [];

		if (file_exists(MCR_ROOT . 'themes/' . $theme_cod_name)) {
			$theme = $this->get_theme($theme_cod_name);
		}

		echo json_encode($theme, JSON_UNESCAPED_UNICODE); exit;
	}
}