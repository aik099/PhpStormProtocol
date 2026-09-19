<?php
/**
 * The resolved target path (a file or a folder - PhpStorm doesn't care which) doesn't exist on
 * disk (wrong alias mapping, a moved/deleted path) - must fail fast with a descriptive message.
 * Uses a &line= URL specifically: that branch appends --line N "%file%", so it must also check
 * isFile/isFolder first rather than assume the path is valid.
 */
final class TargetPathNotFoundTest extends TestCase
{
    public function name(): string
    {
        return 'target-path-not-found';
    }

    protected function execute(Workspace $workspace, ...$args): void
    {
        $ideInstall = new IdeInstallFixture($workspace, 'PhpStorm-CI-Test-TargetMissing', true);

        $missingPath = $workspace->child('does-not-exist.php');

        $script = new PatchedScript($workspace, $ideInstall->settings());

        $expected = 'Path not found: ' . self::normalizeSlashes($missingPath) . ' - check the URL and any projects_basepath/projects_path_alias settings';

        $url = 'phpstorm://open?file=' . $missingPath . '&line=5';
        $this->assertEquals($expected, $script->run($url));
    }
}

return new TargetPathNotFoundTest();
