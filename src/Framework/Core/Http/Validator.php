<?php
namespace Framework\Core\Http;

use Framework\Core\Database\Query;
use Framework\Core\Exceptions\QueryException;
use Framework\Core\Interfaces\Http\Validator\Rule;
use Framework\Core\Interfaces\Database\Queries\Field;
use Framework\Core\Interfaces\Database\Queries\Where;

class Validator
{
	const DATABASE_VALIDATORS
		= [ F_VAL_UNIQUE, F_VAL_EXISTS, F_VAL_NEW ];

	/** @var Request $request */
	protected $request;

	/** @var array $rules */
	protected $rules = [];

	/** @var array $errors */
	protected $errors = [];

	/** @var array $custom_errors */
	protected $custom_errors = [];

	public function __construct(Request &$request)
	{
		$this->request = $request;
	}

	/**
	 * Get all errors
	 *
	 * @return array
	 */
	public function errors()
	{
		return $this->errors;
	}

	/**
	 * @param string $data
	 * @param string $test
	 * @param string $table
	 * @param string $field
	 * @return $this
	 */
	public function setRule(string $data, string $test, string $table = null, string $field = null)
	{
		$rule = new Rule($data, strtolower($test), $table, $field);

		if (!isset($this->rules[ $data ])) {
			$this->rules[ $data ] = [];
		}

		$this->rules[ $data ][] = $rule;

		return $this;
	}

	public function validate()
	{
		if (empty($this->rules)) {
			return true;
		}

		foreach ($this->rules as $data => $rules) {
			$errors = [];

			/** @var Rule $rule */
			foreach ($rules as $rule) {
				if (in_array($rule->test, self::DATABASE_VALIDATORS)) {
					if (!$rule->table || !$rule->field) {
						continue;
					}

					try {
						$query = Query::select($rule->field)
									  ->from($rule->table)
									  ->where(new Where(
										  new Field($rule->field),
										  $this->request->getData($data)
									  ))
									  ->limit(1)
									  ->run();

						switch ($rule->test) {
							case F_VAL_UNIQUE:

								if (!is_null($query)) {
									$errors[] = $data . ' must be unique';
								}

								break;
							case F_VAL_EXISTS:

								if (is_null($query)) {
									$errors[] = $data . ' must exist';
								}

								break;
						}
					} catch (QueryException $e) {
						log($e);
					}

					continue;
				}

				switch ($rule->test) {
					case F_VAL_REQUIRED:
						if (empty($this->request->getData($data))) {
							$errors[] = $data . ' is required';
						}

						break;
					case F_VAL_STRING:
						if (!is_string($this->request->getData($data))) {
							$errors[] = $data . ' must be a string';
						}

						break;
					case F_VAL_NUMERIC:
						if (!is_numeric($this->request->getData($data))) {
							$errors[] = $data . ' must be numeric';
						}

						break;
					case F_VAL_BOOL:
						if (!is_bool($this->request->getData($data))) {
							$errors[] = $data . ' must be a bool';
						}

						break;
					case F_VAL_EMAIL:
						if (!filter_var($this->request->getData($data), FILTER_VALIDATE_EMAIL)) {
							$errors[] = $data . ' must be an email address';
						}

						break;
					case F_VAL_ARRAY:
						if (!is_array($this->request->getData($data))) {
							$errors[] = $data . ' must be an array';
						}

						break;
					case F_VAL_OBJECT:
						if (!is_object($this->request->getData($data))) {
							$errors[] = $data . ' must be an object';
						}

						break;
					case F_VAL_NULLABLE:
					default:
						break;
				}
			}

			if (!empty($errors)) {
				$this->errors[ $data ] = $errors;
			};
		}

		return empty($this->errors);
	}
}