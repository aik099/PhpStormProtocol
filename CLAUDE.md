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
  - `run_editor.js` — parses `phpstorm://` URL, resolves PhpStorm exe via `getPhpStormCommandPath()`
    priority chain (`settings.toolbox_v1_commandPath` → shell script → `state.json` → standalone
    fallback), launches via `shell.Exec`. No `AppActivate`/window-focus step (removed in #71 —
    PhpStorm self-focuses regardless of launch path; the old code never worked anyway).
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
  documentation and is written per-OS (Windows/Mac/Linux sections). Its Windows "Compatibility"
  section lists tested Toolbox/PhpStorm versions — update it when that coverage changes.
- Naming: Toolbox-related functions are prefixed `toolbox_v1_` (legacy folder-scan only) or
  `toolbox_common_` (works across all tested Toolbox generations). Keep this convention for new code.
- `getPhpStormCommandPath`'s `state.json` branch matches `settings.toolbox_update_channel_dir`
  against each entry's `channelId` (substring, since the shape varies by Toolbox version) to pick
  among multiple installed channels; `null` keeps the old first-match default. `channels\` there
  holds per-channel JSON *files*, not folders.

## Backward-compatibility promise — the core design constraint

The public API is the `phpstorm://` URL format itself (`open?url=file://%f&line=%l`, and
`open?file=%f&line=%l` for PhpStorm 8+). **That format must never break**, and it does not change from
release to release. What *does* change, constantly and outside this project's control, is the
detection layer underneath it: how to locate/launch the right PhpStorm executable given whatever
version of Windows, PhpStorm, and JetBrains Toolbox the user happens to have installed.

Users cannot upgrade OS/PhpStorm/Toolbox in lockstep with this repo. When patching detection logic
(`getPhpStormCommandPath`, `toolbox_v1_*`, `toolbox_common_*`, project detection):

- **Add a fallback branch; never let a new path silently replace an old one's result.** PR #50
  (784f1b6) added `state.json` support but broke Toolbox 1.x support in the process: it moved a
  shared `editor` assignment to run after the v1 scan, unconditionally overwriting it, and its own
  fallback formula assumed the wrong install location. Silently broken for ~2 years, unnoticed
  because most users hit the shell-script path instead. Fixed in #72 by giving v1's result its own
  dedicated field (`settings.toolbox_v1_commandPath`) instead of sharing state. **Don't reuse/share
  variables across resolution paths — that's exactly what caused this regression.**
- Detect format/version by **probing what's actually on disk**, not assuming one target version.
- Don't drop standalone-install support when fixing Toolbox, or vice versa.
- If PhpStorm's own CLI behavior changes (e.g. #69: `--line` needs project path first), branch on it —
  don't assume the new calling convention unconditionally.

## PR history: reactive-maintenance repo

Most substantive changes are fixes for Toolbox/PhpStorm/Windows breaking install-path assumptions,
not new features. Expect this to continue.

- Toolbox layout changes: #30 (2019, initial support) → #36/#37 (version-folder detection) → #38
  (auto-detect) → #48 (favorite channel) → #50 (2023, `state.json` — **broke v1 support**, see BC
  section) → #54 (fresh install) → #58/#59 (prefer shell script) → #66 (2025, absolute
  `launchCommand`) → #69 (2026, `.idea` folder vs `.idea/.name` detection fix) → #72 (2026, restored
  v1 support broken by #50) → #74 (2026, honor `toolbox_update_channel_dir` in the `state.json` path,
  which #50 never wired up).
- #71 (2026): removed non-functional `window_title`/`AppActivate`.
- Windows/registry quirks: #21/#25/#26/#28, `Icon\r` removal (`cc2fd3b`).
- PhpStorm version drift: #8/#11/#16 (default folder-name bumps for direct installs).
- Minority are real new features: #17 (Linux docs), #22 (Mac scheme), #52/#53 (no-line-number
  support), #41 (disk letter config), #58/#59 (shell-script config), #65 (uninstall.reg).

**When a user reports breakage** after a Toolbox/PhpStorm/Windows update: check
`gh pr list --repo aik099/PhpStormProtocol` for known unmerged fixes first, then look at
`getPhpStormCommandPath`'s priority chain and `toolbox_v1_*`/`toolbox_common_*` functions.
