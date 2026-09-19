<?php


abstract class TestCase
{
    /**
     * @var Disposable[]
     */
    private array $disposables = [];

    private bool $assertionMade = false;

    private string $label;

    /**
     * @var array The data set's values, from dataProvider().
     */
    private array $data;

    public function __construct(string $label = '', array $data = [])
    {
        $this->label = $label;
        $this->data = $data;
    }

    abstract public function name(): string;

    /**
     * Extra parameters (beyond $workspace) come from dataProvider()'s array.
     */
    abstract protected function execute(Workspace $workspace, ...$args): void;

    /**
     * Override to run execute() once per data set instead of once. Yield label => array of
     * positional arguments for execute() (beyond $workspace).
     *
     * @return iterable<string, array>
     */
    public static function dataProvider(): iterable
    {
        yield '' => [];
    }

    final public function displayName(): string
    {
        return $this->label === '' ? $this->name() : $this->name() . '-' . $this->label;
    }

    final public function run(): bool
    {
        $name = $this->displayName();
        $workspace = $this->track(new Workspace($name));

        try {
            $this->execute($workspace, ...$this->data);
        } catch (AssertionFailedException $e) {
            echo self::color('31', 'FAIL: ' . $name) . PHP_EOL;

            if ($e->getMessage() !== '') {
                echo '  ' . $e->getMessage() . PHP_EOL;
            }

            echo '  expected: ' . $e->expected . PHP_EOL;
            echo '  actual:   ' . $e->actual . PHP_EOL;

            return false;
        } finally {
            $this->cleanup();
        }

        if (!$this->assertionMade) {
            echo self::color('31', 'FAIL: ' . $name . ' - no assertions made') . PHP_EOL;

            return false;
        }

        echo self::color('32', 'PASS: ' . $name) . PHP_EOL;

        return true;
    }

    /**
     * Fails immediately upon an assertion failure.
     */
    protected function assertEquals(string $expected, string $actual, string $message = ''): void
    {
        $this->assertionMade = true;

        if ($actual !== $expected) {
            throw new AssertionFailedException($expected, $actual, $message);
        }
    }

    /**
     * The full URL-format matrix (with/without &line=, file vs folder, new "file="
     * vs legacy "url=file://" form) against an already-resolved executable path.
     *
     * Run this once per resolution branch under test; other assertions should stay
     * minimal.
     */
    protected function assertUrlMatrix(
        PatchedScript $script,
        string $executablePath,
        string $projectDir,
        string $targetFile
    ): void
    {
        // Opening the file directly - exercises the "--line" branch.
        $fileExpected = self::buildExpectedCommand($executablePath, $projectDir, 5, $targetFile);

        $fileUrl = 'phpstorm://open?file=' . $targetFile . '&line=5';
        $this->assertEquals($fileExpected, $script->run($fileUrl), 'opening the file');

        $legacyFileUrl = 'phpstorm://open?url=file:///' . $targetFile . '&line=5';
        $this->assertEquals(
            $fileExpected,
            $script->run($legacyFileUrl),
            'opening the file (legacy url=file:// form)'
        );

        // Opening the containing project folder directly - exercises the "isFolder" branch (no file/line appended).
        $folderExpected = self::buildExpectedCommand($executablePath, $projectDir, null, null);

        $folderUrl = 'phpstorm://open?file=' . $projectDir;
        $this->assertEquals($folderExpected, $script->run($folderUrl), 'opening the folder');

        $legacyFolderUrl = 'phpstorm://open?url=file:///' . $projectDir;
        $this->assertEquals(
            $folderExpected,
            $script->run($legacyFolderUrl),
            'opening the folder (legacy url=file:// form)'
        );

        // Opening the file with no &line= - exercises the plain "isFile" branch (no --line).
        $noLineExpected = self::buildExpectedCommand($executablePath, $projectDir, null, $targetFile);

        $noLineUrl = 'phpstorm://open?file=' . $targetFile;
        $this->assertEquals(
            $noLineExpected,
            $script->run($noLineUrl),
            'opening the file without a line number'
        );

        $legacyNoLineUrl = 'phpstorm://open?url=file:///' . $targetFile;
        $this->assertEquals(
            $noLineExpected,
            $script->run($legacyNoLineUrl),
            'opening the file without a line number (legacy url=file:// form)'
        );
    }

    /**
     * @template T of Disposable
     * @param T $disposable
     * @return T
     */
    protected function track(Disposable $disposable): Disposable
    {
        $this->disposables[] = $disposable;

        return $disposable;
    }

    private function cleanup(): void
    {
        foreach (array_reverse($this->disposables) as $disposable) {
            try {
                $disposable->cleanup();
            } catch (Throwable $e) {
                echo 'WARN: cleanup failed - ' . $e . PHP_EOL;
            }
        }
    }

    /**
     * Builds command like "run_editor.js" does.
     */
    protected static function buildExpectedCommand(
        string $editorPath,
        ?string $project,
        ?int $line,
        ?string $file
    ): string
    {
        $command = '"' . $editorPath . '"';

        if ($project !== null) {
            $command .= ' "' . $project . '"';
        }

        if ($line !== null) {
            $command .= ' --line ' . $line . ' "' . $file . '"';
        } elseif ($file !== null) {
            $command .= ' "' . $file . '"';
        }

        return self::normalizeSlashes($command);
    }

    /**
     * Normalizes slashes like "run_editor.js" does.
     *
     * @param string $path Path.
     *
     * @return string
     */
    protected static function normalizeSlashes(string $path): string
    {
        return str_replace('/', '\\', $path);
    }

    /**
     * Wraps $text in an ANSI color code (30-37/90-97), unless NO_COLOR env var is set.
     */
    public static function color(string $code, string $text): string
    {
        // Respect "NO_COLOR" env variable as per https://no-color.org/.
        return getenv('NO_COLOR') === false ? "\033[" . $code . 'm' . $text . "\033[0m" : $text;
    }
}
