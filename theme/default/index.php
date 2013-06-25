<?php
/**
* copyright            : (C) 2001-2011 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: index.php.t 5264 2011-05-16 16:29:13Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}$collapse = $usr->cat_collapse_status ? unserialize($usr->cat_collapse_status) : array();
	$cat_id = !empty($_GET['cat'])    ? (int) $_GET['cat']    : 0;
	$frm_id = !empty($_GET['frm_id']) ? (int) $_GET['frm_id'] : 0;

	if ($cat_id && !empty($collapse[$cat_id])) {
		$collapse[$cat_id] = 0;
	}

	require $FORUM_SETTINGS_PATH .'idx.inc';
	if (!isset($cidxc[$cat_id])) {
		$cat_id = 0;
	}

	$cbuf = $forum_list_table_data = $cat_path = '';

	if ($cat_id) {
		$cid = $cat_id;
		while (($cid = $cidxc[$cid][4]) > 0) {
			$cat_path = '&nbsp;&raquo; <a href="index.php?t=i&amp;cat='.$cid.'&amp;'._rsid.'">'.$cidxc[$cid][1].'</a>'. $cat_path;
		}
		$cat_path = '<br />
<a href="index.php?t=i&amp;'._rsid.'">Startpagina</a>
'.$cat_path.'&nbsp;&raquo; <b>'.$cidxc[$cat_id][1].'</b>';
	}

	/* List of fetched fields & their ids
	  0	msg.subject,
	  1	msg.id AS msg_id,
	  2	msg.post_stamp,
	  3	users.id AS user_id,
	  4	users.alias
	  5	forum.cat_id,
	  6	forum.forum_icon
	  7	forum.id
	  8	forum.last_post_id
	  9	forum.moderators
	  10	forum.name
	  11	forum.descr
	  12	forum.url_redirect
	  13	forum.post_count
	  14	forum.thread_count
	  15	forum_read.last_view
	  16	is_moderator
	  17	read perm
	  18	is the category using compact view
	*/
	$c = uq('SELECT
				m.subject, m.id, m.post_stamp,
				u.id, u.alias,
				f.cat_id, f.forum_icon, f.id, f.last_post_id, f.moderators, f.name, f.descr, f.url_redirect, f.post_count, f.thread_count,
				'. (_uid ? 'fr.last_view, mo.id, COALESCE(g2.group_cache_opt, g1.group_cache_opt) AS group_cache_opt' : '0,0,g1.group_cache_opt') .',
				c.cat_opt
			FROM fud30_fc_view v
			INNER JOIN fud30_cat c ON c.id=v.c
			INNER JOIN fud30_forum f ON f.id=v.f
			INNER JOIN fud30_group_cache g1 ON g1.user_id='. (_uid ? 2147483647 : 0) .' AND g1.resource_id=f.id
			LEFT JOIN fud30_msg m ON f.last_post_id=m.id
			LEFT JOIN fud30_users u ON u.id=m.poster_id '.
			(_uid ? ' LEFT JOIN fud30_forum_read fr ON fr.forum_id=f.id AND fr.user_id='. _uid .' LEFT JOIN fud30_mod mo ON mo.user_id='. _uid .' AND mo.forum_id=f.id LEFT JOIN fud30_group_cache g2 ON g2.user_id='. _uid .' AND g2.resource_id=f.id' : '').
			' WHERE f.parent = '. $frm_id .
			((!$is_a || $cat_id) ?  ' AND ' : '') .
			($is_a ? '' : (_uid ? ' (mo.id IS NOT NULL OR ('. q_bitand('COALESCE(g2.group_cache_opt, g1.group_cache_opt)', 1) .' > 0))' : ' ('. q_bitand('g1.group_cache_opt', 1) .' > 0)')) .
			($cat_id ? ($is_a ? '' : ' AND ') .' v.c IN('. implode(',', ($cf = $cidxc[$cat_id][5])) .') ' : '') .' ORDER BY v.id');

	$post_count = $thread_count = $last_msg_id = $cat = 0;
	while ($r = db_rowarr($c)) {
		/* Increase thread & post count. */
		$post_count += $r[13];
		$thread_count += $r[14];

		$cid = (int) $r[5];

		if ($cat != $cid && !$frm_id) {
			if ($cbuf) { /* If previous category was using compact view, print forum row. */
				if (empty($collapse[$i[4]])) { /* Only show if parent is not collapsed as well. */
					$forum_list_table_data .= '<tr class="RowStyleB">
	<td colspan="6">Beschikbare forums:'.$cbuf.'</td>
</tr>';
				}
				$cbuf = '';
			}

			while (list($k, $i) = each($cidxc)) {
				/* 2nd check ensures that we don't end up displaying categories without any children. */ 
				if (($cat_id && !isset($cf[$k])) || ($cid != $k && $i[4] >= $cidxc[$cid][4])) {
					continue;
				}

				/* If parent category is collapsed, hide child category. */
				if ($i[4] && !empty($collapse[$i[4]])) {
					$collapse[$k] = 1;
				}

				if ($i[3] & 1 && $k != $cat_id && !($i[3] & 4)) {
					if (!isset($collapse[$k])) {
						$collapse[$k] = !($i[3] & 2);
					}
					$forum_list_table_data .= '<tr id="c'.$r[5].'" style="display: '.(empty($collapse[$i[4]]) ? 'table-row' : 'none' )  .';">
	<td class="CatDesc '.(empty($collapse[$cid]) ? 'expanded' : 'collapsed' )  .'" colspan="5" style="padding-left: '.($i[0] ? $i[0] * 20 : '0').'px;">
		<a href="index.php?t=index&amp;cat='.$k.'&amp;'._rsid.'" class="CatLink">'.$i[1].'</a> '.$i[2].'
	</td>
	<td class="CatDesc">
	'.(key($cidxc) ? '<a href="javascript://" onclick=\'nextCat("c'.$k.'")\'><img src="theme/default/images/down'.img_ext.'" alt="" border="0" style="vertical-align: top; float: right;" /></a>' : '' )  .'
	'.($cat ? '<a href="javascript://" onclick=\'prevCat("c'.$k.'")\'><img src="theme/default/images/up'.img_ext.'" border="0" alt="" style="vertical-align: top; float: right;" /></a>' : '' )  .'
</td>
</tr>';
				} else {
					if ($i[3] & 4) {
						++$i[0];
					}
					$forum_list_table_data .= '<tr id="c'.$r[5].'" style="display: '.(empty($collapse[$i[4]]) ? 'table-row' : 'none' )  .';">
	<td class="CatDesc CatLockPad" colspan="5" style="padding-left: '.($i[0] ? $i[0] * 20 : '0').'px;">
		<span class="CatLockedName"><a href="index.php?t=index&amp;cat='.$k.'&amp;'._rsid.'" class="CatLink">'.$i[1].'</a></span> '.$i[2].'
	</td>
	<td class="CatDesc">
	'.(key($cidxc) ? '<a href="javascript://" onclick=\'nextCat("c'.$k.'")\'><img src="theme/default/images/down'.img_ext.'" alt="" border="0" style="vertical-align: top; float: right;" /></a>' : '' )  .'
	'.($cat ? '<a href="javascript://" onclick=\'prevCat("c'.$k.'")\'><img src="theme/default/images/up'.img_ext.'" border="0" alt="" style="vertical-align: top; float: right;" /></a>' : '' )  .'
