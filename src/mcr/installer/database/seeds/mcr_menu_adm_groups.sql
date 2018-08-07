INSERT INTO `mcr_menu_adm_groups` (`page_ids`, `icon`, `title`, `text`, `access`, `priority`)
VALUES
  ('info|monitoring|modules|logs|blocks', 'shield', 'Разное', 'Описание раздела разное', 'sys_adm_m_g_main', 1),
  ('l10n_phrases|l10n_languages', 'language', 'Локализация (l10n)', 'Управление фразами и языками, выводом информации в зависимости от выбранной локали', 'sys_adm_l10n', 2),
  ('news|news_cats|comments|news_views|news_votes|statics', 'newspaper-o', 'Новости', 'Всё, что связано с модулем новостей', 'sys_adm_m_g_news', 3),
  ('users|groups|permissions', 'users', 'Пользователи', 'Управление пользователями', 'sys_adm_m_g_users', 4),
  ('menu|menu_adm|menu_groups|menu_icons|us', 'sliders', 'Меню', 'Управление группами и пунктами меню сайта и панели управления', 'sys_adm_m_g_menu', 5),
  ('settings', 'cogs', 'Настройки', 'Настройки сайта и движка', 'sys_adm_m_g_settings', 10)
;