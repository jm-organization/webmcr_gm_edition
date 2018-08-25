<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 01.07.2018
 * @Time         : 0:04
 *
 * @Documentation:
 */

namespace mcr\http;


use mcr\auth\auth;

trait csrf
{
	public $csrf_time = 3600;

	private $time = 0000000000;

	/**
	 * Генерирует CSRF ключ.
	 *
	 * @return string
	 */
	public function gen_csrf_key()
	{
		// фиксируем время.
		$this->time = time();
		//генерируем ключ в это время.
		$csrf_key = $this->time . '_' . md5(auth::ip() . APPLICATION_KEY . $this->time);

		// если нет куки с ключём
		if (!isset($_COOKIE['mcr_secure'])) {
			// создаём куку, сохраняя с точным временем
			setcookie("mcr_secure", $csrf_key, time() + $this->csrf_time, '/');

			// возвращяем этот же ключ
			return $csrf_key;
		} else {
			// иначе проверяем ту, что есть
			$cookie = explode('_', $_COOKIE['mcr_secure']);
			$old_time = intval($cookie[0]);
			$old_key = md5(auth::ip() . APPLICATION_KEY . $old_time);

			// если csrf ключ другой, то обновляем куку
			if (!isset($cookie[1]) || $cookie[1] !== $old_key || $this->csrf_is_old($old_time)) {
				setcookie("mcr_secure", $csrf_key, time() + $this->csrf_time, '/');

				return $csrf_key;
			}

			return $_COOKIE['mcr_secure'];
		}
	}

	/**
	 * Добавляет новый ip в белый список
	 *
	 * @param string $ip
	 *
	 * @return bool
	 */
	public function add_ip_to_csrf_whitelist($ip)
	{
		$whitelist = explode(',', config('functions::whitelist'));

		// Если такой ip уже существует в белом списке, то не добавляем его.
		// Выходим из функции.
		if (!in_array($ip, $whitelist)) {
			// Иначе проводим процедуру сохранения.
			global $configs;

			$_functions = config('functions');
			$whitelist[] = $ip;

			$_functions['whitelist'] = implode(',', $whitelist);

			// сохраняем
			//if (!$configs->savecfg($_functions, 'functions.php', 'func')) return false;

			return true;
		}

		return false;
	}

	/**
	 * Валидатор защиты от CSRF атаки
	 * При ошибке возвращается на главную страницу с сообщение "Hacking Attempt!"
	 */
	public function csrf_check()
	{
		$ip_whitelist = explode(',', config('functions::whitelist'));
		if (in_array(auth::ip(), $ip_whitelist)) return true;

		$this->time = time();

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			if (isset($_POST['mcr_secure'])) {
				$secure_key = explode('_', $_POST['mcr_secure']);

				if (isset($secure_key[1])) {

					$secure_time = intval($secure_key[0]);
					if ($this->csrf_is_old($secure_time)) return false;

					$mcr_secure = $secure_time . '_' . md5(auth::ip() . APPLICATION_KEY . $secure_time);
					if ($mcr_secure !== $_POST['mcr_secure']) return false;

				} else {
					return false;
				}
			} else {
				return false;
			}
		}

		return true;
	}

	private function csrf_is_old($time)
	{
		return ($time + $this->csrf_time) < $this->time;
	}
}