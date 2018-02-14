<?php
namespace Framework\Core\Http\Methods;

use Framework\Core\Helpers\Str;
use Framework\Core\Database\Model;

use Framework\Core\Exceptions\ClassMethodException;
use Framework\Core\Exceptions\QueryException;

use Framework\Core\Http\Request;
use Framework\Core\Http\Response;
use Framework\Core\Http\ResponseData;
use Framework\Core\Http\SubRoute;

/**
 * Class Base
 *
 * @property string   $endpoint
 * @property string   $controller
 * @property string[] $guards
 * @property string[] $middleware
 * @property string[] $transformers
 * @property mixed    $result
 * @property Request  $request
 *
 * @package Framework\Core\Http\Methods
 */
class Base
{
	/** @var string $endpoint */
	protected $endpoint;

	/** @var string $controller */
	protected $controller;

	/** @var string[] $arguments */
	protected $arguments = [];

	/** @var string[] $arguments */
	protected $headers = [];

	/** @var string[] $guards */
	protected $guards = [];

	/** @var string[] $middleware */
	protected $middleware = [];

	/** @var string[] $transformers */
	protected $transformers = [];

	/** @var mixed $result */
	protected $result;

	/** @var Request $request */
	protected $request;

	/**
	 * Set endpoint
	 *
	 * @param string $endpoint
	 */
	public function __construct($endpoint)
	{
		$this->endpoint = $endpoint;
		$this->request  = new Request;
	}

	/**
	 * Magically set properties
	 *
	 * @param string       $key
	 * @param string|array $value
	 * @return $this
	 */
	final public function __set($key, $value)
	{
		if (property_exists($this, $key)) {
			if (!is_string($value) || !is_array($value) || $key === 'request' || $key === 'request') {
				return $this;
			}

			if ($key === 'endpoint' || $key === 'controller') {
				$this->{$key} = $value;

				return $this;
			}

			$this->{$key} = is_array($value)
				? $value
				: [ $value ];
		}

		return $this;
	}

	/**
	 * Set endpoint
	 *
	 * @param string $endpoint
	 * @return $this
	 */
	final public function endpoint($endpoint)
	{
		$this->endpoint = $endpoint;

		return $this;
	}

	/**
	 * Prepend endpoint
	 *
	 * @param string $prepend
	 * @return $this
	 */
	final public function prependEndpoint($prepend)
	{
		$prepend = !preg_match('/\/$/', $prepend)
			? $prepend . '/'
			: $prepend;

		$this->endpoint = $prepend . $this->endpoint;

		return $this;
	}

	/**
	 * Append endpoint
	 *
	 * @param string $append
	 * @return $this
	 */
	final public function appendEndpoint($append)
	{
		$append = !preg_match('/\/$/', $this->endpoint)
			? '/' . $append
			: $append;

		$this->endpoint .= $append;

		return $this;
	}

	/**
	 * Set controller
	 *
	 * @param string $controller
	 * @return $this
	 */
	final public function controller($controller)
	{
		$this->controller = $controller;

		return $this;
	}

	/**
	 * Set guards
	 *
	 * @param array|string $guards
	 * @return $this
	 */
	final public function guards($guards)
	{
		$this->guards = is_array($guards)
			? $guards
			: [ $guards ];

		return $this;
	}

	/**
	 * Set middleware
	 *
	 * @param array|string $middleware
	 * @return $this
	 */
	final public function middleware($middleware)
	{
		$this->middleware = is_array($middleware)
			? $middleware
			: [ $middleware ];

		return $this;
	}

	/**
	 * Set transformers
	 *
	 * @param array|string $transformers
	 * @return $this
	 */
	final public function transformers($transformers)
	{
		$this->transformers = is_array($transformers)
			? $transformers
			: [ $transformers ];

		return $this;
	}

	/**
	 * Build sub routes
	 *
	 * @param SubRoute[] $routes
	 * @returns $this
	 */
	final public function routes(Array $routes)
	{
		/** @var Base $route */
		foreach ($routes as &$route) {

			// Prepend endpoint
			$route->prependEndpoint($this->endpoint);

			// Set guards
			$route->guards = array_merge($route->guards, $this->guards);

			// Set middleware
			$route->middleware = array_merge($route->middleware, $this->middleware);

			// Set transformers
			// $route->transformers = array_merge($route->transformers, $this->transformers);

			// Initialize route
			$route->call();
		}

		return $this;
	}

