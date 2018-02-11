<?php
namespace Framework\Core\Helpers;

use Doctrine\Common\Inflector\Inflector;
use Framework\Core\Database\Model;

class Str
{
	/**
	 * Return the plural of a word
	 *
	 * @param string $string
	 * @return string
	 */
	public static function pluralize(string $string)
	{
		$pluralized = Inflector::pluralize($string);

		return self::cleanup($pluralized);
	}

	/**
	 * Return the singular of a word
	 *
	 * @param string $string
	 * @return string
	 */
	public static function singular(string $string)
	{
		$singular = Inflector::singularize($string);

		return self::cleanup($singular);
	}

	/**
	 * Return words separated by capitals
	 *
	 * @param string $string
	 * @return string
	 */
	public static function classify(string $string)
	{
		return Inflector::classify($string);
	}

	/**
	 * Return words separated by underscores
	 *
	 * @param string $string
	 * @return string
	 */
	public static function snakey(string $string)
	{
		return self::cleanup(Inflector::tableize($string));
	}

	/**
	 * Return the assumed foreign key of a model
	 *
	 * @param Model $model
	 * @throws \ReflectionException
	 * @return string
	 */
	public static function primaryField(Model $model)
	{
		return Str::singular(Str::snakey((new \ReflectionClass($model))->getShortName())) . '_id';
	}

	/**
	 * Standardize returned words
	 *
	 * @param string $string
	 * @return string
	 */
	private static function cleanup(string $string)
	{
		return strtolower($string);
	}
}