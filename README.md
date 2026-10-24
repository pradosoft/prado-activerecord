# prado-activerecord

Active Record ORM, Scaffold controls, and the `prado-cli activerecord` command for the [PRADO PHP Framework](https://github.com/pradosoft/prado).

A record class maps one table to one PHP class: each public property is a column, `finder()` methods read rows into objects, `save()` and `delete()` write them back, and a static `$RELATIONS` array declares `HAS_MANY`, `HAS_ONE`, `BELONGS_TO`, and `MANY_TO_MANY` relations that load eagerly with `with<Relation>()` or lazily on access. The Scaffold controls render list, edit, search, and detail views for any record class. prado-activerecord is the `Prado\Data\ActiveRecord` package that shipped inside the framework through PRADO 4.3, moved into its own repository for PRADO 4.4 with the same namespaces and class names.

## Requirements

- PHP 8.1 or later
- PRADO 4.3.3 or later (`pradosoft/prado`)
- PDO extension and a PDO driver for your database

On PRADO 4.3.x the framework still bundles its own ActiveRecord copy; the [migration guide](docs/migration.md) explains which copy loads. PRADO 4.4 removes the bundled copy.

## Installation

Within a PRADO application:

```bash
composer require pradosoft/prado-activerecord
```

Applications upgrading from the ActiveRecord bundled with the PRADO framework: see the [migration guide](docs/migration.md). Release notes are in the [changelog](CHANGELOG.md). The full documentation is under [docs/](docs/README.md).

## Supported Databases

Any database that PRADO's `Prado\Data` layer supports. The Scaffold input builders and `activerecord/generate-all` cover:

| Driver | PDO DSN prefix |
|---|---|
| MySQL / MariaDB | `mysql:` |
| PostgreSQL | `pgsql:` |
| SQLite | `sqlite:` |
| Firebird / InterBase | `firebird:` |
| SQL Server | `sqlsrv:` |
| Oracle | `oci:` |
| IBM DB2 | `ibm:` |

The database test suites run against SQLite, MySQL, PostgreSQL, Microsoft SQL Server, Firebird, Oracle, and IBM DB2, one CI job each.

## Configuration

Register `TActiveRecordConfig` as an application module in `application.xml` (or `application.php`). The module configures the default `TActiveRecordManager`, which every record uses unless it overrides `getDbConnection()`.

### Pattern 1 — Shared connection (recommended)

Reference a `TDataSourceConfig` module by its `id`:

```xml
<modules>
    <module id="db" class="Prado\Data\TDataSourceConfig">
        <database ConnectionString="mysql:host=localhost;dbname=myapp"
                  Username="user" Password="secret"/>
    </module>

    <module id="activerecord" class="Prado\Data\ActiveRecord\TActiveRecordConfig"
            ConnectionID="db" EnableCache="true"/>
</modules>
```

### Pattern 2 — Inline connection

Embed the `<database>` element directly inside `TActiveRecordConfig`:

```xml
<modules>
    <module id="activerecord" class="Prado\Data\ActiveRecord\TActiveRecordConfig">
        <database ConnectionString="sqlite:protected/data/myapp.db"/>
    </module>
</modules>
```

In PRADO 4.4 the package can also be named by its Composer id, `<module id="pradosoft/prado-activerecord" ConnectionID="db"/>`; the class comes from the package's `extra.prado.bootstrap`.

### TActiveRecordConfig properties

| Property | Type | Description |
|---|---|---|
| `ConnectionID` | string | ID of a `TDataSourceConfig` module to share its connection |
| `EnableCache` | bool | Cache table metadata in the PRADO application cache (default `false`) |
| `ManagerClass` | string | `TActiveRecordManager` subclass to use (default `TActiveRecordManager`) |
| `GatewayClass` | string | `TActiveRecordGateway` subclass to use (default `TActiveRecordGateway`) |
| `InvalidFinderResult` | `Null` or `Exception` | What an unknown dynamic finder (`findByNoSuchColumn()`) returns or throws (default `Null`) |

## Defining a record

```sql
CREATE TABLE users (
    username   VARCHAR(40) NOT NULL PRIMARY KEY,
    email      VARCHAR(80),
    first_name VARCHAR(40),
    last_name  VARCHAR(40)
);
```

```php
use Prado\Data\ActiveRecord\TActiveRecord;

class UserRecord extends TActiveRecord
{
    const TABLE = 'users';

    public $username;
    public $email;
    public $first_name;
    public $last_name;

    public static function finder($className = __CLASS__)
    {
        return parent::finder($className);
    }
}
```

`const TABLE` names the table. A record class may define a static `table()` method instead. Each column needs a public property of the same name, or an entry in the static `$COLUMN_MAPPING` array that maps the column to a property. Overriding `finder()` lets `UserRecord::finder()` return a finder for the right class.

## Finders and writes

```php
$user  = UserRecord::finder()->findByPk('admin');
$users = UserRecord::finder()->findAll('last_name = ?', 'Dalton');
$some  = UserRecord::finder()->findAll('email LIKE :mail', [':mail' => '%@example.com']);
$count = UserRecord::finder()->count('first_name = ?', 'Joe');

// dynamic finders: columns joined by And / Or
$joe   = UserRecord::finder()->findByFirst_NameAndLast_Name('Joe', 'Dalton');
$all   = UserRecord::finder()->findAllByEmail(null);

// criteria object
$criteria = new Prado\Data\ActiveRecord\TActiveRecordCriteria('email IS NOT NULL');
$criteria->OrdersBy['last_name'] = 'asc';
$criteria->Limit = 20;
$criteria->Offset = 40;
$page = UserRecord::finder()->findAll($criteria);

$user = new UserRecord();
$user->username = 'joe';
$user->email = 'joe@example.com';
$user->save();            // INSERT; later calls on the same object UPDATE

$user->email = 'joe@dalton.example';
$user->save();

$user->delete();
UserRecord::finder()->deleteByPk('averel');
UserRecord::finder()->deleteAll('last_name = ?', 'Dalton');
```

Finder methods, parameter binding forms, and `TActiveRecordCriteria` are documented in [docs/finders.md](docs/finders.md).

## Relations

```php
class TeamRecord extends TActiveRecord
{
    const TABLE = 'teams';
    public $name;
    public $location;
    public $players = [];

    public static $RELATIONS = [
        'players' => [self::HAS_MANY, PlayerRecord::class],
    ];
}

class PlayerRecord extends TActiveRecord
{
    const TABLE = 'players';
    public $player_id;
    public $age;
    public $team_name;
    public $team;

    public static $RELATIONS = [
        'team'   => [self::BELONGS_TO, TeamRecord::class],
        'skills' => [self::MANY_TO_MANY, SkillRecord::class, 'player_skills'],
    ];
}

$teams  = TeamRecord::finder()->withPlayers()->findAll();          // eager load
$player = PlayerRecord::finder()->withTeam()->withSkills()->findByPk(1);
```

Foreign keys are read from the table metadata. A third array element names the foreign key column or, for `MANY_TO_MANY`, the association table. See [docs/relations.md](docs/relations.md) for lazy loading, self references, and saving related objects.

## Scaffold

```xml
<com:TScaffoldListView ID="list" RecordClass="UserRecord" />
<com:TScaffoldEditView RecordClass="UserRecord" />
<com:TScaffoldSearch   ListViewID="list" />
<com:TScaffoldView     RecordClass="UserRecord" />
```

`TScaffoldView` combines list, edit, and search in one control. The controls publish their own `style.css` (light and dark color schemes) and pick an input builder per database driver. See [docs/scaffold.md](docs/scaffold.md).

## Command line

With a `TActiveRecordConfig` module configured, `prado-cli` gains the `activerecord` action:

```bash
# one record class for one table
prado-cli activerecord/generate users Application.database.UserRecord

# one record class per table, with optional soap properties, overwrite, class prefix and suffix
prado-cli activerecord/generate-all Application.database soap overwrite "" Record
```

`<output>` is a PRADO dot namespace that must resolve inside the application directory. `TActiveRecordConfig::init()` registers the action with the shell application; without the module the action is absent from `prado-cli help`. See [docs/cli.md](docs/cli.md).

## Development

### Setup

```bash
composer install
```

### Commands

| Command | Description |
|---|---|
| `composer fix` | Apply cs-fixer style fixes to `src/` |
| `composer stan` | PHPStan static analysis |
| `composer test` | PHPUnit unit suite (no database required) |
| `composer unittest` | Alias for `composer test` |
| `composer db-sqlite` | Database cases over a SQLite file built from `scripts/sqlite/database.sql`, plus the relation tests over the bundled SQLite files |
| `composer db-mysql` | Database cases over MySQL |
| `composer db-pgsql`, `db-sqlsrv`, `db-firebird`, `db-oracle`, `db-ibm` | The same cases over PostgreSQL, SQL Server, Firebird, Oracle, and IBM DB2 |
| `composer dbtest` | Unit + SQLite + MySQL in one PHPUnit run |
| `composer fulltest` | Pre-commit check: `php -l` + cs-fixer dry-run + phpstan + unit tests |

Run `composer fix` before `composer fulltest` when the style check fails; `fulltest` runs cs-fixer in dry-run (check-only) mode.

### Filtering to one test class or method

```bash
vendor/bin/phpunit --testsuite unit --filter TScaffoldInputBaseTest
vendor/bin/phpunit --testsuite db-sqlite --filter test_has_many
```

### Database test setup

The database cases (finders, criteria, counts, deletes, dynamic finders, record save and delete, auto-increment keys, events) are written once as abstract classes under `tests/unit/Data/ActiveRecord/DbSpecific/Cases/`. Each driver directory runs every case through its connection trait, which names the `PradoUnit::setup*Connection()` helper, the database, the connection settings, and the features the driver lacks; a test that needs a missing feature is skipped with the reason. The schema for each driver is `tests/unit/Data/ActiveRecord/scripts/<driver>/database.sql`, loaded in CI by `.github/scripts/setup-<driver>.sh`. A test class whose server or table is missing fails its first test and skips the rest.

The `db-sqlite` suite builds its database from the SQLite script in the system temporary directory and needs only `pdo_sqlite`; the relation tests read the committed files in `tests/unit/Data/ActiveRecord/sqlite/`.

The `db-mysql` suite connects to `mysql:host=localhost;dbname=prado_unitest` as `prado_unitest` / `prado_unitest`. Create the database and user, then load the MySQL script. The other servers use the same credentials as the PRADO framework tests: PostgreSQL `prado_unitest` / `prado_unitest` on port 5432 (create the database with the C locale, which the ordering tests expect), SQL Server `prado_unitest` on port 1433, Firebird `SYSDBA` / `masterkey` with `FIREBIRD_DB_PATH`, Oracle `prado_unitest` on `ORACLE_SERVICE_NAME` (default `FREEPDB1`), and IBM DB2 through `DB2_USER`, `DB2_PASSWORD`, and `DB2_DATABASE`.

### Pre-commit checklist

Run `composer fulltest`. All four steps must pass before committing:

1. `find src -name '*.php' | xargs php -l`: syntax check
2. `vendor/bin/php-cs-fixer fix --dry-run src/`: style check
3. `vendor/bin/phpstan analyse --memory-limit=512M`: static analysis
4. `vendor/bin/phpunit --testsuite unit`: unit tests

### Adding a database-backed test

- MySQL: put the class in `tests/unit/Data/ActiveRecord/DbSpecific/Mysql/`, use `PradoUnitDataConnectionTrait`, return `true` from `getIsForActiveRecord()`, and list the tables in `getTestTables()`. Add any new table to `scripts/mysql/database.sql`.
- SQLite: put the class in `DbSpecific/Sqlite/` and give its records a `getDbConnection()` override that opens a file under `tests/unit/Data/ActiveRecord/sqlite/`.

## Architecture

```
src/
├── Data/ActiveRecord/
│   ├── TActiveRecordConfig.php          — PRADO module; bootstraps the extension
│   ├── TActiveRecord.php                — base record: finders, save/delete, relations, events
│   ├── TActiveRecordManager.php         — singleton: connection, cache, gateway, InvalidFinderResult
│   ├── TActiveRecordGateway.php         — builds and runs SQL through TDataGatewayCommand
│   ├── TActiveRecordCriteria.php        — Condition, Parameters, OrdersBy, Limit, Offset
│   ├── TActiveRecordChangeEventParameter.php
│   ├── TActiveRecordInvalidFinderResult.php
│   ├── Exceptions/                      — TActiveRecordException, TActiveRecordConfigurationException, messages.txt
│   ├── Relations/                       — TActiveRecordRelation, HasMany, HasOne, BelongsTo, HasManyAssociation, RelationContext
│   └── Scaffold/                        — TScaffoldBase, ListView, EditView, Search, View, templates, style.css
│       └── InputBuilder/                — TScaffoldInputBase, TScaffoldInputCommon, one T<Driver>ScaffoldInput per driver
└── Shell/Actions/
    └── TActiveRecordAction.php          — prado-cli activerecord/generate and generate-all
```

### Finder flow

`UserRecord::finder()->findByPk($id)`
→ `TActiveRecord::getRecordManager()->getRecordGateway()`
→ `TActiveRecordGateway::findRecordsByPks()`
→ `TDataGatewayCommand` builds and executes the SQL on the record's `DbConnection`
→ `TActiveRecord::populateObject()` fills a new instance from the row

## License

BSD-3-Clause. See [LICENSE](LICENSE).

## Authors

- Wei Zhuo — original Active Record and Scaffold implementation
- Fabio Bas — PRADO 4 maintenance
- Brad Anderson — shell command, Firebird and DB2 support, extension extraction and maintenance
