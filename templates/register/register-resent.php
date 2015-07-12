<div class="peepso">
	<section id="mainbody" class="ps-page">
		<section id="component" role="article" class="clearfix">
			<div id="peepso" class="ps-register-resent">
				<h4><?php _e('Resend Activation Code', 'peepso'); ?></h4>

				<div class="ps-register-success">
					<p>
						<?php _e('Your activation code has been sent to your email.', 'peepso'); ?>
					</p>
					<p>
						<?php
							$link = peepso('get-page-link', 'register') . '?activate';
							echo sprintf(__('Follow the link in the email you recieved, or you can enter the activation code on the <a href="%1$s"><u>activation</u></a> page.</a>', 'peepso'), $link);
						?>
					</p>
					<div class="ps-gap"></div>
					<a href="<?php peepso('links', 'home'); ?>"><?php _e('Back to Home', 'peepso'); ?></a>
				</div>
			</div><!--end peepso-->
		</section><!--end component-->
	</section><!--end mainbody-->
</div><!--end row-->
