<div class="peepso">
	<section id="mainbody" class="ps-page">
		<section id="component" role="article" class="clearfix">
			<div id="peepso" class="ps-register-resend">
				<h4><?php _e('Resend Activation Code', 'peepso'); ?></h4>

				<div class="ps-register-success">
					<p>
						<?php _e('Please enter your registered e-mail address here so that we can resend you the activation link.', 'peepso'); ?>
					</p>
					<div class="ps-gap"></div>
<?php
					#247
					if (isset($error))
						peepso('general', 'show-error', $error);
?>
					<form class="ps-form" name="resend-activation" action="<?php peepso('page-link', 'register'); ?>?resend" method="post">
						<input type="hidden" name="task" value="-resend-activation" />
						<input type="hidden" name="-form-id" value="<?php echo wp_create_nonce('resent-activation-form'); ?>" />
						<div class="ps-form-row">
							<div class="ps-form-group">
								<label for="email" class="form-label"><?php _e('Email Address', 'peepso'); ?>
									<span class="required-sign">&nbsp;*<span></span></span>
								</label>
								<div class="ps-form-field">
									<input class="ps-input" type="text" name="email" id="email" placeholder="<?php _e('Email address', 'peepso'); ?>" />
								</div>
							</div>
							<div class="ps-form-group submitel">
								<div class="ps-form-field">
									<input type="submit" name="submit-resend" class="ps-btn ps-btn-primary" value="<?php _e('Submit', 'peepso'); ?>" />
								</div>
							</div>
						</div>
					</form>
					<div class="ps-gap"></div>
					<a href="<?php peepso('links', 'home'); ?>"><?php _e('Back to Home', 'peepso'); ?></a>
				</div>
			</div><!--end peepso-->
		</section><!--end component-->
	</section><!--end mainbody-->
</div><!--end row-->
