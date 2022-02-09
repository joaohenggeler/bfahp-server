<?php
	header("Content-type: text/plain");

	/*
		This file defines how the Service endpoint responds as the player progresses through the game. This logic was based on the services.py script in
		the Python server. For more details see:
		- https://github.com/sebastian404/bfahps/blob/master/docs/server.md
		- https://github.com/sebastian404/bfahps/blob/master/docs/variables.md

		Some differences between the Python server implementation and this one include:
		- Setting the last seen date on GetFirstTimeState.
		- Responding with random player names and scores for the world highscores.
		- If a request comes from a Shockwave projector, this script assumes that the user was whoever logged on most recently.
	*/

	require_once(dirname(__FILE__) . '/Bfahp.php');
	require_once(dirname(__FILE__) . '/Database.php');

	Bfahp::log_request_values('Service');

	$username = filter_input(INPUT_COOKIE, 'login', FILTER_SANITIZE_STRING);
	$event_code = filter_input(INPUT_POST, 'EventCode', FILTER_SANITIZE_STRING);
	$success = true;
	$payload = [];

	class StatusCodes
	{
		const SERVICE_SUCCESS = 0;
		const SERVICE_FAILURE = 1;
	}

	function add_to_payload($value)
	{
		global $payload;

		if(is_array($value))
		{
			$payload = array_merge($payload, $value);
		}
		else
		{
			array_push($payload, $value);
		}
	}

	function filter_all_post_values(array $keys) : array
	{
		$result = [];

		foreach($keys as $key)
		{
			$value = filter_input(INPUT_POST, $key, FILTER_SANITIZE_STRING);
			if($value !== false) $result[$key] = $value;
		}

		return (Bfahp::array_has_keys($result, $keys)) ? ($result) : ([]);
	}

	class EventCodes
	{
		const SEND_DUTIES_SERVER = '001';
		const SEND_FAVORS_SERVER = '002';
		const SEND_ADVENTURE_SERVER = '003';
		const SEND_AVATAR_SERVER = '004';
		const SEND_ACCESSORIES_SERVER = '005';
		const SEND_BUDDY_SERVER = '006';
		const SEND_MINIGAME_SERVER = '007';
		const SEND_MINIGAME_SCORE_SERVER = '008';

		const LOG_OUT = '009';
		const LOG_OUT_TIME = '010';
		
		const SEND_ALBUM = '011';
		const SEND_FURNITURE_SERVER = '012';
		const SEND_INITIAL_SERVER = '013';
		const SEND_TRACKER_MINIGAME = '014';
		const SEND_HAT_BORN_SERVER = '015';
		const SEND_MUSIC_TRACK_SERVER = '016';
		const SEND_DUTIES_LOGIN_SERVER = '017';
		const SEND_AVATAR_ROOM = '018';
		const SEND_CODES_LIST = '019';
		const SEND_OBJECTS_LIST = '020';
		const SEND_MY_BEST_FRIEND_PHOTO_LIST = '021';
		const SEND_TOYS_LIST = '022';
		const SEND_TRACKER_TOY = '023';
		const SEND_BUS = '024';
		
		const GET_MINIGAME_SERVER = '101';
		const VALIDATE_NAME = '102';
		const GET_FIRST_TIME_STATE  = '103';
		const VALIDATE_BEST_FRIEND_AVATAR_CODE = '104';
	}

	const EVENT_INFO = [
		EventCodes::SEND_DUTIES_SERVER 					=> ['Name' => 'SendDutiesServer', 'Args' => ['Duty1Type', 'Duty1Total', 'Duty1Value', 'Duty2Type', 'Duty2Total',
																									'Duty2Value', 'Duty3Type', 'Duty3Total', 'Duty3Value', 'HudUpdate',
																									'HudCitizenship', 'HudFriendship']],
		
		EventCodes::SEND_FAVORS_SERVER 					=> ['Name' => 'SendFavorsServer', 'Args' => ['FavorDone', 'FavorType', 'FavorStep', 'FavorChar', 'FavorRecip',
																									'FavorObject', 'FavorWilt', 'FavorEduardo', 'FavorFrankie', 'FavorMac',
																									'FavorLastType', 'HudUpdate', 'HudFriendship', 'HudPopularity']],
		
		EventCodes::SEND_ADVENTURE_SERVER 				=> ['Name' => 'SendAdventuresServer', 'Args' => ['AdventuresListUpdate', 'AdventuresList', 'AdventureType',
																										'AdventureStep', 'AdventureDate', 'HudUpdate',
																										'HudCitizenship', 'HudPopularity']],

		EventCodes::SEND_AVATAR_SERVER 					=> ['Name' => 'SendAvatarServer', 'Args' => ['AvatarChosen', 'AvatarType', 'AvatarName', 'AvatarAttrib1', 'AvatarAttrib2',
																									'AvatarAttrib3', 'AvatarColor1', 'AvatarColor2', 'AvatarColor3']],
		


		EventCodes::SEND_ACCESSORIES_SERVER 			=> ['Name' => 'SendAccessoriesServer', 'Args' => ['AvatarHat', 'AvatarCostume', 'AvatarTransport']],
		EventCodes::SEND_BUDDY_SERVER 					=> ['Name' => 'SendBuddyServer', 'Args' => ['AvatarBuddy']],
		EventCodes::SEND_MINIGAME_SERVER 				=> ['Name' => 'SendMiniGameServer', 'Args' => ['MinigamesList', 'HudPopularity']],
		
		EventCodes::SEND_MINIGAME_SCORE_SERVER 			=> ['Name' => 'SendMiniGameScoreServer', 'Args' => ['PersonalMinigame', 'PersonalMode', 'PersonalHighscoreUpdate', 'WorldScore',
																											'PersonalFriend1', 'PersonalScore1', 'PersonalFriend2', 'PersonalScore2',
																											'PersonalFriend3', 'PersonalScore3', 'PersonalFriend4', 'PersonalScore4',
																											'PersonalFriend5', 'PersonalScore5', 'PersonalFriend6', 'PersonalScore6',
																											'PersonalFriend7', 'PersonalScore7', 'PersonalFriend8', 'PersonalScore8',
																											'PersonalFriend9', 'PersonalScore9', 'PersonalFriend10', 'PersonalScore10']],
		
		EventCodes::LOG_OUT 							=> ['Name' => 'LogOut', 'Args' => []],
		EventCodes::LOG_OUT_TIME 						=> ['Name' => 'LogOutTime', 'Args' => []],
		EventCodes::SEND_ALBUM 							=> ['Name' => 'SendAlbum', 'Args' => ['AlbumID', 'PhotosList']],
		EventCodes::SEND_FURNITURE_SERVER 				=> ['Name' => 'SendFurnitureServer', 'Args' => ['FurnitureList']],

		EventCodes::SEND_INITIAL_SERVER 				=> ['Name' => 'SendInitialServer', 'Args' => ['HudCitizenship', 'HudFriendship', 'HudPopularity', 'Duty1Type', 'Duty1Total',
																									'Duty1Value', 'Duty2Type', 'Duty2Total', 'Duty2Value', 'Duty3Type',
																									'Duty3Total', 'Duty3Value', 'FavorDone', 'FavorType', 'FavorStep',
																									'FavorChar', 'FavorRecip', 'FavorObject', 'FavorWilt', 'FavorEduardo',
																									'FavorFrankie', 'FavorMac', 'FavorLastType', 'AdventuresList', 'AdventureType',
																									'AdventureStep', 'AdventureDate', 'AvatarChosen', 'AvatarType', 'AvatarName',
																									'AvatarAttrib1', 'AvatarAttrib2', 'AvatarAttrib3', 'AvatarColor1', 'AvatarColor2',
																									'AvatarColor3', 'AvatarHat', 'AvatarBuddy', 'AvatarCostume', 'AvatarTransport',
																									'MinigamesList', 'Album1List', 'Album2List', 'Album3List', 'Album4List',
																									'Album5List', 'FurnitureList', 'BirthdayHat', 'NewBirthdayHat', 'MusicTrack',
																									'AvatarRoom', 'CodesList', 'ObjectsList', 'MyBestFriendPhotoList', 'ToysList',
																									'Bus', 'BestFriendCode']],

		EventCodes::SEND_TRACKER_MINIGAME 				=> ['Name' => 'SendTrackerMinigame', 'Args' => ['Minigame', 'Mode']],
		EventCodes::SEND_HAT_BORN_SERVER 				=> ['Name' => 'SendHatBornServer', 'Args' => ['BirthdayHat', 'NewBirthdayHat', 'HudPopularity']],
		EventCodes::SEND_MUSIC_TRACK_SERVER 			=> ['Name' => 'SendMusicTrackServer', 'Args' => ['MusicTrack']],
		EventCodes::SEND_DUTIES_LOGIN_SERVER 			=> ['Name' => 'SendDutiesLoginServer', 'Args' => ['Duty1Type', 'Duty1Total', 'Duty1Value', 'Duty2Type', 'Duty2Total',
																										'Duty2Value', 'Duty3Type', 'Duty3Total', 'Duty3Value', 'DutiesUpdate']],

		EventCodes::SEND_AVATAR_ROOM 					=> ['Name' => 'SendAvatarRoom', 'Args' => ['AvatarRoom']],
		EventCodes::SEND_CODES_LIST 					=> ['Name' => 'SendCodesList', 'Args' => ['CodesList']],
		EventCodes::SEND_OBJECTS_LIST 					=> ['Name' => 'SendObjectsList', 'Args' => ['ObjectsList']],
		EventCodes::SEND_MY_BEST_FRIEND_PHOTO_LIST		=> ['Name' => 'SendMyBestFriendPhotoList', 'Args' => ['MyBestFriendPhotoList']],
		EventCodes::SEND_TOYS_LIST 						=> ['Name' => 'SendToysList', 'Args' => ['ToysList', 'HudPopularity']],
		EventCodes::SEND_TRACKER_TOY 					=> ['Name' => 'SendTrackerToy', 'Args' => ['Toys']],
		EventCodes::SEND_BUS 							=> ['Name' => 'SendBus', 'Args' => ['Bus']],

		EventCodes::GET_MINIGAME_SERVER 				=> ['Name' => 'GetMiniGameServer', 'Args' => ['Minigame']],
		EventCodes::VALIDATE_NAME 						=> ['Name' => 'ValidateName', 'Args' => ['AvatarName']],
		EventCodes::GET_FIRST_TIME_STATE  				=> ['Name' => 'GetFirstTimeState', 'Args' => []],
		EventCodes::VALIDATE_BEST_FRIEND_AVATAR_CODE	=> ['Name' => 'ValidateBestFriendAvatarCode', 'Args' => ['BestFriendCode']],
	];

	if(!$event_code)
	{
		$success = false;
		$event_name = 'UNKNOWN';
		Bfahp::critical('The event code was not set.');
		goto SKIP_EVENT_HANDLING;
	}

	$event_name = EVENT_INFO[$event_code]['Name'];
	$event_args = EVENT_INFO[$event_code]['Args'];

	if(!$username)
	{
		// For a Shockwave projector, assume that the user was whoever logged on most recently.
		if(!isset($_SERVER['User-Agent']))
		{
			$config = Bfahp::load_config_from_file();
			$username = $config['LastUser'];
			Bfahp::info("Using the last authenticated user \"$username\" for the event $event_name since the request was made from a projector.");
		}

		if(!$username)
		{
			$success = false;
			Bfahp::critical("The username was not set in the login cookie for the event $event_name.");
			goto SKIP_EVENT_HANDLING;
		}
	}

	switch($event_code)
	{
		case(EventCodes::SEND_DUTIES_SERVER):
		case(EventCodes::SEND_FAVORS_SERVER):
		case(EventCodes::SEND_ADVENTURE_SERVER):
		{
			$input = filter_all_post_values($event_args);

			if($input)
			{
				if($input['HudUpdate'] == '1')
				{
					unset($input['HudUpdate']);
				}
				else
				{
					unset($input['HudUpdate'], $input['HudCitizenship'], $input['HudFriendship'], $input['HudPopularity']);
				}

				// The game sends AdventuresListUpdate with the value zero even when the adventure is completed. This means that we wouldn't be
				// able to save the player's progress if we only updated the adventure list when this value was one.
				/*if($input['AdventuresListUpdate'] == '1')
				{
					unset($input['AdventuresListUpdate']);
				}
				else
				{
					unset($input['AdventuresListUpdate'], $input['AdventuresList']);
				}*/

				unset($input['AdventuresListUpdate']);

				$db = Database::get_instance();
				$db->update_user_save($username, $input);			
			}
			else
			{
				Bfahp::error("Missing one or more arguments for the $event_name service.");
			}
		} break;

		case(EventCodes::SEND_DUTIES_LOGIN_SERVER):
		{
			$input = filter_all_post_values($event_args);

			if($input)
			{
				if($input['DutiesUpdate'] == '1')
				{
					unset($input['DutiesUpdate']);
					$db = Database::get_instance();
					$db->update_user_save($username, $input);
				}
			}
			else
			{
				Bfahp::error("Missing one or more arguments for the $event_name service.");
			}
		} break;

		case(EventCodes::LOG_OUT):
		case(EventCodes::LOG_OUT_TIME):
		{
			$db = Database::get_instance();
			$db->update_user_info($username, ['LastSeen' => Bfahp::get_current_date()]);
		} break;

		case(EventCodes::SEND_ALBUM):
		{
			$input = filter_all_post_values($event_args);

			if($input)
			{
				$db = Database::get_instance();
				$key = 'Album' . $input['AlbumID'] . 'List';
				$photos_list = $input['PhotosList'];
				$db->update_user_save($username, [$key => $photos_list]);
			}
			else
			{
				Bfahp::error("Missing one or more arguments for the $event_name service.");
			}
		} break;

		case(EventCodes::SEND_TRACKER_MINIGAME):
		case(EventCodes::SEND_TRACKER_TOY):
		{
			$input = filter_all_post_values($event_args);

			if($input)
			{
				Bfahp::info('Got the tracker values: ' . Bfahp::array_to_string($input));
			}
			else
			{
				Bfahp::error("Missing one or more arguments for the $event_name service.");
			}
		} break;

		case(EventCodes::SEND_AVATAR_SERVER):
		case(EventCodes::SEND_ACCESSORIES_SERVER):
		case(EventCodes::SEND_BUDDY_SERVER):
		case(EventCodes::SEND_MINIGAME_SERVER):
		case(EventCodes::SEND_FURNITURE_SERVER):
		case(EventCodes::SEND_INITIAL_SERVER):
		case(EventCodes::SEND_HAT_BORN_SERVER):
		case(EventCodes::SEND_MUSIC_TRACK_SERVER):
		case(EventCodes::SEND_AVATAR_ROOM):
		case(EventCodes::SEND_CODES_LIST):
		case(EventCodes::SEND_OBJECTS_LIST):
		case(EventCodes::SEND_MY_BEST_FRIEND_PHOTO_LIST):
		case(EventCodes::SEND_TOYS_LIST):
		case(EventCodes::SEND_BUS):
		{
			$input = filter_all_post_values($event_args);

			if($input)
			{
				$db = Database::get_instance();				
				$db->update_user_save($username, $input);
			}
			else
			{
				Bfahp::error("Missing one or more arguments for the $event_name service.");
			}
		} break;

		case(EventCodes::SEND_MINIGAME_SCORE_SERVER):
		{
			$input = filter_all_post_values($event_args);

			if($input && $input['PersonalHighscoreUpdate'] == '1')
			{
				$db = Database::get_instance();

				for($rank = 1; $rank <= Bfahp::MAX_MINIGAME_RANKS; ++$rank)
				{ 
					$minigame = $input['PersonalMinigame'];
					$mode = $input['PersonalMode'];
					$friend = $input['PersonalFriend' . $rank];
					$score = $input['PersonalScore' . $rank];
					$db->update_user_highscores($username, $minigame, $mode, $rank, ['Friend' => $friend, 'Score' => $score]);
				}
			}
			else
			{
				Bfahp::error("Missing one or more arguments for the $event_name service.");
			}	
		} break;

		case(EventCodes::GET_MINIGAME_SERVER):
		{
			$input = filter_all_post_values($event_args);

			if($input)
			{
				$db = Database::get_instance();
				$minigame = $input['Minigame'];

				// This will be an array of zeros if the player doesn't have a best friend.
				$best_friend_mode_highscores = $db->get_best_friend_top_minigame_highscores($username, $minigame);
				add_to_payload($best_friend_mode_highscores);
				Bfahp::info('Found the best friend highscores: ' . Bfahp::array_to_string($best_friend_mode_highscores));
			
				for($mode = 1; $mode <= Bfahp::MAX_MINIGAME_MODES; ++$mode)
				{ 
					$user_highscores = $db->get_user_minigame_and_mode_highscores($username, $minigame, $mode);
					$friend_scores = [];
					$user_scores = [];

					foreach($user_highscores as $highscores)
					{
						array_push($friend_scores, $highscores['Friend']);
						array_push($user_scores, $highscores['Score']);
					}

					add_to_payload($friend_scores);
					add_to_payload($user_scores);
				}

				// Adapted from: https://stackoverflow.com/a/6557863
				function fisher_yates_shuffle(array $array) : array
				{
					for($i = count($array) - 1; $i > 0; --$i)
					{
					    $j = mt_rand(0, $i);
					    $temp = $array[$i];
					    $array[$i] = $array[$j];
					    $array[$j] = $temp;
					}

					return $array;
				}

				// Add random player names and scores to the world highscores. The chosen values are the same for each specific minigame.
				$WORLD_PLAYERS = ['Sofia', 'Gonzalo', 'Christine', 'Daniel', 'Elbio', 'Ernesto', 'Fabian', 'Federico', 'Mariana', 'Raul', 'Brian'];
				
				mt_srand($minigame);
				for($mode = 1; $mode <= Bfahp::MAX_MINIGAME_MODES; ++$mode)
				{
					$random_players = fisher_yates_shuffle($WORLD_PLAYERS);
					$random_players = array_slice($random_players, 0, Bfahp::MAX_MINIGAME_RANKS);

					$random_scores = [];
					for($rank = 1; $rank <= Bfahp::MAX_MINIGAME_RANKS; ++$rank)
					{
						$score = mt_rand(1000, 12000) * (Bfahp::MAX_MINIGAME_MODES - $mode + 1);
						array_push($random_scores, $score);
					}
					rsort($random_scores);

					add_to_payload($random_players);
					add_to_payload($random_scores);
				}

				// Unused value.
				add_to_payload(0);
			}
			else
			{
				Bfahp::error("Missing one or more arguments for the $event_name service.");
			}
		} break;

		case(EventCodes::VALIDATE_NAME):
		{
			$input = filter_all_post_values($event_args);

			if($input)
			{
				$success = !Bfahp::credentials_contain_invalid_characters($input['AvatarName']);
			}
			else
			{
				Bfahp::error("Missing one or more arguments for the $event_name service.");
			}	
		} break;

		case(EventCodes::VALIDATE_BEST_FRIEND_AVATAR_CODE):
		{
			$input = filter_all_post_values($event_args);

			if($input)
			{
				$db = Database::get_instance();
				$friend_code = $input['BestFriendCode'];

				if($friend_code != Bfahp::DEFAULT_AVATAR_CODE)
				{
					$friend_save = $db->get_user_save_by_code($friend_code);

					if($friend_save)
					{
						Bfahp::info('Found the best friend "' . $friend_save['AvatarName'] . "\" for the user \"$username\" using the code \"$friend_code\".");

						$db->update_user_save($username, ['BestFriendCode' => $friend_code]);

						add_to_payload($friend_code);
						add_to_payload($friend_save['AvatarType']);
						add_to_payload($friend_save['AvatarName']);
						add_to_payload($friend_save['AvatarAttrib1']);
						add_to_payload($friend_save['AvatarAttrib2']);
						add_to_payload($friend_save['AvatarAttrib3']);
						add_to_payload($friend_save['AvatarColor1']);
						add_to_payload($friend_save['AvatarColor2']);
						add_to_payload($friend_save['AvatarColor3']);
						add_to_payload($friend_save['AvatarHat']);
						add_to_payload($friend_save['AvatarBuddy']);
						add_to_payload($friend_save['AvatarCostume']);
						add_to_payload($friend_save['AvatarTransport']);
					}
					else
					{
						Bfahp::error("Could not find the best friend for the user \"$username\" using the code \"$friend_code\".");
						$success = false;
					}
				}
				else
				{
					Bfahp::info("Clearing the best friend code for the user \"$username\".");
					$db->update_user_save($username, ['BestFriendCode' => $friend_code]);
					add_to_payload([$friend_code, 0, '[unset]', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]);
				}
			}
			else
			{
				Bfahp::error("Missing one or more arguments for the $event_name service.");
			}	
		} break;

		case(EventCodes::GET_FIRST_TIME_STATE):
		{
			$db = Database::get_instance();

			$user_info = $db->get_user_info($username);
			$user_save = $db->get_user_save_by_name($username);
			$top_user_highscores = $db->get_all_user_top_highscores($username);

			if(!$user_info || !$user_save || !$top_user_highscores)
			{
				$success = false;
				Bfahp::error("Could not retrieve the info, save, or highscores for the user \"$username\".");
				break;
			}

			$today_date = Bfahp::get_current_date();
			$days_since_last_seen = Bfahp::count_days_between_dates($user_info['LastSeen'], $today_date);
			$birthday_date = sprintf('2000%02d%02d', $user_info['BirthdayMonth'], $user_info['BirthdayDay']);

			add_to_payload($days_since_last_seen);
			add_to_payload($today_date);
			add_to_payload(Bfahp::IDLE_TIMEOUT);
			add_to_payload($birthday_date);
			add_to_payload($user_info['AvatarCode']);

			add_to_payload($user_save['AvatarChosen']);
			add_to_payload($user_save['AvatarType']);
			add_to_payload($user_save['AvatarName']);
			add_to_payload($user_save['AvatarAttrib1']);
			add_to_payload($user_save['AvatarAttrib2']);
			add_to_payload($user_save['AvatarAttrib3']);
			add_to_payload($user_save['AvatarColor1']);
			add_to_payload($user_save['AvatarColor2']);
			add_to_payload($user_save['AvatarColor3']);
			add_to_payload($user_save['AvatarHat']);
			add_to_payload($user_save['AvatarBuddy']);
			add_to_payload($user_save['AvatarCostume']);
			add_to_payload($user_save['AvatarTransport']);
			add_to_payload($user_save['HudCitizenship']);
			add_to_payload($user_save['HudFriendship']);
			add_to_payload($user_save['HudPopularity']);

			add_to_payload($user_save['Duty1Type']);
			add_to_payload($user_save['Duty1Value']);
			add_to_payload($user_save['Duty1Total']);
			add_to_payload($user_save['Duty2Type']);
			add_to_payload($user_save['Duty2Value']);
			add_to_payload($user_save['Duty2Total']);
			add_to_payload($user_save['Duty3Type']);
			add_to_payload($user_save['Duty3Value']);
			add_to_payload($user_save['Duty3Total']);

			add_to_payload($user_save['FavorDone']);
			add_to_payload($user_save['FavorLastType']);
			add_to_payload($user_save['FavorWilt']);
			add_to_payload($user_save['FavorEduardo']);
			add_to_payload($user_save['FavorFrankie']);
			add_to_payload($user_save['FavorMac']);
			add_to_payload($user_save['FavorType']);
			add_to_payload($user_save['FavorStep']);
			add_to_payload($user_save['FavorChar']);
			add_to_payload($user_save['FavorRecip']);
			add_to_payload($user_save['FavorObject']);

			add_to_payload($user_save['AdventuresList']);
			add_to_payload($user_save['AdventureType']);
			add_to_payload($user_save['AdventureStep']);
			add_to_payload($user_save['AdventureDate']);

			add_to_payload($user_save['MinigamesList']);

			for($minigame = 1; $minigame <= Bfahp::MAX_MINIGAME; ++$minigame)
			{
				add_to_payload($top_user_highscores[$minigame]);
			}

			for($album_id = 1; $album_id <= Bfahp::MAX_ALBUM; ++$album_id)
			{
				$key = 'Album' . $album_id . 'List';
				add_to_payload($user_save[$key]);
			}

			add_to_payload($user_save['FurnitureList']);
			add_to_payload($user_save['BirthdayHat']);
			add_to_payload($user_save['NewBirthdayHat']);
			add_to_payload($user_save['MusicTrack']);
			add_to_payload($user_save['AvatarRoom']);
			add_to_payload($user_save['CodesList']);
			add_to_payload($user_save['ObjectsList']);
			add_to_payload($user_save['MyBestFriendPhotoList']);
			add_to_payload($user_save['ToysList']);
			add_to_payload($user_save['Bus']);
			add_to_payload($user_save['BestFriendCode']);

			$friend_save = $db->get_user_save_by_code($user_save['BestFriendCode']);

			if($friend_save)
			{
				Bfahp::info('Found the best friend "' . $friend_save['AvatarName'] . "\" when getting save data for the user \"$username\".");
				add_to_payload($friend_save['AvatarType']);
				add_to_payload($friend_save['AvatarName']);
				add_to_payload($friend_save['AvatarAttrib1']);
				add_to_payload($friend_save['AvatarAttrib2']);
				add_to_payload($friend_save['AvatarAttrib3']);
				add_to_payload($friend_save['AvatarColor1']);
				add_to_payload($friend_save['AvatarColor2']);
				add_to_payload($friend_save['AvatarColor3']);
				add_to_payload($friend_save['AvatarHat']);
				add_to_payload($friend_save['AvatarBuddy']);
				add_to_payload($friend_save['AvatarCostume']);
				add_to_payload($friend_save['AvatarTransport']);
			}
			else
			{
				Bfahp::info("The user \"$username\" does not have a best friend to get save data from.");
				add_to_payload([0, '[unset]', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]);
			}

			$db->update_user_info($username, ['LastSeen' => Bfahp::get_current_date()]);

		} break;

		default:
		{
			$success = false;
			Bfahp::critical("Unhandled service event code $event_code for the user \"$username\".");
		} break;
	}

	SKIP_EVENT_HANDLING:

	$status_code = ($success) ? (StatusCodes::SERVICE_SUCCESS) : (StatusCodes::SERVICE_FAILURE);
	$response = Bfahp::SAVE_VERSION . ',' . $status_code;

	if($payload)
	{
		$response .= ',' . implode(',', $payload);
	}

	Bfahp::info("Generated the following service response for event $event_name ($event_code): \"$response\"");

	echo $response;
?>
