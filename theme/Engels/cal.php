<?php
/**
* copyright            : (C) 2001-2011 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: calendar.php.t 5375 2011-09-09 05:47:21Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}

if (!($FUD_OPT_3 & 134217728)) {	// Calender is disabled.
	std_error('disabled');
}

ses_update_status($usr->sid, 'De forumkalender aan het bekijken');

$TITLE_EXTRA = ': Kalender';

/* Draw a calendar.
 * This function is called from a template to inject a calender where it's needed.
 */
function draw_calendar($year, $month, $events = array(), $size = 'large', $highlight_y = '', $highlight_m = '', $highlight_d = '') {
	if ($size == 'large') {
		$weekdays = array('zondag','maandag','dinsdag','woensdag','donderdag','vrijdag','zaterdag');
	} else {
		$weekdays = array('zo','ma','di','wo','do','vr','za');
	}
	// MONDAY $weekdays = array('maandag','dinsdag','woensdag','donderdag','vrijdag','zaterdag', 'zondag');

	/* Table headings. */
	$calendar = '<table cellpadding="0" cellspacing="0" class="calendar">';
	$calendar .= '<tr class="calendar-row"><td class="calendar-day-head">'. implode('</td><td class="calendar-day-head">', $weekdays).'</td></tr>';
	$calendar .= '<tr class="calendar-row">';

	/* Days and weeks vars. */
	$running_day = date('w', mktime(0, 0, 0, $month, 1, $year));
	// MONDAY $running_day = date('w', mktime(0, 0, 0, $month, 1, $year)) - 1;
	$days_in_month = date('t', mktime(0, 0, 0, $month, 1, $year));
	$days_in_this_week = 1;
	$day_counter = 0;

	/* Print "blank" days until the first of the current week. */
	for($x = 0; $x < $running_day; $x++) {
		$calendar .= '<td class="calendar-day-np">&nbsp;</td>';
		$days_in_this_week++;
	}

	/* Keep going with days. */
	for ($day = 1; $day <= $days_in_month; $day++) {
		if ($size == 'large') {
			$calendar .= '<td class="calendar-day"><div style="position:relative; height:100px;">';
		} else {
			$calendar .= '<td class="calendar-day"><div style="position:relative;">';
		}

		/* Add in the day number. */
		if ($year == $highlight_y && $month == $highlight_m && $day == $highlight_d) {
			$calendar .= '<div class="day-number"><b><i>*<a href="index.php?t=cal&amp;view=d&amp;year='.$year.'&amp;month='.$month.'&amp;day='.$day.'">'. $day .'</a></i></b></div>';
		} else {
			$calendar .= '<div class="day-number"><a href="index.php?t=cal&amp;view=d&amp;year='.$year.'&amp;month='.$month.'&amp;day='.$day.'" rel="nofollow">'. $day .'</a></div>';
		}

		$event_day = sprintf('%04d%02d%02d', $year, $month, $day);
		if (isset($events[$event_day])) {
			$event_count = 0;		
			foreach($events[$event_day] as $event) {
				if ($size == 'large') {
					$calendar .= '<div class="event">'. $event .'</div>';
				} else {
					$event_count++;
				}
			}
			if ($size != 'large' && $event_count) {
				$calendar .= '<div class="event">'. $event_count .'</div>';
			}
		} else {
			$calendar.= str_repeat('<p>&nbsp;</p>',2);
		}

		$calendar .= '</div></td>';
		if ($running_day == 6) {
			$calendar .= '</tr>';
			if (($day_counter+1) != $days_in_month) {
				$calendar .= '<tr class="calendar-row">';
			}
			$running_day = -1;
			$days_in_this_week = 0;
		};
		$days_in_this_week++; $running_day++; $day_counter++;
	};

	/* Finish the rest of the days in the week. */
	if($days_in_this_week < 8) {
		for($x = 1; $x <= (8 - $days_in_this_week); $x++) {
			$calendar .= '<td class="calendar-day-np">&nbsp;</td>';
		}
	}

	/* Finalize and return calendar. */
	$calendar .= '</tr></table>';
	return $calendar;
}

/* Query events from database.
 */
