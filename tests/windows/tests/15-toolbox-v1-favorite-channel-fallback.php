<?php
/**
 * toolbox_v1_getFavoriteChannel() must fall back to 'ch-0' whenever it can't determine a real
 * favorite channel, whether that's because ".settings.json" is missing entirely (e.g., deleted, or
 * an older Toolbox release that never wrote one - caught by its own try/catch) or because
 * ".settings.json" exists but its favorite-channel ordering has no PhpStorm entry at all (e.g.,
 * only other JetBrains IDEs are listed - its for-loop falls through without matching).
 */
final class ToolboxV1FavoriteChannelFallbackTest extends TestCase
{
    public function name(): string
    {
        return 'toolbox-v1-favorite-channel-fallback';
    }

    public static function dataProvider(): iterable
    {
        yield 'missing-settings-json' => [null];
        yield 'no-phpstorm-entry' => [
            json_encode([
                'ordering' => [
                    'local' => [
                        ['application_id' => 'PyCharm', 'channel_id' => 'ch-3'],
                    ],
                ],
            ]),
        ];
    }

    protected function execute(Workspace $workspace, ...$args): void
    {
        [$settingsJsonContent] = $args;
        $toolbox = new ToolboxV1Fixture($workspace->localAppData, 'ch-0');

        $settingsJsonPath = $workspace->localAppData . '/JetBrains/Toolbox/.settings.json';

        if ($settingsJsonContent === null) {
            FileSystem::unlink($settingsJsonPath);
        } else {
            FileSystem::filePutContents($settingsJsonPath, $settingsJsonContent);
        }

        $targetFile = $workspace->touch('no-project/example.php');

        $script = new PatchedScript($workspace);
        $url = 'phpstorm://open?file=' . $targetFile;
        $actual = $script->run($url);

        $expected = self::buildExpectedCommand($toolbox->executablePath, null, null, $targetFile);

        $this->assertEquals($expected, $actual);
    }
}

return new ToolboxV1FavoriteChannelFallbackTest();
