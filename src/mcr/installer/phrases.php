<?php

return [
	// Требования (хар-ки)
	'PHP' => 'Поддержка php версии >=5.5',
	'PHP_desc' => 'Для работы MagicMCR необходим интерпретатор php версии не ниже 5.5 (Рекомендовано 5.6).',
	'REG_GLOB' => 'Использование глобальных переменных выключено',
	'REG_GLOB_desc' => 'Для защиты Ваших данных MagicMCR требует выключение опции register_globals в вашем файле конфигурации php. <a href="http://php.net/manual/ru/security.globals.php">Подробнее о безопасности</a>.',
	'LOCALIZATION' => 'Локализация',
	'LOCALIZATION_desc' => 'MagicMCR использует php-бибилиотеку intl для локализации. Перед установкой убедитесь, что даная библиотека установленна. <a href="http://php.net/manual/ru/intl.installation.php">intl > Установка и настройка</a>.',
	'URL_FOPEN' => 'Поддержка оберток URL',
	'URL_FOPEN_desc' => '',
	'GD' => 'Обработка изображений и GD',
	'GD_desc' => 'На вашем сервере должа быть установленна и включена php-библиотека php_gd2.',
	'MYSQLi' => 'Поддержка MySQLi',
	'MYSQLi_desc' => 'Для работы с базой MagicMCR использует MySQLi. Убедитесь, что у вас включена данная php-библиотека.',
	'BUFER' => 'Буферизация вывода',
	'BUFER_desc' => 'Убедитесь, что на вашем сервере доступен буфкерезированый вывод данных.',
	'FOLDER_DATA' => 'Доступ к файлам и папкам',
	'FOLDER_DATA_desc' => 'В временные файл, загруженные на сервер файлы, файлы кеша MagicMCR хранит в директории data/. Убедитесь, что на даную директорию выданы права, позволяющие безпрепятственныей доступ MagicMCR к ней.',

	// Основные названия шагов и их описание
	'mod_name' => 'Установка',
	'start' => 'Подготовка к установке',
	'start_desc' => "Проверка техниче -\nских требований.",
	'step_1' => 'Шаг #1',
	'step_1_desc' => "Настройка подклю -\nчения к базе данных",
	'step_2' => 'Шаг #2',
	'step_2_desc' => "Регистрация\nсуперпользователя",
	'step_3' => 'Шаг #3',
	'step_3_desc' => "Настройки\nсайта",
	'finish' => 'Готово!',

	// Ключевые фразы навигации
	'copyrighting' => 'Полное или частичное копирование сайта %s запрещено.',
	'on' => 'Вкл.',
	'off' => 'Выкл.',
	'yes' => 'Да',
	'no' => 'Нет',
	'update' => 'Обновить',
	'back' => '« Назад',
	'next' => 'Далее »',
	'cancel' => 'Отмена',
	'reinstall' => 'Переустановка',
	'go_home' => 'На главную сайта',
	'go_cp' => 'В панель управления',

	// Расширенная информация по шагам
	'install_progress' => 'Ход установки',
	// ----------
	'install_step_1_title' => 'Вас приветствует мастер установки системы MagicMCR',
	'install_step_1_desc' => 'Для продолжения вам необходимо сверить результаты проверки. Если есть строки, помеченные красным, вам необходимо настроить сервер так, чтобы значение соответствовало системным требованиям',
	// ----------
	'install_step_2_desc' => "Теперь вам необходимо указать настройки базы данных к которой будет подключаться MagicMCR.\n
	<span style='color: #db2828'>Внимание!</span> Убедитесь, что вы используете версию БД не ниже <code>5.6 MySQL</code>. Убедитесь, что база доступна к подключению с данного сервера и проверьте правильность вводимых данных.",
	'db_host' => 'Адрес хоста',
	'db_port' => 'Порт',
	'db_name' => 'Имя базы данных',
	'db_user' => 'Имя пользователя',
	'db_pass' => 'Пароль пользователя',
	// ----------
	'install_step_3_desc' => 'Теперь необходимо создать учётную запись суперпользователя (администратора). Для этого Вам необходимо заполнить форму ниже. Учтите, что это так же может быть вашим игровым аккаунтом.<br><span style="color: #666">Для шифрования паролей MagicMCR использует сильный, необратимый алгоритм хеширования "Bcrypt".</span>',
	'adm_login' => 'Логин администратора',
	'adm_pass' => 'Пароль администратора',
	'adm_repass' => 'Повторите пароль',
	'adm_mail' => 'E-Mail администратора',
	// ----------
	'install_step_4_desc' => 'Последние настройки.<br><br>Заполните информацию о вашем сайте.<br>Указывайте уникальную информацию чтобы поисковые системы могли правильно проиндексировать ваш сайт.',
	'site_name' => 'Название сайта',
	'site_desc' => 'Описание сайта',
	'site_keys' => 'Ключевые слова',
	'site_url' => 'Адресс сайта',
	// ----------
	'reinstall_desc' => 'Внимание! Переустановка приведет к удалению всех, установленных ранее, таблиц с префиксом <code>mcr_</code>. Вы уверены, что хотите выполнить переустановку?',

	'finish_header_title' => 'Поздравляем!',
	'installed_success' => 'Вы успешно установили MagicMCR',
	'about' => 'MagicMCR - это некоммерческий проект с открытым исходным кодом. Вся разработка идет на энтузиазме,<br>а ваши пожертвования дают нам повод для развития своих идей и продолжения разработки.',
	'copyrighting_about' => 'MagicMCR является отдельным и независимым продуктом.<br>Исходный код распространяется под лицензией <code>GNU General Public License v3.0</code>.<br><br><small>MagicMCR не является копией оригинального движка WebMCR, а лишь его подверсией.<br>Разработка MagicMCR производится исключительно в частных интересах. Разработчики, а также лица,<br>участвующие в разработке и поддержке, не несут ответственности за проблемы, возникшие с движком.</small>',

	'useful_links' => 'Полезные ссылки',
	'official_site' => 'Официальный сайт',
	'documentation' => 'Документация',
	'extensions' => 'Дополнения',
	'vk_page' => 'Страница VK',
	'dev_user' => 'Разработчик новых версий',
	'author_user' => 'Автор WebMCR & Разработчик предыдущих версий',
	'theme_user' => 'Автор изначального шаблона',
	'donate' => 'Пожертвования',

	'use_comments' => 'Использовать комментарии',
	'users_on_page' => 'Пользователей на страницу',
	'comments_on_page' => 'Комментариев на страницу',

	// Ошибки и др.
	// ---------------
	// Основные фразы
	'e_msg' => 'Внимание!',
	'e_sql' => 'SQL Error',
	//----------------
	// Ошибки сис. требований
	'e_php_version' => 'Версия PHP не соответствует системным требованиям',
	'e_register_globals' => 'Функция Register Globals не соответствует системным требованиям',
	'e_l10n' => 'Поддержка intl на Вашем сервере не обнаружена.',
	'e_fopen' => 'Функция allow_url_fopen() не соответствует системным требованиям',
	'e_gd' => 'Библиотека GD не найдена',
	'e_mysql_not_found' => 'MySQL не найдена',
	'e_buffer' => 'Функции буферизации данных недоступны',
	'e_perm_data' => 'Отсутствуют права на чтение и/или запись папки data/ и/или ее содержимого',
	//----------------
	// Др. ошибки
	'e_settings' => 'Настройки не могут быть сохранены',
	'e_write' => 'Файл недоступен для записи',
	'e_connection' => 'Невозможно соединиться с базой данных. Проверьте ее доступность для подключения правильность вводимых данных.',
	'e_set_base' => 'Неверно указаны данные для подключения к базе',
	'e_login_format' => 'Логин может состоять только из символов a-zA-Z0-9_- и быть не менее 3-х символов',
	'e_pass_len' => 'Пароль должен быть не менее 6-ти символов',
	'e_pass_match' => 'Пароли не совпадают',
	'e_email_format' => 'Неверный формат E-Mail адреса',
	'e_add_admin' => 'Произошла ошибка добавления администратора',
	'e_add_menu' => 'Ошибка добавления пункта меню',
	'e_add_icon' => 'Ошибка добавления иконки',
	'e_set_site_configs' => 'Ошибка сохранения базовых натсроек сайта.',
	'e_add_menu_adm' => 'Ошибка добавления пункта меню в панель управления',
	'e_add_economy' => 'Произошла ошибка добавления поля экономики',
	'e_upd_group' => 'Произошла ошибка обновления групп пользователей',
];