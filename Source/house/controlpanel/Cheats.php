<?php
	header("Content-type: text/plain");

	/*
		This file defines how the server responds when the player cheats using the control panel page.
	*/

	require_once(dirname(__FILE__) . '/../Bfahp.php');
	require_once(dirname(__FILE__) . '/../Database.php');

	Bfahp::log_request_values('Cheats');

	$response = 'Something went wrong.';

	$username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
	$cheat = filter_input(INPUT_POST, 'cheat', FILTER_SANITIZE_STRING);

	$db = Database::get_instance();

	if($db->user_exists($username))
	{
		switch($cheat)
		{
			case('citizenship'):
			{
				if($db->update_user_save($username, ['HudCitizenship' => 9]))
				{
					$response = "Maxed out the citizenship bar for \"$username\".";
				}
				else
				{
					$response = "Failed to max out the citizenship bar for \"$username\".";
				}
			} break;

			case('popularity'):
			{
				if($db->update_user_save($username, ['HudPopularity' => 9]))
				{
					$response = "Maxed out the popularity bar for \"$username\".";
				}
				else
				{
					$response = "Failed to max out the popularity bar for \"$username\".";
				}
			} break;

			case('birthday_hat'):
			{
				if($db->update_user_save($username, ['BirthdayHat' => 1]))
				{
					$response = "Unlocked the first birthday hat for \"$username\".";
				}
				else
				{
					$response = "Failed to unlock the first birthday hat for \"$username\".";
				}
			} break;

			case('minigames'):
			{
				if($db->update_user_save($username, ['MinigamesList' => str_repeat('3', Bfahp::MAX_MINIGAME)]))
				{
					$response = "Unlocked all minigames for \"$username\".";
				}
				else
				{
					$response = "Failed to unlock all minigames for \"$username\".";
				}
			} break;

			case('everything'):
			{
				if($db->unlock_everything($username))
				{
					$response = "Unlocked everything for \"$username\".";
				}
				else
				{
					$response = "Failed to unlock everything for \"$username\".";
				}
			} break;

			default:
			{
				$response = "Unknown cheat $cheat for \"$username\".";
			} break;
		}		
	}
	else
	{
		$response = "Unknown user \"$username\".";
	}

	Bfahp::info("Generated the following cheating response: $response");

	echo $response;
?>
