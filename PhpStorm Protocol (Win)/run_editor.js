var settings = {
	// Set to 'true' (without quotes) if run on Windows 64bit. Set to 'false' (without quotes) otherwise.
	x64: true,

	// Set to folder name, where PhpStorm was installed to (e.g. 'PhpStorm')
	folder_name: 'PhpStorm 2017.1.4',

	// Set to window title (only text after dash sign), that you see, when switching to running PhpStorm instance
	window_title: 'PhpStorm 2017.1.4',

	// In case your file is mapped via a network share and paths do not match.
	// eg. /var/www will can replaced with Y:/
	projects_basepath: '',
	projects_path_alias: ''
};


// don't change anything below this line, unless you know what you're doing
var	url = WScript.Arguments(0),
	match = /^phpstorm:\/\/open\/?\?(url=file:\/\/|file=)(.+)&line=(\d+)$/.exec(url),
	project = '',
	editor = '"C:\\' + (settings.x64 ? 'Program Files' : 'Program Files (x86)') + '\\JetBrains\\' + settings.folder_name + '\\bin\\phpstorm.exe"';

if (match) {

	var	shell = new ActiveXObject('WScript.Shell'),
		file_system = new ActiveXObject('Scripting.FileSystemObject'),
		file = decodeURIComponent(match[2]).replace(/\+/g, ' '),
		search_path = file.replace(/\//g, '\\');

	if (settings.projects_basepath != '' && settings.projects_path_alias != '') {
		file = file.replace(new RegExp('^' + settings.projects_basepath), settings.projects_path_alias);
	}

	while (search_path.lastIndexOf('\\') != -1) {
		search_path = search_path.substring(0, search_path.lastIndexOf('\\'));

		if(file_system.FileExists(search_path+'\\.idea\\.name')) {
			project = search_path;
			break;
		}
	}

	if (project != '') {
		editor += ' "%project%"';
	}

	editor += ' --line %line% "%file%"';

	var command = editor.replace(/%line%/g, match[3])
						.replace(/%file%/g, file)
						.replace(/%project%/g, project)
						.replace(/\//g, '\\');

	shell.Exec(command);
	shell.AppActivate(settings.window_title);
}
