<?php

require_once(__DIR__ . '/../../vendor/autoload.php');
require_once(__DIR__ . '/../../vendor/pradosoft/prado/framework/Prado.php');

$application = new \Prado\Test\Unit\Harness\TTestApplication();

$classMap = json_decode(file_get_contents(__DIR__ . '/../../config/classes.json'), true);

// Prado::using() includes a class file from the framework directory when the class is not
// declared yet. While the vendored framework still ships its own copy of these classes, a
// Prado::createComponent('Prado\Data\ActiveRecord\...') call made before the Composer
// autoloader has seen the class would define the framework copy instead of this package's.
// Declaring every package class first makes each later lookup find the package copy.
foreach ($classMap as $class) {
	class_exists($class) || interface_exists($class);
}

// Prado applies extra.prado class maps and message files only for installed packages.
// The extension under test is the root package, so they are registered here.
if (method_exists(\Prado\Prado::class, 'registerClassMap')) {
	\Prado\Prado::registerClassMap($classMap);
}
\Prado\Exceptions\TException::addMessageFile(__DIR__ . '/../../src/Data/ActiveRecord/Exceptions/messages.txt');
