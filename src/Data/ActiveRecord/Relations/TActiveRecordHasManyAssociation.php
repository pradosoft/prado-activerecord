<?php

/**
 * TActiveRecordHasManyAssociation class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\ActiveRecord\Relations;

/**
 * Loads base active record relations class.
 */
use Prado\Data\ActiveRecord\Exceptions\TActiveRecordException;
use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Data\Common\IDataSchemalessTableInfo;
use Prado\Data\Common\IDbCommandBuilder;
use Prado\Prado;

/**
 * TActiveRecordHasManyAssociation class
 *
 * Implements the M-N (many to many) relationship via association table.
 * Consider the <b>entity</b> relationship between Articles and Categories
 * via the association table <tt>Article_Category</tt>.
 * ```php
 * +---------+            +------------------+            +----------+
 * | Article | * -----> * | Article_Category | * <----- * | Category |
 * +---------+            +------------------+            +----------+
 * ```
 * Where one article may have 0 or more categories and each category may have 0
 * or more articles. We may model Article-Category <b>object</b> relationship
 * as active record as follows.
 * ```php
 * class ArticleRecord
 * {
 *     const TABLE='Article';
 *     public $article_id;
 *
 *     public $Categories=array(); //foreign object collection.
 *
 *     public static $RELATIONS = array
 *     (
 *         'Categories' => array(self::MANY_TO_MANY, 'CategoryRecord', 'Article_Category')
 *     );
 *
 *     public static function finder($className=__CLASS__)
 *     {
 *         return parent::finder($className);
 *     }
 * }
 * class CategoryRecord
 * {
 *     const TABLE='Category';
 *     public $category_id;
 *
 *     public $Articles=array();
 *
 *     public static $RELATIONS = array
 *     (
 *         'Articles' => array(self::MANY_TO_MANY, 'ArticleRecord', 'Article_Category')
 *     );
 *
 *     public static function finder($className=__CLASS__)
 *     {
 *         return parent::finder($className);
 *     }
 * }
 * ```
 *
 * The static <tt>$RELATIONS</tt> property of ArticleRecord defines that the
 * property <tt>$Categories</tt> has many <tt>CategoryRecord</tt>s. Similar, the
 * static <tt>$RELATIONS</tt> property of CategoryRecord defines many ArticleRecords.
 *
 * The articles with categories list may be fetched as follows.
 * ```php
 * $articles = TeamRecord::finder()->withCategories()->findAll();
 * ```
 * The method <tt>with_xxx()</tt> (where <tt>xxx</tt> is the relationship property
 * name, in this case, <tt>Categories</tt>) fetchs the corresponding CategoryRecords using
 * a second query (not by using a join). The <tt>with_xxx()</tt> accepts the same
 * arguments as other finder methods of TActiveRecord.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @since 3.1
 */
class TActiveRecordHasManyAssociation extends TActiveRecordRelation
{
	private $_association;
	private $_sourceTable;
	private $_foreignTable;
	private $_association_columns = [];
	/** @var ?string pivot column referencing the source, declared as the first
	 *   column of a two-column `pivot.(sourceFk, foreignFk)` association spec. */
	private $_sourceFkColumn;
	/** @var ?string pivot column referencing the foreign table, declared as the
	 *   second column of a two-column `pivot.(sourceFk, foreignFk)` spec. */
	private $_foreignFkColumn;

	/**
	 * Get the foreign key index values from the results and make calls to the
	 * database to find the corresponding foreign objects using association table.
	 * @param array $results by reference, original results.
	 */
	protected function collectForeignObjects(&$results)
	{
		[$sourceKeys, $foreignKeys] = $this->getRelationForeignKeys();
		$properties = array_values($sourceKeys);
		$indexValues = $this->getNonNullIndexValues($this->getIndexValues($properties, $results));
		if (count($indexValues) === 0) {
			$collections = [];
			$this->setResultCollection($results, $collections, $properties);
			return;
		}
		$this->fetchForeignObjects($results, $foreignKeys, $indexValues, $sourceKeys);
	}

