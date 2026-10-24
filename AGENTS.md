# AGENTS.md

Agent instructions for working with prado-activerecord. Supplements `CLAUDE.md`.

## Build, Lint, and Test Commands

```bash
# Lint (dry-run, no changes)
vendor/bin/php-cs-fixer fix --dry-run

# Lint (apply fixes)
vendor/bin/php-cs-fixer fix

# Static analysis
vendor/bin/phpstan analyse --memory-limit=512M

# Run pure unit tests (no database required)
vendor/bin/phpunit --testsuite unit

# Run database-backed test suites
vendor/bin/phpunit --testsuite db-sqlite
vendor/bin/phpunit --testsuite db-mysql

# Filter within a suite
vendor/bin/phpunit --testsuite unit --filter <File, ClassName, or methodName>
```

### Composer shortcuts

```bash
composer fix       # apply cs-fixer style fixes to src/
composer stan      # phpstan analyse --memory-limit=512M
composer test      # = vendor/bin/phpunit --testsuite unit
composer unittest  # = vendor/bin/phpunit --testsuite unit
composer dbtest    # = vendor/bin/phpunit --testsuite unit,db-sqlite,db-mysql
composer fulltest  # full pre-commit check: php -l, cs-fixer dry-run, phpstan, phpunit unit
```

Run `composer fix` before `composer fulltest` to auto-apply style fixes; `fulltest` runs the cs-fixer in dry-run (check-only) mode.

> **Never** add `--verbose` or other extra flags to phpunit commands not listed above. It is not a valid flag.

## PHP Coding Standards

- Indentation: **tabs** (not spaces)
- Line endings: Unix `\n`
- PHP 8.1+ syntax; CI tests 8.2, 8.3, 8.4, 8.5
- PSR-12 enforced via php-cs-fixer
- Uniform Access / Self Encapsulation: access own fields only through getters/setters, never `$this->_prop` directly in subclasses

## Naming Conventions

| Element | Convention | Example |
|---|---|---|
| Classes | `TPascalCase` | `TActiveRecordGateway`, `TScaffoldEditView` |
| Methods | `camelCase` | `findAllByPks`, `getRecordTableInfo` |
| Variables | `camelCase` | `$criteria`, `$tableInfo` |
| Constants | `SCREAMING_SNAKE_CASE` | `HAS_MANY`, `MANY_TO_MANY` |
| Enumeration values | `PascalCase` | `Null`, `Exception` |
| Private / protected properties | `_camelCase` | `_recordState` |
| Namespace | `Prado\Data\ActiveRecord\{Sub}` | `Prado\Data\ActiveRecord\Scaffold\TScaffoldListView` |

## Documentation Standards

All docblocks must pass the documentation style enforced by PRADO. The same rules apply here.

Language and National Variety: English — American
Qualities of the writing: clear, thorough, easy to comprehend, not verbose (brevity), timeless, integrated, wholistic
Tense: Present

**Banned constructions:**

- **Antithesis**: "does not just X, it Ys"; "rather than X, it Ys". State once what it does.
- **Em-dash dramatic asides**: no "— and that's the point", "— never stronger". Use a period.
- **Editorializing / filler**: "importantly", "of course", "it is worth noting". Omit.
- **Rule-of-three rhetorical build-ups**. One fact per sentence.

Prefer: subject–verb–object declaratives and `condition → result` bullet/table pairs.

### New API elements

- **`@since` tag**: use the next release version of this package when adding new public methods or classes, written as `@since 1.1.0 of prado-activerecord`. The existing `@since 3.1` style tags are PRADO versions from before the extraction and stay as they are.
- `@author` names: Wei Zhuo, Brad Anderson, Fabio Bas. Only these three.

## Error Handling

- Extension exceptions inherit from `TActiveRecordException` (a `TDbException`) in `src/Data/ActiveRecord/Exceptions/`.
- Error message codes (`ar_*`) are defined in `src/Data/ActiveRecord/Exceptions/messages.txt`; `TActiveRecordException::getErrorMessageFile()` resolves that file next to the class.
- Do not silently swallow exceptions; rethrow or convert to `TActiveRecordException` at the ActiveRecord boundary.

