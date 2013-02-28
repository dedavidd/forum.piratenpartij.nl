<?php
/**
* copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: poll.php.t 5030 2010-10-08 18:27:42Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

	define('plain_form', 1);

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}function poll_delete($id)
{
	if (!$id) {
		return;
	}

	q('UPDATE fud30_msg SET poll_id=0 WHERE poll_id='. $id);
	q('DELETE FROM fud30_poll_opt WHERE poll_id='. $id);
	q('DELETE FROM fud30_poll_opt_track WHERE poll_id='. $id);
	q('DELETE FROM fud30_poll WHERE id='. $id);
}

function poll_fetch_opts($id)
{
	$a = array();
	$c = uq('SELECT id,name FROM fud30_poll_opt WHERE poll_id='. $id);
	while ($r = db_rowarr($c)) {
		$a[$r[0]] = $r[1];
	}
	unset($c);

	return $a;
}

function poll_del_opt($id, $poll_id)
{
	q('DELETE FROM fud30_poll_opt WHERE poll_id='. $poll_id .' AND id='. $id);
	q('DELETE FROM fud30_poll_opt_track WHERE poll_id='. $poll_id .' AND poll_opt='. $id);
	q('UPDATE fud30_poll SET total_votes=(SELECT SUM(count) FROM fud30_poll_opt WHERE poll_id='. $poll_id .') WHERE id='. $poll_id);
}

function poll_activate($poll_id, $frm_id)
{
	q('UPDATE fud30_poll SET forum_id='. $frm_id .' WHERE id='. $poll_id);
}

function poll_sync($poll_id, $name, $max_votes, $expiry)
{
	q('UPDATE fud30_poll SET name='. _esc(htmlspecialchars($name)) .', expiry_date='. (int)$expiry .', max_votes='. (int)$max_votes .' WHERE id='. $poll_id);
}

function poll_add($name, $max_votes, $expiry, $uid=_uid)
{
	return db_qid('INSERT INTO fud30_poll (name, owner, creation_date, expiry_date, max_votes) VALUES ('. _esc(htmlspecialchars($name)) .', '. $uid .', '. __request_timestamp__ .', '. (int)$expiry .', '. (int)$max_votes .')');
}

function poll_opt_sync($id, $name)
{
	q('UPDATE fud30_poll_opt SET name='. _esc($name) .' WHERE id='. $id);
}

function poll_opt_add($name, $poll_id)
{
	return db_qid('INSERT INTO fud30_poll_opt (poll_id,name) VALUES('. $poll_id .', '. _esc($name) .')');
}

function poll_validate($poll_id, $msg_id)
{
	if (($mid = (int) q_singleval('SELECT id FROM fud30_msg WHERE poll_id='. $poll_id)) && $mid != $msg_id) {
		return 0;
	}
	return $poll_id;
}$GLOBALS['seps'] = array(' '=>' ', "\n"=>"\n", "\r"=>"\r", '\''=>'\'', '"'=>'"', '['=>'[', ']'=>']', '('=>'(', ';'=>';', ')'=>')', "\t"=>"\t", '='=>'=', '>'=>'>', '<'=>'<');

function fud_substr_replace($str, $newstr, $pos, $len)
{
        return substr($str, 0, $pos) . $newstr . substr($str, $pos+$len);
}

function url_check($url)
{
	$url = preg_replace('!\s+!', '', $url);

	if (strpos($url, '&amp;#') !== false) {
		return preg_replace('!&#([0-9]{2,3});!e', "chr(\\1)", char_fix($url));
	}
	return $url;
}

function tags_to_html($str, $allow_img=1, $no_char=0)
{
	if (!$no_char) {
		$str = htmlspecialchars($str);
	}

	$str = nl2br($str);

	$ostr = '';
	$pos = $old_pos = 0;

	// Call all BBcode to HTML conversion plugins.
	if (defined('plugins')) {
		list($str) = plugin_call_hook('BBCODE2HTML', array($str));
	}

	while (($pos = strpos($str, '[', $pos)) !== false) {
		if (isset($str[$pos + 1], $GLOBALS['seps'][$str[$pos + 1]])) {
			++$pos;
			continue;
		}

		if (($epos = strpos($str, ']', $pos)) === false) {
			break;
		}
		if (!($epos-$pos-1)) {
			$pos = $epos + 1;
			continue;
		}
		$tag = substr($str, $pos+1, $epos-$pos-1);
		if (($pparms = strpos($tag, '=')) !== false) {
			$parms = substr($tag, $pparms+1);
			if (!$pparms) { /*[= exception */
				$pos = $epos+1;
				continue;
			}
			$tag = substr($tag, 0, $pparms);
		} else {
			$parms = '';
		}

		if (!$parms && ($tpos = strpos($tag, '[')) !== false) {
			$pos += $tpos;
			continue;
		}
		$tag = strtolower($tag);

		switch ($tag) {
			case 'quote title':
				$tag = 'quote';
				break;
			case 'list type':
				$tag = 'list';
				break;
			case 'hr':
				$str{$pos} = '<';
				$str{$pos+1} = 'h';
				$str{$pos+2} = 'r';
				$str{$epos} = '>';
				continue 2;
		}

		if ($tag[0] == '/') {
			if (isset($end_tag[$pos])) {
				if( ($pos-$old_pos) ) $ostr .= substr($str, $old_pos, $pos-$old_pos);
				$ostr .= $end_tag[$pos];
				$pos = $old_pos = $epos+1;
			} else {
				$pos = $epos+1;
			}

			continue;
		}

		$cpos = $epos;
		$ctag = '[/'. $tag .']';
		$ctag_l = strlen($ctag);
		$otag = '['. $tag;
		$otag_l = strlen($otag);
		$rf = 1;
		$nt_tag = 0;
		while (($cpos = strpos($str, '[', $cpos)) !== false) {
			if (isset($end_tag[$cpos]) || isset($GLOBALS['seps'][$str[$cpos + 1]])) {
				++$cpos;
				continue;
			}

			if (($cepos = strpos($str, ']', $cpos)) === false) {
				if (!$nt_tag) {
					break 2;
				} else {
					break;
				}
			}

			if (strcasecmp(substr($str, $cpos, $ctag_l), $ctag) == 0) {
				--$rf;
			} else if (strcasecmp(substr($str, $cpos, $otag_l), $otag) == 0) {
				++$rf;
			} else {
				$nt_tag++;
				++$cpos;
				continue;
			}

			if (!$rf) {
				break;
			}
			$cpos = $cepos;
		}

		if (!$cpos || ($rf && $str[$cpos] == '<')) { /* Left over [ handler. */
			++$pos;
			continue;
		}

		if ($cpos !== false) {
			if (($pos-$old_pos)) {
				$ostr .= substr($str, $old_pos, $pos-$old_pos);
			}
			switch ($tag) {
				case 'notag':
					$ostr .= '<span name="notag">'. substr($str, $epos+1, $cpos-1-$epos) .'</span>';
					$epos = $cepos;
					break;
				case 'url':
					if (!$parms) {
						$url = substr($str, $epos+1, ($cpos-$epos)-1);
					} else {
						$url = $parms;
					}

					$url = url_check($url);
					$url = str_replace('&quot;', '', $url); // Remove quotes from URL.

					if (!strncasecmp($url, 'www.', 4)) {
						$url = 'http&#58;&#47;&#47;'. $url;
					} else if (strpos(strtolower($url), 'script:') !== false) {
						$ostr .= substr($str, $pos, $cepos - $pos + 1);
						$epos = $cepos;
						$str[$cpos] = '<';
						break;
					} else {
						$url = str_replace('://', '&#58;&#47;&#47;', $url);
					}

					if ( strtolower(substr($str, $epos+1, 6)) == '[/url]' ) {
						$end_tag[$cpos] = $url .'</a>';  // Fill empty link.
					} else {
						$end_tag[$cpos] = '</a>';
					}
					$ostr .= '<a href="'. $url .'">';
					break;
				case 'i':
				case 'u':
				case 'b':
				case 's':
				case 'sub':
				case 'sup':
				case 'del':
					$end_tag[$cpos] = '</'. $tag .'>';
					$ostr .= '<'. $tag .'>';
					break;
				case 'h1':
				case 'h2':
				case 'h3':
				case 'h4':
					$end_tag[$cpos] = '</'.$tag.'>';
					$ostr .= '<'.$tag.'>';
					break;
				case 'email':
					if (!$parms) {
						$parms = str_replace('@', '&#64;', substr($str, $epos+1, ($cpos-$epos)-1));
						$ostr .= '<a href="mailto:'. $parms .'">'. $parms .'</a>';
						$epos = $cepos;
						$str[$cpos] = '<';
					} else {
						$end_tag[$cpos] = '</a>';
						$ostr .= '<a href="mailto:'. str_replace('@', '&#64;', $parms) .'">';
					}
					break;
				case 'color':
				case 'size':
				case 'font':
					if ($tag == 'font') {
						$tag = 'face';
					}
					$end_tag[$cpos] = '</font>';
					$ostr .= '<font '. $tag .'="'. $parms .'">';
					break;
				case 'code':
					$param = substr($str, $epos+1, ($cpos-$epos)-1);

					$ostr .= '<div class="pre"><pre>'. reverse_nl2br($param) .'</pre></div>';
					$epos = $cepos;
					$str[$cpos] = '<';
					break;
				case 'pre':
					$param = substr($str, $epos+1, ($cpos-$epos)-1);

					$ostr .= '<pre>'. reverse_nl2br($param) .'</pre>';
					$epos = $cepos;
					$str[$cpos] = '<';
					break;
				case 'php':
					$param = trim(reverse_fmt(reverse_nl2br(substr($str, $epos+1, ($cpos-$epos)-1))));

					if (strncmp($param, '<?php', 5)) {
						if (strncmp($param, '<?', 2)) {
							$param = "<?php\n". $param;
						} else {
							$param = "<?php\n". substr($param, 3);
						}
					}
					if (substr($param, -2) != '?>') {
						$param .= "\n?>";
					}

					$ostr .= '<SPAN name="php">'. trim(@highlight_string($param, true)) .'</SPAN>';
					$epos = $cepos;
					$str[$cpos] = '<';
					break;
				case 'img':
				case 'imgl':
				case 'imgr':
					if (!$allow_img) {
						$ostr .= substr($str, $pos, ($cepos-$pos)+1);
					} else {
						$class = ($tag == 'img') ? '' : 'class="'. $tag{3} .'" ';

						if (!$parms) {
							$parms = substr($str, $epos+1, ($cpos-$epos)-1);
							if (strpos(strtolower(url_check($parms)), 'script:') === false) {
								$ostr .= '<img '. $class .'src="'. $parms .'" border="0" alt="'. $parms .'" />';
							} else {
								$ostr .= substr($str, $pos, ($cepos-$pos)+1);
							}
						} else {
							if (strpos(strtolower(url_check($parms)), 'script:') === false) {
								$ostr .= '<img '. $class .'src="'. $parms .'" border="0" alt="'. substr($str, $epos+1, ($cpos-$epos)-1) .'" />';
							} else {
								$ostr .= substr($str, $pos, ($cepos-$pos)+1);
							}
						}
					}
					$epos = $cepos;
					$str[$cpos] = '<';
					break;
				case 'quote':
					if (!$parms) {
						$parms = 'Citaat:';
					} else {
						$parms = str_replace(array('@', ':'), array('&#64;', '&#58;'), $parms);
					}
					$ostr .= '<cite>'.$parms.'</cite><blockquote>';
					$end_tag[$cpos] = '</blockquote>';
					break;
				case 'align':
					$end_tag[$cpos] = '</div>';
					$ostr .= '<div align="'. $parms .'">';
					break;
				case 'list':
					$tmp = substr($str, $epos, ($cpos-$epos));
					$tmp_l = strlen($tmp);
					$tmp2 = str_replace('[*]', '<li>', $tmp);
					$tmp2_l = strlen($tmp2);
					$str = str_replace($tmp, $tmp2, $str);

					$diff = $tmp2_l - $tmp_l;
					$cpos += $diff;

					if (isset($end_tag)) {
						foreach($end_tag as $key => $val) {
							if ($key < $epos) {
								continue;
							}

							$end_tag[$key+$diff] = $val;
						}
					}

					switch (strtolower($parms)) {
						case '1':
						case 'decimal':
						case 'a':
							$end_tag[$cpos] = '</ol>';
							$ostr .= '<ol type="'. $parms .'">';
							break;
						case 'square':
						case 'circle':
						case 'disc':
							$end_tag[$cpos] = '</ul>';
							$ostr .= '<ul type="'. $parms .'">';
							break;
						default:
							$end_tag[$cpos] = '</ul>';
							$ostr .= '<ul>';
					}
					break;
				case 'spoiler':
					$rnd = rand();
					$end_tag[$cpos] = '</div></div>';
					$ostr .= '<div class="dashed" style="padding: 3px;" align="center"><a href="javascript://" onclick="javascript: layerVis(\'s'. $rnd .'\', 1);">'
						.($parms ? $parms : 'Spoiler wisselen') .'</a><div align="left" id="s'. $rnd .'" style="display: none;">';
					break;
				case 'acronym':
					$end_tag[$cpos] = '</acronym>';
					$ostr .= '<acronym title="'. ($parms ? $parms : ' ') .'">';
					break;
				case 'wikipedia':
					$end_tag[$cpos] = '</a>';
					$url = substr($str, $epos+1, ($cpos-$epos)-1);
					if ($parms && preg_match('!^[A-Za-z]+$!', $parms)) {
						$parms .= '.';
					} else {
						$parms = '';
					}
					$ostr .= '<a href="http://'. $parms .'wikipedia.com/wiki/'. $url .'" name="WikiPediaLink">';
					break;
			}

			$str[$pos] = '<';
			$pos = $old_pos = $epos+1;
		} else {
			$pos = $epos+1;
		}
	}
	$ostr .= substr($str, $old_pos, strlen($str)-$old_pos);

	/* URL paser. */
	$pos = 0;
	$ppos = 0;
	while (($pos = @strpos($ostr, '://', $pos)) !== false) {
		if ($pos < $ppos) {
			break;
		}
		// Check if it's inside any tag.
		$i = $pos;
		while (--$i && $i > $ppos) {
			if ($ostr[$i] == '>' || $ostr[$i] == '<') {
				break;
			}
		}
		if (!$pos || $ostr[$i] == '<') {
			$pos += 3;
			continue;
		}

		// Check if it's inside the a tag.
		if (($ts = strpos($ostr, '<a ', $pos)) === false) {
			$ts = strlen($ostr);
		}
		if (($te = strpos($ostr, '</a>', $pos)) == false) {
			$te = strlen($ostr);
		}
		if ($te < $ts) {
			$ppos = $pos += 3;
			continue;
		}

		// Check if it's inside the PRE tag.
		if (($ts = strpos($ostr, '<pre>', $pos)) === false) {
			$ts = strlen($ostr);
		}
		if (($te = strpos($ostr, '</pre>', $pos)) == false) {
			$te = strlen($ostr);
		}
		if ($te < $ts) {
			$ppos = $pos += 3;
			continue;
		}

		// Check if it's inside the SPAN tag
		if (($ts = strpos($ostr, '<span>', $pos)) === false) {
			$ts = strlen($ostr);
		}
		if (($te = strpos($ostr, '</span>', $pos)) == false) {
			$te = strlen($ostr);
		}
		if ($te < $ts) {
			$ppos = $pos += 3;
			continue;
		}

		$us = $pos;
		$l = strlen($ostr);
		while (1) {
			--$us;
			if ($ppos > $us || $us >= $l || isset($GLOBALS['seps'][$ostr[$us]])) {
				break;
			}
		}

		unset($GLOBALS['seps']['=']);
		$ue = $pos;
		while (1) {
			++$ue;
			if ($ue >= $l || isset($GLOBALS['seps'][$ostr[$ue]])) {
				break;
			}

			if ($ostr[$ue] == '&') {
				if ($ostr[$ue+4] == ';') {
					$ue += 4;
					continue;
				}
				if ($ostr[$ue+3] == ';' || $ostr[$ue+5] == ';') {
					break;
				}
			}

			if ($ue >= $l || isset($GLOBALS['seps'][$ostr[$ue]])) {
				break;
			}
		}
		$GLOBALS['seps']['='] = '=';

		$url = url_check(substr($ostr, $us+1, $ue-$us-1));
		if (strpos($url, 'script', strlen('script')) !== false || ($ue - $us - 1) < 9) {
			$pos = $ue;
			continue;
		}
		$html_url = '<a href="'. $url .'">'. $url .'</a>';
		$html_url_l = strlen($html_url);
		$ostr = fud_substr_replace($ostr, $html_url, $us+1, $ue-$us-1);
		$ppos = $pos;
		$pos = $us+$html_url_l;
	}

	/* E-mail parser. */
	$pos = 0;
	$ppos = 0;

	$er = array_flip(array_merge(range(0,9), range('A', 'Z'), range('a','z'), array('.', '-', '\'', '_')));

	while (($pos = @strpos($ostr, '@', $pos)) !== false) {
		if ($pos < $ppos) {
			break;
		}

		// Check if it's inside any tag.
		$i = $pos;
		while (--$i && $i>$ppos) {
			if ( $ostr[$i] == '>' || $ostr[$i] == '<') {
				break;
			}
		}
		if ($i < 0 || $ostr[$i]=='<') {
			++$pos;
			continue;
		}


		// Check if it's inside the a tag.
		if (($ts = strpos($ostr, '<a ', $pos)) === false) {
			$ts = strlen($ostr);
		}
		if (($te = strpos($ostr, '</a>', $pos)) == false) {
			$te = strlen($ostr);
		}
		if ($te < $ts) {
			$ppos = $pos += 1;
			continue;
		}

		// Check if it's inside the PRE tag.
		if (($ts = strpos($ostr, '<div class="pre"><pre>', $pos)) === false) {
			$ts = strlen($ostr);
		}
		if (($te = strpos($ostr, '</pre></div>', $pos)) == false) {
			$te = strlen($ostr);
		}
		if ($te < $ts) {
			$ppos = $pos += 1;
			continue;
		}

		for ($es = ($pos - 1); $es > ($ppos - 1); $es--) {
			if (isset($er[ $ostr[$es] ])) continue;
			++$es;
			break;
		}
		if ($es == $pos) {
			$ppos = $pos += 1;
			continue;
		}
		if ($es < 0) {
			$es = 0;
		}

		for ($ee = ($pos + 1); @isset($ostr[$ee]); $ee++) {
			if (isset($er[ $ostr[$ee] ])) continue;
			break;
		}
		if ($ee == ($pos+1)) {
			$ppos = $pos += 1;
			continue;
		}

		$email = str_replace('@', '&#64;', substr($ostr, $es, $ee-$es));
		if (strpos( substr($email, 1, -1), '.') === false) {	// E-mail mostly have dots in them.
			$ppos = $pos += 1; continue;
		}
		$email_url = '<a href="mailto:'. $email .'">'. $email .'</a>';
		$email_url_l = strlen($email_url);
		$ostr = fud_substr_replace($ostr, $email_url, $es, $ee-$es);
		$ppos =	$es+$email_url_l;
		$pos = $ppos;
	}

	// Remove line breaks directly following list tags.
	$ostr = preg_replace('!(<[uo]l>)\s*<br\s*/?\s*>\s*(<li>)!is', '\\1\\2', $ostr);
	$ostr = preg_replace('!<br\s*/?\s*>\s*(</li>|<li>|</ul>|</ol>)!is', '\\1', $ostr);

	return $ostr;
}