	/**
	 * Returns the association table keys to the source table and to the foreign table.
	 * The keys come from the association table's foreign key metadata. The source keys
	 * exclude the columns {@see getSourceKeyExclusions()} lists. The foreign keys exclude
	 * the source key columns. A self-referencing relation, whose association table holds
	 * two keys to the same table, resolves to two different keys.
	 *
	 * | Self-referencing relation           | Source keys                    | Foreign keys |
	 * |-------------------------------------|--------------------------------|--------------|
	 * | `'links.target_id'`                 | the key other than `target_id` | `target_id`  |
	 * | `'links.(source_id, target_id)'`    | `source_id`                    | `target_id`  |
	 * | `'links'`                           | first key in metadata order    | second key   |
	 *
	 * The metadata order of keys differs between drivers. Name the foreign column in a
	 * self-referencing relation. When the metadata holds no foreign keys, as in a
	 * document store, the two columns of a `'pivot.(sourceFk, foreignFk)'` relation
	 * map to the primary keys of the source and foreign tables
	 * ({@see buildDeclaredAssociationKeys()}). A schemaless association table
	 * ({@see getIsSchemalessAssociation()}) always takes the two named columns.
	 * @throws TActiveRecordException when the metadata holds no matching foreign key
	 *   and the relation does not name both association columns, or when a schemaless
	 *   association table's relation does not name both (`ar_mn_relation_keys_required`).
	 * @return array 2 arrays of source keys and foreign keys from the association table.
	 */
	public function getRelationForeignKeys()
	{
		$association = $this->getAssociationTable();
		if ($this->getIsSchemalessAssociation()) {
			$keys = $this->buildDeclaredAssociationKeys();
			if ($keys === null) {
				throw new TActiveRecordException(
					'ar_mn_relation_keys_required',
					$this->getContext()->getForeignRecordFinder()::class,
					$association->getTableName()
				);
			}
			return $keys;
		}
		try {
			$sourceKeys = $this->findForeignKeys($association, $this->getSourceRecord(), true, $this->getSourceKeyExclusions());
			$fkObject = $this->getContext()->getForeignRecordFinder();
			$foreignKeys = $this->findForeignKeys($association, $fkObject, false, array_keys($sourceKeys));
			return [$sourceKeys, $foreignKeys];
		} catch (TActiveRecordException $e) {
			// Stores with no foreign-key constraint metadata (e.g. a document
			// store) can declare both pivot foreign-key columns explicitly via
			// the `pivot.(sourceFk, foreignFk)` association spec.
			$keys = $this->buildDeclaredAssociationKeys();
			if ($keys !== null) {
				return $keys;
			}
			throw $e;
		}
	}

	/**
	 * Builds the source and foreign key maps from the two columns of a
	 * `'pivot.(sourceFk, foreignFk)'` relation. Each column maps to the single
	 * primary key of the source or foreign table.
	 * @return ?array `[sourceKeys, foreignKeys]`, or null when the relation does not
	 *   name both columns or a table has a composite primary key.
	 * @since 1.1.0 of prado-activerecord
	 */
	protected function buildDeclaredAssociationKeys(): ?array
	{
		$this->getAssociationTable(); // ensure the spec is parsed
		if ($this->_sourceFkColumn === null || $this->_foreignFkColumn === null) {
			return null;
		}
		$sourcePk = array_values($this->getSourceTable()->getPrimaryKeys());
		$foreignPk = array_values($this->getForeignTable()->getPrimaryKeys());
		if (count($sourcePk) !== 1 || count($foreignPk) !== 1) {
			return null;
		}
		$sourceKeys = [$this->_sourceFkColumn => $sourcePk[0]];
		$foreignKeys = [$this->_foreignFkColumn => $foreignPk[0]];
		return [$sourceKeys, $foreignKeys];
	}

