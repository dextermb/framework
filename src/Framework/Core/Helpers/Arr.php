<?php
namespace Framework\Core\Helpers;

use Framework\Core\Exceptions\ArrayException;

class Arr
{
	/**
	 * Check that a given array is associative
	 *
	 * @param array $arr
	 * @param bool $throw
	 * @throws ArrayException
	 * @return bool
	 */
	public static function associative(array $arr, bool $throw = false)
	{
		$test = empty($arr) ? false : array_keys($arr) !== range(0, count($arr) - 1);

		if(!$test && $throw) {
			throw new ArrayException('Array must be associative');
		}

		return $test;
	}
}