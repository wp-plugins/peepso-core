<div class="peepso">
	<?php peepso('load-template', 'general/navbar'); ?>
	<?php peepso('load-template', 'profile/submenu'); ?>
	<section id="mainbody" class="ps-page">
		<section id="component" role="article" class="clearfix">
		<!--<h4><?php _e('Emails and Notifications', 'peepso'); ?></h4>-->

			<div class="ps-profile-alerts c-profile-alerts">
				<?php if (peepso('profile', 'has-message')) { ?>
				<div class="ps-alert ps-alert-success">
					<?php peepso('profile', 'profile-message'); ?>
				</div>
				<?php } ?>

				<?php if (peepso('profile', 'get.num-alerts-fields')) { ?>
					<?php peepso('profile', 'alerts-form-fields'); ?>
				<?php } else { ?>
				<div class="ps-alert">
					<?php _e('You have no configurable Emails and Notifications settings.', 'peepso'); ?>
				</div>
				<?php } ?>
			</div>
		</section><!--end compnent-->
	</section><!--end mainbody-->
</div><!--end row-->



