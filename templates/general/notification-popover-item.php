<div class="ps-notifications-item">
	<div class="ps-comment-item">
		<div class="ps-avatar-comment">
			<a href="<?php peepso('user-link', peepso('profile', 'notification-user')); ?>">
				<img src="<?php peepso('avatar', peepso('profile', 'notification-user')); ?>" alt="<?php peepso('display-name', peepso('profile', 'notification-user')); ?>">
			</a>
		</div>
		<div class="ps-comment-body">
			<span class="ps-messages-title">
				<a href="<?php peepso('user-link', peepso('profile', 'notification-user')); ?>" class="ps-comment-user">
					<?php peepso('display-name', peepso('profile', 'notification-user')); ?>
				</a>
				<small class="ps-comment-time activity-post-age" data-timestamp="<?php peepso('profile', 'notification-timestamp'); ?>"><?php peepso('profile', 'notification-age'); ?></small>
			</span>
			<div class="ps-messages-title">
				<?php peepso('profile', 'notification-message'); ?> <?php peepso('profile', 'notification-link'); ?>
			</div>
		</div>
	</div>
</div>