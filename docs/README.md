[prado-activerecord](../README.md) -> ActiveRecord Docs

# ActiveRecord Documentation

Active Record wraps one row of a database table or view in a PHP object. A record class extends `TActiveRecord`, declares one public property per column, and reads and writes its row through `find*()`, `save()`, and `delete()`. Table metadata (columns, primary keys, foreign keys) comes from the PRADO `Prado\Data` layer, so one record class works on MySQL/MariaDB, PostgreSQL, SQLite, Firebird/InterBase, SQL Server, Oracle, and IBM DB2. The package also ships the Scaffold controls, which build list, search, and edit views from a record class, and the `prado-cli activerecord` command, which generates record classes from tables.

## Contents

| Section | Description |
|---|---|
| [Migration](migration.md) | Moving from the framework-bundled Active Record to this package |
| [Prado Integration](integration.md) | Registering `TActiveRecordConfig` as an application module, connection and cache settings, multiple connections, manager and gateway events, binding records to template controls |
| [Quick Start](quickstart.md) | Minimal working example: install, module, table, record class, find, save, delete |
| [Record Classes](records.md) | `TABLE` and `table()`, columns, `finder()`, primary keys, record state, `copyFrom()`, `toArray()`, serialization, `InvalidFinderResult`, read-only records |
| [Finders](finders.md) | `find`, `findAll`, `findByPk`, `findAllByPks`, `findBySql`, `count`, `deleteAll`, dynamic finders, parameter binding, `TActiveRecordCriteria` |
| [Relations](relations.md) | `$RELATIONS`, the four relation types, foreign key detection, `with<Relation>()`, lazy loading, saving related records |
| [Events](events.md) | `OnInsert`, `OnUpdate`, `OnDelete`, `OnCreateCommand`, `OnExecuteCommand`, and their order during `save()` and `delete()` |
| [Scaffold](scaffold.md) | `TScaffoldView`, `TScaffoldListView`, `TScaffoldEditView`, `TScaffoldSearch`, edit renderers, input builders, `style.css` |
| [Command Line](cli.md) | `prado-cli activerecord/generate` and `activerecord/generate-all` |

## TActiveRecord Quick Reference

`finder()` is static. Every other method is called on the finder instance (`UserRecord::finder()`) or on a record object. `...$params` stands for the parameter forms listed in [Finders](finders.md#parameter-binding).

| Method | Returns |
|---|---|
| `finder($className = __CLASS__)` | The shared finder instance for the class |
| `find($criteria, ...$params)` | One record, or `null` |
| `findAll($criteria = null, ...$params)` | Array of records; empty array when nothing matches |
| `findByPk($keys)` | One record by primary key, or `null` |
| `findAllByPks($keys)` | Array of records for a list of primary keys |
| `findBySql($sql, ...$params)` | One record from a full SQL statement, or `null` |
| `findAllBySql($sql, ...$params)` | Array of records from a full SQL statement |
| `findAllByIndex($criteria, $fields, $values)` | Array of records where `(fields) IN (values)` |
| `count($criteria = null, ...$params)` | `int` row count |
| `findBy<Columns>(...)` | One record, or `null` (dynamic finder) |
| `findAllBy<Columns>(...)` | Array of records (dynamic finder) |
| `with<Relation>(...)` | Relation handler; call a finder method on it to load related records |
| `save()` | `true` when the row is inserted or updated, `false` otherwise |
| `delete()` | `true` when the row is deleted, `false` otherwise |
| `deleteByPk($keys)`, `deleteAllByPks($keys)` | `int` number of rows deleted |
| `deleteAll($criteria = null, ...$params)` | `int` number of rows deleted |
| `deleteBy<Columns>(...)`, `deleteAllBy<Columns>(...)` | `int` number of rows deleted (dynamic finder) |
| `copyFrom($data)` | Nothing; copies an array or object into the record |
| `toArray()`, `toJSON()` | Column values as an array, or as a JSON string |
| `equals($record, $strict = false)` | `true` when the primary key values match |
| `getRecordTableInfo()` | `TDbTableInfo` for the record's table |
| `getDbConnection()`, `setDbConnection($conn)` | The record's `TDbConnection` |

## Relation Type Constants

The first element of a `$RELATIONS` entry is one of these `TActiveRecord` constants. See [Relations](relations.md).

| Constant | Value | Meaning |
|---|---|---|
| `TActiveRecord::BELONGS_TO` | `'BELONGS_TO'` | This table holds a foreign key to the related table. The property holds one record or `null`. |
| `TActiveRecord::HAS_ONE` | `'HAS_ONE'` | One row of the related table holds a foreign key to this table. The property holds one record. |
| `TActiveRecord::HAS_MANY` | `'HAS_MANY'` | Many rows of the related table hold a foreign key to this table. The property holds an array. |
| `TActiveRecord::MANY_TO_MANY` | `'MANY_TO_MANY'` | An association table links the two tables. The property holds an array. |

---

[prado-activerecord](../README.md) -> ActiveRecord Docs

## See Also

- [Prado Integration](integration.md): module registration, `TActiveRecordConfig`, connection wiring
- [Record Classes](records.md): defining a record class
- [Finders](finders.md): loading, counting, and deleting rows
