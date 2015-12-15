<?php

http_response_code( 400 );

$Notification = new Notification;

if( $Notification->Verify() && $Notification->Accept() )
{
	http_response_code( 200 );
}

class Notification
{
	private $ValidSeverities =
	[
		'error' => true,
		'info' => true,
		'warning' => true,
	];
	
	private $ValidStages =
	[
		'staging' => true,
		'production' => true,
		'development' => true,
	];
	
	public function Verify()
	{
		return
			array_key_exists( 'REQUEST_METHOD', $_SERVER ) &&
			array_key_exists( 'CONTENT_TYPE', $_SERVER ) &&
			$_SERVER[ 'REQUEST_METHOD' ] === 'POST' &&
			$_SERVER[ 'CONTENT_TYPE' ] === 'application/json';
	}
	
	public function Accept()
	{
		$Payload = file_get_contents( 'php://input' );
		
		if( empty( $Payload ) )
		{
			return false;
		}
		
		$Payload = json_decode( $Payload, true );
		
		if( empty( $Payload[ 'apiKey' ] ) || empty( $Payload[ 'events' ] ) || !is_array( $Payload[ 'events' ] ) )
		{
			return false;
		}
		
		$Database = require __DIR__ . '/db.php';
		
		$Project = $Database->prepare( 'SELECT `id` FROM `projects` WHERE `api_key` = ?' );
		$Project->execute( [ $Payload[ 'apiKey' ] ] );
		$Project = $Project->fetch();
		
		if( !$Project )
		{
			return false;
		}
		
		$InsertEvent = $Database->prepare( 'INSERT INTO `events` (`project_id`, `hash`, `severity`, `release_stage`, `event_json`, `date`) VALUES (?, ?, ?, ?, ?, ?)' );
		$CheckHash = $Database->prepare( 'SELECT `id` FROM `events` WHERE `hash` = ? LIMIT 1' );
		
		foreach( $Payload[ 'events' ] as $Event )
		{
			if( empty( $Event[ 'exceptions' ] ) || !is_array( $Event[ 'exceptions' ] ) )
			{
				return false;
			}
			
			if( !empty( $Event[ 'threads' ] ) )
			{
				if(
					!is_array( $Event[ 'threads' ] ) ||
					empty( $Event[ 'threads' ][ 'id' ] ) ||
					empty( $Event[ 'threads' ][ 'name' ] ) ||
					empty( $Event[ 'threads' ][ 'stacktrace' ] ) ||
					!is_array( $Event[ 'threads' ][ 'stacktrace' ] )
				)
				{
					return false;
				}
			}
			
			foreach( $Event[ 'exceptions' ] as $Exception )
			{
				if( empty( $Exception[ 'errorClass' ] ) || empty( $Exception[ 'stacktrace' ] ) || !is_array( $Exception[ 'stacktrace' ] ) )
				{
					return false;
				}
				
				foreach( $Exception[ 'stacktrace' ] as $Stacktrace )
				{
					if(
						empty( $Stacktrace[ 'method' ] ) ||
						!isset( $Stacktrace[ 'lineNumber' ] ) ||
						!is_numeric( $Stacktrace[ 'lineNumber' ] )
					)
					{
						return false;
					}
					
					// TODO: validate code
				}
			}
			
			if(	!isset( $this->ValidSeverities[ $Event[ 'severity' ] ] ) )
			{
				$Event[ 'severity' ] = 'error';
			}
			
			// We need app object to get releaseStage
			if( !isset( $Event[ 'app' ] ) || !is_array( $Event[ 'app' ] ) )
			{
				$Event[ 'app' ] = [];
			}
			
			if( !isset( $this->ValidStages[ $Event[ 'app' ][ 'releaseStage' ] ] ) )
			{
				$Event[ 'app' ][ 'releaseStage' ] = 'production';
			}
			
			$Hash = md5( $Project[ 'id' ] . json_encode( $Event[ 'exceptions' ] ) );
			
			$CheckHash->execute( $Hash );
			
			if( $CheckHash->fetch() )
			{
				// Store exceptions only once on the first event
				unset( $Event[ 'exceptions' ] );
				
				// Can notify irc here and whatnot
			}
			
			$InsertEvent->execute( [
				$Project[ 'id' ],
				$Hash,
				$Event[ 'severity' ],
				$Event[ 'app' ][ 'releaseStage' ],
				json_encode( $Event ),
				date( 'Y-m-d H:i:s' )
			] );
		}
		
		return true;
	}
}
