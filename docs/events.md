[prado-activerecord](../README.md) -> [ActiveRecord Docs](README.md) -> Events

# Events

Active Record raises five events. Three change events run before a write and can stop it. Two command events expose the SQL command before it runs and the result after it runs. This page lists each event, its parameter class, where handlers attach, and the order in which they fire during `save()` and `delete()`.

---

## Event Summary

| Event | Raised on | Parameter | When |
|---|---|---|---|
| `OnInsert` | the record | `TActiveRecordChangeEventParameter` | `save()` on a new record, before the `INSERT` |
| `OnUpdate` | the record | `TActiveRecordChangeEventParameter` | `save()` on a loaded record, before the `UPDATE` |
| `OnDelete` | the record | `TActiveRecordChangeEventParameter` | `delete()`, before the `DELETE` |
| `OnCreateCommand` | the gateway, then the record that made the call | `TDataGatewayEventParameter` | after the command is built and its parameters are bound, before it runs |
| `OnExecuteCommand` | the gateway, then the record that made the call | `TDataGatewayResultEventParameter` | after the command runs, before the result is used; not raised by `deleteAll()` |

There are no events after a write completes.

---

## Change Events

### `TActiveRecordChangeEventParameter`

**Class:** `Prado\Data\ActiveRecord\TActiveRecordChangeEventParameter`

| Property | Default | Effect |
|---|---|---|
| `IsValid` | `true` | `false` → the statement is not sent; `save()` or `delete()` returns `false` and the record state is unchanged. |

### Handling in the record class

The event methods are public on `TActiveRecord`. Override them in the record class and call the parent so that attached handlers still run.

```php
class UserRecord extends TActiveRecord
{
    const TABLE = 'users';
    public $username;
    public $password;
    public $nounce;

    public function onInsert($param)
    {
        parent::onInsert($param);
        $this->nounce = bin2hex(random_bytes(8));
        $this->password = sha1($this->password . $this->nounce);
    }

    public function onUpdate($param)
    {
        parent::onUpdate($param);
        if ($this->username === '') {
            $param->setIsValid(false);   // save() returns false, no UPDATE
        }
    }

    public function onDelete($param)
    {
        parent::onDelete($param);
        if ($this->username === 'admin') {
            $param->setIsValid(false);   // delete() returns false, no DELETE
        }
    }
}
```

Values changed in `onInsert()` and `onUpdate()` are written, because the gateway reads the column values after the event.

### Attaching handlers

Change events are raised on the record instance that is saved or deleted. Handlers attached to the finder do not fire for them; attach to the instance, or override the method as above.

```php
$user = UserRecord::finder()->findByPk('admin');
$user->OnUpdate[] = function ($sender, $param) {
    $param->IsValid = $sender->email !== '';
};
$user->save();
```

---

## Command Events

### Parameter classes

Both classes live in `Prado\Data\DataGateway`.

| Class | Property | Description |
|---|---|---|
| `TDataGatewayEventParameter` | `Command` | The `TDbCommand` about to run. Read `getText()` for the SQL. Do not rebind its parameters or change its text. |
| | `Criteria` | The `TSqlCriteria` used to build the command. |
| `TDataGatewayResultEventParameter` | `Command` | The `TDbCommand` that ran. |
| | `Result` | The value returned by the database: a row array, a `TDbDataReader`, a row count, or a last insert id. Writable. |

Setting `Result` in an `OnExecuteCommand` handler changes what the finder populates or what `save()` and `delete()` see as the row count.

### Where the events fire

`TDataGatewayCommand` raises the two events on itself. `TActiveRecordGateway` listens and re-raises each one twice with the same parameter object:

1. On the gateway, with the gateway as sender. Fires for every command of every record class.
2. On the record that made the call, with that record as sender.

Which record is "the record that made the call":

| Call | Record |
|---|---|
| `find()`, `findAll()`, `findByPk()`, `count()`, `deleteAll()`, `deleteByPk()`, dynamic finders | the finder instance the method was called on |
| `save()`, `delete()` | the record instance |
| `with<Relation>()` loading | the related class's finder, or for `MANY_TO_MANY` the source record or finder |

