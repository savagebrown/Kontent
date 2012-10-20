<?php
	ini_set("include_path", '/Users/sb/Sites/pear/php'     . PATH_SEPARATOR . ini_get("include_path"));
	ini_set("include_path", '/Users/sb/Sites/Library'     . PATH_SEPARATOR . ini_get("include_path"));

    // include class
    require_once 'Simple/Image/MySQLGallery.php';

	// include db
	/* PEAR:DB */
    require_once 'DB.php';
	require_once 'Functions/Error.php';

    /////////////////////////////////////////////////////////////////////////
    # BEGIN Create Database Object #
    /////////////////////////////////////////////////////////////////////////

    $host   = 'localhost';  // Hostname of MySQL server
    $dbUser = 'root';       // Username for MySQL
    $dbPass = 'adanaspor';  // Password for user
    $dbName = 'caryweldy';  // Database name

    // Connect to MySQL
    $db = DB::connect("mysql://$dbUser:$dbPass@$host/$dbName");
    if (DB::iserror($db)) { sb_error($db); }
    

    // Instantiate SavageGallery Class
	require_once 'Simple/Image/MySQLGallery.php';
    $sg = new simple_mysql_album($db, 'albums', 'http://savagebrown.local/~sb/CaryWeldy/Images/Gallery/Albums/', true);
	
	print $sg->get_groups('FOCUS_TREE');
	
	exit;
	

?>