<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 01.07.2018
 * @Time         : 14:33
 *
 * @Documentation:
 */

namespace mcr\hashing;

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class bcrypt_hasher extends adstract_hasher implements hasher
{
	/**
	 * The default cost factor.
	 *
	 * @var int
	 */
	protected $rounds = 10;

	/**
	 * Хеширует полученное значение
	 *
	 * @param  string $value
	 * @param  array  $options
	 *
	 * @return string
	 * @throws hashing_exception
	 */
	public function make($value, array $options = [])
	{
		$cost = isset($options['rounds']) ? $options['rounds'] : $this->rounds;

		$hash = password_hash($value, PASSWORD_BCRYPT, ['cost' => $cost]);

		if ($hash === false) {
			throw new hashing_exception('Bcrypt hashing not supported.');
		}

		return $hash;
	}

	/**
	 * Set the default password work factor.
	 *
	 * @param  int  $rounds
	 * @return $this
	 */
	public function setRounds($rounds)
	{
		$this->rounds = (int) $rounds;

		return $this;
	}
}