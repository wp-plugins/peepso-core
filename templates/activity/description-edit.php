<div class="ps-postbox clearfix cstream-edit">
	<div class="ps-postbox-content">
		<div class="ps-postbox-status">
			<div style="position:relative">
				<div class="ps-postbox-input ps-inputbox">
					<textarea class="ps-textarea ps-postbox-textarea"><?php echo esc_textarea($cont); ?></textarea>
				</div>
			</div>
		</div>
	</div>
	<nav class="ps-postbox-tab selected">
		<ul class="ps-list-inline">
			<li class="ps-list-item"><a>&nbsp;</a></li>
		</ul>
		<div class="ps-postbox-action" style="display: block;">
			<button class="ps-btn ps-btn-small ps-button-cancel" onclick="return activity.option_cancel_edit_description(<?php echo $act_id; ?>);"><?php _e('Cancel', 'peepso'); ?></button>
			<button class="ps-btn ps-btn-small ps-button-action" onclick="return activity.option_save_description(<?php echo $act_id; ?>, '<?php echo $type; ?>', <?php echo $act_external_id; ?>);"><?php _e('Save', 'peepso'); ?></button>
		</div>
		<div class="ps-edit-loading" style="display: none;">
			<img src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>">
			<div> </div>
		</div>
	</nav>
</div>