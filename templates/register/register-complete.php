<div class="peepso">
	<section id="mainbody" class="ps-page">
		<section id="component" role="article" class="clearfix">
			<div id="peepso" class="ps-register-complete">
				<h4><?php _e('User Registered', 'peepso'); ?></h4>

				<div class="ps-register-success">
					<p>
						<?php 
							if (PeepSo::get_option('site_registration_enableverification', '0'))
								_e('Your account has been created and is still under moderation. Until the site administrator approves your account, you will not be able to login. Once your account has been approved, you will receive a notification email.', 'peepso'); 
							else
								_e('Your account has been created. An activation link has been sent to the email address you provided, click on the link to logon to your account.', 'peepso'); 
						?>
					</p>
					<a href="<?php peepso('links', 'home'); ?>" class="ps-btn ps-btn-primary"><?php _e('Back to Home', 'peepso'); ?></a>
				</div>
			</div><!--end peepso-->
		</section><!--end component-->
	</section><!--end mainbody-->
</div><!--end row-->
