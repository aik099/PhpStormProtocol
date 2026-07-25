var settings = {
    // Set to 'true' (without quotes) if run on Windows 64bit. Set to 'false' (without quotes) otherwise.
    x64: true,

    // Set to disk letter, where PhpStorm was installed to (e.g. C:)
    disk_letter: 'C:',

    // (only, when not using JetBrains Toolbox) Set to folder name, where PhpStorm was installed to (e.g. 'PhpStorm')
    folder_name: '<phpstorm_folder_name>',

    // In case your file is mapped via a network share and paths do not match.
    // eg. /var/www will can replaced with Y:/
    projects_basepath: '',
    projects_path_alias: '',

    // PhpStorm directory name in Toolbox directory
    // eg. for C:\Users\%username%\AppData\Local\JetBrains\Toolbox\apps\PhpStorm\ch-1 use 'ch-1'
    // Leave null to use the first PHPStorm version in Toolbox
    toolbox_update_channel_dir: null,

    // Set to PhpStorm shell script (filename ends with "*.cmd") from the "C:\Users\%username%\AppData\Local\JetBrains\Toolbox\scripts" directory.
    toolbox_shell_script: 'PhpStorm.cmd'
};

// don't change anything below this line, unless you know what you're doing
var url = WScript.Arguments(0),
    match = /^phpstorm:\/\/open\/?\?(url=file:\/\/|file=)(.+?)(?:&line=(\d+))?$/.exec(url),
    project = '';

// add JSON support
includeFile('json2.js');

if (toolbox_v1_isInstalled()) {
    toolbox_v1_configureSettings(settings);
}

