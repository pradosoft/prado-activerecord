<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\ActiveRecordDbTestCase;
use Prado\Test\Unit\Data\ActiveRecord\Records\Blogs;

/**
 * Insert into a table with an auto-increment primary key and read the key back.
 */
abstract class BlogRecordTestCase extends ActiveRecordDbTestCase
{
	public function test_find_first_blog()
	{
		$blog = Blogs::finder()->findByPk(1);
		$this->assertNotNull($blog);
	}

	public function test_insert_new_blog()
	{
		$this->requireFeature('identity', 'The driver metadata reports no auto-increment column, so the gateway cannot read the new primary key back.');
		$blog = new Blogs();
		$blog->blog_name = 'test1';
		$blog->blog_author = 'wei';
		$this->assertTrue($blog->save());
		$this->assertNotNull($blog->blog_id);

		$blog->blog_name = 'test2';
		$this->assertTrue($blog->save());

		$check = Blogs::finder()->findByPk($blog->blog_id);
		$this->assertSameBlog($check, $blog);

		$this->assertTrue($blog->delete());
	}

	public function assertSameBlog($check, $blog)
	{
		$props = ['blog_id', 'blog_name', 'blog_author'];
		foreach ($props as $prop) {
			$this->assertEquals($check->{$prop}, $blog->{$prop});
		}
	}
}
