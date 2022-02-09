<?php
	header("Content-type: text/xml");

	/*
		This file defines how the Registration endpoint responds when the player creates an account. This logic was based on the registration.py script in
		the Python server.
	*/

	require_once(dirname(__FILE__) . '/Bfahp.php');
	require_once(dirname(__FILE__) . '/Database.php');

	Bfahp::log_request_values('Registration');

	$username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
	$password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
	$password_confirm = filter_input(INPUT_POST, 'passwordConfirm', FILTER_SANITIZE_STRING);
	$hint_question = filter_input(INPUT_POST, 'hintQuestion', FILTER_SANITIZE_STRING);
	$hint_answer = filter_input(INPUT_POST, 'hintAnswer', FILTER_SANITIZE_STRING);
	$birthday_day = filter_input(INPUT_POST, 'bdate', FILTER_VALIDATE_INT);
	$birthday_month = filter_input(INPUT_POST, 'bmonth', FILTER_VALIDATE_INT);
	$state = filter_input(INPUT_POST, 'state', FILTER_SANITIZE_STRING);

	class StatusCodes
	{
		const ACCOUNT_CREATED = 0;
		const INTERNAL_ERROR_1 = 1;
		const INTERNAL_ERROR_2 = 2;
		const INTERNAL_ERROR_3 = 3;
		const USERNAME_NOT_SET = 200;
		const PASSWORD_NOT_SET = 201;
		const BDATE_NOT_SET = 202;
		const BMONTH_NOT_SET = 203;
		const HINT_QUESTION_NOT_SET = 204;
		const HINT_ANSWER_NOT_SET = 205;
		const STATE_NOT_SET = 206;
		const USERNAME_ALREADY_EXISTS = 207;
		const INVALID_USERNAME = 208;
		const INVALID_PASSWORD = 209;
		const MISMATCHED_PASSWORDS = 210;
	}

	$status_codes = [];

	if(!$username) array_push($status_codes, StatusCodes::USERNAME_NOT_SET);
	if(!$password) array_push($status_codes, StatusCodes::PASSWORD_NOT_SET);
	if(!$birthday_day) array_push($status_codes, StatusCodes::BDATE_NOT_SET);
	if(!$birthday_month) array_push($status_codes, StatusCodes::BMONTH_NOT_SET);
	if(!$hint_question) array_push($status_codes, StatusCodes::HINT_QUESTION_NOT_SET);
	if(!$hint_answer) array_push($status_codes, StatusCodes::HINT_ANSWER_NOT_SET);
	if(!$state) array_push($status_codes, StatusCodes::STATE_NOT_SET);

	if($username && Bfahp::credentials_contain_invalid_characters($username)) array_push($status_codes, StatusCodes::INVALID_USERNAME);
	if($password && Bfahp::credentials_contain_invalid_characters($password)) array_push($status_codes, StatusCodes::INVALID_PASSWORD);
	if($password && $password !== $password_confirm) array_push($status_codes, StatusCodes::MISMATCHED_PASSWORDS);

	// This date is important since it's tied to the birthday event, so we want to make sure we don't allow the user
	// to lock themselves out of it. The game always assumes that the year is 2000 when validating this date.
	if($birthday_day && $birthday_month && !checkdate($birthday_month, $birthday_day, '2000'))
	{
		array_push($status_codes, StatusCodes::BDATE_NOT_SET);
		array_push($status_codes, StatusCodes::BMONTH_NOT_SET);
	}

	$registered_user = false;

	if(empty($status_codes))
	{
		$db = Database::get_instance();

		if($db->user_exists($username))
		{
			array_push($status_codes, StatusCodes::USERNAME_ALREADY_EXISTS);
			Bfahp::info("The user \"$username\" already exists.");
		}
		else
		{
			if($db->insert_user($_POST))
			{
				$registered_user = true;
				array_push($status_codes, StatusCodes::ACCOUNT_CREATED);
				Bfahp::info("Registered the user \"$username\".");
			}
			else
			{
				array_push($status_codes, StatusCodes::INTERNAL_ERROR_1);
				Bfahp::error("Could not register the user \"$username\".");
			}		
		}
	}

	$response = null;

	/*
		Examples:

		<status code="0"  message="/house/Game?1621370490" />

		or

		<registration>
			
			<message username="example">
				<altname name="example123" reservationId="0" />
				<altname name="example456" reservationId="0" />
				<altname name="example789" reservationId="0" />
			</message>

			<message>
				<status code="200"/>
			</message>

			<message>
				<status code="202"/>
			</message>

			<message>
				<status code="203"/>
			</message>

		</registration>
	*/

	if($registered_user)
	{
		$response = new SimpleXMLElement('<status/>');
		$response->addAttribute('code ', $status_codes[0]);
		$response->addAttribute('message ', '/house/Game?' . time());
		setcookie('login', $username);
		
		Bfahp::set_config_values_in_file(['LastUser' => $username]);
	}
	else
	{
		$response = new SimpleXMLElement('<registration></registration>');

		$message = $response->addChild('message');
		$message->addAttribute('username', $username);

		for($i = 0; $i < 3; ++$i)
		{
			$name = $username . mt_rand(0, 999);
			$altname = $message->addChild('altname');
			$altname->addAttribute('name', $name);
			$altname->addAttribute('reservationId', '0');
		}

		foreach($status_codes as $code)
		{
			$message = $response->addChild('message');
			$status = $message->addChild('status');
			$status->addAttribute('code ', $code);
		}

		Bfahp::error("Failed to register the user \"$username\".");
	}

	$response = $response->asXML();

	Bfahp::info('Found the following registration status codes: ' . implode(', ', $status_codes));
	Bfahp::info("Generated the following registration response: $response");

	echo $response;
?>
