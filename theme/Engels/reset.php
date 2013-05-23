<?php
/**
* copyright            : (C) 2001-2012 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: reset.php.t 5431 2012-03-03 08:14:35Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}function validate_email($email)
{
	$bits = explode('@', $email);
	if (count($bits) != 2) {
		return 1;
	}
	$doms = explode('.', $bits[1]);
	$last = array_pop($doms);

	// Validate domain extension 2-4 characters A-Z
	if (!preg_match('!^[A-Za-z]{2,4}$!', $last)) {
		return 1;
	}

	// (Sub)domain name 63 chars long max A-Za-z0-9_
	foreach ($doms as $v) {
		if (!$v || strlen($v) > 63 || !preg_match('!^[A-Za-z0-9_-]+$!', $v)) {
			return 1;
		}
	}

	// Now the hard part, validate the e-mail address itself.
	if (!$bits[0] || strlen($bits[0]) > 255 || !preg_match('!^[-A-Za-z0-9_.+{}~\']+$!', $bits[0])) {
		return 1;
	}
}

function encode_subject($text)
{
	if (preg_match('![\x7f-\xff]!', $text)) {
		$text = '=?utf-8?B?'. base64_encode($text) .'?=';
	}

	return $text;
}

function send_email($from, $to, $subj, $body, $header='', $munge_newlines=1)
{
        if(strpos($to,"ldap.piratenpartij.nl")>1)
                {
                        $pos=strpos($to,"@");
                        $login=substr($to,0,$pos);
                        include('/srv/FUDforum/plugins/ldap/ldap.ini');
                        $connection = ldap_connect("ldaps://" . $ini['LDAP_HOST'] . ":" . $ini['LDAP_PORT']);
                        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
                        ldap_set_option($connection, LDAP_OPT_REFERRALS, 0);
                        $bind = ldap_bind($connection, $ini['LDAP_PROXY_DN'], $ini['LDAP_PROXY_DN_PASS']);
                        $search = ldap_search($connection, $ini['LDAP_DN'], $ini['LDAP_UID'] .'='. $login);
                        $count=ldap_count_entries($connection,$search);
                        if($count!=1){echo('Could not find ldap user!'.$login.$count);}
                        $info = ldap_get_entries($connection, $search);
                        $mail= $info[0]['mail'][0];
                        $to="$mail";
                }
	if (empty($to)) {
		return 0;
	}

	/* HTML entities check. */
	if (strpos($subj, '&') !== false) {
		$subj = html_entity_decode($subj);
	}

	if ($header) {
		$header = "\n" . str_replace("\r", '', $header);
	}
	$extra_header = '';
	if (strpos($header, 'MIME-Version') === false) {
		$extra_header = "\nMIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 8bit". $header;
	}
	$header = 'From: '. $from ."\nErrors-To: ". $from ."\nReturn-Path: ". $from ."\nX-Mailer: FUDforum v". $GLOBALS['FORUM_VERSION']. $extra_header. $header;

	$body = str_replace("\r", '', $body);
	if ($munge_newlines) {
		$body = str_replace('\n', "\n", $body);
	}
	$subj = encode_subject($subj);

	// Call PRE mail plugins.
	if (defined('plugins')) {
		list($to, $subj, $body, $header) = plugin_call_hook('PRE_MAIL', array($to, $subj, $body, $header));
	}

	if (defined('fud_debug')) {
		if (!function_exists('logaction')) {
			fud_use('logaction.inc');
		}
		logaction(_uid, 'SEND EMAIL', 0, 'To=['. implode(',', (array)$to) .']<br />Subject=['. $subj .']<br />Headers=['. str_replace("\n", '<br />', htmlentities($header)) .']<br />Message=['. $body .']');
	}

	if ($GLOBALS['FUD_OPT_1'] & 512) {
		if (!class_exists('fud_smtp')) {
			fud_use('smtp.inc');
		}
		$smtp = new fud_smtp;
		$smtp->msg = str_replace(array('\n', "\n."), array("\n", "\n.."), $body);
		$smtp->subject = encode_subject($subj);
		$smtp->to = $to;
		$smtp->from = $from;
		$smtp->headers = $header;
		$smtp->send_smtp_email();
		return 1;
	}

	foreach ((array)$to as $email) {
		if (!@mail($email, $subj, $body, $header)) {
			fud_logerror('Your system didn\'t accept E-mail ['. $subj .'] to ['. $email .'] for delivery.', 'fud_errors', $header ."\n\n". $body);
			return -1;
		}
	}
	
	return 1;
}class fud_user
{
	var $id, $login, $alias, $passwd, $salt, $plaintext_passwd,
	    $name, $email, $location, $occupation, $interests, $topics_per_page,
	    $icq, $aim, $yahoo, $msnm, $jabber, $affero, $google, $skype, $twitter,
	    $avatar, $avatar_loc, $posts_ppg, $time_zone, $birthday, $home_page,
	    $sig, $bio, $posted_msg_count, $last_visit, $last_event, $conf_key,
	    $user_image, $join_date, $theme, $last_read,
	    $mod_list, $mod_cur, $level_id, $karma, $u_last_post_id, $users_opt, $cat_collapse_status,
	    $ignore_list, $buddy_list,
	    $custom_fields;
}

