<?php

/**
** =============================================================================
** DATABASE CONFIG - config/database.php
** =============================================================================
** Handles database connection using PDO.
** Credentials loaded from .env file.
** Default schema: public
** =============================================================================
**/

class Database
{
	private $host;
	private $database;
	private $username;
	private $password;
	private $charset = "UTF8";
	private $options =
	[
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_EMULATE_PREPARES => false,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	];
	private $connection;

	public function __construct()
	{
		/**
		** Load .env file
		**/
		$env_file = dirname(__DIR__) . "/.env";
		if (file_exists($env_file))
		{
			$lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
			foreach ($lines as $line)
			{
				if (trim($line) === "" || $line[0] === "#")
				{
					continue;
				}
				if (strpos($line, "=") !== false)
				{
					list($key, $value) = explode("=", $line, 2);
					$key = trim($key);
					$value = trim($value);
					if (!isset($_ENV[$key]) && !isset($_SERVER[$key]))
					{
						$_ENV[$key] = $value;
						putenv("$key=$value");
					}
				}
			}
		}

		$this->host = getenv("DB_HOST");
		$this->database = getenv("DB_NAME");
		$this->username = getenv("DB_USER");
		$this->password = getenv("DB_PASS");
	}

	public function getConnection(): PDO
	{
		if ($this->connection === null)
		{
			$dsn = "pgsql:host={$this->host};dbname={$this->database};options='--client_encoding=UTF8'";
			$this->connection = new PDO($dsn, $this->username, $this->password, $this->options);
			$this->connection->exec("SET search_path TO public");
		}
		return $this->connection;
	}

	public function closeConnection(): void
	{
		$this->connection = null;
	}
}