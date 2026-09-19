<?php
/**
 * Toolbox v1 with two installed channels - confirms toolbox_v1_getFavoriteChannel() (via
 * .settings.json) picks the right one when left to auto-detect, and that an explicit
 * toolbox_update_channel_dir overrides it to pick the other one instead.
 */
final class ToolboxV1TwoChannelsTest extends TestCase
{
    public function name(): string
    {
        return 'toolbox-v1-two-channels';
    }

    protected function execute(Workspace $workspace, ...$args): void
    {
        $ch0 = new ToolboxV1Fixture($workspace->localAppData, 'ch-0', '261.1.1');
        // Constructed second, so its .settings.json write (favorite=ch-1) is the one left in place.
        $ch1 = new ToolboxV1Fixture($workspace->localAppData, 'ch-1', '262.8665.325');

        $targetFile = $workspace->touch('no-project/example.php');
        $url = 'phpstorm://open?file=' . $targetFile;

        $favoriteScript = new PatchedScript($workspace);
        $favoriteExpected = self::buildExpectedCommand($ch1->executablePath, null, null, $targetFile);
        $this->assertEquals(
            $favoriteExpected,
            $favoriteScript->run($url),
            'auto-detected favorite channel (ch-1)'
        );

        $explicitScript = new PatchedScript($workspace, [
            'toolbox_update_channel_dir' => "'ch-0'",
        ]);
        $explicitExpected = self::buildExpectedCommand($ch0->executablePath, null, null, $targetFile);
        $this->assertEquals(
            $explicitExpected,
            $explicitScript->run($url),
            'explicit channel override (ch-0)'
        );
    }
}

return new ToolboxV1TwoChannelsTest();
