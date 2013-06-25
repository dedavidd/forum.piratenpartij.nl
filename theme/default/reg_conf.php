<?php
/**
* copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: reg_conf.php.t 4994 2010-09-02 17:33:29Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}

/* If a registered user or anon user send back to the front page. */
if (!__fud_real_user__ || _uid) {
	if ($FUD_OPT_2 & 32768) {
		header('Location: '.$GLOBALS['WWW_ROOT'].'index.php/i/'. _rsidl);
	} else {
		header('Location: '.$GLOBALS['WWW_ROOT'].'index.php?t=index&'. _rsidl);
	}
	exit;
}

$msg = '';

if (!($usr->users_opt & 131072)) {
	$msg = '<b>E-mailbevestiging</b><br />Er is een e-mail naar u verzonden die een speciale URL bevat waarop u moet klikken om uw gebruiker te activeren. Als u deze e-mail niet ontvangt binnen enkele minuten, meld u zich dan aan en bevestig dat het opgegeven e-mailadres correct is. Na de bevestiging van uw gebruiker hebt u toegang hebben de functies die beperkt zijn tot bevestigde geregistreerde gebruikers.';
}
if ($usr->users_opt & 2097152) {
	if ($msg) {
		$msg .= ' <br /><br />';
	}
	$msg .= '<b>Gebruikersbevestiging</b><br />Voordat uw gebruiker geactiveerd wordt moet uw aanvraag goedgekeurd worden door een beheerder. Daarna ontvangt u een e-mail die aangeeft dat uw gebruikersaanvraag bevestigd is. Voor die tijd kunt u zich al aanmelden, maar hebt u nog geen toegang tot functies die beperkt zijn tot goedgekeurde gebruikers.';
}

$TITLE_EXTRA = ': Registratiebevestiging';
/* Print number of unread private messages in User Control Panel. */
	if (__fud_real_user__ && $FUD_OPT_1 & 1024) {	// PM_ENABLED
		$c = q_singleval('SELECT count(*) FROM fud30_pmsg WHERE duser_id='. _uid .' AND fldr=1 AND read_stamp=0');
		$ucp_private_msg = $c ? '<li><a href="index.php?t=pmsg&amp;'._rsid.'" title="Privéberichten"><img src="theme/default/images/top_pm'.img_ext.'" alt="" /> U hebt <span class="GenTextRed">('.$c.')</span> ongelezen '.convertPlural($c, array('privébericht','privéberichten')).'</a></li>' : '<li><a href="index.php?t=pmsg&amp;'._rsid.'" title="Privéberichten"><img src="theme/default/images/top_pm'.img_ext.'" alt="" /> Privéberichten</a></li>';
	} else {
		$ucp_private_msg = '';
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
<div class="ctb">
<table class="regConf" cellspacing="2" cellpadding="2">
<tr>
	<th>Registratiebevestiging</th>
</tr>
<tr class="RowStyleA">
	<td><?php echo $msg; ?></td>
</tr>
<tr class="RowStyleC">
	<td class="ac">
		<form method="get" action="index.php?t=index&amp;<?php echo _rsid; ?>">
			<input type="submit" name="rc" value="Afgerond" />
		</form>
	</td>
</tr>
</table>
</div>
<br /><div class="ac"><span class="curtime"><b>Huidige tijd:</b> <?php echo strftime('%a %b %#d %H:%M:%S %Z %Y', __request_timestamp__); ?></span></div>
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