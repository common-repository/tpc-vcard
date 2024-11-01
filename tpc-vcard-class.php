<?php
/**
 * TPC! vCard
 * 
 * @package TPC_vCard
 */

class tpc_vcard {
	var $data;
	
	function parse(&$lines) {
		// Reset vCard $data
		$this->data = null;
		
		$property = new tpc_vcard_template();
		
		while ( $property->parse($lines) ) {
			if ( is_null($this->data) ) {
				if ( $property->name == 'BEGIN' )
					$this->data = array();
			} else {
				if ( $property->name == 'END' )
					break;
				else
					$this->data[$property->name][] = $property;
			}
			
			// Create new property to prevent overwriting previous one
			$property = new tpc_vcard_template();
		}
		return $this->data != null;
	}
	
	/**
	 * Returns the first property mapped to the specified name or null if
	 * there are no properties with that name.
	 * 
	 * @return unknown_type
	 */
	function getProperty($name) {
		return $this->data[$name][0];
	}
	
	/**
	 * Returns the properties mapped to the specified name or null if there
	 * are no properties with that name.
	 * 
	 * @return unknown_type
	 */
	function getProperties($name) {
		return $this->data[$name];
	}
	
	/**
	 * Returns an array of categories for this card or a one-element array 
	 * with the value 'Unfiled' if no CATEGORIES property is found.
	 * 
	 * @return array
	 */
	function getCategories() {
		$property = $this->getProperty('CATEGORIES');
		// The Mac OS X Address Book application uses the CATEGORY property
		// instead of the CATEGORIES property.
		if ( !$property )
			$property = $this->getProperty('CATEGORY');
		
		$result = $property ? $property->getComponents(',') : array('Unfiled');
		$result[] = "All"; // each card is always a member of "All"
		
		return $result;
	}
	
	/**
	 * Check if the vCard belongs to at least one of the categories.
	 * 
	 * @return bool True if the card belongs to at least one category.
	 */
	function inCategories(&$categories) {
		$our_categories = $this->getCategories();
		foreach ( $categories as $category ) {
			if ( in_array_case($category, $our_categories) ) {
				return true;
			}
		}
		return false;
	}
}

class tpc_vcard_template {
	var $name;
	var $params;
	var $value;
	
