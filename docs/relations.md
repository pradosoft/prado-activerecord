[prado-activerecord](../README.md) -> [ActiveRecord Docs](README.md) -> Relations

# Relations

A record class declares its related records in a static `$RELATIONS` array. The package reads the foreign key constraints from the table metadata to join the tables, loads related records eagerly through `with<Relation>()` or lazily on property access, and can write related records back. The database must declare foreign key constraints (MySQL InnoDB, PostgreSQL, SQLite with `REFERENCES` clauses, and so on). SQLite does not enforce its constraints, but it reports them, which is what the package needs.

---

## The `$RELATIONS` Array

Each key is the property name that receives the related record or records. Each value is an array:

| Index | Content | Required |
|---|---|---|
| 0 | Relation type: `self::HAS_MANY`, `self::HAS_ONE`, `self::BELONGS_TO`, or `self::MANY_TO_MANY` | yes |
| 1 | Related record class name (`PlayerRecord::class` or `'PlayerRecord'`) | yes |
| 2 | Foreign key column name, or the association table for `MANY_TO_MANY` | `MANY_TO_MANY`: yes; others: when the table has more than one foreign key to the related table |
| 3 | Condition string for the related query | no |
| 4 | Parameters for that condition | no |

```php
class TeamRecord extends TActiveRecord
{
    const TABLE = 'teams';
    public $name;        // primary key
    public $location;

    public static $RELATIONS = [
        'players' => [self::HAS_MANY, PlayerRecord::class, 'team_name', 'age > :age', [':age' => 16]],
    ];

    public static function finder($className = __CLASS__)
    {
        return parent::finder($className);
    }
}

class PlayerRecord extends TActiveRecord
{
    const TABLE = 'players';
    public $player_id;   // primary key
    public $age;
    public $team_name;   // foreign key -> teams.name

    public static $RELATIONS = [
        'team' => [self::BELONGS_TO, TeamRecord::class, 'team_name'],
        'skills' => [self::MANY_TO_MANY, SkillRecord::class, 'player_skills'],
        'profile' => [self::HAS_ONE, ProfileRecord::class],
    ];

    public static function finder($className = __CLASS__)
    {
        return parent::finder($className);
    }
}
```

Relation names are looked up case-insensitively. The property that receives the result keeps the spelling of the array key.

---

## Relation Types

| Type | Where the foreign key lives | Property value | Handler class |
|---|---|---|---|
| `HAS_MANY` | In the related table, pointing at this table | array of records; `[]` when none | `TActiveRecordHasMany` |
| `HAS_ONE` | In the related table, pointing at this table | one record; left unchanged when none | `TActiveRecordHasOne` |
| `BELONGS_TO` | In this table, pointing at the related table | one record, or `null` when none | `TActiveRecordBelongsTo` |
| `MANY_TO_MANY` | In an association table, pointing at both tables | array of records; `[]` when none | `TActiveRecordHasManyAssociation` |

`HAS_ONE` and `BELONGS_TO` throw `TActiveRecordException` (`ar_belongs_to_multiple_result`) when the query returns more than one related row for a record.

A one-to-many table relationship is declared from both sides: `HAS_MANY` on the parent and `BELONGS_TO` on the child.

---

## Foreign Key Detection

`TDbTableInfo::getForeignKeys()` lists each constraint as `['table' => <referenced table>, 'keys' => [<column> => <referenced column>]]`. The handler inspects one table and keeps the first constraint that references the other table:

| Type | Table inspected | Must reference |
|---|---|---|
| `HAS_MANY`, `HAS_ONE` | related record's table | this record's table |
| `BELONGS_TO` | this record's table | related record's table |
| `MANY_TO_MANY` | association table | this record's table, and separately the related record's table |

No matching constraint → `TActiveRecordException` `ar_relations_missing_fk`.

### Naming the foreign key column

When the inspected table has two or more constraints to the same table, index 2 names the column (or columns, separated by commas) of the constraint to use. The column belongs to the table that holds the constraint: the related table for `HAS_MANY` and `HAS_ONE`, this table for `BELONGS_TO`.

