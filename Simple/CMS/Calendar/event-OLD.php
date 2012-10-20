<?php
	require_once 'Includes/Configuration.php';
	require_once 'Pager/Pager.php';

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Delete Event #
	/////////////////////////////////////////////////////////////////////////

	if (ctype_digit(trim($_GET['deleteid'])) && trim($_GET['deleteid']) > 0) {

		$del_id = $_GET['deleteid'];

		$sql_event ="SELECT title FROM events WHERE id = ".$del_id;
		$q_event = $db->query($sql_event);
		if (DB::iserror($q_event)) { sb_error($q_event); }

		$r_event = $q_event->fetchrow(DB_FETCHMODE_ASSOC);
		$event_title = $r_event['title'];

		// Remove from db
		$sql = "DELETE FROM events WHERE id = ".$del_id;
		$q = $db->query($sql);
		if (DB::isError($q)) { sb_error($q); }

		// Log activity
		if ($g['log']['active']==1) {
			$log = new LogManager();
			$log->adminLogger($g['log']['file'], $user->get_fullname(), 'Deleted the event '.$page_title);
		}

		// Redirect
		header ("Location: events.php?deleted=".urlencode($page_title));
		exit;
	}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Display Messages #
	/////////////////////////////////////////////////////////////////////////

	$display_message = setDisplayMessage($display_message, 'updated', 'The event <strong>"%GET%"</strong> has been updated successfully.');
	$display_message = setDisplayMessage($display_message, 'added', 'The event <strong>"%GET%"</strong> has been added successfully.');
	$display_message = setDisplayMessage($display_message, 'error', '%GET%', true);

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Page Settings #
	/////////////////////////////////////////////////////////////////////////

	$admin_page_id = ($admin_page_id)?$admin_page_id:4;
	
	$page_vars			  = build_page($db, $admin_page_id);
	$display_page_title	  = $page_vars['title'];
	$display_mainmenu	  = $page_vars['mainmenu'];
	$display_utility_menu = $page_vars['utilitymenu'];
	$display_submenu	  = $page_vars['submenu'];
	$g['page']['markup']  = ($page_vars['markupref']) ? $g['page']['markup'] : '';
	$g['page']['instructions'] .= '';

	$body = "events";

	$display_content_title = $event_display_title;

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Default Variables #
	/////////////////////////////////////////////////////////////////////////

	if ($_GET['session'] == 'clear' || $_GET['new']==1) {
		$_SESSION['event_id'] = '';
	}

	if (ctype_digit(trim($_GET['event'])) && trim($_GET['event']) > 0) {
		$_SESSION['event_id'] = $_GET['event'];
	}
	
	preg_match('/[A-Za-z]{3}\s[A-Za-z]{3}\s\d{2}\s\d{4}\s\d{2}:\d{2}:\d{2}/', $_GET['start'] ,$startDate);
	preg_match('/[A-Za-z]{3}\s[A-Za-z]{3}\s\d{2}\s\d{4}\s\d{2}:\d{2}:\d{2}/', $_GET['end'] ,$endDate);

	if (isset($_GET['start'])) {
		$default_vars['start_date'] = date('Y-m-d', strtotime($startDate[0]));
	}

	if (isset($_GET['end'])) {
		$default_vars['end_date'] = date('Y-m-d', strtotime($endDate[0]));
	}

	if (isset($_GET['all_day'])) {
		$default_vars['all_day'] = (boolean)$_GET['all-day'];
	}
	
	$end_date = array();

	if (ctype_digit(trim($_SESSION['event_id'])) && trim($_SESSION['event_id']) > 0) {

		$current_event = $_SESSION['event_id'];
		$idset = true;

		$sql = "SELECT * FROM events WHERE id = ".$current_event;
		$q = $db->query($sql);
		if (DB::isError($q)) { sb_error($q); }

		$rows = $q->fetchrow(DB_FETCHMODE_ASSOC);
		$default_vars = array();

		foreach ($rows AS $key => $value) {
			$default_vars[$key] = $value;
		}

		fillDefaultRepeats();
	} else {
		$idset = false;
		$default_vars['all_day'] = "1";
	}

	$all_day = ($default_vars['all_day'] == "1" ? true : false);

	/////////////////////////////////////////////////////////////////////////
	# BEGIN JQuery Daterange #
	/////////////////////////////////////////////////////////////////////////

	// TODO: Pass on default date and time
	$g['page']['header'] .= <<<HTML

	<script type="text/javascript" src="{$g['page']['js']}/DateRange/daterangepicker.jQuery.js"></script>
	<script type="text/javascript" src="{$g['page']['js']}/TimePicker.js"></script>
	<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/themes/ui-lightness/jquery-ui.css" type="text/css" />
	<link rel="stylesheet" href="{$g['page']['js']}/DateRange/ui.daterangepicker.css" type="text/css" />

	<script type="text/javascript">
		$(function(){
		
			checkRepeatVals();
		
			$('#repeat_event').change(function() {
				checkRepeatVals();
			});

			// Event Start
			$('#eventStart').datepicker({
				dateFormat: "yy-mm-dd"
			});

			$('#eventStartTime').timepicker({
				stepMinute: 15,
				hour: 12,
				minute: 00,
				timeFormat: "hh:mm:ss"
			});

			$('#eventStart').change(function() {
				if ($('#eventEnd').val() == "") {
					$('#eventEnd').datepicker( "option", "defaultDate", $('#eventStart').val() );
				}
			});

			// Event End
			$('#eventEnd').datepicker({
				stepMinute: 15,
				hour: 12,
				minute: 00,
				dateFormat: "yy-mm-dd"
			});

			$('#eventEndTime').timepicker({
				stepMinute: 15,
				hour: 12,
				minute: 00,
				timeFormat: "hh:mm:ss"
			});

			$('#all_day').click(function() {
				if ($(this).is(':checked')) {
					$('#row_start_time').hide();
					$('#row_end_time').hide();
				} else {
					$('#row_start_time').show();
					$('#row_end_time').show();
				}
			});

			$('#repeat_until').datepicker({
				dateFormat: "yy-mm-dd"
			});
		});
		
		function checkRepeatVals() {
			if ($('#repeat_event').val() != 0) {
			
				if ($('#repeat_event').val() == 1 || $('#repeat_event').val() == 3) { // WEEKLY & BIWEEKLY
					$("#monthly_options").hide();
					$("#weekly_options").show();
				} else if ($('#repeat_event').val() == 4) { // MONTHLY
					$("#monthly_options").show();
					$("#weekly_options").hide();
				} else {
					$("#monthly_options").hide();
					$("#weekly_options").hide();
				}
			
				if (!$('#repeat_options').is(":visible"))
					$('#repeat_options').slideToggle('fast');
			} else {
				if ($('#repeat_options').is(":visible"))
					$('#repeat_options').slideToggle('fast');
			}
		}

	</script>

