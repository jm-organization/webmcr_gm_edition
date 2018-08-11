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

use mcr\core\registry\component;

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class bcrypt_hasher extends adstract_hasher implements hasher, component
{
	/**
	 * The default cost factor.
	 *
	 * @var int
	 */
	protected $rounds = 10;

	/**
	 * Мотод должен возвращать строковое
	 * аэстрактное имя комопнента.
	 *
	 * @return string
	 */
	public function get_abstract_name()
	{
		return 'hasher';
	}

	/**
	 * Вызывается, когда происходит
	 * инициализация - добовление компонента
	 * в реестр.
	 *
	 * Должен возвращать экземпляр класса component
	 *
	 * @return component
	 */
	public function boot()
	{
		return $this;
	}

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