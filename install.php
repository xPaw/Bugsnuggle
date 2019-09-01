<?php

$Database = require __DIR__ . '/db.php';

$Tables =
[

'CREATE TABLE `events` (
	`id`	INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
	`project_id` INTEGER NOT NULL,
	`hash`	TEXT NOT NULL,
	`severity`	TEXT NOT NULL,
	`release_stage`	TEXT NOT NULL,
	`event_json`	TEXT NOT NULL,
	`date`	TEXT NOT NULL
);',

'CREATE TABLE `projects` (
	`id`	INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
	`api_key`	TEXT NOT NULL,
	`name`	TEXT NOT NULL
);',

'CREATE UNIQUE INDEX `event_id` ON `events` (`id`)',
'CREATE INDEX `event_date` ON `events` (`date` DESC)',
'CREATE INDEX `event_hash` ON `events` (`hash` DESC)',

'CREATE UNIQUE INDEX `project_id` ON `projects` (`id`)',
'CREATE UNIQUE INDEX `project_key` ON `projects` (`api_key`)',

];

foreach( $Tables as $Table )
{
	$Database->query( $Table );
}
