<?php
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


class cache_value
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
		if (!empty($value)) $this->set_value($value);
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
		if (empty($this->value)) throw new cache_exception('Can`t serialize empty value. You must set serializable value.');

		if (is_object($this->value)) {
			// Проверяем наличие метода __sleep у переданного объекта
			if (!method_exists($this->value, '__sleep')) throw new cache_exception('The Received object \\' . get_class($this->value) . ' for serialization must be a implementing __sleep() method.');
		}

		// Сериализируем и устанавливаем данные.
		$this->set_value(serialize($this->value));

		return $this;
	}

	/**
	 * Десериализирует установелнное значение.
	 *
	 * Значение должно быть сериализированым.
	 * Если был сериализирован объект,
	 * то он должен реализовывать метод __wakeup.
	 *
	 * @param array $options
	 *
	 * @return cache_value
	 * @throws cache_exception
	 */
	public function deserialize(array $options = [])
	{
		$options += [ 'allowed_classes' => true ];

		if (empty($this->value)) throw new cache_exception('Can`t deserialize empty value. You must pass serialized value.');

		// Десериализируем и устанавливаем данные
		if ($value = unserialize($this->value, $options) == false) throw new cache_exception('Can`t deserialize received value. (Given: ' . var_export($this->value, true) . ')');

		$this->set_value($value);

		return $this;
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
		if (!is_array($this->value)) throw new cache_exception('The Received value must be of array type. (' . gettype($this->value) . var_export($this->value, true) . ' given).');

		$this->set_value(json_encode($this->value, $options, $depth));

		return $this;
	}

	/**
	 * @param bool  $assoc
	 * @param int   $depth
	 * @param array $options
	 *
	 * @throws cache_exception
	 */
	private function decode_json($assoc = false, $depth = 512, array $options = [])
	{
		if (!is_string($this->value)) throw new cache_exception('The reseived value must be of string type. (' . gettype($this->value) . var_export($this->value, true) . ' given).');

		if ($value = json_decode($this->value, $assoc, $depth, $options) === null) throw new cache_exception('Can`t transform json to array. (' . gettype($this->value) . var_export($this->value, true) . ' given).');

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
	public function to_array($depth = 512, array $options = [])
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
	public function to_object($depth = 512, array $options = [])
	{
		$this->decode_json(false, $depth, $options);

		return $this;
	}

	/**
	 * @return string
	 * @throws cache_exception
	 */
	public function __toString()
	{
		if (null !== $this->value && !is_string($this->value) && !is_numeric($this->value) && !is_callable(array($this->value, '__toString'))) {
			throw new cache_exception('The Response content must be a string or object implementing __toString(), "'.gettype($this->value).'" given.');
		}

		return (string) $this->value;
	}

	/**
	 * Возвращает значение кеша.
	 *
	 * @return null
	 */
	public function get()
	{
		return $this->value;
	}
}