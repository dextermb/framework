<?php
namespace Framework\Core\Database;

use Carbon\Carbon;

use Framework\Core\Helpers\Arr;
use Framework\Core\Helpers\Str;

use Framework\Core\Exceptions\ArrayException;
use Framework\Core\Exceptions\QueryException;
use Framework\Core\Exceptions\ModelException;

use Framework\Core\Interfaces\Database\Queries\Field;
use Framework\Core\Interfaces\Database\Queries\Where;

class Model
{
	/** @var string $table */
	protected $table;

	/** @var string $primary_field */
	protected $primary_field = 'id';

	/** @var bool $auto_increment */
	protected $auto_increment = true;

	/** @var string[] $dates */
	protected $dates = [];

	/** @var string[] $fields */
	protected $fields = [];

	/** @var string[] $hidden */
	protected $hidden = [];

	/** @var array $attributes */
	protected $attributes = [];

	/** @var Model[] $relationships */
	protected $relationships = [];

	/** @var bool $stored */
	protected $stored = false;

	final public function __construct()
	{
		try {
			$reflection  = new \ReflectionClass($this);
			$this->table = $this->table ?: Str::pluralize(Str::snakey($reflection->getShortName()));
		} catch (\ReflectionException $e) {
			log($e);
		}
	}

	/**
	 * Return an attribute
	 *
	 * @param string $property
	 * @return mixed|null
	 */
	final public function __get(string $property)
	{
		if ($property === 'primary_key') {
			$property = $this->primary_field;
		}

		if (array_key_exists($property, $this->attributes)) {
			if (in_array($property, $this->dates)) {
				return Carbon::createFromTimestamp(strtotime($this->attributes[ $property ]));
			}

			return $this->attributes[ $property ];
		}

		if (array_key_exists($property, $this->relationships)) {
			return $this->relationships[ $property ];
		}

		if (method_exists($this, $property)) {
			return $this->{$property}();
		}

		return null;
	}

	/**
	 * Return all attributes
	 *
	 * @return array
	 */
	final public function get()
	{
		return array_filter($this->attributes, function ($attribute) {
			return in_array($attribute, $this->fillable(true, false));
		}, ARRAY_FILTER_USE_KEY);
	}

	/**
	 * Return relationships
	 *
	 * @param string|string[] $relationship
	 * @throws ModelException
	 * @return $this
	 */
	final public function with($relationship)
	{
		if (!$this->hasPrimaryKey()) {
			throw new ModelException('A model must have a primary key before attempting to get relationships');
		}

		$relationships = is_array($relationship) ? $relationship : func_get_args();

		foreach ($relationships as $relationship) {
			$bits         = explode('.', $relationship);
			$relationship = $bits[0];

			// Check that the base relationship exists
			if (!method_exists($this, $relationship)) {
				continue;
			}

			/** @var Model|Model[]|array|null $models */
			$models = $this->relationships[ $relationship ] = $this->{$relationship}() ?: null;

			if (!empty($models) && count($bits) > 1) {
				unset($bits[0]);

				// If there are many models, loop through and get the relationship for each one
				if (is_array($models)) {
					foreach ($models as $model) {
						$model->with(implode('.', $bits));
					}

					continue;
				}

				// If there's only one model, simply get the relationship
				if ($models instanceof Model) {
					$models->with(implode('.', $bits));

					continue;
				}
			}
		}

		return $this;
	}

	/**
	 * Instantiate a model without saving it
	 *
	 * @param string[] $attributes
	 * @throws ArrayException
	 * @return $this
	 */
	final public function make(array $attributes)
	{
		Arr::associative($attributes, true);

		$fillable = $this->fillable();

		$this->attributes = array_filter($attributes, function ($attribute) use ($fillable) {
			return in_array($attribute, $fillable);
		}, ARRAY_FILTER_USE_KEY);

		return $this;
	}