</td>
</tr>';
				}
			
				if ($k == $cid) {
					break;
				}
			}
			$cat = $cid;
		}

		/* Compact category view (ignore when expanded). */
		if ($r[18] & 4 && $cat_id != $cid) {
			$cbuf .= '&nbsp; '.(_uid && $r[15] < $r[2] && $usr->last_read < $r[2] ? '**' : '' )  .'
<a href="'.(empty($r[12]) ? 'index.php?t='.t_thread_view.'&amp;frm_id='.$r[7].'&amp;'._rsid : $r[12] )  .'">'.$r[10].'</a>';
			continue;
		}

		/* Visible forum with no 'read' permission. */
		if (!($r[17] & 2) && !$is_a && !$r[16]) {
			$forum_list_table_data .= '<tr style="display: '.(empty($collapse[$cid]) ? 'table-row' : 'none' )  .'" class="child-c'.$r[5].'">
	<td class="RowStyleA" colspan="6">'.$r[10].($r[11] ? '<br />'.$r[11] : '').'</td>
</tr>';
			continue;
		}

		/* Code to determine the last post id for 'latest' forum message. */
		if ($r[8] > $last_msg_id) {
			$last_msg_id = $r[8];
		}

		if (!_uid) { /* Anon user. */
			$forum_read_indicator = '<img title="Alleen geregistreerde forumleden kunnen gelezen en ongelezen berichten bijhouden" src="theme/default/images/existing_content'.img_ext.'" alt="Alleen geregistreerde forumleden kunnen gelezen en ongelezen berichten bijhouden" />';
		} else if ($r[15] < $r[2] && $usr->last_read < $r[2]) {
			$forum_read_indicator = '<img title="Nieuwe berichten" src="theme/default/images/new_content'.img_ext.'" alt="Nieuwe berichten" />';
		} else {
			$forum_read_indicator = '<img title="Geen nieuwe berichten" src="theme/default/images/existing_content'.img_ext.'" alt="Geen nieuwe berichten" />';
		}

		if ($r[9] && ($mods = unserialize($r[9]))) {
			$moderators = '';	// List of forum moderators.
			$modcount = 0;		// Use singular or plural message form.

			foreach($mods as $k => $v) {
				$moderators .= '<a href="index.php?t=usrinfo&amp;id='.$k.'&amp;'._rsid.'">'.$v.'</a> &nbsp;';
				$modcount++;
			}
			$moderators = '<div class="TopBy"><b>'.convertPlural($modcount, array('Moderator','Moderatoren')).':</b> '.$moderators.'</div>';
		} else {
			$moderators = '&nbsp;';
		}

		$forum_list_table_data .= '<tr style="display: '.(empty($collapse[$cid]) ? 'table-row' : 'none' )  .'" class="row child-c'.$r[5].'">
	<td class="RowStyleA wo">'.($r[6] ? '<img src="images/forum_icons/'.$r[6].'" alt="Forumicoon" />' : '&nbsp;' ) .'</td>
	<td class="RowStyleB ac wo">'.(empty($r[12]) ? $forum_read_indicator : '<img title="Doorverwijzing" src="theme/default/images/moved'.img_ext.'" alt="" />' )  .'</td>
	<td class="RowStyleA wa"><a href="'.(empty($r[12]) ? 'index.php?t='.t_thread_view.'&amp;frm_id='.$r[7].'&amp;'._rsid : $r[12] )  .'" class="big">'.$r[10].'</a>'.($r[11] ? '<br />'.$r[11] : '').$moderators.'</td>
	<td class="RowStyleB ac">'.(empty($r[12]) ? $r[13] : '--' )  .'</td>
	<td class="RowStyleB ac">'.(empty($r[12]) ? $r[14] : '--' )  .'</td>
	<td class="RowStyleA ac nw">'.(empty($r[12]) ? ($r[8] ? '<span class="DateText">'.strftime('%a, %d %B %Y', $r[2]).'</span><br />Door: '.($r[3] ? '<a href="index.php?t=usrinfo&amp;id='.$r[3].'&amp;'._rsid.'">'.$r[4].'</a>' : $GLOBALS['ANON_NICK'] ) .' <a href="index.php?t='.d_thread_view.'&amp;goto='.$r[8].'&amp;'._rsid.'#msg_'.$r[8].'"><img title="'.$r[0].'" src="theme/default/images/goto.gif" alt="'.$r[0].'" /></a>' : 'n.v.t.' )  : '--' )  .'</td>
