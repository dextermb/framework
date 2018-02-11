<?php
namespace Framework\Core\Http;

use Lcobucci\JWT\Token;

use Framework\Core\Helpers\Auth;
use Framework\Core\Exceptions\AuthException;

use Framework\Core\Database\Model;

class Request
{
	/** @var Model[] $models */
	protected $models;

	/** @var array $arguments */
	protected $arguments;

	/** @var array $headers */
	protected $headers;

	public function __construct(array $models = [], array $arguments = [], array $headers = [])
	{
		$this->models    = $models;
		$this->arguments = $arguments;
		$this->headers   = $headers;
	}

	/**
	 * Set a model
	 *
	 * @param string $key
	 * @param Model  $model
	 * @return $this
	 */
	public function setModel(string $key, Model $model)
	{
		$this->models[ strtolower($key) ] = $model;

		return $this;
	}

	/**
	 * Set an argument
	 *
	 * @param string         $key
	 * @param string|integer $value
	 * @return $this
	 */
	public function setArgument(string $key, $value)
	{
		$this->arguments[ strtolower($key) ] = $value;

		return $this;
	}

	/**
	 * Set a header
	 *
	 * @param string         $key
	 * @param string|integer $value
	 * @return $this
	 */
	public function setHeader(string $key, $value)
	{
		$this->headers[ strtolower($key) ] = $value;

		return $this;
	}

	/**
	 * Get a model
	 *
	 * @param string $key
	 * @return Model|null
	 */
	public function getModel(string $key)
	{
		return isset($this->models[ $key ]) ? $this->models[ $key ] : null;
	}

	/**
	 * Get an argument
	 *
	 * @param string $key
	 * @return mixed|null
	 */
	public function getArgument(string $key)
	{
		return isset($this->arguments[ $key ]) ? $this->arguments[ $key ] : null;
	}

	/**
	 * Get a header
	 *
	 * @param string $key
	 * @return mixed|null
	 */
	public function getHeader(string $key)
	{
		return isset($this->headers[ $key ]) ? $this->headers[ $key ] : null;
	}

	/**
	 * Check if a valid token was set with request
	 *
	 * @throws AuthException
	 * @return Token
	 */
	public function authenticate()
	{
		$header = $this->getHeader('authorization') ?: $this->getHeader('token');

		if (is_null($header)) {
			throw new AuthException('No authorization header set');
		}

		$token = preg_replace('/bearer\s+/i', '', $header);

		return Auth::validate($token, true);
	}

	/**
	 * Return all models
	 *
	 * @return Model[]
	 */
	public function models()
	{
		return $this->models;
	}

	/**
	 * Return all arguments
	 *
	 * @return array
	 */
	public function arguments()
	{
		return $this->arguments;
	}

	/**
	 * Return all headers
	 *
	 * @return array
	 */
	public function headers()
	{
		return $this->headers;
	}

	/**
	 * Return all models, arguments, headers in an array
	 *
	 * @return array
	 */
	public function toArray()
	{
		return [
			'models'    => $this->modelsToArray(),
			'arguments' => $this->argumentsToArray(),
			'headers'   => $this->headersToArray(),
		];
	}

	/**
	 * Return all models
	 *
	 * @param bool $convert
	 * @return array
	 */
	public function modelsToArray(bool $convert = true)
	{
		if ($convert) {
			$arr = [];

			foreach ($this->models as $key => $model) {
				$arr[ $key ] = $model->toArray();
			}

			return $arr;
		}

		return $this->models();
	}

	/**
	 * Return all arguments
	 *
	 * @return array
	 */
	public function argumentsToArray()
	{
		return $this->arguments();
	}


	/**
	 * Return all headers
	 *
	 * @return array
	 */
	public function headersToArray()
	{
		return $this->headers();
	}
}