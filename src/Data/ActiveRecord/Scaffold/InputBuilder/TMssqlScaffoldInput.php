<?php

/**
 * TMssqlScaffoldInput class file.
 *
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\ActiveRecord\Scaffold\InputBuilder;

/**
 * TMssqlScaffoldInput class.
 *
 * The SQL Server scaffold input under its former name. {@see TScaffoldInputBase::createInputBuilder()}
 * returns it for the SQL Server drivers so that existing `instanceof` checks hold.
 *
 * @link https://github.com/pradosoft/prado
 * @deprecated since 1.1.0 of prado-activerecord, use {@see TSqlSrvScaffoldInput}.
 */
class TMssqlScaffoldInput extends TSqlSrvScaffoldInput
{
}
