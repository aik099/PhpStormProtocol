<?php


final class FileSystem
{
    public static function mkdir(string $dir): void
    {
        if (is_dir($dir)) {
            return;
        }

        mkdir($dir, 0777, true);
    }

    public static function touch(string $file): void
    {
        touch($file);
    }

    public static function filePutContents(string $file, string $content): void
    {
        file_put_contents($file, $content);
    }

    public static function copy(string $from, string $to): void
    {
        copy($from, $to);
    }

    public static function unlink(string $file): void
    {
        unlink($file);
    }

    public static function rmdir(string $dir): void
    {
        rmdir($dir);
    }
}
