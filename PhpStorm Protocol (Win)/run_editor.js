// Ensure strict mode for better error handling and security
'use strict';

var settings = {
    // Set to 'true' (without quotes) if run on Windows 64bit. Set to 'false' (without quotes) otherwise.
    x64: true,

    // Set to disk letter, where PhpStorm was installed to (e.g. C:)
    disk_letter: 'C:',

    // (only, when not using JetBrains Toolbox) Set to folder name, where PhpStorm was installed to (e.g. 'PhpStorm')
    folder_name: '<phpstorm_folder_name>',

    // (only, when not using JetBrains Toolbox) Set to window title (only text after dash sign), that you see, when switching to running PhpStorm instance
    window_title: '<phpstorm_window_title>',

    // In case your file is mapped via a network share and paths do not match.
    // eg. /var/www can be replaced with Y:/
    projects_basepath: '',
    projects_path_alias: '',

    // PhpStorm directory name in Toolbox directory
    // eg. for C:\Users\%username%\AppData\Local\JetBrains\Toolbox\apps\PhpStorm\ch-1 use 'ch-1'
    // Leave null to use the first PHPStorm version in Toolbox
    toolbox_update_channel_dir: null,

    // Set to PhpStorm shell script (filename ends with "*.cmd") from the "C:\Users\%username%\AppData\Local\JetBrains\Toolbox\scripts" directory.
    toolbox_shell_script: 'PhpStorm.cmd'
};

// Flag to activate JetBrains Toolbox configuration
settings.toolBoxActive = isToolboxInstalled();

