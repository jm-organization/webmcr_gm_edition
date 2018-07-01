<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 01.07.2018
 * @Time         : 14:31
 *
 * @Documentation:
 */

namespace mcr\hashing;


abstract class adstract_hasher
{
	/**
	 * Возвращает информацию о полученном хешированом значении
	 *
	 * @param  string $hashedValue
	 *
	 * @return array
	 */
	public function info($hashedValue)
	{
		return password_get_info($hashedValue);
	}

	/**
	 * Проверяет хешированое значение
	 *
	 * @param  string $value
	 * @param  string $hashedValue
	 * @param  array  $options
	 *
	 * @return bool
	 */
	public function check($value, $hashedValue, array $options = [])
	{
		if (strlen($hashedValue) === 0) {
			return false;
		}

		return password_verify($value, $hashedValue);
	}
}