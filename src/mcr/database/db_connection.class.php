<?php

namespace mcr\database;


use mcr\config;
use mysqli;

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

/**
 * Class db_connection
 *
 * @package mcr\database
 */
class db_connection
{
	/**
	 * @var mysqli|null
	 */
	public $connection = null;

	public $connect_error = '';

	public $connect_errno = '';

	/**
	 * @var config|null
	 */
	private $configs = null;

	/**
	 * DB host
	 * Адресс по которому расположена база данных
	 *
	 * @var string
	 */
	public $host = '';

	/**
	 * DB port
	 * Порт на котором запущен сервер базы данных
	 *
	 * @var string
	 */
	public $port = '';

	/**
	 * DB user
	 * Пользователь, через которого
	 * будет производится взаимодействие с базой данных
	 *
	 * @var string
	 */
	public $user = '';

	/**
	 * DB password
	 * Пароль доступа к базе. Привязан к пользователю
	 *
	 * @var string
	 */
	protected $passwd = '';

	/**
	 * DB name
	 * Имя базы данных
	 *
	 * @var string
	 */
	public $dbname = '';


	/**
	 * db constructor.
	 *
	 * @param config $configs
	 */
	public function __construct(config $configs)
	{
		$this->host = $configs->db['host'];
		$this->user = $configs->db['user'];
		$this->passwd = $configs->db['pass'];
		$this->dbname = $configs->db['base'];
		$this->port = $configs->db['port'];

		$this->configs = $configs->db;

		$this->connection = $this->connect();
	}

	/**
	 * @return mysqli
	 */
	public function connect()
	{
		$connection = new mysqli(
			$this->host,
			$this->user,
			$this->passwd,
			$this->dbname,
			$this->port
		);

		$this->connect_error = $connection->connect_error;
		$this->connect_errno = $connection->connect_errno;


		if (empty($this->connect_error)) {
			if (!$this->connect_errno) {


				if (!$connection->set_charset("utf8")) {
					return null;
				}

				return $connection;


			}
		}

		return null;
	}
}
