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
 * @Time         : 22:26
 *
 * @Documentation: Взаимодействует с значением кеша
 */

namespace mcr\cache;


class cache_value implements \Serializable
{
	/**
	 * @var null
	 */
	private $value = null;

	/**
	 * cache_value constructor.
	 *
	 * @param null $value
	 */
	public function __construct($value = null)
	{
		if ($value != null) $this->set_value($value);
	}

	/**
	 * @param mixed $value
	 */
	public function set_value($value)
	{
		$this->value = $value;
	}

	/**
	 * Сериализирует установленное значение.
	 *
	 * Значение должно быть любым, но не пустым и не resource.
	 * Если быд передан объект,
	 * то он должен реализовывать метод __sleep.
	 *
	 * @return $this
	 * @throws cache_exception
	 */
	public function serialize()
	{
		if (is_object($this->value)) {
			// Проверяем наличие метода __sleep у переданного объекта
			if (!method_exists($this->value, '__sleep')) throw new cache_exception('The Received object \\' . get_class($this->value) . ' for serialization must be a implementing __sleep() method.');
		}

		// Сериализируем и устанавливаем данные.
		$this->set_value(serialize($this->value));

		return $this;
	}

	/**
	 * Constructs the object
	 *
	 * @link  http://php.net/manual/en/serializable.unserialize.php
	 *
	 * @param string $serialized <p>
	 *                           The string representation of the object.
	 *                           </p>
	 *
	 * @return cache_value
	 * @since 5.1.0
	 * @throws cache_exception
	 */
	public function unserialize($serialized)
	{
		if (empty($serialized)) throw new cache_exception('Can`t deserialize empty value. You must pass serialized value.');

		// Десериализируем и устанавливаем данные
		if (false == $value = unserialize($serialized)) throw new cache_exception('Can`t deserialize received value. (Given: ' . var_export($serialized, true) . ')');

		$this->set_value($value);

		return $this;
	}

	/**
	 * Десериализирует установелнное значение.
	 *
	 * Значение должно быть сериализированым.
	 * Если был сериализирован объект,
	 * то он должен реализовывать метод __wakeup.
	 *
	 * @return cache_value
	 * @throws cache_exception
	 */
	public function deserialize()
	{
		return $this->unserialize($this->value);
	}

	/**
	 * Преобразует установленный масив в json.
	 *
	 * @param int $options
	 * @param int $depth
	 *
	 * @return cache_value
	 * @throws cache_exception
	 */
	public function to_json($options = 0, $depth = 512)
	{
		// Проверяем данные.
		if (!is_array($this->value) && !is_integer($this->value) && $this->value !== null)  {
			throw new cache_exception('The Received value must be of array type. (' . gettype($this->value) . ' ' . var_export($this->value, true) . ' given).');
		}

		$value = json_encode($this->value, $options, $depth);

		// Если значение не стандартное,
		// то приводим его к строчному виду,
		// через экспортирование данных.
		if (is_bool($value) || $value == null) {
			$value = var_export($value, true);
		}

		$this->set_value($value);

		return $this;
	}

	/**
	 * @param bool  $assoc
	 * @param int   $depth
	 * @param array $options
	 *
	 * @throws cache_exception
	 */
	private function decode_json($assoc = false, $depth = 512, $options = 0)
	{
		if (!is_string($this->value)) throw new cache_exception('The received value must be of string type. (' . gettype($this->value) . ' ' . var_export($this->value, true) . ' given).');

		$value = json_decode($this->value, $assoc, $depth, $options);

		// Если значение после декодирование отличается
		// проверяем его.
		// Если было возвращено false или пустоту
		// после декодирования выдаём ошибку.
		if ($this->value !== $value && (
			$value == null || $value == false
			)) {
			$type = $assoc ? 'array' : 'object';

			throw new cache_exception('Can`t transform json to ' . $type . '. (' . gettype($this->value) . ' ' . var_export($this->value, true) . ' given).');
		}

		$this->set_value($value);
	}

	/**
	 * Преобразует json обратно в ассоциативный масив.
	 *
	 * @param int   $depth
	 * @param array $options
	 *
	 * @return cache_value
	 * @throws cache_exception
	 */
	public function to_array($depth = 512, $options = 0)
	{
		$this->decode_json(true, $depth, $options);

		return $this;
	}

	/**
	 * Преобразует json в объект.
	 *
	 * @param int   $depth
	 * @param array $options
	 *
	 * @return cache_value
	 * @throws cache_exception
	 */
	public function to_object($depth = 512, $options = 0)
	{
		$this->decode_json(false, $depth, $options);

		return $this;
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return (string) $this->value;
	}

	/**
	 * Возвращает значение кеша.
	 *
	 * @return mixed|null
	 */
	public function pick()
	{
		return $this->value;
	}
}