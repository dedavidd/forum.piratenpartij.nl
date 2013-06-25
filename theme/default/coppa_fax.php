<?php
/**
* copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: coppa_fax.php.t 4994 2010-09-02 17:33:29Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}

	/* this form is for printing, therefore it lacks any advanced layout */
	if (!__fud_real_user__) {
		if ($FUD_OPT_2 & 32768) {
			header('Location: '.$GLOBALS['WWW_ROOT'].'index.php/i/'. _rsidl);
		} else {
			header('Location: '.$GLOBALS['WWW_ROOT'].'index.php?t=index&'. _rsidl);
		}
		exit;
	}
	$name = q_singleval('SELECT name FROM fud30_users WHERE id='. __fud_real_user__);


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
<strong>Instructies voor ouder of voogd</strong><br /><br />
Druk deze pagina af en zend een fax naar:
<pre>
<?php echo @file_get_contents($FORUM_SETTINGS_PATH."coppa_maddress.msg"); ?>
</pre>
<table border="1" cellspacing="1" cellpadding="3">
<tr>
	<td colspan="2">Registratieformulier</td>
</tr>
<tr>
	<td>Gebruikersnaam</td>
	<td><?php echo $usr->login; ?></td>
</tr>
<tr>
	<td>Wachtwoord</td>
	<td>&lt;HIDDEN&gt;</td>
</tr>
<tr>
	<td>E-mail</td>
	<td><?php echo $usr->email; ?></td>
</tr>
<tr>
	<td>Naam</td>
	<td><?php echo $name; ?></td>
</tr>
<tr>
	<td colspan="2">
		Onderteken het onderstaande formulier en verzend het naar ons.<br />
		Ik heb de informatie die mijn kind me heeft bezorgd nagekeken en heb het privacybeleid van de website gelezen. Ik begrijp dat de profielinformatie gewijzigd kan worden met een wachtwoord. Ik begrijp dat ik kan verzoeken de registratie van dit profiel volledig van het forum te verwijderen.
	</td>
</tr>
<tr>
	<td>Teken hier indien u toestemming geeft</td>
	<td><u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u></td>
</tr>
<tr>
	<td>Teken hier indien u wil dat de gebruiker wordt verwijderd</td>
	<td><u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u></td>
</tr>
<tr>
	<td>Volledige naam van de ouder of voogd:</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td>Verwantschap met kind:</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td>Telefoonnummer:</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td>E-mailadres:</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td>Datum:</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td colspan="2">Neem bij vragen contact op met <a href="mailto:<?php echo $GLOBALS['ADMIN_EMAIL']; ?>"><?php echo $GLOBALS['ADMIN_EMAIL']; ?></a></td>
</tr>
</table>
</div>
</body></html>
