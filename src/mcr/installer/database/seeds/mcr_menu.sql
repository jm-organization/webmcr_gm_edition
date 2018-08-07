INSERT INTO `mcr_menu` (`title`, `parent`, `url`, `target`, `permissions`)
VALUES
  ('Главная', 0, '/', '_self', 'sys_share'),
  ('ПУ', 0, '/admin/', '_self', 'sys_adm_main'),
  ('Пользователи', 0, '/users/', '_self', 'mod_users_list')
;