<?php
/**
* copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: showposts.php.t 4994 2010-09-02 17:33:29Z naudefj $
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
}function &get_all_read_perms($uid, $mod)
{
	$limit = array(0);

	$r = uq('SELECT resource_id, group_cache_opt FROM fud30_group_cache WHERE user_id='. _uid);
	while ($ent = db_rowarr($r)) {
		$limit[$ent[0]] = $ent[1] & 2;
	}
	unset($r);

	if (_uid) {
		if ($mod) {
			$r = uq('SELECT forum_id FROM fud30_mod WHERE user_id='. _uid);
			while ($ent = db_rowarr($r)) {
				$limit[$ent[0]] = 2;
			}
			unset($r);
		}

		$r = uq('SELECT resource_id FROM fud30_group_cache WHERE resource_id NOT IN ('. implode(',', array_keys($limit)) .') AND user_id=2147483647 AND '. q_bitand('group_cache_opt', 2) .' > 0');
		while ($ent = db_rowarr($r)) {
			if (!isset($limit[$ent[0]])) {
				$limit[$ent[0]] = 2;
			}
		}
		unset($r);
	}

	return $limit;
}

function perms_from_obj($obj, $adm)
{
	$perms = 1|2|4|8|16|32|64|128|256|512|1024|2048|4096|8192|16384|32768|262144;

	if ($adm || $obj->md) {
		return $perms;
	}

	return ($perms & $obj->group_cache_opt);
}

function make_perms_query(&$fields, &$join, $fid='')
{
	if (!$fid) {
		$fid = 'f.id';
	}

	if (_uid) {
		$join = ' INNER JOIN fud30_group_cache g1 ON g1.user_id=2147483647 AND g1.resource_id='. $fid .' LEFT JOIN fud30_group_cache g2 ON g2.user_id='. _uid .' AND g2.resource_id='. $fid .' ';
		$fields = ' COALESCE(g2.group_cache_opt, g1.group_cache_opt) AS group_cache_opt ';
	} else {
		$join = ' INNER JOIN fud30_group_cache g1 ON g1.user_id=0 AND g1.resource_id='. $fid .' ';
		$fields = ' g1.group_cache_opt ';
	}
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
}

	if (!isset($_GET['id']) || !($tmp = db_saq('SELECT id, alias, posted_msg_count, join_date FROM fud30_users WHERE id='. (int)$_GET['id']))) {
		invl_inp_err();
	} else {
		list($uid,$u_alias,$u_pcount,$u_reg_date) = $tmp;
	}

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
	}$tabs = '';
if (_uid) {
	$tablist = array(
'Meldingen'=>'uc',
'Gebruikersinstellingen'=>'register',
'Abonnementen'=>'subscribed',
'Bladwijzers'=>'bookmarked',
'Ingebrachte gebruikers'=>'referals',
'Vriendenlijst'=>'buddy_list',
'Negeerlijst'=>'ignore_list',
'Eigen berichten weergeven'=>'showposts'
);

	if (!($FUD_OPT_2 & 8192)) {
		unset($tablist['Ingebrachte gebruikers']);
	}

	if (isset($_POST['mod_id'])) {
		$mod_id_chk = $_POST['mod_id'];
	} else if (isset($_GET['mod_id'])) {
		$mod_id_chk = $_GET['mod_id'];
	} else {
		$mod_id_chk = null;
	}

	if (!$mod_id_chk) {
		if ($FUD_OPT_1 & 1024) {
			$tablist['Privéberichten'] = 'pmsg';
		}
		$pg = ($_GET['t'] == 'pmsg_view' || $_GET['t'] == 'ppost') ? 'pmsg' : $_GET['t'];

		foreach($tablist as $tab_name => $tab) {
			$tab_url = 'index.php?t='. $tab . (s ? '&amp;S='. s : '');
			if ($tab == 'referals') {
				if (!($FUD_OPT_2 & 8192)) {
					continue;
				}
				$tab_url .= '&amp;id='. _uid;
			} else if ($tab == 'showposts') {
				$tab_url .= '&amp;id='. _uid;
			}
			$tabs .= $pg == $tab ? '<td class="tabON"><div class="tabT"><a class="tabON" href="'.$tab_url.'">'.$tab_name.'</a></div></td>' : '<td class="tabI"><div class="tabT"><a href="'.$tab_url.'">'.$tab_name.'</a></div></td>';
		}

		$tabs = '<table cellspacing="1" cellpadding="0" class="tab">
<tr>
	'.$tabs.'
</tr>
</table>';
	}
}

	$TITLE_EXTRA = ': Berichten weergeven van: '.$u_alias;

	ses_update_status($usr->sid, 'Berichten van <a href="index.php?t=usrinfo&amp;id='.$uid.'">'.$u_alias.'</a> aan het bekijken');

	if (!isset($_GET['start']) || !($start = (int)$_GET['start'])) {
		$start = 0;
	}

	if (!$is_a) {
		$fids = implode(',', array_keys(get_all_read_perms(_uid, ($usr->users_opt & 524288)), 2));
	}

	if (isset($_GET['so']) && !strcasecmp((string)$_GET['so'], 'asc')) {
		$SORT_ORDER = 'ASC';
		$SORT_ORDER_R = 'DESC';
	} else {
		$SORT_ORDER = 'DESC';
		$SORT_ORDER_R = 'ASC';
	}

	$post_entry = '';
	if ($is_a || $fids) {
		$qry_limit = $is_a ? '' : 'f.id IN ('. $fids .') AND ';

		$c = uq(q_limit('SELECT /*!40000 SQL_CALC_FOUND_ROWS */ f.name, f.id as fid, m.subject, m.id, m.post_stamp
			FROM fud30_msg m
			INNER JOIN fud30_thread t ON m.thread_id=t.id
			INNER JOIN fud30_forum f ON t.forum_id=f.id
			INNER JOIN fud30_cat c ON c.id=f.cat_id
			WHERE '. $qry_limit .' m.apr=1 AND m.poster_id='. $uid .'
			ORDER BY m.post_stamp '. $SORT_ORDER,
			$THREADS_PER_PAGE, $start));

		while ($r = db_rowarr($c)) {
			$post_entry .= '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'">
	<td class="GenText"><a href="index.php?t='.d_thread_view.'&amp;goto='.$r[3].'&amp;'._rsid.'#msg_'.$r[3].'">'.$r[2].'</a></td>
	<td class="GenText nw"><a href="index.php?t='.t_thread_view.'&amp;frm_id='.$r[1].'&amp;'._rsid.'">'.$r[0].'</a></td>
	<td class="DateText nw">'.strftime('%a, %d %B %Y %H:%M', $r[4]).'</td>
</tr>';
		}
		unset($c);

		/* We need the total for the pager & we don't trust the user to pass it via GET or POST. */
		if (($total = (int) q_singleval('SELECT /*!40000 FOUND_ROWS(), */ -1')) < 0) {
			$total = q_singleval('SELECT count(*)
					FROM fud30_msg m
					INNER JOIN fud30_thread t ON m.thread_id=t.id
					INNER JOIN fud30_forum f ON t.forum_id=f.id
					INNER JOIN fud30_cat c ON c.id=f.cat_id
					WHERE '. $qry_limit .' m.apr=1 AND m.poster_id='. $uid);
		}

		if ($FUD_OPT_2 & 32768) {
			$pager = tmpl_create_pager($start, $THREADS_PER_PAGE, $total, 'index.php/sp/'. $uid .'/'. $SORT_ORDER .'/', '/'. _rsid);
		} else {
			$pager = tmpl_create_pager($start, $THREADS_PER_PAGE, $total, 'index.php?t=showposts&amp;id='. $uid .'&amp;so='. $SORT_ORDER .'&amp;'. _rsid);
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
<?php echo (isset($_GET['id']) && _uid == $_GET['id'] ? $tabs.'' : ''); ?>
<?php echo $admin_cp; ?>
<table cellspacing="1" cellpadding="2" class="ContentTable">
<tr>
	<th colspan="3">Gebruikersgegevens</th>
</tr>
<tr class="RowStyleA">
	<td class="GenText wa">Aanmelden: <a href="index.php?t=usrinfo&amp;id=<?php echo $uid; ?>&amp;<?php echo _rsid; ?>"><?php echo $u_alias; ?></a></td>
	<td class="GenText nw">Geregistreerd op <?php echo strftime('%a, %d %B %Y', $u_reg_date); ?></td>
	<td class="GenText nw">Aantal berichten <?php echo $u_pcount; ?></td>
</tr>
<tr>
	<th class="wa">Onderwerp</th>
	<th class="nw">Forum:</th>
	<th class="nw"><a class="thLnk" href="index.php?t=showposts&amp;so=<?php echo $SORT_ORDER_R; ?>&amp;id=<?php echo $uid; ?>&amp;<?php echo _rsid; ?>" rel="nofollow">Berichtdatum</a></th>
</tr>
<?php echo $post_entry; ?>
</table>
<?php echo $pager; ?>
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