<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>BFAHP Control Panel</title>

	<style type="text/css">

		body {
			font: normal 13px Verdana, Arial, sans-serif;
		}

		table, th, td {
			border: 1px solid black;
		}

		th, td {
			padding: 10px;
		}

		iframe {
			height: 200px;
			width: 800px;
			resize: both;
			overflow: auto;
		}

	</style>
</head>
<body>

	<h1>BFHAP Control Panel</h1>

	<?php
		require_once(dirname(__FILE__) . '/../Bfahp.php');
		require_once(dirname(__FILE__) . '/../Database.php');
	?>

	<p>Welcome to the Big Fat Awesome House Party control panel. You can use this page to cheat, change the game's date, list all accounts, or test the different server endpoints.</p>

	<hr>

	<h3>Information</h3>

	<?php
		$sqlite_version = SQLite3::version();
		$config = Bfahp::load_config_from_file();
		$last_user = ($config['LastUser']) ? ($config['LastUser']) : ("Flashpoint");

		echo 'BFAHP Version: ' . $last_user . ' ' . Bfahp::VERSION_STRING . '<br>';
		echo 'Save Version: ' . Bfahp::SAVE_VERSION . '<br>';
		echo 'SQLite3 Version: ' . $sqlite_version['versionString'] . '<br>';
		echo 'Config: ' . Bfahp::array_to_string($config) . '<br>';

		echo '<br>';

		echo 'POST Values: ' . Bfahp::array_to_string($_POST) . '<br>';
		echo 'GET Values: ' . Bfahp::array_to_string($_GET) . '<br>';
		echo 'COOKIE Values: ' . Bfahp::array_to_string($_COOKIE) . '<br>';
		echo 'SESSION Values: ' . Bfahp::array_to_string($_SESSION) . '<br>';

		echo '<br>';

		echo '<strong>Accounts:</strong>';

		echo '<br><br>';

		echo '<table>';

		$db = Database::get_instance();
		$user_info = $db->get_all_users_info();
		
		$columns = ['Username', 'Password', 'AvatarCode', 'BirthdayDay', 'BirthdayMonth', 'LastSeen'];

		echo '<tr>';
		foreach($columns as $key) echo '<th>' . $key . '</th>';
		echo '</tr>';

		foreach($user_info as $info)
		{
			echo '<tr>';
			foreach($columns as $key)
			{
				echo '<td>' . $info[$key] . '</td>';
			}
			echo '</tr>';
		}

		echo '</table>';
	?>

	<p><strong>Remember that these accounts are only used to save your progress locally.</strong></p>

	<hr>

	<h3>Configuration</h3>

	<p>Use the following options to toggle logging, toggle the home page's layout (simple or complete), toggle the buffer page between the home and the game pages, or to backup the game's save file.</p>

	<form action="Configuration.php" method="post" target="configuration-response">
		<input type="hidden" name="action" value="toggle_logging"/>
		<input type="submit" value="Toggle Logging">
	</form>

	<br>

	<form action="Configuration.php" method="post" target="configuration-response">
		<input type="hidden" name="action" value="toggle_home_layout"/>
		<input type="submit" value="Toggle Home Page Layout">
	</form>

	<br>

	<form action="Configuration.php" method="post" target="configuration-response">
		<input type="hidden" name="action" value="toggle_buffer_page"/>
		<input type="submit" value="Toggle Buffer Page">
	</form>

	<br>

	<form action="Configuration.php" method="post" target="configuration-response">
		<input type="hidden" name="action" value="backup_save"/>
		Source Save File: <?php echo Database::$DATABASE_FILE_PATH; ?>
		<br>
		Destination Directory: <input type="text" name="destination_path" size="200" required/>
		<br>
		<strong>Make sure the destination path points to an existing directory.</strong>
		<br>
		<input type="submit" value="Backup Save">
	</form>

	<br>

	Response:<br>
	<iframe name="configuration-response"></iframe>

	<hr>

	<h3>Cheats</h3>

	<p>Use the following options to unlock minigames, album photos, hats, and furniture for your imaginary friend. You can also max out your citizenship bar to go on adventures with Bloo.</p>

	<form action="Cheats.php" method="post" target="cheats-response">
		Username: <input type="text" name="username" value="<?php echo $last_user; ?>" required/>
		<br>
		Cheat:
		<select name="cheat">
			<option value="citizenship">Max Citizenship</option>
			<option value="popularity">Max Popularity</option>
			<option value="birthday_hat">Unlock First Birthday Hat</option>
			<option value="minigames">Unlock All Minigames</option>
			<option value="everything">Unlock Everything</option>
		</select>
		<br>
		<input type="submit" value="Cheat">
	</form>

	<br>

	Response:<br>
	<iframe name="cheats-response"></iframe>

	<br><br>

	<p>Enter the following codes in Notey to unlock collectibles.</p>

	<strong>Secret Codes:</strong> VIKING, OWLFLY, BIGTOE, CREATE, EAGLES, DVDBUD, EGGLIT, HOLLOW, BUTANE, FOSSIL, IGLOOS, CANVAS, ELDEST, RIBBED, VROOOM, TOASTY, HANGER, QUARRY, ROCKON, CAVERN, UNLOCK, SECURE, ACCESS, ENCODE, CIPHER

	<hr>

	<h3>Time Machine</h3>

	<p>Use the following options to change the game's date. This is useful for speeding up your progress or for activating special events.</p>

	<p><strong>Note that the date is changed permanently unless reset. For example, if you go to January 31st and then return the next day, the game date will move to February 1st. Use the reset button to revert back to normal.</strong></p>

	<form action="Time.php" method="post" target="time-response">
		<input type="hidden" name="action" value="change_date"/>
		Date: <input type="date" name="target_date" value="2009-07-13" required/>
		<input type="submit" value="Time Travel">
	</form>

	<br>

	<form action="Time.php" method="post" target="time-response">
		<input type="hidden" name="action" value="reset_date"/>
		<input type="submit" value="Reset To Today">
	</form>

	<br>

	<form action="Time.php" method="post" target="time-response">
		<input type="hidden" name="action" value="skip_days"/>
		Days: <input type="number" name="days" value="1" min="1" required/>
		<input type="submit" value="Skip Days">
	</form>

	<br>

	<form action="Time.php" method="post" target="time-response">
		<input type="hidden" name="action" value="birthday"/>
		Username: <input type="text" name="username" value="<?php echo $last_user; ?>" required/>
		<input type="submit" value="Set Birthday">
	</form>

	<br>

	<form action="Time.php" method="post" target="time-response">		
		Events:
		<select name="action">
			<option value="april_fools">April Fools</option>
		</select>

		<input type="submit" value="Set Event">
	</form>

	<br>

	Response:<br>
	<iframe name="time-response"></iframe>

	<hr>

	<h3>Registration</h3>

	<p>This form may be used to register a new account. <strong>This section was added for testing purposes. It's recommended that you use <a href="http://awesomehouseparty.com/" target="_blank">the main game page</a> to register.</strong></p>

	<form action="../Registration" method="post" target="registration-response">
		Username: <input type="text" name="username" value="Flashpoint"><br>
		Password: <input type="text" name="password" value="123"><br>
		Confirm Password: <input type="text" name="passwordConfirm" value="123"><br>

		Hint Question: <input type="text" name="hintQuestion" value="What's your favorite color?"><br>
		Hint Answer: <input type="text" name="hintAnswer" value="Red"><br>

		Birthday Day: <input type="number" name="bdate" value="15" min="1" max="31"><br>
		Birthday Month: <input type="number" name="bmonth" value="5" min="1" max="12"><br>
		State: <input type="text" name="state" value="AL" minlength="2" maxlength="2"><br>

		<input type="submit" value="Register">
	</form>

	<br>

	Response:<br>
	<iframe name="registration-response"></iframe>

	<hr>

	<h3>Login</h3>

	<p>This form may be used to authenticate an account. <strong>This section was added for testing purposes. It's recommended that you use <a href="http://awesomehouseparty.com/" target="_blank">the main game page</a> to log in.</strong></p>

	<form action="../Login" method="post" target="login-response">
		Username: <input type="text" name="username" value="Flashpoint"><br>
		Password: <input type="text" name="password" value="123"><br>

		<input type="submit" value="Login">
	</form>

	<br>

	<form action="../Login" method="get" target="_blank">
		<input type="hidden" name="action" value="logout"/>

		<input type="submit" value="Logout">
	</form>

	<br>

	Response:<br>
	<iframe name="login-response"></iframe>

	<hr>

	<h3>Service</h3>

	<p>This form may be used to check how the server responds to specific game events. <strong>This section was added for testing purposes. You shouldn't use it during a regular playthrough.</strong></p>

	<form action="../Service" method="post" target="service-response">
		Action:
		<select name="EventCode">
			<option value="103">GetFirstTimeState</option>
		</select>
		<input type="submit" value="Submit">
	</form>

	<br>

	Response:<br>
	<iframe name="service-response"></iframe>

</body>
</html>