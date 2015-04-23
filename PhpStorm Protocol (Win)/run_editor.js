var settings = {
	// Set to 'true' (without quotes) if run on Windows 64bit. Set to 'false' (without quotes) otherwise.
	x64: true,

	// Set to folder name, where PhpStorm was installed to (e.g. 'PhpStorm')
	folder_name: 'PhpStorm 8.0.3',

	// Set to window title (only text after dash sign), that you see, when switching to running PhpStorm instance
	window_title: 'PhpStorm 8.0.3'
};


// don't change anything below this line, unless you're know what you're doing
var url = WScript.Arguments(0),
	match = /^pstorm:\/\/open\/\?url=file:\/\/(.+)&line=(\d+)$/.exec(url),
	editor = '"c:\\' + (settings.x64 ? 'Program Files (x86)' : 'Program Files') + '\\JetBrains\\' + settings.folder_name + '\\bin\\PhpStorm.exe" --line %line% "%file%"';

if ( match ) {
	var	shell = new ActiveXObject('WScript.Shell'),
		file = decodeURIComponent(match[1]).replace(/\+/g, ' '),
		command = editor.replace(/%line%/g, match[2]).replace(/%file%/g, file).replace(/\//g, '\\')/*.replace(/\\/g, '\\\\')*/;

	shell.Exec(command);
	shell.AppActivate(settings.window_title);
}
