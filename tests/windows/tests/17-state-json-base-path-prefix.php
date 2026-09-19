<?php
/**
 * state.json's launchCommand doesn't always already include installLocation as a prefix (some
 * Toolbox versions store it as a bare relative path) - getPhpStormCommandPath() must then prepend
 * installLocation itself via the `basePath` ternary. The sibling state-json tests only ever
 * exercise the already-prefixed case, so this specifically covers the "doesn't already include it"
 * branch.
 */
final class StateJsonBasePathPrefixTest extends TestCase
{
    public function name(): string
    {
        return 'state-json-base-path-prefix';
    }

    protected function execute(Workspace $workspace, ...$args): void
    {
        $toolboxRoot = $workspace->localAppData . '/JetBrains/Toolbox';
        FileSystem::mkdir($toolboxRoot);

        $installLocation = $workspace->child('Program Files/JetBrains/PhpStorm 2026.1.4');
        $executablePath = $installLocation . '/bin/phpstorm64.exe';
        FileSystem::mkdir($installLocation . '/bin');
        FileSystem::touch($executablePath);

        $state = [
            'tools' => [
                [
                    'channelId' => 'PhpStorm-18595934-5e62-4279-90ee-9200359430cb',
                    'toolId' => 'PhpStorm',
                    'installLocation' => $installLocation,
                    /*
                     * Deliberately NOT prefixed with $installLocation - forces
                     * getPhpStormCommandPath() to prepend it itself via the basePath ternary.
                     */
                    'launchCommand' => 'bin/phpstorm64.exe',
                ],
            ],
        ];

        FileSystem::filePutContents($toolboxRoot . '/state.json', json_encode($state, JSON_PRETTY_PRINT));

        $targetFile = $workspace->touch('TestProject/example.php');

        $script = new PatchedScript($workspace);
        $url = 'phpstorm://open?file=' . $targetFile;
        $actual = $script->run($url);

        $expected = self::buildExpectedCommand($executablePath, null, null, $targetFile);

        $this->assertEquals($expected, $actual);
    }
}

return new StateJsonBasePathPrefixTest();
