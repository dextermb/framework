<?php
namespace Framework\Core\Interfaces\Database\Queries;

final class Where
{
	/** @var Field $field */
	public $field;

	/** @var Field|string|number|array $comparison */
	public $comparison;

	/** @var integer $comparitor */
	public $comparitor;

	/** @var integer $relation */
	public $relation;

	public function __construct(Field $field = null, $comparison = null, $comparitor = null, $relation = null)
	{
		$this->field      = $field;
		$this->comparison = $comparison;
		$this->comparitor = $comparitor ?: F_DB_EQUALS;
		$this->relation   = $relation ?: F_DB_AND;
	}
}