	/**
	 * Instantiate a model and save it
	 *
	 * @param string[] $attributes
	 * @throws ArrayException|QueryException
	 * @return Model $model
	 */
	final public function create($attributes)
	{
		/** @var Model $model */
		$model = (new $this);

		$model->make($attributes);

		$fields = $model->attributes;

		array_Walk($fields, function (&$value, $field) {
			$value = new Field($field, null, $value);
		});

		$primary_value = Query::insert($fields)
							  ->into($model->table)
							  ->run();

		if ($model->auto_increment) {
			$model->attributes[ $model->primary_field ] = $primary_value;
		}

		$model->stored = true;

		return $model;
	}

	/**
	 * Update an instance's attributes
	 *
	 * @param string[] $attributes
	 * @param bool     $save
	 * @throws ModelException|ArrayException|QueryException
	 * @return $this
	 */
	final public function update(array $attributes, bool $save = true)
	{
		Arr::associative($attributes, true);

		$fillable = $this->fillable(false);

		foreach ($attributes as $attribute => $value) {
			if (in_array($attribute, $fillable)) {
				$this->attributes[ $attribute ] = $value;
			}
		}

		if ($save) {
			$this->save();
		}

		return $this;
	}

	/**
	 * Deletes a model instance
	 *
	 * @throws ModelException|QueryException
	 * @return bool
	 */
	final public function delete()
	{
		if (!$this->hasPrimaryKey()) {
			throw new ModelException('Model must have a primary key to be deleted');
		}

		Query::delete($this->table)
			 ->where(new Where(
				 new Field($this->primary_field),
				 $this->attributes[ $this->primary_field ]
			 ))
			 ->run();

		return true;
	}

	/**
	 * Push attributes up to the database
	 *
	 * @throws ModelException|QueryException
	 * @return bool
	 */
	final public function save()
	{
		if (!$this->hasPrimaryKey()) {
			throw new ModelException('Model must have a primary key to be updated');
		}

		Query::update($this->table)
			 ->set($this->__buildFields(false))
			 ->where(
				 new Where(
					 new Field($this->primary_field),
					 $this->attributes[ $this->primary_field ]
				 )
			 )
			 ->run();

		return true;
	}

	/**
	 * Find one instance of a model
	 *
	 * @param string|integer $value
	 * @param string         $field
	 * @throws QueryException
	 * @return $this
	 */
	final public function find($value, string $field = null)
	{
		$result = Query::select($this->__buildFields())
					   ->from($this->table)
					   ->where(
						   new Where(
							   new Field(
								   $field ?: $this->primary_field,
								   $table = null
							   ),
							   $value,
							   $comparitor = F_DB_LIKE
						   )
					   )
					   ->limit(1)
					   ->run(true, false);

		if (!is_null($result)) {
			$this->attributes = $result;
			$this->stored     = true;
		}

		return $this;
	}

	/**
	 * Find a model instance or throw
	 *
	 * @param string|integer $value
	 * @param string         $field
	 * @throws ModelException|QueryException
	 * @return $this
	 */
	final public function findOrFail($value, string $field = null)
	{
		$this->find($value, $field);

		if (!$this->stored) {
			throw new ModelException('Model instance not found');
		}

		return $this;
	}

	/**
	 * Find or create a model instance
	 *
	 * @param string[]       $fill
	 * @param string|integer $value
	 * @param string         $field
	 * @throws ArrayException|QueryException
	 * @return Model
	 */
	final public function findOrCreate(array $fill, $value, string $field = null)
	{
		$this->find($value, $field);

		if (!$this->stored) {
			return $this->create(array_merge($field === $this->primary_field ? [] : [ $field => $value ], $fill));
		}

		return $this;
	}

	/**
	 * Find many instances of a model
	 *
	 * @param string|integer $value
	 * @param string         $field
	 * @throws ArrayException|QueryException
	 * @return ModelGroup
	 */
	final public function findMany($value, string $field = null)
	{
		$results = Query::select($this->__buildFields())
						->from($this->table)
						->where(
							new Where(
								new Field(
									$field ?: $this->primary_field,
									$table = null
								),
								$value,
								$comparitor = F_DB_LIKE
							)
						)
						->run(false, false);

		if (!empty($results)) {
			$models = [];

			foreach ($results as $result) {

				/** @var Model $model */
				$model         = new $this;
				$model->stored = true;

				$models[] = $model->make($result);
			}

			return new ModelGroup($models);
		}

		return new ModelGroup;
	}

