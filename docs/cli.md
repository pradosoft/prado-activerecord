[prado-activerecord](../README.md) -> [ActiveRecord Docs](README.md) -> Command Line

# Command Line

`prado-cli` gains the `activerecord` command from this package. It generates record class files from the tables of the configured connection. The command is provided by `Prado\Shell\Actions\TActiveRecordAction`.

---

## Availability

`TActiveRecordConfig::init()` registers `TActiveRecordAction` with the application when the application is a `TShellApplication`. The command exists whenever a `TActiveRecordConfig` module is configured, and is absent otherwise.

`prado-cli` loads the application configuration from the first location that holds `application.xml` or `application.php`:

1. The project root above `vendor/`.
2. The current working directory.
3. `protected/` under the current working directory.
4. The directory given with `-d=<folder>` or `--directory=<folder>`, or its `protected/` subdirectory.

The module's connection is the one the command uses, so the credentials in the application configuration must be valid from the shell.

```sh
cd /path/to/app
vendor/bin/prado-cli help activerecord
```

```
usage: php prado-cli.php activerecord/<action>

example: php prado-cli.php activerecord/generate

The following actions are available:

  activerecord/generate <table> <output> [soap] [overwrite]
         Generate Active Record skeleton for <table> to <output>. May also generate [soap] properties.

  activerecord/generate-all <output> [soap] [overwrite] [prefix] [suffix]
         Generate Active Record skeleton for all Tables to <output>. ...
```

The action name and the method are separated by a slash. `activerecord` alone runs the first method, `generate`.

---

## Commands

| Command | Arguments | Result |
|---|---|---|
| `activerecord/generate` | `<table> <output> [soap] [overwrite]` | One class file for `<table>` at `<output>`. |
| `activerecord/generate-all` | `<output> [soap] [overwrite] [prefix] [suffix]` | One class file per table under the `<output>` namespace. |

Arguments are positional. Pass an empty string (`""`) to skip an optional argument and give a later one.

### `activerecord/generate <table> <output>`

```sh
prado-cli activerecord/generate users Application.database.UserRecord
```

| Argument | Meaning |
|---|---|
| `<table>` | Table or view name on the module's connection. A name with no columns prints `Unable to find table or view` and writes nothing. |
| `<output>` | Dot namespace of the class file, or an existing file path. |
| `soap` | Writes a docblock with `@var` and `@soapproperty` above each generated property. `true` is accepted in place of `soap`. |
| `overwrite` | Replaces an existing output file. Without it an existing file is skipped with `File ... already exists, skipping`. `true` is accepted in place of `overwrite`. |

The class name is the last segment of `<output>`. The file is written only when the directory exists.

### `activerecord/generate-all <output>`

```sh
prado-cli activerecord/generate-all Application.database
prado-cli activerecord/generate-all Application.database "" "" "" Record
```

| Argument | Meaning |
|---|---|
| `<output>` | Dot namespace of the directory that receives the files. |
| `soap`, `overwrite` | As for `generate`. |
| `prefix` | Prepended to each class name. |
| `suffix` | Appended to each class name. |

The command lists the tables with a driver-specific query, then runs `generate` for each with the class name `<prefix>` + table name with its first letter capitalized + `<suffix>`. The second example writes `UsersRecord.php` for a `users` table.

| Driver | Tables listed |
|---|---|
| `mysql`, `mysqli` | `SHOW TABLES` |
| `sqlite`, `sqlite2` | `sqlite_master` entries except `sqlite_sequence` |
| `pgsql` | base tables of the `public` schema |
| `mssql`, `sqlsrv`, `dblib` | `INFORMATION_SCHEMA.TABLES` base tables |
| `oci` | `user_tables` |
| `ibm` | `SYSCAT.TABLES` of the current schema, type `T` |
| `firebird`, `interbase` | user relations that are not views |

Any other driver prints `generateAll is not implemented for <driver>`.

---

## The `<output>` Namespace

`<output>` is a PRADO dot namespace. The first segment is a path alias; `Application` is the application base directory, the one that holds `application.xml`. The remaining segments are directories, and the last segment is the file name without `.php`.

| `<output>` | File |
|---|---|
| `Application.database.UserRecord` | `protected/database/UserRecord.php` |
| `Application.Records.Blog` | `protected/Records/Blog.php` |

The directory must exist and must resolve inside the application base directory. A namespace outside it, or in a missing directory, prints `Output file ... must be within directory ...` and writes nothing. An absolute path to an existing file inside the application directory is accepted in place of the namespace.

Add the directory to the application's `<using>` paths so the generated classes load:

```xml
<paths>
    <using namespace="Application.database.*" />
</paths>
```

---

## The Generated Class

`TActiveRecordAction::generateClass()` writes this skeleton:

```php
<?php
/**
 * Auto generated by prado-cli.php on <generation date and time>.
 */
class UserRecord extends TActiveRecord
{
	const TABLE='users';

	public $username;
	public $email;
	public $first_name;
	public $last_name;

	public static function finder($className=__CLASS__)
	{
		return parent::finder($className);
	}
}
```

| Part | Source |
|---|---|
| Class name | last segment of `<output>`, or the table name with prefix and suffix for `generate-all` |
| `TABLE` | the `<table>` argument as given |
| Properties | one `public` property per column, in table order, named after the column |
| `finder()` | the standard override |

The file has no `namespace` statement and no `use` statement. `TActiveRecord` resolves through the package class map. The timestamp in the header comment is the generation time.

Edit the file after generation to add `$RELATIONS`, `$COLUMN_MAPPING`, getters and setters, or a namespace. Without `overwrite`, the command does not touch an existing file, so edits are kept on later runs.

---

[prado-activerecord](../README.md) -> [ActiveRecord Docs](README.md) -> Command Line

## See Also

- [Prado Integration](integration.md): the module that registers the command
- [Record Classes](records.md): what to add to a generated class
- [Migration](migration.md): how registration changed from the framework-bundled action
