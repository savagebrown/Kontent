<?php

	require_once 'Connection.php';
    require_once 'Functions/Strings.php';

    /////////////////////////////////////////////////////////////////////////
    # BEGIN Password Recovery #
    /////////////////////////////////////////////////////////////////////////
    
     if ($_POST['recover'] == "retrieve") {

        $username = mysql_real_escape_string(trim($_POST['username']));
        $sql_userlookup ="SELECT firstName, lastName, email, password FROM users WHERE login = '".$username."'";
        $q_userlookup = $db->query($sql_userlookup);
        if (DB::iserror($q_userlookup)) { sb_error($q_userlookup); }
        
        $r_userlookup = $q_userlookup->fetchrow(DB_FETCHMODE_ASSOC);

        if ($q_userlookup->numrows() == 1) {

			// Set email components
            $email_recipient = $r_userlookup['firstName']." ".$r_userlookup['lastName']." <".$r_userlookup['email'].">";
            $email_subject   = $g['company']['name']." Administration Account Info";
            $email_message   = $r_userlookup['firstName']." ".$r_userlookup['lastName'].",\n\n";
//TODO: Update to insert new password into email because recovering a password will no longer be possible with md5 hashed passwords.
            $email_message  .= "You have requested the recovery of your password. Your password is: ".$r_userlookup['password'].".\n\n";
            $email_message  .= "You can log into the system at {$g['company']['admin_url']}\n\n";
            $email_message  .= "If you have any questions or comments please email us at {$g['administrator']['email']}.";
            $email_extra     = "From: {$g['company']['name']} Administration <{$g['administrator']['email']}>\r\nReply-To: {$g['company']['name']} Administration <{$g['administrator']['email']}>\r\n";

            // Send confirmation
            mail ($email_recipient, $email_subject, $email_message, $email_extra);

            go_to(null, '?password=sent');

        } else {

            go_to(null, '?password=error');

        }

    }

    /////////////////////////////////////////////////////////////////////////
    # BEGIN Display Message #
    /////////////////////////////////////////////////////////////////////////

    // Show who defaults
    $hide_form_login = '';
    $hide_form_retrieve = ' style="display:none;"';

    // Show login
    if ($_GET['password'] == 'sent') {
        $display_message = '<p class="success">Your password has been sent. Please check your inbox.</p>';
        $hide_form_login = '';
        $hide_form_retrieve = ' style="display:none;"';
    }

    if ($_GET['password'] == 'error') {
        $display_message = '<p class="error">We could not find a match for that username. Please try again. If you continue having problems please email the <a href="'.$g['administrator']['email'].'">System Administrator</a></p>';
        $hide_form_login = ' style="display:none;"';
        $hide_form_retrieve = '';
    }

    /////////////////////////////////////////////////////////////////////////
    # BEGIN Define Target & Errors #
    /////////////////////////////////////////////////////////////////////////

    // TODO: Get admin preferences and forward to prefered page
    // TODO: Build login page that redirects. Only form on index.html
    
    $target = 'pages.php';

    // If $_GET['from'] comes from the Auth class
    if (isset ($_GET['from']) && $_GET['from'] != '') {
        $target = $_GET['from'];
        $error_message = '<p class="error">The username and/or the password you entered is invalid. Please try again.</p>';
    }

	// TODO: add scriptaculous support for show/hide toggle

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?php echo $g['company']['name']; ?> Administration Sign In</title>
<link rel="stylesheet" rev="stylesheet" href="css/login.css" media="screen" />
<script language="javascript">

function expandFirst() {
			document.getElementById(expandFirst.arguments[0]).style.display = "block";
			for (var i=1; i<expandFirst.arguments.length; i++) {
				document.getElementById(expandFirst.arguments[i]).style.display = "none";
			}
		}
function expandCollapse() {
	for (var i=0; i<expandCollapse.arguments.length; i++) {
		var element = document.getElementById(expandCollapse.arguments[i]);
		element.style.display = (element.style.display == "none") ? "block" : "none";
	}
}

</script>
</head>

<body onload="document.forms[0].elements[0].focus();">
<div id="container">

    <form id="login" action="<?php echo $target; ?>" method="post"<?php echo $hide_form_login; ?>>

        <h1><?php echo $g['company']['name']; ?> Administration</h1>
        <?php echo $display_message; ?>
        <?php echo $error_message; ?>

        <table>
            <tr><td colspan="2" class="label"><label for="login">Username:</label></td></tr>
            <tr><td colspan="2"><span><input class="big" name="login" type="text" id="login" /></span></td></tr>

            <tr><td colspan="2" class="label"><label for="password">Password:</label></td></tr>
            <tr>
            	<td><span><input class="big" name="password" type="password" id="password" /></span></td>
            	<td nowrap="nowrap" class="right"><em>( <a href="javascript: expandCollapse('retrieve', 'login');document.forms[1].elements[0].focus();">I forgot my password</a> )</em></td>
           	</tr>

            <tr>
                <td colspan="2" class="label"><input id="bttnSubmit" name="bttnSubmit" type="image" src="Images/Buttons/btn-signin.gif" /></td>
            </tr>
        </table>
    </form>

    <form id="retrieve" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" <?php echo $hide_form_retrieve; ?>>

        <h1>SavageBrown Administration</h1>
        <?php echo $display_message; ?>

        <table>
            <tr><td colspan="2">Simply enter your Username below, and we'll email
                you your password. If you don't remember your username, please
                contact the <a href="mailto:<?php echo $g['administrator']['email']; ?>">System Administrator</a>.</td></tr>

            <tr><td colspan="2" class="label"><span><input class="big" name="username" type="username" id="username" /></span></td></tr>

            <tr>
                <td class="label"><input id="bttn_retrieve" name="bttn_retrieve" type="image" src="Images/Buttons/btn-emailpassword.gif" /></td>
                <td class="right"><em>(<a href="javascript: expandCollapse('retrieve', 'login');document.forms[0].elements[0].focus();">cancel</a>)</em></td>
            </tr>
        </table>
        <input type="hidden" name="recover" id ="recover" value="retrieve" />
    </form>

</div>
</body>
</html>