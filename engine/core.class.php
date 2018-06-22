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

class core
{
	// Set default scope and values
	public $bc, $title, $header, $r_block, $l_block, $menu, $admin_menu;
	public $def_header = '';
	public $db, $user, $cfg = false;
	public $cfg_m, $cfg_b = array();
	public $l10n;
	public $log;
	public $csrf_time = 3600;
	public $captcha = array(
		0 => "---",
		1 => "ReCaptcha",
		2 => "KeyCaptcha"
	);

	public function __construct()
	{
		require(MCR_TOOL_PATH . 'filter.class.php'); // Load filter function
		require(MCR_TOOL_PATH . 'config.class.php'); // Load Configs class
		$this->cfg = new config();

		// Load log class;
		require_once(MCR_TOOL_PATH . 'log.class.php');
		$this->log = new log($this->cfg->main['debug'], log::L_ALL);

		register_shutdown_function(function () {

			$error = error_get_last();

			if (!empty($error)) {
				$this->log->write($error['message'], $error['type'], $error['file'], $error['line']);

				// TODO: Сделать обработчик критических ошибок с выводом на экран!!!
				if ($error['type'] == log::FATAL_ERROR) {
					echo 'fatal error';
				}
			}

			return null;

		});

		define("INSTALLED", $this->cfg->main['install']);
		// Load database class
		require(MCR_TOOL_PATH . 'db/' . $this->cfg->db['backend'] . '.class.php');
		// Create & set new object of database
		$this->db = new db($this->cfg->db['host'], $this->cfg->db['user'], $this->cfg->db['pass'], $this->cfg->db['base'], $this->cfg->db['port'], $this);

		// Load class l10n - Localization
		require(MCR_TOOL_PATH . 'l10n.class.php');
		$this->l10n = new l10n($this);
		$this->title = $this->l10n->gettext('home');

		// Load user class
		require_once(MCR_TOOL_PATH . 'user.class.php');
		// Create & set new object of user
		$this->user = new user($this);

		// Load menu class
		require_once(MCR_TOOL_PATH . 'menu.class.php');
		// Create & set new object of menu
		$this->menu = new menu($this);

		$base_url = ($this->cfg->main['install']) ? $this->cfg->main['s_root'] : $this->base_url();

		// Generate CSRF Secure key
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
		define('MCR_CLOAK_PATH', MCR_ROOT . $this->cfg->main['cloak_path']);
		$bc = array(
			$this->l10n->gettext('error_message') => BASE_URL,
		);
		$this->bc = $this->gen_bc($bc);
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

	/**
	 * Генерация защиты от CSRF
	 * @return String - ключ защиты
	 */
	public function gen_csrf_secure()
	{
		$time = time();
		$new_key = $time . '_' . md5($this->user->ip . $this->cfg->main['mcr_secury'] . $time);

		if (!isset($_COOKIE['mcr_secure'])) {
			setcookie("mcr_secure", $new_key, time() + $this->csrf_time, '/');
			return $new_key;
		}

		$cookie = explode('_', $_COOKIE['mcr_secure']);
		$old_time = intval($cookie[0]);
		$old_key = md5($this->user->ip . $this->cfg->main['mcr_secury'] . $old_time);

		if (!isset($cookie[1]) || $cookie[1] !== $old_key || ($old_time + $this->csrf_time) < $time) {
			setcookie("mcr_secure", $new_key, time() + $this->csrf_time, '/');
			return $new_key;
		}

		return $_COOKIE['mcr_secure'];
	}

	/**
	 * Генератор хлебных крошек
	 * @param array $array - массив элементов списка
	 * @return string
	 */
	public function gen_bc($array = array())
	{
		$data['LIST'] = $this->gen_bc_list($array);

		if (!$this->cfg->func['breadcrumbs']) return false;

		return $this->sp(MCR_THEME_PATH . "breadcrumbs/list.phtml", $data);
	}

	/**
	 * Генератор списка хлебных крошек
	 *
	 * @param array $array - массив элементов списка
	 *
	 * @return string
	 */
	private function gen_bc_list($array = array())
	{
		if (empty($array)) return null;
		$count = count($array) - 1;
		$i = 0;
		$string = '';

		ob_start();

		foreach ($array as $title => $url) {
			$string .= ($i == 0)
				? $title
				: ' — ' . $title;

			if ($count == $i) {
				echo $this->sp(MCR_THEME_PATH . "breadcrumbs/id-active.phtml", array("TITLE" => $title));
			} else {
				$data['TITLE'] = $title;
				$data['URL'] = $url;
				echo $this->sp(MCR_THEME_PATH . "breadcrumbs/id-inactive.phtml", $data);
			}

			$i++;
		}

		$this->title = $this->db->HSC($string);

		return ob_get_clean();
	}

	/**
	 * Загрузка статической страницы
	 * @param String $path - путь к файлу
	 * @param array $data - параметры, передаваемые через массив
	 * @return string
	 */
	public function sp($path, $data = array())
	{
		ob_start();

		include($path);

		return ob_get_clean();
	}

	function callback()
	{
		return null;
	}

	public function is_install($callback = 'callback')
	{
		$this->magic_if(INSTALLED, $callback);
	}

	public function magic_if($condition, $callback = 'callback')
	{
		if ($condition) {
			$callback();
		}
	}

	/**
	 * Генерация AJAX оповещений
	 * @param String $title - Название
	 * @param String $message - Сообщение
	 * @param Boolean $type - Тип ошибки (true|false - Истина|Ложь)
	 * @param array $data - Основное содержимое оповещения и доп. поля
	 */
	public function js_notify($message = '', $title = '', $type = false, $data = array())
	{
		if (empty($title)) {
			$title = $this->l10n->gettext('error_message');
		}

		$data = array(
			"_title" => $title,
			"_message" => $message,
			"_type" => $type,
			"_data" => $data
		);

		echo json_encode($data);

		exit;
	}

	public function colorize($str, $color, $format = '<span style="color: {COLOR};">{STRING}</span>')
	{
		return str_replace(array('{COLOR}', '{STRING}'), array($color, $str), $format);
	}

	/**
	 * pagination(@param) - Pagination method
	 *
	 * @param Integer $res - Кол-во результатов на страницу
	 * @param String $page - Адрес страниц без идентификаторов (YOUR_PAGE)
	 * @param Integer $count - Кол-во результатов в базе
	 * @param String $theme - нестандартный шаблон
	 *
	 * @return String - результаты
	 *
	 */
	public function pagination($res = 10, $page = '', $count = 0, $theme = '')
	{
		if ($this->db === false) {
			return null;
		}

		$pid = (isset($_GET['pid'])) // anchor to TODO #(no more max pages in pginator)
			? intval($_GET['pid'])
			: 1;

		$start = $pid * $res - $res;
		if ($page === 0 && $count === 0) {
			return $start;
		}

		$max = intval(ceil($count / $res));

		if ($pid <= 0 || $pid > $max) {
			return null;
		}

		if ($max > 1) {
			$path = (empty($theme)) ? MCR_THEME_PATH . "pagination/" : $theme;


			// First page +
			$fp_data = array(
				"URL" => BASE_URL . $page . '1',
				"VALUE" => "<<"
			);
			$page_first = $this->sp($path . "page-id.phtml", $fp_data);
			// First page +

			// Prev pages +
			$page_prev = '';
			for ($pp = $this->cfg->pagin['arrows']; $pp > 0; $pp--) {
				if ($pid - $pp <= 0) {
					continue;
				}
				$pp_data = array(
					"URL" => BASE_URL . $page . ($pid - $pp),
					"VALUE" => $pid - $pp
				);
				$page_prev .= $this->sp($path . "page-id.phtml", $pp_data);
			}
			// Prev pages -

			// Selected page +
			$tp_data = array(
				"URL" => BASE_URL . $page . $pid,
				"VALUE" => $pid
			);
			$page_this = $this->sp($path . "page-id-this.phtml", $tp_data);
			// Selected page -

			// Next pages +
			$page_next = '';
			for ($np = 1; $np <= $this->cfg->pagin['arrows']; $np++) {
				if ($pid + $np > $max) {
					continue;
				}
				$np_data = array(
					"URL" => BASE_URL . $page . ($pid + $np),
					"VALUE" => $pid + $np
				);
				$page_next .= $this->sp($path . "page-id.phtml", $np_data);
			}
			// Next pages -

			// Last page +
			$lp_data = array(
				"URL" => BASE_URL . $page . $max,
				"VALUE" => ">>"
			);
			$page_last = $this->sp($path . "page-id.phtml", $lp_data);
			// Last page -


			$data = array(
				"PAGE_FIRST" => $page_first,
				"PAGE_PREV" => $page_prev,
				"PAGE_THIS" => $page_this,
				"PAGE_NEXT" => $page_next,
				"PAGE_LAST" => $page_last
			);

			return $this->sp($path . "object.phtml", $data);
		}

		return null;
	}

	/**
	 * Загрузка класса BB кодов
	 * @return object
	 */
	public function load_bb_class()
	{
		include(MCR_TOOL_PATH . 'libs/bbcode.parse.php');

		return new bbcode($this);
	}

	public function csrf_whitelist_add($ip = '127.0.0.1')
	{
		$whitelist = explode(',', $this->cfg->func['whitelist']);
		if (in_array($ip, $whitelist)) {
			return false;
		}

		$whitelist[] = $ip;
		$this->cfg->func['whitelist'] = implode(',', $whitelist);
		if (!$this->cfg->savecfg($this->cfg->func, 'functions.php', 'func')) {
			return false;
		}

		return true;
	}

	/**
	 * Валидатор защиты от CSRF атаки
	 * При ошибке возвращается на главную страницу с сообщение "Hacking Attempt!"
	 */
	public function csrf_check()
	{
		if (in_array($this->user->ip, explode(',', $this->cfg->func['whitelist']))) return null;

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			if (!isset($_POST['mcr_secure'])) $this->notify($this->l10n->gettext('error_hack'));

			$secure_key = explode('_', $_POST['mcr_secure']);
			if (!isset($secure_key[1])) $this->notify($this->l10n->gettext('error_hack'));

			$secure_time = intval($secure_key[0]);
			if (($secure_time + $this->csrf_time) < time()) $this->notify($this->l10n->gettext('error_hack'));
			$secure_var = $secure_key[1];

			$mcr_secure = $secure_time . '_' . md5($this->user->ip . $this->cfg->main['mcr_secury'] . $secure_time);

			if ($mcr_secure !== $_POST['mcr_secure']) $this->notify($this->l10n->gettext('error_hack'));
		}
	}

