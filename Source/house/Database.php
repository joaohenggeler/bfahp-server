<?php

	/*
		This file defines the game's database structure and provides functions to save the player's information and progress between different sessions.
		The database's schema was based on the definition in the database.py script in the Python server. The two main differences are: 1) the PhotoAlbums
		information is stored in SaveGame instead of its own table; 2) the user's AvatarCode has a unique constraint.

		Note also that the home page Flash movie saves a Flash cookie with the player's login status, and the game Shockwave movie saves a pref (BFAHPRTY.txt)
		with some ingame settings (e.g. music volume) using XML.
	*/

	require_once(dirname(__FILE__) . '/Bfahp.php');

	class Database
	{
		public static $DATABASE_FILE_PATH;
		private static $instance;
		
		private $db;
		
		private function __construct()
		{
			try
			{
				Bfahp::info('Connecting to the database in: ' . Database::$DATABASE_FILE_PATH);
				Bfahp::info('Database exists? ' . ( (file_exists(Database::$DATABASE_FILE_PATH)) ? ('Yes') : ('No') ));

				$this->db = new SQLite3(Database::$DATABASE_FILE_PATH);
				$this->db->enableExceptions(true);

				$MAX_FRIENDSHIP = Bfahp::MAX_FRIENDSHIP;
				$DEFAULT_HUD_FRIENDSHIP = str_repeat('0', $MAX_FRIENDSHIP);

				$MAX_ADVENTURE = Bfahp::MAX_ADVENTURE;
				$DEFAULT_ADVENTURE_LIST = str_repeat('0', $MAX_ADVENTURE);

				$MAX_MINIGAME = Bfahp::MAX_MINIGAME;
				$DEFAULT_MINIGAMES_LIST = str_repeat('0', $MAX_MINIGAME);

				$MAX_ALBUM_LIST = Bfahp::MAX_ALBUM_PHOTOS;
				$DEFAULT_ALBUM_LIST = str_repeat('0', $MAX_ALBUM_LIST);

				$MAX_FURNITURE = Bfahp::MAX_FURNITURE;
				$DEFAULT_FURNITURE_LIST = str_repeat('0', $MAX_FURNITURE);

				$MAX_CODES = Bfahp::MAX_CODES;
				$DEFAULT_CODES_LIST = str_repeat('0', $MAX_CODES);

				$MAX_OBJECTS = Bfahp::MAX_OBJECTS;
				$DEFAULT_OBJECTS_LIST = str_repeat('0', $MAX_OBJECTS);

				$MAX_MY_BEST_FRIEND_PHOTO = Bfahp::MAX_MY_BEST_FRIEND_PHOTO;
				$DEFAULT_MY_BEST_FRIEND_PHOTO_LIST = str_repeat('0', $MAX_MY_BEST_FRIEND_PHOTO);

				$MAX_TOY = Bfahp::MAX_TOY;
				$DEFAULT_TOYS_LIST = str_repeat('0', $MAX_TOY);

				$MAX_AVATAR_CODE = Bfahp::MAX_AVATAR_CODE;
				$DEFAULT_BEST_FRIEND_CODE = Bfahp::DEFAULT_AVATAR_CODE;

				$this->db->exec("CREATE TABLE IF NOT EXISTS 'User'
								(
								'Id' INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
								'Username' VARCHAR(25) NOT NULL UNIQUE,
								'Password' VARCHAR(25) NOT NULL,
								'AvatarCode' VARCHAR($MAX_AVATAR_CODE) NOT NULL UNIQUE,
								'GoodStanding' BOOLEAN NOT NULL DEFAULT 1,
								'HintQuestion' VARCHAR(40) NOT NULL,
								'HintAnswer' VARCHAR(25) NOT NULL,
								'State' VARCHAR(2) NOT NULL,
								'BirthdayDay' INTEGER NOT NULL,
								'BirthdayMonth' INTEGER NOT NULL,
								'LastSeen' DATE NOT NULL
								);");

				$this->db->exec("CREATE TABLE IF NOT EXISTS 'HighScores'
								(
								'Id' INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
								'UserId' INTEGER NOT NULL,
								'Minigame' INTEGER NOT NULL,
								'Mode' INTEGER NOT NULL,
								'Rank' INTEGER NOT NULL,
								'Friend' INTEGER NOT NULL DEFAULT 0,
								'Score' INTEGER NOT NULL DEFAULT 0,

								FOREIGN KEY (UserId) REFERENCES User (Id)
								);");

				$this->db->exec("CREATE TABLE IF NOT EXISTS 'SaveGame'
								(
								'Id' INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
								'UserId' INTEGER NOT NULL,
								
								'AvatarChosen' INTEGER NOT NULL DEFAULT 0,
								'AvatarName' VARCHAR(20) NOT NULL DEFAULT '[unset]',
								'AvatarType' INTEGER NOT NULL DEFAULT 0,
								'AvatarAttrib1' INTEGER NOT NULL DEFAULT 0,
								'AvatarAttrib2' INTEGER NOT NULL DEFAULT 0,
								'AvatarAttrib3' INTEGER NOT NULL DEFAULT 0,
								'AvatarColor1' INTEGER NOT NULL DEFAULT 0,
								'AvatarColor2' INTEGER NOT NULL DEFAULT 0,
								'AvatarColor3' INTEGER NOT NULL DEFAULT 0,
								'AvatarHat' INTEGER NOT NULL DEFAULT 0,
								'AvatarBuddy' INTEGER NOT NULL DEFAULT 0,
								'AvatarCostume' INTEGER NOT NULL DEFAULT 0,
								'AvatarTransport' INTEGER NOT NULL DEFAULT 0,
								
								'HudCitizenship' INTEGER NOT NULL DEFAULT 0,
								'HudFriendship' VARCHAR($MAX_FRIENDSHIP) NOT NULL DEFAULT '$DEFAULT_HUD_FRIENDSHIP',
								'HudPopularity' INTEGER NOT NULL DEFAULT 0,

								'Duty1Type' INTEGER NOT NULL DEFAULT 0,
								'Duty1Value' INTEGER NOT NULL DEFAULT 0,
								'Duty1Total' INTEGER NOT NULL DEFAULT 0,
								'Duty2Type' INTEGER NOT NULL DEFAULT 0,
								'Duty2Value' INTEGER NOT NULL DEFAULT 0,
								'Duty2Total' INTEGER NOT NULL DEFAULT 0,
								'Duty3Type' INTEGER NOT NULL DEFAULT 0,
								'Duty3Value' INTEGER NOT NULL DEFAULT 0,
								'Duty3Total' INTEGER NOT NULL DEFAULT 0,

								'FavorDone' INTEGER NOT NULL DEFAULT 0,
								'FavorLastType' INTEGER NOT NULL DEFAULT 0,
								'FavorWilt' INTEGER NOT NULL DEFAULT 0,
								'FavorEduardo' INTEGER NOT NULL DEFAULT 0,
								'FavorFrankie' INTEGER NOT NULL DEFAULT 0,
								'FavorMac' INTEGER NOT NULL DEFAULT 0,
								'FavorType' INTEGER NOT NULL DEFAULT 0,
								'FavorStep' INTEGER NOT NULL DEFAULT 0,
								'FavorChar' INTEGER NOT NULL DEFAULT 0,
								'FavorRecip' INTEGER NOT NULL DEFAULT 0,
								'FavorObject' INTEGER NOT NULL DEFAULT 0,

								'AdventuresList' VARCHAR($MAX_ADVENTURE) NOT NULL DEFAULT '$DEFAULT_ADVENTURE_LIST',
								'AdventureType' INTEGER NOT NULL DEFAULT 0,
								'AdventureStep' INTEGER NOT NULL DEFAULT 0,
								'AdventureDate' VARCHAR(8) NOT NULL DEFAULT '[unset]',

								'MinigamesList' VARCHAR($MAX_MINIGAME) NOT NULL DEFAULT '$DEFAULT_MINIGAMES_LIST',
								'Album1List' VARCHAR($MAX_ALBUM_LIST) NOT NULL DEFAULT '$DEFAULT_ALBUM_LIST',
								'Album2List' VARCHAR($MAX_ALBUM_LIST) NOT NULL DEFAULT '$DEFAULT_ALBUM_LIST',
								'Album3List' VARCHAR($MAX_ALBUM_LIST) NOT NULL DEFAULT '$DEFAULT_ALBUM_LIST',
								'Album4List' VARCHAR($MAX_ALBUM_LIST) NOT NULL DEFAULT '$DEFAULT_ALBUM_LIST',
								'Album5List' VARCHAR($MAX_ALBUM_LIST) NOT NULL DEFAULT '$DEFAULT_ALBUM_LIST',
								'FurnitureList' VARCHAR($MAX_FURNITURE) NOT NULL DEFAULT '$DEFAULT_FURNITURE_LIST',
								'BirthdayHat' INTEGER NOT NULL DEFAULT 0,
								'NewBirthdayHat' INTEGER NOT NULL DEFAULT 0,

								'MusicTrack' INTEGER NOT NULL DEFAULT 0,
								'AvatarRoom' INTEGER NOT NULL DEFAULT 0,
								'CodesList' VARCHAR($MAX_CODES) NOT NULL DEFAULT '$DEFAULT_CODES_LIST',
								'ObjectsList' VARCHAR($MAX_OBJECTS) NOT NULL DEFAULT '$DEFAULT_OBJECTS_LIST',

								'MyBestFriendPhotoList' VARCHAR($MAX_MY_BEST_FRIEND_PHOTO) NOT NULL DEFAULT '$DEFAULT_MY_BEST_FRIEND_PHOTO_LIST',
								'ToysList' VARCHAR($MAX_TOY) NOT NULL DEFAULT '$DEFAULT_TOYS_LIST',
								'Bus' INTEGER NOT NULL DEFAULT 0,
								'BestFriendCode' VARCHAR($MAX_AVATAR_CODE) NOT NULL DEFAULT '$DEFAULT_BEST_FRIEND_CODE',

								FOREIGN KEY (UserId) REFERENCES User (Id)
								);");
			}
			catch(Exception $e)
			{
				Bfahp::error('Failed to connect to the database with the error: ' . $e->getMessage());
			}
		}
 
		public static function get_instance()
		{
			if(!isset(Database::$instance))
			{
				Database::$instance = new Database();
			}

			return Database::$instance;
		}

		public function insert_user(array $post_data) : bool
		{
			$success = false;
			
			try
			{
				$this->db->exec('BEGIN;');

				// Register the new user.
				$stm = $this->db->prepare('	INSERT INTO User (Username, Password, AvatarCode, HintQuestion, HintAnswer, State, BirthdayDay, BirthdayMonth, LastSeen)
											VALUES (:username, :password, :avatarCode, :hintQuestion, :hintAnswer, :state, :bdate, :bmonth, :lastSeen);');

				// Remove unknown parameter names.
				$allowed_keys = array('username' => 1, 'password' => 1, 'hintQuestion' => 1, 'hintAnswer' => 1, 'bdate' => 1, 'bmonth' => 1, 'state' => 1);
				$post_data = array_intersect_key($post_data, $allowed_keys);

				foreach($post_data as $key => $value)
				{
					$stm->bindValue(":$key", $value);
				}

				$stm->bindValue(":avatarCode", Bfahp::generate_avatar_code($post_data['username']));
				$stm->bindValue(":lastSeen", Bfahp::get_current_date());
				$stm->execute();

				$id = $this->db->lastInsertRowID();

				// Create a default save file.
				$stm = $this->db->prepare('INSERT INTO SaveGame (UserId) VALUES (:userId);');
				$stm->bindValue(":userId", $id);
				$stm->execute();

				// Set the default highscores for every minigame/mode/rank.
				for($minigame = 1; $minigame <= Bfahp::MAX_MINIGAME; ++$minigame)
				{ 
					for($mode = 1; $mode <= Bfahp::MAX_MINIGAME_MODES; ++$mode)
					{
						for($rank = 1; $rank <= Bfahp::MAX_MINIGAME_RANKS; ++$rank)
						{ 
							$stm = $this->db->prepare('INSERT INTO HighScores (UserId, Minigame, Mode, Rank) VALUES (:userId, :minigame, :mode, :rank);');
							$stm->bindValue(":userId", $id);
							$stm->bindValue(":minigame", $minigame);
							$stm->bindValue(":mode", $mode);
							$stm->bindValue(":rank", $rank);
							$stm->execute();
						}
					}
				}

				$this->db->exec('COMMIT;');
				$success = true;
			}
			catch(Exception $e)
			{
				$this->db->exec('ROLLBACK;');
				Bfahp::error('Failed to insert the user given the POST data ' . Bfahp::array_to_string($post_data) . ' with the error: ' . $e->getMessage());
			}

			return $success;
		}

		public function get_user_info(string $username)
		{
			$user_info = false;
			
			try
			{
				$stm = $this->db->prepare('SELECT * FROM User WHERE Username = :username;');
				$stm->bindValue(':username', $username);
				$result = $stm->execute();
				$user_info = $result->fetchArray();
			}
			catch(Exception $e)
			{
				Bfahp::error("Failed to get the info for the user \"$username\" with the error: " . $e->getMessage());
			}

			return $user_info;
		}

		public function user_exists(string $username) : bool
		{
			$user_info = $this->get_user_info($username);
			return $user_info && count($user_info) > 0;
		}

		public function authenticate_user(string $username, string $password) : bool
		{
			$user_info = $this->get_user_info($username);
			return $user_info && $user_info['Password'] === $password;
		}

		public function user_in_good_standing(string $username) : bool
		{
			$user_info = $this->get_user_info($username);
			return $user_info && $user_info['GoodStanding'] == 1;
		}

		public function get_user_save_by_name(string $username)
		{
			$user_save = false;
			
			try
			{
				$stm = $this->db->prepare('SELECT * FROM User U INNER JOIN SaveGame S ON U.Id = S.UserId WHERE Username = :username;');
				$stm->bindValue(':username', $username);
				$result = $stm->execute();
				$user_save = $result->fetchArray();
			}
			catch(Exception $e)
			{
				Bfahp::error("Failed to get the save for the user \"$username\" with the error: " . $e->getMessage());
			}

			return $user_save;
		}

		public function get_user_save_by_code(string $code)
		{
			$user_info = false;
			
			try
			{
				$stm = $this->db->prepare('SELECT * FROM User U INNER JOIN SaveGame S ON U.Id = S.UserId WHERE AvatarCode = :code;');
				$stm->bindValue(':code', $code);
				$result = $stm->execute();
				$user_info = $result->fetchArray();
			}
			catch(Exception $e)
			{
				Bfahp::error("Failed to get the save for the user with the code \"$code\" with the error: " . $e->getMessage());
			}

			return $user_info;
		}

		public function get_all_user_top_highscores(string $username) : array
		{
			$user_highscores = [];
			
			for($minigame = 1; $minigame <= Bfahp::MAX_MINIGAME; ++$minigame)
			{
				$user_highscores[$minigame] = [];

				for($mode = 1; $mode <= Bfahp::MAX_MINIGAME_MODES; ++$mode)
				{
					$user_highscores[$minigame][$mode] = 0;
				}
			}

			try
			{
				$stm = $this->db->prepare('	SELECT Minigame, Mode, MAX(Score) AS MaxUserScore FROM HighScores H
											INNER JOIN User U ON H.UserId = U.Id
											WHERE Friend = 0 AND Username = :username
											GROUP BY Minigame, Mode
											ORDER BY Minigame, Mode;');
				$stm->bindValue(':username', $username);
				$result = $stm->execute();

				while($row = $result->fetchArray())
				{
					$minigame = $row['Minigame'];
					$mode = $row['Mode'];
					$user_highscores[$minigame][$mode] = $row['MaxUserScore'];
				}
			}
			catch(Exception $e)
			{
				Bfahp::error("Failed to get the top highscores for the user \"$username\" with the error: " . $e->getMessage());
			}

			return $user_highscores;
		}

		public function get_best_friend_top_minigame_highscores(string $username, $minigame) : array
		{
			// We'll set every possible mode highscore to zero since it's possible that the following
			// query returns fewer groups than the number of modes. Two examples:
			//
			// - The player doesn't have a best friend, in which case the return value is all zeros.
			// This can also happen if the player removed their best friend or if the server is older
			// than save version 140 (meaning this feature didn't exist in the first place).
			//
			// - The game initialized a minigame's highscores and assigned NPCs to certain ranks (i.e.
			// the Friend column is greater than zero). In this case, if the player got a highscore
			// in the first two modes but not the third, then only two groups would be returned.
			$friend_highscores = array_fill(1, Bfahp::MAX_MINIGAME_MODES, 0);
			
			try
			{
				$stm = $this->db->prepare('	SELECT Mode, MAX(Score) AS MaxBestFriendScore FROM HighScores H
											INNER JOIN User U ON H.UserId = U.Id
											WHERE Friend = 0 AND Minigame = :minigame
											AND AvatarCode = (SELECT BestFriendCode FROM SaveGame S INNER JOIN User U ON S.UserId = U.Id WHERE Username = :username)
											GROUP BY Mode
											ORDER BY Mode;');
				$stm->bindValue(':username', $username);
				$stm->bindValue(':minigame', $minigame);
				$result = $stm->execute();

				while($row = $result->fetchArray())
				{
					$mode = $row['Mode'];
					$friend_highscores[$mode] = $row['MaxBestFriendScore'];
				}
			}
			catch(Exception $e)
			{
				Bfahp::error("Failed to get the top minigame $minigame top highscores for the best friend of the user \"$username\" with the error: " . $e->getMessage());
			}

			return $friend_highscores;
		}

		public function get_user_minigame_and_mode_highscores(string $username, $minigame, $mode) : array
		{
			$user_highscores = [];
			
			try
			{
				$stm = $this->db->prepare('	SELECT Friend, Score FROM Highscores H
											INNER JOIN User U ON H.UserId = U.Id
											WHERE Username = :username AND Minigame = :minigame AND Mode = :mode
											ORDER BY Rank;');
				$stm->bindValue(':username', $username);
				$stm->bindValue(':minigame', $minigame);
				$stm->bindValue(':mode', $mode);
				$result = $stm->execute();

				while($row = $result->fetchArray())
				{
					array_push($user_highscores, $row);
				}
			}
			catch(Exception $e)
			{
				Bfahp::error("Failed to get the minigame $minigame mode $mode highscores for the user \"$username\" with the error: " . $e->getMessage());
			}

			return $user_highscores;
		}

		private function update_generic_user_table(string $table, string $where_condition, array $where_data, array $update_data) : bool
		{
			$query = "UPDATE $table SET ";

			foreach($update_data as $key => $value)
			{
				$query .= "$key = :$key, ";
			}

			$query = rtrim($query, ', ');
			if($where_condition) $query .= " WHERE $where_condition;";

			$success = false;
			
			try
			{
				$stm = $this->db->prepare($query);
				foreach(array_merge($update_data, $where_data) as $key => $value) $stm->bindValue(":$key", $value);
				$stm->execute();
				$success = true;
			}
			catch(Exception $e)
			{
				Bfahp::error("Failed to update the $table table for the user \"$username\" given the POST data " . Bfahp::array_to_string($update_data) . " and the query \"$query\" with the error: " . $e->getMessage());
			}

			return $success;
		}

		public function update_user_info(string $username, array $post_data) : bool
		{
			return $this->update_generic_user_table('User',
													'Username = :Username',
													['Username' => $username],
													$post_data);
		}

		public function update_user_save(string $username, array $post_data) : bool
		{
			return $this->update_generic_user_table('SaveGame',
													'UserId = (SELECT Id FROM User WHERE Username = :Username)',
													['Username' => $username],
													$post_data);
		}

		public function update_user_highscores(string $username, $minigame, $mode, $rank, array $post_data) : bool
		{
			return $this->update_generic_user_table('HighScores',
													'Minigame = :Minigame AND Mode = :Mode AND Rank = :Rank AND UserId = (SELECT Id FROM User WHERE Username = :Username)',
													['Username' => $username, 'Minigame' => $minigame, 'Mode' => $mode, 'Rank' => $rank],
													$post_data);
		}

		public function unlock_everything(string $username) : bool
		{
			$success = false;
			
			try
			{
				$this->db->exec('BEGIN;');

				$stm = $this->db->prepare('	UPDATE SaveGame
											SET AdventuresList = :AdventuresList,
												MinigamesList = :MinigamesList,
												FurnitureList = :FurnitureList,
												BirthdayHat = 1,
												NewBirthdayHat = 1,
												CodesList = :CodesList,
												ToysList = :ToysList
											WHERE UserId = (SELECT Id FROM User WHERE Username = :username);');
				
				$stm->bindValue(':username', $username);
				$stm->bindValue(':AdventuresList', str_repeat('1', Bfahp::MAX_ADVENTURE));
				$stm->bindValue(':MinigamesList', str_repeat('3', Bfahp::MAX_MINIGAME));
				$stm->bindValue(':FurnitureList', str_repeat('1', Bfahp::MAX_FURNITURE));
				$stm->bindValue(':CodesList', str_repeat('1', Bfahp::MAX_CODES));
				$stm->bindValue(':ToysList', str_repeat('1', Bfahp::MAX_TOY));
				$stm->execute();

				$stm = $this->db->prepare('UPDATE HighScores SET Score = 999999 WHERE UserId = (SELECT Id FROM User WHERE Username = :username);');
				$stm->bindValue(':username', $username);
				$stm->execute();

				$this->db->exec('COMMIT;');
				$success = true;
			}
			catch(Exception $e)
			{
				$this->db->exec('ROLLBACK;');
				Bfahp::error("Failed to unlock everything for the user \"$username\" with the error: " . $e->getMessage());
			}

			return $success;
		}

		public function update_all_users_last_seen(string $last_seen) : bool
		{
			return $this->update_generic_user_table('User',
													'',
													[],
													['LastSeen' => $last_seen]);
		}

		public function get_all_users_info()
		{
			$user_info = [];
			
			try
			{
				$stm = $this->db->prepare('SELECT * FROM User ORDER BY Username;');
				$result = $stm->execute();
				while($row = $result->fetchArray())
				{
					array_push($user_info, $row);
				}
			}
			catch(Exception $e)
			{
				Bfahp::error('Failed to get the info for all users with the error: ' . $e->getMessage());
			}

			return $user_info;
		}
	}

	Database::$DATABASE_FILE_PATH = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'bfahp.sqlite';

?>
