<?php
namespace Framework\Core\Database;

use Framework\Core\Exceptions\ModelException;
use Framework\Core\Exceptions\QueryException;

final class ModelGroup
{
	/** @var Model[] $models */
	protected $models = [];

	final public function __construct(array $models = [])
	{
		$this->models = $models;
	}

	/**
	 * Get all models' relationship
	 *
	 * @param string|string[] $relationship
	 * @throws ModelException
	 * @return $this
	 */
	final public function with($relationship)
	{
		$relationships = is_array($relationship) ? $relationship : func_get_args();

		foreach ($this->models as &$model) {
			$model->with($relationships);
		}

		return $this;
	}

	/**
	 * Return all models as arrays
	 *
	 * @return array
	 */
	final public function toArray()
	{
		$arr = [];

		foreach ($this->models as $model) {
			$arr[] = $model->toArray();
		}

		return $arr;
	}

	/**
	 * Delete all model instances
	 *
	 * @throws ModelException|QueryException
	 * @return $this
	 */
	final public function delete()
	{
		foreach ($this->models as $key => $model) {
			$model->delete();

			array_splice($this->models, $key, 1);
		}

		return $this;
	}
}