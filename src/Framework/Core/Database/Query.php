<?php
namespace Framework\Core\Database;

use Framework\Core\Database\Queries\Select;
use Framework\Core\Database\Queries\Insert;
use Framework\Core\Database\Queries\Update;
use Framework\Core\Database\Queries\Delete;

final class Query
{
	/**
	 * Instantiate a new select query
	 *
	 * @param mixed|array $fields
	 * @return Select
	 */
	public static function select($fields)
	{
		return (new Select)->select(is_array($fields) ? $fields : func_get_args());
	}

	/**
	 * Instantiate a new insert query
	 *
	 * @param mixed|array $fields
	 * @return Insert
	 */
	public static function insert($fields)
	{
		return (new Insert)->insert(is_array($fields) ? $fields : func_get_args());
	}

	/**
	 * Instantiate a new update query
	 *
	 * @param string $table
	 * @return Update
	 */
	public static function update($table)
	{
		return (new Update)->update($table);
	}

	/**
	 * Instantiate a new delete query
	 *
	 * @param string $table
	 * @return Delete
	 */
	public static function delete($table)
	{
		return (new Delete)->delete($table);
	}

	/**
	 * Get available query types
	 *
	 * @return array
	 */
	public static function types()
	{
		return array_filter(get_class_methods(Query::class), function ($item) {
			return $item !== 'types';
		});
	}
}