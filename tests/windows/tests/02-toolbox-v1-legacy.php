<?php
/**
 * Legacy Toolbox 1.x layout (apps\PhpStorm\<channel>\<version>\), no shell-script generation.
 * Exercises toolbox_v1_isInstalled() / toolbox_v1_configureSettings() / toolbox_v1_getFavoriteChannel(),
 * through the full URL-format matrix.
 */
final class ToolboxV1LegacyTest extends TestCase
{
    public function name(): string
    {
        return 'toolbox-v1-legacy';
    }

    protected function execute(Workspace $workspace, ...$args): void
    {
        $toolbox = new ToolboxV1Fixture($workspace->localAppData);

        $projectDir = $workspace->child('TestProject');
        FileSystem::mkdir($projectDir . '/.idea');
        $targetFile = $workspace->touch('TestProject/src/example.php');

        $script = new PatchedScript($workspace);

        $this->assertUrlMatrix($script, $toolbox->executablePath, $projectDir, $targetFile);
    }
}

return new ToolboxV1LegacyTest();
