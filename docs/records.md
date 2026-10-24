[prado-activerecord](../README.md) -> [ActiveRecord Docs](README.md) -> Record Classes

# Record Classes

A record class extends `Prado\Data\ActiveRecord\TActiveRecord` and describes one table or view. This page covers naming the table, mapping columns to properties, the `finder()` method, primary keys, record state, copying and exporting data, serialization, the `InvalidFinderResult` setting, and read-only records.

---

## A Record Class

```php
use Prado\Data\ActiveRecord\TActiveRecord;

class UserRecord extends TActiveRecord
{
    const TABLE = 'users';

    public $username;
    public $email;
    public $active = true;

    public static function finder($className = __CLASS__)
    {
        return parent::finder($className);
    }
}
```

| Part | Purpose |
|---|---|
| `const TABLE` | Table or view name. |
| Public properties | One per column. A property default is the column value for a new record. |
| `finder()` | Returns the shared finder instance for this class. |

`TActiveRecord` extends `TComponent`, so a record class may add getter/setter properties, events, and behaviors.

---

## Table Name

`TActiveRecordGateway` resolves the table name in this order:

1. `TABLE` constant → its value. An empty value throws `TActiveRecordException` (`ar_invalid_tablename_property`).
2. `table()` method → its return value. An empty value throws `TActiveRecordException` (`ar_invalid_tablename_method`).
3. Neither → the lowercased class name, including its namespace.

A `table()` method computes the name at run time:

```php
class LogRecord extends TActiveRecord
{
    public function table()
    {
        return 'log_' . date('Y');
    }
}
```

Declare `TABLE` or `table()` for a namespaced class. The fallback `app\logrecord` is not a table name. The value is passed to the metadata reader as written, so a schema-qualified name is accepted where the database supports it.

The name resolves against the record's connection. A missing table or view raises a `TDbException` from the metadata reader.

---

## Columns and Properties

Every column that a query returns needs a place to go. The record maps a column name to a property through `getColumnValue()` and `setColumnValue()`:

- The column name is a key in the static `$COLUMN_MAPPING` array → the mapped property name is used.
- Otherwise → the column name is the property name.

The property is a public property, or a `TComponent` property defined by a getter/setter pair. A column with neither throws `TInvalidOperationException` (`component_property_undefined`) when a row is loaded. Properties that are not columns are allowed; the gateway reads only the columns the table metadata lists.

### Column mapping

`$COLUMN_MAPPING` keys are physical column names and values are property names. Columns whose names already match are left out.

```php
class UserRecord extends TActiveRecord
{
    const TABLE = 'users';

    public static $COLUMN_MAPPING = [
        'user_id' => 'id',
        'email_address' => 'email',
        'first_name' => 'firstName',
    ];

    public $id;
    public $username;
    public $email;
    public $firstName;
}
```

Finder conditions and dynamic finder names use the physical column names (`findByUser_Id()`, `find('email_address = ?', $value)`). Property access uses the mapped names (`$user->firstName`).

### Insert and update values

| Condition | Insert | Update |
|---|---|---|
| Column `getIsExcluded()` is `true` | skipped | skipped |
| Value is `null`, column is `NOT NULL`, has no default, and (insert only) has no sequence | `TActiveRecordException` `ar_value_must_not_be_null` | `TActiveRecordException` `ar_value_must_not_be_null` |
| Value is `null` otherwise | column left out of the `INSERT` | `NULL` written |
| Column is a primary key | written | used in the `WHERE` clause |

After an insert, a column with a sequence (auto-increment, serial) receives the last insert id.

---

## `finder()`

`TActiveRecord::finder($className)` returns one shared instance per class, created with `Prado::createComponent()`. The override in each record class passes its own name, so `UserRecord::finder()` and `TActiveRecord::finder(UserRecord::class)` return the same object.

- Finder methods (`find`, `findAll`, `count`, `deleteAll`, and the rest) are instance methods, called on the finder or on any record object.
- Event handlers attached to the finder are class-wide: they fire for every query that finder runs. See [Events](events.md).
- The finder is never saved. Records a finder loads are new objects.

---

## Primary Keys

Primary keys come from the table metadata. `findByPk()`, `findAllByPks()`, `deleteByPk()`, `save()` on a loaded record, and `delete()` all need at least one primary key column. A table without one raises `TDbException` (`dbtablegateway_no_primary_key_found`) from those calls.

