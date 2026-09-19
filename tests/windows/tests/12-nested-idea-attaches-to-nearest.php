<?php
/**
 * A stray ".idea" folder in an intermediate ancestor (e.g., a vendored dependency) wins over
 * the real outer project root because the upward walk stops at the first (nearest) match rather
 * than continuing to the outermost one.
 *
 * Documents this as the actual (nearest-wins) behavior, not an asserted "should be outer" requirement.
 */
final class NestedIdeaAttachesToNearestTest extends TestCase
{
    public function name(): string
    {
        return 'nested-idea-attaches-to-nearest';
    }

    protected function execute(Workspace $workspace, ...$args): void
    {
        $ideInstall = new IdeInstallFixture($workspace, 'PhpStorm-CI-Test-NestedIdea', true);

        FileSystem::mkdir($workspace->child('Outer/.idea'));

        $innerDir = $workspace->child('Outer/vendor/some-lib');
        FileSystem::mkdir($innerDir . '/.idea');
        $targetFile = $workspace->touch('Outer/vendor/some-lib/src/File.php');

        $script = new PatchedScript($workspace, $ideInstall->settings());
        $url = 'phpstorm://open?file=' . $targetFile;
        $actual = $script->run($url);

        // The inner, stray .idea wins - not $workspace->child('Outer').
        $expected = self::buildExpectedCommand($ideInstall->executablePath, $innerDir, null, $targetFile);

        $this->assertEquals($expected, $actual);
    }
}

return new NestedIdeaAttachesToNearestTest();
