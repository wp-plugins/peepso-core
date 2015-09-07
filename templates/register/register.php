<div class="peepso">
	<section id="mainbody" class="ps-page">
		<section id="component" role="article" class="clearfix">
			<div class="ps-page-register cRegister">
				<h4 class="ps-page-title"><?php _e('Register New User', 'peepso'); ?></h4>

				<div class="ps-register-form cprofile-edit">
					<?php if (!empty($error)) { ?>
						<div class="ps-alert ps-alert-danger"><?php _e('Error: ', 'peepso'); echo $error; ?></div>
					<?php } ?>
					<?php peepso('form', 'render', peepso('register', 'register-form')); ?>
				</div>
			</div><!--end cRegister-->
		</section><!--end component-->
	</section><!--end mainbody-->
</div><!--end row-->

<script type="text/javascript">

function show_terms()
{
	var inst = pswindow.show("<?php _e('Terms and Conditions', 'peepso'); ?>", peepsoregister.terms);
	var elem = inst.$container.find('.ps-dialog');

	elem.addClass('ps-dialog-full');
	ps_observer.add_filter('pswindow_close', function() {
		elem.removeClass('ps-dialog-full');
	}, 10, 1);
}

// Password strenght indicator
var password_strength_settings = {
	'texts' : {
		1 : 'Too short.',
		2 : 'Weak password.',
		3 : 'Normal strength.',
		4 : 'Strong password.',
		5 : 'Very strong password.'
	}
}

jQuery(document).ready( function ()
{
	cvalidate.init();
	cvalidate.noticeTitle	= 'Notice';
	cvalidate.setSystemText('REM','is required. Make sure it contains a valid value!');
	cvalidate.setSystemText('JOINTEXT','and');

	jQuery("#jomsForm").submit(function() {
		jQuery("#btnSubmit").hide();
		jQuery("#cwin-wait").show();
		jQuery("#jomsForm input").attr("readonly", true);

		if (jQuery("#authenticate").val() != '1') {
			joms.registrations.authenticate();
			return (false);
		}
	});

	jQuery("#password").password_strength(password_strength_settings);

	jQuery("#password, #password2").on('keyup', function() {
		var password = jQuery("#password").val();
		var password2 = jQuery("#password2").val();

		jQuery("#password, #password2").toggleClass('ps-alert ps-alert-danger', password !== password2);
	});
});

</script>
