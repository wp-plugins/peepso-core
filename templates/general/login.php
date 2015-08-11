<div id="registration" class="ps-landing-action">
	<div class="login-area">
		<form class="ps-form" action="" onsubmit="return false;" method="post" name="login" id="form-login">
			<div class="ps-form-input ps-form-input-icon">
				<span class="ps-icon"><i class="ps-icon-user"></i></span>
				<input class="ps-input" type="text" name="username" id="username" placeholder="<?php _e('Username', 'peepso'); ?>" mouseev="true"
					autocomplete="off" keyev="true" clickev="true" />
			</div>
			<div class="ps-form-input ps-form-input-icon">
				<span class="ps-icon"><i class="ps-icon-lock"></i></span>
				<input class="ps-input" type="password" name="password" id="password" placeholder="<?php _e('Password', 'peepso'); ?>" mouseev="true"
							autocomplete="off" keyev="true" clickev="true" />
			</div>
			<button type="submit" id="login-submit" class="ps-btn ps-btn-login">
				<span><?php _e('Login', 'peepso'); ?></span>
				<img style="display:none" src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>">
			</button>
			<div class="ps-checkbox">
				<input type="checkbox" alt="<?php _e('Remember Me', 'peepso'); ?>" value="yes" id="remember" name="remember">
				<span><?php _e('Remember Me', 'peepso'); ?></span>
			</div>
			<a class="ps-link" href="<?php peepso('page-link', 'recover'); ?>"><?php _e('Recover Password', 'peepso'); ?></a>
			<a class="ps-link" href="<?php peepso('page-link', 'register'); ?>?resend"><?php _e('Resend activation code', 'peepso'); ?></a>
			<!-- Alert -->
			<div class="errlogin calert clear alert-error" style="display:none"></div>

			<input type="hidden" name="option" value="ps_users">
			<input type="hidden" name="task" value="-user-login">
		</form>
		<div style="display:none">
			<form name="loginform" id="loginform" action="<?php peepso('page-link', 'home'); ?>wp-login.php" method="post">
				<input type="text" name="log" id="user_login" />
				<input type="password" name="pwd" id="user_pass" />
				<input type="checkbox" name="rememberme" id="rememberme" value="forever" />
				<input type="submit" name="wp-submit" id="wp-submit" value="Log In" />
				<input type="hidden" name="redirect_to" value="<?php peepso('redirect-login'); ?>" />
				<input type="hidden" name="testcookie" value="1" />
				<?php wp_nonce_field('ajax-login-nonce', 'security'); ?>
			</form>
		</div>
	</div>
</div>

<script>
jQuery(function( $ ) {
	$('#form-login').on('submit', function( e ) {
		e.preventDefault();
		e.stopPropagation();
		ps_login.form_submit( e );
	});
});
</script>