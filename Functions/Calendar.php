<?php

	function getAllDatesForEvent($event, $showURL=false) {
		
		$title = "title: '".$event['title']."',";
		$sDate = strtotime($event['start_date']);
		$eDate = strtotime($event['end_date']);
		$sTime = strtotime($event['start_time']);
		$eTime = strtotime($event['end_time']);
		$isAllDay = ($event['all_day'] == "1" ? true : false);
		$className = ($event['className'] != null ? "className: '".$event['className']."'," : "className: 'cal-event-default',");
		
		if ($event['repeat_event'] == 0)
			$eventHtml .= calGetDate($title, $sDate, $eDate, $sTime, $eTime, $isAllDay, $className, $event['id'], $showURL);
		
		$days = array(
			'MO' => 'Monday',
			'TU' => 'Tuesday',
			'WE' => 'Wednesday',
			'TH' => 'Thursday',
			'FR' => 'Friday',
			'SA' => 'Saturday',
			'SU' => 'Sunday');
			
		// Create dates to diff against
		$theDate = mktime(date("g", $sTime), date('i', $sTime), 00, date('m', $sDate), date('d', $sDate), date('Y', $sDate));
		$s_date = date('Y-m-d H:i:s', $theDate);
		
		switch ($event['repeat_event']) {
			case 3: // BI-WEEKLY
				$mul = 2;

			case 1: // WEEKLY
			
				if (!isset($mul)) $mul = 1;
				
				$byDay = $event['repeat_byday'];
				
				$a_days = explode(',', $byDay);
				
				// 3 years into the future
				$until = (52/$mul)*3;
				$until -= floor((int)dateDiff('ww', date('Y-m-d H:i:s'), $s_date));
				
				if ($event['repeat_until'] != '0000-00-00 00:00:00') {
					// Create loop length if until date is set.
					$until = floor((int)datediff('ww', $s_date, $event['repeat_until']));
				}

				foreach ($a_days as $day) {
				
					$loop = $until;
					if ($day == "SU") {
						$loop += 1;
					}
					
					// NOTE: must be <= to include the final date before it ends.			
					for ($i = 0; $i <= $loop; $i++) {
						$wk = $i*$mul;
						$nDate = strtotime("{$days[$day]} +{$wk} week", $theDate);
						$eventHtml .= calGetDate($title, $nDate, $nDate, $sTime, $eTime, $isAllDay, $className, $event['id'], $showURL, true);
					}
				}
				
				break;
				
			case 2: // DAILY (TODO: NEED TO ADD ADJUSTEMENT FOR INDIVIDUAL DAYS)
					
				$until = (int)datediff('d', $s_date, $event['repeat_until']);	
				
				// NOTE: must be <= to include the final date before it ends.			
				for ($i = 0; $i <= $until; $i++) {
					$nDate = strtotime("+{$i} days", $theDate);
					$eventHtml .= calGetDate($title, $nDate, $nDate, $sTime, $eTime, $isAllDay, $className, $event['id'], $showURL, true);
				}
					
				break;
				
			case 4: // MONTHLY
					
				$dayNums = array(
					'1' => 'first',
					'2' => 'second',
					'3' => 'third',
					'4' => 'fourth');
				
				$byDay = $event['repeat_byday'];
				
				// 3 years into the future
				$until = 36;
				
				// Shift so we always get 3 years into the future from the current date.
				$until -= (int)dateDiff('m', date('Y-m-d H:i:s'), $s_date);
				
				if ($event['repeat_until'] != '0000-00-00 00:00:00') {
					// Create loop length if until date is set.
					$until = datediff('m', $s_date , $event['repeat_until']);
				}
				
				preg_match('/\d+[A-Z]{2}/', $byDay, $matches);
				
				if (!empty($matches) && $matches[0] == $byDay) { // ex: 1MO (first monday of the month)
					
					$dayNum = $dayNums[substr($byDay, 0, 1)];
					$day = $days[substr($byDay, 1, 2)];
					
					// NOTE: must be <= to include the final date before it ends.			
					for ($i = 0; $i <= $until; $i++) {
						$nDate = strtotime("{$dayNum} {$day} of +{$i} month", $theDate);
						if ($nDate > $event['start_date'])
							$eventHtml .= calGetDate($title, $nDate, $nDate, $sTime, $eTime, $isAllDay, $className, $event['id'], $showURL, true);
					}
				
				} else { // ex: every month on the 15th
					
					for ($i = 0; $i <= $until; $i++) {
						$nDate = strtotime("+{$i} month", $theDate);
						$nDate = mktime(0, 0, 0, date('m', $nDate), (int)$byDay, date('Y', $nDate));
						
						if ($nDate > $event['start_date'])
							$eventHtml .= calGetDate($title, $nDate, $nDate, $sTime, $eTime, $isAllDay, $className, $event['id'], $showURL, true);
					}
				}
				
				break;
				
			case 5: // QUARTERLY
				// NOT USED
				break;
				
			case 6: // ANNUALLY
			
				$until =3;
				
				if ($event['repeat_until'] != '0000-00-00 00:00:00') {
					$until = (int)datediff('yyyy', $s_date, $event['repeat_until']);
				}
				
				// NOTE: must be <= to include the final date before it ends.			
				for ($i = 0; $i <= $until; $i++) {
					$month = date('F', $theDate);
					$day = date('d', $theDate);

					$nDate = strtotime("{$month} {$day} +{$i} years", $theDate);
					$eventHtml .= calGetDate($title, $nDate, $nDate, $sTime, $eTime, $isAllDay, $className, $event['id'], $showURL, true);
				}
				break;
				
			default:
				// NO REPEAT, DO NOTHING
				break;
		}
		
		return $eventHtml;
	
	}
	
	function calGetDate($title, $sDate, $eDate, $sTime, $eTime, $isAllDay, $className, $eventId, $showURL=false, $isRepeat=false)
	{
	
		$startTime = "";
		if (!$isAllDay)
			$startTime = ", ".date('G', $sTime).", ".(int)date('i', $sTime);
		$start = "start: new Date(".date('Y', $sDate).", ".((int)date('n', $sDate)-1).", ".date('j', $sDate).$startTime."),";

		$endTime = "";
		if (!$isAllDay)
			$endTime = ", ".date('G', $eTime).", ".(int)date('i', $eTime);
			
		$end = "end: new Date(".date('Y', $eDate).", ".((int)date('n', $eDate)-1).", ".date('j', $eDate).$endTime."),";

		$allDay = "allDay: ".($isAllDay ? "true" : "false").",";
		
		if ($isRepeat)
			$dateURL = "&date=".date('Y-m-d H:i:s', mktime(date("g", $sTime), date('i', $sTime), 00, date('m', $sDate), date('d', $sDate), date('Y', $sDate)));
		
		if ($showURL) {
		
			$url = "url: 'event.php?event=".$eventId."{$dateURL}'";
			
			$eventHtml .= <<<HTML
	{
		$title
		$start
		$end
		$allDay
		$className
		$url
	},
HTML;
		} else {
			$eventHtml .= <<<HTML
	{
		$title
		$start
		$end
		$allDay
		$className
	},
HTML;
		}

		return $eventHtml;

	}

	
	function datediff($interval, $datefrom, $dateto, $using_timestamps = false) {
		/*
		$interval can be:
		yyyy - Number of full years
		q - Number of full quarters
		m - Number of full months
		y - Difference between day numbers
		(eg 1st Jan 2004 is "1", the first day. 2nd Feb 2003 is "33". The datediff is "-32".)
		d - Number of full days
		w - Number of full weekdays
		ww - Number of full weeks
		h - Number of full hours
		n - Number of full minutes
		s - Number of full seconds (default)
		*/
		if (!$using_timestamps) {
			$datefrom = strtotime($datefrom, 0);
			$dateto = strtotime($dateto, 0);
		}
		$difference = $dateto - $datefrom; // Difference in seconds
		switch($interval) {
			case 'yyyy': // Number of full years
				$years_difference = floor($difference / 31536000);
				if (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom), date("j", $datefrom), date("Y", $datefrom)+$years_difference) > $dateto) {
				$years_difference--;
				}
				if (mktime(date("H", $dateto), date("i", $dateto), date("s", $dateto), date("n", $dateto), date("j", $dateto), date("Y", $dateto)-($years_difference+1)) > $datefrom) {
				$years_difference++;
				}
				$datediff = $years_difference;
				break;
			case "q": // Number of full quarters
				$quarters_difference = floor($difference / 8035200);
				while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom)+($quarters_difference*3), date("j", $dateto), date("Y", $datefrom)) < $dateto) {
				$months_difference++;
				}
				$quarters_difference--;
				$datediff = $quarters_difference;
				break;
			case "m": // Number of full months
				$months_difference = floor($difference / 2678400);
				while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom)+($months_difference), date("j", $dateto), date("Y", $datefrom)) < $dateto) {
					$months_difference++;
				}
				$months_difference--;
				$datediff = $months_difference;
				break;
			case 'y': // Difference between day numbers
				$datediff = date("z", $dateto) - date("z", $datefrom);
				break;
			case "d": // Number of full days
				$datediff = floor($difference / 86400);
				break;
			case "w": // Number of full weekdays
				$days_difference = floor($difference / 86400);
				$weeks_difference = floor($days_difference / 7); // Complete weeks
				$first_day = date("w", $datefrom);
				$days_remainder = floor($days_difference % 7);
				$odd_days = $first_day + $days_remainder; // Do we have a Saturday or Sunday in the remainder?
				if ($odd_days > 7) { // Sunday
					$days_remainder--;
				}
				if ($odd_days > 6) { // Saturday
					$days_remainder--;
				}
				$datediff = ($weeks_difference * 5) + $days_remainder;
				break;
			case "ww": // Number of full weeks
				$datediff = floor($difference / 604800);
				break;
			case "h": // Number of full hours
				$datediff = floor($difference / 3600);
				break;
			case "n": // Number of full minutes
				$datediff = floor($difference / 60);
				break;
			default: // Number of full seconds (default)
				$datediff = $difference;
				break;
		}
		
		return $datediff;
	}
	
?>
