<?php
/**
 * The projects_basepath/projects_path_alias are both configured, but the given URL's path doesn't
 * actually start with projects_basepath - the regex replace is a no-op, so the path passes through
 * unrewritten and (being Unix-style) can't resolve on Windows, surfacing as the ordinary
 * "Path not found" guard rather than any alias-specific message.
 */
final class AliasBasepathMismatchTest extends TestCase
{
    public function name(): string
    {
        return 'alias-basepath-mismatch';
    }

    protected function execute(Workspace $workspace, ...$args): void
    {
        $ideInstall = new IdeInstallFixture($workspace, 'PhpStorm-CI-Test-AliasMismatch', true);

        $script = new PatchedScript($workspace, $ideInstall->settings() + [
            'projects_basepath' => "'/var/www/project'",
            'projects_path_alias' => "'" . $workspace->driveLetter . "'",
        ]);

        // Doesn't start with /var/www/project - the rewrite regex simply doesn't match.
        $unmatchedPath = '/home/other/example.php';
        $url = 'phpstorm://open?file=' . $unmatchedPath;
        $actual = $script->run($url);

        $expected = 'Path not found: ' . self::normalizeSlashes($unmatchedPath)
            . ' - check the URL and any projects_basepath/projects_path_alias settings';

        $this->assertEquals($expected, $actual);
    }
}

return new AliasBasepathMismatchTest();