	/**
	 * Handle calling of given controller
	 *
	 * @param string $controller
	 * @return void
	 */
	final public function call($controller = null)
	{
		try {
			if ($this->__compareRequest()) {
				if (!$controller && !($controller = $this->controller)) {
					return;
				}

				try {

					// Call any guards that might be set
					$this->__callGuards();

					// Call any middleware that might be set
					$this->__callMiddleware();

					// Call controller
					$this->__callControllers($controller);

					// Call any transformers that might be set
					$this->__callTransformers();

					// Return result
					if ($this->result instanceof ResponseData) {

						/** @var ResponseData $result */
						$result = $this->result;
						Response::success($result->data, $result->meta, $result->status);
					}

					Response::success($this->result);
				} catch (ClassMethodException $e) {
					Response::error($e->getMessage(), 500);
				}
			}
		} catch (\ReflectionException $e) {
			Response::error('Unable to compare request URI');
		}
	}

	/**
	 * Call any guards that are set
	 *
	 * @throws ClassMethodException
	 * @return void
	 */
	final private function __callGuards()
	{
		// Loop through each guard
		foreach ($this->guards as $guard) {
			$this->__callClassMethod($guard);
		}
	}

	/**
	 * Call any middleware that are set
	 *
	 * @throws ClassMethodException
	 * @return void
	 */
	final private function __callMiddleware()
	{
		// Loop through each guard
		foreach ($this->middleware as $middleware) {
			$res = $this->__callClassMethod($middleware);

			if ($res instanceof Request) {
				$this->request = $res;
			}
		}
	}

	/**
	 * Call the controller
	 *
	 * @param string $controller
	 * @throws ClassMethodException
	 * @return mixed
	 */
	final private function __callControllers($controller)
	{
		return $this->result = $this->__callClassMethod($controller);
	}

	/**
	 * Call any transformers that are set
	 *
	 * @throws ClassMethodException
	 * @return void
	 */
	final private function __callTransformers()
	{
		// Loop through each transformer
		foreach ($this->transformers as $transformer) {
			$this->result = $this->__callClassMethod($transformer);
		}
	}

	/**
	 * Attempt to call a given class
	 *
	 * @param string $callable
	 * @throws ClassMethodException
	 * @return mixed
	 */
	final private function __callClassMethod($callable)
	{
		// Get what type of class needs to be called
		$type      = str_replace('__call', '', debug_backtrace()[1]['function']);
		$namespace = env(strtoupper($type) . '_NAMESPACE', '\App\\Http\\' . $type . '\\');

		// Compile controller and method
		$bits   = preg_split('/::|@/', $callable);
		$class  = (strpos($bits[0], '\\') === false ? $namespace : '') . $bits[0];
		$method = $bits[1];

		// Check that class and method exist
		if (!is_callable([ $class, $method ])) {
			throw new ClassMethodException('Unable to call: ' . $callable);
		}

		// Call  method
		return call_user_func([ $class, $method ], $this->request);
	}

	/**
	 * Check that endpoint conditions are met
	 * TODO compare uris bit by bit
	 *
	 * @throws \ReflectionException
	 * @return bool
	 */
	final private function __compareRequest()
	{
		// Compare method
		if ($_SERVER['REQUEST_METHOD'] === strtoupper((new \ReflectionClass($this))->getShortName())) {

			// Check if endpoint has arguments
			if (!!preg_match_all('/:(\w+)/', $this->endpoint, $endpoint_arguments)) {

				// Build regex string
				$clean_endpoint = str_replace($endpoint_arguments[0], '(\w+)', $this->endpoint);
				$clean_endpoint = addcslashes($clean_endpoint, '/');

				// Compare endpoints
				if (!!preg_match('/' . $clean_endpoint . '/i', $_SERVER['REQUEST_URI'], $uri_matches)) {
					array_shift($uri_matches);

					$namespace = env('MODEL_NAMESPACE', '\App\\Database\\Models\\');

					$this->__collectStaticData();

					// Store arguments
					foreach ($endpoint_arguments[1] as $key => $argument) {
						$model = $namespace . Str::classify($argument);

						if (class_exists($model)) {
							try {

								/** @var Model $model */
								$model = new $model;

								$this->request->setModel($argument, $model->find($uri_matches[ $key ]));

								continue;
							} catch (QueryException $e) {
								log($e);
							};
						}

						$this->request->setArgument($argument, $uri_matches[ $key ]);
					}

					return true;
				}

				return false;
			}

			// If no arguments are passed then do a like for like comparison
			if ($_SERVER['REQUEST_URI'] === $this->endpoint) {
				$this->__collectStaticData();

				return true;
			}
		}

		return false;
	}

	final private function __collectStaticData()
	{
		$json = json_decode(file_get_contents('php://input'), true);

		foreach (array_merge($_REQUEST, $json) as $item => $value) {
			$this->request->setData($item, $value);
		}

		foreach ($_SERVER as $header => $value) {
			if (strpos($header, 'HTTP_') === 0) {
				$this->request->setHeader(str_replace('HTTP_', '', $header), $value);
			}
		}
	}
}