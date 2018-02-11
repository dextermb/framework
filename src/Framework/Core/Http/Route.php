<?php
namespace Framework\Core\Http;

use Framework\Core\Http\Methods\Delete;
use Framework\Core\Http\Methods\Get;
use Framework\Core\Http\Methods\Patch;
use Framework\Core\Http\Methods\Post;
use Framework\Core\Http\Methods\Put;

class Route
{
	/**
	 * Create a GET route
	 *
	 * @param string $endpoint
	 * @param string $controller
	 * @return Get
	 */
	public static function get($endpoint, $controller = null)
	{
		return (new Get($endpoint))->controller($controller);
	}

	/**
	 * Create a POST route
	 *
	 * @param string $endpoint
	 * @param string $controller
	 * @return Post
	 */
	public static function post($endpoint, $controller = null)
	{
		return (new Post($endpoint))->controller($controller);
	}

	/**
	 * Create a PATCH route
	 *
	 * @param string $endpoint
	 * @param string $controller
	 * @return Patch
	 */
	public static function patch($endpoint, $controller = null)
	{
		return (new Patch($endpoint))->controller($controller);
	}

	/**
	 * Create a PUT route
	 *
	 * @param string $endpoint
	 * @param string $controller
	 * @return Put
	 */
	public static function put($endpoint, $controller = null)
	{
		return (new Put($endpoint))->controller($controller);
	}

	/**
	 * Create a DELETE route
	 *
	 * @param string $endpoint
	 * @param string $controller
	 * @return Delete
	 */
	public static function delete($endpoint, $controller = null)
	{
		return (new Delete($endpoint))->controller($controller);
	}
}