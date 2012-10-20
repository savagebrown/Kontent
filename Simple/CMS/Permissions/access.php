<?php

	include_once 'constants.default.php';

	require_once 'Simple/Session/Wrapper.php';
	require_once 'Simple/Access/Authenticate.php';
	require_once 'Simple/Access/User.php';
	require_once 'Simple/Log/LogManager.php';

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Authenticate User #
	/////////////////////////////////////////////////////////////////////////

	// Instantiate authentication class
	$auth_log_settings = ($g['log']['active'])?$g['log']['file']:false;
	$auth =& new simple_access_authenticate ($db,'index.php','ADANASPOR', false, $auth_log_settings);
	// Logging out user
	if ($_GET['action'] == 'logout') { $auth->logout(); }
	// Instantiate user class
	$user =& new simple_access_user($db, $auth->session);

?>