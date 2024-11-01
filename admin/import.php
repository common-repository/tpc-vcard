<?php
/**
 * TPC! vCard Import File
 * 
 * @package TPC_vCard
 */
$hide_form = tpc_vcard_import();
?>

<div class="wrap">
<?php screen_icon('options-general'); ?>
<h2><?php echo esc_html( $title ); ?></h2>

<br/>

<?php do_action('tpc_vcard_import_view'); ?>

<?php if ( !$hide_form ) : ?>

<div class="tpc-vcard-import-form">
	<form enctype="multipart/form-data" action="" method="post">
	<table class="form-table" cellspacing="0">
		<tr>
			<th><h3><?php _e('Step 1: Upload vCard'); ?></h3></th>
		</tr>
		<tr>
			<td>
				<label for="tpc_vcard_file"><?php esc_html_e('vCard File'); ?>:</label>
				<input type="file" name="tpc_vcard_file" id="tpc_vcard_file" />
				
				<p>
				<input type="submit" id="tpc_vcard_import_form" name="tpc_vcard_import_form" value="<?php esc_attr_e('Process vCard'); ?>" />
				</p>
			</td>
		</tr>
	</table>
	</form>
</div>

<?php endif; ?>

</div>