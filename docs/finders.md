[prado-activerecord](../README.md) -> [ActiveRecord Docs](README.md) -> Finders

# Finders

Finder methods load, count, and delete rows. They are instance methods of `TActiveRecord`, called on the shared finder (`UserRecord::finder()`) or on any record object. This page covers each method, the parameter binding forms, dynamic finders built from method names, and `TActiveRecordCriteria`.

---

## Return Conventions

| Method family | No match | Match |
|---|---|---|
| `find*()` for one record | `null` | a record in the Loaded state |
| `findAll*()` | `[]` | array of records in the Loaded state |
| `count()` | `0` | `int` |
| `delete*()` | `0` | `int` rows deleted |

---

## Finder Methods

In the signatures below, `...$params` stands for the [parameter binding](#parameter-binding) forms, and `$criteria` is a condition string or a `TActiveRecordCriteria` object.

### `find($criteria, ...$params)`

Returns the first row that matches. The criteria `Limit` is set to `1`.

```php
$finder = UserRecord::finder();
$user = $finder->find('username = ?', 'admin');
$user = $finder->find('username = :name AND active = :active', [':name' => 'admin', ':active' => 1]);
```

### `findAll($criteria = null, ...$params)`

Returns every matching row. Without a criteria it returns the whole table.

```php
$all = $finder->findAll();
$gmail = $finder->findAll('email LIKE ?', '%@gmail.com');
$page = $finder->findAll('active = 1 ORDER BY username LIMIT 20 OFFSET 40');
```

### `findByPk($keys)` and `findAllByPks($keys)`

Load by primary key. Composite keys take one array per row.

```php
$user = $finder->findByPk('admin');
$users = $finder->findAllByPks('admin', 'guest');
$users = $finder->findAllByPks(['admin', 'guest']);

$section = DepSections::finder()->findByPk(3, 7);                  // composite key
$sections = DepSections::finder()->findAllByPks([3, 7], [3, 8]);
$sections = DepSections::finder()->findAllByPks([[3, 7], [3, 8]]);
```

`findByPk(null)` returns `null` without a query. See [Record Classes](records.md#primary-keys) for the accepted key forms.

### `findBySql($sql, ...$params)` and `findAllBySql($sql, ...$params)`

Run a complete `SELECT` statement. The returned columns populate a record of the finder's class, so each returned column needs a property on that class. `findBySql()` applies the driver's limit clause for one row.

```php
class UserRecord2 extends UserRecord
{
    public $another_value;
}

$sql = "SELECT users.*, 'hello' AS another_value FROM users WHERE active = ?";
$users = TActiveRecord::finder(UserRecord2::class)->findAllBySql($sql, 1);
```

### `findAllByIndex($criteria, $fields, $values)`

Returns rows where the tuple of `$fields` is in the list of `$values` tuples: `(a, b) IN ((1, 2), (3, 4))`. The relation handlers use it to load related rows for a set of parents. `$criteria` is a `TActiveRecordCriteria` whose condition is combined with the `IN` clause by `AND`.

```php
$rows = $finder->findAllByIndex(new TActiveRecordCriteria(), ['department_id'], [[3], [4]]);
```

### `count($criteria = null, ...$params)`

Returns `SELECT COUNT(*)` for the criteria. Without a criteria it counts the table.

```php
$total = $finder->count();
$active = $finder->count('active = ?', 1);
```

### `deleteByPk($keys)` and `deleteAllByPks($keys)`

Delete by primary key without loading the rows. `deleteAllByPks()` is an alias of `deleteByPk()`; both accept one key, several keys as separate arguments, or an array of keys.

```php
$finder->deleteByPk('guest');
$finder->deleteByPk('guest', 'temp');
$finder->deleteAllByPks(['guest', 'temp']);
DepSections::finder()->deleteByPk([[3, 7], [3, 8]]);   // composite keys
```

### `deleteAll($criteria = null, ...$params)`

Deletes every row that matches and returns the row count. Without a condition it deletes every row of the table.

```php
$deleted = $finder->deleteAll('active = ? AND email IS NULL', 0);
```

---

## Parameter Binding

A condition string holds `?` placeholders or `:name` placeholders. The values follow the condition in one of these forms.

| Form | Example |
|---|---|
| Positional values as extra arguments | `find('a = ? AND b = ?', $a, $b)` |
| Positional values in one array | `find('a = ? AND b = ?', [$a, $b])` |
| Named values in one array | `find('a = :a AND b = :b', [':a' => $a, ':b' => $b])` |
| Criteria object | `find($criteria)`; further arguments are ignored |

The rule inside `TActiveRecord::getRecordCriteria()`: when the second argument is not an array, every argument after the condition is a positional value. When the second argument is an array, it is the parameter list and later arguments are ignored. Do not mix the two forms in one call.

Values are bound through PDO, so quoting is handled by the driver. Column and table names cannot be bound; they are part of the condition string.

---

## Dynamic Finders

`TActiveRecord::__call()` turns a method name into a condition. The prefix selects the operation, and the rest of the name lists columns joined by `And` or `Or`.

| Prefix | Operation | Returns |
|---|---|---|
| `findBy` | `find()` | one record or `null` |
| `findAllBy` | `findAll()` | array |
| `deleteBy` | `deleteAll()` | `int` rows deleted |
| `deleteAllBy` | `deleteAll()` | `int` rows deleted |
| `with` | relation handler, see [Relations](relations.md#eager-loading) | `TActiveRecordRelation` |

Name rules:

- The prefix is case-insensitive and may be followed by one underscore: `findByName`, `findby_name`.
- Column names are matched case-insensitively against the table's columns, using the physical names.
- Separators are `And`, `Or`, `_And_`, `_Or_` (case-insensitive). They produce SQL `AND` and `OR` in a flat condition, so SQL precedence applies.
- Each column takes one argument, in name order. Arguments are separate values or one array of values.
- Comparisons are equality only.

```php
$finder->findByUsername('admin');                       // username = ?
$finder->findBy_Username_And_Password($name, $hash);    // username = ? AND password = ?
$finder->findAllByDepartment_IdOrActive(3, 1);          // department_id = ? OR active = ?
$finder->findAllByActive([1]);                          // one array of values
$finder->deleteByUsername('guest');
```

| Condition | Result |
|---|---|
| Fewer arguments than columns | `TDbException` `dbtablegateway_mismatch_args_exception` |
| More arguments than columns | the driver rejects the extra bindings with `TDbException` |
| No part of the name matches a column | `TDbException` `dbtablegateway_mismatch_column_name` |
| Name has none of the prefixes | `null`, or `TActiveRecordException` when `InvalidFinderResult` is `Exception` (see [Record Classes](records.md#invalidfinderresult)) |

Names starting with `dy` or `fx` are PRADO dynamic and global events and are passed to `TComponent`.

---

## TActiveRecordCriteria

**Class:** `Prado\Data\ActiveRecord\TActiveRecordCriteria`, extends `Prado\Data\DataGateway\TSqlCriteria`

A criteria object carries the parts of a query other than the table. Any finder that takes `$criteria` accepts one.

### Constructor

```php
new TActiveRecordCriteria();
new TActiveRecordCriteria('active = ?', 1);
new TActiveRecordCriteria('a = ? AND b = ?', $a, $b);
new TActiveRecordCriteria('a = ? AND b = ?', [$a, $b]);
new TActiveRecordCriteria('a = :a', [':a' => $a]);
```

### Properties

| Property | Type | Description |
|---|---|---|
| `Condition` | string | SQL after `WHERE`. Setting it extracts trailing `ORDER BY`, `LIMIT`, and `OFFSET` clauses into the properties below. An empty value clears the condition. |
| `Parameters` | `TAttributeCollection` | Positional (integer keys) or named (`:name` keys) values. Keys are case-sensitive. `setParameters()` accepts an array or `ArrayAccess`. |
| `IsNamedParameters` | bool | `true` when the first parameter key is a string. |
| `OrdersBy` | `TAttributeCollection` | Column → `asc` or `desc`. `setOrdersBy()` also accepts a string such as `'name DESC, age'`. |
| `Limit` | int | Maximum rows. |
| `Offset` | int | Rows to skip. |
| `Select` | mixed | Field list after `SELECT`. Default `'*'`. A string is used as written. `null` expands to every quoted column. An array quotes column names and supports aliases: `['col1', 'total' => 'COUNT(*)']`. |

`Limit`, `Offset`, and `OrdersBy` are rendered by the command builder for the connection's driver, in that database's syntax. `Select` applies to `find()`, `findAll()`, and `findAllByIndex()`; `count()` always selects `COUNT(*)`.

```php
$criteria = new TActiveRecordCriteria();
$criteria->Condition = 'department_id = :dept AND active = :active';
$criteria->Parameters[':dept'] = 3;
$criteria->Parameters[':active'] = 1;
$criteria->OrdersBy['last_name'] = 'asc';
$criteria->OrdersBy['first_name'] = 'asc';
$criteria->Limit = 25;
$criteria->Offset = 50;

$users = UserRecord::finder()->findAll($criteria);
$total = UserRecord::finder()->count($criteria);
```

`__toString()` renders the condition, parameters, ordering, limit, and offset for logging.

### Paging pattern

```php
$pageSize = 20;
$criteria = new TActiveRecordCriteria('active = ?', 1);
$criteria->OrdersBy['username'] = 'asc';
$total = $finder->count($criteria);
$criteria->Limit = $pageSize;
$criteria->Offset = $pageIndex * $pageSize;
$rows = $finder->findAll($criteria);
```

`count()` passes `Limit` and `Offset` into the `COUNT(*)` query. Count first, then set them, as above.

---

[prado-activerecord](../README.md) -> [ActiveRecord Docs](README.md) -> Finders

## See Also

- [Record Classes](records.md): primary keys, `InvalidFinderResult`
- [Relations](relations.md): `with<Relation>()` on top of any finder
- [Events](events.md): inspecting the SQL a finder runs