	/**
	 * @return string[] the association column named after the dot in the relation's
	 *   association table, as in `'links.target_id'`. Empty when none or two are named.
	 * @since 1.0.0 of prado-activerecord
	 */
	protected function getAssociationColumns()
	{
		$this->getAssociationTable();
		return $this->_association_columns;
	}

	/**
	 * @return string[] association table columns that are not source keys: the named
	 *   association column, or the foreign column of a `'pivot.(sourceFk, foreignFk)'` relation.
	 * @since 1.1.0 of prado-activerecord
	 */
	protected function getSourceKeyExclusions()
	{
		$columns = $this->getAssociationColumns();
		if ($this->_foreignFkColumn !== null) {
			$columns[] = $this->_foreignFkColumn;
		}
		return $columns;
	}

	/**
	 * Returns whether the association table is schemaless: its metadata implements
	 * {@see IDataSchemalessTableInfo} and reports `isSchemaless()`, as a
	 * document collection does. Such a table has no foreign key metadata and no join.
	 * @return bool true when the association table is schemaless.
	 * @since 1.1.0 of prado-activerecord
	 */
	protected function getIsSchemalessAssociation(): bool
	{
		$association = $this->getAssociationTable();
		return $association instanceof IDataSchemalessTableInfo && $association->isSchemaless();
	}

	/**
	 * @return \Prado\Data\Common\TDbTableInfo association table information.
	 */
	protected function getAssociationTable()
	{
		if ($this->_association === null) {
			$gateway = $this->getSourceRecord()->getRecordGateway();
			$conn = $this->getSourceRecord()->getDbConnection();
			//table name may include the fk column name(s) separated with a dot.
			$table = explode('.', $this->getContext()->getAssociationTable());
			if (count($table) > 1) {
				$columns = preg_replace('/^\((.*)\)$/', '\1', trim($table[1]));
				$cols = array_values(array_filter(
					preg_split('/[\s,]+/', trim($columns)),
					static fn ($c) => $c !== ''
				));
				if (count($cols) >= 2) {
					// Two-column form: (sourceFk, foreignFk).  These are the pivot
					// foreign-key columns for schemaless stores; they are kept in
					// dedicated fields and do NOT populate _association_columns
					// (which carries extra pivot *data* columns to attach to the
					// foreign object — a different feature).
					$this->_sourceFkColumn = $cols[0];
					$this->_foreignFkColumn = $cols[1];
				} else {
					$this->_association_columns = $cols;
				}
			}
			$this->_association = $gateway->getTableInfo($conn, $table[0]);
		}
		return $this->_association;
	}

	/**
	 * @return \Prado\Data\Common\TDbTableInfo source table information.
	 */
	protected function getSourceTable()
	{
		if ($this->_sourceTable === null) {
			$gateway = $this->getSourceRecord()->getRecordGateway();
			$this->_sourceTable = $gateway->getRecordTableInfo($this->getSourceRecord());
		}
		return $this->_sourceTable;
	}

	/**
	 * @return \Prado\Data\Common\TDbTableInfo foreign table information.
	 */
	protected function getForeignTable()
	{
		if ($this->_foreignTable === null) {
			$gateway = $this->getSourceRecord()->getRecordGateway();
			$fkObject = $this->getContext()->getForeignRecordFinder();
			$this->_foreignTable = $gateway->getRecordTableInfo($fkObject);
		}
		return $this->_foreignTable;
	}

	/**
	 * @return \Prado\Data\DataGateway\TDataGatewayCommand
	 */
	protected function getCommandBuilder()
	{
		return $this->getSourceRecord()->getRecordGateway()->getCommand($this->getSourceRecord());
	}

	/**
	 * @return \Prado\Data\DataGateway\TDataGatewayCommand
	 */
	protected function getForeignCommandBuilder()
	{
		$obj = $this->getContext()->getForeignRecordFinder();
		return $this->getSourceRecord()->getRecordGateway()->getCommand($obj);
	}


