<?php
namespace Framework\Core\Database;

use PDOException;
use PDO;

final class Connection
{
	private static $instance = null;

	/**
	 * Create or return a database connection
	 *
	 * @return PDO
	 */
	public static function init()
	{
		if (self::$instance === null) {
			try {
				self::$instance = new PDO(
					self::buildConnectionString()
					, env('DB_USER', 'framework')
					, env('DB_PASS', 'secret'),
					[ 'unix_socket' => env('DB_SOCKET', '/tmp/mysql.sock') ]
				);

				self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				self::$instance->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
			} catch (PDOException $e) {
				throw $e;
			}
		}

		return self::$instance;
	}

	/**
	 * Build the database connection string
	 *
	 * @return string
	 */
	private static function buildConnectionString()
	{
		$connection_string = 'mysql:';

		$connection_string .= 'host=' . env('DB_HOST', 'localhost') . ';';
		$connection_string .= 'dbname=' . env('DB_SCHEMA', 'framework') . ';';
		$connection_string .= 'charset=' . env('DB_CHARSET', 'utf8');

		return $connection_string;
	}
}