if (match) {
    var shell = new ActiveXObject('WScript.Shell'),
        file_system = new ActiveXObject('Scripting.FileSystemObject'),
        file = decodeURIComponent(match[ 2 ]).replace(/\+/g, ' '),
        search_path = file.replace(/\//g, '\\'),
        editor = '"' + getPhpStormCommandPath() + '"';

    if (settings.projects_basepath !== '' && settings.projects_path_alias !== '') {
        file = file.replace(new RegExp('^' + settings.projects_basepath), settings.projects_path_alias);
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
        editor += ' "%project%"';
    }

    if (match[3]) {
        editor += ' --line %line% "%file%"';
    } else if (isFile) {
        editor += ' "%file%"';
    }

    var command = editor.replace(/%line%/g, match[3] || '')
        .replace(/%file%/g, file)
        .replace(/%project%/g, project)
        .replace(/\//g, '\\');

    shell.Exec(command);
}

// Checks for the "apps\<Product>" folder — a false negative on Toolbox 2.0+/3.x (confirmed on
// 3.6.2), which no longer creates it. Not an authoritative "is Toolbox present" check;
// getPhpStormCommandPath() below has its own, separate detection.
function toolbox_v1_isInstalled() {
    var shell = new ActiveXObject('WScript.Shell'),
        appDataLocal = shell.ExpandEnvironmentStrings("%localappdata%"),
        toolboxDirectory = appDataLocal + '\\JetBrains\\Toolbox\\apps\\PhpStorm';

    return (new ActiveXObject('Scripting.FileSystemObject')).FolderExists(toolboxDirectory);
}

// Resolves the PhpStorm launcher. Tries, in priority order:
//   0. settings.toolbox_v1_commandPath, if toolbox_v1_configureSettings() already resolved one. Kept
//      as a dedicated field, separate from settings.folder_name/the fallback formula below, since
//      that formula is for a different resolution path (standalone installs).
//   1. toolbox_common_getShellScript(), if found (confirmed working on Toolbox 1.28.2 once managed,
//      and on 3.6.2).
//   2. Toolbox's state.json parsed directly — picks the *first* entry with toolId "PhpStorm",
//      ignoring settings.toolbox_update_channel_dir (confirmed: with multiple channels installed,
//      this can resolve a different PhpStorm build than #1 does on the same machine).
//   3. The disk_letter + folder_name fallback, for standalone installs.
function getPhpStormCommandPath() {
    if (settings.toolbox_v1_commandPath !== undefined) {
        return settings.toolbox_v1_commandPath;
    }

    var shell = new ActiveXObject('WScript.Shell'),
        appDataLocal = shell.ExpandEnvironmentStrings("%localappdata%"),
        toolboxShellScript = toolbox_common_getShellScript(appDataLocal);

    if (toolboxShellScript !== undefined) {
        return toolboxShellScript;
    }

    var settingsStateFile = appDataLocal + '\\JetBrains\\Toolbox\\state.json',
        defaultCommandPath = settings.disk_letter + '\\' + ( settings.x64 ? 'Program Files' : 'Program Files (x86)' ) + '\\JetBrains\\' + settings.folder_name + ( settings.x64 ? '\\bin\\phpstorm64.exe' : '\\bin\\phpstorm.exe' );

    try {
        var fileStream = (new ActiveXObject('Scripting.FileSystemObject')).OpenTextFile(settingsStateFile, 1, false);
    } catch (error) {
        return defaultCommandPath;
    }

    var state = JSON.parse(fileStream.ReadAll());
    fileStream.Close();

    var tools = state.tools || [];
    for (var i = 0; i < tools.length; i++) {
        if (tools[i].toolId === 'PhpStorm') {
            var basePath = tools[i].launchCommand.indexOf(tools[i].installLocation) === -1 ? tools[i].installLocation +  "\\" : "";

            return (basePath + tools[i].launchCommand).replace(/\//g, "\\");
        }
    }

    return defaultCommandPath;
}

// Reads ".settings.json"'s favorite-channel ordering. Only consulted by toolbox_v1_configureSettings()
// — NOT by getPhpStormCommandPath()'s state.json branch, so on Toolbox 2.0+ with multiple installed
// channels this setting has no effect on which build actually launches.
function toolbox_v1_getFavoriteChannel() {
    var shell = new ActiveXObject('WScript.Shell'),
        appDataLocal = shell.ExpandEnvironmentStrings("%localappdata%"),
        settingsFile = appDataLocal + '\\JetBrains\\Toolbox\\.settings.json';

    try {
        var fileStream = (new ActiveXObject('Scripting.FileSystemObject')).OpenTextFile(settingsFile, 1, false);
    } catch (error) {
        return 'ch-0';
    }

    var settings = JSON.parse(fileStream.ReadAll());
    fileStream.Close()

    var apps = (settings.ordering || {}).local || [];
    for (var i = 0; i < apps.length; i++) {
        if (apps[i].application_id === 'PhpStorm') {
            return apps[i].channel_id;
        }
    }

    return 'ch-0'
}

// Only runs when toolbox_v1_isInstalled() found the "apps\PhpStorm" folder — never on Toolbox
// 2.0+/3.x (confirmed false on 3.6.2). Scans "apps\PhpStorm\<channel>\<version>\" for the newest
// installed version and stores the result in settings.toolbox_v1_commandPath.
function toolbox_v1_configureSettings(settings) {
    var shell = new ActiveXObject('WScript.Shell'),
        appDataLocal = shell.ExpandEnvironmentStrings("%localappdata%"),
        toolboxShellScript = toolbox_common_getShellScript(appDataLocal);

    // Skip the folder scan below when a shell script exists — getPhpStormCommandPath() will use it
    // directly instead. (Window activation itself is handled by PhpStorm's own single-instance
    // relaunch behavior, confirmed to work the same way whether launched via this shell script or a
    // direct exe invocation — not something specific to the shell script.)
    if (toolboxShellScript !== undefined) {
        return;
    }

    // Detect Toolbox PHPStorm top channel
    if (settings.toolbox_update_channel_dir == null) {
        settings.toolbox_update_channel_dir = toolbox_v1_getFavoriteChannel();
    }

    var toolboxDirectory = appDataLocal + '\\JetBrains\\Toolbox\\apps\\PhpStorm\\' + settings.toolbox_update_channel_dir + '\\';

    // Reference the FileSystemObject
    var fso = new ActiveXObject('Scripting.FileSystemObject');

    // Reference the Text directory
    var folder = fso.GetFolder(toolboxDirectory);

    // Reference the File collection of the Text directory
    var fileCollection = folder.SubFolders;

    var maxMajor = 0,
        maxMinor = 0,
        maxPatch = 0,
        maxVersionFolder = "";

    // Traverse through the fileCollection using the FOR loop
    // read the maximum version from toolbox filesystem
    for (var objEnum = new Enumerator(fileCollection); !objEnum.atEnd(); objEnum.moveNext()) {
        var folderObject = ( objEnum.item() );

        if (folderObject.Name.lastIndexOf('plugins') !== -1) {
            continue;
        }

        var versionMatch = /(\d+)\.(\d+)\.(\d+)/.exec(folderObject.Name),
            major = parseInt(versionMatch[ 1 ]),
            minor = parseInt(versionMatch[ 2 ]),
            patch = parseInt(versionMatch[ 3 ]);

        if (maxMajor === 0 || maxMajor <= major) {
            if (maxMajor < major) {
                maxMinor = 0;
                maxPatch = 0;
            }
            maxMajor = major;

            if (maxMinor === 0 || maxMinor <= minor) {
                if (maxMinor < minor) {
                    maxPatch = 0;
                }
                maxMinor = minor;

                if (maxPatch === 0 || maxPatch <= patch) {
                    maxPatch = patch;
                    maxVersionFolder = folderObject.Name;
                }
            }
        }
    }

    settings.folder_name = maxVersionFolder;

    // read launcher path from product-info.json
    var versionFile = fso.OpenTextFile(toolboxDirectory + settings.folder_name + "\\product-info.json", 1, true);
    var productVersion = JSON.parse(versionFile.ReadAll());
    settings.toolbox_v1_commandPath = toolboxDirectory + settings.folder_name + '\\' + productVersion.launch[ 0 ].launcherPath.replace(/\//g, '\\');
}

// Checks whether Toolbox generated a launcher script (scripts\<toolbox_shell_script>). Used by both
// getPhpStormCommandPath() and toolbox_v1_configureSettings() (to skip its folder scan).
function toolbox_common_getShellScript(appDataLocal) {
    var shellScript = appDataLocal + '\\JetBrains\\Toolbox\\scripts\\' + settings.toolbox_shell_script;

    if ((new ActiveXObject('Scripting.FileSystemObject')).FileExists(shellScript)) {
        return shellScript;
    }

    return undefined;
}

function includeFile (filename) {
    var fso = new ActiveXObject ("Scripting.FileSystemObject");
    var currentDirectory = WScript.ScriptFullName.substring(0, WScript.ScriptFullName.lastIndexOf('\\'));
    var fileStream = fso.openTextFile (currentDirectory + '\\' + filename);
    var fileData = fileStream.readAll();
    fileStream.Close();
    eval(fileData);
}
