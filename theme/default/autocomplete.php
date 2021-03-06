<?php
/**
* copyright            : (C) 2001-2011 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: autocomplete.php.t 5364 2011-08-26 15:27:32Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}

	/* Only for logged in users. */
	if (!_uid) {
		std_error('access');
	}



	/* Return DB values for AJAX autocomplete of fields. */
	if (!empty($_GET['lookup']) && !empty($_GET['term'])) {
		$lookup = ($_GET['lookup'] == 'email') ? 'email' : 'alias';
		$term   = _esc($_GET['term'] .'%');

		$c = uq('SELECT '. $lookup .' FROM fud30_users WHERE '. $lookup .' LIKE '. $term .' AND '. q_bitand('users_opt', 1073741824) .'= 0 LIMIT 10');
		$rows = array();
		while ($r = db_rowarr($c)) {
			$rows[] = array('value' => $r[0]);
		}
		echo json_encode($rows);
	}
