<?php

/**
 * A legacy Toolbox 1.x layout (apps\PhpStorm\<channel>\<version>\ + .settings.json) under a
 * Workspace's %localappdata%, real enough for toolbox_v1_isInstalled() to detect it.
 */
final class ToolboxV1Fixture
{
    public string $channelDir;

    public string $executablePath;

    public function __construct(string $localAppData, string $channel = 'ch-0', string $version = '262.8665.325')
    {
        $toolboxRoot = $localAppData . '/JetBrains/Toolbox';
        $this->channelDir = $toolboxRoot . '/apps/PhpStorm/' . $channel . '/' . $version;
        $this->executablePath = $this->channelDir . '/bin/phpstorm64.exe';

        FileSystem::mkdir($this->channelDir . '/bin');
        FileSystem::touch($this->executablePath);

        $productInfo = [
            'version' => '2026.2.0.1',
            'launch' => [
                ['launcherPath' => 'bin/phpstorm64.exe'],
            ],
        ];
        FileSystem::filePutContents($this->channelDir . '/product-info.json', json_encode($productInfo));

        $settings = [
            'ordering' => [
                'local' => [
                    ['application_id' => 'PhpStorm', 'channel_id' => $channel],
                ],
            ],
        ];
        FileSystem::filePutContents($toolboxRoot . '/.settings.json', json_encode($settings));
    }
}