function get_events($year, $month, $day = 0) {
	/* Fetch events to display from DB. */
	$events = array();

	/* Display birthdays (DDMMYYYY) on day view. */
	if ($GLOBALS['FUD_OPT_3'] & 268435456 && $day != 0) {
		$c = uq('SELECT id, alias, birthday FROM fud30_users WHERE birthday LIKE \''. sprintf('%02d%02d', $month, $day) .'%\'');
		while ($r = db_rowarr($c)) {
			$yyyy = substr($r[2], 4);
			$mm   = substr($r[2], 0, 2);
			$dd   = substr($r[2], 2, 2);
			$age  = ($yyyy > 0) ? $year - $yyyy : 0;
			$user = '<a href="index.php?t=usrinfo&amp;id='.$r[0].'&amp;'._rsid.'">'.$r[1].'</a>';
			$events[ $year . $mm . $dd ][] = 'Verjaardag: '.$user.' '.($age ? '('.convertPlural($age, array(''.$age.' jaar',''.$age.' jaar')).').' : '' ) ; // Replace birth year with current year.
		}
	}

	/* Defined events. */
	$c = uq('SELECT event_day, descr, link FROM fud30_calendar WHERE (event_month=\''. $month .'\' AND event_year=\''. $year .'\') OR (event_month=\'*\' AND event_year=\''. $year .'\') OR (event_month=\''. $month .'\' AND event_year=\'*\') OR (event_month=\'*\' AND event_year=\'*\')');
	while ($r = db_rowarr($c)) {
		if (empty($r[2])) {
			$events[ sprintf('%04d%02d%02d', $year, $month, $r[0]) ][] = $r[1];
		} else {
			$events[ sprintf('%04d%02d%02d', $year, $month, $r[0]) ][] = '<a href="'. $r[2] .'">'. $r[1] .'</a>';
		}
	}

	return $events;
}

/* Print number of unread private messages in User Control Panel. */
	if (__fud_real_user__ && $FUD_OPT_1 & 1024) {	// PM_ENABLED
		$c = q_singleval('SELECT count(*) FROM fud30_pmsg WHERE duser_id='. _uid .' AND fldr=1 AND read_stamp=0');
		$ucp_private_msg = $c ? '<li><a href="index.php?t=pmsg&amp;'._rsid.'" title="Privébericht"><img src="theme/default/images/top_pm'.img_ext.'" alt="" /> U hebt <span class="GenTextRed">('.$c.')</span> ongelezen '.convertPlural($c, array('privébericht','privéberichten')).'</a></li>' : '<li><a href="index.php?t=pmsg&amp;'._rsid.'" title="Privébericht"><img src="theme/default/images/top_pm'.img_ext.'" alt="" /> Privébericht</a></li>';
	} else {
		$ucp_private_msg = '';
	}

/* Get calendar settings. */
$day   = isset($_GET['day'])   ? (int)$_GET['day']   : (int)date('d');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
$view  = isset($_GET['view'])  ? $_GET['view']  : 'm';	// Default to month view.
$months = array('januari','februari','maart','april','mei','juni','juli','augustus','september','oktober','november','december');

/* Build a 'month dropdown' that can be used in templates. */
$select_month_control = '<select name="month" id="month">';
for($m = 1; $m <= 12; $m++) {
	$select_month_control .= '<option value="'. $m .'"'. ($m != $month ? '' : ' selected="selected"') .'>'. $months[ date('n',mktime(0,0,0,$m,1,$year)) - 1 ] .'</option>';
}
$select_month_control .= '</select>';

/* Build a 'year dropdown' that can be used in templates. */
$year_range = 10;
$select_year_control = '<select name="year" id="year">';
for($x = ($year-floor($year_range/2)); $x <= ($year+floor($year_range/2)); $x++) {
	$select_year_control .= '<option value="'. $x .'"'. ($x != $year ? '' : ' selected="selected"') .'>'. $x .'</option>';
}
$select_year_control .= '</select>';

if ($view == 'y') {
	$next_year  = $year + 1;
	$prev_year  = $year - 1;
}

if ($view == 'm') {
	$next_year  = $month != 12 ? $year : $year + 1;
	$prev_year  = $month !=  1 ? $year : $year - 1;
	$next_month = $month != 12 ? $month + 1 : 1;
	$prev_month = $month !=  1 ? $month - 1 : 12;
	
	$events = get_events($year, $month);
}

if ($view == 'd') {
	$tomorrow  = mktime(0, 0, 0, $month, $day+1, $year);
	$yesterday = mktime(0, 0, 0, $month, $day-1, $year);
	
	$next_day   = date('d', $tomorrow);
	$prev_day   = date('d', $yesterday);
	$next_month = date('m', $tomorrow);
	$prev_month = date('m', $yesterday);
	$next_year  = date('Y', $tomorrow);
	$prev_year  = date('Y', $yesterday);

	$events = get_events($year, $month, $day);

	$event_day = sprintf('%04d%02d%02d', $year, $month, $day);
	$events_for_day = '';
	if (isset($events[$event_day])) {
		foreach($events[$event_day] as $event) {
			$events_for_day .= '<li><div class="event">'.$event.'</div></li>';
		}
	}
}

