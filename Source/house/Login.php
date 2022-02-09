<?php
	header("Content-type: text/xml");

	/*
		This file defines how the Login endpoint responds when the player authenticates their account. This logic was based on the authentication.py script in
		the Python server.
	*/

	require_once(dirname(__FILE__) . '/Bfahp.php');
	require_once(dirname(__FILE__) . '/Database.php');

	Bfahp::log_request_values('Login');

	class StatusCodes
	{
		const AUTHENTICATED = 0;
		const INTERNAL_ERROR_1 = 1;
		const INTERNAL_ERROR_2 = 2;
		const INTERNAL_ERROR_3 = 3;
		const RESET_PASSWORD = 50;
		const USERNAME_NOT_SET = 100;
		const PASSWORD_NOT_SET = 101;
		const USER_DOES_NOT_EXIST = 102;
		const INCORRECT_PASSWORD = 103;
		const ACCOUNT_CLOSED = 104;
		const MISMATCHED_PASSWORDS_WHILE_CHANGING = 105;
		const OLD_AND_NEW_PASSWORDS_ARE_IDENTICAL = 106;
		const USER_DOES_NOT_EXIST_IN_ALTER_PASSWORD = 107;
		const MISMATCHED_HINT_ANSWERS = 108;
		const INTERNAL_ERROR_4 = 109;
		const INVALID_PASSWORD = 110;
	}

	$request_method = filter_input(INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_STRING);
	$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
	$status_code = StatusCodes::INTERNAL_ERROR_1;
	$message = '';

	switch($request_method)
	{
		case('POST'):
		{
			$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
			$username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
			$password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

			if(!$username)
			{
				$status_code = StatusCodes::USERNAME_NOT_SET;
				break;
			}

			$db = Database::get_instance();
			$user_info = $db->get_user_info($username);
			
			if($action == 'forgotPassword' || $action == 'forgotPasswordWithHint')
			{
				if(!$user_info)
				{
					$status_code = StatusCodes::USER_DOES_NOT_EXIST_IN_ALTER_PASSWORD;
					break;
				}

				$answer = filter_input(INPUT_POST, 'answer', FILTER_SANITIZE_STRING);

				if($action == 'forgotPassword')
				{
					$status_code = StatusCodes::AUTHENTICATED;
					$message = $user_info['HintQuestion'];
					break;
				}
				else if($action == 'forgotPasswordWithHint' && $answer === $user_info['HintAnswer'])
				{
					$status_code = StatusCodes::AUTHENTICATED;
					$message = $user_info['Password'];
					break;
				}

				$status_code = StatusCodes::MISMATCHED_HINT_ANSWERS;
			}
			else if($action == 'changePassword')
			{
				if(!$password)
				{
					$status_code = StatusCodes::PASSWORD_NOT_SET;
					break;
				}

				if(!$user_info)
				{
					$status_code = StatusCodes::USER_DOES_NOT_EXIST_IN_ALTER_PASSWORD;
					break;
				}

				$new_password = filter_input(INPUT_POST, 'newpass', FILTER_SANITIZE_STRING);
				$password_confirm = filter_input(INPUT_POST, 'passwordConfirm', FILTER_SANITIZE_STRING);
				$old_password = filter_input(INPUT_POST, 'oldpass', FILTER_SANITIZE_STRING);

				if($new_password && Bfahp::credentials_contain_invalid_characters($new_password))
				{
					$status_code = StatusCodes::INVALID_PASSWORD;
					break;
				}

				if($new_password !== $password_confirm)
				{
					$status_code = StatusCodes::MISMATCHED_PASSWORDS_WHILE_CHANGING;
					break;
				}

				if($new_password === $old_password)
				{
					$status_code = StatusCodes::OLD_AND_NEW_PASSWORDS_ARE_IDENTICAL;
					break;
				}
				
				$required_keys = ['username', 'oldpass', 'verifypass', 'newpass', 'hintQuestion', 'hintAnswer'];
				if(Bfahp::array_has_keys($_POST, $required_keys))
				{
					$hint_question = filter_input(INPUT_POST, 'hintQuestion', FILTER_SANITIZE_STRING);
					$hint_answer = filter_input(INPUT_POST, 'hintAnswer', FILTER_SANITIZE_STRING);

					if($db->update_user_info($username, ['Password' => $password, 'HintQuestion' => $hint_question, 'HintAnswer' => $hint_answer]))
					{
						$status_code = StatusCodes::AUTHENTICATED;
						break;
					}
				}

				$status_code = StatusCodes::INTERNAL_ERROR_1;
			}
			else
			{	
				if(!$password)
				{
					$status_code = StatusCodes::PASSWORD_NOT_SET;
					break;
				}

				if(!$user_info)
				{
					$status_code = StatusCodes::USER_DOES_NOT_EXIST;
					break;
				}

				if(!$db->authenticate_user($username, $password))
				{
					$status_code = StatusCodes::INCORRECT_PASSWORD;
					break;
				}

				$reset_password_status = filter_input(INPUT_POST, 'resetPswdStatus', FILTER_VALIDATE_BOOLEAN);
				if($reset_password_status)
				{
					$status_code = StatusCodes::RESET_PASSWORD;
					break;
				}

				if(!$db->user_in_good_standing($username))
				{
					$status_code = StatusCodes::ACCOUNT_CLOSED;
					break;
				}

				$status_code = StatusCodes::AUTHENTICATED;
			}

		} break;

		case('GET'):
		{
			$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);

			if($action === 'logout')
			{
				$username = filter_input(INPUT_COOKIE, 'login', FILTER_SANITIZE_STRING);
				Bfahp::info("Logging out the user \"$username\".");

				if(isset($_COOKIE['login']))
				{
					unset($_COOKIE['login']); 
					setcookie('login', '', time() - 3600);
				}

				header('Location: /house/home.jsp?startPoint=login');
				exit();
			}

		} break;

		default:
		{
			Bfahp::error("Unhandled request method $request_method.");
		} break;
	}

	if($status_code == StatusCodes::AUTHENTICATED && empty($message))
	{
		Bfahp::info("Authenticated the user \"$username\" successfully.");
		$message = '/house/Game?' . time();
		setcookie('login', $username);

		Bfahp::set_config_values_in_file(['LastUser' => $username]);
	}

	$response = new SimpleXMLElement('<status/>');

	/*
		Examples:

		<status code="0" message="/house/Game?1621370490"/>

		or

		<status code="103" message=""/>
	*/

	$response->addAttribute('code ', $status_code);
	$response->addAttribute('message ', $message);

	$response = $response->asXML();

	Bfahp::info("Generated the following authentication response: $response");

	echo $response;
?>
