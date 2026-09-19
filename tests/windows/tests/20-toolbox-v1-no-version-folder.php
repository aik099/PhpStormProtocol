<?php
/**
 * A Toolbox v1 channel folder containing only a "plugins" subfolder (no real version folder at
 * all) must fail fast with a descriptive message, not crash reading "<channel>\product-info.json"
 * (nonexistent, auto-created empty by OpenTextFile's create=true, then "Input past end of file" on
 * ReadAll()).
 */
final class ToolboxV1NoVersionFolderTest extends TestCase
{
    public function name(): string
    {
        return 'toolbox-v1-no-version-folder';
    }

    protected function execute(Workspace $workspace, ...$args): void
    {
        FileSystem::mkdir($workspace->localAppData . '/JetBrains/Toolbox/apps/PhpStorm/ch-0/plugins');

        $targetFile = $workspace->touch('no-project/example.php');

        $script = new PatchedScript($workspace);
        $url = 'phpstorm://open?file=' . $targetFile;
        $actual = $script->run($url);

        $toolboxDirectory = $workspace->localAppData . '\JetBrains\Toolbox\apps\PhpStorm\ch-0\\';
        $expected = 'No PhpStorm version folder found under ' . $toolboxDirectory
            . ' - check settings.toolbox_update_channel_dir';

        $this->assertEquals($expected, $actual);
    }
}

return new ToolboxV1NoVersionFolderTest();
