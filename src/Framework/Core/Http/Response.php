<?php
namespace Framework\Core\Http;

final class Response
{
	public $status;
	public $success;
	public $error;
	public $data;
	public $meta;

	/**
	 * Create a successful response
	 *
	 * @param mixed $data
	 * @param mixed $meta
	 * @param int   $status
	 * @return void
	 */
	public static function success($data = null, $meta = null, $status = 200)
	{
		$response = new Response;

		$response->status  = $status;
		$response->success = true;
		$response->error   = false;
		$response->data    = $data;
		$response->meta    = $meta;

		$response->toJSON();
	}

	/**
	 * Create an unsuccessful response
	 *
	 * @param string|array $error
	 * @param int          $status
	 * @return void
	 */
	public static function error($error, $status = 400)
	{
		$response = new Response;

		$response->status  = $status;
		$response->success = false;
		$response->error   = $error;
		$response->data    = null;
		$response->meta    = null;

		$response->toJSON();
	}

	/**
	 * Return a response as JSON with headers
	 */
	private function toJSON()
	{
		http_response_code($this->status);
		header('Content-Type: Application/json');

		exit(json_encode(get_object_vars($this)));
	}
}