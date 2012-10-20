<?php
	require_once 'Includes/Configuration.php';

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Activity Log #
	/////////////////////////////////////////////////////////////////////////

	$admin_page_id = ($admin_page_id)?$admin_page_id:49;
	
	$page_vars			  = build_page($db, $admin_page_id);
	$display_page_title	  = $page_vars['title'];
	$display_mainmenu	  = $page_vars['mainmenu'];
	$display_utility_menu = $page_vars['utilitymenu'];
	$display_submenu	  = str_replace("List", "Log", $page_vars['submenu']);
	$g['page']['instructions']  = $textile->textileThis($page_vars['instructions']);
	$g['page']['markup']  = ($page_vars['markupref']) ? $g['page']['markup'] : '';

	$body = "search-log";

	$display_content_title	= '<div class="head-flag-links">Search Log | <a href="activity.php">Admin Activity Log</a></div>';
	$display_content_title .= 'Property Search Log';

	if ($g['log']['active']) {

		require_once 'Simple/Log/LogManager.php';

		$log = new LogManager();
		$log_array = $log->getLog($g['log']['search']);

		foreach ($log_array as $v) {
			$log_date = explode(' ',$v[0]);
			$log_type = $v[1];
			$log_result_sec1 = $v[2];
			$log_result_sec2 = $v[3];
			if ($log_type=="Filtered") {
				$log_type_category = ($log_result_sec1 && $log_result_sec1!='all')?$log_result_sec1."/":"All Categories/ ";
				$log_type_city = ($log_result_sec2 && $log_result_sec2!='all')?$log_result_sec2:"All Cities";
				$log_split[$log_date[0]][$v[1]][$log_type_category.$log_type_city] = $log_date[1];
			} else if ($log_type=="Searched") {
				$log_split[$log_date[0]][$v[1]][$log_result_sec1] = $log_date[1];
			}
		}

		krsort($log_split);
		$display_list = '';
		foreach ($log_split as $k => $v) {
			$display_list .= "\n\t".'<li><span>'.date('M d Y', strtotime($k)).'</span>';
			$display_list .= "\n\t\t".'<ul>';
			foreach ($v as $k2 => $v2) {
				if ($k2!='error') {
					$display_list .= "\n\t\t\t".'<li>'.$k2.': '.count($v2);
					$display_list .= "\n\t\t\t\t".'<ul>';
					foreach ($v2 as $k3 => $v3) {
						$display_list .= "\n\t\t\t\t\t".'<li><strong>@ '.$k3.'</strong></li>';
					}
					$display_list .= "\n\t\t\t\t".'</ul>';
					$display_list .= "\n\t\t\t".'</li>';
				}
			}
			$display_list .= "\n\t\t".'</ul>';
			$display_list .= "\n\t".'</li>';
		}

		$display_form = <<<HTML

	<div  class="activity-log">
		<ul>
			$display_list
		</ul>
	</div>

HTML;

		$g['page']['header'] .= <<<HTML
	
<style type="text/css" media="screen">
	
	.activity-log ul {
		margin:0;
		font-family: helvetica;
		list-style: none;
		padding:0;
		font-weight: bold;
		font-size:14px;
		color:#000;
	}
	.activity-log ul li {
		margin-top:10px;
	}
	
	.activity-log ul li span {
		padding-left:20px;
	}
	
	.activity-log ul li ul {
		border-top:1px solid #e5e5e5;
		padding-left: 20px;
		margin-bottom:15px;
		font-size:12px;
		color:green;
	}
	
	.activity-log ul li ul li ul {
		font-weight: normal;
		color: #666;
	}
	
	.activity-log ul li ul li ul li {
		padding-right:20px;
	}
	
	.activity-log strong {
		color:#000;
		font-weight: normal;
	}

</style>

	
HTML;
	} else {
		$g['page']['instructions'] = '';
		$display_system_message = <<<HTML

<div class="system_note">
	<strong>System logging is disabled for this account</strong><br />
	If you would like activity logging enabled <a href="mailto:{$g['administrator']['email']}">let us know</a>.
</div>

HTML;
		$display_form = <<<HTML

<div class="table-wrapper">
	<p>The activiy log allows you to track who is doing what to your website. It serves as a good 
		summary of recent changes made to your website or even as a reminder that you should update your website.</p>
</div>

HTML;
	}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Xipe #
	/////////////////////////////////////////////////////////////////////////

	$tpl = new HTML_Template_Xipe($g['xipe']['options']);
	$tpl->compile($g['xipe']['path'].'/default.tpl');
	include($tpl->getCompiledTemplate());
?>