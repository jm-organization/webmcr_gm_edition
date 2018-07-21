<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 21.07.2018
 * @Time         : 18:41
 *
 * @Documentation:
 */

namespace mcr\cache;


interface cache_driver
{
	const patch = MCR_CACHE_PATH;

	const key_pattern = '/[a-zA-Z0-9_\.]{1,64}/s';

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
	public function key_is_valid($key);

	/**
	 * Проверяет существование кеша.
	 * Если кеш не найден, то вернёт false,
	 * иначе true
	 *
	 * @param string $key - ключ (имя) кеша
	 *
	 * @return bool
	 */
	public function exist($key);

	/**
	 * Устанавлвиает новый кеш.
	 * Если такой кеш уже существует, то перезапишет его.
	 *
	 * Для обновления используйте метод put
	 *
	 * @param string 		$key	- ключ (имя) кеша
	 * @param cache_value  	$value - кешируемое значение.
	 */
	public function set($key, cache_value $value);

	/**
	 * Возвращает значение кеша $key
	 * Вернёт ошибку, если кеш не найден.
	 *
	 * @param string $key 	- ключ кеша, его имя
	 *
	 * @return cache_value
	 */
	public function get($key);

	/**
	 * Удаляет кеш $key
	 *
	 * @param string $key - имя кеша, который будет удалён
	 */
	public function delete($key);
}