// Don't change anything below this line, unless you know what you're doing
try {
    var url = WScript.Arguments(0),
        match = /^phpstorm:\/\/open\/?\?(url=file:\/\/|file=)(.+?)(?:&line=(\d+))?$/.exec(url),
        project = '';

    if (settings.toolBoxActive) {
        configureToolboxSettings(settings);
    }

    if (match) {
        var shell = new ActiveXObject('WScript.Shell'),
            file_system = new ActiveXObject('Scripting.FileSystemObject'),
            file = decodeURIComponent(match[2]).replace(/\+/g, ' '),
            search_path = sanitizePath(file.replace(/\//g, '\\')),
            editor = '"' + sanitizePath(getPhpStormCommandPath()) + '"';

        if (settings.projects_basepath && settings.projects_path_alias) {
            var basepathRegex = new RegExp('^' + escapeRegExp(settings.projects_basepath));
            file = file.replace(basepathRegex, settings.projects_path_alias);
        }

        // If only a folder is specified, don't look for a project file or line number
        var isFolder = file_system.FolderExists(search_path);
        var isFile = file_system.FileExists(search_path);

        if (isFolder) {
            project = search_path;
        } else if (isFile) {
            while (search_path.lastIndexOf('\\') !== -1) {
                search_path = search_path.substring(0, search_path.lastIndexOf('\\'));

                if (file_system.FileExists(search_path + '\\.idea\\.name')) {
                    project = search_path;
                    break;
                }
            }
        }

        if (project !== '') {
            editor += ' "' + sanitizePath(project) + '"';
        }

        if (match[3]) {
            var lineNumber = parseInt(match[3], 10);
            if (!isNaN(lineNumber) && lineNumber > 0) {
                editor += ' --line ' + lineNumber + ' "' + sanitizePath(file) + '"';
            }
        } else if (isFile) {
            editor += ' "' + sanitizePath(file) + '"';
        }

        var command = editor;

        // Execute the command safely
        shell.Exec(command);
        shell.AppActivate(settings.window_title);
    }
} catch (error) {
    // Log the error to a file or handle it appropriately
    var errorShell = new ActiveXObject('WScript.Shell');
    errorShell.Popup("An error occurred: " + sanitizeString(error.message), 0, "PhpStorm Script Error", 0x10);
}

function isToolboxInstalled() {
    try {
        var shell = new ActiveXObject('WScript.Shell'),
            appDataLocal = shell.ExpandEnvironmentStrings("%localappdata%"),
            toolboxDirectory = appDataLocal + '\\JetBrains\\Toolbox\\apps\\PhpStorm';

        return (new ActiveXObject('Scripting.FileSystemObject')).FolderExists(toolboxDirectory);
    } catch (e) {
        return false;
    }
}

function getPhpStormCommandPath() {
    try {
        var shell = new ActiveXObject('WScript.Shell'),
            appDataLocal = shell.ExpandEnvironmentStrings("%localappdata%"),
            toolboxShellScript = getToolboxShellScript(appDataLocal);

        if (toolboxShellScript !== undefined) {
            return toolboxShellScript;
        }

        var settingsStateFile = appDataLocal + '\\JetBrains\\Toolbox\\state.json',
            defaultCommandPath = settings.disk_letter + '\\' + (settings.x64 ? 'Program Files' : 'Program Files (x86)') + '\\JetBrains\\' + settings.folder_name + (settings.x64 ? '\\bin\\phpstorm64.exe' : '\\bin\\phpstorm.exe');

        var fso = new ActiveXObject('Scripting.FileSystemObject');
        if (!fso.FileExists(settingsStateFile)) {
            return defaultCommandPath;
        }

        var fileStream = fso.OpenTextFile(settingsStateFile, 1, false);
        var stateContent = fileStream.ReadAll();
        fileStream.Close();

        // Requires JScript 5.8 or newer, >= IE8.0, >= XP SP2 (March 2009)
        var state = JSON.parse(stateContent);
        var tools = state.tools || [];
        for (var i = 0; i < tools.length; i++) {
            if (tools[i].toolId === 'PhpStorm') {
                return sanitizePath(tools[i].installLocation + '\\' + tools[i].launchCommand.replace(/\//g, '\\'));
            }
        }

        return defaultCommandPath;
    } catch (error) {
        return settings.disk_letter + '\\' + (settings.x64 ? 'Program Files' : 'Program Files (x86)') + '\\JetBrains\\' + settings.folder_name + (settings.x64 ? '\\bin\\phpstorm64.exe' : '\\bin\\phpstorm.exe');
    }
}

function getFavoritePhpStormChannel() {
    try {
        var shell = new ActiveXObject('WScript.Shell'),
            appDataLocal = shell.ExpandEnvironmentStrings("%localappdata%"),
            settingsFile = appDataLocal + '\\JetBrains\\Toolbox\\.settings.json',
            fso = new ActiveXObject('Scripting.FileSystemObject');

        if (!fso.FileExists(settingsFile)) {
            return 'ch-0';
        }

        var fileStream = fso.OpenTextFile(settingsFile, 1, false);
        var settingsContent = fileStream.ReadAll();
        fileStream.Close();

        // Requires JScript 5.8 or newer, >= IE8.0, >= XP SP2 (March 2009)
        var settings = JSON.parse(settingsContent);
        var apps = (settings.ordering || {}).local || [];
        for (var i = 0; i < apps.length; i++) {
            if (apps[i].application_id === 'PhpStorm') {
                return sanitizeString(apps[i].channel_id);
            }
        }

        return 'ch-0';
    } catch (error) {
        return 'ch-0';
    }
}

function configureToolboxSettings(settings) {
    try {
        var shell = new ActiveXObject('WScript.Shell'),
            appDataLocal = shell.ExpandEnvironmentStrings("%localappdata%"),
            toolboxShellScript = getToolboxShellScript(appDataLocal);

        // The JetBrains Toolbox Shell Script is clever enough to autofocus PhpStorm window after opening a file in it
        if (toolboxShellScript !== undefined) {
            return;
        }

        // Detect Toolbox PHPStorm top channel
        if (settings.toolbox_update_channel_dir == null) {
            settings.toolbox_update_channel_dir = getFavoritePhpStormChannel();
        }

        var toolboxDirectory = appDataLocal + '\\JetBrains\\Toolbox\\apps\\PhpStorm\\' + settings.toolbox_update_channel_dir + '\\';

        // Reference the FileSystemObject
        var fso = new ActiveXObject('Scripting.FileSystemObject');

        if (!fso.FolderExists(toolboxDirectory)) {
            throw new Error("Toolbox directory does not exist: " + toolboxDirectory);
        }

        // Reference the Folder
        var folder = fso.GetFolder(toolboxDirectory);

        // Reference the SubFolders collection
        var fileCollection = folder.SubFolders;

        var maxVersion = { major: 0, minor: 0, patch: 0 },
            maxVersionFolder = "";

        // Traverse through the fileCollection using the FOR loop
        // Read the maximum version from toolbox filesystem
        for (var objEnum = new Enumerator(fileCollection); !objEnum.atEnd(); objEnum.moveNext()) {
            var folderObject = objEnum.item();

            if (folderObject.Name.toLowerCase().includes('plugins')) {
                continue;
            }

            var versionMatch = /^(\d+)\.(\d+)\.(\d+)$/.exec(folderObject.Name);
            if (!versionMatch) {
                continue;
            }

            var major = parseInt(versionMatch[1], 10),
                minor = parseInt(versionMatch[2], 10),
                patch = parseInt(versionMatch[3], 10);

            if (
                major > maxVersion.major ||
                (major === maxVersion.major && minor > maxVersion.minor) ||
                (major === maxVersion.major && minor === maxVersion.minor && patch > maxVersion.patch)
            ) {
                maxVersion = { major: major, minor: minor, patch: patch };
                maxVersionFolder = folderObject.Name;
            }
        }

        if (maxVersionFolder === "") {
            throw new Error("No valid PhpStorm version folders found in Toolbox directory.");
        }

        settings.folder_name = sanitizeString(maxVersionFolder);

        // Read version name and product name from product-info.json
        var versionFilePath = toolboxDirectory + settings.folder_name + "\\product-info.json";
        if (!fso.FileExists(versionFilePath)) {
            throw new Error("product-info.json does not exist: " + versionFilePath);
        }

        var versionFile = fso.OpenTextFile(versionFilePath, 1, false);
        var content = versionFile.ReadAll();
        versionFile.Close();

        // Requires JScript 5.8 or newer, >= IE8.0, >= XP SP2 (March 2009)
        var productVersion = JSON.parse(content); // Safe parsing
        settings.window_title = 'PhpStorm ' + sanitizeString(productVersion.version);
    } catch (error) {
        // Handle errors appropriately, possibly logging them
        var errorShell = new ActiveXObject('WScript.Shell');
        errorShell.Popup("An error occurred in configuring Toolbox settings: " + sanitizeString(error.message), 0, "PhpStorm Script Error", 0x10);
    }
}

function getToolboxShellScript(appDataLocal) {
    try {
        var shellScript = appDataLocal + '\\JetBrains\\Toolbox\\scripts\\' + sanitizeString(settings.toolbox_shell_script);
        var fso = new ActiveXObject('Scripting.FileSystemObject');

        if (fso.FileExists(shellScript)) {
            return sanitizePath(shellScript);
        }

        return undefined;
    } catch (error) {
        return undefined;
    }
}

// Utility function to sanitize file paths
function sanitizePath(path) {
    // Remove any potential dangerous characters
    return path.replace(/["<>|&;]/g, '');
}

// Utility function to sanitize strings
function sanitizeString(str) {
    return String(str).replace(/["<>|&;]/g, '');
}

// Utility function to escape RegExp special characters
function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}
