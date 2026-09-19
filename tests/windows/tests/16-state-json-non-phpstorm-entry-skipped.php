<?php
/**
 * The "state.json" can list entries for other JetBrains IDEs too (e.g., PyCharm) - getPhpStormCommandPath()
 * must skip any tools[] entry whose toolId isn't 'PhpStorm' via its `continue`, not just get lucky
 * on ordering (this entry is listed first, so a broken skip would return it instead).
 */
final class StateJsonNonPhpStormEntrySkippedTest extends TestCase
{
    public function name(): string
    {
        return 'state-json-non-phpstorm-entry-skipped';
    }

    protected function execute(Workspace $workspace, ...$args): void
    {
        $toolboxRoot = $workspace->localAppData . '/JetBrains/Toolbox';
        FileSystem::mkdir($toolboxRoot);

        $installLocation = $workspace->child('Program Files/JetBrains/PhpStorm 2026.1.4');
        $launchCommand = $installLocation . '/bin/phpstorm64.exe';
        FileSystem::mkdir($installLocation . '/bin');
        FileSystem::touch($launchCommand);

        $state = [
            'tools' => [
                [
                    'channelId' => 'PyCharm-b9162270-8eff-4c15-a37e-557b7e4e8335',
                    'toolId' => 'PyCharm',
                    'installLocation' => 'C:\\Users\\CI\\AppData\\Local\\Programs\\PyCharm',
                    'launchCommand' => 'C:\\Users\\CI\\AppData\\Local\\Programs\\PyCharm\\bin\\pycharm64.exe',
                ],
                [
                    'channelId' => 'PhpStorm-18595934-5e62-4279-90ee-9200359430cb',
                    'toolId' => 'PhpStorm',
                    'installLocation' => $installLocation,
                    'launchCommand' => $launchCommand,
                ],
            ],
        ];

        FileSystem::filePutContents($toolboxRoot . '/state.json', json_encode($state, JSON_PRETTY_PRINT));

        $targetFile = $workspace->touch('TestProject/example.php');

        $script = new PatchedScript($workspace);
        $url = 'phpstorm://open?file=' . $targetFile;
        $actual = $script->run($url);

        $expected = self::buildExpectedCommand($launchCommand, null, null, $targetFile);

        $this->assertEquals($expected, $actual);
    }
}

return new StateJsonNonPhpStormEntrySkippedTest();
