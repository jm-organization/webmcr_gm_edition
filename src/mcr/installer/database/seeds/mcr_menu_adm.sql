INSERT INTO `mcr_menu_adm` (`page_id`, `gid`, `title`, `text`, `url`, `target`, `access`, `priority`, `fixed`, `icon`)
VALUES
  ('info', 1, 'Информация', 'Информация и статистика движка', '~base_url~?mode=admin&do=info', '_self', 'sys_adm_m_i_info', 1, 1, 8),
  ('news', 3, 'Новости', 'Управление списком новостей', '~base_url~?mode=admin&do=news', '_self', 'sys_adm_m_i_news', 1, 1, 2),
  ('news_cats', 3, 'Категории', 'Управление категориями новостей', '~base_url~?mode=admin&do=news_cats', '_self', 'sys_adm_m_i_news_cats', 2, 0, 10),
  ('comments', 3, 'Комментарии', 'Управление комментариями новостей', '~base_url~?mode=admin&do=comments', '_self', 'sys_adm_m_i_comments', 3, 0, 13),
  ('news_views', 3, 'Просмотры', 'Управление просмотрами новостей', '~base_url~?mode=admin&do=news_views', '_self', 'sys_adm_m_i_news_views', 4, 0, 14),
  ('news_votes', 3, 'Голоса', 'Управление голосами новостей', '~base_url~?mode=admin&do=news_votes', '_self', 'sys_adm_m_i_news_votes', 5, 0, 9),
  ('users', 4, 'Пользователи', 'Изменение пользователей', '~base_url~?mode=admin&do=users', '_self', 'sys_adm_m_i_users', 1, 1, 5),
  ('groups', 4, 'Группы', 'Управление группами пользователей и их привилегиями', '~base_url~?mode=admin&do=groups', '_self', 'sys_adm_m_i_groups', 2, 0, 15),
  ('permissions', 4, 'Привилегии', 'Управление доступными привилегиями', '~base_url~?mode=admin&do=permissions', '_self', 'sys_adm_m_i_permissions', 3, 0, 17),
  ('menu', 5, 'Меню сайта', 'Управление пунктами основного меню', '~base_url~?mode=admin&do=menu', '_self', 'sys_adm_m_i_menu', 1, 0, 7),
  ('menu_adm', 5, 'Меню ПУ', 'Управление пунктами меню панели управления', '~base_url~?mode=admin&do=menu_adm', '_self', 'sys_adm_m_i_menu_adm', 2, 0, 24),
  ('menu_groups', 5, 'Группы меню ПУ', 'Управление группами меню панели управления', '~base_url~?mode=admin&do=menu_groups', '_self', 'sys_adm_m_i_menu_groups_adm', 3, 0, 11),
  ('menu_icons', 5, 'Иконки', 'Управление иконками пунктов меню панели управления', '~base_url~?mode=admin&do=menu_icons', '_self', 'sys_adm_m_i_icons', 4, 0, 19),
  ('statics', 3, 'Статические страницы', 'Управление статическими страницами ', '~base_url~?mode=admin&do=statics', '_self', 'sys_adm_m_i_statics', 2, 1, 20),
  ('settings', 6, 'Настройки сайта', 'Основные настройки сайта', '~base_url~?mode=admin&do=settings', '_self', 'sys_adm_m_i_settings', 1, 1, 6),
  ('monitoring', 1, 'Мониторинг серверов', 'Управление серверами мониторинга', '~base_url~?mode=admin&do=monitoring', '_self', 'sys_adm_m_i_monitor', 3, 1, 21),
  ('modules', 1, 'Модули', 'Управление модулями', '~base_url~?mode=admin&do=modules', '_self', 'sys_adm_m_i_modules', 4, 0, 22),
  ('logs', 1, 'Лог действий', 'Журнал действий пользователей', '~base_url~?mode=admin&do=logs', '_self', 'sys_adm_m_i_logs', 5, 1, 23),
  ('blocks', 1, 'Блоки', 'Управление Блоками', '~base_url~?mode=admin&do=blocks', '_self', 'sys_adm_m_i_blocks', 6, 0, 18),
  ('l10n_phrases', 2, 'Фразы', 'Управление фразами', '~base_url~?mode=admin&do=l10n_phrases', '_self', 'sys_adm_m_i_l10n', 2, 0, 25),
  ('l10n_languages', 2, 'Языки', 'Управление языками', '~base_url~?mode=admin&do=l10n_languages', '_self', 'sys_adm_m_i_l10n', 1, 0, 26),
  ('us', 5, 'Модуль пользователей', 'Управление модулем пользователей', '/?mode=admin&do=us', '_self', 'mod_adm_m_i_us', 4, 0, 27)
;