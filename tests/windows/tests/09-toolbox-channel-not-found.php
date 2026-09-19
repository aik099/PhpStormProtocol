<?php
/**
 * A resolved Toolbox channel directory that doesn't exist on disk (e.g., a stale or mistyped
 * settings.toolbox_update_channel_dir) must fail fast with a descriptive message, not an opaque
 * "Path not found" JScript runtime crash from an unguarded GetFolder() call.
 */
final class ToolboxChannelNotFoundTest extends TestCase
{
    public function name(): string
    {
        return 'toolbox-channel-not-found';
    }

    protected function execute(Workspace $workspace, ...$args): void
    {
        new ToolboxV1Fixture($workspace->localAppData);

        $targetFile = $workspace->touch('TestProject/example.php');

        $script = new PatchedScript($workspace, [
            'toolbox_update_channel_dir' => "'ch-does-not-exist'",
        ]);

        $actual = $script->run('phpstorm://open?file=' . $targetFile);

        $toolboxDirectory = $workspace->localAppData . '\JetBrains\Toolbox\apps\PhpStorm\ch-does-not-exist\\';
        $expected = 'Toolbox channel directory not found: ' . $toolboxDirectory . ' (check settings.toolbox_update_channel_dir)';

        $this->assertEquals($expected, $actual);
    }
}

return new ToolboxChannelNotFoundTest();
