<?php
/**
* copyright            : (C) 2001-2012 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: karma_track.php.t 4898 2010-01-25 21:30:30Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

	define('plain_form', 1);

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}function logaction($user_id, $res, $res_id=0, $action=null)
{
	q('INSERT INTO fud30_action_log (logtime, logaction, user_id, a_res, a_res_id)
		VALUES('. __request_timestamp__ .', '. ssn($action) .', '. $user_id .', '. ssn($res) .', '. (int)$res_id .')');
}

	/* Only admins have access to this control panel. */
	if (!_uid) {
		std_error('login');
	} if (!($usr->users_opt & 1048576)) {
		std_error('access');
	}

	$msgid   = isset($_GET['msgid'])   ? (int)$_GET['msgid']   : 0;
	$karmaid = isset($_GET['karmaid']) ? (int)$_GET['karmaid'] : 0;
	if (!$msgid) {
		invl_inp_err();
	}

	$usrid = db_sab('SELECT poster_id FROM fud30_msg WHERE id = '. $msgid);
	if (!$usrid) {
		invl_inp_err();
	}

	/* delete rating */
	if ($karmaid && sq_check(0, $usr->sq) && $msgid) {
		q('DELETE FROM fud30_karma_rate_track WHERE msg_id='. $msgid .' AND id = '. $karmaid);
		$rt = db_saq('SELECT SUM(rating) FROM fud30_karma_rate_track WHERE poster_id='. $usrid->poster_id);
		q('UPDATE fud30_users SET karma='. (int)$rt[0] .' WHERE id='. $usrid->poster_id);

		logaction(_uid, 'DELKARMA', 0, 'removed karma of user '. $usrid->poster_id .' for message '. $msgid);
	}



	$c = uq('SELECT u.alias, k.rating, k.id, k.msg_id FROM fud30_karma_rate_track k INNER JOIN fud30_users u ON k.user_id = u.id WHERE k.poster_id = '. $usrid->poster_id);
	$table_data = '';
	while ($r = db_rowarr($c)) {
		$table_data .= '<tr>
	<td>'.$r[0].'</td>
	<td>'.$r[1].'</td>
	<td><a href="index.php?t=msg&amp;goto='.$r[3].'#msg_'.$r[3].'" target="_blank">'.$r[3].'</a></td>
	<td><a href="index.php?t=karma_track&amp;msgid='.$r[3].'&amp;karmaid='.$r[2].'&amp;'._rsid.'&amp;SQ='.$GLOBALS['sq'].'">Verwijderen</a></td>
</tr>';
	}
	unset($c);

?>
<!DOCTYPE html>
<html lang="nl" dir="ltr">
<head>
<meta charset="utf-8">
<title><?php echo $GLOBALS['FORUM_TITLE'].$TITLE_EXTRA; ?></title>
<base href="<?php echo $GLOBALS['WWW_ROOT']; ?>" />
<script src="js/jquery.js"></script>
<script src="js/ui/jquery-ui.js"></script>
<script src="js/lib.js"></script>
<link rel="stylesheet" href="theme/default/forum.css" />
</head>
<body>
<div class="content">
<table cellspacing="0" cellpadding="3" class="wa dashed">
<tr>
	<td class="small" colspan="4">Reputatie voor deze gebruiker beheren:</td>
</tr>
<tr>
	<td class="mvTc">Gebruiker</td>
	<td class="mvTc">Waardering</td>
	<td class="mvTc">Bericht</td>
	<td class="mvTc">Handeling</td>
</tr>
<?php echo $table_data; ?>
<tr>
	<td class="ac RowStyleC" colspan="4">[<a href="javascript://" onclick="window.close();">Venster sluiten</a>]</td>
</tr>
</table>
</div>
</body></html>
