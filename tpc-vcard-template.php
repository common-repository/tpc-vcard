<?php
/**
 * TPC! vCard Template API
 * 
 * @package TPC_vCard
 */

function add_vcard_page($page_title, $menu_title, $access_level, $file, $function = '') {
	add_submenu_page(TPC_VCARD_FOLDER, $page_title, $menu_title, $access_level, $file, $function);
}

/**
 * Parse a vCard for a preview.
 * 
 * @param $cards
 * @return void
 */
function tpc_vcard_preview(&$cards) {
	if ( !$cards )
		return false;
	
	foreach ( $cards as $card_name => $card ) {
		//var_dump($card);
		
		$split = split(", ", $card_name);
		$username = strtolower($split[1][0] . $split[0]);
		$email = $card->getProperty('EMAIL')->value;
		$url = $card->getProperty('URL')->value;
		
		_tpc_vcard_preview($username, $email, $url);
	}
}

/**
 * Display step two in vCard import process or complete finalization and display result.
 * 
 * @param $username User login.
 * @param $email User e-mail.
 * @param $url User URL.
 * @param $completed Whether or not step two was completed. Defaults to false.
 */
function _tpc_vcard_preview($username, $email, $url, $completed = false) {
	if ( !$completed ) {
		$header = esc_html__('Step 2: Confirm New User');
	} else {
		$header = esc_html__('vCard Import Results');
		
		$userdata = array(
			'user_login' => $username,
			'user_pass' => wp_generate_password(8),
			'user_email' => $email,
			'user_url' => $url );
		
		$vcard_user_id = wp_insert_user($userdata);
		wp_new_user_notification($vcard_user_id, $_POST['tpc_vcard_send_email'] ? $userdata['user_pass'] : '');
		
		if ( $vcard_user_id ) {
			$message = 'User #' . $vcard_user_id . ' successfully created';
		} else {
			$message = 'Failed to create user';
		}
	}
?>

<div class="tpc-vcard-import-form">
	<?php if ( !$completed ) { ?><form action="" method="post"><?php } ?>
		<table class="form-table" cellspacing="0">
		<tr>
			<th colspan="2"><h3><?php echo $header; ?></h3></th>
		</tr>
		<tr>
			<th scope="col" width="100"><label for="tpc_vcard_username"><?php _e('Username'); ?>:</label></th>
			<td><input type="text" name="tpc_vcard_username" id="tpc_vcard_username" value="<?php echo esc_attr($username); ?>" size="30" /></td>
		</tr>
		<tr>
			<th><label for="tpc_vcard_email"><?php _e('E-mail'); ?>:</label></th>
			<td><input type="text" name="tpc_vcard_email" id="tpc_vcard_email" value="<?php echo esc_attr($email); ?>" size="30" /></td>
		</tr>
		<tr>
			<th><label for="tpc_vcard_url"><?php _e('URL'); ?>:</label></th>
			<td><input type="text" name="tpc_vcard_url" id="tpc_vcard_url" value="<?php echo esc_attr($url); ?>" size="65" /></td>
		</tr>
		<?php if ( !$completed ): ?>
		<tr>
			<th><?php _e('Send Password?'); ?></th>
			<td>
				<input type="checkbox" name="tpc_vcard_send_email" id="tpc_vcard_send_email" value="1" checked="checked" /> 
				<label for="tpc_vcard_send_email"><?php _e('Send this password to the new user by e-mail.'); ?></label>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<input type="submit" name="tpc_vcard_finalize" id="tpc_vcard_finalize" value="<?php echo esc_attr('Finalize'); ?>" />
			</td>
		</tr>
		<?php else: ?>
		<tr>
			<th><strong><?php _e('Result'); ?>:</strong></th>
			<td><?php esc_html_e($message); ?></td>
		</tr>
		<?php endif; ?>
		</table>
	<?php if ( !$completed ) { ?></form><?php } ?>
</div>

<br/>

<?php
}

/**
 * Display TPC! vCard error message.
 * 
 * @param string $err The error code.
 */
function tpc_vcard_error($err) {
	
	switch ( $err ) {
		case 'no_file_selected':
			$message = 'No file selected. Please select a file and try again.';
			break;
		
		case 'invalid_extension':
			$message = 'Invalid file format. VCF (vCard format) is the only allowed file extension. Please select a different file and try again.';
			break;
		
		case 'upload_error':
			$message = 'Error uploading selected file.';
			break;
		
		case 'empty_username':
			$message = 'Cannot create user without a username.';
			break;
		
		case 'empty_email':
			$message = 'Cannot create e-mail without an e-mail address.';
			break;
		
		default:
			$message = 'Unknown error.';
			break;
	}
	
	echo '<div id="message" class="error"><p>';
	echo __( $message );
	echo "</p></div>\n";
}

?>