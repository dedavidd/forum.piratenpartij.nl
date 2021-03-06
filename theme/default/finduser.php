<?php
/**
* copyright            : (C) 2001-2011 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: finduser.php.t 5309 2011-07-10 09:00:29Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}function pager_replace(&$str, $s, $c)
{
	$str = str_replace(array('%s', '%c'), array($s, $c), $str);
}

function tmpl_create_pager($start, $count, $total, $arg, $suf='', $append=1, $js_pager=0, $no_append=0)
{
	if (!$count) {
		$count =& $GLOBALS['POSTS_PER_PAGE'];
	}
	if ($total <= $count) {
		return;
	}

	$upfx = '';
	if ($GLOBALS['FUD_OPT_2'] & 32768 && (!empty($_SERVER['PATH_INFO']) || strpos($arg, '?') === false)) {
		if (!$suf) {
			$suf = '/';
		} else if (strpos($suf, '//') !== false) {
			$suf = preg_replace('!/+!', '/', $suf);
		}
	} else if (!$no_append) {
		$upfx = '&amp;start=';
	}

	$cur_pg = ceil($start / $count);
	$ttl_pg = ceil($total / $count);

	$page_pager_data = '';

	if (($page_start = $start - $count) > -1) {
		if ($append) {
			$page_first_url = $arg . $upfx . $suf;
			$page_prev_url = $arg . $upfx . $page_start . $suf;
		} else {
			$page_first_url = $page_prev_url = $arg;
			pager_replace($page_first_url, 0, $count);
			pager_replace($page_prev_url, $page_start, $count);
		}

		$page_pager_data .= !$js_pager ? '&nbsp;<a href="'.$page_first_url.'" class="PagerLink">&laquo;</a>&nbsp;&nbsp;<a href="'.$page_prev_url.'" accesskey="p" class="PagerLink">&lsaquo;</a>&nbsp;&nbsp;' : '&nbsp;<a href="javascript://" onclick="'.$page_first_url.'" class="PagerLink">&laquo;</a>&nbsp;&nbsp;<a href="javascript://" onclick="'.$page_prev_url.'" class="PagerLink">&lsaquo;</a>&nbsp;&nbsp;';
	}

	$mid = ceil($GLOBALS['GENERAL_PAGER_COUNT'] / 2);

	if ($ttl_pg > $GLOBALS['GENERAL_PAGER_COUNT']) {
		if (($mid + $cur_pg) >= $ttl_pg) {
			$end = $ttl_pg;
			$mid += $mid + $cur_pg - $ttl_pg;
			$st = $cur_pg - $mid;
		} else if (($cur_pg - $mid) <= 0) {
			$st = 0;
			$mid += $mid - $cur_pg;
			$end = $mid + $cur_pg;
		} else {
			$st = $cur_pg - $mid;
			$end = $mid + $cur_pg;
		}

		if ($st < 0) {
			$start = 0;
		}
		if ($end > $ttl_pg) {
			$end = $ttl_pg;
		}
		if ($end - $start > $GLOBALS['GENERAL_PAGER_COUNT']) {
			$end = $start + $GLOBALS['GENERAL_PAGER_COUNT'];
		}
	} else {
		$end = $ttl_pg;
		$st = 0;
	}

	while ($st < $end) {
		if ($st != $cur_pg) {
			$page_start = $st * $count;
			if ($append) {
				$page_page_url = $arg . $upfx . $page_start . $suf;
			} else {
				$page_page_url = $arg;
				pager_replace($page_page_url, $page_start, $count);
			}
			$st++;
			$page_pager_data .= !$js_pager ? '<a href="'.$page_page_url.'" class="PagerLink">'.$st.'</a>&nbsp;&nbsp;' : '<a href="javascript://" onclick="'.$page_page_url.'" class="PagerLink">'.$st.'</a>&nbsp;&nbsp;';
		} else {
			$st++;
			$page_pager_data .= !$js_pager ? $st.'&nbsp;&nbsp;' : $st.'&nbsp;&nbsp;';
		}
	}

	$page_pager_data = substr($page_pager_data, 0 , strlen((!$js_pager ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;')) * -1);

	if (($page_start = $start + $count) < $total) {
		$page_start_2 = ($st - 1) * $count;
		if ($append) {
			$page_next_url = $arg . $upfx . $page_start . $suf;
			$page_last_url = $arg . $upfx . $page_start_2 . $suf;
		} else {
			$page_next_url = $page_last_url = $arg;
			pager_replace($page_next_url, $upfx . $page_start, $count);
			pager_replace($page_last_url, $upfx . $page_start_2, $count);
		}
		$page_pager_data .= !$js_pager ? '&nbsp;&nbsp;<a href="'.$page_next_url.'" accesskey="n" class="PagerLink">&rsaquo;</a>&nbsp;&nbsp;<a href="'.$page_last_url.'" class="PagerLink">&raquo;</a>' : '&nbsp;&nbsp;<a href="javascript://" onclick="'.$page_next_url.'" class="PagerLink">&rsaquo;</a>&nbsp;&nbsp;<a href="javascript://" onclick="'.$page_last_url.'" class="PagerLink">&raquo;</a>';
	}

	return !$js_pager ? '<span class="SmallText fb">Pagina&#39;s ('.$ttl_pg.'): ['.$page_pager_data.']</span>' : '<span class="SmallText fb">Pagina&#39;s ('.$ttl_pg.'): ['.$page_pager_data.']</span>';
}$GLOBALS['__revfs'] = array('&quot;', '&lt;', '&gt;', '&amp;');
$GLOBALS['__revfd'] = array('"', '<', '>', '&');

function reverse_fmt($data)
{
	$s = $d = array();
	foreach ($GLOBALS['__revfs'] as $k => $v) {
		if (strpos($data, $v) !== false) {
			$s[] = $v;
			$d[] = $GLOBALS['__revfd'][$k];
		}
	}

	return $s ? str_replace($s, $d, $data) : $data;
}function alt_var($key)
{
	if (!isset($GLOBALS['_ALTERNATOR_'][$key])) {
		$args = func_get_args(); unset($args[0]);
		$GLOBALS['_ALTERNATOR_'][$key] = array('p' => 2, 't' => func_num_args(), 'v' => $args);
		return $args[1];
	}
	$k =& $GLOBALS['_ALTERNATOR_'][$key];
	if ($k['p'] == $k['t']) {
		$k['p'] = 1;
	}
	return $k['v'][$k['p']++];
}function draw_user_link($login, $type, $custom_color='')
{
	if ($custom_color) {
		return '<span style="color: '.$custom_color.'">'.$login.'</span>';
	}

	switch ($type & 1572864) {
		case 0:
		default:
			return $login;
		case 1048576:
			return '<span class="adminColor">'.$login.'</span>';
		case 524288:
			return '<span class="modsColor">'.$login.'</span>';
	}
}

	if (!$is_a && !($FUD_OPT_1 & 8388608) && (!($FUD_OPT_1 & 4194304) || !_uid)) {
		std_error((!_uid ? 'login' : 'disabled'));
	}

	if (isset($_GET['js_redr'])) {
		define('plain_form', 1);
		$is_a = 0;
	}

	$TITLE_EXTRA = ': Gebruiker zoeken';

	ses_update_status($usr->sid, 'Bezig met het zoeken naar gebruikers');

if (_uid) {
	$admin_cp = $accounts_pending_approval = $group_mgr = $reported_msgs = $custom_avatar_queue = $mod_que = $thr_exch = '';

	if ($usr->users_opt & 524288 || $is_a) {	// is_mod or admin.
		if ($is_a) {
			// Approval of custom Avatars.
			if ($FUD_OPT_1 & 32 && ($avatar_count = q_singleval('SELECT count(*) FROM fud30_users WHERE users_opt>=16777216 AND '. q_bitand('users_opt', 16777216) .' > 0'))) {
				$custom_avatar_queue = '| <a href="adm/admavatarapr.php?S='.s.'&amp;SQ='.$GLOBALS['sq'].'">Wachtrij voor aangepaste avatars</a> <span class="GenTextRed">('.$avatar_count.')</span>';
			}

			// All reported messages.
			if ($report_count = q_singleval('SELECT count(*) FROM fud30_msg_report')) {
				$reported_msgs = '| <a href="index.php?t=reported&amp;'._rsid.'" rel="nofollow">Gerapporteerde berichten</a> <span class="GenTextRed">('.$report_count.')</span>';
			}

			// All thread exchange requests.
			if ($thr_exchc = q_singleval('SELECT count(*) FROM fud30_thr_exchange')) {
				$thr_exch = '| <a href="index.php?t=thr_exch&amp;'._rsid.'">Verplaatsverzoeken</a> <span class="GenTextRed">('.$thr_exchc.')</span>';
			}

			// All account approvals.
			if ($FUD_OPT_2 & 1024 && ($accounts_pending_approval = q_singleval('SELECT count(*) FROM fud30_users WHERE users_opt>=2097152 AND '. q_bitand('users_opt', 2097152) .' > 0 AND id > 0'))) {
				$accounts_pending_approval = '| <a href="adm/admuserapr.php?S='.s.'&amp;SQ='.$GLOBALS['sq'].'">Goed te keuren gebruikers</a> <span class="GenTextRed">('.$accounts_pending_approval.')</span>';
			} else {
				$accounts_pending_approval = '';
			}

			$q_limit = '';
		} else {
			// Messages reported in moderated forums.
			if ($report_count = q_singleval('SELECT count(*) FROM fud30_msg_report mr INNER JOIN fud30_msg m ON mr.msg_id=m.id INNER JOIN fud30_thread t ON m.thread_id=t.id INNER JOIN fud30_mod mm ON t.forum_id=mm.forum_id AND mm.user_id='. _uid)) {
				$reported_msgs = '| <a href="index.php?t=reported&amp;'._rsid.'" rel="nofollow">Gerapporteerde berichten</a> <span class="GenTextRed">('.$report_count.')</span>';
			}

			// Thread move requests in moderated forums.
			if ($thr_exchc = q_singleval('SELECT count(*) FROM fud30_thr_exchange te INNER JOIN fud30_mod m ON m.user_id='. _uid .' AND te.frm=m.forum_id')) {
				$thr_exch = '| <a href="index.php?t=thr_exch&amp;'._rsid.'">Verplaatsverzoeken</a> <span class="GenTextRed">('.$thr_exchc.')</span>';
			}

			$q_limit = ' INNER JOIN fud30_mod mm ON f.id=mm.forum_id AND mm.user_id='. _uid;
		}

		// Messages requiring approval.
		if ($approve_count = q_singleval('SELECT count(*) FROM fud30_msg m INNER JOIN fud30_thread t ON m.thread_id=t.id INNER JOIN fud30_forum f ON t.forum_id=f.id '. $q_limit .' WHERE m.apr=0 AND f.forum_opt>=2')) {
			$mod_que = '<a href="index.php?t=modque&amp;'._rsid.'">Moderatiewachtrij</a> <span class="GenTextRed">('.$approve_count.')</span>';
		}
	} else if ($usr->users_opt & 268435456 && $FUD_OPT_2 & 1024 && ($accounts_pending_approval = q_singleval('SELECT count(*) FROM fud30_users WHERE users_opt>=2097152 AND '. q_bitand('users_opt', 2097152) .' > 0 AND id > 0'))) {
		$accounts_pending_approval = '| <a href="adm/admuserapr.php?S='.s.'&amp;SQ='.$GLOBALS['sq'].'">Goed te keuren gebruikers</a> <span class="GenTextRed">('.$accounts_pending_approval.')</span>';
	} else {
		$accounts_pending_approval = '';
	}
	if ($is_a || $usr->group_leader_list) {
		$group_mgr = '| <a href="index.php?t=groupmgr&amp;'._rsid.'">Groepsbeheerder</a>';
	}

	if ($thr_exch || $accounts_pending_approval || $group_mgr || $reported_msgs || $custom_avatar_queue || $mod_que) {
		$admin_cp = '<br /><span class="GenText fb">Beheer:</span> '.$mod_que.' '.$reported_msgs.' '.$thr_exch.' '.$custom_avatar_queue.' '.$group_mgr.' '.$accounts_pending_approval.'<br />';
	}
} else {
	$admin_cp = '';
}/* Print number of unread private messages in User Control Panel. */
	if (__fud_real_user__ && $FUD_OPT_1 & 1024) {	// PM_ENABLED
		$c = q_singleval('SELECT count(*) FROM fud30_pmsg WHERE duser_id='. _uid .' AND fldr=1 AND read_stamp=0');
		$ucp_private_msg = $c ? '<li><a href="index.php?t=pmsg&amp;'._rsid.'" title="Privéberichten"><img src="theme/default/images/top_pm'.img_ext.'" alt="" /> U hebt <span class="GenTextRed">('.$c.')</span> ongelezen '.convertPlural($c, array('privébericht','privéberichten')).'</a></li>' : '<li><a href="index.php?t=pmsg&amp;'._rsid.'" title="Privéberichten"><img src="theme/default/images/top_pm'.img_ext.'" alt="" /> Privéberichten</a></li>';
	} else {
		$ucp_private_msg = '';
	}

	if (!isset($_GET['start']) || !($start = (int)$_GET['start'])) {
		$start = 0;
	}

	if (isset($_GET['pc'])) {
		$ord = 'posted_msg_count '. ($_GET['pc'] % 2 ? 'ASC' : 'DESC');
	} else if (isset($_GET['us'])) {
		$ord = 'alias '. ($_GET['us'] % 2 ? 'DESC' : 'ASC');
	} else if (isset($_GET['rd'])) {
		$ord = 'join_date '. ($_GET['rd'] % 2 ? 'DESC' : 'ASC');
	} else if (isset($_GET['fl'])) {
		$ord = 'flag_cc '. ($_GET['fl'] % 2 ? 'DESC' : 'ASC');
	} else if (isset($_GET['lv'])) {
		$ord = 'last_visit '. ($_GET['lv'] % 2 ? 'DESC' : 'ASC');
	} else {
		$ord = 'id DESC';
	}
	$usr_login = !empty($_GET['usr_login']) ? trim((string)$_GET['usr_login']) : '';

	if ($usr_login) {
		$qry = 'alias LIKE '. _esc(char_fix(htmlspecialchars(addcslashes($usr_login.'%','\\')))) .' AND';
	} else {
		$qry = '';
	}

	$find_user_data = '';
	// Exclude anonymous (id=0) and spider users (users_opt&1073741824).
	$c = uq(q_limit('SELECT /*!40000 SQL_CALC_FOUND_ROWS */ flag_cc, flag_country, home_page, users_opt, alias, join_date, posted_msg_count, id, custom_color, last_visit FROM fud30_users WHERE '. $qry .' id>1 AND '. q_bitand('users_opt', 1073741824) .' = 0 ORDER BY '. $ord,
			$MEMBERS_PER_PAGE, $start));
	while ($r = db_rowobj($c)) {
		$find_user_data .= '<tr class="'.alt_var('finduser_alt','RowStyleA','RowStyleB').'">
	'.($GLOBALS['FUD_OPT_3'] & 524288 ? '<td>'.($r->flag_cc ? '<img src="images/flags/'.$r->flag_cc.'.png" border="0" width="16" height="11" alt="'.$r->flag_country.'" title="'.$r->flag_country.'" />' : '' )  .'</td>' : '' )  .'
	<td class="nw GenText"><a href="index.php?t=usrinfo&amp;id='.$r->id.'&amp;'._rsid.'">'.draw_user_link($r->alias, $r->users_opt, $r->custom_color).'</a>'.($r->users_opt & 131072 ? '' : '&nbsp;&nbsp;(onbevestigde gebruiker)' ) .'</td>
	<td class="ac nw">'.$r->posted_msg_count.'</td><td class="DateText nw">'.strftime('%a, %d %B %Y', $r->join_date).'</td><td class="nw GenText"><a href="index.php?t=showposts&amp;id='.$r->id.'&amp;'._rsid.'"><img alt="" src="theme/default/images/show_posts.gif" /></a>
'.(($FUD_OPT_2 & 1073741824 && $r->users_opt & 16) ? '<a href="index.php?t=email&amp;toi='.$r->id.'&amp;'._rsid.'" rel="nofollow"><img src="theme/default/images/msg_email.gif" alt="" /></a>' : '' ) .'
'.(($FUD_OPT_1 & 1024 && _uid) ? '<a href="index.php?t=ppost&amp;'._rsid.'&amp;toi='.$r->id.'"><img src="theme/default/images/msg_pm.gif" alt="" /></a>' : '' ) .'
'.($r->home_page ? '<a href="'.$r->home_page.'" rel="nofollow"><img alt="" src="theme/default/images/homepage.gif" /></a>' : '' ) .'</td>
	'.($is_a ? '<td class="SmallText nw">
	<a href="'.$GLOBALS['WWW_ROOT'].'adm/admuser.php?usr_id='.$r->id.'&amp;S='.s.'&amp;act=1&amp;SQ='.$GLOBALS['sq'].'">Bewerken</a> ||
	<a href="'.$GLOBALS['WWW_ROOT'].'adm/admuser.php?usr_id='.$r->id.'&amp;S='.s.'&amp;act=del&amp;f=1&amp;SQ='.$GLOBALS['sq'].'">Verwijderen</a> ||
	'.($r->users_opt & 65536 ? '<a href="'.$GLOBALS['WWW_ROOT'].'adm/admuser.php?act=block&amp;usr_id='.$r->id.'&amp;S='.s.'&amp;SQ='.$GLOBALS['sq'].'">Deblokkeren</a>' : '<a href="'.$GLOBALS['WWW_ROOT'].'adm/admuser.php?act=block&amp;usr_id='.$r->id.'&amp;S='.s.'&amp;SQ='.$GLOBALS['sq'].'">Blokkeren</a>' ) .'
</td>' : '' ) .'
</tr>';
	}
	unset($c);
	if (!$find_user_data) {
		$find_user_data = '<tr class="RowStyleA">
	<td colspan="'.($is_a ? '5' : '4' )  .'" class="wa GenText">Deze gebruiker bestaat niet</td>
</tr>';
	}

	$pager = '';
	if (($total = (int) q_singleval('SELECT /*!40000 FOUND_ROWS(), */ -1')) < 0) {
		$total = q_singleval('SELECT count(*) FROM fud30_users WHERE '. $qry .' id > 1 AND '. q_bitand('users_opt', 1073741824) .' = 0');
	}
	if ($total > $MEMBERS_PER_PAGE) {
		if ($FUD_OPT_2 & 32768) {
			$pg = 'index.php/ml/';

			if (isset($_GET['pc'])) {
				$pg .= (int)$_GET['pc'] .'/';
			} else if (isset($_GET['us'])) {
				$pg .= (int)$_GET['us'] .'/';
			} else if (isset($_GET['rd'])) {
				$pg .= (int)$_GET['rd'] .'/';
			} else if (isset($_GET['fl'])) {
				$pg .= ($_GET['fl']+6) .'/';
			} else if (isset($_GET['lv'])) {
				$pg .= (int)$_GET['lv'] .'/';
			} else {
				$pg .= '0/';
			}

			$ul = $usr_login ? urlencode($usr_login) : 0;
			$pg2 = '/'. $ul .'/';

			if (isset($_GET['js_redr'])) {
				$pg2 .= '1/';
			}
			$pg2 .= _rsid;

			$pager = tmpl_create_pager($start, $MEMBERS_PER_PAGE, $total, $pg, $pg2);
		} else {
			$pg = 'index.php?t=finduser&amp;'. _rsid .'&amp;';
			if ($usr_login) {
				$pg .= 'usr_login='. urlencode($usr_login) .'&amp;';
			}
			if (isset($_GET['pc'])) {
				$pg .= 'pc='. (int)$_GET['pc'] .'&amp;';
			}
			if (isset($_GET['us'])) {
				$pg .= 'us='. (int)$_GET['us'] .'&amp;';
			}
			if (isset($_GET['rd'])) {
				$pg .= 'rd='. (int)$_GET['rd'] .'&amp;';
			}
			if (isset($_GET['fl'])) {
				$pg .= 'fl='. (int)$_GET['fl'] .'&amp;';
			}
			if (isset($_GET['lv'])) {
				$pg .= 'lv='. (int)$_GET['lv'] .'&amp;';
                        }
			if (isset($_GET['js_redr'])) {
				$pg .= 'js_redr='. urlencode($_GET['js_redr']) .'&amp;';
			}
			$pager = tmpl_create_pager($start, $MEMBERS_PER_PAGE, $total, $pg);
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
	<?php echo (__fud_real_user__ ? '<li><a href="index.php?t=uc&amp;'._rsid.'" title="Gebruikersbeheer"><img src="theme/default/images/top_profile'.img_ext.'" alt="" /> Configuratiescherm</a></li>' : ($FUD_OPT_1 & 2 ? '<li><a href="index.php?t=register&amp;'._rsid.'" title="Registreren"><img src="theme/default/images/top_register'.img_ext.'" alt="" /> Registreren</a></li>' : '')).'
	'.(__fud_real_user__ ? '<li><a href="index.php?t=login&amp;'._rsid.'&amp;logout=1&amp;SQ='.$GLOBALS['sq'].'" title="Afmelden"><img src="theme/default/images/top_logout'.img_ext.'" alt="" /> Afmelden [ '.$usr->alias.' ]</a></li>' : '<li><a href="index.php?t=login&amp;'._rsid.'" title="Aanmelden"><img src="theme/default/images/top_login'.img_ext.'" alt="" /> Aanmelden</a></li>'); ?>
	<li><a href="index.php?t=index&amp;<?php echo _rsid; ?>" title="Startpagina"><img src="theme/default/images/top_home<?php echo img_ext; ?>" alt="" /> Startpagina</a></li>
	<?php echo ($is_a || ($usr->users_opt & 268435456) ? '<li><a href="adm/index.php?S='.s.'&amp;SQ='.$GLOBALS['sq'].'" title="Beheer"><img src="theme/default/images/top_admin'.img_ext.'" alt="" /> Beheer</a></li>' : ''); ?>
</ul>
</div>
<br /><?php echo $admin_cp; ?>
<form method="get" id="fufrm" action="index.php"><?php echo _hs; ?>
<table cellspacing="1" cellpadding="2" class="ContentTable">
<tr>
	<th colspan="3">Gebruikersgegevens</th>
</tr>
<tr class="RowStyleA">
	<td class="GenText">Op gebruikersnaam:</td>
	<td class="GenText">
		<input type="text" name="usr_login" tabindex="1" value="<?php echo char_fix(htmlspecialchars($usr_login)); ?>" />
		<input type="submit" class="button" tabindex="2" name="btn_submit" value="Zoeken" /></td>
		<td class="RowStyleC SmallText vt">De zoekmachine voegt automatisch het masker * toe aan uw zoekopdracht. <b>De zoekopdracht is hoofdlettergevoelig</b>.<br />Voorbeeld: om alle gebruikers te vinden waarvan de naam begint met &#39;a&#39;, voer &#39;a&#39; in in het zoekveld.
	</td>
</tr>
</table>
<input type="hidden" name="t" value="finduser" />
</form>
<table cellspacing="1" cellpadding="2" class="ContentTable">
<tr>
	<?php echo ($GLOBALS['FUD_OPT_3'] & 524288 ? '<th width="1"><a class="thLnk" href="index.php?t=finduser&amp;usr_login='.urlencode($usr_login).'&amp;'._rsid.'&amp;fl='.(isset($_GET['fl']) && !($_GET['fl'] % 2) ? '1' : '2' )  .'&amp;btn_submit=Find" rel="nofollow">Vlag</a></th>' : ''); ?>
	<th class="wa"><a class="thLnk" href="index.php?t=finduser&amp;usr_login=<?php echo urlencode($usr_login); ?>&amp;us=<?php echo (isset($_GET['us']) && !($_GET['us'] % 2) ? '1' : '2' )  .'&amp;btn_submit=Find&amp;'._rsid.'" rel="nofollow">Gebruiker</a></th>
	<th class="nw"><a href="index.php?t=finduser&amp;usr_login='.urlencode($usr_login).'&amp;'._rsid.'&amp;pc='.(isset($_GET['pc']) && !($_GET['pc'] % 2) ? '1' : '2' )  .'&amp;btn_submit=Find" class="thLnk" rel="nofollow">Berichtenteller</a></th>
	<th class="nw"><div class="ac"><a href="index.php?t=finduser&amp;usr_login='.urlencode($usr_login).'&amp;'._rsid.'&amp;rd='.(isset($_GET['rd']) && !($_GET['rd'] % 2) ? '1' : '2' )  .'&amp;btn_submit=Find" class="thLnk" rel="nofollow">Registratiedatum</a></div></th>
	<th class="ac">Handeling</th>
	'.($is_a ? '<th class="nw">Beheerdersinstellingen</th>' : ''); ?>
</tr>
<?php echo $find_user_data; ?>
</table>
<?php echo $pager; ?>
<br /><div class="ac"><span class="curtime"><b>Huidige tijd:</b> <?php echo strftime('%a %b %#d %H:%M:%S %Z %Y', __request_timestamp__); ?></span></div>
<?php echo $page_stats; ?>
<script>
	document.forms['fufrm'].usr_login.focus();
</script>
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