if ($FUD_OPT_2 & 2 || $is_a) {	// PUBLIC_STATS is enabled or Admin user.
	$page_gen_time = number_format(microtime(true) - __request_timestamp_exact__, 5);
	$page_stats = $FUD_OPT_2 & 2 ? '<br /><div class="SmallText al">Totale tijd voor paginaaanmaak: '.convertPlural($page_gen_time, array(''.$page_gen_time.' seconden')).'</div>' : '<br /><div class="SmallText al">Totale tijd voor paginaaanmaak: '.convertPlural($page_gen_time, array(''.$page_gen_time.' seconden')).'</div>';
} else {
	$page_stats = '';
}
?>
<!DOCTYPE html>
<html lang="nl" dir="ltr">
<head>
	<meta charset="utf-8">
	<meta name="description" content="<?php echo (!empty($META_DESCR) ? $META_DESCR.'' : $GLOBALS['FORUM_DESCR'].''); ?>" />
	<title><?php echo $GLOBALS['FORUM_TITLE'].$TITLE_EXTRA; ?></title>
	<base href="<?php echo $GLOBALS['WWW_ROOT']; ?>" />
	<link rel="search" type="application/opensearchdescription+xml" title="<?php echo $GLOBALS['FORUM_TITLE']; ?> Search" href="<?php echo $GLOBALS['WWW_ROOT']; ?>open_search.php" />
	<?php echo $RSS; ?>
	<link rel="stylesheet" href="js/ui/jquery-ui.css" media="screen" />
	<link rel="stylesheet" href="theme/default/forum.css" media="screen" title="Default Forum Theme" />
	<script src="js/jquery.js"></script>
	<script src="js/ui/jquery-ui.js"></script>
	<script src="js/lib.js"></script>
