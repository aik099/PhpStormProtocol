<?php
/**
 * Current-generation Toolbox (2.0+/3.x) state.json, with two installed channels - exercises
 * whether settings.toolbox_update_channel_dir is actually honored when picking among them,
 * through the full URL-format matrix. No scripts\PhpStorm.cmd and no apps\PhpStorm folder, so
 * this isolates the state.json branch from the shell-script and legacy-v1 branches, which both
 * take priority over it.
 */
final class ToolboxStateJsonTest extends TestCase
{
    public function name(): string
    {
        return 'toolbox-state-json';
    }

    protected function execute(Workspace $workspace, ...$args): void
    {
        $fixture = new ToolboxStateJsonFixture($workspace);

        $projectDir = $workspace->child('TestProject');
        FileSystem::mkdir($projectDir . '/.idea');
        $targetFile = $workspace->touch('TestProject/src/example.php');

        // Pin to the second channel via a uuid substring - if toolbox_update_channel_dir were
        // ignored, the first (wrong) entry's launchCommand would come back instead.
        $script = new PatchedScript($workspace, [
            'toolbox_update_channel_dir' => "'" . ToolboxStateJsonFixture::SECOND_CHANNEL_UUID . "'",
        ]);

        $this->assertUrlMatrix($script, $fixture->secondLaunchCommand, $projectDir, $targetFile);
    }
}

return new ToolboxStateJsonTest();
