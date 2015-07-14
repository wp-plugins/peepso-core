<div class="peepso">
	<section id="mainbody" class="ps-page">
		<section id="component" role="article" class="clearfix">
			<div id="peepso" class="on-socialize ltr cRegister">
				<h4><?php _e('Recover Password', 'peepso'); ?></h4>

				<div class="ps-register-recover">
					<p>
						<?php _e('Please enter the email address for your account. A verification code will be sent to you. Once you have received the verification code, you will be able to choose a new password for your account.', 'peepso'); ?>
					</p>
					<div class="ps-gap"></div>
<?php
					if (isset($error))
						peepso('general', 'show-error', $error);
?>
					<form id="recoverpasswordform" name="recoverpasswordform" action="<?php peepso('page-link', 'recover'); ?>?submit" method="post" class="ps-form">
						<input type="hidden" name="task" value="-recover-password" />
						<input type="hidden" name="-form-id" value="<?php echo wp_create_nonce('peepso-recover-password-form'); ?>" />
						<div class="ps-form-row">
							<div class="ps-form-group">
								<label for="email" class="ps-form-label"><?php _e('Email Address:', 'peepso'); ?>
									<span class="required-sign">&nbsp;*<span></span></span>
								</label>
								<div class="ps-form-field">
									<input class="ps-input" type="text" name="email" placeholder="<?php _e('Email address', 'peepso'); ?>" />
								</div>
							</div>
							<div class="ps-form-group submitel">
								<input type="submit" name="submit-recover" class="ps-btn ps-btn-primary" value="<?php _e('Submit', 'peepso'); ?>" />
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
