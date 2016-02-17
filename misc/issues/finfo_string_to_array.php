<?php

$finfo = finfo_open(FILEINFO_MIME);

$files = [
	__FILE__, // ok
	'array_object.php', // ok
	'C:/Windows/system.ini', // ok
	'D:\\htdocs\\jquery\\README.md', // this will appear notice "Array to string conversion"
];

foreach ($files as $file) {
	var_dump(finfo_file($finfo, $file));
}

finfo_close($finfo);