# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

PhpStormProtocol registers a `phpstorm://` custom URL protocol handler so that links (e.g. from a
debugger's stack trace: Xdebug's `xdebug.file_link_format`, Nette debugger, In-Portal debugger) open
the given file at a given line directly in PhpStorm. It is an OS-level protocol-handler installer, not
a JetBrains plugin, an application with a build/test suite, or a long-running service.

There is no build, test, lint, or CI setup in this repo (no package.json/composer.json/Makefile/
.github/workflows) — changes are made directly to the installer scripts/registry files and verified
by manually running the install flow on the target OS.

## Layout

- `README.md` — install/uninstall/config instructions for Windows, Mac, and Linux (Linux is not
  bundled here; it defers to an external AUR package / `sanduhrs/phpstorm-url-handler`).
- `LinCastor.md` — Mac fallback doc for configuring the third-party LinCastor app on OS X 10.9+.
- `PhpStorm Protocol (Win)/` — Windows installer, copied to `C:\Program Files\`:
  - `run_editor.js` — JScript run via `wscript` on protocol invocation. Parses the `phpstorm://` URL
    with a regex, resolves the PhpStorm executable (either a directly configured disk/folder, or
    auto-detected via JetBrains Toolbox's `state.json`/`.settings.json`/`product-info.json`), and
    launches/focuses PhpStorm at the file:line via `WScript.Shell.Exec` + `AppActivate`.
  - `json2.js` — vendored JSON polyfill, included by `run_editor.js`.
  - `run_editor.reg` — installs the `HKEY_CLASSES_ROOT\phpstorm` registry key pointing at
    `run_editor.js`.
  - `uninstall.reg` — removes the `HKEY_CLASSES_ROOT\phpstorm` registry key.
- `PhpStorm Protocol.app/` — macOS AppleScript applet bundle, copied to `/Applications/`:
  - `Contents/bin/parse_url.sh` — shell script invoked by the applet; regex-parses the `phpstorm://`
    URL and shells out to `/usr/local/bin/pstorm "$file:$line"`.
  - `Contents/Resources/Scripts/main.scpt` — compiled AppleScript source driving the applet.

## Working in this repo

- Windows logic lives entirely in `run_editor.js` — read its URL-parsing regex and Toolbox-detection
  logic before changing path resolution or URL format support, since both direct-install and Toolbox
  install paths must keep working.
- Mac logic lives in `Contents/bin/parse_url.sh` (plain shell, easy to edit) — `main.scpt` is a
  compiled AppleScript binary and is not meant to be hand-edited as text.
- When changing install/uninstall behavior, keep `README.md` in sync — it is the only end-user
  documentation and is written per-OS (Windows/Mac/Linux sections).

## Backward-compatibility promise — the core design constraint

The public API is the `phpstorm://` URL format itself (`open?url=file://%f&line=%l`, and
`open?file=%f&line=%l` for PhpStorm 8+). **That format must never break**, and it does not change from
release to release. What *does* change, constantly and outside this project's control, is the
detection layer underneath it: how to locate/launch the right PhpStorm executable given whatever
version of Windows, PhpStorm, and JetBrains Toolbox the user happens to have installed.

Users cannot always upgrade their OS/PhpStorm/Toolbox in lockstep with this repo. So a new
PhpStormProtocol release must keep working for someone still on an old Toolbox 1.x layout or a
directly-installed PhpStorm, not just for whoever triggered the latest fix. Concretely, when patching
`run_editor.js`'s detection logic (`getPhpStormCommandPath`, `getFavoritePhpStormChannel`,
`configureToolboxSettings`, project detection, etc.):

- **Add a fallback branch, don't replace the old code path.** Every historical Toolbox-format change
  (PR #30 → #50 → #66 → #69) was handled by detecting the new layout first and falling back to the
  previous logic if it's absent — never by assuming the new layout is the only one that exists.
- Detect format/version by **probing for what's actually present on disk** (does `state.json` exist?
  does `apps/` exist? is `launchCommand` absolute or relative?), not by assuming a single supported
  target version.
- Don't drop support for standalone (non-Toolbox) PhpStorm installs when fixing a Toolbox-specific
  issue, and vice versa.
- When PhpStorm's own CLI behavior changes (e.g. PR #69's `--line`-requires-project-path-first-arg
  change), branch on it rather than making the new calling convention unconditional, if there's any
  chance older PhpStorm versions relied on the old one.

## PR history: this is mostly a reactive-maintenance repo

Looking at the GitHub PR/commit history (80 commits, ~50 merged PRs since 2013), the large majority of
substantive changes are **not new features** — they are fixes for `run_editor.js` (Windows) breaking
because JetBrains Toolbox, PhpStorm itself, or Windows changed how they lay out install paths/config.
Expect this pattern to continue, and treat "PhpStorm/Toolbox changed something again" as the default
reason for a bug report, not an edge case:

- **JetBrains Toolbox storage/layout changes**, repeatedly, as Toolbox evolved:
  - PR #30 (2019) — initial Toolbox support (`apps/` folder + `.settings.json`).
  - PR #36/#37 (2021) — "fix current/really fix folder version detection on Windows" (Toolbox's
    version-folder naming changed).
  - PR #38 (2021) — auto-detect whether Toolbox is installed at all.
  - PR #48 (2023) — read favorite PhpStorm channel from Toolbox settings.
  - PR #50 (2023) — "Add support to new Toolbox storage engine" (Toolbox introduced a new state
    format).
  - PR #54 (2024) — handle a fresh Toolbox install (no prior state yet).
  - PR #58/#59 (2024) — prefer Toolbox's own generated shell script launcher over reimplementing
    path resolution.
  - PR #66 (2025, merged) — Toolbox's `launchCommand` started including a full path instead of a
    relative one.
  - **PR #69 (2026-07-24, open)** — Toolbox 2.0+ removed the `apps/` directory entirely (IDEs now
    under `%localappdata%\Programs\PhpStorm\`, with an absolute `launchCommand` in `state.json`), and
    PhpStorm 2026.2+ started silently ignoring `--line` unless the project path is passed as the
    first CLI argument. Fixes `getPhpStormCommandPath`, `getFavoritePhpStormChannel`,
    `configureToolboxSettings`, project detection (`.idea/` folder vs `.idea/.name` file), and
    switches the launcher from `shell.Exec` to `shell.Run`.
- **Windows OS/registry quirks**: PR #21/#25/#26 (Windows 10 / x64 registry-key fixes), PR #28 ("total
  compatibility x64 windows"), the `Icon\r` file removal (commit `cc2fd3b`) that was breaking Windows
  file listings.
- **PhpStorm CLI/versioning drift**: recurring "update default settings for current PhpStorm version"
  PRs (#8, #11, #16) whenever a new PhpStorm version changed its default install folder name.
- Genuinely new-feature PRs are the minority: Linux instructions (#17), Mac scheme support (#22),
  project-folder-without-line-number support (#52/#53), configurable disk letter (#41), configurable
  Toolbox shell-script path (#58/#59), and the uninstall.reg addition (#65, this branch).

**Implication for future work**: when a user reports "phpstorm:// stopped working" after updating
Toolbox, PhpStorm, or Windows, the fix almost always belongs in `run_editor.js`'s Toolbox-path/
version-detection or CLI-argument logic (see `getPhpStormCommandPath`, `getFavoritePhpStormChannel`,
`configureToolboxSettings` per PR #69) — check `gh pr list --repo aik099/PhpStormProtocol` and the
currently open PRs for known unmerged fixes before re-deriving the same investigation.
