<?php
/**
 * No Toolbox installed, standalone direct install - exercises the disk_letter/folder_name
 * fallback in getPhpStormCommandPath() for both x64 ("Program Files" + phpstorm64.exe) and x86
 * ("Program Files (x86)" + phpstorm.exe), through the full URL-format matrix.
 */
final class StandaloneTest extends TestCase
{
    public function name(): string
    {
        return 'standalone';
    }

    public static function dataProvider(): iterable
    {
        yield 'x64' => [true];
        yield 'x86' => [false];
    }

    protected function execute(Workspace $workspace, ...$args): void
    {
        $ideInstall = new IdeInstallFixture($workspace, 'PhpStorm-CI-Test-Standalone', $args[0]);

        $projectDir = $workspace->child('TestProject');
        FileSystem::mkdir($projectDir . '/.idea');
        $targetFile = $workspace->touch('TestProject/src/example.php');

        $script = new PatchedScript($workspace, $ideInstall->settings());

        $this->assertUrlMatrix($script, $ideInstall->executablePath, $projectDir, $targetFile);
    }
}

return new StandaloneTest();