Handlers attached to `UserRecord::finder()` fire for that class's finder queries. Handlers attached to the gateway fire for everything.

```php
// Every command of every record class
$gateway = TActiveRecordManager::getInstance()->getRecordGateway();
$gateway->OnCreateCommand[] = function ($sender, $param) {
    \Prado\Prado::trace($param->getCommand()->getText(), 'sql');
};

// Finder queries of one class
UserRecord::finder()->OnExecuteCommand[] = [$logger, 'log'];

// One record's own save() and delete()
$user->OnExecuteCommand[] = function ($sender, $param) {
    // $param->getResult() is the row count or the last insert id
};
```

Handlers on the finder are class-wide and live for the request. Attach them once, for example in a module's `init()` or a base record class constructor.

---

## Order During `save()`

A new record (`STATE_NEW`):

1. `onInsert($param)` → `OnInsert` handlers.
2. `$param->IsValid` is `false` → `save()` returns `false`. Stop.
3. The gateway collects insert values. A `NOT NULL` column without a value, default, or sequence → `TActiveRecordException` `ar_value_must_not_be_null`.
4. `OnCreateCommand` on the gateway, then on the record.
5. The `INSERT` runs.
6. `OnExecuteCommand` on the gateway, then on the record. `Result` is the row count.
7. Columns with a sequence receive the last insert id.
8. The row count is greater than zero → state becomes `STATE_LOADED`, `save()` returns `true`. Otherwise `save()` returns `false`.

A loaded record (`STATE_LOADED`):

1. `onUpdate($param)` → `OnUpdate` handlers.
2. `$param->IsValid` is `false` → `save()` returns `false`. Stop.
3. The gateway collects update values and the primary key values. The same `NOT NULL` check applies.
4. `OnCreateCommand` on the gateway, then on the record.
5. The `UPDATE ... WHERE <primary key>` runs.
6. `OnExecuteCommand` on the gateway, then on the record. `Result` is the row count.
7. The row count is greater than zero → `save()` returns `true`. Otherwise `false`.

A deleted record → `TActiveRecordException` `ar_save_invalid` before any event.

The row count in step 7 is what the driver reports. A driver that reports zero for an `UPDATE` that changes no values makes `save()` return `false` for an unchanged record.

---

## Order During `delete()`

A loaded record:

1. `onDelete($param)` → `OnDelete` handlers.
2. `$param->IsValid` is `false` → `delete()` returns `false`. Stop.
3. `OnCreateCommand` on the gateway, then on the record.
4. The `DELETE ... WHERE <primary key>` runs.
5. `OnExecuteCommand` on the gateway, then on the record. `Result` is the row count.
6. The row count is greater than zero → state becomes `STATE_DELETED`, `delete()` returns `true`. Otherwise `false`.

A new or deleted record → `TActiveRecordException` `ar_delete_invalid` before any event.

`deleteByPk()` and `deleteAll()` run on the finder and raise no `OnDelete`. `deleteByPk()` raises both command events. `deleteAll()`, and the dynamic `deleteBy*()` finders built on it, raise `OnCreateCommand` only; `TDataGatewayCommand::delete()` returns the row count without raising `OnExecuteCommand`.

---

## Logging Example

```php
abstract class LoggedRecord extends TActiveRecord
{
    public function __construct($data = [], $connection = null)
    {
        parent::__construct($data, $connection);
        $this->OnExecuteCommand[] = [$this, 'logCommand'];
    }

    public function logCommand($sender, $param)
    {
        \Prado\Prado::trace($param->getCommand()->getText(), static::class);
    }
}
```

Every record and finder of a class that extends `LoggedRecord` logs the commands it runs. A gateway handler, shown above, does the same for all classes with one attachment.

---

[prado-activerecord](../README.md) -> [ActiveRecord Docs](README.md) -> Events

## See Also

- [Record Classes](records.md): record state and `save()`/`delete()` return values
- [Prado Integration](integration.md): reaching the gateway through the manager
- [Finders](finders.md): the calls that raise command events