	/**
	 * Генерация основных оповещений движка
	 * @param String $title - Название оповещения
	 * @param String $text - Текст оповещения
	 * @param Integer $type - Тип оповещения (1 - Warning | 2 - Error | 3 - Success | 4 - Info)
	 * @param String $url - URL путь, куда будет направлено оповещение
	 * @param Boolean $out - указывается, если URL является внешним и будет начинаться с http
	 */
	public function notify($title = '', $text = '', $type = 2, $url = '', $out = false)
	{
		$url = (!$out) ? $this->base_url() . $url : $url;
		if ($out || (empty($title) && empty($text))) {
			header("Location: " . $url);
			exit;
		}

		switch ($type) {
			case 2:
				$_SESSION['notify_type'] = 'alert-error';
				break;
			case 3:
				$_SESSION['notify_type'] = 'alert-success';
				break;
			case 4:
				$_SESSION['notify_type'] = 'alert-info';
				break;
			default:
				$_SESSION['notify_type'] = '';
				break;
		}

		$_SESSION['mcr_notify'] = true;
		$_SESSION['notify_title'] = $title;
		$_SESSION['notify_msg'] = $text;

		$this->redirect($url);

		exit;
	}

	public function redirect($url)
	{
		header("Location: $url");
	}

	/**
	 * Генератор случайной строки
	 * @param $length - длина строки (integer)
	 * @param $safe - По умолчанию строка будет состоять только из латинских букв и цифр (boolean)
	 * @return String
	 */
	public function random($length = 10, $safe = true)
	{
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPRQSTUVWXYZ0123456789";
		if (!$safe) {
			$chars .= '$()#@!';
		}
		$string = "";
		$len = strlen($chars) - 1;
		while (strlen($string) < $length) {
			$string .= $chars[mt_rand(0, $len)];
		}
		return $string;
	}

