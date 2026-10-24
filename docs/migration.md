[prado-activerecord](../README.md) -> [ActiveRecord Docs](README.md) -> Migration

# Migration guide

Active Record shipped inside the PRADO framework from PRADO 3.1. PRADO 4.4 removes `framework/Data/ActiveRecord` from the framework, and this package takes its place. Class names and namespaces are unchanged, so an application adds the dependency and keeps its record classes, module configuration, and templates as they are.

## Install the package

```sh
composer require pradosoft/prado-activerecord
```

The package requires PHP 8.1 or later and PRADO 4.3.3 or later. Its Composer type is `prado4-extension`.

PRADO reads the package's `extra.prado` section from `composer.json`:

| Key | Value | Effect |
|---|---|---|
| `extra.prado.class-map` | `config/classes.json` | Short class names such as `TActiveRecord` and `TScaffoldListView` resolve to their namespaced classes. PRADO 4.4 registers the map system-wide. |
| `extra.prado.error-messages` | `src/Data/ActiveRecord/Exceptions/messages.txt` | The `ar_*` error codes keep their message text. |
| `extra.prado.bootstrap` (and legacy `extra.bootstrap`) | `Prado\Data\ActiveRecord\TActiveRecordConfig` | In PRADO 4.4, `<module id="pradosoft/prado-activerecord"/>` registers the module by package name. |

Existing module definitions keep working without changes.

### PRADO 4.3 and the bundled copy

PRADO 4.3.x still ships `framework/Data/ActiveRecord`. With the package installed beside it, two copies of each class exist and the first one loaded wins:

- A record class that `extends TActiveRecord`, and any class reached through Composer's autoloader → the package copy.
- A class created by its fully qualified name through `Prado::createComponent()` before Composer has loaded it, for example `<module class="Prado\Data\ActiveRecord\TActiveRecordConfig">` → the framework copy, because `Prado::using()` includes the file from the framework directory when it exists.
- A class created by its short name, `<module class="TActiveRecordConfig">` → the class map decides; on PRADO 4.4 the package's `config/classes.json` is registered and the package copy loads.

The two copies behave the same for everything an application calls. The fixes listed in the [changelog](../CHANGELOG.md) apply only to the package copy. The package takes full effect on PRADO 4.4, where the framework copy is gone and every lookup reaches the package.

## What is unchanged

- **Namespaces**: every class keeps its namespace, for example `Prado\Data\ActiveRecord\TActiveRecord`, `Prado\Data\ActiveRecord\TActiveRecordConfig`, `Prado\Data\ActiveRecord\Scaffold\TScaffoldListView`, and `Prado\Shell\Actions\TActiveRecordAction`.
- **Short names** still resolve. `config/classes.json` maps them to their namespaced classes, so `<module class="TActiveRecordConfig">`, `<com:TScaffoldView>`, and `Prado::createComponent('TActiveRecordManager')` work as before.
- **Module configuration**: `TActiveRecordConfig` properties (`ConnectionID`, `EnableCache`, `ManagerClass`, `GatewayClass`, `InvalidFinderResult`) and the inline `<database>` element are unchanged.
- **Record classes**: the `TABLE` constant, the `table()` method, `$COLUMN_MAPPING`, and `$RELATIONS` definitions are unchanged.
- **API**: finder methods, dynamic finders, `TActiveRecordCriteria`, `save()`, `delete()`, and the `with<Relation>()` methods keep their names, arguments, and return values.
- **Events**: `OnInsert`, `OnUpdate`, `OnDelete`, `OnCreateCommand`, and `OnExecuteCommand` are raised at the same points with the same parameter classes.
- **Scaffold**: control properties, templates, input builders, and `style.css` are unchanged.
- **Command line**: `prado-cli activerecord/generate` and `activerecord/generate-all` take the same arguments and write the same class skeleton.
- **Error codes**: existing `ar_*` codes keep their text.

The package code is the framework copy in PRADO `master` at commit `165efc3a8`, with the one change described below.

## What changed

### Shell action registration

In the framework, `TShellApplication::installShellActions()` registered `TActiveRecordAction` itself when the application had a `TActiveRecordConfig` module. In this package, `TActiveRecordConfig::init()` registers the action with the `TShellApplication` when the application is one.

- Application is a `TShellApplication` and the action is not yet registered → `init()` calls `addShellActionClass(TActiveRecordAction::class)`.
- Action already registered → `init()` keeps the existing instance.
- Application is a web application → nothing is registered.

The visible result is the same: `prado-cli activerecord/generate` is available whenever a `TActiveRecordConfig` module is configured. Code that called `TShellApplication::hasActiveRecordConfig()` to decide whether to register the action no longer needs to.

## Verifying the move

After installing the package:

1. Confirm `class="TActiveRecordConfig"` and `class="Prado\Data\ActiveRecord\TActiveRecordConfig"` module definitions still resolve.
2. Load a page that uses a record class and a page that uses a Scaffold control.
3. Run `prado-cli help` in the application directory and confirm `activerecord/generate` and `activerecord/generate-all` are listed.
4. Run the application's own tests against its record classes.
