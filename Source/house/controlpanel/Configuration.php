<?php
	header("Content-type: text/plain");

	/*
		This file defines how the server responds when the player changes the configuration using the control panel page.
	*/

	require_once(dirname(__FILE__) . '/../Bfahp.php');
	require_once(dirname(__FILE__) . '/../Database.php');

	Bfahp::log_request_values('Configuration');
	
	$response = 'Something went wrong.';

	$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

	function toggle_config(string $config_key, string $value_when_true, string $value_when_false) : string
	{
		$config = Bfahp::load_config_from_file();
		$config[$config_key] = !$config[$config_key];
		Bfahp::save_config_to_file($config);

		$config_value = ($config[$config_key]) ? ($value_when_true) : ($value_when_false);
		return "Set $config_key to $config_value.";
	}

	switch($action)
	{
		case('backup_save'):
		{
			$destination_path = filter_input(INPUT_POST, 'destination_path', FILTER_SANITIZE_STRING);

			if($destination_path)
			{
				$source_path = Database::$DATABASE_FILE_PATH;
				$destination_path = $destination_path . DIRECTORY_SEPARATOR . basename($source_path);

				if(copy($source_path, $destination_path))
				{
					$response = "Copied the database to \"$destination_path\".";
				}
				else
				{
					$response = 'Failed to copy the database.';
				}				
			}
			else
			{
				$response = 'Missing the destination path.';
			}
		} break;

		case('toggle_logging'):
		{
			$response = toggle_config('LoggingEnabled', 'Yes', 'No');
		} break;

		case('toggle_home_layout'):
		{
			$response = toggle_config('UseSimpleHomeLayout', 'Simple', 'Complete');
		} break;

		case('toggle_buffer_page'):
		{
			$response = toggle_config('ShowBufferPage', 'Yes', 'No');
		} break;

		default:
		{
			$response = "Unknown action $action.";
		} break;
	}

	Bfahp::info("Generated the following configuration response: $response");

	echo $response;
?>
