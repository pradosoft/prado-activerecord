[prado-activerecord](../README.md) -> [ActiveRecord Docs](README.md) -> Quick Start

# Quick Start

This page installs the package, registers the module, and runs one record class through find, save, and delete.

---

## 1. Install

```sh
composer require pradosoft/prado-activerecord
```

The package requires PHP 8.1 or later and PRADO 4.3.3 or later.

---

## 2. Register the module

Add a `TActiveRecordConfig` module to `protected/application.xml`. The `<database>` element carries the `TDbConnection` settings. The `<using>` path lets the application load record classes from `protected/database/`.

```xml
<application id="MyApp">
  <paths>
    <using namespace="Application.database.*" />
  </paths>
  <modules>
    <module id="activerecord" class="Prado\Data\ActiveRecord\TActiveRecordConfig">
      <database ConnectionString="mysql:host=localhost;dbname=myapp"
                Username="dbuser" Password="dbpass" />
    </module>
  </modules>
</application>
```

The same configuration in PHP format:

```php
return [
    'paths' => [
        'using' => ['Application.database.*'],
    ],
    'modules' => [
        'activerecord' => [
            'class' => 'Prado\Data\ActiveRecord\TActiveRecordConfig',
            'database' => [
                'ConnectionString' => 'mysql:host=localhost;dbname=myapp',
                'Username' => 'dbuser',
                'Password' => 'dbpass',
            ],
        ],
    ],
];
```

The module hands its connection to `TActiveRecordManager`, and every record class uses that connection. See [Prado Integration](integration.md) for `ConnectionID`, `EnableCache`, and the other properties.

---

## 3. Create a table

```sql
CREATE TABLE users
(
    username VARCHAR(20) NOT NULL,
    email VARCHAR(200),
    PRIMARY KEY (username)
);
```

---

## 4. Define the record class

Create `protected/database/UserRecord.php`. Each column has a public property of the same name. `TABLE` names the table. `finder()` returns the shared finder instance for the class.

```php
<?php

use Prado\Data\ActiveRecord\TActiveRecord;

class UserRecord extends TActiveRecord
{
    const TABLE = 'users';

    public $username;
    public $email;

    public static function finder($className = __CLASS__)
    {
        return parent::finder($className);
    }
}
```

---

## 5. Find

```php
$finder = UserRecord::finder();

$admin = $finder->findByPk('admin');                    // one record, or null
$user = $finder->find('email = ?', 'admin@example.com'); // one record, or null
$all = $finder->findAll();                              // array, empty when the table is empty
$gmail = $finder->findAll('email LIKE ?', '%@gmail.com');
$total = $finder->count();                              // int

$sameAsAbove = $finder->findByUsername('admin');        // dynamic finder
```

Single-record finders return `null` when nothing matches. Multi-record finders return an empty array.

---

## 6. Save

A record created with `new` is inserted. A record returned by a finder is updated.

```php
$user = new UserRecord();
$user->username = 'admin';
$user->email = 'admin@example.com';
$user->save(); // INSERT INTO users ...

$user = new UserRecord(['username' => 'guest', 'email' => 'guest@example.com']);
$user->save(); // INSERT, record data given to the constructor

$user = UserRecord::finder()->findByPk('admin');
$user->email = 'root@example.com';
$user->save(); // UPDATE users SET ... WHERE username = 'admin'
```

`save()` returns `true` on success. A column declared `NOT NULL` without a default must have a value, or `save()` throws `TActiveRecordException`.

---

## 7. Delete

```php
$user = UserRecord::finder()->findByPk('admin');
$user->delete();                                  // DELETE ... WHERE username = 'admin'

UserRecord::finder()->deleteByPk('guest');        // by primary key, without loading
UserRecord::finder()->deleteAll('email IS NULL'); // by condition, returns the row count
```

A deleted record object cannot be saved again. `save()` on it throws `TActiveRecordException`.

---

## Next Steps

- [Record Classes](records.md): `table()`, composite keys, column mapping, `toArray()`, serialization
- [Finders](finders.md): parameter forms, `TActiveRecordCriteria`, dynamic finders
- [Relations](relations.md): `$RELATIONS` and `with<Relation>()`
- [Scaffold](scaffold.md): `<com:TScaffoldView RecordClass="UserRecord" />`
- [Command Line](cli.md): generate record classes from existing tables

---

[prado-activerecord](../README.md) -> [ActiveRecord Docs](README.md) -> Quick Start