function html_to_tags($fudml)
{
	// Call all HTML to BBcode conversion plugins.
	if (defined('plugins')) {
		list($fudml) = plugin_call_hook('HTML2BBCODE', array($fudml));
	}

	// PHP code blocks.
	while (preg_match('!<span name="php">(.*?)</span>!is', $fudml, $res)) {
		$tmp = trim(html_entity_decode(strip_tags(str_replace('<br />', "\n", $res[1]))));
		$m = md5($tmp);
		$php[$m] = $tmp;
		$fudml = str_replace($res[0], "[php]\n". $m ."\n[/php]", $fudml);
	}

	// Wikipedia tags.
	while (preg_match('!<a href="http://(?:([A-ZA-z]+)?\.)?wikipedia.com/wiki/([^"]+)"( target="_blank")? name="WikiPediaLink">(.*?)</a>!s', $fudml, $res)) {
		if (count($res) == 5) {
			$fudml = str_replace($res[0], '[wikipedia='. $res[1] .']'. $res[2] .'[/wikipedia]', $fudml);
		} else {
			$fudml = str_replace($res[0], '[wikipedia]'. $res[2] .'[/wikipedia]', $fudml);
		}
	}

	// Quote tags.
	if (strpos($fudml, '<cite>') !== false) {
               $fudml = str_replace(array('<cite>','</cite><blockquote>','</blockquote>'), array('[quote title=', ']', '[/quote]'), $fudml);
	}
	// Old bad quote tags.
	if (preg_match('!class="quote"!', $fudml)) { 
		$fudml = preg_replace('!<table border="0" align="center" width="90%" cellpadding="3" cellspacing="1">(<tbody>)?<tr><td class="SmallText"><b>!', '[quote title=', $fudml);
		$fudml = preg_replace('!</b></td></tr><tr><td class="quote">(<br>)?!', ']', $fudml);
		$fudml = preg_replace('!(<br>)?</td></tr>(</tbody>)?</table>!', '[/quote]', $fudml);
	}

	/* Spoiler tags. */	
	if (preg_match('!<div class="dashed" style="padding: 3px;" align="center"( width="100%")?><a href="javascript://" OnClick="javascript: layerVis\(\'.*?\', 1\);">.*?</a><div align="left" id="(.*?)" style="display: none;">!is', $fudml)) {
		$fudml = preg_replace('!\<div class\="dashed" style\="padding: 3px;" align\="center"( width\="100%")?\>\<a href\="javascript://" OnClick\="javascript: layerVis\(\'.*?\', 1\);">(.*?)\</a\>\<div align\="left" id\=".*?" style\="display: none;"\>!is', '[spoiler=\2]', $fudml);
		$fudml = str_replace('</div></div>', '[/spoiler]', $fudml);
	}
	/* Old bad spoiler format. */
	if (preg_match('!<div class="dashed" style="padding: 3px;" align="center" width="100%"><a href="javascript://" OnClick="javascript: layerVis\(\'.*?\', 1\);">.*?</a><div align="left" id="(.*?)" style="visibility: hidden;">!is', $fudml)) {
		$fudml = preg_replace('!\<div class\="dashed" style\="padding: 3px;" align\="center" width\="100%"\>\<a href\="javascript://" OnClick\="javascript: layerVis\(\'.*?\', 1\);">(.*?)\</a\>\<div align\="left" id\=".*?" style\="visibility: hidden;"\>!is', '[spoiler=\1]', $fudml);
		$fudml = str_replace('</div></div>', '[/spoiler]', $fudml);
	}

	// Color, font and size tags.
	$fudml = str_replace('<font face=', '<font font=', $fudml);
	foreach (array('color', 'font', 'size') as $v) {
		while (preg_match('!<font '. $v .'=".+?">.*?</font>!is', $fudml, $m)) {
			$fudml = preg_replace('!<font '. $v .'="(.+?)">(.*?)</font>!is', '['. $v .'=\1]\2[/'. $v .']', $fudml);
		}
	}

	// Acronym tags.
	while (preg_match('!<acronym title=".+?">.*?</acronym>!is', $fudml)) {
		$fudml = preg_replace('!<acronym title="(.+?)">(.*?)</acronym>!is', '[acronym=\1]\2[/acronym]', $fudml);
	}

	// List tags.
	while (preg_match('!<(o|u)l type=".+?">.*?</\\1l>!is', $fudml)) {
		$fudml = preg_replace('!<(o|u)l type="(.+?)">(.*?)</\\1l>!is', '[list type=\2]\3[/list]', $fudml);
	}

	$fudml = str_replace(
	array(
		'<b>', '</b>', '<i>', '</i>', '<u>', '</u>', '<s>', '</s>', '<sub>', '</sub>', '<sup>', '</sup>', '<del>', '</del>',
		'<div class="pre"><pre>', '</pre></div>', '<div align="center">', '<div align="left">', '<div align="right">', '</div>',
		'<ul>', '</ul>', '<span name="notag">', '</span>', '<li>', '&#64;', '&#58;&#47;&#47;', '<br />', '<pre>', '</pre>','<hr>',
		'<h1>', '</h1>', '<h2>', '</h2>', '<h3>', '</h3>', '<h4>', '</h4>'
	),
	array(
		'[b]', '[/b]', '[i]', '[/i]', '[u]', '[/u]', '[s]', '[/s]', '[sub]', '[/sub]', '[sup]', '[/sup]', '[del]', '[/del]', 
		'[code]', '[/code]', '[align=center]', '[align=left]', '[align=right]', '[/align]', '[list]', '[/list]',
		'[notag]', '[/notag]', '[*]', '@', '://', '', '[pre]', '[/pre]','[hr]',
		'[h1]', '[/h1]', '[h2]', '[/h2]', '[h3]', '[/h3]', '[h4]', '[/h4]'
	),
	$fudml);

	while (preg_match('!<img src="(.*?)" border="?0"? alt="\\1" ?/?>!is', $fudml)) {
                $fudml = preg_replace('!<img src="(.*?)" border="?0"? alt="\\1" ?/?>!is', '[img]\1[/img]', $fudml);
	}
	while (preg_match('!<img class="(r|l)" src="(.*?)" border="?0"? alt="\\2" ?/?>!is', $fudml)) {
                $fudml = preg_replace('!<img class="(r|l)" src="(.*?)" border="?0"? alt="\\2" ?/?>!is', '[img\1]\2[/img\1]', $fudml);
	}
	while (preg_match('!<a href="mailto:(.+?)"( target="_blank")?>\\1</a>!is', $fudml)) {
		$fudml = preg_replace('!<a href="mailto:(.+?)"( target="_blank")?>\\1</a>!is', '[email]\1[/email]', $fudml);
	}
	while (preg_match('!<a href="(.+?)"( target="_blank")?>\\1</a>!is', $fudml)) {
		$fudml = preg_replace('!<a href="(.+?)"( target="_blank")?>\\1</a>!is', '[url]\1[/url]', $fudml);
	}

	if (strpos($fudml, '<img src="') !== false) {
                $fudml = preg_replace('!<img src="(.*?)" border="?0"? alt="(.*?)" ?/?>!is', '[img=\1]\2[/img]', $fudml);
	}
	if (strpos($fudml, '<img class="') !== false) {
                $fudml = preg_replace('!<img class="(r|l)" src="(.*?)" border="?0"? alt="(.*?)" ?/?>!is', '[img\1=\2]\3[/img\1]', $fudml);
	}
	if (strpos($fudml, '<a href="mailto:') !== false) {
		$fudml = preg_replace('!<a href="mailto:(.+?)"( target="_blank")?>(.+?)</a>!is', '[email=\1]\3[/email]', $fudml);
	}
	if (strpos($fudml, '<a href="') !== false) {
		$fudml = preg_replace('!<a href="(.+?)"( target="_blank")?>(.+?)</a>!is', '[url=\1]\3[/url]', $fudml);
	}

	if (isset($php)) {
		$fudml = str_replace(array_keys($php), array_values($php), $fudml);
	}

	/* Un-htmlspecialchars. */
	return reverse_fmt($fudml);
}

