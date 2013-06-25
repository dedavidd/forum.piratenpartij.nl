<?php
/**
* copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: ignore_list.php.t 4994 2010-09-02 17:33:29Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}function ignore_add($user_id, $ignore_id)
{
	q('INSERT INTO fud30_user_ignore (ignore_id, user_id) VALUES ('. $ignore_id .', '. $user_id .')');
	q('DELETE FROM fud30_buddy WHERE user_id='. $ignore_id .' AND bud_id='. $user_id);
	if (db_affected()) {
		fud_use('buddy.inc');
		buddy_rebuild_cache($ignore_id);
	}

	return ignore_rebuild_cache($user_id);
}

function ignore_delete($user_id, $ignore_id)
{
	q('DELETE FROM fud30_user_ignore WHERE user_id='. $user_id .' AND ignore_id='. $ignore_id);
	return ignore_rebuild_cache($user_id);
}

function ignore_rebuild_cache($uid)
{
	$arr = array();
	$q = uq('SELECT ignore_id FROM fud30_user_ignore WHERE user_id='. $uid);
	while ($ent = db_rowarr($q)) {
		$arr[$ent[0]] = 1;
	}
	unset($q);

	if ($arr) {
		q('UPDATE fud30_users SET ignore_list='. _esc(serialize($arr)) .' WHERE id='. $uid);
		return $arr;
	}
	q('UPDATE fud30_users SET ignore_list=NULL WHERE id='. $uid);
}function check_return($returnto)
{
	if ($GLOBALS['FUD_OPT_2'] & 32768 && !empty($_SERVER['PATH_INFO'])) {
		if (!$returnto || !strncmp($returnto, '/er/', 4)) {
			header('Location: '.$GLOBALS['WWW_ROOT'].'index.php/i/'. _rsidl);
		} else if ($returnto[0] == '/') { /* Unusual situation, path_info & normal themes are active. */
			header('Location: '.$GLOBALS['WWW_ROOT'].'index.php'. $returnto);
		} else {
			header('Location: '.$GLOBALS['WWW_ROOT'].'index.php?'. $returnto);
		}
	} else if (!$returnto || !strncmp($returnto, 't=error', 7)) {
		header('Location: '.$GLOBALS['WWW_ROOT'].'index.php?t=index&'. _rsidl);
	} else if (strpos($returnto, 'S=') === false && $GLOBALS['FUD_OPT_1'] & 128) {
		header('Location: '.$GLOBALS['WWW_ROOT'].'index.php?'. $returnto .'&S='. s);
	} else {
		header('Location: '.$GLOBALS['WWW_ROOT'].'index.php?'. $returnto);
	}
	exit;
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

	if (!_uid) {
		std_error('login');
	}

function ignore_alias_fetch($al, &$is_mod)
{
	if (!($tmp = db_saq('SELECT id, '. q_bitand('users_opt', 1048576) .' FROM fud30_users WHERE alias='. _esc(char_fix(htmlspecialchars($al)))))) {
		return;
	}
	$is_mod = $tmp[1];

	return $tmp[0];
}

	if (isset($_POST['add_login']) && is_string($_POST['add_login'])) {
		if (!($ignore_id = ignore_alias_fetch($_POST['add_login'], $is_mod))) {
			error_dialog('Gebruikers niet gevonden', 'De gebruiker die u aan uw negeerlijst probeerde toe te voegen is niet gevonden.');
		}
		if ($is_mod) {
			error_dialog('Informatie', 'U kunt deze gebruiker niet in uw negeerlijst opnemen');
		}
		if (!empty($usr->ignore_list)) {
			$usr->ignore_list = unserialize($usr->ignore_list);
		}
		if (!isset($usr->ignore_list[$ignore_id])) {
			ignore_add(_uid, $ignore_id);
		} else {
			error_dialog('Informatie', 'Deze gebruiker staat al op uw negeerlijst');
		}
	}

	/* Incomming from message display page (ignore link). */
	if (isset($_GET['add']) && ($_GET['add'] = (int)$_GET['add'])) {
		if (!sq_check(0, $usr->sq)) {
			check_return($usr->returnto);
		}

		if (!empty($usr->ignore_list)) {
			$usr->ignore_list = unserialize($usr->ignore_list);
		}

		if (($ignore_id = q_singleval('SELECT id FROM fud30_users WHERE id='. $_GET['add'] .' AND '. q_bitand('users_opt', 1048576) .'=0')) && !isset($usr->ignore_list[$ignore_id])) {
			ignore_add(_uid, $ignore_id);
		}
		check_return($usr->returnto);
	}

	/* Anon user hack. */
	if (isset($_GET['del']) && $_GET['del'] === '0') {
		$_GET['del'] = 1;
	}

	if (isset($_GET['del']) && ($_GET['del'] = (int)$_GET['del'])) {
		if (!sq_check(0, $usr->sq)) {
			check_return($usr->returnto);
		}

		ignore_delete(_uid, $_GET['del']);
		/* Needed for external links to this form. */
		if (isset($_GET['redr'])) {
			check_return($usr->returnto);
		}
	}

	ses_update_status($usr->sid, 'Eigen negeerlijst aan het bekijken');