	public function check_cfg_block($cfg)
	{
		$format = array(
			'ENABLE',
			'POSITION',
			'TITLE',
			'DESC',
			'AUTHOR',
			'SITE',
			'EMAIL',
			'VERSION',
			'UPDATES',
			'UPDATER'
		);
		$result = true;
		foreach ($format as $key => $val) {
			if (!isset($cfg[$val])) {
				$result = false;
			}
		}
		return $result;
	}

	/**
	 * Подгрузчик модулей
	 *
	 * @param String $mode - название модуля
	 *
	 * @return string
	 */
	public function load_mode($mode)
	{
		if (!preg_match("/^\w+$/i", $mode) || !file_exists(MCR_MODE_PATH . $mode . ".php")) {
			$this->title = $this->l10n->gettext('error_mode_found');
			return $this->sp(MCR_THEME_PATH . "default_sp/404.phtml");
		}

		if (!file_exists(MCR_CONF_PATH . 'modules/' . $mode . '.php')) {
			return $this->sp(MCR_THEME_PATH . "default_sp/mod_disable.phtml");
		}
		require_once(MCR_CONF_PATH . 'modules/' . $mode . '.php');
		if (!isset($cfg) || !$this->check_cfg($cfg) || !$cfg['MOD_ENABLE']) {
			return $this->sp(MCR_THEME_PATH . "default_sp/mod_disable.phtml");
		}
		include_once(MCR_MODE_PATH . $mode . ".php");
		if (!class_exists("module")) {
			return sprintf($this->l10n->gettext('error_mode_found'), 'module');
		}
		$this->cfg_m = $cfg;
		$module = new module($this);

		if (!method_exists($module, "content")) {
			return sprintf($this->l10n->gettext('method_not_found'), 'content');
		}
		return $module->content();
	}

