<?php

	require_once 'Strings.php';

	function sb_error($object) {
		global $g;

		$page = $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];

		$g_admin_error_message = <<<HTML

<div style="background:#fff0ae;
			color:red;
			padding:0 15px;
			border-top:2px solid red;
			border-bottom:2px solid red;
			margin: 15px 0;">
	<p>An error has occurred. Please
	<a href="mailto:{$g['administrator']['email']}?subject=My site broke. Please help.">
	email the developer</a> (or call {$g['administrator']['phone']}). Include as much
	detail as possible so that the error can be recreated. Copy and paste any
	error messages and describe the steps taken before the error occurred.
	Sorry for the inconvenience and thank you for your cooperation. We will
	look into this issue immediately.</p>
</div>

HTML;
		// Log the error
		require_once 'Simple/Log/LogManager.php';
		$log = new LogManager();
		// Log activity
		if ($g['log']['active']==1) {
			$chars = array('[',']');
			$r_chars = array('<hr />','<hr />');
			$error = str_replace($chars, $r_chars, $page.'<hr />'.$object->getMessage().'<hr />'.$object->userinfo);
			$log->adminLogger($g['log']['error'], 'error', $error);
		}

		switch ( $g['global']['status'] ) {
			case 'testing':
				die(
					str_replace('mysql://koray:adanaspor@','mysql://**login**@','<div style="margin:10px;padding:20px;">
					 <p><strong>Page:</strong> '.$page.'</p>
					 <p><strong>Browser:</strong> '.$_SERVER['HTTP_USER_AGENT'].'</p><hr />
					 <h3 style="background:#ffc;color:red;">'.
					 $object->getMessage().'</h3>
					 <hr />
					 <pre style="background:#f5f5f5;padding:10px">'.wraplines(str_replace("[","<hr />[", $object->userinfo), 100).'</pre>'.
					 $g_admin_error_message.'
					 </div>'));
			break;
			case 'LIVE':
				header('Location: /');
				exit;
			break;
		}
	}

?>