<?php


final class PatchedScript
{
    public string $path;

    private string $localAppData;

    /**
     * @param array<string, string> $settings Raw JS literals, e.g. ['x64' => 'true', 'folder_name' => "'Foo'"]
     */
    public function __construct(Workspace $workspace, array $settings = [])
    {
        // Copy & patch the "run_editor.js" script.
        // Normalized to "\n" in case "core.autocrlf=true" checked it out as CRLF.
        $content = str_replace("\r\n", "\n", file_get_contents(WIN_APP_DIR . '/run_editor.js'));

        foreach ($settings as $key => $value) {
            $pattern = '/^(\s*' . preg_quote($key, '/') . '\s*:\s*)[^,\r\n]+(,)?$/m';
            $content = preg_replace_callback($pattern, static function (array $regs) use ($value): string {
                $comma = $regs[2] ?? '';

                return $regs[1] . $value . $comma;
            }, $content);
        }

        $this->path = $workspace->child('run_editor.js');
        FileSystem::filePutContents($this->path, $content);

        // Copy the "json2.js" that is mandatory for "run_editor.js" to run.
        FileSystem::copy(WIN_APP_DIR . '/json2.js', $workspace->child('json2.js'));

        $this->localAppData = $workspace->localAppData;
    }

    public function run(string $url): string
    {
        $env = getenv();
        $env['LOCALAPPDATA'] = $this->localAppData;

        // Array command form bypasses "cmd.exe" entirely, so URL characters like "&" never get shell-interpreted.
        $command = ['cscript.exe', '//nologo', $this->path, $url, '--dry-run'];
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];

        $process = proc_open($command, $descriptors, $pipes, null, $env);

        if (!is_resource($process)) {
            throw new RuntimeException('Failed to start: ' . implode(' ', $command));
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return trim($stdout . $stderr);
    }
}
