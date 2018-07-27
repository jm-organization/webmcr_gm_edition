<?php
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

namespace mcr\core;


use mcr\html;
use mcr\http\request;
use mcr\html\document;

trait dispatcher
{
	/**
	 * @var request
	 */
	private $request;

	/**
	 * @var \mcr\http\routing\router
	 */
	private $router;

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
	 * @param core_v2 $core
	 *
	 * @return \mcr\http\response
	 * @throws html\blocks\blocks_manager_exception
	 */
	public function dispatch(core_v2 $core)
	{
		list($status, $route_info, $additional_info) = $this->router->dispatch();
		$content = '';

		if (isset($additional_info->route[1]) && is_callable($hundler = $additional_info->route[1])) {
			$content = $hundler($this->request);
		} elseif (count($route_info) >= 2) {
			////////////////////////////////////////////////////////////////////////////
			// Инициализация текущего модуля приложения
			////////////////////////////////////////////////////////////////////////////

			/** @var \modules\module $module */
			$module = $this->initialize_module($route_info['controller']);

			if ($module) {
				$module->boot($core);

				$document = new document(
					$module,
					$core->request,
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
	 * @return \modules\module|bool
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

		return false;
	}
}