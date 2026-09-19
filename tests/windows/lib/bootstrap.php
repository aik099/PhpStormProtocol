<?php

define('WIN_APP_DIR', realpath(__DIR__ . '/../../../PhpStorm Protocol (Win)'));

// Listed explicitly (not derived from class name) so a typo fails as "class not found".
$knownClasses = [
    'FileSystem' => 'FileSystem.php',
    'Disposable' => 'Disposable.php',
    'AssertionFailedException' => 'AssertionFailedException.php',
    'Workspace' => 'Workspace.php',
    'SubstDrive' => 'SubstDrive.php',
    'PatchedScript' => 'PatchedScript.php',
    'TestCase' => 'TestCase.php',
    'TestRunner' => 'TestRunner.php',
    'IdeInstallFixture' => '../tests/fixtures/IdeInstallFixture.php',
    'ToolboxV1Fixture' => '../tests/fixtures/ToolboxV1Fixture.php',
    'ToolboxStateJsonFixture' => '../tests/fixtures/ToolboxStateJsonFixture.php',
];

spl_autoload_register(static function (string $class) use ($knownClasses): void {
    if (isset($knownClasses[$class])) {
        require __DIR__ . '/' . $knownClasses[$class];
    }
});
