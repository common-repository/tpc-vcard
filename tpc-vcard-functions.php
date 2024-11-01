<?php
/**
 * Main TPC! vCard API
 * 
 * @package TPC_vCard
 */

function tpc_vcard_init() {
	global $tpc_vcard_admin_panel;
	global $vcard, $vcard_error;
	
	$tpc_vcard_admin_panel = new tpc_vcard_admin_panel();
}

/**
 * Processes any of the vCard import steps.
 * 
 * @return bool True if we don't want to show the upload portion of the form, false to show it.
 */
function tpc_vcard_import() {
	global $vcard, $vcard_error;
	
	$vcard_error = false;
	
	if ( $_POST['tpc_vcard_import_form'] ) {
		// Make sure a file was actually uploaded
		if ( !isset($_FILES['tpc_vcard_file']['tmp_name']) || trim($_FILES['tpc_vcard_file']['tmp_name']) == '' ) {
			$vcard_error = 'no_file_selected';
			return false;
		}
		
		// Make sure that this is a valid vCard file
		if ( !tpc_vcard_check_file($_FILES['tpc_vcard_file']) ) {
			$vcard_error = 'invalid_extension';
			return false;
		}
		
		// Store vCard in variable
		$vcard = file( $_FILES['tpc_vcard_file']['tmp_name'] );
		if ( !$vcard ) {
			$vcard_error = 'upload_error';
			return false;
		}
		
		// Parse the vCard
		$vcard = tpc_vcard_parse($vcard);
		
		return true;
	} elseif ( $_POST['tpc_vcard_finalize'] ) {
		$_POST['tpc_vcard_username'] = trim($_POST['tpc_vcard_username']);
		$_POST['tpc_vcard_email'] = trim($_POST['tpc_vcard_email']);
		
		// Username field is required!
		if ( empty($_POST['tpc_vcard_username']) )
			$vcard_error = 'empty_username';
		
		if ( empty($_POST['tpc_vcard_email']) )
			$vcard_error = 'empty_email';
		
		return false;
	}
	
	return false;
}

function tpc_vcard_view() {
	global $vcard, $vcard_error;
	
	if ( !$vcard_error ) {
		if ( $_POST['tpc_vcard_import_form'] ) {
			// Show confirmation page
			tpc_vcard_preview($vcard);
		} elseif ( $_POST['tpc_vcard_finalize'] ) {
			// Show last part of form... (creates user and shows results)
			_tpc_vcard_preview($_POST['tpc_vcard_username'], $_POST['tpc_vcard_email'], $_POST['tpc_vcard_url'], true);
		}
	} else {
		tpc_vcard_error( $vcard_error );
	}
}

function tpc_vcard_check_file($file) {
	$allowedExtensions = array('vcf');
	
	if ( $file['tmp_name'] > '' ) {
		if ( in_array( end(explode(".", strtolower($file['name']))), $allowedExtensions) ) {
			return true;
		}
	}
	
	return false;
}

function tpc_vcard_parse(&$lines) {
	$cards = array();
	$card = new tpc_vcard();
	
	while ( $card->parse($lines) ) {
		$property = $card->getProperty('N');
		if ( !$property )
			return '';
		$n = $property->getComponents();
		$tmp = array();
		if ($n[3]) $tmp[] = $n[3]; // Mr.
		if ($n[1]) $tmp[] = $n[1]; // John
		if ($n[2]) $tmp[] = $n[2]; // Doe
		if ($n[4]) $tmp[] = $n[4]; // Esq.
		$ret = array();
		if ($n[0]) $ret[] = $n[0];
		$tmp = join(" ", $tmp);
		if ($tmp) $ret[] = $tmp;
		$key = join(", ", $ret);
		$cards[$key] = $card;
		// Create new vCard to prevent overwriting previous one (PHP5)
		$card = new tpc_vcard();
	}
	ksort($cards);
	
	return $cards;
}

function tpc_vcard_categories(&$cards) {
	$unfiled = false; // set if there is at least one unfiled card
	$result = array();
	foreach ( (array)$cards as $card_name => $card ) {
		$properties = $card->getProperties('CATEGORIES');
		if ( $properties ) {
			foreach ( $properties as $property ) {
				$categories = $property->getComponents(',');
				foreach ( $categories as $category ) {
					if ( !in_array($category, $result) )
						$result[] = $category;
				}
			}
		} else {
			$unfiled = true;
		}
	}
	
	if ( $unfiled && !in_array('Unfiled', $result) )
		$result[] = 'Unfiled';
	
	return $result;
}

/**
 * Checks if needle $str is in haystack $arr while ignoring case.
 * 
 * @param string $str
 * @param array $arr
 */
function in_array_case($str, $arr) {
	foreach ( $arr as $s ) {
		if ( strcasecmp($str, $s) == 0 )
			return true;
	}
	return false;
}

/**
 * Splits a string. Similar to the split function but uses a single
 * character delimiter and ignores delimiters in double quotes.
 * 
 * @param $d
 * @param $s
 * @param $n Defaults to 0.
 */
function split_quoted_string($d, $s, $n = 0) {
	$quote = false;
	$len = strlen($s);
	for ( $i = 0; $i < $len && ($n == 0 || $n > 1); $i++ ) {
		$c = $s{$i};
		if ( $c == '"' ) {
			$quote = !$quote;
		} elseif ( !$quote && $c == $d ) {
			$s{$i} = "\x00";
			if ( $n > 0 )
				$n--;
		}
	}
	
	return explode("\x00", $s);
}

?>