## Framework-Specific Guidelines

### TActiveRecordConfig

The extension participates in the host PRADO application through `TActiveRecordConfig`, a `TDataSourceConfig` subclass. When the host application loads the module, it calls `TActiveRecordConfig::init()`, which configures the `TActiveRecordManager` singleton and, in a `TShellApplication`, registers `TActiveRecordAction`.

**Pattern 1: ConnectionID reference** (production, recommended):
```xml
<modules>
  <module id="db" class="Prado\Data\TDataSourceConfig">
    <database ConnectionString="mysql:host=localhost;dbname=app" Username="user" Password="pass"/>
  </module>
  <module id="activerecord" class="Prado\Data\ActiveRecord\TActiveRecordConfig" ConnectionID="db" EnableCache="true"/>
</modules>
```

**Pattern 2: Inline `<database>` child element** (self-contained apps and tests):
```xml
<module class="Prado\Data\ActiveRecord\TActiveRecordConfig">
  <database ConnectionString="sqlite:protected/data/app.db"/>
</module>
```

### Record classes

- A record class extends `TActiveRecord`, names its table with `const TABLE = 'name'` (or a static `table()` method), declares one public property per column, and overrides `finder($className = __CLASS__)` so `Record::finder()` returns the right class.
- Relations are declared in `public static $RELATIONS = ['prop' => [self::HAS_MANY, OtherRecord::class, 'optional_fk_or_assoc_table']]`. Types: `HAS_MANY`, `HAS_ONE`, `BELONGS_TO`, `MANY_TO_MANY`.
- Eager loading uses `with<Prop>()` before a finder call; lazy loading uses `fetchResultsFor('prop')` from a getter.

### Scaffold controls

`TScaffoldListView`, `TScaffoldEditView`, `TScaffoldSearch`, and `TScaffoldView` take a `RecordClass` property. Their templates (`.tpl`) and `style.css` sit beside the classes and are found through the class file path, so they work from any install location. `TScaffoldInputBase::createInputBuilder()` selects the `T<Driver>ScaffoldInput` by the connection's driver name and raises `fxActiveRecordScaffoldInputClass` for an unknown driver.

### prado-cli

`TActiveRecordAction` provides `activerecord/generate <table> <output> [soap] [overwrite]` and `activerecord/generate-all <output> [soap] [overwrite] [prefix] [suffix]`. The action is registered by `TActiveRecordConfig::init()`, so it exists only when the application configures that module. `<output>` is a dot namespace such as `Application.database.UserRecord` that must resolve inside the application directory.

## Testing

### Test Suite Boundaries

| Suite | Description | DB needed |
|---|---|---|
| `unit` | `tests/unit/Data/ActiveRecord` minus `DbSpecific/`, plus `tests/unit/Shell` | No |
| `db-sqlite` | `DbSpecific/Sqlite`: relation tests over the bundled SQLite files | SQLite files in the repo |
| `db-mysql` | `DbSpecific/Mysql`: finders, criteria, counts, deletes, records over `prado_unitest` | Yes |

### Adding a Test

**Pure unit test**: place under `tests/unit/Data/ActiveRecord/` (not in `DbSpecific/`), mirroring the `src/` subdirectory. Extend `PHPUnit\Framework\TestCase` directly.

**MySQL test**: place in `DbSpecific/Mysql/`, use `PradoUnitDataConnectionTrait`, return `true` from `getIsForActiveRecord()`, and list the tables in `getTestTables()`. Add missing tables to `tests/unit/Data/ActiveRecord/scripts/mysql/database.sql`; `.github/scripts/setup-mysql.sh` loads that file in CI.

**SQLite test**: place in `DbSpecific/Sqlite/`. Give the records a `getDbConnection()` that opens a file under `tests/unit/Data/ActiveRecord/sqlite/`. Keep the test read-only or restore the file afterward; the files are committed.

### Test Bootstrap

