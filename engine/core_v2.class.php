<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 26.06.2018
 * @Time         : 20:45
 *
 * @Documentation:
 */

namespace mcr;

use mcr\db\mysqli_db_driver;

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

global $configs;

define("INSTALLED", $configs->main['install']);

class core_v2
{
	use l10n;


	public $configs = null;

	public $document = '';

	public $db = null;

	public $log = null;

	public $csrf_time = 3600;


	public function __construct(config $configs, log $log)
	{
		$this->configs = $configs;

		$this->db = new mysqli_db_driver($this->configs->db['host'], $this->configs->db['user'], $this->configs->db['pass'], $this->configs->db['base'], $this->configs->db['port'], $this);

		$this->log = $log;

		$base_url = (INSTALLED) ? $this->cfg->main['s_root'] : $this->base_url();

		/*// Generate CSRF Secure key
		define("MCR_SECURE_KEY", $this->gen_csrf_secure());

		// System constants
		define('MCR_LANG', $this->cfg->main['s_lang']);
		define('MCR_LANG_DIR', MCR_LANG_PATH . MCR_LANG . '/');
		define('MCR_THEME_PATH', MCR_ROOT . 'themes/' . $this->cfg->main['s_theme'] . '/');
		define('MCR_THEME_MOD', MCR_THEME_PATH . 'modules/');
		define('MCR_THEME_BLOCK', MCR_THEME_PATH . 'blocks/');
		define('BASE_URL', $base_url);
		define('ADMIN_MOD', 'mode=admin');
		define('ADMIN_URL', BASE_URL . '?' . ADMIN_MOD);
		define('MOD_URL', (isset($_GET['mode'])) ? BASE_URL . '?mode=' . filter($_GET['mode'], 'chars') : BASE_URL . '?mode=' . $this->cfg->main['s_dpage']);
		define('STYLE_URL', BASE_URL . 'themes/' . $this->cfg->main['s_theme'] . '/');
		define('UPLOAD_URL', BASE_URL . 'uploads/');
		define('SKIN_URL', BASE_URL . $this->cfg->main['skin_path']);
		define('CLOAK_URL', BASE_URL . $this->cfg->main['cloak_path']);
		define('LANG_URL', BASE_URL . 'language/' . MCR_LANG . '/');
		define('MCR_SKIN_PATH', MCR_ROOT . $this->cfg->main['skin_path']);
		define('MCR_CLOAK_PATH', MCR_ROOT . $this->cfg->main['cloak_path']);*/
	}

	/**
	 * Адрес сайта по умолчанию
	 * @return String - адрес сайта
	 */
	public function base_url()
	{
		$pos = strripos($_SERVER['PHP_SELF'], 'install/index.php');

		if ($pos === false) {
			$pos = strripos($_SERVER['PHP_SELF'], 'index.php');
		}

		return mb_substr($_SERVER['PHP_SELF'], 0, $pos, 'UTF-8');
	}

	public function version()
	{
		echo VERSION;
	}

	public function run()
	{
		//echo $this->document;
		$this->version();
	}
}