<div class="ps-comment-edit cstream-edit">
	<div class="ps-textarea-wrapper cstream-form-input">
		<textarea class="ps-textarea cstream-form-text" placeholder="<?php _e('Write a comment...', 'peepso');?>"><?php echo $cont;?></textarea>
	</div>
	<button class="ps-btn ps-btn-small ps-button-cancel" onclick="return activity.option_canceleditcomment(<?php echo $post_id;?>, this);"><?php _e('Cancel', 'peepso'); ?></button>
	<button class="ps-btn ps-btn-small ps-button-action" onclick="return activity.option_savecomment(<?php echo $post_id; ?>, this);"><?php _e('Save', 'peepso'); ?></button>
	<div class="ps-edit-loading" style="display:none;">
		<img src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>" alt="" />
		<div> </div>
	</div>
</div>