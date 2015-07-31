<div class="peepso">
	<?php peepso('load-template', 'general/navbar'); ?>
	<?php peepso('load-template', 'profile/submenu'); ?>
	<section id="mainbody" class="ps-page">
		<section id="component" role="article" class="clearfix">
		<!--<h4><?php _e('Blocked Users', 'peepso'); ?></h4>-->

			<div class="ps-profile-blocked cprofile-blocked">
			<?php if (peepso('profile', 'has-blocked')) { ?>
				<div class="ps-text-center ps-text-muted">
					<?php _e('The following users are blocked and will not be able to see your posts or your Profile.', 'peepso'); ?>
				</div>
				<div class="ps-gap"></div>				
				<div class="ps-members">
					<?php
					while (peepso('profile', 'next-blocked')) {
						peepso('profile', 'show-blocked');
					} ?>
				</div>
			<?php } ?>
			</div>
<!-- blocked=<?php echo '['.peepso('profile', 'get.num-blocked').']'; ?> -->
			<?php if (peepso('profile', 'get.num-blocked')) { ?>
			<div class="ps-gap"></div>
			<button id="delete-selected" class="ps-btn ps-btn-danger" onclick="ps_blocks.delete_selected(); return false;"><?php _e('Remove Selected Blocks', 'peepso'); ?></button>
			<?php } else { ?>
			<div class="ps-text-center ps-text-muted">
				<?php _e('You have no blocked users.', 'peepso'); ?>
			</div>
			<?php } ?>
		</section><!--end compnent-->
	</section><!--end mainbody-->
</div><!--end row-->

<div style="display: none;">
	<div id="peepso-no-block-user-selected"><?php echo _e('Please select at least one user to unblock.', 'peepso') ?></div>
</div>
