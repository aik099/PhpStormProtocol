<?php
/**
 * state.json exists, but toolbox_update_channel_dir matches none of its entries - the tools[]
 * loop falls through, and the disk_letter/folder_name default actually affects the output despite
 * Toolbox being installed (normally discarded once a match is found).
 */
final class ToolboxStateJsonFallbackTest extends TestCase
{
    public function name(): string
    {
        return 'toolbox-state-json-fallback';
    }

    protected function execute(Workspace $workspace, ...$args): void
    {
        new ToolboxStateJsonFixture($workspace);

        $ideInstall = new IdeInstallFixture($workspace, 'PhpStorm-CI-Test-StateJsonFallback', true);

        $targetFile = $workspace->touch('TestProject/example.php');

        $script = new PatchedScript($workspace, $ideInstall->settings() + [
            'toolbox_update_channel_dir' => "'no-such-channel'",
        ]);

        $expected = self::buildExpectedCommand($ideInstall->executablePath, null, null, $targetFile);

        $url = 'phpstorm://open?file=' . $targetFile;
        $this->assertEquals($expected, $script->run($url));
    }
}

return new ToolboxStateJsonFallbackTest();
