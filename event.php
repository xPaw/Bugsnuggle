<?php
    $EventID = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_STRING );
    
    $Database = require __DIR__ . '/db.php';
    
    $Event = $Database->prepare( 'SELECT `hash`, `event_json`, `date` FROM `events` WHERE `hash` = ? ORDER BY `id` ASC LIMIT 1' );
    $Event->execute( [ $EventID ] );
    $Event = $Event->fetch();
    
    if( !$Event )
    {
        header( 'Location: index.php' );
        
        exit;
    }
    
    $EventID = $Event[ 'hash' ];
    $Date = $Event[ 'date' ];
    $Event = $RawPayload = json_decode( $Event[ 'event_json' ], true );
    
    switch( $Event[ 'severity' ] )
    {
        case 'error': $SeverityClass = 'danger'; break;
        case 'warning': $SeverityClass = 'warning'; break;
        default: $SeverityClass = 'default';
    }
    
    switch( $Event[ 'app' ][ 'releaseStage' ] )
    {
        case 'production': $StageClass = 'danger'; break;
        case 'staging': $StageClass = 'warning'; break;
        default: $StageClass = 'default';
    }
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <title>Bugsnuggle</title>
    
	<link rel="shortcut icon" href="favicon.ico">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap-flat.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-default navbar-jumbo">
        <div class="container">
            <a class="navbar-brand" href="index.php">Bugsnuggle</a>
            
            <ul class="nav navbar-nav">
                <li><a href="projects.php">Projects</a></li>
                <li><a href="index.php">Errors</a></li>
                <li class="active"><a href="#">Viewing Event <?=$EventID;?></a></li>
            </ul>
        </div>
    </nav>
    
    <div class="jumbotron">
        <div class="container">
            <h2><?=htmlspecialchars( $Event[ 'exceptions' ][ 0 ][ 'errorClass' ] );?></h2>
            
            <div class="pull-right">
                <div class="label label-<?=$SeverityClass;?> label-lg"><?=ucfirst( $Event[ 'severity' ] );?></div>
                <div class="label label-<?=$StageClass;?> label-lg"><?=ucfirst( $Event[ 'app' ][ 'releaseStage' ] );?></div>
            </div>
            
            <h3><?=htmlspecialchars( empty( $Event[ 'context' ] ) ? $Event[ 'exceptions' ][ 0 ][ 'message' ] : $Event[ 'context' ] );?></h3>
        </div>
    </div>
    
    <div class="container">
<?php
foreach( $Event[ 'exceptions' ] as $Exception )
{
    echo '<p><b>' . htmlspecialchars( $Exception[ 'errorClass' ] ) . '</b> · ' . htmlspecialchars( $Exception[ 'message' ] ) . '</p>';
    
    foreach( $Exception[ 'stacktrace' ] as $Stack )
    {
        echo '
        <div class="panel panel-' . ( ( $Stack[ 'inProject' ] ?? false ) ? 'default' : 'danger' ) . ' panel-code">
            <div class="panel-heading">' . htmlspecialchars( empty( $Stack[ 'file' ] ) ? '<unknown>' : $Stack[ 'file' ] ) . ':<b>' . (int)$Stack[ 'lineNumber' ] . '</b>' . ( isset( $Stack[ 'columnNumber' ] ) ? ':' . (int)$Stack[ 'columnNumber' ] : '' ) . ' · <b>' . htmlspecialchars( $Stack[ 'method' ] ?? '<unknown>' ). '</b></div>
        ';
        
        if( !empty( $Stack[ 'code' ] ) )
        {
            echo '
                <div class="Code">
                <table class="Code-yield">';
            
            foreach( $Stack[ 'code' ] as $Line => $Code )
            {
                echo '
                    <tr>
                        <td class="Code-lineNumber' . ( $Line === $Stack[ 'lineNumber' ] ? ' is-selected' : '' ) . '">' . (int)$Line . '</td>
                        <td class="Code-width"><pre class="Code-line' . ( $Line === $Stack[ 'lineNumber' ] ? ' is-selected' : '' ) . '">' . htmlspecialchars( $Code ) . '</pre></td>
                    </tr>
                ';
            }
            
            echo '
                </table>
                </div>';
        }
        
        
        echo '
        </div>
        ';
    }
}

unset( $Exception, $Stack, $Line, $Code );

if( !empty( $Event[ 'app' ] ) )
{
    $Event[ 'metaData' ][ 'About the app' ] = $Event[ 'app' ];
}

if( !empty( $Event[ 'device' ] ) )
{
    $Event[ 'metaData' ][ 'About the device' ] = $Event[ 'device' ];
}

if( !empty( $Event[ 'user' ] ) )
{
    $Event[ 'metaData' ][ 'About the user' ] = $Event[ 'user' ];
}

ksort( $Event[ 'metaData' ] );

foreach( $Event[ 'metaData' ] as $Name => $Data )
{
    echo '
        <h3>' . htmlspecialchars( $Name ) . '</h3>
        <table class="table Code">
    ';
    
    foreach( $Data as $Key => $Value )
    {
        echo '
            <tr>
                <td class="col-md-2">' . htmlspecialchars( $Key ) . '</td>
                <td><pre class="Code-line">' . var_export( $Value, true ) . '</pre></td></tr>';
    }
    
    echo '
        </table>
    ';
}

echo '<h3>Raw payload</h3><pre>';
array_walk_recursive( $RawPayload, function(&$v) { $v = htmlspecialchars($v); } );
print_r( $RawPayload );
echo '</pre>';

unset( $Metadata, $Event, $Name, $Data, $Key, $Value );
?>
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
