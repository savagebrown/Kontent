<?php
	require_once 'Includes/Configuration.php';
	require_once 'Simple/Image/NewSize.php';
	require_once 'Pager/Pager.php';
	require_once 'Functions/Calendar.php';

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Display Messages #
	/////////////////////////////////////////////////////////////////////////

	$display_message = setDisplayMessage($display_message, 'deleted', 'The event <strong>"%GET%"</strong> has been deleted successfully.');
	$display_message = setDisplayMessage($display_message, 'error', '%GET%', true);

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Page Settings #
	/////////////////////////////////////////////////////////////////////////

	$admin_page_id = ($admin_page_id)?$admin_page_id:4;
	
	$page_vars				= build_page($db, $admin_page_id);
	$display_page_title		= $page_vars['title'];
	$display_mainmenu		= $page_vars['mainmenu'];
	$display_utility_menu	= $page_vars['utilitymenu'];
	$display_submenu		= $page_vars['submenu'];
	$g['page']['instructions']	= (!isset($_GET['new']))?'<p><a class="neutral" href="event.php?new=1"><img src="'.$g['page']['buttons'].'/btn-big-addnewevent.png" alt="Add a new event" /></a></p>':'';
	$g['page']['instructions']	.= '<p class="sidebar-link"><img src="'.$g['page']['images'].'/icon-cat-add.gif" align="absmiddle" />&nbsp;&nbsp;<a href="event_categories.php">Manage Event Type</a></p>';
	$g['page']['markup']		= ($page_vars['markupref']) ? $g['page']['markup'] : '';
	
	// Get Events -------------------------------------------------------------
	
	$sql_u = "SELECT *, E.id AS eid FROM events E LEFT JOIN event_categories EC ON E.event_type = EC.id";

	$events_query = $db->query($sql_u);
	if (DB::isError($events_query)) { sb_error($events_query); }
		
	$events = array();

	while($event = $events_query->fetchrow(DB_FETCHMODE_ASSOC)) {
		$curEvent = array();
		$curEvent['id']			  	= $event['eid'];
		$curEvent['title']			= mysql_real_escape_string($event['title']);
		$curEvent['description']	= $event['description'];
		$curEvent['location']		= mysql_real_escape_string($event['location']);
		$curEvent['repeat_event']	= $event['repeat_event'];
		$curEvent['repeat_until']	= $event['repeat_until'];
		$curEvent['repeat_byday']	= $event['repeat_byday'];
		$curEvent['start_date']		= $event['start_date'];
		$curEvent['end_date']		= $event['end_date'];
		$curEvent['event_type']		= $event['event_type'];
		$curEvent['start_time']		= $event['start_time'];
		$curEvent['end_time']		= $event['end_time'];
		$curEvent['all_day']		= $event['all_day'];
		$curEvent['className']		= $event['class'];

		$events[] = $curEvent;
	}
	
	// Full Calendar ----------------------------------------------------------
	
	$g['page']['header'] .= <<<HTML

	<script type="text/javascript" src="{$g['page']['js']}/fullcalendar/fullcalendar.min.js"></script>
	<link rel='stylesheet' type='text/css' href='{$g['page']['js']}/fullcalendar/fullcalendar.css' />
	<link rel='stylesheet' type='text/css' href='{$g['page']['js']}/fullcalendar/fullcalendar.print.css' media='print' />
	<script type='text/javascript'>


	$(document).ready(function() {
	
		var date = new Date();
		var d = date.getDate();
		var m = date.getMonth();
		var y = date.getFullYear();

		var calendar = $('#calendar').fullCalendar({
			header: {
				left: 'prev,next today',
				center: 'title',
				right: 'month,agendaWeek,agendaDay'
			},
			selectable: true,
			selectHelper: true,
			select: function(start, end, allDay) {
				window.location.replace("event.php?new=1&start="+start+"&end="+end+"&allDay="+allDay);
			},
			editable: true,
			events: [
HTML;

			foreach ($events as $event) {
				$g['page']['header'] .= getAllDatesForEvent($event, true);
			}
			
			$g['page']['header'] = rtrim($g['page']['header'], ',');
			
$g['page']['header'] .= <<<HTML
			]
		});
		
	});

	</script>
	
	<style type='text/css'>

		#calendar-container {
			margin: 45px;
			}

	</style>

HTML;

	$body = "events";

	$display_content_title = 'Events';

	$calendar = <<<HTML
	
	<div id="calendar-container">
		<div id='calendar'></div>
	</div>
	
HTML;

	$display_form = $calendar;

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Xipe #
	/////////////////////////////////////////////////////////////////////////

	$tpl = new HTML_Template_Xipe($g['xipe']['options']);
	$tpl->compile($g['xipe']['path'].'/default.tpl');
	include($tpl->getCompiledTemplate());

?>
