<?php
/**
 * projects_basepath/projects_path_alias rewriting (e.g. a Unix-style network-share path remapped
 * to a Windows drive letter). FolderExists/FileExists check the *rewritten* path for real, so the
 * alias target has to actually resolve on disk - here, that's simply the test's own Workspace
 * drive, with the project files living at its root (rather than a subfolder) since the alias
 * rewrite replaces the whole projects_basepath prefix, not a segment within it.
 */
final class ProjectsPathAliasTest extends TestCase
{
    public function name(): string
    {
        return 'projects-path-alias';
    }

    protected function execute(Workspace $workspace, ...$args): void
    {
        FileSystem::mkdir($workspace->child('.idea'));
        $targetFile = $workspace->touch('src/example.php');

        $ideInstall = new IdeInstallFixture($workspace, 'PhpStorm-CI-Test-Alias', true);

        $script = new PatchedScript($workspace, $ideInstall->settings() + [
            'projects_basepath' => "'/var/www/project'",
            'projects_path_alias' => "'" . $workspace->driveLetter . "'",
        ]);

        $expected = self::buildExpectedCommand($ideInstall->executablePath, $workspace->driveLetter, 3, $targetFile);

        $url = 'phpstorm://open?file=/var/www/project/src/example.php&line=3';
        $this->assertEquals($expected, $script->run($url));
    }
}

return new ProjectsPathAliasTest();
