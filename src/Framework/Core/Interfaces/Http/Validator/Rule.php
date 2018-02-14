<?php
namespace Framework\Core\Interfaces\Http\Validator;

class Rule
{
	/** @var string $data */
	public $data;

	/** @var string $test */
	public $test;

	/** @var string $table */
	public $table;

	/** @var string $field */
	public $field;

	public function __construct(string $data, string $test, string $table = null, string $field = null)
	{
		$this->data  = $data;
		$this->test  = $test;
		$this->table = $table;
		$this->field = $field;
	}
}