	/**
	 * Fetches the M-N foreign objects.
	 *
	 * | Association table and foreign command builder | Fetch |
	 * |---|---|
	 * | schemaless association table ({@see getIsSchemalessAssociation()}) | association rows, then foreign rows, joined in PHP, {@see fetchForeignObjectsByQueries()} |
	 * | SQL builder ({@see IDbCommandBuilder}) | one INNER JOIN query, {@see fetchForeignObjectsByJoin()} |
	 * | any other builder, such as a document store's | the two queries, {@see fetchForeignObjectsByQueries()} |
	 *
	 * @param array $results by reference, original source results.
	 * @param array $foreignKeys foreign-table column → pivot-column mapping.
	 * @param array $indexValues source-key tuples used to filter the pivot.
	 * @param array $sourceKeys pivot-column → source-property mapping.
	 */
	protected function fetchForeignObjects(&$results, $foreignKeys, $indexValues, $sourceKeys)
	{
		$builder = $this->getForeignCommandBuilder()->getBuilder();
		if (!$this->getIsSchemalessAssociation() && $builder instanceof IDbCommandBuilder) {
			$this->fetchForeignObjectsByJoin($results, $foreignKeys, $indexValues, $sourceKeys);
		} else {
			$this->fetchForeignObjectsByQueries($results, $foreignKeys, $indexValues, $sourceKeys);
		}
	}

	/**
	 * Loads the M-N foreign objects with one INNER JOIN of the foreign and association tables.
	 *
	 * @param array $results by reference, original source results.
	 * @param array $foreignKeys foreign-table column → pivot-column mapping.
	 * @param array $indexValues source-key tuples used to filter the join.
	 * @param array $sourceKeys pivot-column → source-property mapping.
	 * @since 1.1.0 of prado-activerecord
	 */
	protected function fetchForeignObjectsByJoin(&$results, $foreignKeys, $indexValues, $sourceKeys)
	{
		$criteria = $this->getCriteria();
		$type = $this->getContext()->getForeignRecordFinder()::class;
		$command = $this->createCommand($criteria, $foreignKeys, $indexValues, $sourceKeys);
		$srcProps = array_keys($sourceKeys);
		$collections = [];
		foreach ($this->getCommandBuilder()->onExecuteCommand($command, $command->query()) as $row) {
			$hash = $this->getObjectHash($row, $srcProps);
			foreach ($srcProps as $column) {
				unset($row[$column]);
			}
			$obj = $this->createFkObject($type, $row, $foreignKeys);
			$collections[$hash][] = $obj;
		}
		$this->setResultCollection($results, $collections, array_values($sourceKeys));
	}

	/**
	 * Loads the M-N foreign objects with two queries joined in PHP: the association
	 * rows of the source keys, then the foreign rows those rows reference. A driver
	 * whose command builder cannot build a join uses it; it needs the builder's
	 * `createIndexedFindCommand()`.
	 *
	 * @param array $results by reference, original source results.
	 * @param array $foreignKeys foreign-table column → pivot-column mapping.
	 * @param array $indexValues source-key tuples used to filter the pivot.
	 * @param array $sourceKeys pivot-column → source-property mapping.
	 * @since 1.1.0 of prado-activerecord
	 */
	protected function fetchForeignObjectsByQueries(&$results, $foreignKeys, $indexValues, $sourceKeys)
	{
		$srcProps = array_keys($sourceKeys);
		$pivotRows = $this->fetchPivotRows($srcProps, $indexValues);
		if (empty($pivotRows)) {
			$collections = [];
			$this->setResultCollection($results, $collections, array_values($sourceKeys));
			return;
		}

		[$pivotFkCols, $foreignFkCols] = $this->resolvePivotForeignColumns($foreignKeys);
		$fkTuples = $this->extractDistinctTuples($pivotRows, $pivotFkCols);
		$foreignByFk = $this->fetchForeignRowsByFk($foreignFkCols, $fkTuples);

		$type = $this->getContext()->getForeignRecordFinder()::class;
		$hasAssocCols = count($this->_association_columns) > 0;
		$collections = [];
		foreach ($pivotRows as $pivotRow) {
			$hash = $this->getObjectHash($pivotRow, $srcProps);
			$fkTuple = $this->extractTuple($pivotRow, $pivotFkCols);
			$key = serialize($fkTuple);
			if (!isset($foreignByFk[$key])) {
				continue;
			}
			foreach ($foreignByFk[$key] as $foreignRow) {
				if ($hasAssocCols) {
					foreach ($this->_association_columns as $assocCol) {
						$foreignRow[$assocCol] = $pivotRow[$assocCol] ?? null;
					}
				}
				$collections[$hash][] = $this->createFkObject($type, $foreignRow, $foreignKeys);
			}
		}
		$this->setResultCollection($results, $collections, array_values($sourceKeys));
	}

