var settings = {
	// Set to 'true' (without quotes) if run on Windows 64bit. Set to 'false' (without quotes) otherwise.
	x64: true,

	// Set to folder name, where PhpStorm was installed to (e.g. 'PhpStorm')
	folder_name: 'PhpStorm 8.0.3',

	// Set to window title (only text after dash sign), that you see, when switching to running PhpStorm instance
	window_title: 'PhpStorm 8.0.3'
};


// don't change anything below this line, unless you know what you're doing
var	url = WScript.Arguments(0),
	match = /^pstorm:\/\/open\/\?url=file:\/\/(.+)&line=(\d+)$/.exec(url),
	project = '',
	editor = '"C:\\' + (settings.x64 ? 'Program Files (x86)' : 'Program Files') + '\\JetBrains\\' + settings.folder_name + '\\bin\\PhpStorm.exe"';

if (match) {

	var	shell = new ActiveXObject('WScript.Shell'),
		file_system = new ActiveXObject('Scripting.FileSystemObject'),
		file = decodeURIComponent(match[1]).replace(/\+/g, ' '),
		search_path = file.replace(/\//g, '\\');

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

	var command = editor.replace(/%line%/g, match[2])
						.replace(/%file%/g, file)
						.replace(/%project%/g, project)
						.replace(/\//g, '\\');

	shell.Exec(command);
	shell.AppActivate(settings.window_title);
}
