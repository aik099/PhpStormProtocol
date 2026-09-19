<?php


final class IdeInstallFixture
{
    public string $dir;

    public string $executablePath;

    private string $folderName;

    private bool $x64;

    private string $driveLetter;

    public function __construct(Workspace $workspace, string $folderName, bool $x64 = true)
    {
        if ($x64) {
            $this->dir = $workspace->child('Program Files/JetBrains/' . $folderName);
            $this->executablePath = $this->dir . '/bin/phpstorm64.exe';
        } else {
            $this->dir = $workspace->child('Program Files (x86)/JetBrains/' . $folderName);
            $this->executablePath = $this->dir . '/bin/phpstorm.exe';
        }

        $this->folderName = $folderName;
        $this->x64 = $x64;
        $this->driveLetter = $workspace->driveLetter;

        FileSystem::mkdir($this->dir . '/bin');
        FileSystem::touch($this->executablePath);
    }

    /**
     * @return array<string, string>
     */
    public function settings(): array
    {
        return [
            'x64' => $this->x64 ? 'true' : 'false',
            'disk_letter' => "'" . $this->driveLetter . "'",
            'folder_name' => "'" . $this->folderName . "'",
        ];
    }
}
