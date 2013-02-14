<?php
/******************************************************************************
* LogInOut.php                                                                *
*******************************************************************************
* SMF: Simple Machines Forum                                                  *
* Open-Source Project Inspired by Zef Hemel (zef@zefhemel.com)                *
* =========================================================================== *
* Software Version:           SMF 1.0.2                                       *
* Software by:                Simple Machines (http://www.simplemachines.org) *
* Copyright 2001-2005 by:     Lewis Media (http://www.lewismedia.com)         *
* Support, News, Updates at:  http://www.simplemachines.org                   *
*******************************************************************************
* This program is free software; you may redistribute it and/or modify it     *
* under the terms of the provided license as published by Lewis Media.        *
*                                                                             *
* This program is distributed in the hope that it is and will be useful,      *
* but WITHOUT ANY WARRANTIES; without even any implied warranty of            *
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                        *
*                                                                             *
* See the "license.txt" file for details of the Simple Machines license.      *
* The latest version can always be found at http://www.simplemachines.org.    *
******************************************************************************/
if (!defined('SMF'))
	die('Hacking attempt...');

/*	This file is concerned pretty entirely, as you see from its name, with
	logging in and out members, and the validation of that.  It contains:

	void Login()
		- shows a page for the user to type in their username and password.
		- caches the referring URL in $_SESSION['login_url'].
		- uses the Login template and language file with the login sub
		  template.
		- if you are using a wireless device, uses the protocol_login sub
		  template in the Wireless template.
		- accessed from ?action=login.

	void Login2()
		- actually logs you in and checks that login was successful.
		- employs protection against a specific IP or user trying to brute
		  force a login to an account.
		- on error, uses the same templates Login() uses.
		- upgrades password encryption on login, if necessary.
		- after successful login, redirects you to $_SESSION['login_url'].
		- accessed from ?action=login2, by forms.

	void Logout()
		- logs the current user out of their account.
		- requires that the session hash is sent as well, to prevent automatic
		  logouts by images or javascript.
		- redirects back to $_SESSION['logout_url'], if it exists.
		- accessed via ?action=logout;sc=...
*/

// Ask them for their login information.
function Login()
{
	global $txt, $context;

	// In wireless?  If so, use the correct sub template.
	if (WIRELESS)
		$context['sub_template'] = WIRELESS_PROTOCOL . '_login';
	// Otherwise, we need to load the Login template/language file.
	else
	{
		loadTemplate('Login');
		loadLanguage('Login');
		$context['sub_template'] = 'login';
	}

	// Get the template ready.... not really much else to do.
	$context['page_title'] = $txt[34];
	$context['default_username'] = &$_REQUEST['u'];
	$context['default_password'] = '';
	$context['never_expire'] = false;

	// Set the login URL - will be used when the login process is done.
	if (isset($_SESSION['old_url']) && (strstr($_SESSION['old_url'], 'board=') !== false || strstr($_SESSION['old_url'], 'topic=') !== false))
		$_SESSION['login_url'] = $_SESSION['old_url'];
	else
		unset($_SESSION['login_url']);
}

