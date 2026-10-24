# CLAUDE.md

This file provides guidance to Agents when working with code in this repository.

## What This Is

**prado-activerecord** is a PRADO 4 composer extension (`prado4-extension`) that provides the Active Record ORM, the Scaffold controls, and the `prado-cli activerecord` command for the PRADO PHP framework. Source lives under `src/Data/ActiveRecord/` (PSR-4 namespace `Prado\Data\ActiveRecord\`) and `src/Shell/Actions/TActiveRecordAction.php` (`Prado\Shell\Actions\`). Tests live under `tests/unit/Data/ActiveRecord/` and `tests/unit/Shell/Actions/`.

## Commands

```bash
# Code style check (dry-run)
vendor/bin/php-cs-fixer fix --dry-run src/

# Code style fix (apply)
vendor/bin/php-cs-fixer fix src/

# Static analysis
vendor/bin/phpstan analyse --memory-limit=512M

# Run pure unit tests (no database required)
composer test
# or:
vendor/bin/phpunit --testsuite unit

# Run unit + SQLite + MySQL together in one run
composer dbtest
# or:
vendor/bin/phpunit --testsuite unit,db-sqlite,db-mysql

# Run a single database suite
composer db-sqlite
composer db-mysql
# or:
vendor/bin/phpunit --testsuite db-sqlite
vendor/bin/phpunit --testsuite db-mysql

# Filter to a specific test class or method
vendor/bin/phpunit --testsuite unit --filter <File, ClassName, or methodName>
```

### Full Check (required before git commit)

```bash
composer fulltest
```

Runs in order. All must pass:

1. PHP compile check: `find src -name '*.php' | xargs php -l`
2. `vendor/bin/php-cs-fixer fix --dry-run src/`
3. `vendor/bin/phpstan analyse --memory-limit=512M`
4. `vendor/bin/phpunit --testsuite unit`

Run `composer fix` first to auto-apply style fixes before the dry-run check.

> **Never add or change phpunit command options** when unit testing. Only run project unit tests as specified above. phpunit does NOT have a `--verbose` CLI option.

## Architecture

### Extension Entry Point

**`TActiveRecordConfig`** (`src/Data/ActiveRecord/TActiveRecordConfig.php`) is the `TDataSourceConfig` subclass that bootstraps the extension. It is the `extra.prado.bootstrap` module in `composer.json`. `init()` creates the `TActiveRecordManager` singleton, hands it the connection (from `ConnectionID` or the inline `<database>` element), the application cache when `EnableCache` is true, the `GatewayClass`, and the `InvalidFinderResult` setting, then registers `TActiveRecordAction` when the application is a `TShellApplication`.

### Source Layout

```
src/
├── Data/ActiveRecord/
│   ├── TActiveRecordConfig.php       — application module; extension entry point
│   ├── TActiveRecord.php             — base record class: finders, save/delete, relations, events
│   ├── TActiveRecordManager.php      — singleton: connection, cache, gateway, InvalidFinderResult
│   ├── TActiveRecordGateway.php      — SQL execution over TDataGatewayCommand; table info cache
│   ├── TActiveRecordCriteria.php     — Condition, Parameters, OrdersBy, Limit, Offset
│   ├── TActiveRecordChangeEventParameter.php — OnInsert/OnUpdate/OnDelete parameter (IsValid)
│   ├── TActiveRecordInvalidFinderResult.php  — enum: Null | Exception
│   ├── Exceptions/                   — TActiveRecordException, TActiveRecordConfigurationException, messages.txt
│   ├── Relations/                    — TActiveRecordRelation and HasMany, HasOne, BelongsTo, HasManyAssociation, RelationContext
│   └── Scaffold/                     — TScaffoldBase, TScaffoldListView, TScaffoldEditView, TScaffoldSearch, TScaffoldView (+ .tpl, style.css)
│       └── InputBuilder/             — TScaffoldInputBase/Common and one T<Driver>ScaffoldInput per driver
└── Shell/Actions/
    └── TActiveRecordAction.php       — prado-cli `activerecord/generate` / `generate-all`
```

### Test Layout

```
tests/
├── test_tools/
│   ├── phpunit_bootstrap.php   — autoloader, Prado.php, TTestApplication, class map, message file
│   └── phpstan-bootstrap.php   — autoloader and Prado.php for phpstan
└── unit/Data/ActiveRecord/
    ├── BaseActiveRecordTest.php, TActiveRecordConfigTest.php   — unit suite
    ├── Exceptions/TActiveRecordExceptionTest.php                — unit suite: every thrown code has message text
    ├── Scaffold/InputBuilder/TScaffoldInputBaseTest.php        — unit suite
    ├── Records/                 — record fixture classes shared by the suites
    ├── DbSpecific/Mysql/        — db-mysql suite: needs the prado_unitest MySQL database
    ├── DbSpecific/Sqlite/       — db-sqlite suite: reads the bundled SQLite files
    ├── sqlite/                  — fk_tests.db, test1.sqlite, blog.db
    └── scripts/mysql, scripts/sqlite — schema scripts (database.sql is what CI loads)
tests/unit/Shell/Actions/TActiveRecordActionTest.php — unit suite: prado-cli flag parsing over an in-memory SQLite
```

### Finder Flow

`UserRecord::finder()->findByPk($id)` → `TActiveRecord::getRecordManager()->getRecordGateway()` → `TActiveRecordGateway::findRecordsByPks()` → `TDataGatewayCommand` builds and runs the SQL on the record's `DbConnection` → `TActiveRecord::populateObject()` fills a new record instance.

## Naming Conventions

| Thing | Convention | Example |
|---|---|---|
| Classes | `TPascalCase` | `TActiveRecordManager`, `TScaffoldListView` |
| Methods | `camelCase` | `findByPk`, `getRecordGateway` |
| Variables | `camelCase` | `$tableInfo` |
| Class Constants | `SCREAMING_SNAKE_CASE` | `HAS_MANY`, `BELONGS_TO` |
| Enumerated Constants | `PascalCase` | `Null`, `Exception` |
| Class properties | `_camelCase` | `_recordState`, `_connection` |
| Namespace | `Prado\Data\ActiveRecord\{Sub}` | `Prado\Data\ActiveRecord\Relations\TActiveRecordHasMany` |

## Important Rules

- **No `classes.php`**. This is a standalone extension; the PSR-4 autoloader handles class discovery. Do not create a `classes.php`.
- **`composer.json` `extra.prado`** (Prado 4.4) declares `bootstrap`, `error-messages` (`src/Data/ActiveRecord/Exceptions/messages.txt`), and `class-map` (`config/classes.json`, short name → FQCN for `class="TActiveRecordConfig"` and `<com:TScaffoldListView>` style references). Keep the legacy top-level `extra.bootstrap` for Prado 4.3. Add new classes to `config/classes.json`.
- **Namespaces stay as they were in the framework.** `Prado\Data\ActiveRecord\...` and `Prado\Shell\Actions\TActiveRecordAction` are the public API of applications that upgraded from the bundled copy.
- **`if` statements** always use a block (`{}`), never a single-line body.
- **Backward compatibility**: all changes must be backward compatible for point releases.
- **`@since` tag**: use the next release version of this package when adding new methods or classes, written as `@since 1.1.0 of prado-activerecord` so it is not read as a PRADO version. Omit the method tag when it matches the class tag.
- **Uniform Access Principal / Self Encapsulation** required: access own properties via getters/setters, not raw `$this->_prop`.
- **Error codes** for PRADO exceptions are defined in `src/Data/ActiveRecord/Exceptions/messages.txt` (`ar_*`).
- **`@author` tags**: only Wei Zhuo, Brad Anderson, and Fabio Bas are listed as extension authors.

## Test Bootstrap

The bootstrap (`tests/test_tools/phpunit_bootstrap.php`) loads the Composer autoloader, `Prado.php`, and boots a `TTestApplication` (from the prado vendor harness). `TTestApplication` uses `sys_get_temp_dir()` as its base path and creates its own `runtime/` directory, so no `app/` directory is needed. The bootstrap first declares every class in `config/classes.json` through Composer. `Prado::using()`, which `Prado::createComponent()` calls for a fully qualified name, includes the file from the framework directory when the class is not declared yet, so without this step a test that creates a class by name before Composer has loaded it would run the vendored framework's copy instead of `src/`. The bootstrap also registers `config/classes.json` and the message file, which PRADO does for installed packages but not for the root package under test.

`autoload-dev` maps `Prado\Test\Unit\` to `tests/unit/` first and `vendor/pradosoft/prado/tests/unit/` second, so `PradoUnit`, `PradoUnitDataConnectionTrait`, `TTestApplication`, and `TTestShellApplication` come from the framework harness while the `Records\*` fixtures come from this package.

## Testing

### Test Suite Boundaries

- **`unit` suite**: pure unit tests only (no database). Everything under `tests/unit/Data/ActiveRecord` except `DbSpecific/`, plus `tests/unit/Shell`.
- **`db-sqlite`**: `DbSpecific/Sqlite`. Reads the committed files in `tests/unit/Data/ActiveRecord/sqlite/`. Needs `pdo_sqlite` and nothing else.
- **`db-mysql`**: `DbSpecific/Mysql`. Needs a MySQL server with the `prado_unitest` database, user `prado_unitest` / `prado_unitest`, loaded from `tests/unit/Data/ActiveRecord/scripts/mysql/database.sql`. The tests use `PradoUnitDataConnectionTrait`, which fails the first test and skips the rest of a class when the server or a table is missing.

### Adding a New Test

- A test that needs no database goes next to `BaseActiveRecordTest.php` or under a subdirectory mirroring `src/` (as `Scaffold/InputBuilder/` does).
- A test that needs MySQL goes in `DbSpecific/Mysql/` in namespace `Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Mysql`, uses `PradoUnitDataConnectionTrait`, returns `true` from `getIsForActiveRecord()`, and lists its tables in `getTestTables()`. Add any new table to `scripts/mysql/database.sql`.
- A test that needs only a SQLite file goes in `DbSpecific/Sqlite/` and gives its records a `getDbConnection()` override pointing at a file in `tests/unit/Data/ActiveRecord/sqlite/`.

### PradoUnit Infrastructure

- `PradoUnit::snapshot($obj)` / `PradoUnit::restore($obj, $snap)`: save/restore all properties including private ancestors.
- `PradoUnit::getProp($obj, 'name')` / `PradoUnit::setProp($obj, 'name', $val)`: reflection access to private properties.
- `PradoUnit::snapshotStatic(Cls::class)` / `PradoUnit::restoreStatic(Cls::class, $snap)`: same for static state.
- `TActiveRecordManager::getInstance()` keeps its singleton in a function-static variable. A test that replaces it (any `TActiveRecordConfig::init()` call does) must capture the previous instance and pass it back to `getInstance($previous)` in a `finally` block.

## Code Style

- Indentation: **tabs** (not spaces)
- Line endings: Unix (`\n`)
- PHP minimum: **8.1** (CI tests 8.2, 8.3, 8.4, 8.5; the `pradosoft/prado` dev dependency requires 8.2)
- PSR-12 enforced via php-cs-fixer
- Use `?` for single nullable types and in doc blocks

### Documentation Style (enforced)

Docblocks are technical documentation written with direct technical statements.
Language and National Variety: English — American
Qualities of the writing: clear, thorough, easy to comprehend, not verbose (brevity), timeless/always was, integrated, wholistic
Tense: Present — tune for ease of comprehension

_Banned constructions_:
- **Antithesis / "not merely X — it Ys"**: no "does not just X, it Ys", "is not a Y, it's a Z", "rather than X, it Ys". State what it does, once.
- **Em-dash dramatic asides** used for emphasis or reveal ("— and that's the point", "— never stronger"). Use a period or plain clause.
- **Editorializing / filler** is unnecessary.
- **Rule-of-three rhetorical lists** and build-up sentences. One fact per sentence.

Prefer: subject–verb–object declaratives, tables and bullet lists of `condition → result` where appropriate.
Docblocks inform and describe; persuasive writing is not the goal.

## Anti-Patterns (Required Safeguards)

- **Never** run `git clone/mv/restore/rm/add/commit/merge/rebase/reset/pull/push/fetch` without developer approval first.
- **Never** run `rm` on any path without developer approval first.
- **Never** remove `composer --dev` dependencies.
- **Never** erase or overwrite files during unit testing. The file changes being tested must be preserved.
