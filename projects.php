<?php
	$Database = require __DIR__ . '/db.php';
?><!DOCTYPE html>
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
				<li class="active"><a href="projects.php">Projects</a></li>
				<li><a href="index.php">Errors</a></li>
			</ul>
		</div>
	</nav>

	<div class="container">
<?php
	if( !empty( $_POST ) )
	{
		$Action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING );

		switch( $Action )
		{
			case 'create':
			{
				$Name = trim( filter_input( INPUT_POST, 'name', FILTER_SANITIZE_STRING ) );

				if( empty( $Name ) )
				{
					echo '<div class="alert alert-danger">Missing project name</div>';
				}
				else
				{
					$ApiKey = bin2hex( random_bytes( 16 ) );

					$NewProject = $Database->prepare( 'INSERT INTO `projects` (`api_key`, `name`) VALUES (?, ?)' );
					$NewProject->execute( [ $ApiKey, $Name ] );

					echo '<div class="alert alert-success">Project created. Notifier API Key: ' . $ApiKey . '</div>';
				}

				break;
			}
			case 'delete':
			{
				$ProjectID = filter_input( INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT );
				$ApiKey = filter_input( INPUT_POST, 'api_key', FILTER_SANITIZE_STRING );

				$DeleteProject = $Database->prepare( 'DELETE FROM `projects` WHERE `id` = ? AND `api_key` = ?' );
				$DeleteProject->execute( [ $ProjectID, $ApiKey ] );

				echo '<div class="alert alert-success">Project has been deleted.</div>';

				break;
			}
			case 'regenerate':
			{
				$ProjectID = filter_input( INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT );
				$ApiKey = filter_input( INPUT_POST, 'api_key', FILTER_SANITIZE_STRING );
				$NewApiKey = bin2hex( random_bytes( 16 ) );

				$UpdateProject = $Database->prepare( 'UPDATE `projects` SET `api_key` = ? WHERE `id` = ? AND `api_key` = ?' );
				$UpdateProject->execute( [ $NewApiKey, $ProjectID, $ApiKey ] );

				echo '<div class="alert alert-success">New Notifier API Key: ' . $ApiKey . '</div>';

				break;
			}
			case 'clearerrors':
			{
				$ProjectID = filter_input( INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT );
				$ApiKey = filter_input( INPUT_POST, 'api_key', FILTER_SANITIZE_STRING );

				$UpdateProject = $Database->prepare( 'DELETE FROM `events` WHERE `project_id` = (SELECT `id` FROM `projects` WHERE `id` = ? AND `api_key` = ?)' );
				$UpdateProject->execute( [ $ProjectID, $ApiKey ] );

				echo '<div class="alert alert-success">Errors have been cleared.</div>';

				break;
			}
			default:
			{
				echo '<div class="alert alert-danger">Unknown action.</div>';
			}
		}
	}

	$Projects = $Database->query( 'SELECT * FROM `projects`' );
	$Projects = $Projects->fetchAll();

	foreach( $Projects as $Project )
	{
		echo '
			<form class="panel panel-default" action="projects.php" method="post">
				<div class="panel-body">
				<input type="hidden" name="id" value="' . $Project[ 'id' ] . '">

				<div class="form-group">
					<label for="js-project-name-' . $Project[ 'id' ] . '">Project name</label>
					<input type="text" maxlength="100" class="form-control" id="js-project-name-' . $Project[ 'id' ] . '" placeholder="Project name" name="name" value="' . htmlspecialchars( $Project[ 'name' ] ) . '" disabled>
				</div>

				<div class="form-group">
					<label for="js-project-key-' . $Project[ 'id' ] . '">Notifier API key</label>
					<input type="text" class="form-control" id="js-project-key-' . $Project[ 'id' ] . '" value="' . htmlspecialchars( $Project[ 'api_key' ] ) . '" disabled>
					<input type="hidden" name="api_key" value="' . htmlspecialchars( $Project[ 'api_key' ] ) . '">
				</div>

				<div class="pull-right">
					<button class="btn btn-danger" type="submit" name="action" value="clearerrors" onClick="return confirm(\'Are you sure you want to clear all errors in this project?\n\nThere is no going back.\');">Clear errors</button>
					<button class="btn btn-danger" type="submit" name="action" value="regenerate" onClick="return confirm(\'This will regenerate your API key, and invalidate the existing key.\n\nThere is no going back.\');">Regenerate key</button>
					<button class="btn btn-danger" type="submit" name="action" value="delete" onClick="return confirm(\'Are you sure you want to delete this project?\n\nThere is no going back.\');">Delete</button>
				</div>

				<button class="btn btn-primary" type="submit" name="action" value="save" style="display:none">Save</button>
				<a href="index.php?project=' . $Project[ 'id' ] . '" class="btn btn-primary">View errors</a>

				</div>
			</form>
		';
	}
?>
		</table>

		<form class="panel panel-success" action="projects.php" method="post">
			<div class="panel-heading">
				Create a new project
			</div>

			<div class="panel-body">
				<div class="form-group">
					<label for="js-project-name">Project name</label>
					<input type="text" maxlength="100" class="form-control" id="js-project-name" placeholder="Project name" name="name" required>
				</div>

				<button type="submit" class="btn btn-default" name="action" value="create">Create</button>
			</div>
		</form>
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
