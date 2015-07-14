<div class="peepso">
	<?php peepso('load-template', 'general/navbar'); ?>
	<?php peepso('load-template', 'profile/submenu'); ?>
	<section id="mainbody" class="ps-page">
		<section id="component" role="article" class="clearfix">
		<!--<h4 class="ps-page-title"><?php _e('Preferences', 'peepso'); ?></h4>-->
		
			<div class="ps-form-container">
				<?php if (peepso('profile', 'has-message')) { ?>
				<div class="ps-alert ps-alert-success">
					<?php peepso('profile', 'profile-message'); ?>
				</div>
				<?php } ?>
				<div class="ps-gap"></div>
				<?php peepso('form', 'render', peepso('profile', 'edit-preferences')); ?>
				<div class="ps-form-group">
					<label for=""></label>
					<!--<span class="ps-form-helper"><?php _e('Fields marked with an asterisk (<span class="required-sign">*</span>) are required.', 'peepso'); ?></span>-->
				</div>
			</div> <!-- .clayout -->
		</section><!--end compnent-->
	</section><!--end mainbody-->
</div><!--end row-->