</head>
<body>
<!--  -->
<div class="header">
  <?php echo ($GLOBALS['FUD_OPT_1'] & 1 && $GLOBALS['FUD_OPT_1'] & 16777216 ? '
  <div class="headsearch">
    <form id="headsearch" method="get" action="index.php">'._hs.'
      <br /><label accesskey="f" title="Zoeken">Zoeken:<br />
      <input type="text" name="srch" value="" size="20" placeholder="Zoeken" /></label>
      <input type="hidden" name="t" value="search" />
      <input type="image" src="theme/default/images/search.png" value="Zoeken" alt="Zoeken" name="btn_submit">&nbsp;
    </form>
  </div>
  ' : ''); ?>
  <a href="index.php" title="Startpagina">
    <img src="theme/default/images/header.gif" alt="" align="left" height="80" />
    <span class="headtitle"><?php echo $GLOBALS['FORUM_TITLE']; ?></span>
  </a><br />
  <span class="headdescr"><?php echo $GLOBALS['FORUM_DESCR']; ?><br /><br /></span>
</div>
<div class="content">

<!-- Table for sidebars. -->
<table width="100%"><tr><td>
<div id="UserControlPanel">
<ul>
	<?php echo $ucp_private_msg; ?>
	<?php echo (($FUD_OPT_1 & 8388608 || (_uid && $FUD_OPT_1 & 4194304) || $usr->users_opt & 1048576) ? '<li><a href="index.php?t=finduser&amp;btn_submit=Find&amp;'._rsid.'" title="Leden"><img src="theme/default/images/top_members'.img_ext.'" alt="" /> Leden</a></li>' : ''); ?>
	<?php echo ($FUD_OPT_3 & 134217728 ? '<li><a href="index.php?t=cal&amp;'._rsid.'" title="Kalender"><img src="theme/default/images/calendar'.img_ext.'" alt="" /> Kalender</a></li>' : ''); ?>
	<?php echo ($FUD_OPT_3 & 536870912 ? '<li><a href="index.php?t=page&amp;'._rsid.'" title="Pagina&#39;s"><img src="theme/default/images/pages'.img_ext.'" alt="" /> Pagina&#39;s</a></li>' : ''); ?>
	<?php echo ($FUD_OPT_1 & 16777216 ? ' <li><a href="index.php?t=search'.(isset($frm->forum_id) ? '&amp;forum_limiter='.(int)$frm->forum_id.'' : '' )  .'&amp;'._rsid.'" title="Zoeken"><img src="theme/default/images/top_search'.img_ext.'" alt="" /> Zoeken</a></li>' : ''); ?>
	<li><a accesskey="h" href="index.php?t=help_index&amp;<?php echo _rsid; ?>" title="Hulp"><img src="theme/default/images/top_help<?php echo img_ext; ?>" alt="" /> Hulp</a></li>
	<?php echo (__fud_real_user__ ? '<li><a href="index.php?t=uc&amp;'._rsid.'" title="Gebruikersbeheer"><img src="theme/default/images/top_profile'.img_ext.'" alt="" /> Profiel</a></li>' : ($FUD_OPT_1 & 2 ? '<li><a href="index.php?t=register&amp;'._rsid.'" title="Registreren"><img src="theme/default/images/top_register'.img_ext.'" alt="" /> Registreren</a></li>' : '')).'
	'.(__fud_real_user__ ? '<li><a href="index.php?t=login&amp;'._rsid.'&amp;logout=1&amp;SQ='.$GLOBALS['sq'].'" title="Afmelden"><img src="theme/default/images/top_logout'.img_ext.'" alt="" /> Afmelden [ '.$usr->alias.' ]</a></li>' : '<li><a href="index.php?t=login&amp;'._rsid.'" title="Aanmelden"><img src="theme/default/images/top_login'.img_ext.'" alt="" /> Aanmelden</a></li>'); ?>
	<li><a href="index.php?t=index&amp;<?php echo _rsid; ?>" title="Startpagina"><img src="theme/default/images/top_home<?php echo img_ext; ?>" alt="" /> Startpagina</a></li>
	<?php echo ($is_a || ($usr->users_opt & 268435456) ? '<li><a href="adm/index.php?S='.s.'&amp;SQ='.$GLOBALS['sq'].'" title="Beheer"><img src="theme/default/images/top_admin'.img_ext.'" alt="" /> Beheer</a></li>' : ''); ?>
</ul>
</div>
<table cellspacing="1" cellpadding="2" class="ContentTable">
<?php echo ($view == 'y' ? '
<tr>
	<th colspan="3">
		<h2>&nbsp;<a href="index.php?t=cal&amp;view=y&amp;year='.$prev_year.'" class="control" rel="nofollow">&laquo;</a>&nbsp; 
		'.$year.' &nbsp;
		<a href="index.php?t=cal&amp;view=y&amp;year='.$next_year.'" class="control" rel="nofollow">&raquo;</a>&nbsp;
		</h2>
	</th>
</tr>
<tr>
	<td width="33%" class="vt"><h4>'.$months[0].' '.$year.'</h4>'.draw_calendar($year, 1, null, 'small', $year, $month, $day).'</td>
	<td width="33%" class="vt"><h4>'.$months[1].' '.$year.'</h4>'.draw_calendar($year, 2, null, 'small', $year, $month, $day).'</td>
	<td width="33%" class="vt"><h4>'.$months[2].' '.$year.'</h4>'.draw_calendar($year, 3, null, 'small', $year, $month, $day).'</td>
</tr>
<tr>
	<td width="33%" class="vt"><h4>'.$months[3].' '.$year.'</h4>'.draw_calendar($year, 4, null, 'small', $year, $month, $day).'</td>
	<td width="33%" class="vt"><h4>'.$months[4].' '.$year.'</h4>'.draw_calendar($year, 5, null, 'small', $year, $month, $day).'</td>
	<td width="33%" class="vt"><h4>'.$months[5].' '.$year.'</h4>'.draw_calendar($year, 6, null, 'small', $year, $month, $day).'</td>
</tr>
<tr>
	<td width="33%" class="vt"><h4>'.$months[6].' '.$year.'</h4>'.draw_calendar($year, 7, null, 'small', $year, $month, $day).'</td>
	<td width="33%" class="vt"><h4>'.$months[7].' '.$year.'</h4>'.draw_calendar($year, 8, null, 'small', $year, $month, $day).'</td>
	<td width="33%" class="vt"><h4>'.$months[8].' '.$year.'</h4>'.draw_calendar($year, 9, null, 'small', $year, $month, $day).'</td>
</tr>
<tr>
	<td width="33%" class="vt"><h4>'.$months[9].'  '.$year.'</h4>'.draw_calendar($year, 10, null, 'small', $year, $month, $day).'</td>
	<td width="33%" class="vt"><h4>'.$months[10].' '.$year.'</h4>'.draw_calendar($year, 11, null, 'small', $year, $month, $day).'</td>
	<td width="33%" class="vt"><h4>'.$months[11].' '.$year.'</h4>'.draw_calendar($year, 12, null, 'small', $year, $month, $day).'</td>
</tr>
' : ''); ?>

<?php echo ($view == 'm' ? '
<tr>
	<th width="35%" class="al">
		<a href="index.php?t=cal&amp;view=m&amp;year='.$prev_year.'&amp;month='.$prev_month.'" class="control" rel="nofollow">&laquo;</a>
	</th>
	<th class="ac">
		<h2>'.$months[$month-1].' <a href="index.php?t=cal&amp;view=y&amp;year='.$year.'" class="control" rel="nofollow">'.$year.'</a></h2>
	</th>
	<th width="35%" class="ar">
		<a href="index.php?t=cal&amp;view=m&amp;year='.$next_year.'&amp;month='.$next_month.'" class="control" rel="nofollow">&raquo;</a>
	</th>
</tr>
<tr class="ac">
	<td colspan="3">
		'.draw_calendar($year, $month, $events, 'large', $year, $month, $day).'
	</td>
</tr>
<tr>
	<td class="ac" colspan="3">
		<form method="get" action="index.php">
		<b>Ga naar:</b><input type="hidden" name="t" value="cal" />
		<br />'.$select_month_control.' '.$select_year_control.' <input type="submit" name="submit" value="OK" />
		</form>
	</td>
</tr>
' : ''); ?>

<?php echo ($view == 'd' ? '
<tr>
	<th colspan="2">
		<h2><a href="index.php?t=cal&amp;view=d&amp;year='.$prev_year.'&amp;month='.$prev_month.'&amp;day='.$prev_day.'" class="control" rel="nofollow">&laquo;</a>
		'.$day.' <a href="index.php?t=cal&amp;view=m&amp;month='.$month.'&amp;year='.$year.'"class="control" rel="nofollow">'.$months[$month-1].'</a> <a href="index.php?t=cal&amp;view=y&amp;year='.$year.'" class="control" rel="nofollow">'.$year.'</a>
		<a href="index.php?t=cal&amp;view=d&amp;year='.$next_year.'&amp;month='.$next_month.'&amp;day='.$next_day.'" class="control" rel="nofollow">&raquo;</a></h2>
	</th>
</tr>
<tr>
	<td class="RowStyleB vt" width="55%">
		<h3>Gebeurtenissen voor dag</h3>
		'.($events_for_day ? '<ul>'.$events_for_day.'</ul>' : '<p>Geen gebeurtenissen voor dag.</p>' )  .'
		<br /><br />
		<form method="get" action="index.php">
		Ga naar: <input type="hidden" name="t" value="cal" /><input type="hidden" name="view" value="'.$view.'" />
		'.$select_month_control.' '.$select_year_control.' 
		<input type="hidden" name="day" value="'.$day.'" /><input type="submit" name="submit" value="OK" />
		</form>
	</td>
	<td class="ac" width="45%"> 
		<h4><a href="index.php?t=cal&amp;view=m&amp;month='.$month.'&amp;year='.$year.'" class="control">'.$months[$month-1].' '.$year.'</a></h4>
		'.draw_calendar($year, $month, $events, 'small', $year, $month, $day).'
	</td>
</tr>
' : ''); ?>

</table>
<br /><div class="ac"><span class="curtime"><b>Huidige tijd:</b> <?php echo strftime('%a %b %#d %H:%M:%S %Z %Y', __request_timestamp__); ?></span></div>
<?php echo $page_stats; ?>
<?php echo (!empty($RIGHT_SIDEBAR) ? '
</td><td width="200px" align-"right" valign="top" class="sidebar-right">
	'.$RIGHT_SIDEBAR.'
' : ''); ?>
</td></tr></table>

</div>
<div class="footer ac">
	<b>.::</b>
	<a href="mailto:<?php echo $GLOBALS['ADMIN_EMAIL']; ?>">Contact</a>
	<b>::</b>
	<a href="index.php?t=index&amp;<?php echo _rsid; ?>">Hoofdmenu</a>
	<b>::.</b>
	<p class="SmallText">Maakt gebruik van FUDforum <?php echo $GLOBALS['FORUM_VERSION']; ?>.<br />Copyright &copy;2001-2012 <a href="http://fudforum.org/">FUDforum Bulletin Board Software</a></p>
</div>

</body></html>
