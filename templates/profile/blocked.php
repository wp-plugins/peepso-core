<div class="ps-members-item">
	<div class="ps-members-item-avatar">
		<span class="ps-avatar">
			<img src="<?php peepso('avatar', peepso('profile', 'block-user')); ?>" title="<?php peepso('display-name', peepso('profile', 'block-user')); ?>">
		</span>
	</div>
	<div class="ps-members-item-body">
		<div class="ps-members-item-title">
			<?php peepso('profile', 'block-username'); ?>
		</div>
		<div class="ps-members-item-options">
			<input class="reset-gap" type="checkbox" id="ckbx-<?php echo peepso('profile', 'block-user'); ?>" />
		</div>
	</div>
</div>
