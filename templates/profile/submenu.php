<ul class="ps-submenu clearfix">
	<li class=" ">
		<?php $class = '';
				if (isset($_GET['edit']))
					$class = ' class="active" ';
		?>
		<a href="<?php echo PeepSo::get_page('profile'); ?>?edit" <?php echo $class; ?> ><?php _e('Edit Profile', 'peepso'); ?></a>
	</li>
	<li class=" ">
		<?php $class = '';
				if (isset($_GET['pref']))
					$class = ' class="active" ';
		?>
		<a href="<?php echo PeepSo::get_page('profile'); ?>?pref" <?php echo $class; ?> ><?php _e('Preferences', 'peepso'); ?></a>
	</li>
	<li class=" ">
		<?php $class = '';
				if (isset($_GET['notifications']))
					$class = ' class="active" ';
		?>
		<a href="<?php echo PeepSo::get_page('profile'); ?>?notifications" <?php echo $class; ?> ><?php _e('Notifications', 'peepso'); ?></a>
	</li>
	<li class=" ">
		<?php $class = '';
				if (isset($_GET['blocked']))
					$class = ' class="active" ';
		?>
		<a href="<?php echo PeepSo::get_page('profile'); ?>?blocked" <?php echo $class; ?> ><?php _e('Block List', 'peepso'); ?></a>
	</li>
	<li class=" ">
		<?php $class = '';
				if (isset($_GET['alerts']))
					$class = ' class="active" ';
		?>
		<a href="<?php echo PeepSo::get_page('profile'); ?>?alerts" <?php echo $class; ?> ><?php _e('Emails and Notifications', 'peepso'); ?></a>
	</li>
	<?php if (PeepSo::get_option('site_registration_allowdelete', FALSE) && ! PeepSo::is_admin()) { ?>
	<li class="action">
		<?php $class = '';
				if (isset($_GET['delete']))
					$class = ' class="active" ';
		?>
		<a href="#" onclick="profile.delete_profile(); return false;" <?php echo $class; ?> ><?php _e('Delete Profile', 'peepso'); ?></a>
	</li>
	<?php } ?>
</ul>
<div id="ps-dialogs" style="display:none">
	<div id="ps-profile-delete-dialog">
		<div id="profile-delete-title"><?php _e('Confirm Delete', 'peepso'); ?></div>
		<div id="profile-delete-content">
			<div>
				<h4>Delete Profile</h4>
				<p>Are you sure you want to <strong>delete your Profile</strong>?<br/>
				This will remove all of your posts, saved information and <strong>delete</strong> your account.</p>
				<p><em>The delete cannot be undone.</em></p>
				<br/>
				<button type="button" name="rep_cacel" class="ps-button-cancel" onclick="pswindow.hide(); return false;"><?php _e('Cancel', 'peepso'); ?></button>
				&nbsp;
				<button type="button" name="rep_submit" class="ps-button-action" onclick="profile.delete_profile_action(); return false;"><?php _e('Delete My Profile', 'peepso'); ?></button>
			</div>
		</div>
	</div>

	<div id="default-confirm-dialog">
		<div id="default-confirm-title"><?php _e('Confirm', 'peepso'); ?></div>
		<div id="default-confirm-content">
			<div>{content}</div>
		</div>
		<div id="default-confirm-actions">
			<button type="button" class="ps-button-cancel" onclick="return pswindow.do_no_confirm();"><?php _e('No', 'peepso'); ?></button>
			<button type="button" class="ps-button-action" onclick="return pswindow.do_confirm();"><?php _e('Yes', 'peepso'); ?></button>
		</div>
	</div>	
</div>
