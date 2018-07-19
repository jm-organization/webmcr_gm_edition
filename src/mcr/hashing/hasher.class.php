<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 01.07.2018
 * @Time         : 14:28
 *
 * @Documentation:
 */

namespace mcr\hashing;


if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

interface hasher
{
	/**
	 * Возвращает информацию о полученном хешированом значении
	 *
	 * @param  string $hashedValue
	 *
	 * @return array
	 */
	public function info($hashedValue);

	/**
	 * Хеширует полученное значение
	 *
	 * @param  string $value
	 * @param  array  $options
	 *
	 * @return string
	 */
	public function make($value, array $options = []);

	/**
	 * Проверяет хешированое значение
	 *
	 * @param  string $value
	 * @param  string $hashedValue
	 * @param  array  $options
	 *
	 * @return bool
	 */
	public function check($value, $hashedValue, array $options = []);
}