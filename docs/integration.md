[prado-activerecord](../README.md) -> [ActiveRecord Docs](README.md) -> Prado Integration

# Prado Integration

This page covers registering `TActiveRecordConfig` as a PRADO application module, what the module sets up, the `TActiveRecordManager` and `TActiveRecordGateway` objects behind every record, using more than one database connection, and binding records to template controls.

---

## Module Registration

`TActiveRecordConfig` (`Prado\Data\ActiveRecord\TActiveRecordConfig`) is a `TDataSourceConfig` subclass. Declare it once in `application.xml` or `application.php`. Its `init()` hands the connection to the shared `TActiveRecordManager`, and every record class uses that connection unless it overrides `getDbConnection()`.

### Attributes

| Attribute | Type | Default | Description |
|---|---|---|---|
| `id` | string | | Module identifier. The package locates the module by type, so any id works. |
| `class` | string | | `Prado\Data\ActiveRecord\TActiveRecordConfig`. The short name `TActiveRecordConfig` resolves through the package class map. |
| `ConnectionID` | string | `''` | ID of a `TDataSourceConfig` or `TDbConnection` module whose connection this module reuses. Empty means the `<database>` element configures a connection owned by this module. |
| `ConnectionClass` | string | `Prado\Data\TDbConnection` | Class instantiated for the connection when `ConnectionID` is empty. Must extend `TDbConnection`. |
| `EnableCache` | bool | `false` | When `true`, table metadata is stored in the application cache. See [Caching Table Metadata](#caching-table-metadata). |
| `ManagerClass` | string | `Prado\Data\ActiveRecord\TActiveRecordManager` | Class instantiated as the record manager. |
| `GatewayClass` | string | `Prado\Data\ActiveRecord\TActiveRecordGateway` | Class the manager instantiates as the record gateway. |
| `InvalidFinderResult` | `Null` or `Exception` | `Null` | What `TActiveRecord::__call()` does for a method name that is not a finder. See [Record Classes](records.md#invalidfinderresult). |

### The `<database>` element

The `<database>` child element (the `database` array in PHP format) sets properties on the connection object. Any `TDbConnection` property is accepted. The ones used most often:

| Attribute | Description |
|---|---|
| `ConnectionString` | PDO DSN, for example `mysql:host=localhost;dbname=myapp` or `sqlite:/var/data/myapp.db`. |
| `Username`, `Password` | Credentials for the DSN. |
| `Charset` | Connection character set. Not applied for IBM DB2; SQL Server and Oracle take the charset from the DSN. |
| `Persistent` | `true` for a persistent PDO connection. |
| `ColumnCase` | `Preserved`, `LowerCase`, or `UpperCase` (`TDbColumnCaseMode`). |
| `NullConversion` | `Preserved`, `NullToEmptyString`, or `EmptyStringToNull` (`TDbNullConversionMode`). |

Use `ConnectionID` or `<database>`, not both. With `ConnectionID` set, `<database>` attributes are applied to the referenced module's connection.

### Example: shared connection module

```xml
<modules>
    <module id="db" class="Prado\Data\TDataSourceConfig">
        <database ConnectionString="mysql:host=localhost;dbname=myapp"
                  Username="dbuser" Password="dbpass" />
    </module>

    <module id="activerecord" class="Prado\Data\ActiveRecord\TActiveRecordConfig"
            ConnectionID="db" EnableCache="true" />
</modules>
```

The `db` module can also serve other modules, for example `TSqlMapConfig`, so one connection is shared across the application.

### Example: inline database element

```xml
<modules>
    <module id="activerecord" class="Prado\Data\ActiveRecord\TActiveRecordConfig"
            EnableCache="true" InvalidFinderResult="Exception">
        <database ConnectionString="sqlite:/var/data/myapp.db" />
    </module>
</modules>
```

### PHP configuration style

```php
return [
    'modules' => [
        'db' => [
            'class' => 'Prado\Data\TDataSourceConfig',
            'database' => [
                'ConnectionString' => 'mysql:host=localhost;dbname=myapp',
                'Username' => 'dbuser',
                'Password' => 'dbpass',
            ],
        ],
        'activerecord' => [
            'class' => 'Prado\Data\ActiveRecord\TActiveRecordConfig',
            'properties' => [
                'ConnectionID' => 'db',
                'EnableCache' => 'true',
            ],
        ],
    ],
];
```

### PRADO 4.4 package id

PRADO 4.4 reads `extra.prado.bootstrap` from the package's `composer.json`, so the module can be named by package:

```xml
<module id="pradosoft/prado-activerecord" ConnectionID="db" EnableCache="true" />
```

---

## TActiveRecordConfig

**Class:** `Prado\Data\ActiveRecord\TActiveRecordConfig`

`init()` runs these steps in order:

1. `TDataSourceConfig::init()` applies the `<database>` attributes to the connection.
2. `getManager()` creates the manager from `ManagerClass` and installs it as the singleton with `TActiveRecordManager::getInstance($manager)`.
3. `EnableCache` is `true` and the application has a cache module → `$manager->setCache($cache)`.
4. `$manager->setDbConnection($this->getDbConnection())`. With `ConnectionID` set, this is the referenced module's connection.
5. `$manager->setInvalidFinderResult($this->getInvalidFinderResult())`.
6. `$manager->setGatewayClass($this->getGatewayClass())`.
7. `installShellAction()`: the application is a `TShellApplication` and `TActiveRecordAction` is not registered → `addShellActionClass(TActiveRecordAction::class)`. See [Command Line](cli.md).

`TActiveRecordConfig` implements `IModuleDependency`. `getModuleDependencies()` returns `ConnectionID`, so the application initializes the referenced connection module before this one.

Declare one `TActiveRecordConfig` module. A second module replaces the manager singleton in step 2, and the first module's settings are lost.

| Property | PHP accessor | Description |
|---|---|---|
| `Manager` | `getManager()` | The `TActiveRecordManager` singleton. |
| `DbConnection` | `getDbConnection()` | The connection this module configured or referenced. |
| `ConnectionID` | `getConnectionID()` / `setConnectionID()` | Module id of a shared connection. |
| `ConnectionClass` | `getConnectionClass()` / `setConnectionClass()` | Connection class for an inline `<database>` element. Throws `TConfigurationException` when the connection already exists. |
| `EnableCache` | `getEnableCache()` / `setEnableCache()` | Whether table metadata goes to the application cache. |
| `ManagerClass` | `getManagerClass()` / `setManagerClass()` | Manager implementation class. |
| `GatewayClass` | `getGatewayClass()` / `setGatewayClass()` | Gateway implementation class. |
| `InvalidFinderResult` | `getInvalidFinderResult()` / `setInvalidFinderResult()` | `TActiveRecordInvalidFinderResult::Null` or `::Exception`. |

The constants `TActiveRecordConfig::DEFAULT_MANAGER_CLASS` and `DEFAULT_GATEWAY_CLASS` hold the default class names.

---

## Caching Table Metadata

Every record class needs its table's metadata: column names and types, primary keys, foreign keys, sequences. `TActiveRecordGateway::getTableInfo()` reads it through `TDbMetaData` once per request and keeps it in memory.

With `EnableCache="true"` and an application cache module (`TMemCache`, `TDbCache`, `TFileCache`, and so on):

- Cache key → the connection string followed by the table name.
- Cache hit → the stored `TDbTableInfo` is used and the database is not queried for metadata.
- Cache miss → metadata is read from the database and stored with the cache module's default expiry.

Without a cache module, `EnableCache` has no effect. A schema change is not visible while the cached entry lives. Flush the cache after changing a table definition (`prado-cli cache/flush-all` when a cache module is configured).

---

## TActiveRecordManager

**Class:** `Prado\Data\ActiveRecord\TActiveRecordManager`

The manager is a singleton that holds the default connection, the cache, the gateway class, and the `InvalidFinderResult` setting. Records reach it through the static `TActiveRecord::getRecordManager()`.

| Member | Description |
|---|---|
| `getInstance($self = null)` | Returns the singleton. Passing an object installs it as the singleton. |
| `DbConnection` | Default `TDbConnection` for records that do not override `getDbConnection()`. |
| `Cache` | `ICache` used for table metadata, or `null`. |
| `GatewayClass` | Gateway implementation class. Setting it discards the current gateway. |
| `getRecordGateway()` | The `TActiveRecordGateway`, created on first call with `Prado::createComponent($gatewayClass, $manager)`. |
| `InvalidFinderResult` | Default for records whose own setting is `null`. |

Without the module, for example in a script or a test, configure the manager directly:

```php
use Prado\Data\ActiveRecord\TActiveRecordManager;
use Prado\Data\TDbConnection;

$conn = new TDbConnection('pgsql:host=localhost;dbname=test', 'dbuser', 'dbpass');
TActiveRecordManager::getInstance()->setDbConnection($conn);

$user = UserRecord::finder()->findByPk('admin');
```

---

## TActiveRecordGateway

**Class:** `Prado\Data\ActiveRecord\TActiveRecordGateway`

The gateway turns finder calls into SQL. It keeps one `TDataGatewayCommand` per connection string, sets that command's `TDbTableInfo` to the current record's table before each call, and records which `TActiveRecord` made the call so events can reach it. The `DataGatewayClass` property names the command class (default `Prado\Data\DataGateway\TDataGatewayCommand`).

| Method | Called by |
|---|---|
| `getRecordTableInfo($record)` | `TActiveRecord::getRecordTableInfo()`, the Scaffold controls |
| `getTableInfo($connection, $tableName)` | The CLI and the `MANY_TO_MANY` relation for the association table |
| `getCommand($record)` | Every finder and write path |
| `findRecordByPK`, `findRecordsByPks`, `findRecordsByCriteria`, `findRecordBySql`, `findRecordsBySql`, `findRecordsByIndex`, `countRecords` | The finder methods |
| `insert`, `update`, `delete` | `save()` and `delete()` |
| `deleteRecordsByPk`, `deleteRecordsByCriteria` | `deleteByPk()` and `deleteAll()` |

Application code does not call the gateway. Subclass it and set `GatewayClass` to change how commands are built or executed.

---

## Multiple Database Connections

The manager holds one default connection. A record class chooses another connection in one of these ways.

### Override `getDbConnection()`

A base record class returns its own connection. Every record class that extends it uses that connection for finders, writes, and relation queries.

```php
use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Data\TDbConnection;

abstract class ReportingRecord extends TActiveRecord
{
    public function getDbConnection()
    {
        static $conn;
        if ($conn === null) {
            $conn = new TDbConnection('mysql:host=reports;dbname=stats', 'reader', 'secret');
        }
        return $conn;
    }
}

class PageViewRecord extends ReportingRecord
{
    const TABLE = 'page_views';
    public $page;
    public $views;

    public static function finder($className = __CLASS__)
    {
        return parent::finder($className);
    }
}
```

The override can also pull a connection from a second `TDataSourceConfig` module:

```php
public function getDbConnection()
{
    return \Prado\Prado::getApplication()->getModule('reportsdb')->getDbConnection();
}
```

### Per-instance connection

The constructor takes a connection as its second argument, and `setDbConnection()` changes it later.

```php
$record = new PageViewRecord(['page' => '/home', 'views' => 1], $otherConnection);
$record->save();
```

Records that a finder loads are created without a connection, so a connection set on the finder instance does not carry over to them. They use the override or the manager default.

### Serialization

`TActiveRecord::__sleep()` drops the connection. After `unserialize()` the record uses the override or the manager default again.

---

## Manager and Gateway Events

`TActiveRecordManager` raises no events of its own. The gateway raises `OnCreateCommand` and `OnExecuteCommand` for every command of every record class on every connection. Attach a handler through the manager:

```php
$gateway = TActiveRecordManager::getInstance()->getRecordGateway();
$gateway->OnExecuteCommand[] = function ($sender, $param) {
    \Prado\Prado::trace($param->getCommand()->getText(), 'sql');
};
```

The same two events are raised on the record that made the call, and `save()` and `delete()` raise `OnInsert`, `OnUpdate`, and `OnDelete`. See [Events](events.md).

---

## Binding Records to Template Controls

`Prado\Web\UI\TTemplateControl` has two methods that move values between a model and the controls on a page or template control. They match by name: a control whose `ID` equals a key of the model data. A record is a model through `getViewData()` and `setViewData()` (see [records.md](records.md#exporting-data)), which are PRADO 4.4's `Prado\Web\UI\IViewDataModel` contract. The record provides them by name, so the binding also works on PRADO 4.3, whose `tryToUpdateView()` reads the record's public properties and `$RELATIONS` directly.

| Method | Direction |
|---|---|
| `tryToUpdateView($record, $throwExceptions = false)` | `getViewData()` → control values |
| `tryToUpdateModel($record, $throwExceptions = false)` | Input control values → `setViewData()` (PRADO 4.4) |
| `tryToUpdateAR($record, $throwExceptions = false)` | Same as `tryToUpdateModel()`; the name from PRADO 4.3 |

```php
// Fill the form from a record
$student = StudentRecord::finder()->findByPk(1);
$this->tryToUpdateView($student);

// Build a record from the form and save it
$student = new StudentRecord();
$this->tryToUpdateAR($student);
$student->save();
```

`tryToUpdateView()` sets each value of `getViewData()` on the control with that `ID`. The control type decides what it takes; a key without a control, or with a control of another type, is skipped.

| Control | Value | Takes |
|---|---|---|
| `TDataBoundControl` (`TDropDownList`, `TRepeater`, ...) | iterable, such as a `HAS_MANY` list | `DataSource`, then `dataBind()` |
| `TDatePicker` | date string | `Date` |
| any `IDataRenderer` (`TTextBox`, `TCheckBox`, `TLabel`, `TListControl`, ...) | scalar | `Data`: `Text`, `Checked`, `SelectedValue` |

Relations follow the columns in `getViewData()`. A `BELONGS_TO` or `HAS_ONE` value is the related record, so the control shows its string form; a `HAS_MANY` or `MANY_TO_MANY` value is a list for a data-bound control. A relation that is not loaded loads during the call.

`tryToUpdateModel()` reads every input control registered with the view (`IPostBackDataHandler` controls that are also `IDataRenderer`, a `TDatePicker` as `Date`) and passes the values, keyed by control `ID`, to `setViewData()`. The record assigns the keys that name column properties and ignores relation keys and unknown keys. Related records are never written back from the view.

Both methods catch every exception raised while binding one control and continue with the next. A mistyped `ID` produces no error and no value. Pass `true` as the second argument to rethrow instead:

```php
$this->tryToUpdateView($student, true);
$this->tryToUpdateAR($student, true);
```

---

[prado-activerecord](../README.md) -> [ActiveRecord Docs](README.md) -> Prado Integration

## See Also

- [Quick Start](quickstart.md): a complete module, table, and record class
- [Record Classes](records.md): `TABLE`, columns, `finder()`, `InvalidFinderResult`
- [Events](events.md): `OnCreateCommand` and `OnExecuteCommand` parameters
- [Command Line](cli.md): the `activerecord` command the module registers