	public function check_cfg($cfg)
	{
		$validator = array(
			'MOD_ENABLE',
			'MOD_TITLE',
			'MOD_DESC',
			'MOD_AUTHOR',
			'MOD_SITE',
			'MOD_EMAIL',
			'MOD_VERSION',
			'MOD_URL_UPDATE',
			'MOD_CHECK_UPDATE',
		);
		$result = true;
		foreach ($validator as $key => $val) {
			if (!isset($cfg[$val])) {
				$result = false;
			}
		}
		return $result;
	}

	/**
	 * Системный генератор хэшей паролей пользователей
	 * @param String $string - исходный пароль
	 * @param String $salt - соль
	 * @return String
	 */
	public function gen_password($string = '', $salt = '', $crypt = false)
	{
		if ($crypt === false) {
			$crypt = $this->cfg->main['crypt'];
		}
		switch ($crypt) {
			case 1:
				return sha1($string);
				break;
			case 2:
				return hash('sha256', $string);
				break;
			case 3:
				return hash('sha512', $string);
				break;
			case 4:
				return md5(md5($string));
				break;
			case 5:
				return md5($string . $salt);
				break; // Joomla
			case 6:
				return md5($salt . $string);
				break; // osCommerce, TBDev
			case 7:
				return md5(md5($salt) . $string);
				break; // vBulletin, IceBB, Discuz
			case 8:
				return md5(md5($string) . $salt);
				break;
			case 9:
				return md5($string . md5($salt));
				break;
			case 10:
				return md5($salt . md5($string));
				break;
			case 11:
				return sha1($string . $salt);
				break;
			case 12:
				return sha1($salt . $string);
				break;
			case 13:
				return md5(md5($salt) . md5($string));
				break; // ipb, MyBB
			case 14:
				return hash('sha256', $string . $salt);
				break;
			case 15:
				return hash('sha512', $string . $salt);
				break;
			default:
				return md5($string);
				break;
		}
	}

