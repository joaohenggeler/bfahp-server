<?php
	header("Content-type: text/html");

	/*
		This file defines how the Game endpoint responds when the player goes to the Shockwave movie's page. This logic was based on the game() function in
		the main.py script in the Python server.
	*/

	require_once(dirname(__FILE__) . '/Bfahp.php');

	Bfahp::log_request_values('Game');

	$config = Bfahp::load_config_from_file();

	if($config['ShowBufferPage'])
	{
		// If the player wants to always see the buffer page before going into the game,
		// we must first check if came from the home or the buffer pages. Although the
		// server passes the value from the 'useBuffer' query parameter in the home page
		// to the Flash movie's 'CN_useBuffer' variable, the player wouldn't normally see
		// this buffer screen.

		$came_from_buffer = false;
		$referer_url = filter_input(INPUT_SERVER, 'HTTP_REFERER', FILTER_SANITIZE_STRING);
		if($referer_url)
		{
			$referer_filename = basename(parse_url($referer_url, PHP_URL_PATH));
			$came_from_buffer = ($referer_filename == 'buffer.jsp');
		}

		// If we came here from the home page after registering or logging in.
		if(!$came_from_buffer)
		{
			header('Location: /house/buffer.jsp');
			exit();
		}
		else
		{
			Bfahp::info("The request for the game page came from the buffer at $referer_url");
		}
	}

	$game_page = file_get_contents(dirname(__FILE__) . '/templates/game.html');
	
	if($game_page !== false)
	{
		// This is meant to emulate the behavior of the Python server (which uses Flask). We could have also done this in the respective JSP file using PHP,
		// but let's keep this convention to stay consistent with the original Python server.
		$game_page = str_replace('{{ version }}', Bfahp::VERSION_STRING, $game_page);
		Bfahp::info('Serving the parameterized game page with the value sw2 (' . Bfahp::VERSION_STRING . ').');
	}
	else
	{
		$game_page = 'Failed to read the game template page.';
		Bfahp::error($game_page);
	}

	echo $game_page;
?>
