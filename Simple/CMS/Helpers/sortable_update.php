<?php
	require_once 'Includes/Configuration.php';
	// TODO: Place in updatelist method in Sortable Class
	foreach($_GET['item'] as $key=>$value) {
	 	$sql_u = "UPDATE ".$_SESSION['sortable_table']." SET rank = ".$key." WHERE id = '".$value."'";
	 	$q_u = $db->query($sql_u);
	 	if (DB::isError($q_u)) { sb_error($q_u); }
	 }

	// DEBUGPOINT: print_r($_GET['item']);

?>