	/**
	 * Parses a vCard property from one or more lines. Lines that are not
	 * property lines, such as blank lines are skipped.
	 * 
	 * @param $lines
	 * @return bool Returns false if there are no more lines to be parsed.
	 */
	function parse(&$lines) {
		while ( list(, $line) = each($lines) ) {
			$line = rtrim($line);
			$tmp = split_quoted_string(":", $line, 2);
			if ( count($tmp) == 2 ) {
				$this->value = $tmp[1];
				$tmp = strtoupper($tmp[0]);
				$tmp = split_quoted_string(";", $tmp);
				$this->name = $tmp[0];
				$this->params = array();
				
				for ( $i = 1; $i < count($tmp); $i++ ) {
					$this->_parseParam($tmp[$i]);
				}
				
				if ( $this->params['ENCODING'][0] == 'QUOTED-PRINTABLE' ) {
					$this->_decodeQuotedPrintable($lines);
				}
				
				if ( $this->params['CHARSET'][0] == 'UTF-8' ) {
					$this->value = utf8_decode($this->value);
				}
				
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Splits the value on unescaped delimiter characters.
	 * 
	 * @param string $delim The delimiter.
	 * @return unknown_value
	 */
	function getComponents($delim = ";") {
		$value = $this->value;
		// Save escaped delimiters
		$value = str_replace("\\$delim", "\x00", $value);
		// Tag unescaped delimiters
		$value = str_replace("$delim", "\x01", $value);
		// Restore the escaped delimiters.
		$value = str_replace("\x00", "$delim", $value);
		// Split the line on the delimiter tag
		return explode("\x01", $value);
	}
	
	/**
	 * Parses a parameter string where the parameter string is either
	 * in the form "name=value[,value...]" such as "TYPE=WORK,CELL" or
	 * is a vCard 2.1 parameter such as "WORK" in which case the 
	 * parameter name is determined from the parameter value.
	 * 
	 * @param $param
	 */
	function _parseParam($param) {
		$tmp = split_quoted_string('=', $param, 2);
		if ( count($tmp) == 1 ) {
			$value = $tmp[0];
			$name = $this->_paramName($value);
			$this->params[$name][] = $value;
		} else {
			$name = $tmp[0];
			$values = split_quoted_string(',', $tmp[1]);
			foreach ( (array)$values as $value ) {
				$this->params[$name][] = $value;
			}
		}
	}
	
	/**
	 * The vCard 2.1 specification allows parameter values without a
	 * name. The parameter name is then determined from the unique
	 * parameter value.
	 * 
	 * @param $value
	 */
	function _paramName($value) {
		static $types = array(
			'DOM', 'INTL', 'POSTAL', 'PARCEL','HOME', 'WORK',
			'PREF', 'VOICE', 'FAX', 'MSG', 'CELL', 'PAGER',
			'BBS', 'MODEM', 'CAR', 'ISDN', 'VIDEO',
			'AOL', 'APPLELINK', 'ATTMAIL', 'CIS', 'EWORLD',
			'INTERNET', 'IBMMAIL', 'MCIMAIL',
			'POWERSHARE', 'PRODIGY', 'TLX', 'X400',
			'GIF', 'CGM', 'WMF', 'BMP', 'MET', 'PMB', 'DIB',
			'PICT', 'TIFF', 'PDF', 'PS', 'JPEG', 'QTIME',
			'MPEG', 'MPEG2', 'AVI',
			'WAVE', 'AIFF', 'PCM',
			'X509', 'PGP');
		static $values = array('INLINE', 'URL', 'CID');
		static $encodings = array('7BIT', 'QUOTED-PRINTABLE', 'BASE64');
		
		$name = 'UNKNOWN';
		if ( in_array($value, $types) ) {
			$name = 'TYPE';
		} elseif ( in_array($value, $values) ) {
			$name = 'VALUE';
		} elseif ( in_array($value, $encodings) ) {
			$name = 'ENCODING';
		}
		
		return $name;
	}
	
	/**
	 * Decodes a quoted printable value spanning multiple lines.
	 * 
	 * @param $lines
	 */
	function _decodeQuotedPrintable(&$lines) {
		$value = &$this->value;
		while ( $value[strlen($value) - 1] == "=" ) {
			$value = substr($value, 0, strlen($value) - 1);
			if ( !(list(, $line) = each ($lines)) )
				break;
			$value .= rtrim($line);
		}
		$value = quoted_printable_decode($value);
	}
}

class tpc_vcard_admin_panel {
	/**
	 * PHP5-Style Constructor
	 * 
	 * @return void
	 */
	function __construct() {
		// Add the admin menu
		add_action('admin_menu', array(&$this, 'add_menu'));
		add_action('admin_print_styles', array(&$this, 'load_styles'));
	}
	
	/**
	 * Integrates the menu into the admin panel.
	 * 
	 * @uses add_menu_page()
	 * @uses add_submenu_page()
	 */
	function add_menu() {
		add_menu_page( __('vCard'), __('vCard'), 'edit_users', TPC_VCARD_FOLDER, array(&$this, 'show_menu'), '' );
		add_vcard_page( __('TPC! vCard: Overview'), __('Overview'), 'edit_users', 'tpc-vcard', array(&$this, 'show_menu') );
		add_vcard_page( __('TPC! vCard: Import'), __('Import'), 'create_users', 'tpc-vcard-import', array(&$this, 'show_menu') );
		//add_vcard_page( __('TPC! vCard: Export'), __('Export'), 'edit_users', 'tpc-vcard-export', array(&$this, 'show_menu') );
	}
	
	/**
	 * The 'bootstrap' for loading TPC! vCard admin pages.
	 * 
	 * @return void
	 */
	function show_menu() {
		global $title;
		
		$title = get_admin_page_title();
		
		switch ( $_GET['page'] ) {
			case 'tpc-vcard':
				require_once( dirname(__FILE__) . '/admin/overview.php' );
				break;
			case 'tpc-vcard-import':
				require_once( dirname(__FILE__) . '/admin/import.php' );
				break;
			case 'tpc-vcard-export':
				echo dirname(__FILE__) . '/admin/export.php';
				break;
			case 'tpc-vcard':
			default:
				echo dirname(__FILE__) . '/admin/overview.php';
				break;
		}
	}
	
	function load_styles() {
		if ( !isset($_GET['page']) )
			return;
		
		switch ( $_GET['page'] ) {
			case 'tpc-vcard-import':
				wp_enqueue_style('tpc-vcard', TPC_VCARD_URLPATH . 'admin/tpc-vcard.css');
				break;
		}
	}
	
	/**
	 * PHP4 Compatibility
	 * 
	 * @return void
	 */
	function tpc_vcard_admin_panel() {
		$this->__construct();
	}
}

?>