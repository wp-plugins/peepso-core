<?php echo $content; ?>

<?php /*
<div class="modal-comment-container" data-activity-id="<?php echo $act_id; ?>">
	<input type="hidden" name="module-id" value="<?php echo $act_module_id;?>" />
	<?php wp_nonce_field('activity-delete', '_delete_nonce'); ?>
	<div class="modal-comment-content">
		<div class="modal-comment-content-wrapper">
			<?php echo $content; ?>
		</div>
		<?php if ($_total_objects > 1) { ?>
			<div class="nav-container"></div>
		<?php } ?>
	</div>
	<div class="modal-comment-comments ps-stream-content">
		<header class="pstd-text">
			<div class="ps-stream-avatar ps-stream-avatar">
				<a class="cstream-avatar cfloat-l" href="<?php peepso('user-link', $post_author); ?>">
					<img class="cavatar" data-author="<?php echo $post_author; ?>" src="<?php peepso('avatar', $post_author); ?>" alt="" />
				</a>
			</div>

			<a href="<?php peepso('user-link', $post_author); ?>"><?php peepso('display-name', $post_author); ?></a>
			<div class="ps-share-meta date">
				<a href="<?php peepso('activity', 'post-link'); ?>">
					<span class="activity-post-age pstd-neutral" data-timestamp="<?php peepso('activity', 'post-timestamp'); ?>">
						<?php peepso('activity', 'post-age'); ?>
					</span>
				</a>
				<?php if ($post_author == PeepSo::get_user_id()) { ?>
				<span>
					<div class="ps-privacy-dropdown ps-stream-privacy">
						<button data-toggle="dropdown" data-value="" class="ps-dropdown-toggle" type="button">
							<span class="dropdown-value">
								<?php peepso('activity', 'post-access'); ?>
							</span>
							<span class="dropdown-caret ps-icon-caret-down"></span>
						</button>
						<?php wp_nonce_field('change-post-privacy-' . $act_id, '_privacy_wpnonce_' . $act_id); ?>
						<?php peepso('privacy', 'display-dropdown', 'activity.change_post_privacy(this, ' . $act_id . ')'); ?>
					</div>
				</span>
				<?php } ?>
			</div>
		</header>

		<div class="cstream-attachment">
			<?php echo $act_description; ?>
		</div>

		<div data-type="stream-action" class="stream-actions clearfix">
			<nav class="ps-stream-status-action ps-stream-status-action pstd-contrast">
				<?php peepso('activity', 'post-actions'); ?>
			</nav>
			<?php
			$likes = peepso('activity', 'has-likes', $act_id);

			if ($likes) {
				?>
			<div id="act-like-<?php echo $act_id; ?>" class="cstream-likes ps-js-act-like--<?php echo $act_id; ?>">
				<?php peepso('activity', 'show-like-count', $likes); ?>
			</div>
			<?php } ?>
		</div>

		<div class="comment-container" data-act-id="<?php echo $act_id; ?>">
			<?php peepso('activity', 'show-recent-comments'); ?>
		</div><!-- act-comment-container -->

		<div data-type="stream-comments" class="cstream-respond wall-cocs" id="wall-cmt-<?php echo $act_id; ?>">
			<?php if (is_user_logged_in() && FALSE === peepso('activity', 'has-max-comments', $ID)) { ?>
			<div id="act-new-comment-<?php echo $act_id; ?>" data-type="stream-newcomment" class="cstream-form stream-form wallform " data-formblock="true" style="display: block;">
				<a class="cstream-avatar cstream-author cfloat-l" href="<?php peepso('user-link', peepso('get-user-id')); ?>">
					<img class="cavatar sm-avatar" data-author="<?php echo $post_author; ?>" src="<?php peepso('avatar', peepso('get-user-id')); ?>" alt="" />
				</a>
				<form class="reset-gap">
					<div class="cstream-form-input">
						<textarea
							data-act-id="<?php echo $act_id;?>"
							class="cstream-form-text"
							name="comment"
							onkeyup="return activity.on_commentbox_change(this);"
							style="height: 45px; min-height: 20px; resize: none; overflow: hidden; word-wrap: break-word;"
							placeholder="<?php _e('Write a comment...', 'peepso');?>"></textarea>
					</div>
					<div class="cstream-form-submit">
						<div class="ps-comment-loading" style="display:none;">
							<img src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>" alt="" />
							<div> </div>
						</div>
						<div class="ps-comment-actions" style="display:none;">
							<button onclick="return activity.comment_cancel(<?php echo $act_id; ?>);" class="ps-button-cancel"><?php _e('Clear', 'peepso'); ?></button>
							<button onclick="return activity.comment_save(<?php echo $act_id; ?>, this);" class="ps-button-action" disabled><?php _e('Post Comment', 'peepso'); ?></button>
						</div>
					</div>
				</form>
			</div>
			<?php } // is_user_loggged_in ?>
		</div>
	</div>
	<div class="clearfix"></div>
</div>
*/ ?>