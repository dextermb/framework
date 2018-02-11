<?php
namespace Framework\Core\Database\Queries;

use PDO;

use Framework\Core\Exceptions\QueryException;
use Framework\Core\Interfaces\Database\Queries\Field;
use Framework\Core\Interfaces\Database\Queries\Join;
use Framework\Core\Interfaces\Database\Queries\Where;

final class Select extends Base
{
	/** @var Join[] $joins */
	protected $joins = [];

	/** @var integer $limit */
	protected $limit;

	/** @var integer $offset */
	protected $offset;

	/**
	 * Set fields to select
	 *
	 * @param array|mixed $fields
	 * @return $this
	 */
	public function select($fields)
	{
		$fields = is_array($fields) ? $fields : func_get_args();

		$this->fields = array_map(function (&$field) {
			if (!($field instanceof Field)) {
				return new Field($field);
			}

			return $field;
		}, $fields);

		return $this;
	}

	/**
	 * Set base table to select from
	 *
	 * @param string $table
	 * @return $this
	 */
	public function from(string $table)
	{
		$this->table = $table;

		return $this;
	}

	/**
	 * Set joins
	 *
	 * @param Join[] $join
	 * @return $this
	 */
	public function join($join)
	{
		$this->joins = func_get_arg(0);

		return $this;
	}

	/**
	 * Set limit
	 *
	 * @param integer $limit
	 * @return $this
	 */
	public function limit(int $limit)
	{
		$this->limit = $limit;

		return $this;
	}

	/**
	 * Set offset
	 *
	 * @param integer $offset
	 * @return $this
	 */
	public function offset(int $offset)
	{
		$this->offset = $offset;

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
			throw new QueryException('Missing table or fields for select query');
		}

		$bits = [
			'SELECT',
			$this->__buildFields(),
			'FROM',
			$this->table,
		];

		if (!!count($this->joins)) {
			$bits[] = $this->__buildJoins();
		}

		if (!!count($this->wheres)) {
			$bits[] = $this->__buildWheres();
		}

		if ($this->limit > 0) {
			$bits[] = 'LIMIT ' . $this->limit;
		}

		if ($this->offset > 0) {
			$bits[] = 'OFFSET ' . $this->offset;
		}

		$this->query = implode(' ', $bits);

		return $this;
	}

	/**
	 * Execute the built query
	 *
	 * @param bool $collapse
	 * @param bool $minify
	 * @throws QueryException
	 * @return mixed
	 */
	public function run(bool $collapse = true, bool $minify = true)
	{
		if (!$this->query) $this->build();

		$sth = $this->connection->prepare($this->query);

		if (!$sth->execute($this->variables)) {
			throw new QueryException('Unable to execute select query');
		}

		if (!$sth->rowCount()) {
			if (!$this->limit || $this->limit > 1) {
				return [];
			}

			return null;
		}

		if ($this->limit === 1 && !!$collapse) {
			if (count($this->fields) === 1 && !!$minify) {
				return $sth->fetchColumn();
			}

			return $sth->fetch(PDO::FETCH_ASSOC);
		}

		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Build select fields
	 *
	 * @return string
	 */
	private function __buildFields()
	{
		$bits = [];

		foreach ($this->fields as $field) {
			$bits[] = ($field->table ?: $this->table) . '.' . $field->field;
		}

		return implode(', ', $bits);
	}

	/**
	 * Build joins
	 *
	 * @return string
	 */
	private function __buildJoins()
	{
		$bits = [];

		foreach ($this->joins as $join) {
			$j = [];

			switch ($join->type) {
				case F_DB_LEFT_JOIN:
					$j[] = 'LEFT JOIN';
					break;
				case F_DB_RIGHT_JOIN:
					$j[] = 'RIGHT JOIN';
					break;
				case F_DB_INNER_JOIN:
				default:
					$j[] = 'INNER JOIN';
					break;
			}

			$j[] = $join->foreign->table;
			$j[] = 'ON';
			$j[] = ($join->local->table ?: $this->table) . '.' . $join->local->field;
			$j[] = '=';
			$j[] = $join->foreign->table . '.' . $join->foreign->field;


			$bits[] = implode(' ', $j);
		}

		return implode(' ', $bits);
	}
}