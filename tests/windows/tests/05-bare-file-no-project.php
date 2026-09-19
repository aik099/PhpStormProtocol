<?php
/**
 * Bare file with no ".idea" anywhere in its ancestor path, and no "&line" in the URL - should
 * still open standalone, hitting the `else if (isFile) { editor += ' "%file%"' }` branch rather
 * than the "--line" branch exercised by the other tests.
 */
final class BareFileNoProjectTest extends TestCase
{
    public function name(): string
    {
        return 'bare-file-no-project';
    }

    protected function execute(Workspace $workspace, ...$args): void
    {
        $ideInstall = new IdeInstallFixture($workspace, 'PhpStorm-CI-Test-Bare', true);
        $script = new PatchedScript($workspace, $ideInstall->settings());

        $targetFile = $workspace->touch('no-project/example.php');

        $expected = self::buildExpectedCommand($ideInstall->executablePath, null, null, $targetFile);

        $url = 'phpstorm://open?file=' . $targetFile;
        $this->assertEquals($expected, $script->run($url));
    }
}

return new BareFileNoProjectTest();
