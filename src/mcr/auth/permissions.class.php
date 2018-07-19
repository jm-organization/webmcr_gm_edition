<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 10.07.2018
 * @Time         : 23:21
 *
 * @Documentation:
 */

namespace mcr\auth;


trait permissions
{
	/**
	 * @var mixed|null
	 */
	public $permissions = null;

	/**
	 * Проверяет может ли пользователь сделать что-то
	 *
	 * @param $permission
	 *
	 * @return bool
	 */
	public function can($permission)
	{
		if ($this->permission_is_valid($permission)) {
			if (!$this->permissions->$permission) return false;
		} else {
			return false;
		}

		return true;
	}

	/**
	 * Обратный методу can.
	 *
	 * @param $permission
	 *
	 * @return bool
	 */
	public function cannot($permission)
	{
		if ($this->permission_is_valid($permission)) {
			if (!$this->permissions->$permission) return true;
		} else {
			return true;
		}

		return false;
	}

	/**
	 * Разрешает пользователю что-то сделать.
	 *
	 * @param $permission
	 */
	public function allow($permission) { }

	/**
	 * Обратный методу allow.
	 *
	 * @param $permission
	 */
	public function forbid($permission) { }

	/**
	 * Проверяет пермишен на валидность.
	 *
	 * Пермишен валидный, если он строчный, не пустой и он есть в списке всех пермишенов
	 *
	 * @param $permission
	 *
	 * @return bool
	 */
	private function permission_is_valid($permission)
	{
		return is_string($permission) && !empty(trim($permission)) && property_exists($this->permissions, $permission);
	}
}