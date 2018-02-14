<?php
namespace Framework\Core\Http;

use Lcobucci\JWT\Token;
use Framework\Core\Helpers\Auth;
use Framework\Core\Exceptions\AuthException;

use Framework\Core\Helpers\Arr;
use Framework\Core\Exceptions\ArrayException;

use Framework\Core\Helpers\Str;
use Framework\Core\Database\Model;
use Framework\Core\Exceptions\ValidationException;

/**
 * Class Request
 *
 * @method setModel(string $key, $value)
 * @method setArgument(string $key, $value)
 * @method setData(string $key, $value)
 * @method setHeader(string $key, $value)
 *
 * @method getModel(string $key)
 * @method getArgument(string $key)
 * @method getData(string $key)
 * @method getHeader(string $key)
 *
 * @method models()
 * @method arguments()
 * @method data()
 * @method headers()
 *
 * @package Framework\Core\Http
 */
class Request
{
	/** @var Model[] $models */
	protected $models;

	/** @var array $arguments */
	protected $arguments;

	/** @var array $data */
	protected $data;

	/** @var array $headers */
	protected $headers;

	/** @var Validator $validator */
	protected $validator;

	/** @var string[] $errors */
	public $errors;

	public function __construct(array $models = [], array $arguments = [], array $data = [], array $headers = [])
	{
		$this->models    = $models;
		$this->arguments = $arguments;
		$this->data      = $data;
		$this->headers   = $headers;

		$this->validator = new Validator($this);
		$this->errors    = [];
	}

	public function __call($method, $args)
	{
		$property = strtolower(preg_replace('/set|get/', '', $method));

		if (!property_exists($this, $property = Str::pluralize($property))) {
			return $this;
		}

		$key   = (string)$args[0];
		$value = isset($args[1]) ? $args[1] : null;

		// Set attribute
		if (strpos($method, 'set') === 0) {
			$this->{strtolower($property)}[ strtolower($key) ] = $value;

			return $this;
		}

		// Get attribute
		if (strpos($method, 'get') === 0) {
			$property = $this->{strtolower($property)};

			return isset($property[ $key ]) ? $property[ $key ] : null;
		}

		// Get attribute group
		return $this->{strtolower($property)};
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
	 * @param string[] $rules
	 * @throws ArrayException|ValidationException
	 * @return $this
	 */
	public function validate(array $rules)
	{
		Arr::associative($rules, true);

		foreach ($rules as $data => $raw) {
			$tests = explode('|', $raw);

			foreach ($tests as $test) {
				$bits = explode(':', $test);

				// Database related test
				if (isset($bits[1]) && count($query = explode(',', $bits[1])) > 1) {
					$this->validator->setRule($data, $bits[0], $query[0], $query[1]);

					continue;
				};

				// Basic test
				$this->validator->setRule($data, $bits[0]);
			}
		}

		if (!$this->validator->validate()) {
			$this->errors = $this->validator->errors();

			throw new ValidationException('Failed validation tests');
		}

		return $this;
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
			'data'      => $this->dataToArray(),
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
	 * Return all data
	 *
	 * @return array
	 */
	public function dataToArray()
	{
		return $this->data();
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