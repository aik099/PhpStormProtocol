<?php
/**
 * The "+" in the URL decodes to a literal space - a real file with a space in its name proves the
 * round trip (decodeURIComponent leaves "+" untouched; a separate .replace(/\+/g, ' ') handles it).
 */
final class PlusEncodedSpaceTest extends TestCase
{
    public function name(): string
    {
        return 'plus-encoded-space';
    }

    protected function execute(Workspace $workspace, ...$args): void
    {
        $ideInstall = new IdeInstallFixture($workspace, 'PhpStorm-CI-Test-PlusSpace', true);
        $script = new PatchedScript($workspace, $ideInstall->settings());

        $spacedFile = $workspace->touch('no-project/file with space.php');
        $spacedUrl = 'phpstorm://open?file=' . $workspace->child('no-project/file+with+space.php');
        $expected = self::buildExpectedCommand($ideInstall->executablePath, null, null, $spacedFile);

        $this->assertEquals($expected, $script->run($spacedUrl));
    }
}

return new PlusEncodedSpaceTest();
