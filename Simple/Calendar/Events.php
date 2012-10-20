<?php

	/*
		We should not include database management in the class. This is
		just a get and display class.

	*/

	class simple_calendar_events {

		/* list main private and public variables as they are created */
		var $db;
		
		/**
         * Unix Epoch time starting date for calendar 
         * @access public
         * @var int
         */
		var $startDate;
		
		/**
         * Day the week starts on, Sunday (0) or Monday (1) 
         * @access public
         * @var int
         */
		var $week_starts_on = 0;
		
		
		public function __construct($db, $initDate=date('U')) {
			$this->db = $db;
			$this->set_events();
			
			$this->startDate = $initDate;
		}


	/////////////////////////////////////////////////////////////////////////
	# BEGIN PUBLIC #
	/////////////////////////////////////////////////////////////////////////

		/**
		 * This can be used for temporary events, google, ical standard feed, etc.
		 *
		 * @param
		 *
		 * @return void
		 * @author Killer Interactive, LLC
		 **/
		public function add_events($source, $source_config) {

		}

		/**
		 * Returns array of events
		 *
	     * @param integer Number of Events (default 3)
	     *
		 * @return array
		 * @author Killer Interactive, LLC
		 **/
		public function get_events($num_events=3) {
	
			$year = date('Y', $this->startDate);
			$month = date('m', $this->startDate);
			
			$first_day_this_month = date('Y-m-d', strtotime("first day of this month", $this->startDate));
			$first_day_next_month = date('Y-m-d', strtotime("first day of next month", $this->startDate));
			
			$query = <<<SQL
SELECT 
	e.id, 
	e.title, 
	e.location, 
	e.start_date, 
	e.end_date, 
	e.start_time, 
	e.end_time, 
	e.repeat_event, 
	e.repeat_until, 
	e.repeat_byday, 
	e.all_day,
	ec.class
FROM events AS e 
LEFT JOIN event_categories AS ec ON e.event_type = ec.id
WHERE 
  (start_date BETWEEN "{$first_day_this_month}" AND "{$first_day_next_month}" AND repeat_event = 0) OR
  (start_date < "{$first_day_next_month}" AND (repeat_until = "0000-00-00 00:00:00" OR repeat_until >= "{$first_day_this_month}") AND repeat_event <> 0)
ORDER BY day(start_date) ASC, start_time ASC
	  
SQL;
			
			$results = $this->db->query($query);
			if (DB::isError($results)) { sb_error($results); }
			
			while($row = $results->fetchrow(DB_FETCHMODE_ASSOC)) {
			
				/* Repeat Options
					0 => 'No Repeat',
					1 => 'Weekly',
					2 => 'Daily',
					3 => 'Bi-Weekly',
					4 => 'Monthy',
					5 => 'Quarterly',
					6 => 'Annually');
				*/
				
				switch ($row['repeat_event']) {
				
					case 0: // NO REPEAT
	
						$day = date('j', strtotime($row['start_date']));
						$startTime = date('g:i a', strtotime($row['start_time']));
						$event[$day] .= "<span class=\"{$row['class']}\">{$startTime} {$row['title']}</span>";
						
						break;
	
					case 1: // WEEKLY REPEAT
						// SU MO TU WE TH FR SA
						$byday = explode(',', $row['repeat_byday']);
						
						$shortDays = array(
							'SU'=>'Sunday',
							'MO'=>'Monday',
							'TU'=>'Tuesday',
							'WE'=>'Wednesday',
							'TH'=>'Thursday',
							'FR'=>'Friday',
							'SA'=>'Saturday'
						);
						
						foreach ($byday as $day) {
							
							$startDay = 0;
							// Get the first instance of the repeat day by starting at the last day from the previous month
							$nextDate = date('Y-m-d', strtotime('next '.$shortDays[$day], strtotime('previous day', strtotime($first_day_this_month))));
							// Get the day of that instance
							$nextDay = date('j', strtotime($nextDate));
							
							// Loop through all instances of the repeat day
							while($nextDay > $startDay) {
							
								// Add event to list
								$startTime = date('g:i a', strtotime($row['start_time']));
								$event[$nextDay] .= "<span class=\"{$row['class']}\">{$startTime} {$row['title']}</span>";
								
								// Get next instance of repeat day
								$startDay = $nextDay;
								$nextDate = date('Y-m-d', strtotime('next '.$shortDays[$day], strtotime($nextDate)));
								$nextDay = date('j', strtotime($nextDate));	
								
							}
							
						}
						
						break;
					
					case 2: // DAILY REPEAT
						// START DAY -> END DAY
						
						break;
						
					case 3: // BI_WEEKLY
						// NOT USED CURRENTLY
					
						break;
						
					case 4: // MONTHLY
						// 1SA (1st Saturday), etc...
					
						break;
						
					case 5: // QUARTERLY
						// NOT USED CURRENTLY
						
						break;
						
					case 6: // YEARLY
						// REPEATED ON START DATE EVERY YEAR
					
						break;
				}
			}
			
			mysql_close($link);
			
			return $event;
	
		}


		/**
		 * Return formatted events
		 *
		 * @param string event template "<li id="%ID%"><strong>%TITLE%</strong><br>%EXCERPT%</li>"
	     * @param integer Number of Events (default 3)
	     *
		 * @return string
		 * @author Killer Interactive, LLC
		 **/
		public function get_event_list($template, $num_rows=3) {

		}

		/**
		 * return event detail in array with following keys:
		 * id, title, excerpt, description, start_date, end_date, start_time, end_time, etc (as we know them add them)
		 *
	     * @param integer Event Id
	     *
		 * @return array
		 * @author Killer Interactive, LLC
		 **/
		public function get_event_detail($event_id) {

		}

		/**
		 * event item template
		 *
		 * @return void
		 * @author Killer Interactive, LLC
		 **/
		public function set_template($template) {
			$this->populate_template($template);
		}

		/**
		 * After events are set this should only be a call:
		 * $c = new Events();
		 * print $c->print_calendar();
		 *
		 * @return void
		 * @author Killer Interactive, LLC
		 **/
		public function print_calendar() {
	
			$day = 1;
			$today = 0;
			
			$events = $this->get_events();
			
			$pMonth = date('F', $this->startDate);
				 
			$next_month = date('M', strtotime("first day of next month", $this->startDate));
			$prev_month = date('M', strtotime("first day of previous month", $this->startDate));
			
			$next_year = date('Y', strtotime('first day of next month', $this->startDate));
			$prev_year = date('Y', strtotime('first day of next month', $this->startDate));
		
			if ($year == date('Y') && $pMonth == strtolower(date('F'))) {
				$today = date('j');
			}
		
			$days_in_month = date("t", $this->startDate);
			$first_day_in_month = date('w', strtotime('first day of this month', $this->startDate));
			
			$pMonth = ucfirst($pMonth);
			
			// Generate Cell Headers
			$days_in_week = $this->week_starts_on == 0 ? array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat') : array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
			
			for ($i = 0; $i < 7;$i++) {
				$cellHeaders .= '<th class="cell-header">' . $days_in_week[$i] . '</th>';
			}
			
			// Generate Cells in Month
			while($day <= $days_in_month) {
				
				$monthDays .= '<tr>';
		
				for($i = 0; $i < 7; $i ++) {
		
					$cell = '&nbsp;';
		
					if(isset($events[$day])) {
						$cell = $events[$day];
					}
		
					$class = '';
		
					if(($this->week_starts_on == 0 && ($i == 6 || $i == 0)) ||
						($this->week_starts_on == 1 && $i > 4)) {
						$class = ' class="cell-weekend" ';
					}
		
					if($day == $today) {
						$class = ' class="cell-today" ';
					}
					
					if(($first_day_in_month == $i || $day > 1) && ($day <= $days_in_month)) {
						$monthDays .= <<<HTML
					<td {$class}><div class="cell-number">{$day}</div>
						<div class="cell-data">{$cell}</div>
					</td>
HTML;
						$day++;
					} else {
						$monthDays .= "<td {$class}>&nbsp;</td>";
					}
				}
		
				$monthDays .= "</tr>";
			}
		
		
			$calendar = <<<HTML
		<div id="spc-main-nav" class="ui-helper-clearfix">
		
			<table id="spc-cal-pager">
				<tbody>
					<tr>
						<td>
							<div id="cal-pager-prev" class="spc-cal-page ui-state-default ui-corner-left" data-direction="prev">
								<span class="ui-icon ui-icon ui-icon-carat-1-w"></span>
							</div>
						</td>
						<td>
							<div id="cal-pager-next" class="spc-cal-page ui-state-default" data-direction="next">
								<span class="ui-icon ui-icon ui-icon-carat-1-e"></span>
							</div>
						</td>
						<td>
							<div id="cal-pager-today" class="spc-cal-page ui-state-default ui-corner-right" data-direction="today">
								<span>Today</span>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
			
			<div id="spc-cal-nav-date" class="black-text-shadow">{$pMonth} {$year}</div>
			<div id="spc-cal-view-buttons" class="ui-buttonset">
				<input value="Day" id="spc-cal-day-view-btn" data-view-name="day" class="day ui-button ui-widget ui-state-default ui-corner-left" type="button">
				<input value="Week" id="spc-cal-week-view-btn" data-view-name="week" class="week ui-button ui-widget ui-state-default" type="button">
				<input value="Month" id="spc-cal-month-view-btn" data-view-name="month" class="month ui-button ui-widget ui-state-default ui-state-active" type="button">
				<input value="Agenda" id="spc-cal-agenda-view-btn" data-view-name="agenda" class="agenda ui-button ui-widget ui-state-default" type="button">
				<input value="5 Days" id="spc-cal-custom-view-btn" data-view-name="custom" class="custom ui-button ui-widget ui-state-default" type="button">
				<input value="Year" id="spc-cal-year-view-btn" data-view-name="year" class="year ui-button ui-widget ui-state-default ui-corner-right" type="button">
			</div>
		</div>
							
		<div style="height: 796.633px;" id="spc-main-app">
		
			<table id="spc-month-cal-header" style="left: -202px; position:relative">
				<tbody>
					<tr>
						<td class="spc-month-cal-header-date black-text-shadow">Sunday</td>
						<td class="spc-month-cal-header-date black-text-shadow">Monday</td>
						<td class="spc-month-cal-header-date black-text-shadow">Tuesday</td>
						<td class="spc-month-cal-header-date black-text-shadow">Wednesday</td>
						<td class="spc-month-cal-header-date black-text-shadow">Thursday</td>
						<td class="spc-month-cal-header-date black-text-shadow">Friday</td>
						<td class="spc-month-cal-header-date black-text-shadow">Saturday</td>
					</tr>
				</tbody>
			</table>
			
			<div class="month-cal-row" style="position: relative;height: 155.1266662597656px !important;width: 100%;">
				<table style="z-index: -1;" class="spc-month-all-day-box-table">
					<tbody>
						<tr>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 0;">
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-01-29</span>
									<span class="day-num hidden">1</span>
									<span class="m-c-day-element-index hidden">0</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 0;">
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-01-30</span>
									<span class="day-num hidden">1</span>
									<span class="m-c-day-element-index hidden">1</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 0;">
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-01-31</span>
									<span class="day-num hidden">1</span>
									<span class="m-c-day-element-index hidden">2</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 0;">
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-02-01</span>
									<span class="day-num hidden">2</span>
									<span class="m-c-day-element-index hidden">3</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 0;">
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-02-02</span>	
									<span class="day-num hidden">2</span>
									<span class="m-c-day-element-index hidden">4</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 0;">
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-02-03</span>
									<span class="day-num hidden">2</span>
									<span class="m-c-day-element-index hidden">5</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 0;">
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-02-04</span>
									<span class="day-num hidden">2</span>
									<span class="m-c-day-element-index hidden">6</span>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
				
				<table class="smart-month" cellpadding="0" cellspacing="0">
					<tbody>
						<tr>
							<td class="m-c-day-header month-day-passive  2012-01-30">
								<span class="m-c-day-num go-date" data-date="2012-01-30">
									29<span class="date hidden">2012-01-30</span>
								</span>
								<span class="m-c-day-element-index hidden">0</span>
								<span class="date hidden">2012-01-30</span>		
							</td>
							<td class="m-c-day-header month-day-passive  2012-01-31">
								<span class="m-c-day-num go-date" data-date="2012-01-31">
									30<span class="date hidden">2012-01-31</span>
								</span>
								<span class="m-c-day-element-index hidden">1</span>
								<span class="date hidden">2012-01-31</span>		
							</td>
							<td class="m-c-day-header month-day-active  2012-02-01">
								<span class="m-c-day-num go-date" data-date="2012-02-01">
									31<span class="date hidden">2012-02-01</span>
								</span>
								<span class="m-c-day-element-index hidden">2</span>
								<span class="date hidden">2012-02-01</span>		
							</td>
							<td class="m-c-day-header month-day-active  2012-02-02">
								<span class="m-c-day-num go-date" data-date="2012-02-02">
									Feb1<span class="date hidden">2012-02-02</span>
								</span>
								<span class="m-c-day-element-index hidden">3</span>
								<span class="date hidden">2012-02-02</span>		
							</td>
							<td class="m-c-day-header month-day-active  2012-02-03">
								<span class="m-c-day-num go-date" data-date="2012-02-03">
									2<span class="date hidden">2012-02-03</span>
								</span>
								<span class="m-c-day-element-index hidden">4</span>
								<span class="date hidden">2012-02-03</span>		
							</td>
							<td class="m-c-day-header month-day-active  2012-02-04">
								<span class="m-c-day-num go-date" data-date="2012-02-04">
									3<span class="date hidden">2012-02-04</span>
								</span>
								<span class="m-c-day-element-index hidden">5</span>
								<span class="date hidden">2012-02-04</span>		
							</td>
							<td class="m-c-day-header month-day-active  2012-02-05">
								<span class="m-c-day-num go-date" data-date="2012-02-05">
									4<span class="date hidden">2012-02-05</span>
								</span>
								<span class="m-c-day-element-index hidden">6</span>
								<span class="date hidden">2012-02-05</span>		
							</td>
						</tr>
						<tr>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">30</span>
								<span class="m-c-day-element-index hidden">0</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">31</span>
								<span class="m-c-day-element-index hidden">1</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">1</span>
								<span class="m-c-day-element-index hidden">2</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">2</span>
								<span class="m-c-day-element-index hidden">3</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">3</span>
								<span class="m-c-day-element-index hidden">4</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">4</span>
								<span class="m-c-day-element-index hidden">5</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">5</span>
								<span class="m-c-day-element-index hidden">6</span>&nbsp;
							</td>
						</tr>
						<tr>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">30</span>
								<span class="m-c-day-element-index hidden">0</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">31</span>
								<span class="m-c-day-element-index hidden">1</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">1</span>
								<span class="m-c-day-element-index hidden">2</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">2</span>
								<span class="m-c-day-element-index hidden">3</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">3</span>
								<span class="m-c-day-element-index hidden">4</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">4</span>
								<span class="m-c-day-element-index hidden">5</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">5</span>
								<span class="m-c-day-element-index hidden">6</span>&nbsp;
							</td>
						</tr>
						<tr>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">30</span>
								<span class="m-c-day-element-index hidden">0</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">31</span>
								<span class="m-c-day-element-index hidden">1</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">1</span>
								<span class="m-c-day-element-index hidden">2</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">2</span>
								<span class="m-c-day-element-index hidden">3</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">3</span>
								<span class="m-c-day-element-index hidden">4</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">4</span>
								<span class="m-c-day-element-index hidden">5</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">5</span>
								<span class="m-c-day-element-index hidden">6</span>&nbsp;
							</td>
						</tr>
						<tr>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">30</span>
								<span class="m-c-day-element-index hidden">0</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">31</span>
								<span class="m-c-day-element-index hidden">1</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">1</span>
								<span class="m-c-day-element-index hidden">2</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">2</span>
								<span class="m-c-day-element-index hidden">3</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">3</span>
								<span class="m-c-day-element-index hidden">4</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">4</span>
								<span class="m-c-day-element-index hidden">5</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">5</span>
								<span class="m-c-day-element-index hidden">6</span>&nbsp;
							</td>
						</tr>
						<tr>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">30</span>
								<span class="m-c-day-element-index hidden">0</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">31</span>
								<span class="m-c-day-element-index hidden">1</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">1</span>
								<span class="m-c-day-element-index hidden">2</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">2</span>
								<span class="m-c-day-element-index hidden">3</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">3</span>
								<span class="m-c-day-element-index hidden">4</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">4</span>
								<span class="m-c-day-element-index hidden">5</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">5</span>
								<span class="m-c-day-element-index hidden">6</span>&nbsp;
							</td>
						</tr>
						<tr>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">0</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">1</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">2</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">3</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">4</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">5</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">6</span>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			
			<div class="month-cal-row" style="position: relative;height: 155.1266662597656px !important;width: 100%;">
				<table style="z-index: -1;" class="spc-month-all-day-box-table">
					<tbody>
						<tr>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 0;">
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-02-05</span>
									<span class="day-num hidden">2</span>
									<span class="m-c-day-element-index hidden">7</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 0;">
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-02-06</span>
									<span class="day-num hidden">2</span>
									<span class="m-c-day-element-index hidden">8</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 0;">
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-02-07</span>
									<span class="day-num hidden">2</span>
									<span class="m-c-day-element-index hidden">9</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 0;background:yellow;">
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-02-08</span>
									<span class="day-num hidden">2</span>
									<span class="m-c-day-element-index hidden">10</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 0;">
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-02-09</span>
									<span class="day-num hidden">2</span>
									<span class="m-c-day-element-index hidden">11</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 0;">
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-02-10</span>
									<span class="day-num hidden">2</span>
									<span class="m-c-day-element-index hidden">12</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 0;">
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-02-11</span>
									<span class="day-num hidden">2</span>
									<span class="m-c-day-element-index hidden">13</span>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
				
				<table class="smart-month" cellpadding="0" cellspacing="0">
					<tbody>
						<tr>
							<td class="m-c-day-header month-day-active  2012-02-06">
								<span class="m-c-day-num go-date" data-date="2012-02-06">
									5<span class="date hidden">2012-02-06</span>
								</span>
								<span class="m-c-day-element-index hidden">7</span>
								<span class="date hidden">2012-02-06</span>		
							</td>
							<td class="m-c-day-header month-day-active  2012-02-07">
								<span class="m-c-day-num go-date" data-date="2012-02-07">
									6<span class="date hidden">2012-02-07</span>
								</span>
								<span class="m-c-day-element-index hidden">8</span>
								<span class="date hidden">2012-02-07</span>		
							</td>
							<td class="m-c-day-header month-day-active today 2012-02-08">
								<span class="m-c-day-num go-date" data-date="2012-02-08">
									7<span class="date hidden">2012-02-08</span>
								</span>
								<span class="m-c-day-element-index hidden">9</span>
								<span class="date hidden">2012-02-08</span>		
							</td>
							<td class="m-c-day-header month-day-active  2012-02-09">
								<span class="m-c-day-num go-date" data-date="2012-02-09">
									8<span class="date hidden">2012-02-09</span>
								</span>
								<span class="m-c-day-element-index hidden">10</span>
								<span class="date hidden">2012-02-09</span>		
							</td>
							<td class="m-c-day-header month-day-active  2012-02-10">
								<span class="m-c-day-num go-date" data-date="2012-02-10">
									9<span class="date hidden">2012-02-10</span>
								</span>
								<span class="m-c-day-element-index hidden">11</span>
								<span class="date hidden">2012-02-10</span>		
							</td>
							<td class="m-c-day-header month-day-active  2012-02-11">
								<span class="m-c-day-num go-date" data-date="2012-02-11">
									10<span class="date hidden">2012-02-11</span>
								</span>
								<span class="m-c-day-element-index hidden">12</span>
								<span class="date hidden">2012-02-11</span>		
							</td>
							<td class="m-c-day-header month-day-active  2012-02-12">
								<span class="m-c-day-num go-date" data-date="2012-02-12">
									11<span class="date hidden">2012-02-12</span>
								</span>
								<span class="m-c-day-element-index hidden">13</span>
								<span class="date hidden">2012-02-12</span>		
							</td>
						</tr>
						<tr>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">6</span>
								<span class="m-c-day-element-index hidden">7</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">7</span>
								<span class="m-c-day-element-index hidden">8</span>
		
								<div class="spc-event standard change  ui-corner-all ui-draggable" style="color: blue !important;" data-event-id="214" data-calendar-id="24" data-start-date="2012-02-07" data-start-time="11:00" data-end-date="2012-02-07" data-end-time="13:40" data-title="evento1 dfsdfsdfdsfsd" data-event-color="#D8F3C9" data-event-type="standard" data-invitation="0">
									<span style="width: 143px; display: inline-block;" class="spc-event-title f-left">11:00 evento1 dfsdfsdfdsfsd</span>
									<sub style="position: absolute; right: 0; margin: 0 6px; font-weight: bold; font-size: 12px;"></sub>
								</div>
							</td>
							<td colspan="5">
		
								<div class="spc-event multi-day change  ui-corner-all ui-draggable" style="background-color: #D8F3C9;box-shadow: 1px 1px 1px #D8F3C9;-moz-box-shadow: 1px 1px 1px #D8F3C9;-webkit-box-shadow: 1px 1px 1px #D8F3C9;" data-event-id="216" data-calendar-id="24" data-start-date="2012-02-08" data-start-time="11:00" data-end-date="2012-02-12" data-end-time="11:30" data-title="" data-event-color="#D8F3C9" data-event-type="multi_day" data-invitation="0">
		
								<div style="width: 844px; display: inline-block;" class="spc-event-title">(No title)</div>
									<span class="event-owner-username" style="right: 2px;"></span>
								</div>
							</td>
						</tr>
						<tr>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">6</span>
								<span class="m-c-day-element-index hidden">7</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">7</span>
								<span class="m-c-day-element-index hidden">8</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">8</span>
								<span class="m-c-day-element-index hidden">9</span>
		
								<div class="spc-event standard change  ui-corner-all ui-draggable" style="color: green !important;" data-event-id="215" data-calendar-id="24" data-start-date="2012-02-08" data-start-time="09:10" data-end-date="2012-02-08" data-end-time="09:40" data-title="" data-event-color="#D8F3C9" data-event-type="standard" data-invitation="0">
									<span style="width: 143px; display: inline-block;" class="spc-event-title f-left">09:10 (No title)</span>
									<sub style="position: absolute; right: 0; margin: 0 6px; font-weight: bold; font-size: 12px;"></sub>
								</div>
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">9</span>
								<span class="m-c-day-element-index hidden">10</span>
		
								<div class="spc-event standard change  ui-corner-all ui-draggable" style="color: green !important;" data-event-id="218" data-calendar-id="24" data-start-date="2012-02-09" data-start-time="09:00" data-end-date="2012-02-09" data-end-time="09:30" data-title="123456" data-event-color="#D8F3C9" data-event-type="standard" data-invitation="0">
									<span style="width: 143px; display: inline-block;" class="spc-event-title f-left">09:00 123456</span>
									<sub style="position: absolute; right: 0; margin: 0 6px; font-weight: bold; font-size: 12px;"></sub>
								</div>
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">10</span>
								<span class="m-c-day-element-index hidden">11</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">11</span>
								<span class="m-c-day-element-index hidden">12</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">12</span>
								<span class="m-c-day-element-index hidden">13</span>&nbsp;
							</td>
						</tr>
						<tr>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">6</span>
								<span class="m-c-day-element-index hidden">7</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">7</span>
								<span class="m-c-day-element-index hidden">8</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">8</span>
								<span class="m-c-day-element-index hidden">9</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">9</span>
								<span class="m-c-day-element-index hidden">10</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">10</span>
								<span class="m-c-day-element-index hidden">11</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">11</span>
								<span class="m-c-day-element-index hidden">12</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">12</span>
								<span class="m-c-day-element-index hidden">13</span>&nbsp;
							</td>
						</tr>
						<tr>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">6</span>
								<span class="m-c-day-element-index hidden">7</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">7</span>
								<span class="m-c-day-element-index hidden">8</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">8</span>
								<span class="m-c-day-element-index hidden">9</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">9</span>
								<span class="m-c-day-element-index hidden">10</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">10</span>
								<span class="m-c-day-element-index hidden">11</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">11</span>
								<span class="m-c-day-element-index hidden">12</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">12</span>
								<span class="m-c-day-element-index hidden">13</span>&nbsp;
							</td>
						</tr>
						<tr>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">6</span>
								<span class="m-c-day-element-index hidden">7</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">7</span>
								<span class="m-c-day-element-index hidden">8</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">8</span>
								<span class="m-c-day-element-index hidden">9</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">9</span>
								<span class="m-c-day-element-index hidden">10</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">10</span>
								<span class="m-c-day-element-index hidden">11</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">11</span>
								<span class="m-c-day-element-index hidden">12</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">12</span>
								<span class="m-c-day-element-index hidden">13</span>&nbsp;
							</td>
						</tr>
						<tr>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">7</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">8</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">9</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">10</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">11</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">12</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">13</span>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			
			<div class="month-cal-row" style="position: relative;height: 155.1266662597656px !important;width: 100%;">
				<table style="z-index: -1;" class="spc-month-all-day-box-table">
					<tbody>
						<tr>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 0;">
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-02-12</span>
									<span class="day-num hidden">2</span>
									<span class="m-c-day-element-index hidden">14</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 0;">
		
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-02-13</span>
									<span class="day-num hidden">2</span>
									<span class="m-c-day-element-index hidden">15</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 0;">
		
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-02-14</span>
									<span class="day-num hidden">2</span>
									<span class="m-c-day-element-index hidden">16</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 0;">
		
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-02-15</span>
									<span class="day-num hidden">2</span>
									<span class="m-c-day-element-index hidden">17</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 0;">
		
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-02-16</span>
									<span class="day-num hidden">2</span>
									<span class="m-c-day-element-index hidden">18</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 0;">
		
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-02-17</span>
									<span class="day-num hidden">2</span>
									<span class="m-c-day-element-index hidden">19</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 0;">
		
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-02-18</span>
									<span class="day-num hidden">2</span>
									<span class="m-c-day-element-index hidden">20</span>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
				
				<table class="smart-month" cellpadding="0" cellspacing="0">
					<tbody>
						<tr>
							<td class="m-c-day-header month-day-active  2012-02-13">
								<span class="m-c-day-num go-date" data-date="2012-02-13">
									12<span class="date hidden">2012-02-13</span>
								</span>
								<span class="m-c-day-element-index hidden">14</span>
								<span class="date hidden">2012-02-13</span>		
							</td>
							<td class="m-c-day-header month-day-active  2012-02-14">
								<span class="m-c-day-num go-date" data-date="2012-02-14">
									13<span class="date hidden">2012-02-14</span>
								</span>
								<span class="m-c-day-element-index hidden">15</span>
								<span class="date hidden">2012-02-14</span>		
							</td>
							<td class="m-c-day-header month-day-active  2012-02-15">
								<span class="m-c-day-num go-date" data-date="2012-02-15">
									14<span class="date hidden">2012-02-15</span>
								</span>
								<span class="m-c-day-element-index hidden">16</span>
								<span class="date hidden">2012-02-15</span>		
							</td>
							<td class="m-c-day-header month-day-active  2012-02-16">
								<span class="m-c-day-num go-date" data-date="2012-02-16">
									15<span class="date hidden">2012-02-16</span>
								</span>
								<span class="m-c-day-element-index hidden">17</span>
								<span class="date hidden">2012-02-16</span>		
							</td>
							<td class="m-c-day-header month-day-active  2012-02-17">
								<span class="m-c-day-num go-date" data-date="2012-02-17">
									16<span class="date hidden">2012-02-17</span>
								</span>
								<span class="m-c-day-element-index hidden">18</span>
								<span class="date hidden">2012-02-17</span>		
							</td>
							<td class="m-c-day-header month-day-active  2012-02-18">
								<span class="m-c-day-num go-date" data-date="2012-02-18">
									17<span class="date hidden">2012-02-18</span>
								</span>
								<span class="m-c-day-element-index hidden">19</span>
								<span class="date hidden">2012-02-18</span>		
							</td>
							<td class="m-c-day-header month-day-active  2012-02-19">
								<span class="m-c-day-num go-date" data-date="2012-02-19">
									18<span class="date hidden">2012-02-19</span>
								</span>
								<span class="m-c-day-element-index hidden">20</span>
								<span class="date hidden">2012-02-19</span>		
							</td>
						</tr>
						<tr>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">13</span>
								<span class="m-c-day-element-index hidden">14</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">14</span>
								<span class="m-c-day-element-index hidden">15</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">15</span>
								<span class="m-c-day-element-index hidden">16</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">16</span>
								<span class="m-c-day-element-index hidden">17</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">17</span>
								<span class="m-c-day-element-index hidden">18</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">18</span>
								<span class="m-c-day-element-index hidden">19</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">19</span>
								<span class="m-c-day-element-index hidden">20</span>&nbsp;
							</td>
						</tr>
						<tr>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">13</span>
								<span class="m-c-day-element-index hidden">14</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">14</span>
								<span class="m-c-day-element-index hidden">15</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">15</span>
								<span class="m-c-day-element-index hidden">16</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">16</span>
								<span class="m-c-day-element-index hidden">17</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">17</span>
								<span class="m-c-day-element-index hidden">18</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">18</span>
								<span class="m-c-day-element-index hidden">19</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">19</span>
								<span class="m-c-day-element-index hidden">20</span>&nbsp;
							</td>
						</tr>
						<tr>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">13</span>
								<span class="m-c-day-element-index hidden">14</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">14</span>
								<span class="m-c-day-element-index hidden">15</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">15</span>
								<span class="m-c-day-element-index hidden">16</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">16</span>
								<span class="m-c-day-element-index hidden">17</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">17</span>
								<span class="m-c-day-element-index hidden">18</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">18</span>
								<span class="m-c-day-element-index hidden">19</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">19</span>
								<span class="m-c-day-element-index hidden">20</span>&nbsp;
							</td>
						</tr>
						<tr>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">13</span>
								<span class="m-c-day-element-index hidden">14</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">14</span>
								<span class="m-c-day-element-index hidden">15</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">15</span>
								<span class="m-c-day-element-index hidden">16</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">16</span>
								<span class="m-c-day-element-index hidden">17</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">17</span>
								<span class="m-c-day-element-index hidden">18</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">18</span>
								<span class="m-c-day-element-index hidden">19</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">19</span>
								<span class="m-c-day-element-index hidden">20</span>&nbsp;
							</td>
						</tr>
						<tr>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">13</span>
								<span class="m-c-day-element-index hidden">14</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">14</span>
								<span class="m-c-day-element-index hidden">15</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">15</span>
								<span class="m-c-day-element-index hidden">16</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">16</span>
								<span class="m-c-day-element-index hidden">17</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">17</span>
								<span class="m-c-day-element-index hidden">18</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">18</span>
								<span class="m-c-day-element-index hidden">19</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">19</span>
								<span class="m-c-day-element-index hidden">20</span>&nbsp;
							</td>
						</tr>
						<tr>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">14</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">15</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">16</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">17</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">18</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">19</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">20</span> 
							</td>
						</tr>
					</tbody>
				</table> 
			</div>
			
			<div class="month-cal-row" style="position: relative;height: 155.1266662597656px !important;width: 100%;">
				<table style="z-index: -1;" class="spc-month-all-day-box-table">
					<tbody>
						<tr>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 0;">
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-02-19</span>
									<span class="day-num hidden">2</span>
									<span class="m-c-day-element-index hidden">21</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 0;">
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-02-20</span>
									<span class="day-num hidden">2</span>
									<span class="m-c-day-element-index hidden">22</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 0;">
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-02-21</span>	
									<span class="day-num hidden">2</span>
									<span class="m-c-day-element-index hidden">23</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 0;">
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-02-22</span>
									<span class="day-num hidden">2</span>
									<span class="m-c-day-element-index hidden">24</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 0;">
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-02-23</span>
									<span class="day-num hidden">2</span>
									<span class="m-c-day-element-index hidden">25</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 0;">
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-02-24</span>
									<span class="day-num hidden">2</span>
									<span class="m-c-day-element-index hidden">26</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 0;">
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-02-25</span>
									<span class="day-num hidden">2</span>
									<span class="m-c-day-element-index hidden">27</span>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
				
				<table class="smart-month" cellpadding="0" cellspacing="0">
					<tbody>
						<tr>
							<td class="m-c-day-header month-day-active  2012-02-20">
								<span class="m-c-day-num go-date" data-date="2012-02-20">
									19<span class="date hidden">2012-02-20</span>
								</span>
								<span class="m-c-day-element-index hidden">21</span>
								<span class="date hidden">2012-02-20</span>		
							</td>
							<td class="m-c-day-header month-day-active  2012-02-21">
								<span class="m-c-day-num go-date" data-date="2012-02-21">
									20<span class="date hidden">2012-02-21</span>
								</span>
								<span class="m-c-day-element-index hidden">22</span>
								<span class="date hidden">2012-02-21</span>		
							</td>
							<td class="m-c-day-header month-day-active  2012-02-22">
								<span class="m-c-day-num go-date" data-date="2012-02-22">
									21<span class="date hidden">2012-02-22</span>
								</span>
								<span class="m-c-day-element-index hidden">23</span>
								<span class="date hidden">2012-02-22</span>		
							</td>
							<td class="m-c-day-header month-day-active  2012-02-23">
								<span class="m-c-day-num go-date" data-date="2012-02-23">
									22<span class="date hidden">2012-02-23</span>
								</span>
								<span class="m-c-day-element-index hidden">24</span>
								<span class="date hidden">2012-02-23</span>		
							</td>
							<td class="m-c-day-header month-day-active  2012-02-24">
								<span class="m-c-day-num go-date" data-date="2012-02-24">
									23<span class="date hidden">2012-02-24</span>
								</span>
								<span class="m-c-day-element-index hidden">25</span>
								<span class="date hidden">2012-02-24</span>		
							</td>
							<td class="m-c-day-header month-day-active  2012-02-25">
								<span class="m-c-day-num go-date" data-date="2012-02-25">
									24<span class="date hidden">2012-02-25</span>
								</span>
								<span class="m-c-day-element-index hidden">26</span>
								<span class="date hidden">2012-02-25</span>		
							</td>
							<td class="m-c-day-header month-day-active  2012-02-26">
								<span class="m-c-day-num go-date" data-date="2012-02-26">
									25<span class="date hidden">2012-02-26</span>
								</span>
								<span class="m-c-day-element-index hidden">27</span>
								<span class="date hidden">2012-02-26</span>		
							
							</td>
						</tr>
						<tr>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">20</span>
								<span class="m-c-day-element-index hidden">21</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">21</span>
								<span class="m-c-day-element-index hidden">22</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">22</span>
								<span class="m-c-day-element-index hidden">23</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">23</span>
								<span class="m-c-day-element-index hidden">24</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">24</span>
								<span class="m-c-day-element-index hidden">25</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">25</span>
								<span class="m-c-day-element-index hidden">26</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">26</span>
								<span class="m-c-day-element-index hidden">27</span>&nbsp;
							</td>
						</tr>
						<tr>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">20</span>
								<span class="m-c-day-element-index hidden">21</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">21</span>
								<span class="m-c-day-element-index hidden">22</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">22</span>
								<span class="m-c-day-element-index hidden">23</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">23</span>
								<span class="m-c-day-element-index hidden">24</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">24</span>
								<span class="m-c-day-element-index hidden">25</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">25</span>
								<span class="m-c-day-element-index hidden">26</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">26</span>
								<span class="m-c-day-element-index hidden">27</span>&nbsp;
							</td>
						</tr>
						<tr>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">20</span>
								<span class="m-c-day-element-index hidden">21</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">21</span>
								<span class="m-c-day-element-index hidden">22</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">22</span>
								<span class="m-c-day-element-index hidden">23</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">23</span>
								<span class="m-c-day-element-index hidden">24</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">24</span>
								<span class="m-c-day-element-index hidden">25</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">25</span>
								<span class="m-c-day-element-index hidden">26</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">26</span>
								<span class="m-c-day-element-index hidden">27</span>&nbsp;
							</td>
						</tr>
						<tr>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">20</span>
								<span class="m-c-day-element-index hidden">21</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">21</span>
								<span class="m-c-day-element-index hidden">22</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">22</span>
								<span class="m-c-day-element-index hidden">23</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">23</span>
								<span class="m-c-day-element-index hidden">24</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">24</span>
								<span class="m-c-day-element-index hidden">25</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">25</span>
								<span class="m-c-day-element-index hidden">26</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">26</span>
								<span class="m-c-day-element-index hidden">27</span>&nbsp;
							</td>
						</tr>
						<tr>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">20</span>
								<span class="m-c-day-element-index hidden">21</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">21</span>
								<span class="m-c-day-element-index hidden">22</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">22</span>
								<span class="m-c-day-element-index hidden">23</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">23</span>
								<span class="m-c-day-element-index hidden">24</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">24</span>
								<span class="m-c-day-element-index hidden">25</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">25</span>
								<span class="m-c-day-element-index hidden">26</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">26</span>
								<span class="m-c-day-element-index hidden">27</span>&nbsp;
							</td>
						</tr>
						<tr>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">21</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">22</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">23</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">24</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">25</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">26</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">27</span>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			
			<div class="month-cal-row" style="position: relative;height: 155.1266662597656px !important;width: 100%;">
				<table style="z-index: -1;" class="spc-month-all-day-box-table">
					<tbody>
						<tr>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 0;">
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-02-26</span>
									<span class="day-num hidden">2</span>
									<span class="m-c-day-element-index hidden">28</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 1px solid #ddd;">
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-02-27</span>
									<span class="day-num hidden">2</span>
									<span class="m-c-day-element-index hidden">29</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 1px solid #ddd;">
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-02-28</span>
									<span class="day-num hidden">2</span>
									<span class="m-c-day-element-index hidden">30</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 1px solid #ddd;">
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-02-29</span>
									<span class="day-num hidden">2</span>	
									<span class="m-c-day-element-index hidden">31</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 1px solid #ddd;">
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-03-01</span>
									<span class="day-num hidden">3</span>
									<span class="m-c-day-element-index hidden">32</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 1px solid #ddd;">
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-03-02</span>
									<span class="day-num hidden">3</span>
									<span class="m-c-day-element-index hidden">33</span>
								</div>
							</td>
							<td class="spc-month-all-day-box-cell" style="border-bottom: 1px solid #ddd;">
								<div style="background-color: transparent; opacity: 1;" class="spc-month-all-day-box">
									<span class="date hidden">2012-03-03</span>
									<span class="day-num hidden">3</span>
									<span class="m-c-day-element-index hidden">34</span>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
				
				<table class="smart-month" cellpadding="0" cellspacing="0">
					<tbody>
						<tr>
							<td class="m-c-day-header month-day-active  2012-02-27">
								<span class="m-c-day-num go-date" data-date="2012-02-27">
									26<span class="date hidden">2012-02-27</span>
								</span>
								<span class="m-c-day-element-index hidden">28</span>
								<span class="date hidden">2012-02-27</span>		
							</td>
							<td class="m-c-day-header month-day-active  2012-02-28">
								<span class="m-c-day-num go-date" data-date="2012-02-28">
									27<span class="date hidden">2012-02-28</span>
								</span>
								<span class="m-c-day-element-index hidden">29</span>
								<span class="date hidden">2012-02-28</span>		
							</td>
							<td class="m-c-day-header month-day-active  2012-02-29">
								<span class="m-c-day-num go-date" data-date="2012-02-29">
									28<span class="date hidden">2012-02-29</span>
								</span>
								<span class="m-c-day-element-index hidden">30</span>
								<span class="date hidden">2012-02-29</span>		
							</td>
							<td class="m-c-day-header month-day-passive  2012-03-01">
								<span class="m-c-day-num go-date" data-date="2012-03-01">
									29<span class="date hidden">2012-03-01</span>
								</span>
								<span class="m-c-day-element-index hidden">31</span>
								<span class="date hidden">2012-03-01</span>		
							</td>
							<td class="m-c-day-header month-day-passive  2012-03-02">
								<span class="m-c-day-num go-date" data-date="2012-03-02">
									Mar1<span class="date hidden">2012-03-02</span>
								</span>
								<span class="m-c-day-element-index hidden">32</span>
								<span class="date hidden">2012-03-02</span>		
							</td>
							<td class="m-c-day-header month-day-passive  2012-03-03">
								<span class="m-c-day-num go-date" data-date="2012-03-03">
									2<span class="date hidden">2012-03-03</span>
								</span>
								<span class="m-c-day-element-index hidden">33</span>
								<span class="date hidden">2012-03-03</span>		
							</td>
							<td class="m-c-day-header month-day-passive  2012-03-04">
								<span class="m-c-day-num go-date" data-date="2012-03-04">
									3 <span class="date hidden">2012-03-04</span>
								</span>
								<span class="m-c-day-element-index hidden">34</span>
								<span class="date hidden">2012-03-04</span>		
							
							</td>
						</tr>
						<tr>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">27</span>
								<span class="m-c-day-element-index hidden">28</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">28</span>
								<span class="m-c-day-element-index hidden">29</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">29</span>
								<span class="m-c-day-element-index hidden">30</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">1</span>
								<span class="m-c-day-element-index hidden">31</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">2</span>
								<span class="m-c-day-element-index hidden">32</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">3</span>
								<span class="m-c-day-element-index hidden">33</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">4</span>
								<span class="m-c-day-element-index hidden">34</span>&nbsp;
							</td>
						</tr>
						<tr>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">27</span>
								<span class="m-c-day-element-index hidden">28</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">28</span>
								<span class="m-c-day-element-index hidden">29</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">29</span>
								<span class="m-c-day-element-index hidden">30</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">1</span>
								<span class="m-c-day-element-index hidden">31</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">2</span>
								<span class="m-c-day-element-index hidden">32</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">3</span>
								<span class="m-c-day-element-index hidden">33</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">4</span>
								<span class="m-c-day-element-index hidden">34</span>&nbsp;
							</td>
						</tr>
						<tr>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">27</span>
								<span class="m-c-day-element-index hidden">28</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">28</span>
								<span class="m-c-day-element-index hidden">29</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">29</span>
								<span class="m-c-day-element-index hidden">30</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">1</span>
								<span class="m-c-day-element-index hidden">31</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">2</span>
								<span class="m-c-day-element-index hidden">32</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">3</span>
								<span class="m-c-day-element-index hidden">33</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">4</span>
								<span class="m-c-day-element-index hidden">34</span>&nbsp;
							</td>
						</tr>
						<tr>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">27</span>
								<span class="m-c-day-element-index hidden">28</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">28</span>
								<span class="m-c-day-element-index hidden">29</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">29</span>
								<span class="m-c-day-element-index hidden">30</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">1</span>
								<span class="m-c-day-element-index hidden">31</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">2</span>
								<span class="m-c-day-element-index hidden">32</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">3</span>
								<span class="m-c-day-element-index hidden">33</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">4</span>
								<span class="m-c-day-element-index hidden">34</span>&nbsp;
							</td>
						</tr>
						<tr>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">27</span>
								<span class="m-c-day-element-index hidden">28</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">28</span>
								<span class="m-c-day-element-index hidden">29</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">29</span>
								<span class="m-c-day-element-index hidden">30</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">1</span>
								<span class="m-c-day-element-index hidden">31</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">2</span>
								<span class="m-c-day-element-index hidden">32</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">3</span>
								<span class="m-c-day-element-index hidden">33</span>&nbsp;
							</td>
							<td class="m-c-empty-cell">
								<span class="m-c-day-num hidden">4</span>
								<span class="m-c-day-element-index hidden">34</span>&nbsp;
							</td>
						</tr>
						<tr>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">28</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">29</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">30</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">31</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">32</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">33</span>
							</td>
							<td style="height: 100px;">
								<span class="m-c-day-element-index hidden">34</span>
							</td>
						</tr>
					</tbody>
				</table> 
			</div>
		</div>
HTML;
		
			return $calendar;
		
		}
		
		/**
		 *
		 *
		 * @return void
		 * @author Killer Interactive, LLC
		 **/
		public function apply_filters() {
		
		}
		
		/**
		 *
		 *
		 * @return void
		 * @author Killer Interactive, LLC
		 **/
		public function get_categories() {
		
		}
		
		/**
		 *
		 *
		 * @return void
		 * @author Killer Interactive, LLC
		 **/
		public function get_category_event() {
		
		}
		
		/**
		 * Move calendar ahead X month(s) (1 by default)
		 *
		 * @param integer Next
		 *  
		 * @return void
		 * @author Killer Interactive, LLC
		 **/
		public function next_month($next = 1) {
		
		}
		
		/**
		 * Move calendar back X month(s) (1 by default)
		 *
		 * @param integer Prev
		 *  
		 * @return void
		 * @author Killer Interactive, LLC
		 **/
		public function prev_month($prev = 1) {
		
		}
		
		/**
		 * Move calendar ahead X year(s) (1 by default)
		 *
		 * @param integer Next
		 *  
		 * @return void
		 * @author Killer Interactive, LLC
		 **/
		public function next_year($next = 1) {
		
		}
		
		/**
		 * Move calendar back X year(s) (1 by default)
		 *
		 * @param integer Prev
		 *  
		 * @return void
		 * @author Killer Interactive, LLC
		 **/
		public function prev_year($prev = 1) {
		
		}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN PRIVATE #
	/////////////////////////////////////////////////////////////////////////

		/**
		 * Sets event data in calendar
		 *
		 * @return void
		 * @author Killer Interactive, LLC
		 **/
		private function set_events() {

		}

		/**
		 * Applies provided template
		 *
		 * @return void
		 * @author Killer Interactive, LLC
		 **/
		private function populate_template() {

		}


	}

?>