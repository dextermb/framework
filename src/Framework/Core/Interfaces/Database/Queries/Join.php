<?php
namespace Framework\Core\Interfaces\Database\Queries;

final class Join
{
	/** @var Field $local */
	public $local;

	/** @var Field $foreign */
	public $foreign;

	/** @var string $type */
	public $type;

	public function __construct(Field $local, Field $foreign, string $type = null)
	{
		$this->local   = $local;
		$this->foreign = $foreign;
		$this->type    = strtolower($type) ?: F_DB_INNER_JOIN;
	}
}