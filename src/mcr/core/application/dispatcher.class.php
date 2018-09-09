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
 * @Date         : 12.07.2018
 * @Time         : 22:26
 *
 * @Documentation:
 */

namespace mcr\core\application;


use mcr\html;
use mcr\http\request;
use mcr\html\document;
use mcr\http\routing\routing_exception;

trait dispatcher
{
	/**
	 * Компилятор приложения.
	 * Получает информацию о маршруте от маршрутизатора.
	 *
	 * Если удалось получить инормацию о обработчике маршрута, то создаём документ о данному обработчику.
	 * Возвращаем ответ серверу.
	 *
	 * Ответ состоит из статуса, который вернул маршрутизатор, документа,
	 * который был получен обработчиком маршрута.
	 *
	 * @param application $app
	 *
	 * @return \mcr\http\response
	 * @throws html\blocks\blocks_manager_exception
	 * @throws routing_exception
	 */
	public function dispatch(application $app)
	{
		list($status, $route_info, $additional_info) = $app->router->dispatch();
		$content = '';

		if (isset($additional_info->route[1]) && is_callable($hundler = $additional_info->route[1])) {
			$content = $hundler($app->request);
		} elseif (count($route_info) >= 2) {
			////////////////////////////////////////////////////////////////////////////
			// Инициализация текущего модуля приложения
			////////////////////////////////////////////////////////////////////////////

			/** @var \mcr\http\module $module */
			$module = $this->initialize_module($route_info['controller']);

			if ($module) {
				$module->boot($app);

				$document = new document(
					$module,
					$app->request,
					$route_info['action']
				);

				$content = (string) $document->render();
			}
		}

		if (request::method() == 'GET') return response()->status($status)->charset('utf-8')->content($content); else exit;
	}

	/**
	 * Инитиализатор модуля.
	 * Загружает ресурсы модуля
	 * создаёт его экземпляр и возвращает его.
	 *
	 * Если модуль не был найден,
	 * то возвращает false
	 *
	 * @param string $module
	 *
	 * @return \mcr\http\module|bool
	 * @throws routing_exception
	 */
	private function initialize_module($module)
	{
		$class = MCR_ROOT . $module . '.php';
		// Если файл модуля найден, погружаем его.
		load_if_exist($class);


		// Если класс модуля доступен - инициализируем модуль
		// и возвращаем экземпляр объекта модуля.
		if (class_exists($module)) {

			return new $module();

		}

		throw new routing_exception("Module `$module` not found.");
	}
}