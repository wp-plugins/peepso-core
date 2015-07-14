<div class="peepso">
	<?php peepso('load-template', 'general/navbar'); ?>
	<?php peepso('load-template', 'profile/submenu'); ?>
	<section id="mainbody" class="ps-page ps-page-unstyled">
		<section id="component" role="article" class="clearfix">
		<!--<h4><?php _e('Notifications', 'peepso'); ?></h4>-->

			<div class="ps-profile-notifications">
				<?php if (peepso('profile', 'has-notifications')) { ?>
					<div class="ps-text-center ps-text-muted ps-padding">
						<?php _e('Your notifications are stored for only 20 day(s). Old notifications will be deleted.', 'peepso'); ?>
					</div>
					<div class="ps-notifications">
						<?php
						while (peepso('profile', 'next-notification')) {
							peepso('profile', 'show-notification');
						} ?>
					</div>
				<?php } else { ?>
					<div class="ps-text-center ps-text-muted ps-padding">
						<?php _e('You currently have no notifications', 'peepso'); ?>
					</div>
				<?php } ?>
			</div>
			<?php if (peepso('profile', 'has-notifications')) { ?>
			<div class="ps-padding">
				<button id="notifications-select-all" class="ps-btn ps-button-cancel" onclick="ps_profile_notification.select_all(); return false;"><?php _e('Select All', 'peepso'); ?></button>
				<button id="notifications-unselect-all" class="ps-btn ps-button-cancel" onclick="ps_profile_notification.unselect_all(); return false;" style="display: none;"><?php _e('Unselect All', 'peepso'); ?></button>
				<button id="delete-selected" class="ps-btn ps-btn-danger" onclick="ps_profile_notification.delete_selected(); return false;"><?php _e('Delete Selected', 'peepso'); ?></button>
			</div>
			<?php } ?>
		</section><!--end compnent-->
	</section><!--end mainbody-->
</div><!--end row-->