<?php

$Config =
[
	'db' => 'sqlite:' . __DIR__ . DIRECTORY_SEPARATOR . 'errors.sqlite',
	'db_user' => '',
	'db_pass' => '',
];

return @new PDO(
	$Config[ 'db' ],
	$Config[ 'db_user' ],
	$Config[ 'db_pass' ],
	[
		PDO::ATTR_TIMEOUT            => 1,
		PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		//PDO::ATTR_PERSISTENT         => true
	]
);