/* Print number of unread private messages in User Control Panel. */
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

	$c = uq('SELECT ui.ignore_id, ui.id as ignoreent_id,
			u.id, u.alias AS login, u.join_date, u.posted_msg_count, u.home_page
		FROM fud30_user_ignore ui
		LEFT JOIN fud30_users u ON ui.ignore_id=u.id
		WHERE ui.user_id='. _uid);

	$ignore_list = '';
	if (($r = db_rowarr($c))) {
		do {
			$ignore_list .= $r[0] ? '<tr class="'.alt_var('ignore_alt','RowStyleA','RowStyleB').'">
	<td class="GenText wa"><a href="index.php?t=usrinfo&amp;id='.$r[2].'&amp;'._rsid.'">'.$r[3].'</a>&nbsp;<span class="SmallText">(<a href="index.php?t=ignore_list&amp;del='.$r[0].'&amp;'._rsid.'&amp;SQ='.$GLOBALS['sq'].'">verwijderen</a>)</span></td>
	<td class="ac">'.$r[5].'</td>
	<td class="ac nw">'.strftime('%a, %d %B %Y %H:%M', $r[4]).'</td>
	<td class="GenText nw"><a href="index.php?t=showposts&amp;'._rsid.'&amp;id='.$r[2].'"><img src="theme/default/images/show_posts.gif" alt="" /></a> '.($FUD_OPT_2 & 1073741824 ? '<a href="index.php?t=email&amp;toi='.$r[2].'&amp;'._rsid.'" rel="nofollow"><img src="theme/default/images/msg_email.gif" alt="" /></a>' : '' ) .($r[6] ? '<a href="'.$r[6].'"><img src="theme/default/images/homepage.gif" alt="" /></a>' : '' ) .'</td>
</tr>' : '<tr class="'.alt_var('ignore_alt','RowStyleA','RowStyleB').'">
	<td colspan="4" class="wa GenText"><span class="anon">'.$GLOBALS['ANON_NICK'].'</span>&nbsp;<span class="SmallText">(<a href="index.php?t=ignore_list&amp;del='.$r[1].'&amp;'._rsid.'">verwijderen</a>)</span></td>
</tr>';
		} while (($r = db_rowarr($c)));
		$ignore_list = '<table cellspacing="1" cellpadding="2" class="ContentTable">
<tr>
	<th>Genegeerde gebruikers</th>
	<th class="nw ac">Berichtenteller</th>
	<th class="nw ac">Geregistreerd op</th>
	<th class="nw ac">Handeling</th>
</tr>
'.$ignore_list.'
</table>';
	}
	unset($c);

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
<?php echo $tabs; ?>
<?php echo $ignore_list; ?>
<br /><br />
<form id="buddy_add" action="index.php?t=ignore_list" method="post"><?php echo _hs; ?><div class="ctb">
<table cellspacing="1" cellpadding="2" class="MiniTable">
<tr>
	<th class="nw">Gebruiker negeren</th>
</tr>
<tr class="RowStyleA">
	<td class="GenText nw Smalltext">
		Geef de gebruikersnaam op van de gebruiker die u wilt toevoegen.<?php echo (($FUD_OPT_1 & (8388608|4194304)) ? '<br />Of gebruik de optie <a href="javascript://" onclick="javascript: window_open(&#39;'.$GLOBALS['WWW_ROOT'].'index.php?t=pmuserloc&amp;'._rsid.'&amp;js_redr=buddy_add.add_login&amp;overwrite=1&#39;, &#39;user_list&#39;, 400,250);">Gebruiker zoeken</a> om een gebruiker te vinden.' : ''); ?><br /><br />
		<input type="text" name="add_login" id="add_login" tabindex="1" value="" maxlength="100" size="25" />
		<input tabindex="2" type="submit" class="button" name="submit" value="Toevoegen" />
	</td>
</tr>
</table></div></form>
<br /><div class="ac"><span class="curtime"><b>Huidige tijd:</b> <?php echo strftime('%a %b %#d %H:%M:%S %Z %Y', __request_timestamp__); ?></span></div>
<?php echo $page_stats; ?>
<script>
	document.forms['buddy_add'].add_login.focus();
</script>

<style>
	.ui-autocomplete-loading { background: white url("theme/default/images/ajax-loader.gif") right center no-repeat; }
</style>
<script>
	jQuery(function() {
		jQuery("#add_login").autocomplete({
			source: "index.php?t=autocomplete&lookup=alias", minLength: 1
		});
	});
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
