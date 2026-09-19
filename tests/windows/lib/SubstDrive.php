<?php


final class SubstDrive implements Disposable
{
    public string $driveLetter;

    public function __construct(string $backingDir)
    {
        foreach (self::candidateDriveLetters() as $driveLetter) {
            if (is_dir($driveLetter . '/')) {
                continue;
            }

            exec(
                'subst ' . $driveLetter . ' ' . escapeshellarg(str_replace('/', '\\', $backingDir)),
                $output,
                $exitCode
            );

            if ($exitCode === 0) {
                $this->driveLetter = $driveLetter;

                return;
            }
        }

        throw new RuntimeException('No free drive letter is available for subst.');
    }

    public function cleanup(): void
    {
        exec('subst ' . $this->driveLetter . ' /d', $output, $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException('subst ' . $this->driveLetter . ' /d failed: ' . implode(PHP_EOL, $output));
        }
    }

    /**
     * @return iterable<string> Drive letters in "<letter>:" format (e.g. "K:").
     */
    private static function candidateDriveLetters(): iterable
    {
        for ($ord = ord('K'); $ord <= ord('T'); $ord++) {
            yield chr($ord) . ':';
        }
    }
}
