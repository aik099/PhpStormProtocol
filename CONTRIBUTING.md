# Contributing

There's no build, test, lint, or CI setup in this repo — changes to the installer scripts are
verified by manually running the install flow (or invoking `run_editor.js` directly) on the
target OS. This doc covers how that testing usually happens for Windows changes, since that's
where almost all the complexity (and almost all bug reports) live.

## Getting test builds

Don't rely on guessed/hardcoded download URLs — grab exact versions from JetBrains' own catalogs:

- PhpStorm, all versions (Windows x64/ARM64 selector): https://www.jetbrains.com/phpstorm/download/other/
- JetBrains Toolbox, previous releases: https://www.jetbrains.com/toolbox-app/download/other/

## Test environment

A disposable Windows VM with snapshot support (Parallels, VMware, Hyper-V, VirtualBox) is strongly
recommended. JetBrains Toolbox self-updates the moment it has network access, and reinstalling a
specific old Toolbox generation from scratch is slow — snapshot right after installing each
Toolbox generation you need, before it has a chance to phone home, so you can revert and try a
different PhpStorm/config combination without reinstalling Toolbox itself each time.

To force a specific old Toolbox version to stay put:
1. Disconnect the VM's network adapter.
2. Install that Toolbox version.
3. Disable auto-update in its settings.
4. Reconnect the network adapter — it'll show an available self-update but won't apply it, and
   you can now let it download a PhpStorm build itself if that's the path you're testing.

## Running `run_editor.js` without installing the protocol handler

`run_editor.js` only reads its single argument via `WScript.Arguments(0)` — it doesn't need the
registry key or a browser to exercise:

```cmd
cscript //nologo "run_editor.js" "phpstorm://open?file=C:/TestProject/src/example.php&line=5"
```

Notes:
- Use `cscript`, not `wscript` — `wscript` shows script errors as popup dialogs; `cscript` prints
  them to the console, which is much faster to iterate on.
- Keep `run_editor.js` and `json2.js` together — `includeFile('json2.js')` resolves relative to
  the script's own location (`WScript.ScriptFullName`), not your current directory.
