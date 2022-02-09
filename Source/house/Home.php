<?php
	header("Content-type: text/html");

	/*
		This file defines how the home.jsp endpoint responds when the player goes to the game's home page. This logic was based on the home() function in
		the main.py script in the Python server.
	*/

	require_once(dirname(__FILE__) . '/Bfahp.php');

	Bfahp::log_request_values('Home');

	$config = Bfahp::load_config_from_file();
	$layout_filename = ($config['UseSimpleHomeLayout']) ? ('home_simple.html') : ('home_complete.html');

	$template_file_path = dirname(__FILE__) . "/templates/$layout_filename";
	$home_page = file_get_contents($template_file_path);

	if($home_page !== false)
	{
		$start_point = filter_input(INPUT_GET, 'startPoint', FILTER_SANITIZE_STRING);
		$use_buffer = filter_input(INPUT_GET, 'useBuffer', FILTER_SANITIZE_STRING);
		
		$authenticated = filter_input(INPUT_COOKIE, 'CartoonNetworkBFAHPreg', FILTER_VALIDATE_BOOLEAN);
		if(!$authenticated) $start_point = 'login';

		if(!$start_point) $start_point = 'animation';
		if(!$use_buffer) $use_buffer = 'true';

		// This is meant to emulate the behavior of the Python server (which uses Flask). We could have also done this in the respective JSP file using PHP,
		// but let's keep this convention to stay consistent with the original Python server.
		$home_page = str_replace('{{ startPoint }}', $start_point, $home_page);
		$home_page = str_replace('{{ useBuffer }}', $use_buffer, $home_page);
		
		Bfahp::info("Serving the parameterized home page \"$layout_filename\" with the values startPoint ($start_point) and useBuffer ($use_buffer). Authenticated? " . (($authenticated) ? ('Yes') : ('No')));
	}
	else
	{
		$home_page = "Failed to read the home template page: $template_file_path";
		Bfahp::error($home_page);
	}

	echo $home_page;
?>
