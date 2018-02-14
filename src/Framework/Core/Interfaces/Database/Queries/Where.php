<?php
namespace Framework\Core\Interfaces\Database\Queries;

final class Where
{
	/** @var Field $field */
	public $field;

	/** @var Field|string|number|array $comparison */
	public $comparison;

	/** @var string $comparitor */
	public $comparitor;

	/** @var string $relation */
	public $relation;

	public function __construct(Field $field, $comparison, string $comparitor = null, string $relation = null)
	{
		$this->field      = $field;
		$this->comparison = $comparison;
		$this->comparitor = strtolower($comparitor) ?: F_DB_EQUALS;
		$this->relation   = strtolower($relation) ?: F_DB_AND;
	}
}