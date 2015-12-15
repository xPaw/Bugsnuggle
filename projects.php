<?php
    $Database = require __DIR__ . '/db.php';
    
    $Projects = $Database->query( 'SELECT * FROM `projects`' );
    $Projects = $Projects->fetchAll();
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <title>Bugsnuggle</title>
    
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
    foreach( $Projects as $Project )
    {
        
        echo '
            <form class="panel panel-default" action="projects.php" method="post">
                <div class="panel-body">
                <input type="hidden" name="id" value="' . $Project[ 'id' ] . '">
                
                <div class="form-group">
                    <label for="js-project-name-' . $Project[ 'id' ] . '">Project name</label>
                    <input type="text" maxlength="100" class="form-control" id="js-project-name-' . $Project[ 'id' ] . '" placeholder="Project name" name="name" value="' . htmlspecialchars( $Project[ 'name' ] ) . '">
                </div>
                
                <div class="form-group">
                    <label for="js-project-key-' . $Project[ 'id' ] . '">API key</label>
                    <input type="text" class="form-control" id="js-project-key-' . $Project[ 'id' ] . '" value="' . htmlspecialchars( $Project[ 'api_key' ] ) . '" disabled>
                </div>
                
                <div class="pull-right">
                    <button class="btn btn-danger" type="submit" name="action" type="clearerrors" onClick="return confirm(\'Are you sure you want to clear all errors in this project?\n\nThere is no going back.\');">Clear errors</button>
                    <button class="btn btn-danger" type="submit" name="action" type="regenerate" onClick="return confirm(\'This will regenerate your API key, and invalidate the existing key.\n\nAre you sure you want to do this?\');">Regenerate key</button>
                    <button class="btn btn-danger" type="submit" name="action" type="delete" onClick="return confirm(\'Are you sure you want to delete this project?\n\nThere is no going back.\');">Delete</button>
                </div>
                
                <button class="btn btn-primary" type="submit" name="action" type="save">Save</button>
                
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
            
            <input type="hidden" name="action" value="create">
            
            <div class="panel-body">
                <div class="form-group">
                    <label for="js-project-name">Project name</label>
                    <input type="text" maxlength="100" class="form-control" id="js-project-name" placeholder="Project name" name="name">
                </div>
                
                <button type="submit" class="btn btn-default">Create</button>
            </div>
        </form>
    </div>
    
    <footer class="bs-docs-footer" role="contentinfo">
        <div class="container">
            <p>Page rendered in <span class="text-success"><?=number_format( microtime( true ) - $_SERVER[ 'REQUEST_TIME_FLOAT' ], 5 );?></span> seconds.</p>
            <p>Made by <a rel="author" href="https://xpaw.me">xPaw</a>. Code licensed under <a rel="license" href="https://github.com/xPaw/Bugsnuggle/blob/master/LICENSE" target="_blank">MIT</a> and is available on <a href="https://github.com/xPaw/Bugsnuggle">GitHub</a>.</p>
            <p>Bugsnag notifier clients are © Bugsnag Inc.</p>
        </div>
    </footer>
</body>
</html>
