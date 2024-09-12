var settings = {
    // Set to 'true' (without quotes) if run on Windows 64bit. Set to 'false' (without quotes) otherwise.
    x64: true,

    // Set to disk letter, where PhpStorm was installed to (e.g. C:)
    disk_letter: 'C:',

    // Set to folder name, where PhpStorm was installed to (e.g. 'PhpStorm')
    folder_name: '<phpstorm_folder_name>',

    // Set to window title (only text after dash sign), that you see, when switching to running PhpStorm instance
    window_title: '<phpstorm_window_title>',

    // In case your file is mapped via a network share and paths do not match.
    // eg. /var/www will can replaced with Y:/
    projects_basepath: '',
    projects_path_alias: '',

    // PhpStorm directory name in Toolbox directory
    // eg. for C:\Users\%username%\AppData\Local\JetBrains\Toolbox\apps\PhpStorm\ch-1 use 'ch-1'
    // Leave null to use the first PHPStorm version in Toolbox
    toolbox_update_channel_dir: null
};

// flag to active Jetbrain Toolbox configuration
settings.toolBoxActive = isToolboxInstalled();


// don't change anything below this line, unless you know what you're doing
var url = WScript.Arguments(0),
    match = /^phpstorm:\/\/open\/?\?(url=file:\/\/|file=)(.+?)(?:&line=(\d+))?$/.exec(url),
    project = '';

// add JSON support
includeFile('json2.js');

if (settings.toolBoxActive) {
    configureToolboxSettings(settings);
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
    shell.AppActivate(settings.window_title);
}

function isToolboxInstalled() {
    var shell = new ActiveXObject('WScript.Shell'),
        appDataLocal = shell.ExpandEnvironmentStrings("%localappdata%"),
        toolboxDirectory = appDataLocal + '\\JetBrains\\Toolbox\\apps\\PhpStorm';

    return (new ActiveXObject('Scripting.FileSystemObject')).FolderExists(toolboxDirectory);
}

function getPhpStormCommandPath() {
    var shell = new ActiveXObject('WScript.Shell'),
        appDataLocal = shell.ExpandEnvironmentStrings("%localappdata%"),
        settingsStateFile = appDataLocal + '\\JetBrains\\Toolbox\\state.json',
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
        if (tools[i].toolId == 'PhpStorm') {
            return tools[i].installLocation + '\\' + tools[i].launchCommand.replace(/\//g, '\\');
        }
    }

    return defaultCommandPath;
}

function getFavoritePhpStormChannel() {
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
        if (apps[i].application_id == 'PhpStorm') {
            return apps[i].channel_id;
        }
    }

    return 'ch-0'
}

function configureToolboxSettings(settings) {
    // Detect Toolbox PHPStorm top channel
    if (settings.toolbox_update_channel_dir == null) {
        settings.toolbox_update_channel_dir = getFavoritePhpStormChannel();
    }

    var shell = new ActiveXObject('WScript.Shell'),
        appDataLocal = shell.ExpandEnvironmentStrings("%localappdata%"),
        toolboxDirectory = appDataLocal + '\\JetBrains\\Toolbox\\apps\\PhpStorm\\' + settings.toolbox_update_channel_dir + '\\';

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

    // read version name and product name from product-info.json
    var versionFile = fso.OpenTextFile(toolboxDirectory + settings.folder_name + "\\product-info.json", 1, true);
    var content = versionFile.ReadAll();

    eval('var productVersion = ' + content + ';');
    settings.window_title = 'PhpStorm ' + productVersion.version;
    editor = '"' + toolboxDirectory + settings.folder_name + '\\' + productVersion.launch[ 0 ].launcherPath.replace(/\//g, '\\') + '"';
}

function includeFile (filename) {
    var fso = new ActiveXObject ("Scripting.FileSystemObject");
    var currentDirectory = WScript.ScriptFullName.substring(0, WScript.ScriptFullName.lastIndexOf('\\'));
    var fileStream = fso.openTextFile (currentDirectory + '\\' + filename);
    var fileData = fileStream.readAll();
    fileStream.Close();
    eval(fileData);
}