	/**
	 * Подгрузчик модулей по умолчанию (прямой загрузчик без лишних проверок)
	 *
	 * @param String $mode - название модуля
	 *
	 * @return string
	 */
	public function load_def_mode($mode)
	{
		include_once(MCR_MODE_PATH . $mode . ".php");
		require_once(MCR_CONF_PATH . 'modules/' . $mode . '.php');
		require(MCR_LANG_PATH . $this->cfg->main['s_lang'] . '/' . $mode . '.php');
		global $cfg;

		if ($cfg['MOD_ENABLE']) return $this->sp(MCR_THEME_PATH . "default_sp/mod_disable.phtml");

		$this->cfg_m = $cfg;
		$module = new module($this);

		return $module->content();
	}

	/**
	 * Загрузчик блоков боковой панели
	 *
	 * @return bool|string
	 */
	public function load_def_blocks($is_all = true, $block = '')
	{
		$format = array('ENABLE', 'POSITION', 'TITLE', 'DESC', 'AUTHOR', 'SITE', 'EMAIL', 'VERSION', 'UPDATES', 'UPDATER');
		$content = '';
		$blocks = array();

		if ($is_all) {
			$block_config = scandir(MCR_CONF_PATH . 'blocks');
			if (empty($block_config)) {
				return false;
			}

			foreach ($block_config as $file) {
				$this->cfg_b = $cfg = array();
				if ($file == '.' || $file == '..' || substr($file, -4) != '.php') continue;

				include(MCR_CONF_PATH . 'blocks/' . $file);
				if ($cfg == false) continue;

				$block_config_params = array_keys($cfg);
				$diff = array_diff($format, $block_config_params);
				if (!empty($diff) || !$cfg['ENABLE']) continue;

				if (!file_exists(MCR_SIDE_PATH . $file)) continue;
				include(MCR_SIDE_PATH . $file);


				$this->cfg_b = $cfg;
				$classname = 'block_' . substr($file, 0, -4);
				if (!class_exists($classname)) continue;

				$block_class = new $classname($this);
				if (!method_exists($block_class, 'content')) {
					unset($block_class);
					continue;
				}

				$blocks[$cfg['POSITION']] = $block_class->content();

				unset($block_class);
			}
		} else {
			$block_config = MCR_CONF_PATH . 'blocks/' . $block . '.php';
			$block_file_class = MCR_SIDE_PATH . $block . '.php';
			global $cfg;

			if (file_exists($block_config)) {
				include($block_config);
			}

			if ($cfg['ENABLE']) {
				if (file_exists($block_file_class)) {
					include($block_file_class);
				}

				$classname = 'block_' . $block;

				if (class_exists($classname)) {
					$block_class = new $classname($this);

					if (!method_exists($block_class, 'content')) {
						unset($block_class);

						$content .= '';
					}

					$blocks[$cfg['POSITION']] = $block_class->content();
				}

				unset($block_class);
			}

			$content .= '';
		}

		ksort($blocks);

		foreach ($blocks as $key => $val) {
			$content .= $val;
		}

		return $content;
	}

	/**
	 * Загрузка советов
	 * @return string
	 */
	public function advice()
	{
		if (!$this->cfg->func['advice']) {
			return '';
		}
		$data = file(MCR_THEME_PATH . "default_sp/advice.txt");
		$size = count($data);
		$sp_data["ADVICE"] = ($size <= 0) ? $this->lng['e_advice_found'] : $data[rand(0, $size - 1)];
		return $this->sp(MCR_THEME_PATH . "default_sp/advice.phtml", $sp_data);
	}

	/**
	 * Поиск размеров скина или плаща по форматам
	 *
	 * @param $width - width of skin
	 * @param $height - height of skin
	 *
	 * @return bool
	 *
	 */
	public function find_in_formats($width, $height)
	{
		foreach ($this->get_array_formats() as $key => $value) {
			if ($value["skin_w"] == $width && $value["skin_h"] == $height) {
				return $key;
			}
		}

		return false;
	}

