<?php
namespace Framework\Core\Interfaces\Database\Queries;

final class Field
{
	public $field;
	public $table;
	public $value;

	public function __construct($field = null, $table = null, $value = null)
	{
		$this->field = $field;
		$this->table = $table;
		$this->value = $value;
	}
}