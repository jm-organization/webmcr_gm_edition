<?php

namespace mcr\html;

use mcr\auth\auth;
use mcr\database\db;
use mcr\http\request;

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class menu
{
	/**
	 * Содержит єкзмепляр класса поступившего запроса
	 *
	 * @var request|null
	 */
	private $request = null;

	public function __construct(request $request)
	{
		$this->request = $request;
	}

	public static function is_active($url)
	{
		$request_url = request::url();

		return strripos($request_url, $url);
	}

	/**
	 * Возвращает админ меню в виде масива данных
	 *
	 * @return string|null
	 * @throws \mcr\database\db_exception
	 */
	private function admin_menu()
	{
		$sub_menus = [];
		$admin_menu = '';

		$query = db::query(
			"SELECT `title`, `text`, `url`, `target`, `gid`, `priority` FROM `mcr_menu_adm`"
		);

		if ($query->result() && $query->num_rows > 0) {
			while ($sub_menu_element = $query->fetch_assoc()) {
				$id = intval($sub_menu_element['priority']);

				if (isset($sub_menus[$sub_menu_element['gid']][$id])) {
					$id++;
				}

				$sub_menus[$sub_menu_element['gid']][$id] = $sub_menu_element;
			}
		}

		$query = db::query("
			SELECT id, `page_ids`, `icon`, title, `text`, `access`
			FROM `mcr_menu_adm_groups`
			ORDER BY `priority` ASC
		");

		if ($query->result() && $query->num_rows > 0) {
			$counter = 0;
			while ($menu_element = $query->fetch_assoc()) {
				$counter++;

				$id = intval($menu_element['id']);
				// Извлекаем айдишники страниц из группы админ меню.
				// разворачиваем полученый масив, чтобы потом найти в нём необходимую траницу
				$page_ids = array_flip(explode('|', $menu_element['page_ids']));

				// Если нет прав доступа к меню, не выводим его.
				// TODO: перенести метод проверки прав в класс user
				// if (!$this->core->is_access($menu_element['access'])) continue;

				// сортируем вложенные меню asc'ом
				ksort($sub_menus[$id]);

				$admin_menu .= tmpl('modules.admin.menu-item', [
					"id" => $id,
					"title" => htmlspecialchars($menu_element['title']),
					"active" => isset($_GET['do']) && array_key_exists($_GET['do'], $page_ids) ? ' active open' : null,
					"icon" => trim(str_replace('fa-', '', $menu_element['icon'])),
					"sub_menu" => $sub_menus[$id],
					"parent" => null
				]);

			}


			//echo $this->core->sp(MCR_THEME_MOD."admin/panel_menu/menu-groups/group-id.phtml", $data);

			return $admin_menu;
		}

//		return "<center>{$this->l10n->gettext('empty_panel_menu_group')}</center>";
		return null;
	}

	/**
	 *
	 * @return array|null
	 * @throws \mcr\database\db_exception
	 */
	private function public_menu()
	{
		$user = empty(auth::user()) ? auth::guest() : auth::user();

		$query = db::query("
			SELECT id, title, `parent`, `url`, `style`, `target`, `permissions`
			FROM `mcr_menu`
			ORDER BY `parent` DESC
		");

		if ($query->result() && $query->num_rows > 0) {
			$menu = [];

			while ($public_menu_element = $query->fetch_assoc()) {
				// TODO: перенести метод проверки прав в класс user
			    if ($user->cannot($public_menu_element['permissions'])) continue;

				$menu[$public_menu_element['id']] = [
					"id" => $public_menu_element['id'],
					"title" => $public_menu_element['title'],
					"parent" => $public_menu_element['parent'],
					"url" => $public_menu_element['url'],
					"style" => $public_menu_element['style'],
					"target" => $public_menu_element['target'],
					"permissions" => $public_menu_element['permissions']
				];
			}

			ob_start();

			$tree = $this->create_tree($menu);

			foreach ($tree as $key => $ar) {
				$data = array(
					"title" => $ar['title'],
					"url" => htmlspecialchars($ar['url']),
					"style" => htmlspecialchars($ar['style']),
					"target" => htmlspecialchars($ar['target']),

					"active" => (self::is_active($ar['url'])) ? 'active' : '',

					"sub_menu" => (!empty($ar['sons'])) ? $this->generate_sub_menu($ar['sons']) : "",
				);

				if (!empty($ar['sons'])) {
					echo tmpl('menu.menu-id-parented', $data);

					continue;
				}

				echo tmpl('menu.menu-id', $data);

			}

			return ob_get_clean();
		}

		return null;
	}

	/**
	 * @param string $menu_name
	 *
	 * @uses menu::admin_menu()
	 * @uses menu::public_menu()
	 *
	 * @return string
	 */
	public function generate($menu_name = 'public')
	{
		$menu_controller = $menu_name . '_menu';

		if (method_exists($this, $menu_controller)) {
			// Извлекаем данные о меню
			return $this->$menu_controller();
		}

		return null;
	}

	private function generate_sub_menu($tree)
	{
		ob_start();

		foreach ($tree as $key => $ar) {

			$data = array(
				"title" => $ar['title'],
				"url" => htmlspecialchars($ar['url']),
				"style" => htmlspecialchars($ar['style']),
				"target" => htmlspecialchars($ar['target']),

				"active" => (self::is_active($ar['url'])) ? 'active' : '',

				"sub_menu" => (!empty($ar['sons'])) ? $this->generate_sub_menu($ar['sons']) : "",
			);

			if (!empty($ar['sons'])) {
				echo tmpl('menu.menu-id-sub-parented', $data);

				continue;
			}

			echo tmpl('menu.menu-id-sub', $data);
		}

		return ob_get_clean();
	}

	private function create_tree($categories)
	{
		$tree = array();

		$this->new_tree_element($categories, $tree, null);

		return $tree;
	}

	private function new_tree_element(&$categories, &$tree, $parent)
	{
		foreach ($categories as $key => $ar) {
			if (intval($ar['parent']) == $parent) {
				$tree[$key] = $categories[$key];
				$tree[$key]['sons'] = array();
				$this->new_tree_element($categories, $tree[$key]['sons'], $key);
			}

			if (empty($tree['sons'])) {
				unset ($tree['sons']);
			}

		}

		unset($categories[$parent]);

		return;
	}
}