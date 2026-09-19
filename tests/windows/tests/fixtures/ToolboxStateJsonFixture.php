<?php

/**
 * A current-generation (2.0+/3.x) Toolbox state.json under a Workspace's %localappdata%, with two PhpStorm
 * channels. The second entry's launchCommand points at a real file under the Workspace's own
 * drive - run_editor.js verifies the resolved launcher actually exists before using it. The first
 * entry's location stays fictional.
 */
final class ToolboxStateJsonFixture
{
    public const FIRST_CHANNEL_UUID = 'b9162270';

    public const SECOND_CHANNEL_UUID = '18595934';

    public string $secondLaunchCommand;

    public function __construct(Workspace $workspace)
    {
        $toolboxRoot = $workspace->child('AppData/Local/JetBrains/Toolbox');
        FileSystem::mkdir($toolboxRoot);

        $installLocation = $workspace->child('Program Files/JetBrains/PhpStorm 2026.1.4');
        $this->secondLaunchCommand = $installLocation . '/bin/phpstorm64.exe';
        FileSystem::mkdir($installLocation . '/bin');
        FileSystem::touch($this->secondLaunchCommand);

        $state = [
            'tools' => [
                [
                    'channelId' => 'PhpStorm-' . self::FIRST_CHANNEL_UUID . '-8eff-4c15-a37e-557b7e4e8335',
                    'toolId' => 'PhpStorm',
                    'installLocation' => 'C:\\Users\\CI\\AppData\\Local\\Programs\\PhpStorm',
                    'launchCommand' => 'C:\\Users\\CI\\AppData\\Local\\Programs\\PhpStorm\\bin\\phpstorm64.exe',
                ],
                [
                    'channelId' => 'PhpStorm-' . self::SECOND_CHANNEL_UUID . '-5e62-4279-90ee-9200359430cb',
                    'toolId' => 'PhpStorm',
                    'installLocation' => $installLocation,
                    'launchCommand' => $this->secondLaunchCommand,
                ],
            ],
        ];

        FileSystem::filePutContents($toolboxRoot . '/state.json', json_encode($state, JSON_PRETTY_PRINT));
    }
}