HTML;

	/////////////////////////////////////////////////////////////////////////
	# BEGIN QuickForm Setup & Templates #
	/////////////////////////////////////////////////////////////////////////

	// Form setup -----------------------------------------------------------

	if ($idset) {
		$target = $_SERVER['PHP_SELF'].'?event='.$current_event;
	} else {
		$target = $_SERVER['PHP_SELF'];
	}

	// Instantiate QuickForm
	$form = new HTML_QuickForm('frm', 'post', $target);

	// Default Template -----------------------------------------------------

	$checkbox_items = <<<HTML
		<td style="border:1px solid #ccc;text-align:center;">{element}</td>
HTML;

	// Need to close with html (</td></tr>)
	$repeat_event_start = <<<HTML

	<!-- BEGIN error -->
	<tr>
	<td colspan="2">
	<div class="error">{error}</div>
	</td>
	</tr>
	<!-- END error -->

	<tr>
		<td class="label" valign="top">
		<span <!-- BEGIN required -->class="required"<!-- END required --> >
		{label}
		</span>
		</td>
		<td valign="top">{element}


HTML;

	$renderer =& $form->defaultRenderer();
	$renderer->clearAllTemplates();
	$renderer->setFormTemplate($qf_container);
	$renderer->setHeaderTemplate($qf_header);
	$renderer->setElementTemplate($qf_element);
	$renderer->setElementTemplate($qf_button, 'btnUpdate');
	$renderer->setElementTemplate($qf_button, 'btnAdd');

	$renderer->setElementTemplate($repeat_event_start, 'repeat_event');
	
	$default = <<<HTML
	<label>
		{label}
	</label>
	{element}
