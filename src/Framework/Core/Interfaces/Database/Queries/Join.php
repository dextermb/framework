<?php
namespace Framework\Core\Interfaces\Database\Queries;

final class Join
{
	/** @var Field $local */
	public $local;

	/** @var Field $foreign */
	public $foreign;

	/** @var integer $type */
	public $type;

	public function __construct(Field $local = null, Field $foreign = null, $type = null)
	{
		$this->local   = $local;
		$this->foreign = $foreign;
		$this->type    = $type;
	}
}