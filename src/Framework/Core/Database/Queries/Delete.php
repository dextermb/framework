<?php
namespace Framework\Core\Database\Queries;

use Framework\Core\Exceptions\QueryException;

final class Delete extends Base
{
	/**
	 * Set a table to delete from
	 *
	 * @param string $table
	 * @return $this
	 */
	public function delete(string $table)
	{
		$this->table = $table;

		return $this;
	}

	/**
	 * Build the query
	 *
	 * @throws QueryException
	 * @return $this
	 */
	public function build()
	{
		if (!$this->table) {
			throw new QueryException('Missing table for delete query');
		}

		$bits = [
			'DELETE FROM',
			$this->table
		];

		if (!!count($this->wheres)) {
			$bits[] = $this->__buildWheres();
		}

		$this->query = implode(' ', $bits);

		return $this;
	}

	/**
	 * Run the built query
	 *
	 * @throws QueryException
	 * @return bool
	 */
	public function run()
	{
		if (!$this->query) $this->build();

		$sth = $this->connection->prepare($this->query);

		if (!$sth->execute($this->variables)) {
			throw new QueryException('Unable to execute update query');
		}

		return true;
	}
}