<div id="comment-item-<?php echo $ID; ?>" class="ps-comment-item cstream-comment stream-comment" data-comment-id="<?php echo $ID; ?>">
	<div class="ps-avatar-comment">
		<a class="cstream-avatar cstream-author" href="<?php peepso('user-link', $post_author); ?>">
			<img data-author="<?php echo $post_author; ?>" src="<?php peepso('avatar', $post_author); ?>" alt="" />
		</a>
	</div>

	<div class="ps-comment-body cstream-content">
		<div class="ps-comment-message stream-comment-content">
			<a class="ps-comment-user cstream-author" href="<?php peepso('user-link', $post_author); ?>"><?php peepso('display-name', $post_author); ?></a>
			<span class="comment" data-type="stream-comment-content"><?php peepso('activity', 'content'); ?></span>
		</div>

		<div data-type="stream-more" class="cstream-more" data-commentmore="true"></div>

		<div class="ps-comment-media cstream-attachments">
			<?php peepso('activity', 'comment-attachment'); ?>
		</div>
		<div class="ps-comment-time ps-shar-meta-date">
			<small class="activity-post-age" data-timestamp="<?php peepso('activity', 'post-timestamp'); ?>"><?php peepso('activity', 'post-age'); ?></small>
			<div id="act-like-<?php echo $act_id; ?>" class="ps-comment-links cstream-likes ps-js-act-like--<?php echo $act_id; ?>" style="display:none">
				<?php peepso('activity', 'show-like-count', peepso('activity', 'has-likes', $act_id)); ?>
			</div>
			<div class="ps-comment-links stream-actions" data-type="stream-action">
				<span class="ps-stream-status-action ps-stream-status-action">
					<?php peepso('activity', 'comment-actions'); ?>
				</span>
			</div>
		</div>
	</div>
</div>