function filter_ext($file_name)
{
	include $GLOBALS['FORUM_SETTINGS_PATH'] .'file_filter_regexp';
	if (empty($GLOBALS['__FUD_EXT_FILER__'])) {
		return;
	}
	if (($p = strrpos($file_name, '.')) === false) {
		return 1;
	}
	return !in_array(strtolower(substr($file_name, ($p + 1))), $GLOBALS['__FUD_EXT_FILER__']);
}

function reverse_nl2br($data)
{
	if (strpos($data, '<br />') !== false) {
		return str_replace('<br />', '', $data);
	}
	return $data;
}$GLOBALS['__SML_CHR_CHK__'] = array("\n"=>1, "\r"=>1, "\t"=>1, ' '=>1, ']'=>1, '['=>1, '<'=>1, '>'=>1, '\''=>1, '"'=>1, '('=>1, ')'=>1, '.'=>1, ','=>1, '!'=>1, '?'=>1);

function smiley_to_post($text)
{
	$text_l = strtolower($text);
	include $GLOBALS['FORUM_SETTINGS_PATH'] .'sp_cache';

	/* remove all non-formatting blocks */
	foreach (array('</pre>'=>'<pre>', '</span>' => '<span name="php">') as $k => $v) {
		$p = 0;
		while (($p = strpos($text_l, $v, $p)) !== false) {
			if (($e = strpos($text_l, $k, $p)) === false) {
				$p += 5;
				continue;
			}
			$text_l = substr_replace($text_l, str_repeat(' ', $e - $p), $p, ($e - $p));
			$p = $e;
		}
	}

	foreach ($SML_REPL as $k => $v) {
		$a = 0;
		$len = strlen($k);
		while (($a = strpos($text_l, $k, $a)) !== false) {
			if ((!$a || isset($GLOBALS['__SML_CHR_CHK__'][$text_l[$a - 1]])) && ((@$ch = $text_l[$a + $len]) == '' || isset($GLOBALS['__SML_CHR_CHK__'][$ch]))) {
				$text_l = substr_replace($text_l, $v, $a, $len);
				$text = substr_replace($text, $v, $a, $len);
				$a += strlen($v) - $len;
			} else {
				$a += $len;
			}
		}
	}

	return $text;
}

