<div class="ps-stream ps-js-activity ps-js-activity--<?php echo $act_id; ?>" data-id="<?php echo $act_id; ?>">
	<div class="ps-stream-header">
		<!-- post author avatar -->
		<div class="ps-avatar-stream">
			<a href="<?php peepso('user-link', $post_author); ?>">
				<img data-author="<?php echo $post_author; ?>" src="<?php peepso('avatar', $post_author); ?>" alt="" />
			</a>
		</div>
		<!-- post meta -->
		<div class="ps-stream-meta">
			<div class="reset-gap">
				<?php peepso('activity', 'post-action-title'); ?>
				<?php do_action('peepso_after_post_author'); ?>
			</div>
			<small class="ps-stream-time" data-timestamp="<?php peepso('activity', 'post-timestamp'); ?>">
				<a href="<?php peepso('activity', 'post-link'); ?>">
					<?php peepso('activity', 'post-age'); ?>
				</a>
			</small>
			<?php if ($post_author == PeepSo::get_user_id()) { ?>
			<span class="ps-dropdown ps-dropdown-privacy ps-stream-privacy ps-js-privacy--<?php echo $act_id; ?>">
				<a href="javascript:" data-toggle="dropdown" data-value="" class="ps-dropdown-toggle">
					<span class="dropdown-value">
						<?php peepso('activity', 'post-access'); ?>
					</span>
				<!--<span class="dropdown-caret ps-icon-caret-down"></span>-->
				</a>
				<?php wp_nonce_field('change_post_privacy_' . $act_id, '_privacy_wpnonce_' . $act_id); ?>
				<?php peepso('privacy', 'display-dropdown', 'activity.change_post_privacy(this, ' . $act_id . ')'); ?>
			</span>
			<?php } ?>
		</div>
		<!-- post options -->
		<div class="ps-stream-options">
			<?php peepso('activity', 'post-options'); ?>
		</div>
	</div>

	<!-- post body -->
	<div class="ps-stream-body">
		<div class="ps-stream-attachment cstream-attachment">
			<?php peepso('activity', 'content'); ?>
		</div>
		<div class="ps-stream-attachments cstream-attachments">
			<?php peepso('activity', 'post-attachment'); ?>
		</div>
	</div>

	<!-- post actions -->
	<div class="ps-stream-actions stream-actions" data-type="stream-action"><?php peepso('activity', 'post-actions'); ?></div>

	<?php if ($likes = peepso('activity', 'has-likes', $act_id)) { ?>
	<div id="act-like-<?php echo $act_id; ?>" class="ps-stream-status cstream-likes ps-js-act-like--<?php echo $act_id; ?>">
		<?php peepso('activity', 'show-like-count', $likes); ?>
	</div>
	<?php } ?>

	<div class="ps-comment cstream-respond wall-cocs" id="wall-cmt-<?php echo $act_id; ?>">
		<div class="ps-comment-container comment-container" data-act-id="<?php echo $act_id; ?>">
			<?php if (peepso('activity', 'has-comments')) { ?>
					<?php peepso('activity', 'show-recent-comments'); ?>
			<?php } ?>
		</div>

		<?php if (is_user_logged_in() && !peepso('activity', 'has-max-comments', $ID)) { ?>
		<div id="act-new-comment-<?php echo $act_id; ?>" class="ps-comment-reply cstream-form stream-form wallform" data-type="stream-newcomment" data-formblock="true">
			<a class="ps-avatar cstream-avatar cstream-author" href="<?php peepso('user-link', peepso('get-user-id')); ?>">
				<img data-author="<?php echo $post_author; ?>" src="<?php peepso('avatar', peepso('get-user-id')); ?>" alt="" />
			</a>
			<div class="ps-textarea-wrapper cstream-form-input">
				<textarea
					data-act-id="<?php echo $act_id;?>"
					class="ps-textarea cstream-form-text"
					name="comment"
					onkeyup="return activity.on_commentbox_change(this);"
					placeholder="<?php _e('Write a comment...', 'peepso');?>"></textarea>
			</div>
			<div class="ps-comment-send cstream-form-submit" style="display:none;">
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
