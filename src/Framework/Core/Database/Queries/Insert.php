<?php
namespace Framework\Core\Database\Queries;

use Framework\Core\Exceptions\QueryException;
use Framework\Core\Interfaces\Database\Queries\Field;

final class Insert extends Base
{
	/**
	 * Set fields
	 *
	 * @param Field|Field[] $fields
	 * @return $this
	 */
	public function insert($fields)
	{
		$this->fields = is_array($fields) ? $fields : func_get_args();

		return $this;
	}

	/**
	 * Set table
	 *
	 * @param string $table
	 * @return $this
	 */
	public function into(string $table)
	{
		$this->table = $table;

		return $this;
	}

	/**
	 * Build the query
	 *
	 * @throws QueryException
	 * @return $this|string;
	 */
	public function build()
	{
		if (!$this->table || !count($this->fields)) {
			throw new QueryException('Missing table or fields for select query');
		}

		$bits = [
			'INSERT INTO',
			$this->table,
			'SET',
			$this->__buildFields(),
		];

		$this->query = implode(' ', $bits);

		return $this;
	}

	/**
	 * Run the built query
	 *
	 * @throws QueryException
	 * @return integer
	 */
	public function run()
	{
		if (!$this->query) $this->build();

		$sth = $this->connection->prepare($this->query);

		if (!$sth->execute($this->variables)) {
			throw new QueryException('Unable to execute insert query');
		}

		return $this->connection->lastInsertID();
	}

	/**
	 * Build insert fields
	 *
	 * @return string
	 */
	private function __buildFields()
	{
		$bits = [];

		foreach ($this->fields as $field) {
			$f = [];

			// Skip invalid fields
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