function post_to_smiley($text)
{
	/* include once since draw_post_smiley_cntrl() may use it too */
	include_once $GLOBALS['FORUM_SETTINGS_PATH'].'ps_cache';
	if (isset($PS_SRC)) {
		$GLOBALS['PS_SRC'] = $PS_SRC;
		$GLOBALS['PS_DST'] = $PS_DST;
	} else {
		$PS_SRC = $GLOBALS['PS_SRC'];
		$PS_DST = $GLOBALS['PS_DST'];
	}

	/* check for emoticons */
	foreach ($PS_SRC as $k => $v) {
		if (strpos($text, $v) === false) {
			unset($PS_SRC[$k], $PS_DST[$k]);
		}
	}

	return $PS_SRC ? str_replace($PS_SRC, $PS_DST, $text) : $text;
}function tmpl_draw_select_opt($values, $names, $selected)
{
	$vls = explode("\n", $values);
	$nms = explode("\n", $names);

	if (count($vls) != count($nms)) {
		exit("FATAL ERROR: inconsistent number of values inside a select<br />\n");
	}

	$options = '';
	foreach ($vls as $k => $v) {
		$options .= '<option value="'.$v.'"'.($v == $selected ? ' selected="selected"' : '' )  .'>'.$nms[$k].'</option>';
	}

	return $options;
}/* Replace and censor text before it's stored. */
function apply_custom_replace($text)
{
	defined('__fud_replace_init') or make_replace_array();
	if (empty($GLOBALS['__FUD_REPL__'])) {
		return $text;
	}

	return preg_replace($GLOBALS['__FUD_REPL__']['pattern'], $GLOBALS['__FUD_REPL__']['replace'], $text);
}

