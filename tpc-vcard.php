<?php
/*
Plugin Name: TPC! vCard
Plugin URI: http://webjawns.com/tpc-vcard-wordpress-plugin/
Description: TPC! vCard allows WordPress administrators to import vCards and create new users from that information.
Version: 0.1
Author: Chris Strosser
Author URI: http://webjawns.com/
*/

// Prevent direct calls to this file
if ( !function_exists('add_action') ) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

// Settings
define('TPC_VCARD_FOLDER', plugin_basename( dirname(__FILE__) ));
define('TPC_VCARD_URLPATH', WP_PLUGIN_URL . '/' . TPC_VCARD_FOLDER . '/');

require_once('tpc-vcard-class.php');
require_once('tpc-vcard-functions.php');
require_once('tpc-vcard-template.php');

// Register hooks and filters
add_action('init', 'tpc_vcard_init');
add_action('tpc_vcard_import_view', 'tpc_vcard_view');

?>