<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex">

	<title>Bugsnuggle</title>

	<link rel="shortcut icon" href="favicon.ico">
	<link href="assets/css/bootstrap.min.css" rel="stylesheet">
	<link href="assets/css/bootstrap-flat.min.css" rel="stylesheet">
	<link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
	<nav class="navbar navbar-default">
		<div class="container">
			<a class="navbar-brand" href="index.php">Bugsnuggle</a>

			<ul class="nav navbar-nav">
				<li><a href="projects.php">Projects</a></li>
				<li class="active"><a href="index.php">Errors</a></li>
			</ul>
		</div>
	</nav>

	<div class="container">
<?php
	require __DIR__ . '/timeago.inc.php';

	$Database = require __DIR__ . '/db.php';

	if( isset( $_POST[ 'delete_event' ] ) )
	{
		$EventID = filter_input( INPUT_POST, 'delete_event', FILTER_SANITIZE_STRING );

		$Delete = $Database->prepare( 'DELETE FROM `events` WHERE `hash` = ?' );
		$Delete->execute( [ $EventID ] );
	}

	$ProjectID = (int)filter_input( INPUT_GET, 'project', FILTER_SANITIZE_NUMBER_INT );

	if( $ProjectID > 0 )
	{
		$Events = $Database->prepare( 'SELECT `hash`, `event_json`, `date` FROM `events` WHERE `project_id` = ? GROUP BY `hash` ORDER BY `events`.`id` DESC' );
		$Events->execute( [ $ProjectID ] );
	}
	else
	{
		$Events = $Database->query( 'SELECT `hash`, `event_json`, `projects`.`name`, `date` FROM `events` JOIN `projects` on `project_id` = `projects`.`id` GROUP BY `hash` ORDER BY `events`.`id` DESC' );
	}

	$Events = $Events->fetchAll();

	$DetailsQuery = $Database->prepare( 'SELECT COUNT(*) as `count` FROM `events` WHERE `hash` = ?' );

	if( empty( $Events ) )
	{
		echo '<div class="alert alert-info">No errors have been collected yet. <a href="https://docs.bugsnag.com/platforms/">View Bugsnag documentation on how to setup error reporting in your application.</a></div>';
	}

	foreach( $Events as $Event )
	{
		$Project = $Event[ 'name' ] ?? null;
		$EventID = $Event[ 'hash' ];
		$Date = $Event[ 'date' ];
		$Event = json_decode( $Event[ 'event_json' ], true );

		$DetailsQuery->execute( [ $EventID ] );

		$Details = $DetailsQuery->fetch();

		switch( $Event[ 'severity' ] )
		{
			case 'error': $PanelClass = 'danger'; break;
			case 'warning': $PanelClass = 'warning'; break;
			default: $PanelClass = 'default';
		}

		switch( $Event[ 'app' ][ 'releaseStage' ] )
		{
			case 'production': $LabelClass = 'danger'; break;
			case 'staging': $LabelClass = 'warning'; break;
			default: $LabelClass = 'default';
		}

		$Exception = $Event[ 'exceptions' ][ 0 ];

		echo '
		<div class="panel panel-' . $PanelClass . '">
			<div class="panel-heading">
				<div class="pull-right">
					<span class="label label-' . $PanelClass . '">' . ucfirst( $Event[ 'severity' ] ) . '</span>
					<span class="label label-' . $LabelClass . '">' . ucfirst( $Event[ 'app' ][ 'releaseStage' ] ) . '</span>
				</div>

				' . ( $Project ? htmlspecialchars( $Project ) . ' · ' : '' ) . '<b>' . htmlspecialchars( $Exception[ 'errorClass' ] ) . '</b> · ' . htmlspecialchars( empty( $Event[ 'context' ] ) ? '' : $Event[ 'context' ] ) . '
			</div>
			<div class="panel-body">
				<form action="index.php" method="post">
					<input type="hidden" name="delete_event" value="' . $EventID . '">
					<button type="submit" class="pull-right btn btn-link">Delete</button>
				</form>


				<a class="btn-group pull-right" href="event.php?id=' . $EventID . '">
					<span class="btn btn-' . ( $Details[ 'count' ] < 10 ? 'primary' : 'danger' ) . '">' . $Details[ 'count' ] . ' occurrence' . ( $Details[ 'count' ] == 1 ? '' : 's' ) . '</span>
					<span class="btn btn-default">View details</span>
				</a>

				<p>' . htmlspecialchars( $Exception[ 'message' ] ) . '</p>
				<p class="text-muted">Last event ' . timeAgoInWords( $Date ) . '</p>
			</div>
		</div>
		';
	}
?>
	</div>

	<footer class="bs-docs-footer" role="contentinfo">
		<div class="container">
			<p>Page rendered in <span class="text-success"><?=number_format( microtime( true ) - $_SERVER[ 'REQUEST_TIME_FLOAT' ], 5 );?></span> seconds.</p>
			<p>Made by <a rel="author" href="https://xpaw.me">xPaw</a>. Code licensed under <a rel="license" href="https://github.com/xPaw/Bugsnuggle/blob/master/LICENSE">MIT</a> and is available on <a href="https://github.com/xPaw/Bugsnuggle">GitHub</a>.</p>
			<p>Bugsnag notifier clients are © Bugsnag Inc.</p>
		</div>
	</footer>
</body>
</html>
