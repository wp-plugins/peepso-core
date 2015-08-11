<div class="peepso">
	<?php peepso('load-template', 'general/navbar'); ?>
	<?php peepso('load-template', 'general/register-panel'); ?>
	<section id="mainbody" class="ps-page">
		<section id="component" role="article" class="clearfix">
			<h4 class="ps-page-title"><?php _e('Members', 'peepso'); ?></h4>

			<form class="ps-form ps-form-search" role="form" name="form-peepso-search" onsubmit="return false;">
				<div class="ps-form-row">
					<input placeholder="<?php _e('Start typing to search...', 'peepso');?>" type="text" class="ps-input full ps-js-members-query" name="query" value="<?php echo esc_attr($search); ?>" />
				</div>
			</form>

			<div class="clearfix mb-20"></div>
            <div class="ps-gallery ps-js-members ps-js-members--<?php echo  apply_filters('peepso_user_profile_id', 0); ?>"></div>
            <div class="ps-gallery-scroll ps-js-members-triggerscroll ps-js-members-triggerscroll--<?php echo  apply_filters('peepso_user_profile_id', 0); ?>">&nbsp;</div>
		</section>
	</section>
</div><!--end row-->
<?php peepso('load-template', 'activity/dialogs'); ?>
