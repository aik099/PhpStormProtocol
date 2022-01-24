var settings = {

    // In case your file is mapped via a network share and paths do not match.
    // eg. /var/www will can replaced with Y:/
    projects_basepath   : '',
    projects_path_alias : ''

};

// don't change anything below this line, unless you know what you're doing
var url   = WScript.Arguments(0),
    match = /^phpstorm:\/\/open\/?\?(url=file:\/\/|file=)(.+)&line=(\d+)$/.exec(url);

if (match) {

    var shell       = new ActiveXObject('WScript.Shell'),
        file_system = new ActiveXObject('Scripting.FileSystemObject'),
        file        = decodeURIComponent(match[2]).replace(/\+/g, ' '),
        search_path = file.replace(/\//g, '\\'),
        project     = '';

    if (settings.projects_basepath !== '' && settings.projects_path_alias !== '') {
        file = file.replace(new RegExp('^' + settings.projects_basepath), settings.projects_path_alias);
    }

    while (search_path.lastIndexOf('\\') !== -1) {
        search_path = search_path.substring(0, search_path.lastIndexOf('\\'));

        if (file_system.FileExists(search_path + '\\.idea\\.name')) {
            project = search_path;
            break;
        }
    }


    var fileSystemObj = new ActiveXObject('Scripting.FileSystemObject'),
        process_name  = 'phpstorm.exe';

        // see if is installed on a 64 path or 32 path...
        for (var objEnum = new Enumerator(['C:\\Program Files (x86)\\JetBrains', 'C:\\Program Files\\JetBrains']); !objEnum.atEnd(); objEnum.moveNext()) {

            var jetBrainsPath = objEnum.item();

            if (fileSystemObj.FolderExists(jetBrainsPath)) {
                // get PHPStorm folder name from JetBrains path
                for (var objEnum = new Enumerator(fileSystemObj.GetFolder(jetBrainsPath).SubFolders); !objEnum.atEnd(); objEnum.moveNext()) {
                    var folderObject = objEnum.item();

                    // see if is installed as 64 or 32
                    if (fileSystemObj.FileExists(folderObject.Path + '\\bin\\phpstorm64.exe')) {
                        process_name = 'phpstorm64.exe';
                    }

                    var editor = folderObject.Path + '\\bin\\' + process_name;
                    //debug(editor);
                }
            }
        }


    if (project !== '') {
        editor += ' "%project%"';
    }

    editor += ' --line %line% "%file%"';

    var command = editor.replace(/%line%/g, match[3])
        .replace(/%file%/g, file)
        .replace(/%project%/g, project)
        .replace(/\//g, '\\');

    shell.Exec(command);

    var locator     = new ActiveXObject('WbemScripting.SWbemLocator'),
        service     = locator.ConnectServer('.', '/root/CIMV2'),
        processes   = service.ExecQuery('Select * from Win32_Process WHERE Name = "' + process_name + '"');

        // focus on editor based process id
        shell.AppActivate((new Enumerator(processes)).item(0).ProcessId);
}

function debug(text) {
    return (new ActiveXObject('WScript.Shell')).Popup(text, 0, 'Debug', 48);
}