	/**
	 * Получить массив доступных форматов скинов и плащей
	 * @param formats (array)
	 *
	 */
	public function get_array_formats($hd = false)
	{
		$w = 64;
		$h = 32;
		$c_w = ($hd) ? 64 : 22;
		$c_h = ($hd) ? 32 : 17;
		$i = 1;
		$array = array();
		$skin_h = $h;
		$skin_w = $w;
		$cloak_w = $c_w;
		$cloak_h = $c_h;
		while ($i <= 32) {
			$skin_w = $i * $w;
			$skin_h = $i * $h;
			$cloak_w = $i * $c_w;
			$cloak_h = $i * $c_h;
			$array[$i] = array(
				"skin_w" => $skin_w,
				"skin_h" => $skin_h,
				"cloak_w" => $cloak_w,
				"cloak_h" => $cloak_h
			);
			$i = ($i < 2) ? $i + 1 : $i + 2;
		}
		return $array;
	}

	/**
	 * Поворот изображения по заданым параметрам из исходного изображения
	 */
	public function imageflip(&$result, &$img, $rx = 0, $ry = 0, $x = 0, $y = 0, $size_x = null, $size_y = null)
	{
		if ($size_x < 1) {
			$size_x = imagesx($img);
		}
		if ($size_y < 1) {
			$size_y = imagesy($img);
		}
		imagecopyresampled($result, $img, $rx, $ry, ($x + $size_x - 1), $y, $size_x, $size_y, 0 - $size_x, $size_y);
	}

	/**
	 * Отправка почты через PHPMailer
	 * @param String $to - кому
	 * @param String $subject - тема письма
	 * @param String $message - текст сообщения
	 * @param String $altmessage - альтернативное сообщение
	 * @param Boolean $smtp - отправка почты через SMTP
	 * @param Boolean $cc - отправлять копию письма
	 * @return Boolean
	 */
	public function send_mail($to, $subject = '[WebMCR]', $message = '', $altmassage = '', $smtp = false, $cc = false)
	{
		require(MCR_LIBS_PATH . 'smtp/PHPMailerAutoload.php');
		PHPMailerAutoload('smtp');
		include_once(MCR_LIBS_PATH . 'smtp/class.phpmailer.php');
		$mail = new PHPMailer;
		//$mail->SMTPDebug = 3;
		if ($this->cfg->mail['smtp']) {
			$mail->isSMTP();
			$mail->Host = $this->cfg->mail['smtp_host']; // Specify main and backup SMTP servers
			$mail->SMTPAuth = true; // Enable SMTP authentication
			$mail->Username = $this->cfg->mail['smtp_user']; // SMTP username
			$mail->Password = $this->cfg->mail['smtp_pass']; // SMTP password
			$mail->SMTPSecure = ($this->cfg->mail['smtp_tls']) ? 'tls' : 'ssl'; // Enable TLS encryption, `ssl` also accepted
			$mail->Port = 587; // TCP port to connect to
		}
		$mail->CharSet = 'UTF-8';
		$mail->setLanguage('ru', MCR_LANG_DIR . 'smtp/');
		$mail->From = ($this->cfg->mail['smtp']) ? $this->cfg->mail['smtp_user'] : $this->cfg->mail['from'];
		$mail->FromName = $this->cfg->mail['from_name'];
		if (is_array($to)) {
			foreach ($to as $key => $value) {
				$mail->addAddress($value);
			}
		} else {
			$mail->addAddress($to);
		}

		$mail->addReplyTo($this->cfg->mail['reply'], $this->cfg->mail['reply_name']);
		if ($this->cfg->mail['cc']) {
			$mail->addCC($this->cfg->mail['from']);
		}
		//$mail->addBCC($this->cfg->mail['bcc']);
		$mail->isHTML(true); // Set email format to HTML
		$mail->Subject = $subject;
		$mail->Body = $message;
		$mail->AltBody = $altmassage;
		return $mail->send();
	}

