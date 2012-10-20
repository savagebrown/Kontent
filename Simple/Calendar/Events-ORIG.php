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
		<table class="calendar">
			<thead>
				<tr>
					<th class="cell-prev">
						<a href="{$base_url}/{$prev_year}/{$prev_month}">prev</a>
					</th>
					<th colspan="5">{$pMonth} {$year}</th>
					<th class="cell-next">
						<a href="{$base_url}/{$next_year}/{$next_month}">next</a>
					</th>
				</tr>
				<tr>
					{$cellHeaders}
				</tr>
			</thead>
			<tbody>
				{$monthDays}
			</tbody>
		</table>
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