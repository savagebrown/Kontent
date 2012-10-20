<?php

	/**
	 * Simple Ajax Sort
	 * This class is very specific to <ul> and jquery sortable. Depends on 
	 * $_SESSION['sortable_table'] being before initiation set and has a "rank" 
	 * field.
	*/
	class simple_ajax_sortable {

		function __construct($db, $table) {
			$this->db = $db;
			$this->table = $table;
		}

		public function getList($filter_field='', $filter_value='') {
			if ($filter_field && $filter_value) {
				$filter = "WHERE $filter_field = $filter_value";
			}
			$sql = "SELECT * FROM {$this->table} $filter ORDER BY rank";
			$q = $this->db->query($sql);
			if ($this->db->isError($q)) { sb_error($q); }
			$results = array();
			while($r = $q->fetchrow(DB_FETCHMODE_ASSOC)) {
				$results[] = $r;
			}
			return $results;
		}

		public function updateList() {

			foreach($_GET['item'] as $key=>$value) {
			 	$sql_u = "UPDATE ".$_SESSION['sortable_table']." SET rank = ".$key." WHERE id = '".$value."'";
			 	$q_u = $db->query($sql_u);
			 	if (DB::isError($q_u)) { sb_error($q_u); }
			 }

		}

		public function getJS() {

			$js = <<<HTML

	<script>
		$(function() {
			$( "#listContainer" ).sortable({
				stop:function() {
					$.ajax({
						handle : '.handle',
						type: "GET",
						url: "sortable_update.php",
						data: $("#listContainer").sortable("serialize"),
						// DEBUGGING: success: function(message) { alert(message) }
					});
				}
			});
		});
	</script>


HTML;
			return $js;
		}
	}

?>