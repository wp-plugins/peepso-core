<div class="ps-comment-item">
	<div class="ps-avatar-comment" style="cursor:pointer;" onclick="window.location='<?php peepso('user-link', $user_id); ?>'">
		<img src="<?php peepso('avatar', $user_id); ?>" alt="<?php peepso('display-name', $user_id); ?>">
	</div>
	<div class="ps-comment-body" style="cursor:pointer;" onclick="window.location='<?php peepso('user-link', $user_id); ?>'">
		<div class="ps-comment-user">
			<?php peepso('display-name', $user_id); ?>
		</div>
	</div>
	<?php if (isset($buttons) && count($buttons) >= 1) { ?>
	<div class="ps-popover-actions">
		<?php foreach ($buttons as $button) { ?>
		<button class="<?php echo esc_attr($button['class']); ?>" <?php echo isset($button['click-notif']) ? 'onclick="' . esc_attr($button['click-notif']) . '"' : ''; ?>>
			<?php echo esc_attr($button['label']); ?>
		</button>
		<?php } ?>
	</div>
	<?php } ?>
</div>