| Key | `findByPk()` | `deleteByPk()` one row | `deleteByPk()` several rows |
|---|---|---|---|
| Single column | `findByPk($id)` | `deleteByPk($id)` | `deleteByPk($id1, $id2)` or `deleteByPk([$id1, $id2])` |
| Composite | `findByPk($a, $b)`, `findByPk([$a, $b])`, or `findByPk(['col_a' => $a, 'col_b' => $b])` | `deleteByPk([$a, $b])` | `deleteByPk([$a, $b], [$c, $d])` or `deleteByPk([[$a, $b], [$c, $d]])` |

Composite values given as a plain list follow the order of the primary key columns in the metadata. `findByPk(null)` returns `null` without a query.

```php
$section = DepSections::finder()->findByPk(3, 7);
$section = DepSections::finder()->findByPk(['department_id' => 3, 'section_id' => 7]);
```

---

## Record State

Each record carries a state that decides what `save()` and `delete()` do.

| State | Constant | How a record gets it |
|---|---|---|
| New | `TActiveRecord::STATE_NEW` | `new UserRecord()` |
| Loaded | `TActiveRecord::STATE_LOADED` | Returned by a finder, or a new record after a successful `save()` |
| Deleted | `TActiveRecord::STATE_DELETED` | After a successful `delete()` |

| Call | New | Loaded | Deleted |
|---|---|---|---|
| `save()` | `INSERT`, then state becomes Loaded | `UPDATE` by primary key | `TActiveRecordException` `ar_save_invalid` |
| `delete()` | `TActiveRecordException` `ar_delete_invalid` | `DELETE` by primary key, then state becomes Deleted | `TActiveRecordException` `ar_delete_invalid` |

`save()` and `delete()` return `true` when the row count the driver reports is greater than zero and `false` otherwise. The state is stored in the protected `_recordState` property; there is no public getter. `TActiveRecord::createRecord($className, $data)` builds a record in the Loaded state from an array, and returns `null` for empty data.

Records hold copies of row data. Two finder calls for the same row return two objects, so `===` compares object identity. Use `equals()` to compare rows.

---

## Constructor and `copyFrom()`

```php
public function __construct($data = [], $connection = null)
```

`$data` is an array or object of column values; `$connection` is a `TDbConnection` for this instance. Both are optional.

`copyFrom($data)` copies an array or an object's public properties into the record through `setColumnValue()`, so `$COLUMN_MAPPING` applies. Any other type throws `TActiveRecordException` (`ar_data_invalid`).

```php
$user = new UserRecord(['username' => 'guest', 'email' => 'guest@example.com']);
$user->copyFrom($this->Request->getItems()); // array
$user->save();
```

---

## `__get()` and `__set()`

`TActiveRecord` overrides the `TComponent` magic accessors for one case: a name that is a key in `$RELATIONS` and is not a declared property.

