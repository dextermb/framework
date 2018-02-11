<?php
namespace Framework\Core\Database\Queries;

use Framework\Core\Exceptions\QueryException;
use Framework\Core\Interfaces\Database\Queries\Field;

final class Update extends Base
{
	/**
	 * Set the fields to update
	 *
	 * @param Field|Field[] $fields
	 * @return $this
	 */
	public function set($fields)
	{
		$this->fields = is_array($fields) ? $fields : func_get_args();

		return $this;
	}

	/**
	 * Set the table to update
	 *
	 * @param string $table
	 * @return $this
	 */
	public function update(string $table)
	{
		$this->table = $table;

		return $this;
	}

	/**
	 * Build the query
	 *
	 * @throws QueryException
	 * @return $this|string
	 */
	public function build()
	{
		if (!$this->table || !count($this->fields)) {
			throw new QueryException('Missing table or fields for update query');
		}

		$bits = [
			'UPDATE',
			$this->table,
			'SET',
			$this->__buildFields(),
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

	/**
	 * Build update fields
	 *
	 * @return string
	 */
	private function __buildFields()
	{
		$bits = [];

		foreach ($this->fields as $field) {
			$f = [];

			if (!($field instanceof Field)) {
				continue;
			}

			$f[] = ($field->table ?: $this->table) . '.' . $field->field;
			$f[] = '=';

			if (!$this->prepared) {
				$f[] = is_numeric($field->value)
					? $field->value
					: '\'' . $field->value . '\'';
			} else {
				$f[]               = '?';
				$this->variables[] = $field->value;
			}

			$bits[] = implode(' ', $f);
		}

		return implode(', ', $bits);
	}
}