```php
class TicketRecord extends TActiveRecord
{
    const TABLE = 'tickets';
    public $ticket_id;
    public $owner_id;     // -> users.user_id
    public $reporter_id;  // -> users.user_id

    public static $RELATIONS = [
        'owner' => [self::BELONGS_TO, UserRecord::class, 'owner_id'],
        'reporter' => [self::BELONGS_TO, UserRecord::class, 'reporter_id'],
    ];
}
```

### Naming the association table

For `MANY_TO_MANY`, index 2 is the association table. The table needs one constraint to each side. Its columns are not part of either record class.

```sql
CREATE TABLE player_skills (
    player_id INTEGER NOT NULL REFERENCES players(player_id),
    skill_id  INTEGER NOT NULL REFERENCES skills(skill_id),
    PRIMARY KEY (player_id, skill_id)
);
```

---

## Declared and Undeclared Relation Properties

| Relation key | Read `$record->players` | Loading |
|---|---|---|
| Not a declared property | `__get()` runs the relation query and returns the result | lazy, on every read |
| Declared `public $players = [];` | PHP reads the property directly | only through `with<Relation>()` or `fetchResultsFor()` |

An undeclared relation property runs its query on each read. Assign the result to a local variable, or give the relation a getter/setter pair that caches it (see [Lazy Loading](#lazy-loading)).

---

## Eager Loading

`with<Relation>()` returns a relation handler. Call any finder method on the handler; the handler runs the finder on the record class, then loads the related records for every result with one more query and assigns them.

```php
$teams = TeamRecord::finder()->withPlayers()->findAll();
$team = TeamRecord::finder()->with_players()->findByPk('Team A');   // same relation
$players = PlayerRecord::finder()->withTeam()->withSkills()->findAll('age < ?', 30);
```

- The relation name after `with` is case-insensitive and may follow one underscore.
- Chained `with` calls each run one query after the finder returns. The example above runs three queries: players, teams, skills.
- `HAS_MANY`, `HAS_ONE`, and `BELONGS_TO` load with `(columns) IN (values)` through `findAllByIndex()`. `MANY_TO_MANY` loads with an `INNER JOIN` on the association table.
- Every result row of the main query is kept, with or without related rows. There is no outer join and no aggregate across tables.

### Criteria for the related query

`with<Relation>()` takes the same arguments as `findAll()`. They apply to the related query only.

```php
$teams = TeamRecord::finder()->withPlayers('age > ?', 30)->findAll();

$criteria = new TActiveRecordCriteria();
$criteria->OrdersBy['age'] = 'desc';
$teams = TeamRecord::finder()->withPlayers($criteria)->findAll();
```

Indexes 3 and 4 of the `$RELATIONS` entry are the condition and parameters used when the relation loads lazily. A `with<Relation>()` call replaces them with its own arguments; with no arguments it loads without a condition.

---

## Lazy Loading

Reading an undeclared relation property calls `fetchResultsFor($name)` and returns the result:

```php
$team = TeamRecord::finder()->findByPk('Team A');
$players = $team->players;   // one query, condition and parameters from the $RELATIONS entry
```

To cache the result, or to combine a relation with a declared property, declare a getter/setter pair and call the protected `fetchResultsFor()` yourself. The relation handler assigns through the setter.

```php
class TeamRecord extends TActiveRecord
{
    const TABLE = 'teams';
    public $name;
    public $location;

    private $_players;

    public static $RELATIONS = [
        'players' => [self::HAS_MANY, PlayerRecord::class],
    ];

    public function getPlayers()
    {
        if ($this->_players === null) {
            $this->fetchResultsFor('players');
        }
        return $this->_players;
    }

    public function setPlayers($value)
    {
        $this->_players = $value;
    }
}

$team = TeamRecord::finder()->findByPk('Team A');
count($team->players);   // first read runs the query
count($team->players);   // cached
```

`fetchResultsFor()` returns `false` when the name is not a relation.

---

## Self-Referencing Relations

A table that references itself uses the same two declarations, with the foreign key column named on both.

```php
class Category extends TActiveRecord
{
    const TABLE = 'categories';
    public $cat_id;
    public $category_name;
    public $parent_cat_id;   // -> categories.cat_id

    public static $RELATIONS = [
        'parent_category' => [self::BELONGS_TO, Category::class, 'parent_cat_id'],
        'child_categories' => [self::HAS_MANY, Category::class, 'parent_cat_id'],
    ];
}

$category = Category::finder()->withChild_Categories()->withParent_Category()->findByPk(2);
```

A self-referencing `MANY_TO_MANY` association table has two constraints to the same table. Append the column that points at the related side to the association table name, and declare that column as a property on the record class. The handler stores the association column value in it.

```sql
CREATE TABLE related_items (
    item_id         INTEGER NOT NULL REFERENCES items(item_id),
    related_item_id INTEGER NOT NULL REFERENCES items(item_id),
    PRIMARY KEY (item_id, related_item_id)
);
```

```php
class ItemRecord extends TActiveRecord
{
    const TABLE = 'items';
    public $item_id;
    public $name;

    public $related_items = [];
    public $related_item_id;   // column of the association table

    public static $RELATIONS = [
        'related_items' => [self::MANY_TO_MANY, ItemRecord::class, 'related_items.related_item_id'],
    ];
}
```

---

## Saving Related Records

`save()` writes the record's own row only. Related records are saved either one by one or through the relation handler's `updateAssociatedRecords()`.

### One by one

```php
$team = TeamRecord::finder()->findByPk('Team A');

$player = new PlayerRecord(['age' => 20]);
$player->team_name = $team->name;
$player->save();
```

### Through the relation handler

`$record->with<Relation>()` on a record instance returns a handler whose source is that instance. `updateAssociatedRecords()` copies the key values and saves the related records.

| Type | `updateAssociatedRecords()` |
|---|---|
| `HAS_MANY` | Sets the foreign key columns of each record in the array from this record, then calls `save()` on each. |
| `HAS_ONE` | Sets the foreign key columns of the related record from this record, then calls `save()` on it. |
| `BELONGS_TO` | Calls `save()` on the related record, then copies its key into this record's foreign key columns. This record is not saved. |
| `MANY_TO_MANY` | Calls `save()` on each related record, then inserts the association rows that do not exist yet. |

```php
$team = TeamRecord::finder()->findByPk('Team A');
$team->players = [new PlayerRecord(['age' => 20]), new PlayerRecord(['age' => 25])];
$team->withPlayers()->updateAssociatedRecords();   // sets team_name on each, saves each

$player = new PlayerRecord(['age' => 27]);
$player->team = new TeamRecord(['name' => 'Team C', 'location' => 'Sydney']);
$player->withTeam()->updateAssociatedRecords();    // saves the team, sets $player->team_name
$player->save();
```

Removing a record from a relation array does not delete its row, and setting a `BELONGS_TO` property to `null` does not clear the foreign key. Delete rows with `delete()`, `deleteByPk()`, or `deleteAll()`, or with `ON DELETE CASCADE` constraints. An association row is removed through a record class for the association table, or with a `deleteAll()` condition on it.

---

## Relation Classes

All classes live in `Prado\Data\ActiveRecord\Relations`.

| Class | Role |
|---|---|
| `TActiveRecordRelationContext` | Holds the source record, the property name, and the `$RELATIONS` entry. `getRelationHandler($criteria)` creates the handler for the entry's type. |
| `TActiveRecordRelation` | Abstract handler. `__call()` forwards finder calls to the source record and then collects related records; `fetchResultsInto($record)` loads one record's relation; `getRelationForeignKeys()` returns the matched constraint; `updateAssociatedRecords()` writes related records. |
| `TActiveRecordHasMany` | `HAS_MANY`. |
| `TActiveRecordHasOne` | `HAS_ONE`. |
| `TActiveRecordBelongsTo` | `BELONGS_TO`. |
| `TActiveRecordHasManyAssociation` | `MANY_TO_MANY`. Builds the `INNER JOIN` query and maintains the association table. |

`TActiveRecord` exposes the relation table through `getRecordRelation($name)`, `getRecordRelations()`, and `hasRecordRelation($name)`.

---

[prado-activerecord](../README.md) -> [ActiveRecord Docs](README.md) -> Relations

## See Also

- [Finders](finders.md): the finder methods that `with<Relation>()` wraps
- [Record Classes](records.md): declared properties and `__get()`
- [Events](events.md): the commands relation queries raise
