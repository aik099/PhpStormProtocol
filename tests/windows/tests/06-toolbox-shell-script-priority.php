<?php
/**
 * scripts\PhpStorm.cmd must win over the legacy v1 folder-scan - both toolbox_v1_configureSettings()
 * and getPhpStormCommandPath() check for it first. A real v1 install exists here too, to prove
 * priority rather than mere presence.
 */
final class ToolboxShellScriptPriorityTest extends TestCase
{
    public function name(): string
    {
        return 'toolbox-shell-script-priority';
    }

    protected function execute(Workspace $workspace, ...$args): void
    {
        new ToolboxV1Fixture($workspace->localAppData);

        $shellScript = $workspace->localAppData . '/JetBrains/Toolbox/scripts/PhpStorm.cmd';
        FileSystem::mkdir(dirname($shellScript));
        FileSystem::touch($shellScript);

        $targetFile = $workspace->touch('TestProject/example.php');

        $script = new PatchedScript($workspace);

        $expected = self::buildExpectedCommand($shellScript, null, 7, $targetFile);

        $url = 'phpstorm://open?file=' . $targetFile . '&line=7';
        $this->assertEquals($expected, $script->run($url));
    }
}

return new ToolboxShellScriptPriorityTest();