function make_alias($text)
{
	if (strlen($text) > $GLOBALS['MAX_LOGIN_SHOW']) {
		$text = substr($text, 0, $GLOBALS['MAX_LOGIN_SHOW']);
	}
	return char_fix(str_replace(array(']', '['), array('&#93;','&#91;'), htmlspecialchars($text)));
}

function generate_salt()
{
	return substr(md5(uniqid(mt_rand(), true)), 0, 9);
}

class fud_user_reg extends fud_user
{
	function html_fields()
	{
		foreach(array('name', 'location', 'occupation', 'interests', 'bio') as $v) {
			if ($this->{$v}) {
				$this->{$v} = char_fix(htmlspecialchars($this->$v));
			}
		}
	}

	/** Deprecated: Please use add(). */
	function add_user()
	{
		return $this->add();
	}

	/** Add a new user account. */
	function add()
	{
		// Track referer.
		if (isset($_COOKIE['frm_referer_id']) && (int)$_COOKIE['frm_referer_id']) {
			$ref_id = (int)$_COOKIE['frm_referer_id'];
		} else {
			$ref_id = 0;
		}

		// Geneate password & salt (if not supplied).
		if (empty($this->passwd) && empty($this->plaintext_passwd)) {
			$this->plaintext_passwd = substr(md5(get_random_value()), 0, 8);
		}
		if (!empty($this->plaintext_passwd)) {
			$this->salt  = generate_salt();
			$this->passwd = sha1($this->salt . sha1($this->plaintext_passwd));
		}

		$o2 =& $GLOBALS['FUD_OPT_2'];
		$this->alias = make_alias((!($o2 & 128) || !$this->alias) ? $this->login : $this->alias);

		/* This is used when utilities create users (aka nntp/mlist/xmlagg imports). */
		if ($this->users_opt == -1) {
			$this->users_opt = 4|16|128|256|512|2048|4096|8192|16384|131072|4194304;

			if (!($o2 & 4)) {	// Flat thread listing/Tree message listing.
				$this->users_opt ^= 128;	// Unset default_topic_view=MSG.
			}
			if (!($o2 & 8)) {	// Tree thread listing/Flat message listing.
				$this->users_opt ^= 256;	//  Unset default_message_view=MSG.
			}
			if ($o2 & 1) {	// Unset EMAIL_CONFIRMATION (no confirmation email now).
				$o2 ^= 1;
			}
			$registration_ip = '::1';
		} else {
			$registration_ip = get_ip();
		}

		/* No user options? Initialize to sensible values. */
		if (empty($this->users_opt)) {
			$this->users_opt = 2|4|16|32|64|128|256|512|2048|4096|8192|16384|131072|4194304;
		}

		if (empty($this->theme)) {
			$this->theme = q_singleval(q_limit('SELECT id FROM fud30_themes WHERE theme_opt>=2 AND '. q_bitand('theme_opt', 2) .' > 0', 1));
		}
		if (empty($this->topics_per_page)) {
			$this->topics_per_page = $GLOBALS['THREADS_PER_PAGE'];
		}
		if (empty($this->posts_ppg)) {
			$this->posts_ppg =& $GLOBALS['POSTS_PER_PAGE'];
		}
		if (empty($this->join_date)) {
			$this->join_date = __request_timestamp__;
		}
		if (empty($this->time_zone)) {
			$this->time_zone =& $GLOBALS['SERVER_TZ'];
		}

		if ($o2 & 1) {	// EMAIL_CONFIRMATION
			$this->conf_key = md5(implode('', (array)$this) . __request_timestamp__ . getmypid());
		} else {
			$this->conf_key = '';
			$this->users_opt |= 131072;
		}
		$this->icq = (int)$this->icq ? (int)$this->icq : 'NULL';

		$this->html_fields();

		$flag = ret_flag($registration_ip);

		$this->id = db_qid('INSERT INTO
			fud30_users (
				login,
				alias,
				passwd,
				salt,
				name,
				email,
				avatar, 
				avatar_loc,
				icq,
				aim,
				yahoo,
				msnm,
				jabber,
				affero,
				google,
				skype,
				twitter,
				posts_ppg,
				time_zone,
				birthday,
				last_visit,
				conf_key,
				user_image,
				join_date,
				location,
				theme,
				occupation,
				interests,
				referer_id,
				last_read,
				sig,
				home_page,
				bio,
				users_opt,
				registration_ip,
				topics_per_page,
				flag_cc,
				flag_country,
				custom_fields
			) VALUES (
				'. _esc($this->login) .',
				'. _esc($this->alias) .',
				\''. $this->passwd .'\',
				\''. $this->salt .'\',
				'. _esc($this->name) .',
				'. _esc($this->email) .',
				'. (int)$this->avatar .',
				'. ssn($this->avatar_loc) .',
				'. $this->icq .',
				'. ssn(urlencode($this->aim)) .',
				'. ssn(urlencode($this->yahoo)) .',
				'. ssn($this->msnm) .',
				'. ssn(htmlspecialchars($this->jabber)) .',
				'. ssn(urlencode($this->affero)) .',
				'. ssn($this->google) .',
				'. ssn(urlencode($this->skype)) .',
				'. ssn(urlencode($this->twitter)) .',
				'. (int)$this->posts_ppg .',
				'. _esc($this->time_zone) .',
				'. ssn($this->birthday) .',
				'. __request_timestamp__ .',
				\''. $this->conf_key .'\',
				'. ssn(htmlspecialchars($this->user_image)) .',
				'. $this->join_date .',
				'. ssn($this->location) .',
				'. (int)$this->theme .',
				'. ssn($this->occupation) .',
				'. ssn($this->interests) .',
				'. (int)$ref_id .',
				'. __request_timestamp__ .',
				'. ssn($this->sig) .',
				'. ssn(htmlspecialchars($this->home_page)) .',
				'. ssn($this->bio) .',
				'. $this->users_opt .',
				'. _esc($registration_ip) .',
				'. (int)$this->topics_per_page .',
				'. ssn($flag[0]) .',
				'. ssn($flag[1]) .',
				'. _esc($this->custom_fields) .'
			)
		');

		return $this->id;
	}

	/** Deprecated: Please use sync(). */
	function sync_user()
	{
		$this->sync();
	}

	/** Change a user account. */
	function sync()
	{
		if (!empty($this->plaintext_passwd)) {
			if (empty($this->salt)) {
				$this->salt = generate_salt();
			}
			$passwd = 'passwd=\''. sha1($this->salt . sha1($this->plaintext_passwd)) .'\', salt=\''. $this->salt .'\', ';
		} else {
			$passwd = '';
		}

		$this->alias = make_alias((!($GLOBALS['FUD_OPT_2'] & 128) || !$this->alias) ? $this->login : $this->alias);
		$this->icq = (int)$this->icq ? (int)$this->icq : 'NULL';

		$rb_mod_list = (!($this->users_opt & 524288) && ($is_mod = q_singleval('SELECT id FROM fud30_mod WHERE user_id='. $this->id)) && (q_singleval('SELECT alias FROM fud30_users WHERE id='. $this->id) == $this->alias));

		$this->html_fields();

		q('UPDATE fud30_users SET '. $passwd .'
			name='. _esc($this->name) .',
			alias='. _esc($this->alias) .',
			email='. _esc($this->email) .',
			icq='. $this->icq .',
			aim='. ssn(urlencode($this->aim)) .',
			yahoo='. ssn(urlencode($this->yahoo)) .',
			msnm='. ssn($this->msnm) .',
			jabber='. ssn(htmlspecialchars($this->jabber)) .',
			affero='. ssn(urlencode($this->affero)) .',
			google='. ssn($this->google) .',
			skype='. ssn(urlencode($this->skype)) .',
			twitter='. ssn(urlencode($this->twitter)) .',
			posts_ppg='. (int)$this->posts_ppg .',
			time_zone='. _esc($this->time_zone) .',
			birthday='. ssn($this->birthday) .',
			user_image='. ssn(htmlspecialchars($this->user_image)) .',
			location='. ssn($this->location) .',
			occupation='. ssn($this->occupation) .',
			interests='. ssn($this->interests) .',
			avatar='. (int)$this->avatar .',
			theme='. (int)$this->theme .',
			avatar_loc='. ssn($this->avatar_loc) .',
			sig='. ssn($this->sig) .',
			home_page='. ssn(htmlspecialchars($this->home_page)) .',
			bio='. ssn($this->bio) .',
			users_opt='. (int)$this->users_opt .',
			topics_per_page='. (int)$this->topics_per_page .',
			custom_fields='. _esc($this->custom_fields) .'
		WHERE id='. $this->id);

		if ($rb_mod_list) {
			rebuildmodlist();
		}
	}

	/** Delete a user account. */
	static function delete($id)
	{
		q('DELETE FROM fud30_users WHERE id = '. (int)$id);
	}
}

function get_id_by_email($email)
{
	return q_singleval('SELECT id FROM fud30_users WHERE email='. _esc($email));
}

function get_id_by_login($login)
{
	return q_singleval('SELECT id FROM fud30_users WHERE login='. _esc($login));
}

function usr_email_unconfirm($id)
{
	$conf_key = md5(__request_timestamp__ . $id . get_random_value());
	q('UPDATE fud30_users SET users_opt='. q_bitand('users_opt', ~131072) .', conf_key=\''. $conf_key .'\' WHERE id='. $id);

	return $conf_key;
}

function &usr_reg_get_full($id)
{
	if (($r = db_sab('SELECT * FROM fud30_users WHERE id='. $id))) {
		if (!extension_loaded('overload')) {
			$o = new fud_user_reg;
			foreach ($r as $k => $v) {
				$o->{$k} = $v;
			}
			$r = $o;
		} else {
			aggregate_methods($r, 'fud_user_reg');
		}
	}
	return $r;
}

function user_login($id, $cur_ses_id, $use_cookies)
{
	if (!$use_cookies && isset($_COOKIE[$GLOBALS['COOKIE_NAME']])) {
		/* Remove cookie so it does not confuse us. */
		setcookie($GLOBALS['COOKIE_NAME'], '', __request_timestamp__-100000, $GLOBALS['COOKIE_PATH'], $GLOBALS['COOKIE_DOMAIN']);
	}
	if ($GLOBALS['FUD_OPT_2'] & 256 && ($s = db_saq('SELECT ses_id, sys_id FROM fud30_ses WHERE user_id='.$id))) {
		if ($use_cookies) {
			setcookie($GLOBALS['COOKIE_NAME'], $s[0], __request_timestamp__+$GLOBALS['COOKIE_TIMEOUT'], $GLOBALS['COOKIE_PATH'], $GLOBALS['COOKIE_DOMAIN']);
		}
		if ($s[1]) {
			q('UPDATE fud30_ses SET sys_id=\'\' WHERE ses_id=\''. $s[0] .'\'');
		}
		return $s[0];
	}

	/* If we can only have 1 login per account, 'remove' all other logins. */
	q('DELETE FROM fud30_ses WHERE user_id='. $id .' AND ses_id!=\''. $cur_ses_id .'\'');
	q('UPDATE fud30_ses SET user_id='. $id .', sys_id=\''. ses_make_sysid() .'\' WHERE ses_id=\''. $cur_ses_id .'\'');
	$GLOBALS['new_sq'] = regen_sq($id);
	if ($GLOBALS['FUD_OPT_3'] & 2097152) {
		$flag = ret_flag();
	} else {
		$flag = '';	
	}
	q('UPDATE fud30_users SET last_used_ip=\''. get_ip() .'\', '. $flag .' sq=\''. $GLOBALS['new_sq'] .'\' WHERE id='. $id);

	return $cur_ses_id;
}

function rebuildmodlist()
{
	$tbl =& $GLOBALS['DBHOST_TBL_PREFIX'];
	$lmt =& $GLOBALS['SHOW_N_MODS'];
	$c = uq('SELECT u.id, u.alias, f.id FROM '. $tbl .'mod mm INNER JOIN '. $tbl .'users u ON mm.user_id=u.id INNER JOIN '. $tbl .'forum f ON f.id=mm.forum_id ORDER BY f.id,u.alias');
	$u = $ar = array();

	while ($r = db_rowarr($c)) {
		$u[] = $r[0];
		if ($lmt < 1 || (isset($ar[$r[2]]) && count($ar[$r[2]]) >= $lmt)) {
			continue;
		}
		$ar[$r[2]][$r[0]] = $r[1];
	}
	unset($c);

	q('UPDATE '. $tbl .'forum SET moderators=NULL');
	foreach ($ar as $k => $v) {
		q('UPDATE '. $tbl .'forum SET moderators='. ssn(serialize($v)) .' WHERE id='. $k);
	}
	q('UPDATE '. $tbl .'users SET users_opt='. q_bitand('users_opt', ~524288) .' WHERE users_opt>=524288 AND '. q_bitand('users_opt', 524288) .'>0');
	if ($u) {
		q('UPDATE '. $tbl .'users SET users_opt='. q_bitor('users_opt', 524288) .' WHERE id IN('. implode(',', $u) .') AND '. q_bitand('users_opt', 1048576) .'=0');
	}
}

/** Lookup geoip info (if enabled) and return SQL UPDATE fragment. */
function ret_flag($raw=0)
{
	if ($raw) {
		$ip = $raw;
	} else {
		$ip = get_ip();
	}

	if ($GLOBALS['FUD_OPT_3'] & 524288) {	// ENABLE_GEO_LOCATION.
		$val = db_saq('SELECT cc, country FROM fud30_geoip WHERE '. sprintf('%u', ip2long($ip)) .' BETWEEN ips AND ipe');
		if ($raw) {
			return $val ? $val : array(null,null);
		}
		if ($val) {
			return 'flag_cc='. _esc($val[0]) .', flag_country='. _esc($val[1]).',';
		}
	}
	if ($raw) {
		return array(null, null);
	}
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
}class fud_smtp
{
	var $fs, $last_ret, $msg, $subject, $to, $from, $headers;

	function get_return_code($cmp_code='250')
	{
		if (!($this->last_ret = @fgets($this->fs, 515))) {
			return;
		}
		if ((int)$this->last_ret == $cmp_code) {
			return 1;
		}
		return;
	}

	function wts($string)
	{
		/* Write to stream. */
		fwrite($this->fs, $string ."\r\n");
	}

	function open_smtp_connex()
	{
		if( !($this->fs = @fsockopen($GLOBALS['FUD_SMTP_SERVER'], $GLOBALS['FUD_SMTP_PORT'], $errno, $errstr, $GLOBALS['FUD_SMTP_TIMEOUT'])) ) {
			fud_logerror('ERROR: SMTP server at '. $GLOBALS['FUD_SMTP_SERVER'] ." is not available<br />\n". ($errno ? "Additional Problem Info: $errno -> $errstr <br />\n" : ''), 'fud_errors');
			return;
		}
		if (!$this->get_return_code(220)) {	// 220 == Ready to speak SMTP.
			return;
		}

		$es = strpos($this->last_ret, 'ESMTP') !== false;
		$smtp_srv = $_SERVER['SERVER_NAME'];
		if ($smtp_srv == 'localhost' || $smtp_srv == '127.0.0.1' || $smtp_srv == '::1') {
			$smtp_srv = 'FUDforum SMTP server';
		}

		$this->wts(($es ? 'EHLO ' : 'HELO ') . $smtp_srv);
		if (!$this->get_return_code()) {
			return;
		}

		/* Scan all lines and look for TLS support. */
		$tls = false;
		if ($es) {
			while($str = @fgets($this->fs, 515)) {
				if (substr($str, 0, 12) == '250-STARTTLS') $tls = true;
				if (substr($str, 3,  1) == ' ') break;	// Done reading if 4th char is a space.

			}
		}

		/* Do SMTP Auth if needed. */
		if ($GLOBALS['FUD_SMTP_LOGIN']) {
			if ($tls) {
				/*  Initiate TSL communication with server. */
				$this->wts('STARTTLS');
				if (!$this->get_return_code(220)) {
					return;
				}
				/* Encrypt the connection. */
				if (!stream_socket_enable_crypto($this->fs, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
					return false;
				} 
				/* Say hi again. */
				$this->wts(($es ? 'EHLO ' : 'HELO ').$smtp_srv);
				if (!$this->get_return_code()) {
					return;
				}
				/* we need to scan all other lines */
				while($str = @fgets($this->fs, 515)) {
					if (substr($str, 3, 1) == ' ') break;
				}
			}

			$this->wts('AUTH LOGIN');
			if (!$this->get_return_code(334)) {
				return;
			}
			$this->wts(base64_encode($GLOBALS['FUD_SMTP_LOGIN']));
			if (!$this->get_return_code(334)) {
				return;
			}
			$this->wts(base64_encode($GLOBALS['FUD_SMTP_PASS']));
			if (!$this->get_return_code(235)) {
				return;
			}
		}

		return 1;
	}

	function send_from_hdr()
	{
		$this->wts('MAIL FROM: <'. $GLOBALS['NOTIFY_FROM'] .'>');
		return $this->get_return_code();
	}

	function send_to_hdr()
	{
		$this->to = (array) $this->to;

		foreach ($this->to as $to_addr) {
			$this->wts('RCPT TO: <'. $to_addr .'>');
			if (!$this->get_return_code()) {
				return;
			}
		}
		return 1;
	}

	function send_data()
	{
		$this->wts('DATA');
		if (!$this->get_return_code(354)) {
			return;
		}

		/* This is done to ensure what we comply with RFC requiring each line to end with \r\n */
		$this->msg = preg_replace('!(\r)?\n!si', "\r\n", $this->msg);

		if( empty($this->from) ) $this->from = $GLOBALS['NOTIFY_FROM'];

		$this->wts('Subject: '. $this->subject);
		$this->wts('Date: '. date('r'));
		$this->wts('To: '. (count($this->to) == 1 ? $this->to[0] : $GLOBALS['NOTIFY_FROM']));
		$this->wts('From: '. $this->from);
		$this->wts('X-Mailer: FUDforum v'. $GLOBALS['FORUM_VERSION']);
		$this->wts($this->headers ."\r\n");
		$this->wts($this->msg);
		$this->wts('.');

		return $this->get_return_code();
	}

	function close_connex()
	{
		$this->wts('QUIT');
		fclose($this->fs);
	}

	function send_smtp_email()
	{
		if (!$this->open_smtp_connex()) {
			if ($this->last_ret) {
				fud_logerror('Open SMTP connection - invalid return code: '. $this->last_ret, 'fud_errors');
			}
			return false;
		}
		if (!$this->send_from_hdr()) {
			fud_logerror('Send "From:" header - invalid SMTP return code: '. $this->last_ret, 'fud_errors');
			$this->close_connex();
			return false;
		}
		if (!$this->send_to_hdr()) {
			fud_logerror('Send "To:" header - invalid SMTP return code: '. $this->last_ret, 'fud_errors');
			$this->close_connex();
			return false;
		}
		if (!$this->send_data()) {
			fud_logerror('Send data - invalid SMTP return code: '. $this->last_ret, 'fud_errors');
			$this->close_connex();
			return false;
		}

		$this->close_connex();
		return true;
	}
}function read_msg_body($off, $len, $id)
{
	if ($off == -1) {	// Fetch from DB and return.
		return q_singleval('SELECT data FROM fud30_msg_store WHERE id='. $id);
	}

	if (!$len) {	// Empty message.
		return;
	}

	// Open file if it's not already open.
	if (!isset($GLOBALS['__MSG_FP__'][$id])) {
		$GLOBALS['__MSG_FP__'][$id] = fopen($GLOBALS['MSG_STORE_DIR'] .'msg_'. $id, 'rb');
	}

	// Read from file.
	fseek($GLOBALS['__MSG_FP__'][$id], $off);
	return fread($GLOBALS['__MSG_FP__'][$id], $len);
}

	// User is logged in, redirect to forum index.
	if (_uid) {
		if ($FUD_OPT_2 & 32768) {
			header('Location: '.$GLOBALS['WWW_ROOT'].'index.php/i/'. _rsidl);
		} else {
			header('Location: '.$GLOBALS['WWW_ROOT'].'index.php?t=index&'. _rsidl);
		}
		exit;
	}

	// Password resets are disabled.
	if (!($FUD_OPT_4 & 2)) {
                std_error('disabled');
        }

	if (isset($_GET['reset_key'])) {
		if (($ui = db_saq('SELECT email, login, id FROM fud30_users WHERE reset_key='. _esc((string)$_GET['reset_key'])))) {
			// Generate new password and salt for user.
			$salt   = substr(md5(uniqid(mt_rand(), true)), 0, 9);
			$passwd = dechex(get_random_value(32));	// New password that will be mailed to the user.
			q('UPDATE fud30_users SET passwd=\''. sha1($salt . sha1($passwd)) .'\', salt=\''. $salt .'\', reset_key=\'0\' WHERE id='. $ui[2]);
			send_email($NOTIFY_FROM, $ui[0], 'Wachtwoord opnieuw instellen', 'Hallo.\n\nZoals gevraagd zijn uw aanmeldgegevens naar u verzonden.\nHier zijn uw gegevens:\n\nGebruikersnaam: '.$ui[1].'\nWachtwoord: '.$passwd.'\n\nLet op: uw wachtwoord is opnieuw ingesteld naar de bovenstaande waarde. Als u toch een ander wachtwoord wilt instellen, kunt u dat via uit gebruikersinstellingen doen:\n'.$GLOBALS['WWW_ROOT'].'index.php?t=register\n\n\n\nAls u dit bericht onterecht hebt ontvangen, negeer het dan. Als u meerdere kopieën van deze e-mail hebt ontvangen die u niet hebt gevraagd, neem dan contact op met een beheerder via '.$GLOBALS['ADMIN_EMAIL'].'.\n\nDit verzoek is afkomstig van '.$_SERVER['REMOTE_ADDR'].'.\n\n');
			ses_putvar((int)$usr->sid, 'Uw wachtwoord is naar u verzonden. Meestal ontvangt u de e-mail binnen enige minuten.<br />');
			if ($FUD_OPT_2 & 32768) {
				header('Location: '.$GLOBALS['WWW_ROOT'].'index.php/l/'. _rsidl);
			} else {
				header('Location: '.$GLOBALS['WWW_ROOT'].'index.php?t=login&'. _rsidl);
			}
			exit;
		}
		error_dialog('Fout', 'Er is een ongeldige sleutel voor het opnieuw instellen van het wachtwoord opgegeven. Het is mogelijk dat uw e-mailprogramma deze pagina al automatisch heeft geopend, waardoor de sleutel ongeldig is geworden. Als dit het geval is, dan ontvangt u snel een e-mail met uw nieuwe wachtwoord. Als u zo&#39;n e-mail niet ontvangt, probeer dan opnieuw het wachtwoord opnieuw in te stellen.');
	}

	if (isset($_GET['email'])) {
		$email = (string) $_GET['email'];
	} else if (isset($_POST['email'])) {
		$email = (string) $_POST['email'];
	} else {
		$email = '';
	}

	if ($email) {
		if ($uobj = db_sab('SELECT id, users_opt FROM fud30_users WHERE email='. _esc($email))) {
			if ($FUD_OPT_2 & 1 && !($uobj->users_opt & 131072)) {
				$uent = new stdClass();
				$uent->conf_key = usr_email_unconfirm($uobj->id);
				send_email($NOTIFY_FROM, $email, 'Registratiebevestiging', 'Dank u voor uw registratie.\nVolg de volgende verwijzing om uw gebruiker te activeren:\n\n'.$GLOBALS['WWW_ROOT'].'index.php?t=emailconf&conf_key='.$uent->conf_key.'\n\nAls uw gebruiker is geactiveerd, wordt u aangemeld bij het forum en automatisch doorverwezen naar de hoofdpagina.\n\nAls u dit bericht onterecht hebt ontvangen, negeer het dan. Als u meerdere kopieën van deze e-mail hebt ontvangen die u niet hebt gevraagd, neem dan contact op met een beheerder via '.$GLOBALS['ADMIN_EMAIL'].'.\n\nDit verzoek is afkomstig van '.$_SERVER['REMOTE_ADDR'].'.\n\n');
			} else {
				q('UPDATE fud30_users SET reset_key=\''. ($key = md5(__request_timestamp__ . $uobj->id . get_random_value())) .'\' WHERE id='. $uobj->id);
				$url = ''.$GLOBALS['WWW_ROOT'].'index.php?t=reset&reset_key='. $key;
				send_email($NOTIFY_FROM, $email, 'Wachtwoord opnieuw instellen', 'Hallo,\n\nU hebt een verzoek uitgevoerd voor het opnieuw instellen van uw wachtwoord. Ga naar de volgende URL om het proces af te ronden:\n\n'.$url.'\n\nLet op: het forum versleutelt de wachtwoorden op een manier die het niet mogelijk maakt het wachtwoord te achterhalen. De ingave van uw wachtwoord wordt versleuteld en vergeleken met de opgeslagen versleutelde versie. Als u hierover meer wilt weten, kunt u op internet zoeken naar het MD5 hash algoritme.\n\nAls u dit bericht onterecht hebt ontvangen, negeer het dan. Als u meerdere kopieën van deze e-mail hebt ontvangen die u niet hebt gevraagd, neem dan contact op met een beheerder via '.$GLOBALS['ADMIN_EMAIL'].'.\n\nDit verzoek is afkomstig van '.$_SERVER['REMOTE_ADDR'].'.\n\n');
			}
			error_dialog('Gegevens', 'U ontvangt meestal binnen enkele minuten een e-mail met instructies.');
		} else {
			$no_such_email = '<span class="ErrorText">Het e-mailadres is niet in de database aangetroffen</span><br />';
		}
	} else {
		$no_such_email = '';
	}

	$TITLE_EXTRA = ': Wachtwoord opnieuw instellen';

/* Print number of unread private messages in User Control Panel. */
	if (__fud_real_user__ && $FUD_OPT_1 & 1024) {	// PM_ENABLED
		$c = q_singleval('SELECT count(*) FROM fud30_pmsg WHERE duser_id='. _uid .' AND fldr=1 AND read_stamp=0');
		$ucp_private_msg = $c ? '<li><a href="index.php?t=pmsg&amp;'._rsid.'" title="Privébericht"><img src="theme/default/images/top_pm'.img_ext.'" alt="" /> U hebt <span class="GenTextRed">('.$c.')</span> ongelezen '.convertPlural($c, array('privébericht','privéberichten')).'</a></li>' : '<li><a href="index.php?t=pmsg&amp;'._rsid.'" title="Privébericht"><img src="theme/default/images/top_pm'.img_ext.'" alt="" /> Privébericht</a></li>';
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
	<?php echo (__fud_real_user__ ? '<li><a href="index.php?t=uc&amp;'._rsid.'" title="Gebruikersbeheer"><img src="theme/default/images/top_profile'.img_ext.'" alt="" /> Profiel</a></li>' : ($FUD_OPT_1 & 2 ? '<li><a href="index.php?t=register&amp;'._rsid.'" title="Registreren"><img src="theme/default/images/top_register'.img_ext.'" alt="" /> Registreren</a></li>' : '')).'
	'.(__fud_real_user__ ? '<li><a href="index.php?t=login&amp;'._rsid.'&amp;logout=1&amp;SQ='.$GLOBALS['sq'].'" title="Afmelden"><img src="theme/default/images/top_logout'.img_ext.'" alt="" /> Afmelden [ '.$usr->alias.' ]</a></li>' : '<li><a href="index.php?t=login&amp;'._rsid.'" title="Aanmelden"><img src="theme/default/images/top_login'.img_ext.'" alt="" /> Aanmelden</a></li>'); ?>
	<li><a href="index.php?t=index&amp;<?php echo _rsid; ?>" title="Startpagina"><img src="theme/default/images/top_home<?php echo img_ext; ?>" alt="" /> Startpagina</a></li>
	<?php echo ($is_a || ($usr->users_opt & 268435456) ? '<li><a href="adm/index.php?S='.s.'&amp;SQ='.$GLOBALS['sq'].'" title="Beheer"><img src="theme/default/images/top_admin'.img_ext.'" alt="" /> Beheer</a></li>' : ''); ?>
</ul>
</div>
<div class="ctb">
<form method="post" action="index.php?t=reset">
<table cellspacing="1" cellpadding="2" class="DialogTable">
<tr>
	<th colspan="2">Wachtwoord wijzigen via e-mail</th>
</tr>
<tr>
	<td colspan="2" class="RowStyleA GenText">Uw wachtwoord zal opnieuw ingesteld en aan u toegezonden. Als u uw e-mailadres nog niet hebt bevestigd, wordt het bevestigingsverzoek opnieuw aan u toegezonden.</td>
</tr>
<tr class="RowStyleB">
	<td class="GenText">E-mail:</td>
	<td><?php echo $no_such_email; ?><input type="text" name="email" value="<?php echo htmlspecialchars($email); ?>" /></td>
</tr>
<tr class="RowStyleA">
	<td class="nw ar" colspan="2"><input type="submit" class="button" name="reset_passwd" value="Wachtwoord opnieuw instellen" /></td>
</tr>
</table>
<?php echo _hs; ?>
</form>
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
