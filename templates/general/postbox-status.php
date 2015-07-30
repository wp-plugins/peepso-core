<div class="ps-postbox-status">
	<div style="position:relative">
		<div style="position:absolute">
			<span class="ps-postbox-mirror"></span>
			<span class="ps-postbox-addons"></span>
		</div>
		<div class="ps-postbox-input ps-inputbox">
			<textarea class="ps-textarea ps-postbox-textarea" placeholder="<?php _e(apply_filters('peepso_postbox_message', 'Say what is on your mind...'), 'peepso'); ?>"></textarea>
		</div>
	</div>
	<div class="post-charcount charcount ps-postbox-charcount"><?php echo PeepSo::get_option('site_status_limit', 4000) ?></div>
</div>