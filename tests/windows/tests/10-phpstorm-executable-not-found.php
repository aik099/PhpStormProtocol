<?php
/**
 * getPhpStormCommandPath() resolving to a path nothing actually created (e.g., a user who never
 * edited the template's own folder_name placeholder) must fail fast with a descriptive message
 * instead of silently calling shell.Exec() on a nonexistent target.
 */
final class PhpStormExecutableNotFoundTest extends TestCase
{
    public function name(): string
    {
        return 'phpstorm-executable-not-found';
    }

    protected function execute(Workspace $workspace, ...$args): void
    {
        $targetFile = $workspace->touch('TestProject/example.php');

        /*
         * No settings overrides at all - run_editor.js's own unedited template defaults
         * (disk_letter 'C:', folder_name '<phpstorm_folder_name>') resolve to a path nobody
         * ever created, and no Toolbox is installed here either.
         */
        $script = new PatchedScript($workspace);

        $actual = $script->run('phpstorm://open?file=' . $targetFile);

        $expected = 'PhpStorm executable not found: C:\Program Files\JetBrains\<phpstorm_folder_name>\bin\phpstorm64.exe - check your settings';

        $this->assertEquals($expected, $actual);
    }
}

return new PhpStormExecutableNotFoundTest();
