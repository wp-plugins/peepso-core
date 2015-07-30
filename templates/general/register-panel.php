<?php if ( ! is_user_logged_in()) { ?>
	<div class="ps-landing">
		<div class="ps-landing-cover">
			<div class="ps-landing-image" style="background-image:url(<?php echo PeepSo::get_asset('images/register-bg.jpg'); ?>);"></div>

			<div class="ps-landing-content">
				<div class="ps-landing-text">
					<h2><?php echo PeepSo::get_option('site_registration_header', __('Get Connected!', 'peepso')); ?></h2>
					<p><?php echo PeepSo::get_option('site_registration_callout', __('Come and join our community. Expand your network and get to know new people!', 'peepso')); ?></p>
				</div>
				<div class="ps-landing-signup">
					<a class="ps-btn ps-btn-join" href="<?php peepso('links', 'register'); ?>">
						<?php echo PeepSo::get_option('site_registration_buttontext', __('Join us now, it\'s free!', 'peepso')); ?></a>
				</div>
			</div>
		</div>

		<?php peepso('load-template', 'general/login');?>
	</div>
<?php
} // is_user_logged_in() ?>