	/**
	 * Return a single instance of a relationship model
	 *
	 * @param Model  $model
	 * @param string $foreign_field
	 * @param string $local_field
	 * @throws ModelException|QueryException|\ReflectionException
	 * @return Model
	 */
	final public function hasOne(Model $model, string $foreign_field = null, string $local_field = null)
	{
		$local_field = $local_field ?: Str::primaryField($model);

		if (!in_array($local_field, array_keys($this->attributes))) {
			throw new ModelException('"' . $local_field . '" does not exist in stored attributes');
		}

		return $model->find($this->attributes[ $local_field ], $foreign_field);
	}

	/**
	 * Return many instances of a relationship model
	 *
	 * @param Model  $model
	 * @param string $foreign_field
	 * @param string $local_field
	 * @throws ModelException|ArrayException|QueryException|\ReflectionException
	 * @return ModelGroup
	 */
	final public function hasMany(Model $model, string $foreign_field = null, string $local_field = null)
	{
		$local_field = $local_field ?: Str::primaryField($model);

		if (!in_array($local_field, array_keys($this->attributes))) {
			throw new ModelException('"' . $local_field . '" does not exist in stored attributes');
		}

		return $model->findMany($this->attributes[ $local_field ], $foreign_field);
	}

	/**
	 * Check if a model has a primary key or not
	 *
	 * @return bool
	 */
	final public function hasPrimaryKey()
	{
		return in_array($this->primary_field, array_keys($this->attributes));
	}

	/**
	 * Check if a model is stored or not
	 *
	 * @return bool
	 */
	final public function isStored()
	{
		return $this->stored;
	}

	/**
	 * Get fillable fields
	 *
	 * @param bool $show_primary
	 * @param bool $show_hidden
	 * @return array|string[]
	 */
	final public function fillable(bool $show_primary = true, bool $show_hidden = true)
	{
		return array_merge(($show_primary ? [ $this->primary_field ] : []), $this->fields, $this->dates, ($show_hidden ? $this->hidden : []));
	}

	/**
	 * Return attributes and relationships as array
	 *
	 * @return array
	 */
	final public function toArray()
	{
		return array_merge($this->attributesToArray(), $this->relationshipsToArray());
	}

	/**
	 * Return attributes that aren't hidden
	 *
	 * @param array $arr
	 * @return array
	 */
	final protected function attributesToArray(array &$arr = [])
	{
		$arr = $arr ?: $this->attributes;

		foreach ($arr as $attribute => $value) {
			if (in_array($attribute, $this->hidden)) {
				unset($arr[ $attribute ]);

				continue;
			}

			if (is_array($value)) {
				$this->attributesToArray($value);
			}
		}

		return $arr;
	}

	/**
	 * Return relationship models as an array
	 *
	 * @param array $arr
	 * @return array|Model[]
	 */
	final protected function relationshipsToArray(array &$arr = [])
	{
		$arr = $arr ?: $this->relationships;

		foreach ($arr as $relationship => &$model) {
			if ($model instanceof Model) {
				$model = $model->toArray();

				continue;
			}

			if (is_array($model)) {
				$this->relationshipsToArray($model);
			}
		}

		return $arr;
	}

	/**
	 * Build fields for select queries
	 *
	 * @param bool $string
	 * @return array|string
	 */
	final private function __buildFields(bool $string = true)
	{
		if ($string) {
			$fields = array_merge([ $this->primary_field ], $this->fields, $this->dates, $this->hidden);

			return count($fields) > 1 ? $fields : '*';
		}

		$fields = array_map(function ($field) {
			if (in_array($field, array_keys($this->attributes))) {
				return new Field(
					$field,
					null,
					$this->attributes[ $field ]
				);
			}

			return $field;
		}, array_merge($this->fields, $this->dates, $this->hidden));

		return array_filter($fields, function ($field) {
			return $field instanceof Field;
		});
	}
}