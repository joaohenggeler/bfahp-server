<?php

	/*
		This file defines any basic constants and general purpose functions used by other scripts.

		The PHP files inside this directory and any subdirectories are used to emulate the server-side logic required to play the web game Big Fat Awesome House Party.

		The code in the Registration, Logic, Game, and Service scripts was based on a previous implementation developed in Python: https://github.com/sebastian404/bfahps
		We'll used the term "Python server" when we want to refer to this implementation. Some of the constants in this file were taken from the config.py script in
		the Python server.
	*/

	class Bfahp
	{
		public static $LOGGING_ENABLED = false;
		public static $CONFIG_FILE_PATH = null;
		const DEFAULT_CONFIG = ['LoggingEnabled' => false, 'UseSimpleHomeLayout' => false, 'ShowBufferPage' => false,
								'TimeTravelEnabled' => false, 'ChangedDate' => null, 'GameDate' => null, 'LastUser' => null];

		// For more details about the version numbers, see: https://github.com/sebastian404/bfahps/blob/master/docs/versions.md
		const SAVE_VERSION = 140;
		const VERSION_STRING = '1.14.3';

		const IDLE_TIMEOUT = 300;

		const MAX_AVATAR_CODE = 6;
		const DEFAULT_AVATAR_CODE = '000000';

		const MAX_MINIGAME = 99;
		const MAX_MINIGAME_MODES = 3;
		const MAX_MINIGAME_RANKS = 10;
		
		const MAX_ALBUM = 5;
		const MAX_ALBUM_PHOTOS = 99;

		const MAX_FRIENDSHIP = 4;
		const MAX_ADVENTURE = 150;
		const MAX_FURNITURE = 350;
		const MAX_CODES = 99;
		const MAX_OBJECTS = 99;
		const MAX_MY_BEST_FRIEND_PHOTO = 99;
		const MAX_TOY = 99;

		public static function log(string $message)
		{
			if(!Bfahp::$LOGGING_ENABLED) return;
			error_log($message);
		}

		public static function log_request_values(string $identifier)
		{
			for ($i = 0; $i < 3; ++$i) Bfahp::log('');

			Bfahp::info("$identifier - POST values: " . Bfahp::array_to_string($_POST));
			Bfahp::info("$identifier - GET values: " . Bfahp::array_to_string($_GET));
			Bfahp::info("$identifier - COOKIE values: " . Bfahp::array_to_string($_COOKIE));
		}

		public static function info(string $message)
		{
			Bfahp::log('[INFO] ' . $message);
		}

		public static function warning(string $message)
		{
			Bfahp::log('[WARNING] ' . $message);
		}

		public static function error(string $message)
		{
			Bfahp::log('[ERROR] ' . $message);
		}

		public static function critical(string $message)
		{
			Bfahp::log('[CRITICAL] ' . $message);
		}

		public static function array_to_string(?array $array) : string
		{
			$result = '[';

			if($array)
			{
				foreach($array as $key => $value)
				{
					if(is_array($value)) $value = Bfahp::array_to_string($value);
					$result .= "'$key': '$value', ";
				}				
			}

			return rtrim($result, ', ') . ']';
		}

		public static function array_has_keys(array $array, array $keys) : bool
		{
			return !array_diff($keys, array_keys($array));
		}

		public static function generate_avatar_code(string $username) : string
		{
			$code = hash('sha256', $username);
			$code = strtoupper(substr($code, 0, Bfahp::MAX_AVATAR_CODE));

			while($code == Bfahp::DEFAULT_AVATAR_CODE)
			{
				$code = strtoupper(bin2hex(random_bytes(Bfahp::MAX_AVATAR_CODE / 2)));
			}

			return $code;
		}

		public static function load_config_from_file() : array
		{
			$config = @file_get_contents(Bfahp::$CONFIG_FILE_PATH);

			if($config !== false)
			{
				$config = json_decode($config, true);
			}
			
			if(!$config)
			{
				$config = Bfahp::DEFAULT_CONFIG;
				Bfahp::save_config_to_file($config);
			}

			return $config;
		}

		public static function save_config_to_file(array $config)
		{
			$config = json_encode($config, JSON_PRETTY_PRINT);
			file_put_contents(Bfahp::$CONFIG_FILE_PATH, $config);
		}

		public static function set_config_values_in_file(array $config_values)
		{
			$config = Bfahp::load_config_from_file();
			foreach($config_values as $key => $value)
			{
				$config[$key] = $value;
			}
			Bfahp::save_config_to_file($config);
		}

		public static function count_days_between_dates(string $from_date, string $to_date) : int
		{
			$from_date = date_create($from_date);
			$to_date = date_create($to_date);

			if($from_date > $to_date)
			{
				Bfahp::warning("The first date {$from_date->format('Y-m-d')} is after the second date {$to_date->format('Y-m-d')}. This first value will be set to the latter.");
				$from_date = $to_date;
			}

			return date_diff($from_date, $to_date)->format('%a');
		}

		// Used to determine the current server date. This value may be different from the computer's date if the user changes it in the control panel page.
		public static function get_current_date() : string
		{
			$date = null;

			$today_date = date('Ymd');
			$config = Bfahp::load_config_from_file();

			if($config['TimeTravelEnabled'])
			{
				$days_since_last_changed = Bfahp::count_days_between_dates($config['ChangedDate'], $today_date);
				$game_date = $config['GameDate'];

				$date = date('Ymd', strtotime($game_date . " + $days_since_last_changed days"));
				Bfahp::info("Using the user defined date: $date ($game_date + $days_since_last_changed days)");
			}
			else
			{
				$date = $today_date;
				Bfahp::info("Using today's date: $date");
			}

			return $date;
		}

		public static function credentials_contain_invalid_characters(string $credentials) : bool
		{
			$credentials = str_split($credentials);
			$disallowed_characters = str_split('[~!@#$%^&*()_+{}":;\']+$');
			$intersection = array_intersect($credentials, $disallowed_characters);
			return count($intersection) > 0;
		}
	}

	Bfahp::$CONFIG_FILE_PATH = dirname(__FILE__) . '/config.json';

	$config = Bfahp::load_config_from_file();
	Bfahp::$LOGGING_ENABLED = $config['LoggingEnabled'];

	// This is a silly way to handle logging but it works for the vast majority cases
	// since logging is disabled by default and was mostly used during testing.
	if(Bfahp::$LOGGING_ENABLED)
	{
		ini_set('log_errors', 1);
		ini_set('error_log', dirname(__FILE__) . '/bfahp.log');		
	}
?>
