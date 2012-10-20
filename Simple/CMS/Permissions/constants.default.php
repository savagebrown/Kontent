<?php

	# Modify these constants to match the $_POST variable used in login form
	// Name to use for login variable e.g. $_POST['login']
	@define ( 'POST_LOGIN','login');
	// Name to use for password variable e.g. $_POST['password']
	@define ( 'POST_PASSW','password');

	# Modify these constants to match the session variables
	// Name to use for login variable e.g. $_SESSION['login']
	@define ( 'S_LOGIN','login');
	// Name to use for login variable e.g. $_SESSION['login']
	@define ( 'S_PASSW','password');
	// Name to use for user id variable e.g. $_SESSION['user_id']
	@define ( 'S_USERID','user_id');
	// Name to use for user id variable e.g. $_SESSION['user_id']
	@define ( 'S_LOGIN_TIME','logged_in_at');
	// Name to use for full name variable e.g. $_SESSION['user_fullname'];
	@define ( 'S_FULL_NAME', 'user_fullname');

	# Modify these constants to match your user login table
	// Name of users table
	@define ( 'USER_TABLE','users');
	// Name of user_id column in table
	@define ( 'USER_COL_ID','user_id');
	// Name of access_level column in table
	@define ( 'USER_COL_ACCESSLEVEL','access_level');
	// Name of login column in table
	@define ( 'USER_COL_LOGIN','login');
	// Name of password column in table
	@define ( 'USER_COL_PASSW','password');
	// Name of email column in table
	@define ( 'USER_COL_EMAIL','email');
	// Name of firstname column in table
	@define ( 'USER_COL_FIRST','firstName');
	// Name of lastname column in table
	@define ( 'USER_COL_LAST','lastName');

	# Modify these constants to match your user permissions table
	// Name of Permission table
	@define ( 'PERM_TABLE','permissions');
	// Permission table id column
	@define ( 'PERM_COL_USERID','user_id');

?>