	public function captcha_check()
	{
		if (!isset($this->captcha[$this->cfg->main['captcha']])) {
			return true;
		}
		switch ($this->cfg->main['captcha']) {
			case 1:
				$response = @$_POST['g-recaptcha-response'];
				$request = file_get_contents(
					"https://www.google.com/recaptcha/api/siteverify?secret="
					. $this->cfg->main['rc_private']
					. "&response="
					. $response
					. "&remoteip="
					. $this->user->ip
				);
				$request = json_decode($request);
				if (!$request->success) {
					return false;
				}
				return true;
				break;
			case 2:
				$response = @$_POST['capcode'];
				require(MCR_TOOL_PATH . 'libs/keycaptcha.php');
				$kc = new KeyCAPTCHA_CLASS('', $this);
				if (!$kc->check_result($response)) {
					return false;
				}
				return true;
				break;
			default:
				return true;
				break;
		}
	}

	public function captcha()
	{
		switch ($this->cfg->main['captcha']) {
			case 1:
				$content = $this->sp(MCR_THEME_PATH . "captcha/recaptcha.phtml");
				break;
			case 2:
				require(MCR_TOOL_PATH . 'libs/keycaptcha.php');
				$kc = new KeyCAPTCHA_CLASS('', $this);
				$data["CONTENT"] = $kc->render_js();
				$content = $this->sp(MCR_THEME_PATH . "captcha/keycaptcha.phtml", $data);
				break;
			default:
				return null;
				break;
		}
		return $content;
	}

	public function safestr($string = '')
	{
		return preg_replace("/[\<\>\"\'\`]+/i", "", $string);
	}

	public function filter_int_array($array)
	{
		if (empty($array)) {
			return false;
		}
		$new_array = array();
		foreach ($array as $key => $value) {
			$new_array[] = intval($value);
		}
		return $new_array;
	}

	public function search()
	{
		if (!$this->is_access('sys_search')) {
			return null;
		}
		$type = (isset($_GET['type'])) ? $_GET['type'] : 'news';
		$data['SEARCH_ELEMENTS'] = $this->search_array($type);
		if (empty($data['SEARCH_ELEMENTS'])) {
			return null;
		}
		return $this->sp(MCR_THEME_MOD . "search/form.phtml", $data);
	}

	public function is_access($name = '')
	{
		if (empty($name)) {
			return false;
		}
		if (!@$this->user->permissions_v2[$name]) {
			return false;
		}
		return true;
	}

	private function search_array($active = 'news')
	{
		if (empty($this->cfg->search)) {
			return null;
		}
		ob_start();
		foreach ($this->cfg->search as $key => $value) {
			if (!$this->is_access($value['permissions'])) {
				continue;
			}
			$data = array(
				"ID" => $key,
				"TITLE" => $value['title'],
				"ACTIVE" => ($key == $active) ? 'active' : ''
			);
			echo $this->sp(MCR_THEME_MOD . "search/elem-id.phtml", $data);
		}
		return ob_get_clean();
	}

	public function perm_list($selected = '')
	{
		$query = $this->db->query("SELECT title, `value` FROM `mcr_permissions` ORDER BY title ASC");
		if (!$query || $this->db->num_rows($query) <= 0) {
			return null;
		}
		ob_start();
		while ($ar = $this->db->fetch_assoc($query)) {
			$title = $this->db->HSC($ar['title']);
			$value = $this->db->HSC($ar['value']);
			$select = ($value == $selected) ? 'selected' : '';
			echo "<option value=\"$value\" $select>$title</option>";
		}
		return ob_get_clean();
	}

	public function validate_perm($perm)
	{
		$perm = $this->db->safesql($perm);
		$query = $this->db->query("SELECT COUNT(*) FROM `mcr_permissions` WHERE `value`='$perm'");
		if (!$query) {
			return false;
		}
		$ar = $this->db->fetch_array($query);
		return ($ar[0] <= 0) ? false : true;
	}

	public function file_manager()
	{
		if (!$this->is_access('sys_adm_manager')) {
			return null;
		}
		return $this->sp(MCR_THEME_PATH . "default_sp/file_manager.phtml");
	}
}