	/**
	 * Returns the association rows of the source key values, the first query of {@see fetchForeignObjectsByQueries()}.
	 * @param array $pivotSourceCols pivot columns referencing source PK.
	 * @param array $indexValues source-key tuples.
	 * @return array pivot-table rows.
	 * @since 1.1.0 of prado-activerecord
	 */
	protected function fetchPivotRows(array $pivotSourceCols, array $indexValues): array
	{
		$pivot = $this->getAssociationTable();
		$conn = $this->getSourceRecord()->getDbConnection();
		$builder = $pivot->createCommandBuilder($conn);
		$cmd = $builder->createIndexedFindCommand(
			new \Prado\Data\DataGateway\TSqlCriteria(),
			$pivotSourceCols,
			$indexValues
		);
		return array_map(fn ($row) => (array) $row, $cmd->query()->readAll());
	}

	/**
	 * Returns the association columns that reference the foreign table and the foreign
	 * columns they reference. A column named in the relation, as in `'links.target_id'`,
	 * replaces the metadata's association column.
	 * @param array $foreignKeys foreign-table column → pivot-column mapping.
	 * @return array two-element tuple: `[pivotFkCols, foreignFkCols]`.
	 * @since 1.1.0 of prado-activerecord
	 */
	protected function resolvePivotForeignColumns(array $foreignKeys): array
	{
		$hasAssocCols = count($this->_association_columns) > 0;
		$pivotFkCols = [];
		$foreignFkCols = [];
		$i = 0;
		foreach ($foreignKeys as $ref => $fk) {
			$pivotFkCols[] = $hasAssocCols ? $this->_association_columns[$i++] : $ref;
			$foreignFkCols[] = $fk;
		}
		return [$pivotFkCols, $foreignFkCols];
	}

	/**
	 * Returns the foreign rows whose key columns hold one of the value tuples and that
	 * match the relation's criteria, the second query of {@see fetchForeignObjectsByQueries()}.
	 * @param array $foreignFkCols foreign-table column names.
	 * @param array $fkTuples distinct value tuples to match.
	 * @return array foreign rows keyed by `serialize(fkTuple)` → list of rows.
	 * @since 1.1.0 of prado-activerecord
	 */
	protected function fetchForeignRowsByFk(array $foreignFkCols, array $fkTuples): array
	{
		$criteria = $this->getCriteria();
		$builder = $this->getForeignCommandBuilder()->getBuilder();
		$cmd = $builder->createIndexedFindCommand($criteria, $foreignFkCols, $fkTuples);
		$this->getCommandBuilder()->onCreateCommand($cmd, $criteria);
		$rows = $this->getCommandBuilder()->onExecuteCommand($cmd, $cmd->query())->readAll();
		$indexed = [];
		foreach ($rows as $row) {
			$row = (array) $row;
			$indexed[serialize($this->extractTuple($row, $foreignFkCols))][] = $row;
		}
		return $indexed;
	}

