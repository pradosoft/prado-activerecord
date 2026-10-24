[prado-activerecord](../README.md) -> [ActiveRecord Docs](README.md) -> Scaffold

# Scaffold

The Scaffold controls build list, search, and edit views from a record class and its table metadata. They are template controls in `Prado\Data\ActiveRecord\Scaffold`; the short names (`<com:TScaffoldView>`) resolve through the package class map. The controls cover prototyping and administration pages. For a page with its own layout and validation, write the templates with `TDataGrid`, `TRepeater`, and the finder methods.

```xml
<com:TScaffoldView RecordClass="UserRecord" />
```

That one tag lists the rows of `UserRecord` with paging and sorting, a search box, an "Add new record" button, and per-row Edit and Delete buttons.

---

## Requirements

- A `TActiveRecordConfig` module, or a record class that overrides `getDbConnection()`.
- A table with a primary key. Edit and Delete work through `findByPk()` and `deleteByPk()`.
- A database with an input builder (see [Input Builders](#input-builders)) when `TScaffoldEditView` or `TScaffoldView` is used. `TScaffoldListView` and `TScaffoldSearch` work on every database Active Record supports.

---

## Common Properties

Every scaffold control extends `TScaffoldBase`.

| Property | Type | Default | Description |
|---|---|---|---|
| `RecordClass` | string | | Record class name. Required. |
| `EnableDefaultStyle` | bool | `true` | Publish and register the bundled stylesheet during `onPreRender()`. |
| `DefaultStyle` | string | `style` | Base name of the stylesheet file in the package's `Scaffold` directory. |

Missing `RecordClass` → `TConfigurationException` `scaffold_invalid_record_class`.

---

## TScaffoldView

**Class:** `Prado\Data\ActiveRecord\Scaffold\TScaffoldView`

A composite of the three other controls. Its template holds a `TScaffoldSearch`, a `TScaffoldListView`, an "Add new record" `TButton`, and a hidden `TScaffoldEditView`. `onPreRender()` copies `RecordClass` and `EnableDefaultStyle` to the three children.

| Property | Returns |
|---|---|
| `ListView` | the `TScaffoldListView` |
| `EditView` | the `TScaffoldEditView` |
| `SearchControl` | the `TScaffoldSearch` |
| `AddButton` | the `TButton` with `CommandName="new"` |

Commands that bubble up to the view:

| Command | Source | Result |
|---|---|---|
| `edit` | a row's Edit button | list hidden, edit view shown with that row, Cancel button visible |
| `new` | the Add button | edit view shown with an empty record |
| any other (`save` after a successful save, `cancel`, `search`) | | list shown, edit view hidden |

Subproperties reach the children from the template:

```xml
<com:TScaffoldView RecordClass="UserRecord">
    <prop:ListView.List.ItemTemplate>
        <%# $this->Data->username %>
        <com:TLinkButton Text="Edit" CommandName="edit" />
    </prop:ListView.List.ItemTemplate>
</com:TScaffoldView>
```

---

## TScaffoldListView

**Class:** `Prado\Data\ActiveRecord\Scaffold\TScaffoldListView`

Lists records with a sort drop-down, a header row, and a pager.

| Property | Type | Description |
|---|---|---|
| `SearchCondition` | string | `Condition` of the `TActiveRecordCriteria` used for the count and the page query. |
| `SearchParameters` | array | `Parameters` of that criteria. |
| `EditViewID` | string | ID of a `TScaffoldEditView` under the same parent. When set, each row shows an Edit button. |
| `List` | `TRepeater` | One item per record. `PageSize` defaults to 10. |
| `Header` | `TRepeater` | Column names, with underscores replaced by spaces and words capitalized. |
| `Sort` | `TDropDownList` | `<column> ASC` and `<column> DESC` for every column; posts back on change. |
| `Pager` | `TPager` | Numeric pager for `List`, 10 page buttons. |

`onPreRender()` runs `count()` with the search criteria, sets `VirtualItemCount`, then runs `findAll()` with `Limit`, `Offset`, and `OrdersBy` from the pager and sort state.

Commands the list handles:

| Command | Result |
|---|---|
| `delete` | `deleteByPk()` with the row's primary key values, which are stored in the item's `CustomData`. The default button asks for confirmation in the browser. |
| `edit` | `EditViewID` set → `RecordPk` of the edit view is set and its form is initialized. The command then bubbles on. |

The default item template renders every column through `htmlspecialchars()` in a `<span class="field field_<index>">`. Replace it with `List.ItemTemplate` or `List.AlternatingItemTemplate`; `$this->Data` is the record.

```xml
<com:TScaffoldEditView ID="editView" RecordClass="UserRecord" />
<com:TScaffoldListView RecordClass="UserRecord" EditViewID="editView"
    SearchCondition="active = ?" SearchParameters="1">
    <prop:List.ItemTemplate>
        <strong><%# $this->Data->username %></strong> <%# $this->Data->email %>
        <com:TButton Text="Edit" CommandName="edit" CausesValidation="false" />
        <com:TButton Text="Delete" CommandName="delete" CausesValidation="false" />
    </prop:List.ItemTemplate>
</com:TScaffoldListView>
```

`EditViewID` that matches no control → `TConfigurationException` `scaffold_unable_to_find_edit_view`.

---

## TScaffoldEditView

**Class:** `Prado\Data\ActiveRecord\Scaffold\TScaffoldEditView`

Edits one record. With `RecordPk` set, the record is loaded with `findByPk()`; without it, a new record is created with `Prado::createComponent(RecordClass)`.

| Property | Type | Description |
|---|---|---|
| `RecordPk` | array or scalar | Primary key of the record to edit. `null` for a new record. Setting it discards the current record object. |
| `EditRenderer` | string | Class name of a custom renderer. Empty (default) uses the generated inputs. |
| `ValidationGroup` | string | `group_` followed by the control's `UniqueID`. Every generated validator and the Save button use it. |
| `SaveButton`, `ClearButton`, `CancelButton` | `TButton` | The three buttons. Cancel is hidden until `TScaffoldView` shows the edit view. |

```xml
<com:TScaffoldEditView RecordClass="UserRecord" RecordPk="admin" />
```

Commands:

| Command | Result |
|---|---|
| `save` | `Page->IsValid` is `true` → every input value is written into the record and `save()` is called; the command then bubbles on. `false` → nothing is saved and the command stops. |
| `clear` | `RecordPk` is set to `null` and an empty form is built. |
| `cancel` | Bubbles on. `TScaffoldView` answers it by showing the list. |

A `RecordPk` that matches no row → `TConfigurationException` `scaffold_invalid_record_pk`.

### Generated inputs

For each column the view creates a label, an input, and validators. The input builder for the connection's driver chooses the control from the column type.

| Condition | Result |
|---|---|
| Column has a sequence | input disabled, value not written back |
| Editing an existing record and the column is a primary key | input disabled, value not written back |
| Column is `NOT NULL` without a sequence | `TRequiredFieldValidator` (not for check boxes or time lists) |
| Integer type | `TDataTypeValidator` for integers |
| Decimal type | `TDataTypeValidator` for floats; `TRangeValidator` when the column reports a maximum |
| Column has a default value and the record value is `null` | input shows the default |

Each item carries the CSS classes `edit-item`, `input_<index>`, and `property_<column>`, so a column can be hidden or restyled from a stylesheet.

### Custom edit renderers

`EditRenderer` names a template control that implements `IScaffoldEditRenderer`:

```php
namespace Prado\Data\ActiveRecord\Scaffold;

interface IScaffoldEditRenderer extends \Prado\IDataRenderer
{
    public function updateRecord($record);
}
```

| Method | Called |
|---|---|
| `setData($record)` | when the form is initialized, with the record to edit |
| `getData()` | by the renderer's own template |
| `updateRecord($record)` | before `save()`, and after a failed validation to keep the entered values |

The renderer replaces the generated input repeater at the same position in the edit view. Validators in the renderer template set `ValidationGroup` to the edit view's `ValidationGroup`; the edit view is the renderer's `Parent`.

```php
class UserEditor extends TTemplateControl implements IScaffoldEditRenderer
{
    private $_record;

    public function getData()
    {
        return $this->_record;
    }

    public function setData($value)
    {
        $this->_record = $value;
        $this->username->Text = $value->username;
        $this->email->Text = $value->email;
    }

    public function updateRecord($record)
    {
        $record->username = $this->username->Text;
        $record->email = $this->email->Text;
    }
}
```

```xml
<!-- UserEditor.tpl -->
<com:TTextBox ID="username" />
<com:TRequiredFieldValidator ControlToValidate="username"
    ValidationGroup=<%= $this->Parent->ValidationGroup %> ErrorMessage="Required" />
<com:TTextBox ID="email" />
```

```xml
<com:TScaffoldEditView RecordClass="UserRecord" EditRenderer="Application.controls.UserEditor" />
```

A renderer class that does not implement the interface → `TConfigurationException` `scaffold_invalid_edit_renderer`.

---

## TScaffoldSearch

**Class:** `Prado\Data\ActiveRecord\Scaffold\TScaffoldSearch`

A text box and a Search button that set the `SearchCondition` of a `TScaffoldListView`.

| Property | Type | Description |
|---|---|---|
| `ListViewID` | string | ID of the `TScaffoldListView` under the same parent. |
| `SearchableFields` | string | Comma-separated column names to search. Empty (default) searches every column. |
| `SearchText` | `TTextBox` | The text box. |
| `SearchButton` | `TButton` | The button, `CommandName="search"`. |

The condition comes from the command builder's `getSearchExpression()`: the text is split on whitespace, each word must match each searched column with `LIKE '%word%'`, and the columns are combined with `OR`. An empty text box clears the condition.

```xml
<com:TScaffoldSearch ListViewID="list" SearchableFields="username, email" />
<com:TScaffoldListView ID="list" RecordClass="UserRecord" />
```

`ListViewID` that matches no control → `TConfigurationException` `scaffold_unable_to_find_list_view`.

---

## Input Builders

**Namespace:** `Prado\Data\ActiveRecord\Scaffold\InputBuilder`

`TScaffoldInputBase::createInputBuilder($record)` opens the record's connection, reads the PDO driver name, and returns the builder for it.

| Driver name | Builder |
|---|---|
| `mysql`, `mysqli` | `TMysqlScaffoldInput` |
| `sqlite`, `sqlite2` | `TSqliteScaffoldInput` |
| `pgsql` | `TPgsqlScaffoldInput` |
| `mssql` | `TMssqlScaffoldInput` |
| `ibm` | `TIbmScaffoldInput` |
| `firebird`, `interbase` | `TFirebirdScaffoldInput` |

The `sqlsrv`, `dblib`, and `oci` drivers have no builder. Active Record works on them; the edit view raises `TConfigurationException` `ar_invalid_database_driver` unless a handler supplies a builder (see below).

Every builder extends `TScaffoldInputCommon`, which creates the controls. `TMysqlScaffoldInput` maps column types as follows; the other builders map their database's type names to the same controls.

| MySQL column type | Control |
|---|---|
| `tinyint(1)` | `TCheckBox` |
| `int`, `integer`, `tinyint`, `smallint`, `mediumint`, `bigint` | `TTextBox` with an integer validator |
| `decimal`, `double`, `float` | `TTextBox` with a float validator |
| `date` | `TDatePicker` (drop-down lists, `yyyy-MM-dd`) |
| `time` | three `TDropDownList` controls for hour, minute, second |
| `datetime`, `timestamp` | `TDatePicker` plus the three time lists |
| `year` | `TDropDownList`, current year ±10 |
| `text`, `tinytext`, `mediumtext`, `longtext`, `blob` variants | multi-line `TTextBox` |
| `enum` | `TRadioButtonList` of the declared values |
| `set` | `TCheckBoxList` of the declared values |
| anything else | `TTextBox`, `MaxLength` from the column size |

The other builders compare the column type name reported by their database:

| Builder | Check box | Integer | Decimal | Date | Time | Date and time | Multi-line |
|---|---|---|---|---|---|---|---|
| `TSqliteScaffoldInput` | `boolean` | `int`, `integer`, `tinyint`, `smallint`, `mediumint`, `bigint` | `decimal`, `double`, `float` | `date` | `time` | `datetime`, `timestamp` | `text` and `blob` variants; `year` gives the year list |
| `TPgsqlScaffoldInput` | `boolean` | `smallint`, `integer`, `bigint` | `decimal`, `numeric`, `real`, `double precision` | `date` | `time without time zone` | `timestamp without time zone` | `text` |
| `TMssqlScaffoldInput` | `bit` | `smallint`, `int`, `bigint`, `tinyint` | `decimal`, `float`, `money`, `numeric`, `real`, `smallmoney` | | | `datetime`, `smalldatetime` | `text` |
| `TIbmScaffoldInput` | | `smallint`, `integer`, `bigint` | `decimal`, `numeric`, `real`, `float`, `double` | `date` | `time` | `timestamp` | `char`, `varchar` |
| `TFirebirdScaffoldInput` | `BOOLEAN` | `SMALLINT`, `INTEGER`, `BIGINT` | `FLOAT`, `DOUBLE PRECISION`, `DECIMAL`, `NUMERIC`, `DECFLOAT(16)`, `DECFLOAT(34)` | `DATE` | `TIME`, `TIME WITH TIME ZONE` | `TIMESTAMP`, `TIMESTAMP WITH TIME ZONE` | `CHAR`, `VARCHAR`, `TEXT`, `BLOB` |

Any other type gives the plain `TTextBox`. `TMssqlScaffoldInput` disables the text box for excluded columns and stores an empty text box as `NULL` when the column allows it.

The primary input of each item has the ID `scaffold_input` (`IScaffoldInput::DEFAULT_ID`) and the label's `ForControl` points at it.

### Supplying a builder for another driver

When no built-in builder matches, `createInputBuilder()` raises the global event `fxActiveRecordScaffoldInputClass` on the connection. The sender is the connection and the parameter is the driver name. A handler returns the fully qualified name of a class that implements `IScaffoldInput`. The first non-null return value is instantiated. A returned class that does not implement the interface → `TConfigurationException` `ar_not_input_base`.

```php
use Prado\Data\ActiveRecord\Scaffold\InputBuilder\TScaffoldInputCommon;

class TOdbcScaffoldInput extends TScaffoldInputCommon
{
    protected function createControl($container, $column, $record)
    {
        switch (strtolower($column->getDbType())) {
            case 'bit':
                return $this->createBooleanControl($container, $column, $record);
            case 'integer':
                return $this->createIntegerControl($container, $column, $record);
            default:
                return $this->createDefaultControl($container, $column, $record);
        }
    }

    protected function getControlValue($container, $column, $record)
    {
        return $this->getDefaultControlValue($container, $column, $record);
    }
}
```

A global event reaches every `TComponent` that listens for it. Register the handler on a listening component, or directly on the connection:

```php
// Option 1: a component that listens to global events
class ScaffoldInputProvider extends \Prado\TComponent
{
    public function fxActiveRecordScaffoldInputClass($sender, $driver)
    {
        return $driver === 'odbc' ? TOdbcScaffoldInput::class : null;
    }
}
$provider = new ScaffoldInputProvider();
$provider->listen();

// Option 2: a handler on the connection the records use
$conn->attachEventHandler('fxActiveRecordScaffoldInputClass', function ($sender, $driver) {
    return $driver === 'odbc' ? TOdbcScaffoldInput::class : null;
});
```

`IScaffoldInput` declares `createScaffoldInput($parent, $item, $column, $record)` and `loadScaffoldInput($parent, $item, $column, $record)`. `TScaffoldInputBase` implements both and calls the protected `createControl()` and `getControlValue()`, which a builder overrides.

---

## Stylesheet

Each scaffold control publishes `style.css` from the package's `Scaffold` directory through `publishAsset()` during `onPreRender()` and registers it with the page's client script manager. The file styles the list rows, header, pager, edit items, buttons, and validator states.

| Selector | Element |
|---|---|
| `.scaffold_view`, `.scaffold_list_view`, `.scaffold_edit_view`, `.scaffold_search` | the four controls |
| `.item-header`, `.item`, `.item_0`, `.item_1` | header row, data rows, alternating rows |
| `.field`, `.field_<index>` | one column value in a row |
| `.pager` | the pager |
| `.edit-item`, `.item-label`, `.item-input`, `.scaffold_input`, `.multiline-textbox` | edit form items |
| `.required`, `.required-input`, `.required-input2`, `.required-input3` | validator message and the input it marks |
| `.edit-button`, `.delete-button`, `.new-button`, `.edit-page-buttons` | buttons |

Colors follow the `color-scheme` the application declares. Each color is written twice: a plain light value, then a `light-dark()` pair that browsers with `light-dark()` support use for the dark scheme. An application that declares no color scheme renders the light values.

To use another stylesheet, set `EnableDefaultStyle="false"` and register your own. `DefaultStyle` selects a different file name inside the package directory and is not a path to an application file.

```xml
<com:TScaffoldView RecordClass="UserRecord" EnableDefaultStyle="false" />
```

---

[prado-activerecord](../README.md) -> [ActiveRecord Docs](README.md) -> Scaffold

## See Also

- [Record Classes](records.md): the record class a scaffold control renders
- [Finders](finders.md): `SearchCondition` and `SearchParameters` are a `TActiveRecordCriteria`
- [Prado Integration](integration.md): the module and connection the controls use