- Read → the relation is loaded and the related record or array is returned. See [Relations](relations.md#lazy-loading).
- Write → the value is stored for that relation name.

Every other name follows `TComponent` rules: a public property, a getter/setter pair, or an event. An undefined name throws `TInvalidOperationException`.

---

## Exporting Data

| Method | Returns |
|---|---|
| `toArray()` | Array keyed by column name, one entry per table column, values from the record |
| `toJSON()` | `json_encode()` of `toArray()` |
| `equals($record, $strict = false)` | `true` when both records are the same class and their primary key values match (`==`, or `===` with `$strict`). A table without primary keys compares every column. |

`toArray()` lists table columns, so extra properties and relation values are left out.

---

## Table Metadata

| Method | Returns |
|---|---|
| `getRecordTableInfo()` | `Prado\Data\Common\TDbTableInfo` for the record's table on the record's connection |
| `getRecordGateway()` | The `TActiveRecordGateway` |
| `getRecordManager()` (static) | The `TActiveRecordManager` singleton |
| `getDbConnection()` / `setDbConnection($conn)` | The record's connection; defaults to the manager's connection |
| `getColumnValue($column)` / `setColumnValue($column, $value)` | Property access by physical column name |

`TDbTableInfo` exposes `getColumns()`, `getColumn($name)`, `getPrimaryKeys()`, `getForeignKeys()`, `getTableName()`, `getTableFullName()`, and `getIsView()`. Each column is a `TDbTableColumn` with `getDbType()`, `getPHPType()`, `getAllowNull()`, `getDefaultValue()`, `getIsPrimaryKey()`, `hasSequence()`, and `getColumnSize()`.

```php
foreach (UserRecord::finder()->getRecordTableInfo()->getColumns() as $name => $column) {
    echo $name, ': ', $column->getDbType(), PHP_EOL;
}
```

---

## Serialization

A record serializes with `serialize()` and survives a session or cache round trip.

- `__sleep()` drops the connection, so credentials and the PDO handle are not stored.
- The record state, column values, and relation values are stored.
- `__wakeup()` rebuilds the column mapping and relation tables for the class.
- After `unserialize()`, `getDbConnection()` returns the class override or the manager connection.

A subclass that overrides `__sleep()` calls `parent::__sleep()` and filters that list.

---

## InvalidFinderResult

`TActiveRecord::__call()` handles the dynamic prefixes `with`, `findBy`, `findAllBy`, `deleteBy`, and `deleteAllBy`, and passes `dy` and `fx` names to `TComponent`. Any other method name is an invalid finder. The `InvalidFinderResult` setting decides the outcome.

| `TActiveRecordInvalidFinderResult` | Result of `$finder->noSuchMethod()` |
|---|---|
| `Null` (default) | returns `null` |
| `Exception` | throws `TActiveRecordException` `ar_invalid_finder_method` |

The setting is read in this order:

1. The record's own `InvalidFinderResult` property, when not `null`.
2. `TActiveRecordManager::getInvalidFinderResult()`, set by the `InvalidFinderResult` attribute of `TActiveRecordConfig`.

```php
UserRecord::finder()->setInvalidFinderResult(TActiveRecordInvalidFinderResult::Exception);
UserRecord::finder()->setInvalidFinderResult(null); // back to the manager setting
```

`TActiveRecordInvalidFinderResult` is a `TEnumerable` with the constants `Null` and `Exception`. Both the string and the constant are accepted. A dynamic finder whose name matches no column throws `TDbException` regardless of this setting.

---

## Read-Only Records and Views

`TABLE` may name a database view. Finders work on a view like a table. Writes depend on the view's primary key metadata:

- The view reports no primary key → `save()` on a loaded record and `delete()` throw `TDbException` (`dbtablegateway_no_primary_key_found`). `save()` on a new record sends an `INSERT`, and the database accepts or rejects it.
- The view reports a primary key → the statements are sent, and the database decides.

To make a record class read-only in code, stop the change events. The message file defines `ar_readonly_exception` for this purpose; no built-in code raises it.

```php
use Prado\Data\ActiveRecord\Exceptions\TActiveRecordException;

class SalesSummary extends TActiveRecord
{
    const TABLE = 'v_sales_summary';

    public $region;
    public $total;

    public function onInsert($param)
    {
        throw new TActiveRecordException('ar_readonly_exception', static::class);
    }

    public function onUpdate($param)
    {
        throw new TActiveRecordException('ar_readonly_exception', static::class);
    }

    public function onDelete($param)
    {
        throw new TActiveRecordException('ar_readonly_exception', static::class);
    }
}
```

Setting `$param->IsValid = false` in those methods instead makes `save()` and `delete()` return `false` without a statement. See [Events](events.md).

---

## Transactions

The record's connection provides transactions.

```php
$finder = UserRecord::finder();
$finder->getDbConnection()->setActive(true);
$transaction = $finder->getDbConnection()->beginTransaction();
try {
    $user = $finder->findByPk('admin');
    $user->email = 'root@example.com';
    $user->save();
    $transaction->commit();
} catch (\Exception $e) {
    $transaction->rollBack();
    throw $e;
}
```

---

[prado-activerecord](../README.md) -> [ActiveRecord Docs](README.md) -> Record Classes

## See Also

- [Finders](finders.md): loading, counting, and deleting rows
- [Relations](relations.md): `$RELATIONS` and related records
- [Events](events.md): `OnInsert`, `OnUpdate`, `OnDelete`
- [Prado Integration](integration.md): connections and the manager