</tr>';
	}
	unset($c);

	if ($cbuf) { /* If previous category was using compact view, print forum row. */
		$forum_list_table_data .= '<tr class="RowStyleB">
	<td colspan="6">Beschikbare forums:'.$cbuf.'</td>
</tr>';
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

	$RSS = ($FUD_OPT_2 & 1048576 ? '<link rel="alternate" type="application/rss+xml" title="Dit forum syndiceren (XML)" href="'.$GLOBALS['WWW_ROOT'].'feed.php?mode=m&amp;l=1&amp;basic=1" />
' : '' )  ;
	ses_update_status($usr->sid, 'Door de <a href="index.php?t=index">forumlijst</a> aan het bladeren');

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
	}if (!isset($th)) {
	$th = 0;
}
if (!isset($frm->id)) {
	$frm = new stdClass();	// Initialize to prevent 'strict standards' notice.
	$frm->id = 0;
}

	$TITLE_EXTRA = ': Welkom bij het forum';

// @TODO: Move to seperate file.
	/* Handling of announcements. */
	include $GLOBALS['FORUM_SETTINGS_PATH'] .'announce_cache';
	$announcements = '';
	foreach ($announce_cache as $a_id => $a) {
		if ($a['start'] <= __request_timestamp__ && $a['end'] >= __request_timestamp__) {
			$announce_subj = $a['subject'];
			$announce_body = $a['text'];
			$announcements .= '<fieldset class="AnnText">
	<legend class="AnnSubjText">'.$announce_subj.'</legend>
	'.$announce_body.'
</fieldset>';
		}
	}

function &rebuild_stats_cache($last_msg_id)
{
	$tm_expire = __request_timestamp__ - ($GLOBALS['LOGEDIN_TIMEOUT'] * 60);

	$obj = new stdClass();	// Initialize to prevent 'strict standards' notice.
	list($obj->last_user_id, $obj->user_count) = db_saq('SELECT MAX(id), count(*)-1 FROM fud30_users');

	$obj->online_users_anon	= q_singleval('SELECT count(*) FROM fud30_ses s WHERE time_sec>'. $tm_expire .' AND user_id>2000000000');
	$obj->online_users_hidden = q_singleval('SELECT count(*) FROM fud30_ses s INNER JOIN fud30_users u ON u.id=s.user_id WHERE s.time_sec>'. $tm_expire .' AND '. q_bitand('u.users_opt', 32768) .'>0');
	$obj->online_users_reg = q_singleval('SELECT count(*) FROM fud30_ses s INNER JOIN fud30_users u ON u.id=s.user_id WHERE s.time_sec>'. $tm_expire .' AND '. q_bitand('u.users_opt', 32768) .'=0');
	$c = uq(q_limit('SELECT u.id, u.alias, u.users_opt, u.custom_color FROM fud30_ses s INNER JOIN fud30_users u ON u.id=s.user_id WHERE s.time_sec>'. $tm_expire .' AND '. q_bitand('u.users_opt', 32768) .'=0 ORDER BY s.time_sec DESC', $GLOBALS['MAX_LOGGEDIN_USERS']));
	$obj->online_users_text = array();
	while ($r = db_rowarr($c)) {
		$obj->online_users_text[$r[0]] = draw_user_link($r[1], $r[2], $r[3]);
	}
	unset($c);

	q('UPDATE fud30_stats_cache SET
		cache_age='. __request_timestamp__ .',
		last_user_id='. (int)$obj->last_user_id .',
		user_count='. (int)$obj->user_count .',
		online_users_anon='. (int)$obj->online_users_anon .',
		online_users_hidden='. (int)$obj->online_users_hidden .',
		online_users_reg='. (int)$obj->online_users_reg .',
		online_users_text='. ssn(serialize($obj->online_users_text)));

	$obj->last_user_alias = q_singleval('SELECT alias FROM fud30_users WHERE id='. $obj->last_user_id);
	$obj->last_msg_subject = q_singleval('SELECT subject FROM fud30_msg WHERE id='. $last_msg_id);

	list($obj->most_online,$obj->most_online_time) = db_saq('SELECT most_online, most_online_time FROM fud30_stats_cache');
	/* Update most online users stats if needed. */
	if (($obj->online_users_reg + $obj->online_users_hidden + $obj->online_users_anon) > $obj->most_online) {
		$obj->most_online = $obj->online_users_reg + $obj->online_users_hidden + $obj->online_users_anon;
		$obj->most_online_time = __request_timestamp__;
		q('UPDATE fud30_stats_cache SET most_online='. $obj->most_online .', most_online_time='. $obj->most_online_time);
	} else if (!$obj->most_online_time) {
		$obj->most_online_time = __request_timestamp__;
	}

	return $obj;
}

$logedin = $forum_info = '';

if ($FUD_OPT_1 & 1073741824 || $FUD_OPT_2 & 16) {
	if (!($st_obj = db_sab('SELECT sc.*,m.subject AS last_msg_subject, u.alias AS last_user_alias FROM fud30_stats_cache sc INNER JOIN fud30_users u ON u.id=sc.last_user_id LEFT JOIN fud30_msg m ON m.id='. $last_msg_id .' WHERE sc.cache_age>'. (__request_timestamp__ - $STATS_CACHE_AGE)))) {
		$st_obj = rebuild_stats_cache($last_msg_id);
	} else if ($st_obj->online_users_text && (_uid || !($FUD_OPT_3 & 262144))) {
		$st_obj->online_users_text = unserialize($st_obj->online_users_text);
	}

	if (!$st_obj->most_online_time) {
		$st_obj->most_online_time = __request_timestamp__;
	}

	if ($FUD_OPT_1 & 1073741824 && (_uid || !($FUD_OPT_3 & 262144))) {
		if (!empty($st_obj->online_users_text)) {
			foreach($st_obj->online_users_text as $k => $v) {
				$logedin .= '<a href="index.php?t=usrinfo&amp;id='.$k.'&amp;'._rsid.'">'.$v.'</a> ';
			}
		}
		$logedin = '<tr>
	<th class="wa">Aangemelde gebruikers '.(($FUD_OPT_1 & 536870912) ? (_uid || !($FUD_OPT_3 & 131072) ? '[ <a href="index.php?t=actions&amp;'._rsid.'" class="thLnk" rel="nofollow">Laat zien waar andere gebruikers mee bezig zijn</a> ]' : '' ) .'
'.(_uid || !($FUD_OPT_3 & 262144) ? '[ <a href="index.php?t=online_today&amp;'._rsid.'" class="thLnk" rel="nofollow">Bezoekers van vandaag</a> ]' : '' )  : '' ) .'</th>
</tr>
<tr>
	<td class="RowStyleA">
		<span class="SmallText">Er '.convertPlural($st_obj->online_users_reg, array('is <b>'.$st_obj->online_users_reg.' lid','zijn <b>'.$st_obj->online_users_reg.' leden')).'</b>, <b>'.convertPlural($st_obj->online_users_hidden, array(''.$st_obj->online_users_hidden.' onzichtbaar lid',''.$st_obj->online_users_hidden.' onzichtbare leden')).'</b> en <b>'.convertPlural($st_obj->online_users_anon, array(''.$st_obj->online_users_anon.' gast',''.$st_obj->online_users_anon.' gasten')).'</b> die dit forum bezoeken.&nbsp;&nbsp;&nbsp;
		<span class="adminColor">[Beheerder]</span>&nbsp;&nbsp;
		<span class="modsColor">[Moderator]</span></span><br />
		'.$logedin.'
	</td>
</tr>';
	}
	if ($FUD_OPT_2 & 16) {
		$forum_info = '<tr>
	<td class="RowStyleB SmallText">
		Onze gebruikers hebben in totaal <b>'.convertPlural($post_count, array(''.$post_count.' bericht',''.$post_count.' berichten')).'</b> geplaatst in <b>'.convertPlural($thread_count, array(''.$thread_count.' onderwerp',''.$thread_count.' onderwerpen')).'</b>.<br />
		Het hoogste aantal actieve gebruikers was <b>'.$st_obj->most_online.'</b> op <b>'.strftime('%a, %d %B %Y %H:%M', $st_obj->most_online_time).'</b><br />
		We hebben <b>'.$st_obj->user_count.'</b> geregistreerde '.convertPlural($st_obj->user_count, array('gebruiker','gebruikers')).'.<br />
		Het nieuwste lid is <a href="index.php?t=usrinfo&amp;id='.$st_obj->last_user_id.'&amp;'._rsid.'"><b>'.$st_obj->last_user_alias.'</b></a>
		'.($last_msg_id ? '<br />Laatste bericht op het forum: <a href="index.php?t='.d_thread_view.'&amp;goto='.$last_msg_id.'&amp;'._rsid.'#msg_'.$last_msg_id.'"><b>'.$st_obj->last_msg_subject.'</b></a>' : '' ) .'
	</td>
</tr>';
	}
}if ($FUD_OPT_2 & 2 || $is_a) {	// PUBLIC_STATS is enabled or Admin user.
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
<?php echo (_uid ? '<span class="GenText">Welkom <b>'.$usr->alias.'</b>. Uw laatste bezoek was op '.strftime('%a, %d %B %Y %H:%M', $usr->last_visit).'</span><br />' : ''); ?>
<span class="GenText fb">Weergeven:</span>
<a href="index.php?t=selmsg&amp;date=today&amp;<?php echo _rsid; ?>&amp;frm_id=<?php echo (isset($frm->forum_id) ? $frm->forum_id.'' : $frm->id.'' )  .'&amp;th='.$th.'" title="Alle berichten van vandaag weergeven" rel="nofollow">Berichten van vandaag</a>
'.(_uid ? '<b>::</b> <a href="index.php?t=selmsg&amp;unread=1&amp;'._rsid.'&amp;frm_id='.(isset($frm->forum_id) ? $frm->forum_id.'' : $frm->id.'' )  .'" title="Alle ongelezen berichten weergeven" rel="nofollow">Ongelezen berichten</a>&nbsp;' : ''); ?>
<?php echo (!$th ? '<b>::</b> <a href="index.php?t=selmsg&amp;reply_count=0&amp;'._rsid.'&amp;frm_id='.(isset($frm->forum_id) ? $frm->forum_id.'' : $frm->id.'' )  .'" title="Alle berichten waarop geen antwoorden zijn gegeven weergeven" rel="nofollow">Onbeantwoorde berichten</a>&nbsp;' : ''); ?>
<b>::</b> <a href="index.php?t=polllist&amp;<?php echo _rsid; ?>" rel="nofollow">Stemmingen weergeven</a>
<b>::</b> <a href="index.php?t=mnav&amp;<?php echo _rsid; ?>" rel="nofollow">Navigatie</a><?php echo $admin_cp; ?>
<?php echo $cat_path; ?>

<?php echo $announcements; ?>
<?php echo (!$frm_id || ($frm_id && !empty($forum_list_table_data)) ? '
<table cellspacing="1" cellpadding="2" class="ContentTable">
<tr>
	<th colspan="3" class="wa">Forum</th>
	<th class="nw">Berichten</th>
	<th class="nw">Onderwerpen</th>
	<th class="nw">Laatste bericht</th>
</tr>
	'.$forum_list_table_data.'
</table>
' : ''); ?>
<?php echo (_uid ? '<div class="SmallText ar">[ <a href="index.php?t=markread&amp;'._rsid.'&amp;SQ='.$GLOBALS['sq'].'&amp;cat='.$cat_id.'" title="Alle berichten die u nog niet gelezen hebt worden als gelezen gemarkeerd">Alle berichten als gelezen markeren</a> ]
'.($FUD_OPT_2 & 1048576 ? '[ <a href="feed.php?mode=m&amp;l=1&amp;basic=1"><img src="theme/default/images/rss.gif" title="Dit forum syndiceren (XML)" alt="Dit forum syndiceren (XML)"/></a> ]' : '' )  .'
</div>' : ''); ?>
<?php echo (__fud_real_user__ ? '' : '<div class="fr">
<form id="quick_login_form" method="post" action="index.php?t=login"'.($GLOBALS['FUD_OPT_3'] & 256 ? ' autocomplete="off"' : '').'>
'._hs.'
<table border="0" cellspacing="0" cellpadding="3">
<tr class="SmallText">
	<td>
		<label>Gebruikernaam:<br />
		<input class="SmallText" type="text" name="quick_login" size="18" /></label>
	</td>
	<td>
		<label>Wachtwoord:<br />
		<input class="SmallText" type="password" name="quick_password" size="18" /></label>
	</td>
	'.($FUD_OPT_1 & 128 ? '<td>
	&nbsp;<br />
	<label><input type="checkbox" checked="checked" name="quick_use_cookies" value="1" /> Cookies gebruiken?</label>
</td>' : '' )  .'
	<td>
		&nbsp;<br />
		<input type="submit" class="button" name="quick_login_submit" value="Gebruiker:" />
	</td>
</tr>
</table>
</form>
</div>'); ?>
<?php echo ($logedin || $forum_info ? '<br />
<table cellspacing="1" cellpadding="2" class="ContentTable">
	'.$logedin.'
	'.$forum_info.'
</table>' : ''); ?>
<br /><fieldset>
<legend>Legenda</legend>
<img src="theme/default/images/new_content<?php echo img_ext; ?>" alt="Nieuwe berichten sinds laatste bezoek" /> Nieuwe berichten sinds laatste bezoek&nbsp;&nbsp;
<img src="theme/default/images/existing_content<?php echo img_ext; ?>" alt="Geen nieuwe berichten sinds laatste bezoek" /> Geen nieuwe berichten sinds laatste bezoek&nbsp;&nbsp;
<img src="theme/default/images/moved<?php echo img_ext; ?>" alt="Doorverwijzing" /> Doorverwijzing
</fieldset>
<div><br /></div>
<br /><div class="ac"><span class="curtime"><b>Huidige tijd:</b> <?php echo strftime('%a %b %#d %H:%M:%S %Z %Y', __request_timestamp__); ?></span></div>
<?php echo $page_stats; ?>
<script>
	min_max_cats("theme/default/images", "<?php echo img_ext; ?>", "Categorie minimaliseren", "Categorie maximaliseren", "<?php echo $usr->sq; ?>", "<?php echo s; ?>");
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
