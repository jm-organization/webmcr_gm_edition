INSERT INTO `mcr_blocks_configs` (
  `block_id`,
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
  ('block_banner', 'a:2:{s:5:"order";i:5;s:17:"permissions_level";s:12:"block_banner";}', 'Разработчик', 'Блок вывода баннера.', 'Magicmen & Qexy', 'http://www.jm-org.net/', 'admin@jm-org.net', '2.1.1', '', false),
  ('block_monitor', 'a:2:{s:5:"order";i:3;s:17:"permissions_level";s:14:"sys_monitoring";}', 'Мониторинг', 'Блок вывода мониторинга', 'Magicmen & Qexy', 'http://www.jm-org.net/', 'admin@jm-org.net', '2.1.3', '', false),
  ('block_notify', 'a:2:{s:5:"order";i:1;s:17:"permissions_level";N;}', 'Оповещения', 'Блок вывода оповещений', 'Magicmen & Qexy', 'http://www.jm-org.net/', 'admin@jm-org.net', '2.3.1', '', false),
  ('block_online', 'a:3:{s:5:"order";i:4;s:17:"permissions_level";s:12:"block_online";s:7:"timeout";i:60;}', 'Онлайн статистика', 'Блок вывода текущего онлайна на сайте', 'Magicmen & Qexy', 'http://www.jm-org.net/', 'admin@jm-org.net', '2.0', '', false),
  ('block_profile', 'a:2:{s:5:"order";i:2;s:17:"permissions_level";N;}', 'Мини-профиль', 'Блок вывода мини-профиля пользователя', 'Magicmen & Qexy', 'http://www.jm-org.net/', 'admin@jm-org.net', '2.2.1', '', false)
;