	/**
	 * Returns the distinct value-tuples for the given columns across an array
	 * of rows, preserving insertion order.
	 * @param array $rows array of column → value rows.
	 * @param array $columns columns to extract.
	 * @return array list of value-arrays.
	 * @since 1.1.0 of prado-activerecord
	 */
	protected function extractDistinctTuples(array $rows, array $columns): array
	{
		$set = [];
		foreach ($rows as $row) {
			$tuple = $this->extractTuple($row, $columns);
			$set[serialize($tuple)] = $tuple;
		}
		return array_values($set);
	}

	/**
	 * Extracts a column-value tuple from a single row.
	 * @param array $row column → value row.
	 * @param array $columns columns to extract in order.
	 * @return array value tuple.
	 * @since 1.1.0 of prado-activerecord
	 */
	protected function extractTuple(array $row, array $columns): array
	{
		$tuple = [];
		foreach ($columns as $col) {
			$tuple[] = $row[$col] ?? null;
		}
		return $tuple;
	}

	/**
	 * Creates a foreign record from a joined row. The record uses the connection of
	 * the foreign record finder, which ran the query.
	 * @param string $type active record class name.
	 * @param array $row row data
	 * @param array $foreignKeys foreign key column names
	 * @return TActiveRecord
	 */
	protected function createFkObject($type, $row, $foreignKeys)
	{
		$obj = TActiveRecord::createRecord($type, $row);
		$obj->setDbConnection($this->getContext()->getForeignRecordFinder()->getDbConnection());
		if (count($this->_association_columns) > 0) {
			$i = 0;
			foreach ($foreignKeys as $ref => $fk) {
				$obj->setColumnValue($ref, $row[$this->_association_columns[$i++]]);
			}
		}
		return $obj;
	}

	/**
	 * Builds the INNER JOIN command of {@see fetchForeignObjectsByJoin()}.
	 * @param \Prado\Data\DataGateway\TSqlCriteria $criteria
	 * @param array $foreignKeys field names
	 * @param array $indexValues field values
	 * @param array $sourceKeys source table column names.
	 */
	public function createCommand($criteria, $foreignKeys, $indexValues, $sourceKeys)
	{
		$innerJoin = $this->getAssociationJoin($foreignKeys, $indexValues, $sourceKeys);
		$fkTable = $this->getForeignTable()->getTableFullName();
		$srcColumns = $this->getSourceColumns($sourceKeys);
		if (($where = $criteria->getCondition()) === null) {
			$where = '1=1';
		}
		$sql = "SELECT {$fkTable}.*, {$srcColumns} FROM {$fkTable} {$innerJoin} WHERE {$where}";

		$parameters = $criteria->getParameters()->toArray();
		$ordering = $criteria->getOrdersBy();
		$limit = $criteria->getLimit();
		$offset = $criteria->getOffset();

		$builder = $this->getForeignCommandBuilder()->getBuilder();
		$command = $builder->applyCriterias($sql, $parameters, $ordering, $limit, $offset);
		$this->getCommandBuilder()->onCreateCommand($command, $criteria);
		return $command;
	}

	/**
	 * @param array $sourceKeys source table column names.
	 * @return string comma separated source column names.
	 */
	protected function getSourceColumns($sourceKeys)
	{
		$columns = [];
		$table = $this->getAssociationTable();
		$tableName = $table->getTableFullName();
		$columnNames = array_merge(array_keys($sourceKeys), $this->_association_columns);
		foreach ($columnNames as $name) {
			$columns[] = $tableName . '.' . $table->getColumn($name)->getColumnName();
		}
		return implode(', ', $columns);
	}

