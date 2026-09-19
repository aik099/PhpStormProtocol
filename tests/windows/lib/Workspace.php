<?php

/**
 * A scratch drive per test case: a fresh temp folder, subst'd to a free drive letter, so
 * every fixture path (project files, %localappdata%, a fake "Program Files\...") is a real
 * Windows path on that one drive.
 *
 * cleanup() unsubst's then removes the backing folder.
 */
final class Workspace implements Disposable
{
    public string $driveLetter;

    public string $localAppData;

    private string $backingDir;

    private SubstDrive $drive;

    public function __construct(string $name)
    {
        $tempDir = getenv('RUNNER_TEMP') ?: sys_get_temp_dir();
        $this->backingDir = rtrim($tempDir, '/\\') . '/phpstorm-protocol-tests/' . $name;

        self::removeDirectory($this->backingDir);
        FileSystem::mkdir($this->backingDir);

        $this->drive = new SubstDrive($this->backingDir);
        $this->driveLetter = $this->drive->driveLetter;

        $this->localAppData = $this->child('AppData/Local');
        FileSystem::mkdir($this->localAppData);
    }

    public function cleanup(): void
    {
        $this->drive->cleanup();
        self::removeDirectory($this->backingDir);
    }

    public function child(string $relative): string
    {
        return $this->driveLetter . '/' . ltrim($relative, '/');
    }

    /**
     * Creates an empty file at $relative, including any missing parent directories.
     */
    public function touch(string $relative): string
    {
        $path = $this->child($relative);
        FileSystem::mkdir(dirname($path));
        FileSystem::touch($path);

        return $path;
    }

    public static function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                FileSystem::rmdir($item->getPathname());
            } else {
                FileSystem::unlink($item->getPathname());
            }
        }

        FileSystem::rmdir($dir);
    }
}
