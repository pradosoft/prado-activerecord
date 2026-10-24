<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\ActiveRecordDbTestCase;
use Prado\Test\Unit\Data\ActiveRecord\Records\DepSections;
use Prado\Test\Unit\Data\ActiveRecord\Records\DepartmentRecord;

abstract class DeleteByPkTestCase extends ActiveRecordDbTestCase
{
	public function test_delete_by_pks()
	{
		$finder = DepartmentRecord::finder();
		$this->assertEquals($finder->deleteByPk(100), 0);
		$this->assertEquals($finder->deleteByPk(100, 101), 0);
		$this->assertEquals($finder->deleteByPk([100, 101]), 0);
	}

	public function test_delete_by_composite_pks()
	{
		$this->requireFeature('composite_pk', 'The driver metadata reports only the first column of a composite primary key.');
		$this->requireFeature('composite_pk_in', 'The driver does not accept the (a, b) IN ((1, 2)) form the gateway builds for a composite primary key.');
		$finder = DepSections::finder();
		$this->assertEquals($finder->deleteByPk([100, 101]), 0);
		$this->assertEquals($finder->deleteByPk([100, 101], [102, 103]), 0);
		$this->assertEquals($finder->deleteByPk([[100, 101], [102, 103]]), 0);
	}
}
