<div class="ps-members-item">
	<div class="ps-members-item-avatar">
		<a class="ps-avatar" href="<?php peepso('user-link', peepso('profile', 'block-user')); ?>">
			<img src="<?php peepso('avatar', peepso('profile', 'block-user')); ?>" title="<?php peepso('display-name', peepso('profile', 'block-user')); ?>">
		</a>
	</div>
	<div class="ps-members-item-body">
		<div class="ps-members-item-title">
			<?php peepso('profile', 'block-username'); ?>
		</div>
		<div class="ps-members-item-status">
			<a href="<?php peepso('user-link', peepso('profile', 'block-user')); ?>">
				<?php _e('View profile', 'peepso'); ?>
			</a>
		</div>
		<div class="ps-members-item-options">
			<input class="reset-gap" type="checkbox" id="ckbx-<?php echo peepso('profile', 'block-user'); ?>" />
		</div>
	</div>
</div>