- Use forward slashes for the file path in the URL — the regex expects `file://`, and the script
  converts `/` to `\` internally.
- To see which resolution branch the script took (direct install / Toolbox shell-script /
  `state.json` / legacy Toolbox v1 folder-scan; project detected or not), temporarily add
  `WScript.Echo(...)` calls at the relevant decision points — much faster than inferring behavior
  from the black-box result alone. Strip them back out before submitting a PR.
- Only exercise the real `.reg` file + a browser link once at the end, to confirm end-to-end OS
  dispatch still works — everything else can go through the `cscript` invocation above.
- Make `C:\TestProject\src\example.php` 10+ lines, so `--line N` is unambiguous. Let PhpStorm
  create `.idea` itself — open the folder as a project, then open Settings and click Save/OK (no
  changes needed). Don't create `.idea` by hand; PhpStorm may misbehave on one it doesn't recognize.

## What to test when touching PhpStorm/Toolbox detection

Cross-reference against whichever of these are relevant to your change:

**Install source**
- No Toolbox, one direct install (the `disk_letter`/`folder_name` fallback).
- No Toolbox, two direct installs (does config pick the intended one?).
- Toolbox only, one channel.
- Toolbox only, two channels (does `toolbox_update_channel_dir` actually get honored?).
- Toolbox and a direct install both present (which wins, and is that intentional?).
- Toolbox installed but no PhpStorm downloaded yet (should fail cleanly, not throw).

**Toolbox generation** (install the same PhpStorm version under each, snapshot between)
- Oldest obtainable Toolbox 1.x — legacy `apps\PhpStorm\<channel>\<version>\` layout.
- A mid-generation Toolbox (e.g. 2.x) — may still generate `apps\` alongside `state.json`, or not;
  don't assume.
- Current Toolbox — `state.json` schema, and whether `channels\` is a directory of files or
  subfolders (this has changed between generations).
- With and without shell-script generation enabled in Toolbox's settings — it takes resolution
  priority when present, which can mask bugs in the other resolution paths.

**PhpStorm version**
- Invoke the exe directly (bypassing the protocol handler) to isolate PhpStorm's own CLI behavior
  from this repo's project-detection logic, when diagnosing a "stopped working after updating
  PhpStorm" report.

**Window/project targeting**
- Project already open in an unfocused window; open a link to a file inside it — should reuse the
  window, not spawn a second instance.
- File inside a project with no `.idea\.name` file (the common case — that file only exists when
  the project's display name differs from its folder name).
- File under a subfolder that itself has a stray `.idea` folder (e.g. a vendored dependency) —
  should attach to the real outer project, not the inner one.
- Bare file with no project anywhere in its ancestor path — should still open, standalone.

## Toolbox folder/file structures

Real, trimmed samples of what `run_editor.js` actually reads, by generation. All paths are under
`%localappdata%\JetBrains\Toolbox\` (e.g. `C:\Users\<username>\AppData\Local\JetBrains\Toolbox\`).

### Legacy Toolbox 1.x

```
apps\PhpStorm\ch-0\262.8665.325\bin\phpstorm64.exe
apps\PhpStorm\ch-0\262.8665.325\product-info.json
scripts\PhpStorm.cmd          (if shell-script generation is enabled)
.settings.json
```

`.settings.json` — `toolbox_v1_getFavoriteChannel()` reads `ordering.local[]`:
```json
{
    "ordering": { "local": [ { "application_id": "PhpStorm", "channel_id": "ch-0" } ] }
}
```

`product-info.json` — `toolbox_v1_configureSettings()` reads `launch[0].launcherPath`:
```json
{ "version": "2026.2.0.1", "launch": [ { "launcherPath": "bin/phpstorm64.exe" } ] }
```

### Current Toolbox (2.0+/3.x)

```
state.json
channels\PhpStorm-fc73e598-5002-43da-87f6-f53e953ccccd.json   (a file per channel, not a folder)
scripts\PhpStorm.cmd
```

`state.json` — `getPhpStormCommandPath()` reads `tools[]`, matching `toolId` and `channelId`. Note
the two different `installLocation` shapes below: a build Toolbox actually installed/manages itself
lands in a fixed, version-less path (`%localappdata%\Programs\PhpStorm`); one it merely detected
(a direct/standalone install elsewhere) keeps that install's own path:
```json
{
    "tools": [
        {
            "channelId": "PhpStorm-b9162270-8eff-4c15-a37e-557b7e4e8335",
            "toolId": "PhpStorm",
            "installLocation": "C:\\Users\\<username>\\AppData\\Local\\Programs\\PhpStorm",
            "launchCommand": "C:\\Users\\<username>\\AppData\\Local\\Programs\\PhpStorm\\bin\\phpstorm64.exe"
        },
        {
            "channelId": "PhpStorm-18595934-5e62-4279-90ee-9200359430cb",
            "toolId": "PhpStorm",
            "installLocation": "C:\\Program Files\\JetBrains\\PhpStorm 2026.1.4",
            "launchCommand": "C:\\Program Files\\JetBrains\\PhpStorm 2026.1.4\\bin\\phpstorm64.exe"
        }
    ]
}
```

`scripts\PhpStorm.cmd` — just a thin wrapper `getPhpStormCommandPath()` invokes directly:
```bat
start "" %waitarg% "C:\Program Files\JetBrains\PhpStorm 2026.2.0.1\bin\phpstorm64.exe" %intellij_args%
```

## Pull requests

Keep PRs small and single-topic — a change that touches Toolbox detection, a change that touches
window focus, and a change that touches project detection are three PRs, not one, even if you
found all three while working on the same bug report. Commit/PR titles use the
`[OS name] Sentence-case summary` format (e.g. `[Windows] Fix ...`).