	/**
	 * SQL inner join for M-N relationship via association table.
	 * @param array $foreignKeys foreign table column key names.
	 * @param array $indexValues source table index values.
	 * @param array $sourceKeys source table column names.
	 * @return string inner join condition for M-N relationship via association table.
	 */
	protected function getAssociationJoin($foreignKeys, $indexValues, $sourceKeys)
	{
		$refInfo = $this->getAssociationTable();
		$fkInfo = $this->getForeignTable();

		$refTable = $refInfo->getTableFullName();
		$fkTable = $fkInfo->getTableFullName();

		$joins = [];
		$hasAssociationColumns = count($this->_association_columns) > 0;
		$i = 0;
		foreach ($foreignKeys as $ref => $fk) {
			if ($hasAssociationColumns) {
				$refField = $refInfo->getColumn($this->_association_columns[$i++])->getColumnName();
			} else {
				$refField = $refInfo->getColumn($ref)->getColumnName();
			}
			$fkField = $fkInfo->getColumn($fk)->getColumnName();
			$joins[] = "{$fkTable}.{$fkField} = {$refTable}.{$refField}";
		}
		$joinCondition = implode(' AND ', $joins);
		$index = $this->getCommandBuilder()->getIndexKeyCondition($refInfo, array_keys($sourceKeys), $indexValues);
		return "INNER JOIN {$refTable} ON ({$joinCondition}) AND {$index}";
	}

	/**
	 * Updates the associated foreign objects.
	 * @return bool true if all update are success (including if no update was required), false otherwise .
	 */
	public function updateAssociatedRecords()
	{
		$obj = $this->getContext()->getSourceRecord();
		$fkObjects = $obj->{$this->getContext()->getProperty()};
		$success = true;
		if (($total = count($fkObjects)) > 0) {
			$source = $this->getSourceRecord();
			$builder = $this->getAssociationTableCommandBuilder();
			for ($i = 0; $i < $total; $i++) {
				$success = $fkObjects[$i]->save() && $success;
			}
			return $this->updateAssociationTable($obj, $fkObjects, $builder) && $success;
		}
		return $success;
	}

	/**
	 * @return \Prado\Data\Common\TDbCommandBuilder
	 */
	protected function getAssociationTableCommandBuilder()
	{
		$conn = $this->getContext()->getSourceRecord()->getDbConnection();
		return $this->getAssociationTable()->createCommandBuilder($conn);
	}

	private function hasAssociationData($builder, $data)
	{
		$condition = [];
		$table = $this->getAssociationTable();
		foreach ($data as $name => $value) {
			$condition[] = $table->getColumn($name)->getColumnName() . ' = ?';
		}
		$command = $builder->createCountCommand(implode(' AND ', $condition), array_values($data));
		$result = $this->getCommandBuilder()->onExecuteCommand($command, (int) ($command->queryScalar()));
		return (int) $result > 0;
	}

	private function addAssociationData($builder, $data)
	{
		$command = $builder->createInsertCommand($data);
		return $this->getCommandBuilder()->onExecuteCommand($command, $command->execute()) > 0;
	}

	private function updateAssociationTable($obj, $fkObjects, $builder)
	{
		[$sourceKeys, $foreignKeys] = $this->getRelationForeignKeys();
		$source = $this->getSourceRecordValues($obj, $sourceKeys);
		$success = true;
		foreach ($fkObjects as $fkObject) {
			$data = array_merge($source, $this->getForeignObjectValues($foreignKeys, $fkObject));
			if (!$this->hasAssociationData($builder, $data)) {
				$success = $this->addAssociationData($builder, $data) && $success;
			}
		}
		return $success;
	}

	private function getSourceRecordValues($obj, $sourceKeys)
	{
		$indexValues = $this->getIndexValues(array_values($sourceKeys), $obj);
		$data = [];
		$i = 0;
		foreach ($sourceKeys as $name => $srcKey) {
			$data[$name] = $indexValues[0][$i++];
		}
		return $data;
	}

	private function getForeignObjectValues($foreignKeys, $fkObject)
	{
		$data = [];
		foreach ($foreignKeys as $name => $fKey) {
			$data[$name] = $fkObject->getColumnValue($fKey);
		}
		return $data;
	}
}
