<?php
/**
 * Toolbox v1 with two version folders under the same channel - confirms
 * toolbox_v1_configureSettings() picks the truly newest one via toolbox_v1_compareVersion(),
 * not just "whichever happens to sort first". Deliberately reproduces the exact non-semver
 * ordering the function's own comment describes: "192.6262.66" has a larger middle segment
 * than "193.5233.101", but the newer major (193) must still win.
 */
final class ToolboxV1MultipleVersionsTest extends TestCase
{
    public function name(): string
    {
        return 'toolbox-v1-multiple-versions';
    }

    protected function execute(Workspace $workspace, ...$args): void
    {
        new ToolboxV1Fixture($workspace->localAppData, 'ch-0', '192.6262.66');
        $newer = new ToolboxV1Fixture($workspace->localAppData, 'ch-0', '193.5233.101');

        $targetFile = $workspace->touch('no-project/example.php');

        $script = new PatchedScript($workspace);
        $url = 'phpstorm://open?file=' . $targetFile;
        $actual = $script->run($url);

        $expected = self::buildExpectedCommand($newer->executablePath, null, null, $targetFile);

        $this->assertEquals($expected, $actual);
    }
}

return new ToolboxV1MultipleVersionsTest();
