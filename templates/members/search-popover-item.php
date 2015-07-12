<div class="ps-comment-item" style="cursor:pointer;" onclick="window.location='<?php peepso('user-link', $user_id); ?>'">
	<div class="ps-avatar-comment">
		<img src="<?php peepso('avatar', $user_id); ?>" alt="<?php peepso('display-name', $user_id); ?>">
	</div>
	<div class="ps-comment-body">
		<div class="ps-comment-user">
			<?php peepso('display-name', $user_id); ?>
		</div>
	</div>
</div>