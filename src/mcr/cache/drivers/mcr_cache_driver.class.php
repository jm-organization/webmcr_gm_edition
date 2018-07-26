<?php
/**
 * Copyright (c) 2018.
 * MagicMCR является отдельным и независимым продуктом.
 * Исходный код распространяется под лицензией GNU General Public License v3.0.
 *
 * MagicMCR не является копией оригинального движка WebMCR, а лишь его подверсией.
 * Разработка MagicMCR производится исключительно в частных интересах. Разработчики, а также лица,
 * участвующие в разработке и поддержке, не несут ответственности за проблемы, возникшие с движком.
 */

/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 21.07.2018
 * @Time         : 18:42
 *
 * @Documentation: Взаимодействует с хранилищем кеша
 */

namespace mcr\cache\drivers;


use mcr\cache\cache_driver;
use mcr\cache\cache_exception;
use mcr\cache\cache_value;
use mcr\filesystem\storage_adapter;

class mcr_cache_driver implements cache_driver
{
	use storage_adapter;

	/**
	 * Проверяет валидность ключа.
	 * Возвращает true если ключ верный,
	 * иначе false.
	 *
	 * Ключ должен соответсвовать шаблону a-zA-Z0-9_\.
	 * и быть длиной от 1 до 64 символов.
	 *
	 * @param string $key - ключ (имя) кеша
	 *
	 * @return bool
	 */
	public function key_is_valid($key)
	{
		if (preg_match(self::key_pattern, $key) == 1) return true;

		return false;
	}

	/**
	 * Проверяет существование кеша.
	 * Если кеш не найден, то вернёт false,
	 * иначе true
	 *
	 * @param string $key - ключ (имя) кеша
	 *
	 * @return bool
	 */
	public function exist($key)
	{
		$filename = $this->dot_to_slash($key);

		if (file_exists(self::patch . $filename)) return true;

		return false;
	}

	private function get_full_filename($key)
	{
		// Получаем имя файла кеша
		$path = explode('.', $key);
		$cache_file = array_pop($path);

		// удаляем имя файла кеша из относительного пути к кешу
		$path = str_replace(".$cache_file", '', $key);

		// получаем папку, где хранится кеш
		$folder = $this->folder($path, self::patch);

		// Возвращаем полное имя кеша:
		// Путь к нему + имя кеша (файла).
		return $folder . $cache_file;
	}

	/**
	 * Устанавлвиает новый кеш.
	 * Если такой кеш уже существует, то перезапишет его.
	 *
	 * Для обновления используйте метод put
	 *
	 * @param string      $key   - ключ (имя) кеша
	 * @param cache_value $value - кешируемое значение.
	 *
	 * @return bool|int
	 * @throws cache_exception
	 */
	public function set($key, cache_value $value)
	{
		if ($this->key_is_valid($key)) {
			$filename = $this->get_full_filename($key);

			return file_put_contents($filename, $value);
		}

		throw new cache_exception("The cache key is invalid. (`$key` given).");
	}

	/**
	 * Возвращает значение кеша $key
	 * Вернёт ошибку, если кеш не найден.
	 *
	 * @param string $key - ключ кеша, его имя
	 *
	 * @return cache_value
	 * @throws cache_exception
	 */
	public function get($key)
	{
		if ($this->key_is_valid($key) && $this->exist($key)) {
			$filename = $this->get_full_filename($key);

			$value = new cache_value(file_get_contents($filename));

			return $value;
		}

		throw new cache_exception("Unknown cache key. (`$key` given).");
	}

	/**
	 * Удаляет кеш $key
	 *
	 * @param string $key - имя кеша, который будет удалён
	 *
	 * @return bool
	 * @throws cache_exception
	 */
	public function delete($key)
	{
		if ($this->key_is_valid($key) && $this->exist($key)) {
			$filename = $this->get_full_filename($key);

			return unlink($filename);
		}

		throw new cache_exception("Unknown cache key. (`$key` given).");
	}
}