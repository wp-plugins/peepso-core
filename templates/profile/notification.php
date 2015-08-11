<div class="ps-notifications-item">
	<div class="ps-comment-item">
		<div class="ps-notifications-check">
			<input type="checkbox" id="ckbx-<?php peepso('profile', 'notification-id'); ?>" />&nbsp;
			&nbsp;
		</div>
		<div class="ps-avatar-comment">
			<a href="<?php peepso('user-link', peepso('profile', 'notification-user')); ?>">
				<img src="<?php peepso('avatar', peepso('profile', 'notification-user')); ?>" alt="<?php peepso('display-name', peepso('profile', 'notification-user')); ?>">
			</a>
		</div>
		<div class="ps-comment-body">
			<a href="<?php peepso('user-link', peepso('profile', 'notification-user')); ?>" class="ps-comment-user">
				<?php peepso('display-name', peepso('profile', 'notification-user')); ?>
			</a>
			<?php peepso('profile', 'notification-message'); ?> <?php peepso('profile', 'notification-link'); ?>

			<div class="ps-comment-time">
				<small class="activity-post-age" data-timestamp="<?php peepso('profile', 'notification-timestamp'); ?>"><?php peepso('profile', 'notification-age'); ?></small>
			</div>
		</div>
	</div>
</div>