`tests/test_tools/phpunit_bootstrap.php` loads the Composer autoloader, `Prado.php`, boots a `TTestApplication` (from `vendor/pradosoft/prado/tests/unit/Harness`), declares every class in `config/classes.json` through Composer (so `Prado::createComponent()` by fully qualified name finds the `src/` copy and not the vendored framework's), registers `config/classes.json` with `Prado::registerClassMap()`, and adds `messages.txt` with `TException::addMessageFile()`. No `app/` directory is required; `TTestApplication` uses `sys_get_temp_dir()` and creates its own `runtime/`.

Individual test files do not need any harness `require_once`; the autoloader covers `Prado\Test\Unit\*` from `tests/unit/` first and the framework's `tests/unit/` second.

`tests/test_tools/phpstan-bootstrap.php` loads the autoloader and `Prado.php` for PHPStan.

## Static Analysis (PHPStan)

`phpstan.neon.dist` at the project root registers all 7 PRADO PHPStan extensions from `vendor/pradosoft/prado`. These extensions handle:

- `DynamicMethodsClassReflectionExtension`: `getXxx`/`setXxx` method magic
- `TComponentPropertiesReflectionExtension`: TComponent property access
- `TComponentHasMethodTypeSpecifyingExtension`
- `TComponentCanGetPropertyTypeSpecifyingExtension`
- `TComponentCanSetPropertyTypeSpecifyingExtension`
- `TComponentIsaTypeSpecifyingExtension`
- `PradoMethodVisibleStaticMethodTypeSpecifyingExtension`

Level: 2. Paths: `src/`.

## Directory Structure

```
prado-activerecord/
├── composer.json               — prado4-extension; bootstrap = TActiveRecordConfig
├── config/classes.json         — short class name → FQCN map (extra.prado.class-map)
├── phpstan.neon.dist           — level 2; registers 7 Prado\PHPStan\* extensions
├── phpunit.xml                 — suites: unit, db-sqlite, db-mysql
├── CLAUDE.md                   — concise guidance for agents
├── AGENTS.md                   — this file
├── docs/                       — user documentation
├── src/
│   ├── Data/ActiveRecord/
│   │   ├── TActiveRecord.php, TActiveRecordConfig.php, TActiveRecordManager.php,
│   │   │   TActiveRecordGateway.php, TActiveRecordCriteria.php, ...
│   │   ├── Exceptions/         — TActiveRecordException, messages.txt
│   │   ├── Relations/          — relation classes
│   │   └── Scaffold/           — scaffold controls, templates, style.css, InputBuilder/
│   └── Shell/Actions/TActiveRecordAction.php
└── tests/
    ├── test_tools/
    │   ├── phpunit_bootstrap.php
    │   └── phpstan-bootstrap.php
    └── unit/Data/ActiveRecord/
        ├── Exceptions/         — guard test: every thrown code has message text
        ├── Records/            — fixture record classes
        ├── Scaffold/InputBuilder/
        ├── DbSpecific/Mysql/
        ├── DbSpecific/Sqlite/
        ├── sqlite/             — committed SQLite databases
        └── scripts/            — mysql/database.sql (CI schema), fk_tests.sql variants
    └── unit/Shell/Actions/     — TActiveRecordActionTest (prado-cli flag parsing)
```

## Anti-Patterns and Required Safeguards

- **Never** run `git clone/mv/restore/rm/add/commit/merge/rebase/reset/pull/push/fetch` without developer approval first.
- **Never** run `rm` on any path without developer approval first.
- **Never** remove `composer --dev` dependencies.
- **Never** erase or overwrite files during unit testing. The file changes being tested must be preserved.
- **Never** delete any folders or files until the associated task is absolutely and totally complete.
- **Never** add `--no-verify` to git commands or `--no-strict` to phpunit.
- **Never** rename a namespace or class. Applications that upgraded from the framework-bundled ActiveRecord depend on the existing names.
- **Never** write to the committed SQLite files from a test without restoring them.

## Working Knowledge Sub-Process

When asked to understand a subsystem before modifying it, follow this pattern:

1. Read the primary class file(s) for the subsystem.
2. Read the corresponding test files to understand expected behavior.
3. Check `tests/unit/Data/ActiveRecord/Records/` and `scripts/mysql/database.sql` if the subsystem touches a table.
4. Run `vendor/bin/phpunit --testsuite unit` (no DB) to confirm the baseline, then `--testsuite db-sqlite`.
5. Only then propose changes.
