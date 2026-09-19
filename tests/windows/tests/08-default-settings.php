<?php
/**
 * No fixture, no cscript - just confirms the unmodified run_editor.js still ships disk_letter
 * and x64 defaults of 'C:' and true. IdeInstallFixture exercises the standalone-fallback *formula*
 * against a subst'd drive instead of the real C:\Program Files\... (no admin rights needed), so
 * this is the one place that separately guards the actual shipped default values from drifting.
 */
final class DefaultSettingsTest extends TestCase
{
    public function name(): string
    {
        return 'default-settings';
    }

    protected function execute(Workspace $workspace, ...$args): void
    {
        $content = file_get_contents(WIN_APP_DIR . '/run_editor.js');

        $this->assertEquals("'C:'", self::extractSetting($content, 'disk_letter'));
        $this->assertEquals("true", self::extractSetting($content, 'x64'));
    }

    private static function extractSetting(string $content, string $key): string
    {
        $pattern = '/^\s*' . preg_quote($key, '/') . '\s*:\s*([^,\r\n]+),/m';

        if (preg_match($pattern, $content, $matches) !== 1) {
            throw new RuntimeException('Could not find settings.' . $key . ' in run_editor.js');
        }

        return trim($matches[1]);
    }
}

return new DefaultSettingsTest();
