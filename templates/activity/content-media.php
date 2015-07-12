<div class="ps-media-video">
	<div class="ps-media-thumbnail video-avatar">
	<?php
	if (isset($content)) { 
	?>
		<div class="<?php peepso('activity', 'content-media-class', 'media-object'); ?>">
	 			<?php echo ($content); ?>
	 	</div>
	<?php 
	} ?>
	</div>
	<div class="ps-media-body video-description">
		<!-- video description -->
		<div class="ps-media-title">
			<a href="<?php echo $url; ?>" rel="nofollow" <?php echo $target; ?>><?php echo ($title); ?></a>
			<small>
				<a href="<?php echo $url; ?>" rel="nofollow" <?php echo $target; ?>><?php echo ($host); ?></a>
			</small>
		</div>
		<div class="ps-media-desc">
			<?php if (isset($description)) echo $description; ?>
		</div>
	</div>
</div>
