<?php
/**
 * Only one of projects_basepath/projects_path_alias configured (not both) - must fail fast with a
 * descriptive message rather than silently skipping the rewrite and likely opening the wrong path.
 */
final class IncompleteAliasConfigTest extends TestCase
{
    public function name(): string
    {
        return 'incomplete-alias-config';
    }

    protected function execute(Workspace $workspace, ...$args): void
    {
        $ideInstall = new IdeInstallFixture($workspace, 'PhpStorm-CI-Test-IncompleteAlias', true);

        $targetFile = $workspace->touch('TestProject/example.php');

        $url = 'phpstorm://open?file=' . $targetFile;
        $expected = 'Incomplete projects_basepath/projects_path_alias configuration'
            . ' - both must be set together, or both left empty';

        $basepathOnlyScript = new PatchedScript($workspace, $ideInstall->settings() + [
            'projects_basepath' => "'/var/www/project'",
        ]);
        $this->assertEquals(
            $expected,
            $basepathOnlyScript->run($url),
            'only projects_basepath set'
        );

        $aliasOnlyScript = new PatchedScript($workspace, $ideInstall->settings() + [
            'projects_path_alias' => "'" . $workspace->driveLetter . "'",
        ]);
        $this->assertEquals(
            $expected,
            $aliasOnlyScript->run($url),
            'only projects_path_alias set'
        );
    }
}

return new IncompleteAliasConfigTest();
