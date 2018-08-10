INSERT INTO `mcr_modules_configs` (
  `module_id`,
  `configs`,
  `name`,
  `description`,
  `author`,
  `site`,
  `email`,
  `version`,
  `updation_url`,
  `checking_on_update`
)
VALUES
  ('admin', '', 'Панель управления', 'Панель управления. Не может быть отключена. Постовляется, как основной пакет.', 'Magicmen & Qexy', 'http://www.jm-org.net/', 'admin@jm-org.net', '2.0', '', false),
  ('auth', '', 'Аутентификация', 'Управляет регистрацией, авторизацией, восстонавлением пароля и выходом на сайте.', 'Magicmen & Qexy', 'http://www.jm-org.net/', 'admin@jm-org.net', '2.1.3', '', false),
  ('news', '', 'Новости', 'Модуль новостей. Позволяет доступ к нвостям.', 'Magicmen & Qexy', 'http://www.jm-org.net/', 'admin@jm-org.net', '2.0.1', '', false),
  ('user_profile', '', 'Профиль пользователя', 'Модуль личного кабинета пользователя', 'Magicmen & Qexy', 'http://www.jm-org.net/', 'admin@jm-org.net', '1.0', '', false),
  ('search', '', 'Поиск', 'Модуль поиска по сайту.', 'Magicmen & Qexy', 'http://www.jm-org.net/', 'admin@jm-org.net', '1.0', '', false),
  ('statics_pages', '', 'Статические страницы', 'Модуль статических страницу.', 'Magicmen & Qexy', 'http://www.jm-org.net/', 'admin@jm-org.net', '1.0', '', false),
  ('users', 'a:3:{s:15:"enable_comments";b:1;s:13:"users_on_page";i:15;s:16:"comments_on_page";i:15;}', 'Users System', 'Модуль пользователей.', 'Qexy, edited by Magicmen', 'http://qexy.org/', 'admin@qexy.org', '1.3.1', 'http://update.webmcr.com/?do=modules&op=users', false)
;