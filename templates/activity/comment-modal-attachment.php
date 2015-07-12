<div class="ps-js-modal-attachment--<?php echo $act_id; ?>">
	<div class="ps-stream-header">
		<!-- post author avatar -->
		<div class="ps-avatar-stream">
			<a href="<?php peepso('user-link', $post_author); ?>">
				<img data-author="<?php echo $post_author; ?>" src="<?php peepso('avatar', $post_author); ?>" alt="">
			</a>
		</div>
		<!-- post meta -->
		<div class="ps-stream-meta">
			<div class="reset-gap">
				<a class="ps-stream-user" href="<?php peepso('user-link', $post_author); ?>"><?php peepso('display-name', $post_author); ?></a>
			</div>
			<small class="ps-stream-time" data-timestamp="<?php peepso('activity', 'post-timestamp'); ?>">
				<a href="<?php peepso('activity', 'post-link'); ?>">
					<span><?php peepso('activity', 'post-age'); ?></span>
				</a>
			</small>
			<?php if ($post_author == PeepSo::get_user_id()) { ?>
			<span class="ps-dropdown ps-dropdown-privacy ps-stream-privacy ps-js-privacy--<?php echo $act_id; ?>">
				<?php if (TRUE == $disable_privacy) { ?>
				<span style="opacity:.5">
					<span class="dropdown-value"><?php peepso('activity', 'post-access'); ?></span>
				</span>
				<?php } else { ?>
				<a href="javascript:" data-toggle="dropdown" data-value="" class="ps-dropdown-toggle">
					<span class="dropdown-value"><?php peepso('activity', 'post-access'); ?></span>
				</a>
				<?php wp_nonce_field('change-post-privacy-' . $act_id, '_privacy_wpnonce_' . $act_id); ?>
				<?php peepso('privacy', 'display-dropdown', 'activity.change_post_privacy(this, ' . $act_id . ')'); ?>
				<?php } ?>
			</span>
			<?php } ?>
		</div>
	</div>
	<div class="ps-stream-body">
		<?php if (isset($post_attachments)) { ?>
		<div>
			<p><?php echo $post_attachments; ?></p>
		</div>
		<?php } ?>
		<div class="ps-stream-attachment cstream-attachment">
			<?php echo $act_description; ?>
		</div>
	</div>
	<div class="ps-stream-actions stream-actions" data-type="stream-action">
		<input type="hidden" name="module-id" value="<?php echo $act_module_id;?>" />
		<?php wp_nonce_field('activity-delete', '_delete_nonce'); ?>
		<nav class="ps-stream-status-action ps-stream-status-action pstd-contrast">
			<?php peepso('activity', 'post-actions'); ?>
		</nav>
		<div id="act-like-<?php echo $act_id; ?>" class="cstream-likes ps-js-act-like--<?php echo $act_id; ?>">
		<?php
			$likes = peepso('activity', 'has-likes', $act_id);
			if ($likes) {
				peepso('activity', 'show-like-count', $likes);
			}
		?>
		</div>
	</div>
	<div style="padding:0 5px;">
		<div class="ps-comment cstream-respond wall-cocs" id="wall-cmt-<?php echo $act_id; ?>">
			<div class="ps-comment-container comment-container" data-act-id="<?php echo $act_id; ?>">
				<?php peepso('activity', 'show-recent-comments'); ?>
			</div>
			<?php if (is_user_logged_in() && FALSE === peepso('activity', 'has-max-comments', $ID)) { ?>
			<div id="act-new-comment-<?php echo $act_id; ?>" data-type="stream-newcomment" class="ps-comment-reply cstream-form stream-form wallform" data-formblock="true">
				<div class="ps-textarea-wrapper cstream-form-input">
					<textarea
						data-act-id="<?php echo $act_id;?>"
						class="ps-textarea cstream-form-text"
						name="comment"
						onkeyup="return activity.on_commentbox_change(this);"
						style="height: 45px; min-height: 20px; resize: none; overflow: hidden; word-wrap: break-word;"
						placeholder="<?php _e('Write a comment...', 'peepso');?>"></textarea>
				</div>
				<div class="ps-comment-send cstream-form-submit">
					<div class="ps-comment-loading" style="display:none;">
						<img src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>" alt="" />
						<div> </div>
					</div>
					<div class="ps-comment-actions" style="display:none;">
						<button onclick="return activity.comment_cancel(<?php echo $act_id; ?>);" class="ps-btn ps-button-cancel"><?php _e('Clear', 'peepso'); ?></button>
						<button onclick="return activity.comment_save(<?php echo $act_id; ?>, this);" class="ps-btn ps-btn-primary ps-button-action" disabled><?php _e('Post', 'peepso'); ?></button>
					</div>
				</div>
			</div>
			<?php } // is_user_loggged_in ?>
		</div>
	</div>
</div>