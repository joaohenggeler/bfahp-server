<?php
	header("Content-type: text/plain");

	/*
		This file defines how the server responds when the player changes the game's date using the control panel page.
	*/

	require_once(dirname(__FILE__) . '/../Bfahp.php');
	require_once(dirname(__FILE__) . '/../Database.php');

	Bfahp::log_request_values('Time Traveling');
	
	$response = 'Something went wrong.';
	
	$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
	$config = Bfahp::load_config_from_file();

	function change_game_date(string $date)
	{
		global $config;
		$config['TimeTravelEnabled'] = true;
		$config['ChangedDate'] = date('Ymd');
		$config['GameDate'] = date("Ymd", strtotime($date));
	}

	switch($action)
	{
		case('change_date'):
		{
			$date = filter_input(INPUT_POST, 'target_date', FILTER_SANITIZE_STRING);

			if($date)
			{
				change_game_date($date);
				$response = 'Changed the game date to ' . $config['GameDate'] . '.';
			}
			else
			{
				$response = 'Missing the date value.';
			}
		} break;

		case('reset_date'):
		{
			$config['TimeTravelEnabled'] = false;
			$config['ChangedDate'] = null;
			$config['GameDate'] = null;
		
			// Set LastSeen to today for all users.
			$db = Database::get_instance();
			$db->update_all_users_last_seen(date('Ymd'));

			$response = 'Reset the game date to the present day.';
		} break;

		case('skip_days'):
		{
			$days = filter_input(INPUT_POST, 'days', FILTER_VALIDATE_INT);

			if($days)
			{
				$current_date = ($config['TimeTravelEnabled']) ? ($config['GameDate']) : (date('Ymd'));
				change_game_date($current_date . " + $days days");		
				$response = "Skipped $days days to " . $config['GameDate'] . '.';		
			}
			else
			{
				$response = 'The number of days is not valid.';
			}
		} break;

		case('birthday'):
		{
			$username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);

			if($username)
			{
				$db = Database::get_instance();
				$user_info = $db->get_user_info($username);
				$user_save = $db->get_user_save_by_name($username);

				if($user_info && $user_save)
				{
					if($user_save['NewBirthdayHat'] == 1)
					{
						Bfahp::info("Resetting the second birthday hat for the user \"$username\".");
						$db->update_user_save($username, ['NewBirthdayHat' => 0]);
					}

					$birthday_date = date('Y') . sprintf('%02d%02d', $user_info['BirthdayMonth'], $user_info['BirthdayDay']);
					change_game_date($birthday_date);
					$response = "Changed the game date to the user's \"$username\" birthday: " . $config['GameDate'] . '.';
				}
				else
				{
					$response = "The user \"$username\" does not exist.";
				}
			}
			else
			{
				$response = 'Missing the username value.';
			}
		} break;

		case('april_fools'):
		{
			change_game_date(date('Y0401'));
			$db = Database::get_instance();
			$db->update_all_users_last_seen(date('Y0331'));
			$response = 'Changed the game date to April Fools: ' . $config['GameDate'] . '.';
		} break;

		default:
		{
			$response = "Unknown action $action.";
		} break;
	}

	Bfahp::save_config_to_file($config);

	Bfahp::info("Generated the following time traveling response: $response");

	echo $response;
?>