HTML;

	$renderer->setElementTemplate($default, 'week_number');
	$renderer->setElementTemplate($default, 'repeat_until');
	$renderer->setElementTemplate($default, 'week_day');
	$renderer->setElementTemplate($default, 'select_this_date');
	$renderer->setElementTemplate($checkbox_items, 'repeat_su');
	$renderer->setElementTemplate($checkbox_items, 'repeat_mo');
	$renderer->setElementTemplate($checkbox_items, 'repeat_tu');
	$renderer->setElementTemplate($checkbox_items, 'repeat_we');
	$renderer->setElementTemplate($checkbox_items, 'repeat_th');
	$renderer->setElementTemplate($checkbox_items, 'repeat_fr');
	$renderer->setElementTemplate($checkbox_items, 'repeat_sa');

	/////////////////////////////////////////////////////////////////////////
	# BEGIN QuickForm Fields #
	/////////////////////////////////////////////////////////////////////////

	// Header ---------------------------------------------------------------

	if ($idset) {
		// Header
		$display_content_title = '<a href="events.php">Events</a> &nbsp;&rarr;&nbsp;You are updating "'.$default_vars['title'].'"';
	} else {
		// Header
		$display_content_title = '<div class="head-flag-links"><a href="events.php">Cancel</a></div><a href="events.php">Events</a> &nbsp;&rarr;&nbsp;Add a New event';
	}

	// Event Title ------------------------------------------------------------

	$form->addElement('text', 'title', 'Event Name:', 'class=long');
	$form->addRule('title', 'Please provide a name for this event.', 'required');
	$form->addRule('title', 'Can not exceed 50 characters for the name. Please try something shorter.', 'maxlength', 75);

	// Event Type -------------------------------------------------------------

	$sql_event_type = "SELECT id, name FROM event_categories ORDER BY rank";
	$q_event_type = $db->query($sql_event_type);
	if (DB::isError($q_event_type)) { sb_error($q_event_type); }
	while ($r_event_type = $q_event_type->fetchrow(DB_FETCHMODE_ASSOC) ) {
		$event_types[$r_event_type['id']] = $r_event_type['name'];
	}
	$form->addElement('select', 'event_type', 'Event Type:', $event_types);
	$form->addRule('event_type', 'Please select an event type.', 'required');

	// Event dates ------------------------------------------------------------

	$form->addElement('checkbox', 'all_day', null, '&nbsp;&nbsp;Check to make this an all day event <em>(i.e. Holidays, Days Off)</em>.', array('id'=>'all_day'));

	$form->addElement('text', 'start_date', 'Event Start:', array('class'=>'short', 'id'=>'eventStart'));
	$form->addRule('start_date', 'Please provide a start date and time for your event', 'required');

	$form->addElement('text', 'start_time', 'Start Time:', array('class'=>'short', 'id'=>'eventStartTime'));

	$form->addElement('text', 'end_date', 'Event End:', array('class'=>'short', 'id'=>'eventEnd'));
	$form->addRule('end_date', 'Please provide a ending date and time for your event', 'required');

	$form->addElement('text', 'end_time', 'End Time:', array('class'=>'short', 'id'=>'eventEndTime'));

	// Event Description ------------------------------------------------------

	// Place a default row ammount
	$event_copy_rows = ($event_copy_rows) ? $event_copy_rows:8;
	if ($g['xipe']['wysiwyg']==1) {
		$eventcopy_attrs = array('id'=>'eventcopy', "cols"=>"55", "rows"=>$event_copy_rows);
		$form->addElement('html', textarea_wysiwyg('eventcopy'));
	} else {
		$eventcopy_attrs = array("rows"=>$page_copy_rows, "cols"=>"55");
	}
	$form->addElement('textarea', 'description', '<span class="highlight">'.$event_copy_label.'</span>', $eventcopy_attrs);
	$form->addRule('copy', 'Please provide a description for '.$event_copy_label.'.', 'required');

	// Event Location ---------------------------------------------------------

	$form->addElement('text', 'location', 'Event Location:', 'class="medium"');

	// Event Repeat -----------------------------------------------------------

	$repeat_options = array(
			0 => 'No Repeat',
			2 => 'Daily',
			1 => 'Weekly',
			3 => 'Bi-Weekly',
			4 => 'Monthy',
			5 => 'Quarterly',
			6 => 'Annually');

	$form->addElement('select', 'repeat_event', 'Repeat this event:', $repeat_options, array('id'=>'repeat_event'));

	// Event Repeat Options ---------------------------------------------------

	$form->addElement('html', '<div id="repeat_options" style="display:none;background:#eee;border:1px solid #ccc;padding:20px;">');

	$form->addElement('text', 'repeat_until', 'Repeat Until:', array('class'=>'short', 'id'=>'repeat_until'));

	$form->addElement('html', '<div id="monthly_options">');

	$week_number = array(
		1 => 'First',
		2 => 'Second',
		3 => 'Third',
		4 => 'Fourth'
	);

	$form->addElement('select', 'week_number', 'Week Number:', $week_number);

	$week_day =  array(
		"SU" => "Sunday",
		"MO" => "Monday",
		"TU" => "Tuesday",
		"WE" => "Wednesday",
		"TH" => "Thursday",
		"FR" => "Friday",
		"SA" => "Saturday"
	);

	$form->addElement('select', 'week_day', 'Day in Week:', $week_day);

	$form->addElement('checkbox', 'select_this_date', 'Use selected date every month');

	$form->addElement('html', '
		</div>
		<div id="weekly_options">
			<table id="days_of_the_week">
				<tr>
					<th style="border:1px solid #ccc;text-align:center;">SU</th>
					<th style="border:1px solid #ccc;text-align:center;">MO</th>
					<th style="border:1px solid #ccc;text-align:center;">TU</th>
					<th style="border:1px solid #ccc;text-align:center;">WE</th>
					<th style="border:1px solid #ccc;text-align:center;">TH</th>
					<th style="border:1px solid #ccc;text-align:center;">FR</th>
					<th style="border:1px solid #ccc;text-align:center;">SA</th>
				</tr>
				<tr>'
	);

	$form->addElement('checkbox', 'repeat_su', null);
	$form->addElement('checkbox', 'repeat_mo', null);
	$form->addElement('checkbox', 'repeat_tu', null);
	$form->addElement('checkbox', 'repeat_we', null);
	$form->addElement('checkbox', 'repeat_th', null);
	$form->addElement('checkbox', 'repeat_fr', null);
	$form->addElement('checkbox', 'repeat_sa', null);

	$form->addElement('html', '
				</tr>
			</table>
		</div>
	</div>');
	
	// Close out repeat_event
	$form->addElement('html', '</td></tr>');

	// Event Reminders --------------------------------------------------------

	$reminder_options = array(
			0 => 'No Reminders',
			1 => 'Send email one week prior',
			2 => 'Send email 3 days prior',
			3 => 'Send email the day of');

	$form->addElement('select', 'reminders', 'Set Reminder:', $reminder_options);

	// BUTTON -----------------------------------------------------------------

	if ($idset) {
		// Update info button
		$form->addElement('image', 'btnUpdate', $g['page']['buttons'].'/btn-updateevent.gif');
		// Page ID
		$form->addElement('hidden', 'id');
	} else {
		$form->addElement('image', 'btnAdd', $g['page']['buttons'].'/btn-addnewevent.gif');
	}
	// Form protection
	$form->addElement('hidden', 'formcheck', $display_page_title);

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Delete Option #
	/////////////////////////////////////////////////////////////////////////

	if ($idset && !$default_vars['protect']) {
		$linkstuff = js_confirm('?deleteid='.$default_vars['id'],
								'Delete this event',
								'Are you sure you want to delete the event -> '.$default_vars['title'].'?',
								'Delete '.$default_vars['title'],
								'attention');
		$display_content_title = '<div class="head-flag-links"><a href="events.php">Cancel</a> | '.$linkstuff.'</div>'.$display_content_title;
	}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Process form #
	/////////////////////////////////////////////////////////////////////////

	// Keeps errors from displaying if form coming in from elsewhere
	if ($form->getSubmitValue('formcheck') == $display_page_title) {

		if ($form->validate()) {
			$submit_vars = array(
				'id'				=> safe_escape($form->getSubmitValue('id')			,'int',true),
				'title'				=> safe_escape($form->getSubmitValue('title')		,'str',true),
				'description'		=> safe_escape($form->getSubmitValue('description')	,'textarea',true),
				'location'			=> safe_escape($form->getSubmitValue('location')	,'str',true),
				'start_date'		=> safe_escape($form->getSubmitValue('start_date')	,'str',true),
				'end_date'			=> safe_escape($form->getSubmitValue('end_date')	,'str',true),
				'start_time'		=> safe_escape($form->getSubmitValue('start_time')	,'str',true),
				'end_time'			=> safe_escape($form->getSubmitValue('end_time')	,'str',true),
				'all_day'			=> safe_escape($form->getSubmitValue('all_day')		,'chk',true),
				'event_type'		=> safe_escape($form->getSubmitValue('event_type')	,'int',true),
				'repeat_event'		=> safe_escape($form->getSubmitValue('repeat_event'),'int',true),
				'repeat_until'		=> safe_escape(getRepeatUntilVal()					,'str',true),
				'repeat_byday'		=> safe_escape(getByDay()							,'str',true)
			);

	// Update ---------------------------------------------------------------

			if ($form->getSubmitValue('btnUpdate_x')) {

				$sql_u = <<<SQL

					UPDATE events SET
						title			= {$submit_vars['title']},
						description		= {$submit_vars['description']},
						location		= {$submit_vars['location']},
						repeat_event	= {$submit_vars['repeat_event']},
						start_date		= {$submit_vars['start_date']},
						end_date		= {$submit_vars['end_date']},
						start_time		= {$submit_vars['start_time']},
						end_time		= {$submit_vars['end_time']},
						all_day			= {$submit_vars['all_day']},
						event_type		= {$submit_vars['event_type']},
						repeat_until	= {$submit_vars['repeat_until']},
						repeat_byday	= {$submit_vars['repeat_byday']}
					WHERE
						id				= {$submit_vars['id']}
SQL;

				$q_u = $db->query($sql_u);
				if (DB::isError($q_u)) { sb_error($q_u); }

				// Log activity
				if ($g['log']['active']==1) {
					$log = new LogManager();
					$log->adminLogger($g['log']['file'], $user->get_fullname(), 'Updated the event '.$form->getSubmitValue('title'));
				}

				go_to(null, '?event='.$submit_vars['id'].'&updated='.urlencode(stripslashes($submit_vars['title'])));

			}

	// Insert ---------------------------------------------------------------

			if ($form->getSubmitValue('btnAdd_x')) {
				$sql_add_event = <<<SQL
				INSERT INTO events (title, description, location, repeat_event, start_date, end_date, start_time, end_time, all_day, event_type, repeat_until, repeat_byday)
					VALUES ({$submit_vars['title']}, {$submit_vars['description']}, {$submit_vars['location']}, {$submit_vars['repeat_event']}, {$submit_vars['start_date']}, {$submit_vars['end_date']},
					{$submit_vars['start_time']}, {$submit_vars['end_time']}, {$submit_vars['all_day']}, {$submit_vars['event_type']}, {$submit_vars['repeat_until']}, {$submit_vars['repeat_byday']})
SQL;

				$q_add_event = $db->query($sql_add_event);
				if (DB::isError($q_add_event)) { sb_error($q_add_event); }

				// Get new id
				$new_event_id = $db->getOne( "SELECT LAST_INSERT_ID() FROM events" );

				// Log activity
				if ($g['log']['active']==1) {
					$log = new LogManager();
					$log->adminLogger($g['log']['file'], $user->get_fullname(), 'Added the event '.$form->getSubmitValue('title'));
				}

				go_to(null, '?event='.$new_event_id.'&added='.urlencode(stripslashes($form->getSubmitValue('title'))));

			}

		} // end validate

	} // end formcheck

	$form->setDefaults($default_vars);
	$display_form = $form->toHtml();

	if ($all_day) {

	$g['page']['footer'] .= <<<HTML

<SCRIPT type="text/javascript">
	$('#row_start_time').hide();
	$('#row_end_time').hide();
</SCRIPT>

HTML;
	}
	/////////////////////////////////////////////////////////////////////////
	# BEGIN Xipe #
	/////////////////////////////////////////////////////////////////////////

	$tpl = new HTML_Template_Xipe($g['xipe']['options']);
	$tpl->compile($g['xipe']['path'].'/default.tpl');
	include($tpl->getCompiledTemplate());



	/////////////////////////////////////////////////////////////////////////
	# BEGIN Functions #
	/////////////////////////////////////////////////////////////////////////

	function fillDefaultRepeats() {

		global $default_vars;

		$repeat_type = (int) $default_vars['repeat_event'];

		switch ($repeat_type) {

			case 1: // WEEKLY
			case 3: // BIWEEKLY

				$days = explode(',', $default_vars['repeat_byday']);

				//var_dump($days);

				foreach ($days as $day) {
					if ($day == "SU") $default_vars['repeat_su'] = "1";
					if ($day == "MO") $default_vars['repeat_mo'] = "1";
					if ($day == "TU") $default_vars['repeat_tu'] = "1";
					if ($day == "WE") $default_vars['repeat_we'] = "1";
					if ($day == "TH") $default_vars['repeat_th'] = "1";
					if ($day == "FR") $default_vars['repeat_fr'] = "1";
					if ($day == "SA") $default_vars['repeat_sa'] = "1";
				}

				//var_dump($default_vars);

				break;

			case 4: // MONTHLY

				$day = $default_vars['repeat_byday'];

				if (!is_integer($day)) {
					$default_vars['week_number'] = substr($day, 0, 1);
					$default_vars['week_day'] = substr($day, 1, 2);
				}

				break;

			default: // DO NOTHING
				break;
		}

	}

	function getRepeatUntilVal($split_event = false) {

		global $form;

		$repeat_until_orig = $form->getSubmitValue('repeat_until');

		if ($repeat_until_orig == "")
			return null;

		preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $repeat_until_orig, $matches);

		if ($matches)
			return $repeat_until_orig;
			
		$sTime = strtotime($form->getSubmitValue('start_time'));
		if ($sTime == "")
			$sTime = "00:00:00";
			
		$uDate = strtotime($repeat_until_orig);

		$repeat_until = date('Y-m-d H:i:s', mktime(date("g", $sTime), (date('i', $sTime) - ($split_event ? 1 : 0)), 00, date('m', $uDate), date('d', $uDate), date('Y', $uDate)));

		return $repeat_until;
	}

	function getByDay() {

		global $form;

		$repeat_type = (int)$form->getSubmitValue('repeat_event');

		$byDay = null;

		switch ($repeat_type) {

			case 1: // WEEKLY
			case 3: // BIWEEKLY
			
				$byDay .= ($form->getSubmitValue('repeat_su') ? "SU," : "");
				$byDay .= ($form->getSubmitValue('repeat_mo') ? "MO," : "");
				$byDay .= ($form->getSubmitValue('repeat_tu') ? "TU," : "");
				$byDay .= ($form->getSubmitValue('repeat_we') ? "WE," : "");
				$byDay .= ($form->getSubmitValue('repeat_th') ? "TH," : "");
				$byDay .= ($form->getSubmitValue('repeat_fr') ? "FR," : "");
				$byDay .= ($form->getSubmitValue('repeat_sa') ? "SA," : "");

				$byDay = rtrim($byDay, ',');

				break;

			case 4: // MONTHLY

				if (!$form->getSubmitValue('select_this_date'))
					$byDay = $form->getSubmitValue('week_number') . $form->getSubmitValue('week_day');
				else
					$byDay = date('d', strtotime($form->getSubmitValue('start_date')));

				break;

			default: // NO BYDAY VALUE TO SET
				// DO NOTHING!
				break;
		}

		return $byDay;
	}


?>