// Perform the actual logging-in.
function Login2()
{
	global $txt, $db_prefix, $scripturl, $user_info;
	global $cookiename, $maintenance, $ID_MEMBER;
	global $modSettings, $scripturl, $context, $sc, $sourcedir;

	// Load cookie authentication stuff.
	require_once($sourcedir . '/Subs-Auth.php');

	// Double check the cookie...
	if (isset($_GET['sa']) && $_GET['sa'] == 'check')
	{
		// Strike!  You're outta there!
		if ($_GET['member'] != $ID_MEMBER)
			fatal_lang_error('login_cookie_error', false);

		// Some whitelisting for login_url...
		if (empty($_SESSION['login_url']))
			redirectexit();
		else
		{
			// Best not to clutter the session data too much...
			$temp = $_SESSION['login_url'];
			unset($_SESSION['login_url']);

			redirectexit($temp, false);
		}
	}

	// Are you guessing with a script that doesn't keep the session id?
	spamProtection('login');

	// Been guessing a lot, haven't we?
	if (isset($_SESSION['failed_login']) && $_SESSION['failed_login'] >= $modSettings['failed_login_threshold'] * 3)
		fatal_lang_error('login_threshold_fail');

	// Set up the cookie length.  (if it's invalid, just fall through and use the default.)
	if (isset($_POST['cookieneverexp']) || (!empty($_POST['cookielength']) && $_POST['cookielength'] == -1))
		$modSettings['cookieTime'] = 3153600;
	elseif (!empty($_POST['cookielength']) && ($_POST['cookielength'] >= 1 || $_POST['cookielength'] <= 525600))
		$modSettings['cookieTime'] = (int) $_POST['cookielength'];

	// Set things up in case an error occurs.
	if (!empty($maintenance) || empty($modSettings['allow_guestAccess']))
		$context['sub_template'] = 'kick_guest';

	// Load the template stuff - wireless or normal.
	if (WIRELESS)
		$context['sub_template'] = WIRELESS_PROTOCOL . '_login';
	else
	{
		loadTemplate('Login');
		$context['sub_template'] = 'login';
	}
	loadLanguage('Login');

	// Set up the default/fallback stuff.
	$context['default_username'] = isset($_REQUEST['user']) ? htmlspecialchars(stripslashes($_REQUEST['user'])) : '';
	$context['default_password'] = '';
	$context['never_expire'] = $modSettings['cookieTime'] == 525600 || $modSettings['cookieTime'] == 3153600;
	$context['login_error'] = &$txt[106];
	$context['page_title'] = $txt[34];

	// You forgot to type your username, dummy!
	if (!isset($_REQUEST['user']) || $_REQUEST['user'] == '')
	{
		$context['login_error'] = &$txt[37];
		return;
	}

	// Hmm... maybe 'admin' will login with no password. Uhh... NO!
	if (!isset($_REQUEST['passwrd']) || $_REQUEST['passwrd'] == '')
	{
		$context['login_error'] = &$txt[38];
		return;
	}

	// No funky symbols either.
	if (preg_match('~[<>&"\'=\\\]~', $_REQUEST['user']) != 0)
	{
		$context['login_error'] = &$txt[240];
		return;
	}
	
	//Begin LDAP integration
	$ldap_admin_user = 'Admin';
	$ldap_server = 'ldap.company.com';
	$ldap_base_dn = 'o=directory';
	$ldap_uid_field = 'uid';
	$ldap_real_name = 'givenname';
	$ldap_email = 'mail';
	
	// Only special users get to authenticate the old way
	if ($_REQUEST[user] == $ldap_admin_user) {
		// Load the data up!
		$request = db_query("
			SELECT passwd, ID_MEMBER, ID_GROUP, lngfile, is_activated, emailAddress, additionalGroups, memberName, passwordSalt
			FROM {$db_prefix}members
			WHERE memberName = '$_REQUEST[user]'
			LIMIT 1", __FILE__, __LINE__);
		// Probably mistyped or their email, try it as an email address. (memberName first, though!)
		if (mysql_num_rows($request) == 0)
		{
			$request = db_query("
				SELECT passwd, ID_MEMBER, ID_GROUP, lngfile, is_activated, emailAddress, additionalGroups, memberName, passwordSalt
				FROM {$db_prefix}members
				WHERE emailAddress = '$_REQUEST[user]'
				LIMIT 1", __FILE__, __LINE__);
			// Let them try again, it didn't match anything...
			if (mysql_num_rows($request) == 0)
			{
				$context['login_error'] = &$txt[40];
				return;
			}
		}

		// Figure out the password, and load the settings.
		$user_settings = mysql_fetch_assoc($request);
		$md5_passwrd = md5_hmac($_REQUEST['passwrd'], strtolower($user_settings['memberName']));

		// Check if the account is activated...
		if (empty($user_settings['is_activated']))
		{
			$context['login_error'] = $txt['activate_not_completed1'] . ' <a href="' . $scripturl . '?action=activate;sa=resend;u=' . $user_settings['ID_MEMBER'] . '">' . $txt['activate_not_completed2'] . '</a>';
			log_error($txt['activate_not_completed1'] . ' - ' . $user_settings['memberName'], false);
			return;
		}

		// Old style encryption... now's the only time to fix it.
		if ($user_settings['passwd'] == crypt($_REQUEST['passwrd'], substr($_REQUEST['passwrd'], 0, 2)) || $user_settings['passwd'] == md5($_REQUEST['passwrd']))
		{
			updateMemberData($user_settings['ID_MEMBER'], array('passwd' => '\'' . $md5_passwrd . '\''));
			$user_settings['passwd'] = $md5_passwrd;
		}
		// What about if the user has come from vBulletin or Invision?  Let's welcome them with open arms \o/.
		elseif ($user_settings['passwordSalt'] != '' && ($user_settings['passwd'] == md5(md5($_REQUEST['passwrd']) . $user_settings['passwordSalt']) || $user_settings['passwd'] == md5(md5($user_settings['passwordSalt']) . md5($_REQUEST['passwrd']))))
		{
			// Get our new encryption in!
			updateMemberData($user_settings['ID_MEMBER'], array('passwd' => '\'' . $md5_passwrd . '\'', 'passwordSalt' => '\'\''));
			$user_settings['passwd'] = $md5_passwrd;
		}
		// Bad password!  Thought you could fool the database?!
		elseif ($user_settings['passwd'] != $md5_passwrd)
		{
			// They've messed up again - keep a count to see if they need a hand.
			if (isset($_SESSION['failed_login']))
				$_SESSION['failed_login']++;
			else
				$_SESSION['failed_login'] = 1;

			// Hmm... don't remember it, do you?  Here, try the password reminder ;).
			if ($_SESSION['failed_login'] >= $modSettings['failed_login_threshold'])
				redirectexit('action=reminder');
			// We'll give you another chance...
			else
			{
				$context['login_error'] = &$txt[39];
				log_error($txt[39] . ' - ' . $user_settings['memberName']);
				return;
			}
		}
		mysql_free_result($request);
	}	else {
		// If not the admin, authenticate via LDAP

		$ldap_connect = @ldap_connect($ldap_server);
		if (!$ldap_connect) {
			$context['login_error'] = 'LDAP could not connect';
			return;
		}

		$ldap_bind = ldap_bind($ldap_connect);
		if (!$ldap_bind){
			$context['login_error'] = 'LDAP could not bind to the server';
			return;
		}

		$ldap_search = @ldap_search($ldap_connect, $ldap_base_dn, $ldap_uid_field.'='.$_REQUEST[user], array($ldap_uid_field));
		$ldap_result = @ldap_first_entry($ldap_connect, $ldap_search);

		if (!$ldap_result) {
			$context['login_error'] = &$txt[40];
			return;
		}

		$ldap_fields = @ldap_get_attributes($ldap_connect, $ldap_result);
		$ldap_dn = @ldap_get_dn($ldap_connect, $ldap_result);
	
		if (is_array($ldap_fields) AND count($ldap_fields) > 1) {
			if (@ldap_bind($ldap_connect, $ldap_dn, $_REQUEST['passwrd'])) {
				$request = db_query("
					SELECT passwd, ID_MEMBER, ID_GROUP, lngfile, is_activated, emailAddress, additionalGroups, memberName, passwordSalt
					FROM {$db_prefix}members
					WHERE memberName = '$_REQUEST[user]'
					LIMIT 1", __FILE__, __LINE__);
				if (mysql_num_rows($request) == 0) {
					// User bound to LDAP OK but does not exist in SMF database - create
					
					$ldap_search = @ldap_search($ldap_connect, $ldap_base_dn, $ldap_uid_field.'='.$_REQUEST[user], array($ldap_real_name, $ldap_email));
					$ldap_result = @ldap_first_entry($ldap_connect, $ldap_search);
					$ldap_fields = @ldap_get_attributes($ldap_connect, $ldap_result);
					
					$register_vars = array(
						'memberName' => "'$_REQUEST[user]'",
						'emailAddress' => "'".$ldap_fields[$ldap_email]['0']."'",
						'passwd' => '\'' . md5_hmac($_REQUEST['passwrd'], strtolower($_REQUEST[user])) . '\'',
						'posts' => 0,
						'dateRegistered' => time(),
						'memberIP' => "'$user_info[ip]'",
						'is_activated' => 1,
						'validation_code' => "''",
						'realName' => "'".$ldap_fields[$ldap_real_name]['0']."'",
						'personalText' => '\'' . addslashes($modSettings['default_personalText']) . '\'',
						'im_email_notify' => 1,
						'ID_THEME' => 0,
						'ID_POST_GROUP' => 4,
					);

					db_query("
						INSERT INTO {$db_prefix}members
							(" . implode(', ', array_keys($register_vars)) . ")
						VALUES (" . implode(', ', $register_vars) . ')', __FILE__, __LINE__);
					$memberID = db_insert_id();
					updateStats('member');

					// If it's enabled, increase the registrations for today.
					trackStats(array('registers' => '+'));

					//Retry the query
					mysql_free_result($request);
					$request = db_query("
						SELECT passwd, ID_MEMBER, ID_GROUP, lngfile, is_activated, emailAddress, additionalGroups, memberName, passwordSalt
						FROM {$db_prefix}members
						WHERE memberName = '$_REQUEST[user]'
						LIMIT 1", __FILE__, __LINE__);
					if (mysql_num_rows($request) == 0) {
						$context['login_error'] = 'Failed to add LDAP user to the database';
						return;
					}
				}
				// LDAP user found in the database
				// Figure out the password, and load the settings.
				$user_settings = mysql_fetch_assoc($request);
				$md5_passwrd = md5_hmac($_REQUEST['passwrd'], strtolower($user_settings['memberName']));

				// Old style encryption... now's the only time to fix it.
				if ($user_settings['passwd'] == crypt($_REQUEST['passwrd'], substr($_REQUEST['passwrd'], 0, 2)) || $user_settings['passwd'] == md5($_REQUEST['passwrd']))
				{
					updateMemberData($user_settings['ID_MEMBER'], array('passwd' => '\'' . $md5_passwrd . '\''));
					$user_settings['passwd'] = $md5_passwrd;
				}
				// What about if the user has come from vBulletin or Invision?  Let's welcome them with open arms \o/.
				elseif ($user_settings['passwordSalt'] != '' && ($user_settings['passwd'] == md5(md5($_REQUEST['passwrd']) . $user_settings['passwordSalt']) || $user_settings['passwd'] == md5(md5($user_settings['passwordSalt']) . md5($_REQUEST['passwrd']))))
				{
					// Get our new encryption in!
					updateMemberData($user_settings['ID_MEMBER'], array('passwd' => '\'' . $md5_passwrd . '\'', 'passwordSalt' => '\'\''));
					$user_settings['passwd'] = $md5_passwrd;
				}
				// SMF's password doesn't match LDAP's password
				elseif ($user_settings['passwd'] != $md5_passwrd)
				{
					updateMemberData($user_settings['ID_MEMBER'], array('passwd' => '\'' . $md5_passwrd . '\'', 'passwordSalt' => '\'\''));
					$user_settings['passwd'] = $md5_passwrd;
				}
				mysql_free_result($request);
			} else {
				// LDAP says bad password
				// They've messed up again - keep a count to see if they need a hand.
				if (isset($_SESSION['failed_login']))
					$_SESSION['failed_login']++;
				else
					$_SESSION['failed_login'] = 1;

				// Hmm... don't remember it, do you?  Here, try the password reminder ;).
				if ($_SESSION['failed_login'] >= $modSettings['failed_login_threshold'])
					redirectexit('action=reminder');
				// We'll give you another chance...
				else
				{
					$context['login_error'] = &$txt[39];
					log_error($txt[39] . ' - ' . $user_settings['memberName']);
					return;
				}
			}
		}
	}

	// Get ready to set the cookie...
	$username = $user_settings['memberName'];
	$ID_MEMBER = $user_settings['ID_MEMBER'];

	// Bam!  Cookie set.  A session too, just incase.
	setLoginCookie(60 * $modSettings['cookieTime'], $user_settings['ID_MEMBER'], $md5_passwrd);

	// Reset the login threshold.
	if (isset($_SESSION['failed_login']))
		unset($_SESSION['failed_login']);

	$user_info['is_guest'] = false;
	$user_settings['additionalGroups'] = explode(',', $user_settings['additionalGroups']);
	$user_info['is_admin'] = $user_settings['ID_GROUP'] == 1 || in_array(1, $user_settings['additionalGroups']);

	// Are you banned?
	if (isset($_SESSION['ban']['last_checked']))
		unset($_SESSION['ban']['last_checked']);
	is_not_banned();

	// An administrator, set up the login so they don't have to type it again.
	if ($user_info['is_admin'])
		$_SESSION['admin_time'] = time();

	// Don't stick the language or theme after this point.
	unset($_SESSION['language']);
	unset($_SESSION['ID_THEME']);

	// You've logged in, haven't you?
	updateMemberData($ID_MEMBER, array('lastLogin' => time(), 'memberIP' => '\'' . $user_info['ip'] . '\''));

	// Get rid of the online entry for that old guest....
	db_query("
		DELETE FROM {$db_prefix}log_online
		WHERE session = 'ip$user_info[ip]'
		LIMIT 1", __FILE__, __LINE__);
	$_SESSION['log_time'] = 0;

	// Just log you back out if it's in maintenance mode and you AREN'T an admin.
	if (empty($maintenance) || allowedTo('admin_forum'))
		redirectexit('action=login2;sa=check;member=' . $ID_MEMBER, true, $context['server']['needs_login_fix']);
	else
		redirectexit('action=logout;sesc=' . $sc, true, $context['server']['needs_login_fix']);
}

// Log the user out.
function Logout()
{
	global $db_prefix, $sourcedir, $ID_MEMBER, $context;

	// Make sure they aren't being auto-logged out.
	checkSession('get');

	require_once($sourcedir . '/Subs-Auth.php');

	// If you log out, you aren't online anymore :P.
	db_query("
		DELETE FROM {$db_prefix}log_online
		WHERE ID_MEMBER = $ID_MEMBER
		LIMIT 1", __FILE__, __LINE__);
	$_SESSION['log_time'] = 0;

	// Empty the cookie! (set it in the past, and for ID_MEMBER = 0)
	setLoginCookie(-3600, 0);

	// Off to the merry board index we go!
	if (empty($_SESSION['logout_url']))
		redirectexit('', true, $context['server']['needs_login_fix']);
	else
	{
		$temp = $_SESSION['logout_url'];
		unset($_SESSION['logout_url']);

		redirectexit($temp, false, $context['server']['needs_login_fix']);
	}
}

?>