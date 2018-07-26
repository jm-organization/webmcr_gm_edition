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
 * @e-mail: admin@jm-org.net
 * @Author: Magicmen
 *
 * @Date  : 26.07.2018
 * @Time  : 23:03
 */

namespace mcr\filesystem;


trait storage_adapter
{
	/**
	 * Короткий алиас функции create_if_isnot_exist
	 * Имеет функционал создания папки, если её нет,
	 * Возвращает готовый полный путь.
	 *
	 * @param              $path
	 * @param mixed|string $base_path
	 *
	 * @return mixed
	 */
	public function folder($path, $base_path = MCR_ROOT)
	{
		$this->create_path_if_is_not_exist($path, $base_path);

		return $path;
	}

	/**
	 * Создаёт папку, по переданному пути, если такой нет.
	 *
	 * @param              $path
	 * @param mixed|string $base_path
	 */
	public function create_path_if_is_not_exist(&$path, $base_path = MCR_ROOT)
	{
		// Определяем количесвто папок и
		// преобразуем папки в масив вложеных папок
		$folders = explode('.', $path);
		$folders_count = count($folders);

		// Определяем путь
		$path = $base_path . $this->dot_to_slash($path);

		// Если такого пути нет, создаём его.
		if (!file_exists($path)) {
			// Збрасываем путь.
			$path = $base_path;

			// Для каждой папки совершаем проверку на её существование
			for ($i = 0; $i < $folders_count; $i++) {
				// Добавляем текущую папку в путь.
				$path .=  $folders[$i] . '/';

				// Если паки нет - создаём её.
				if (!file_exists($path)) mkdir($path, 0644);
			}
		}
	}

	public function dot_to_slash($path)
	{
		return str_replace('.', '/', $path) . '/';
	}
}