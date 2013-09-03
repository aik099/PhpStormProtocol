Overview
========
This app allows to use ```pstorm://``` protocol to open a file in a [PhpStorm IDE](http://www.jetbrains.com/phpstorm/) the same way as it's done in [TextMate, (section 21.2)](http://manual.macromates.com/en/using_textmate_from_terminal.html).

Following string must be specified as an editor in your app:
```bash
pstorm://open/?url=file://%f&line=%l
```
If something doesn't work, then feel free to [submit an issue](https://github.com/aik099/PhpStormProtocol/issues/new) on GitHub.


More reading about protocol handlers: http://pla.nette.org/en/how-open-files-in-ide-from-debugger.

Installing on Mac
=================
1. clone this repository
2. go to cloned folder
2. copy folder ```PhpStorm Protocol.app``` to ```/Applications/``` folder
3. delete cloned folder

Installing on Windows
=====================
1. clone this repository
2. go to cloned folder
3. copy folder ```PhpStorm Protocol (Win)``` to ```C:\Program Files\``` folder
4. double click on ```C:\Program Files\PhpStorm Protocol (Win)\run_editor.reg``` file
5. agree to whatever Registry Editor asks you
6. update settings at ```C:\Program Files\PhpStorm Protocol (Win)\run_editor.js``` file, because each PhpStorm version is installed into it's own sub-folder!
7. delete cloned folder
