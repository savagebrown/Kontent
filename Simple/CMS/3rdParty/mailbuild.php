<?php

	// Log activity
	if ($g['log']['active']==1) {
		$log = new LogManager();
		$log->adminLogger($g['log']['file'], $user->get_fullname(), 'Logged into Newsletter Manager');
	}

	$forward = <<<HTML

	<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

	<html lang="en">
	<head>
		<title>Newsletter System Redirect</title>

	</head>
	<body onLoad="document.frm.submit()">

	<form name="frm" id="frm" action="https://nurd.createsend.com/login.aspx" method="post">
	<div>
	<input type="hidden" name="username" id="username" value="$username" />
	<input type="hidden" name="password" id="password" value="$password" />
	</div>
	</form>
	
	

	</body>
	</html>

HTML;
	print $forward;
	exit;
?>