function make_replace_array()
{
	$GLOBALS['__FUD_REPL__']['pattern'] = $GLOBALS['__FUD_REPL__']['replace'] = array();
	$a =& $GLOBALS['__FUD_REPL__']['pattern'];
	$b =& $GLOBALS['__FUD_REPL__']['replace'];

	$c = uq('SELECT with_str, replace_str FROM fud30_replace WHERE replace_str IS NOT NULL AND with_str IS NOT NULL AND LENGTH(replace_str)>0');
	while ($r = db_rowarr($c)) {
		$a[] = $r[1];
		$b[] = $r[0];
	}
	unset($c);

	define('__fud_replace_init', 1);
}

/* Reverse replacement and censorship of text. */
function apply_reverse_replace($text)
{
	defined('__fud_replacer_init') or make_reverse_replace_array();
	if (empty($GLOBALS['__FUD_REPLR__'])) {
		return $text;
	}
	return preg_replace($GLOBALS['__FUD_REPLR__']['pattern'], $GLOBALS['__FUD_REPLR__']['replace'], $text);
}

function make_reverse_replace_array()
{
	$GLOBALS['__FUD_REPLR__']['pattern'] = $GLOBALS['__FUD_REPLR__']['replace'] = array();
	$a =& $GLOBALS['__FUD_REPLR__']['pattern'];
	$b =& $GLOBALS['__FUD_REPLR__']['replace'];

	$c = uq('SELECT replace_opt, with_str, replace_str, from_post, to_msg FROM fud30_replace');
	while ($r = db_rowarr($c)) {
		if (!$r[0]) {
			$a[] = $r[3];
			$b[] = $r[4];
		} else if ($r[0] && strlen($r[1]) && strlen($r[2])) {
			$a[] = '/'.str_replace('/', '\\/', preg_quote(stripslashes($r[1]))).'/';
			preg_match('/\/(.+)\/(.*)/', $r[2], $regs);
			$b[] = str_replace('\\/', '/', $regs[1]);
		}
	}
	unset($c);

	define('__fud_replacer_init', 1);
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
}

	if (isset($_GET['frm_id'])) {
		$frm_id = (int) $_GET['frm_id'];
	} else if (isset($_POST['frm_id'])) {
		$frm_id = (int) $_POST['frm_id'];
	} else {
		invl_inp_err();
	}

	if (isset($_GET['pl_id'])) {
		$pl_id = (int) $_GET['pl_id'];
	} else if (isset($_POST['pl_id'])) {
		$pl_id = (int) $_POST['pl_id'];
	} else {
		$pl_id = 0;
	}

	make_perms_query($fields, $join, $frm_id);

	/* Fetch forum, poll & moderator data. */
	if (!$pl_id) { /* New poll. */
		$frm = db_sab('SELECT f.id, f.forum_opt, m.id AS md, '. $fields .'
			FROM fud30_forum f
			LEFT JOIN fud30_mod m ON m.user_id='. _uid .' AND m.forum_id=f.id
			'. $join .'
			WHERE f.id='. $frm_id);
	} else { /* Editing a poll. */
		$frm = db_sab('SELECT f.id, f.forum_opt, m.id AS md, ms.id AS old_poll, p.id AS poll_id, p.*, '. $fields .'
			FROM fud30_forum f
			INNER JOIN fud30_poll p ON p.id='. $pl_id .'
			LEFT JOIN fud30_mod m ON m.user_id='. _uid .' AND m.forum_id=f.id
			LEFT JOIN fud30_msg ms ON ms.poll_id=p.id
			'. $join .'
			WHERE f.id='. $frm_id);
	}

	if ($frm) {
		$frm->group_cache_opt = (int)$frm->group_cache_opt;
		$frm->forum_opt = (int)$frm->forum_opt;
	}

	if (!$frm || (!$frm->md && !$is_a && (!empty($frm->old_poll) && (!($frm->group_cache_opt & 4096) || (!($frm->group_cache_opt & 16) && $frm->owner != _uid))) && !($frm->group_cache_opt & 4))) {
		std_error('access');
	}

	if (isset($_POST['pl_submit']) && !empty($_POST['pl_name'])) {
		$pl_name = isset($_POST['pl_name']) ? (string) $_POST['pl_name'] : '';
		$pl_max_votes = isset($_POST['pl_max_votes']) ? (int) $_POST['pl_max_votes'] : 0;
		$pl_expiry_date = isset($_POST['pl_expiry_date']) ? (int) $_POST['pl_expiry_date'] : 0;
		if ($pl_id) { /* Update a poll. */
			poll_sync($pl_id, $pl_name, $pl_max_votes, $pl_expiry_date);
		} else { /* Adding a new poll. */
			$pl_id = poll_add($pl_name, $pl_max_votes, $pl_expiry_date);
		}
	} else if (!empty($frm->poll_id)) {
		$pl_name = reverse_fmt($frm->name);
		$pl_max_votes = $frm->max_votes;
		$pl_expiry_date = $frm->expiry_date;
	} else {
		$pl_name = $pl_max_votes = $pl_expiry_date = '';
	}

	/* Remove a poll option. */
	if (isset($_GET['del_id'])) {
		poll_del_opt((int)$_GET['del_id'], $pl_id);
	}

	/* Adding or Updating poll options. */
	if(!empty($_POST['pl_upd']) || !empty($_POST['pl_add'])) {
		$pl_option = apply_custom_replace($_POST['pl_option']);

		if ($frm->forum_opt & 16) {
			$pl_option = tags_to_html($pl_option, $frm->group_cache_opt & 32768);
		} else if ($frm->forum_opt & 8) {
			$pl_option = nl2br(htmlspecialchars($pl_option));
		}

		if ($frm->group_cache_opt & 16384 && !isset($_POST['pl_smiley_disabled'])) {
			$pl_option = smiley_to_post($pl_option);
		}

		if (isset($_POST['pl_upd'], $_POST['pl_option_id'])) {
			poll_opt_sync((int)$_POST['pl_option_id'], $pl_option);
		} else {
			poll_opt_add($pl_option, $pl_id);
		}
	}
	$pl_option = '';

	/* If we have a poll, fetch poll options. */
	$poll_opts = $pl_id ? poll_fetch_opts($pl_id) : array();

	/* Edit a poll option. */
	if (isset($_GET['pl_optedit'], $poll_opts[$_GET['pl_optedit']])) {
		$pl_option = $poll_opts[$_GET['pl_optedit']];
		$pl_option_id = $_GET['pl_optedit'];
	}

	$TITLE_EXTRA = ': Stemming bewerken';



	$pl_expiry_date_data = tmpl_draw_select_opt("0\n3600\n21600\n43200\n86400\n259200\n604800\n2635200\n31536000", "Onbeperkt\n1 uur\n6 uur\n12 uur\n1 dag\n3 dagen\n1 week\n1 maand\n1 jaar", $pl_expiry_date);
	$pl_max_votes_data = tmpl_draw_select_opt("0\n10\n50\n100\n200\n500\n1000\n10000\n100000", "Onbeperkt\n10\n50\n100\n200\n500\n1000\n10000\n100000", $pl_max_votes);

	if ($frm->group_cache_opt & 16384) {
		$checked = isset($_POST['pl_smiley_disabled']) ? ' checked' : '';
		$pl_smiley_disabled_chk = '<tr>
	<td align="right" valign="top" colspan="2" class="GenText"><label><input type="checkbox" name="pl_smiley_disabled" value="Y" checked="'.$checked.'" />Smiley&#39;s uitschakelen</label></td>
</tr>';
	} else {
		$pl_smiley_disabled_chk = '';
	}

	/* This is only available on a created poll. */
	if ($pl_id) {
		if ($pl_option) {
			$pl_option = post_to_smiley($pl_option);

			if ($frm->forum_opt & 16) {
				$pl_option = html_to_tags($pl_option);
			} else if ($frm->forum_opt & 8) {
				$pl_option = reverse_nl2br($pl_option);
			}

			$pl_option = apply_reverse_replace($pl_option);
		} else {
			$pl_option = '';
		}

		$poll_option_entry_data = '';
		foreach ($poll_opts as $k => $v) {
			$poll_option_entry_data .= '<tr>
	<td class="GenText">'.$v.'</td>
	<td class="nw">
		[<a href="index.php?t=poll&amp;frm_id='.$frm_id.'&amp;'._rsid.'&amp;pl_id='.$pl_id.'&amp;pl_optedit='.$k.'">Bewerken</a>]
		[<a href="index.php?t=poll&amp;frm_id='.$frm_id.'&amp;pl_id='.$pl_id.'&amp;del_id='.$k.'&amp;'._rsid.'">Verwijderen</a>]
	</td>
</tr>';
		}

		$poll_editor = '<table width="99%" cellspacing="2" cellpadding="0" class="dashed">
<tr>
	<td class="GenText">Optie toevoegen:</td>
	<td class="ar"><input type="text" spellcheck="true" name="pl_option" value="'.htmlspecialchars($pl_option).'" />
	'.$pl_smiley_disabled_chk.'
<tr>
	<td colspan="2" class="ar">'.(!isset($_GET['pl_optedit']) ? '<input type="submit" class="button" name="pl_add" onclick="return check_submission();" value="Optie toevoegen" />' : '<input type="hidden" name="pl_option_id" value="'.$pl_option_id.'" />
<input type="submit" class="button" name="pl_upd" onclick="return check_submission();" value="Update Optie" />' ) .'</td>
</tr>
<tr>
	<td colspan="2">
		<table>
			'.$poll_option_entry_data.'
		</table>
	</td>
</tr>
</table>';
	} else {
		$poll_editor = '';
	}


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
</script>
<form action="index.php?t=poll" method="post" id="poll_submit">
<input type="hidden" name="frm_id" value="<?php echo $frm_id; ?>" />
<input type="hidden" name="pl_id" value="<?php echo $pl_id; ?>" />
<?php echo _hs; ?>
<table cellspacing="2" width="99%" cellpadding="0" class="dashed">
<tr>
	<td class="GenText">Stemmingsnaam:</td>
	<td><input tabindex="1" type="text" spellcheck="true" name="pl_name" value="<?php echo htmlspecialchars($pl_name); ?>" /></td>
</tr>
<tr>
	<td class="GenText">Tijdslimiet:</td>
	<td><select tabindex="2" name="pl_expiry_date"><?php echo $pl_expiry_date_data; ?></select></td>
</tr>
<tr>
	<td class="GenText">Maximum aantal stemmen:</td>
	<td><select tabindex="3" name="pl_max_votes"><?php echo $pl_max_votes_data; ?></select></td>
</tr>
<tr>
	<td colspan="2" class="ar"><?php echo (!$pl_id ? '<input tabindex="5" type="submit" class="button" name="pl_submit" value="Stemming aanmaken" />' : '<input tabindex="5" type="submit" class="button" name="pl_submit" value="Stemming bijwerken" />'); ?></td>
</tr>
</table>
<br />
<?php echo $poll_editor; ?>
<br />
<div class="ar">
<?php echo (!$pl_id ? '<input type="button" tabindex="4" class="button" onclick="window.opener.document.forms[\'post_form\'].pl_id.value=\'0\'; window.opener.document.forms[\'post_form\'].submit(); window.close();" value="Aanmaken" />' : '<input type="button" tabindex="4" class="button" onclick="window.opener.document.forms[\'post_form\'].pl_id.value='.$pl_id.'; window.opener.document.forms[\'post_form\'].submit(); window.close();" value="Bijwerken" />'); ?>
</div>
</form>
<script>
function check_submission()
{
	var a;

	a = document.forms['poll_submit'].pl_option.value.replace('/[ \n\r]/g', '');

	if (!a.length) {
		if (document.forms['poll_submit'].pl_add && document.forms['poll_submit'].pl_add.value.length) {
			alert("U kunt geen lege optie toevoegen.");
			return false;
		} else if (document.forms['poll_submit'].pl_upd && document.forms['poll_submit'].pl_upd.value.length) {
			return confirm("Klik OK om deze optie te verwijderen");
		}
	}
	return true;
}

if (document.forms['poll_submit'].pl_option) {
	document.forms['poll_submit'].pl_option.focus();
} else {
	document.forms['poll_submit'].pl_name.focus();
}
</script>
</div>
</body></html>