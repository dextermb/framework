<?php
namespace Framework\Core\Database\Queries;

use PDO;
use Framework\Core\Database\Connection;
use Framework\Core\Exceptions\QueryException;
use Framework\Core\Interfaces\Database\Queries\Field;
use Framework\Core\Interfaces\Database\Queries\Where;

abstract class Base
{
	/** @const integer[] BASIC_COMPARISONS */
	const BASIC_COMPARISONS
		= [
			F_DB_GREATER_THAN, F_DB_GREATER_OR_EQUAL_THAN,
			F_DB_LESS_THAN, F_DB_LESS_OR_EQUAL_THAN,
			F_DB_LIKE, F_DB_EQUALS,
		];

	/** @var PDO $connection */
	protected $connection;

	/** @var bool $prepared */
	protected $prepared;

	/** @var string $query */
	protected $query;

	/** @var string|integer[] $variables */
	protected $variables = [];

	/** @var string $table */
	protected $table;

	/** @var Field[] $fields */
	protected $fields = [];

	/** @var Where[] $wheres */
	protected $wheres = [];

	public function __construct()
	{
		$this->connection = Connection::init();
		$this->prepared   = true;
	}

	/**
	 * Set wheres
	 *
	 * @param Where|Where[] $wheres
	 * @return $this
	 */
	public function where($wheres)
	{
		$this->wheres = is_array($wheres) ? $wheres : func_get_args();

		return $this;
	}

	/**
	 * Return the query
	 *
	 * @return string
	 */
	public function getQuery()
	{
		return $this->query;
	}

	/**
	 * Return prepared variables
	 *
	 * @return array
	 */
	public function getVariables()
	{
		return $this->variables;
	}

	/**
	 * Build wheres
	 *
	 * @return string
	 */
	final protected function __buildWheres()
	{
		$wheres_length = count($this->wheres);
		$bits          = [ 'WHERE' ];


		for ($i = 0; $i < $wheres_length; $i++) {
			$w = [];

			/** @var Where $current */
			$current = $this->wheres[ $i ];

			/** @var Where $next */
			$next = $this->wheres[ $i + 1 ];

			if ($i > 0) {
				switch ($current->relation) {
					case F_DB_OR:
						$w[] = 'OR';
						break;
					case F_DB_AND:
					default:
						$w[] = 'AND';
						break;
				}
			}

			if ($next->relation === F_DB_OR) {
				$w[] = '(';
			}

			$w[] = ($current->field->table ?: $this->table) . '.' . $current->field->field;

			if (!in_array($current->comparitor, self::BASIC_COMPARISONS)) {
				switch ($current->comparitor) {
					case F_DB_IS_NULL:
						$w[] = 'IS NULL';
						break;
					case F_DB_IS_NOT_NULL:
						$w[] = 'IS NOT NULL';
						break;
					case F_DB_IN:
						if (!is_array($current->comparison)) {
							continue 2;
						}

						if (!$this->prepared) {
							$w[] = '(';
							$w[] = implode(',', array_map(function (&$item) {
								return $item instanceof Field
									? ($item->table ?: $this->table) . '.' . $item->field
									: is_numeric($item)
										? $item
										: '\'' . $item . '\'';
							}, $current->comparison));
							$w[] = ')';
						} else {
							$w[] = '(';
							$w[] = implode(', ', array_fill(0, count($current->comparison), '?'));
							$w[] = ')';

							foreach ($current->comparison as $comparison) {
								if ($comparison instanceof Field) {
									$this->variables[] = ($comparison->table ?: $this->table) . '.' . $comparison->field;
									continue;
								}

								$this->variables[] = $comparison;
							}
						}

						break;
					default:
						continue 2;
				}
			} else {
				switch ($current->comparitor) {
					case F_DB_GREATER_THAN:
						$w[] = '>';
						break;
					case F_DB_GREATER_OR_EQUAL_THAN:
						$w[] = '>=';
						break;
					case F_DB_LESS_THAN:
						$w[] = '<';
						break;
					case F_DB_LESS_OR_EQUAL_THAN:
						$w[] = '<=';
						break;
					case F_DB_LIKE:
						$w[] = 'LIKE';
						break;
					case F_DB_EQUALS:
					default:
						$w[] = '=';
						break;
				}

				if ($current->comparison instanceof Field) {
					$w[] = ($current->comparison->table ?: $this->table) . '.' . $current->comparison->field;
				} elseif (!$this->prepared) {
					$w[] = is_numeric($current->comparison)
						? $current->comparison
						: '\'' . $current->comparison . '\'';
				} else {
					$w[]               = '?';
					$this->variables[] = $current->comparison;
				}
			}

			if ($current->relation === F_DB_OR) {
				$w[] = ')';
			}

			$bits[] = implode(' ', $w);
		}

		return count($bits) > 1
			? implode(' ', $bits)
			: null;
	}
}