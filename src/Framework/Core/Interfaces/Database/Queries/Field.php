<?php
namespace Framework\Core\Interfaces\Database\Queries;

final class Field
{
	/** @var string $field */
	public $field;

	/** @var string $table */
	public $table;

	/** @var string $value */
	public $value;

	public function __construct(string $field, string $table = null, string $value = null)
	{
		$this->field = $field;
		$this->table = $table;
		$this->value = $value;
	}
}