
Overview
========
This app allows to use ```phpstorm://``` protocol to open a file in a [PhpStorm IDE](http://www.jetbrains.com/phpstorm/) the same way as it's done in [TextMate, (section 21.2)](http://manual.macromates.com/en/using_textmate_from_terminal.html).

NOTE: Built-in ``idea://`` and ``phpstorm://`` protocols are supported in PhpStorm 8 EAP 138.190+
according to this [comment](https://youtrack.jetbrains.com/oauth?state=%2Fissue%2FIDEA-65879#comment=27-736256).

Following string must be specified as an editor in your app:
```bash
phpstorm://open?url=file://%f&line=%l
```

Alternative syntax is supported for cross-platform compatibility with PhPStorm 8+ (Mac version)
```bash
phpstorm://open?file=%f&line=%l
```

If something doesn't work, then feel free to [submit an issue](https://github.com/aik099/PhpStormProtocol/issues/new) on GitHub.


More reading about protocol handlers: http://pla.nette.org/en/how-open-files-in-ide-from-debugger.

Installing on Linux
===================

A package for Arch Linux (Arch, Manjaro, Antergos, etc.) is available from the [AUR](https://aur.archlinux.org/packages/phpstorm-url-handler/).
If you're using other Linux distribution then follow these instructions:

1. clone [https://github.com/sanduhrs/phpstorm-url-handler](https://github.com/sanduhrs/phpstorm-url-handler) repository
2. go to cloned folder
3. read installation instructions in [README.md](https://github.com/sanduhrs/phpstorm-url-handler/blob/master/README.md)
4. delete cloned folder

Installing on Mac
=================
Please follow instructions from the [this page](LinCastor.md) if the `PhpStorm Protocol.app` won't work as expected on your OS X version.
PhpStorm 8+ (Mac version only) has built-in url handler and `phpstorm://open?file=%f&line=%l` url needs to be used instead (see https://youtrack.jetbrains.com/issue/IDEA-65879).

1. clone this repository
2. go to cloned folder
3. copy folder ```PhpStorm Protocol.app``` to ```/Applications/``` folder
4. delete cloned folder

Installing on Windows
=====================
1. clone this repository
2. go to cloned folder
3. copy folder ```PhpStorm Protocol (Win)``` to ```C:\Program Files\``` folder
4. double click on ```C:\Program Files\PhpStorm Protocol (Win)\run_editor.reg``` file
5. agree to whatever Registry Editor asks you
6. update settings at ```C:\Program Files\PhpStorm Protocol (Win)\run_editor.js``` file, because each PhpStorm version is installed into it's own sub-folder!
7. delete cloned folder

#### Working under another path?
You can make use of the [project alias settings](/PhpStorm Protocol (Win)/run_editor.js#L11-L14) in case you are working under a network share or Vagrant.
