<ul class="ps-list-info cstream-list creset-list">
	<li>
		<ul class="ps-list-column cfield-list creset-list">
			<li>
				<h3 class="ps-list-info-title"><?php _e('Basic Information', 'peepso'); ?></h3>
			</li>
			<?php $output = FALSE; ?>
			<?php if (peepso('profile', 'user-hasgender')) { // #264
				$output = TRUE; ?>
			<li>
				<h4 class="ps-list-info-name creset-h"><?php _e('Gender', 'peepso'); ?></h4>
				<div class="ps-list-info-content"><?php peepso('profile', 'user-gender'); ?></div>
			</li>
			<?php } ?>
			<?php if (peepso('profile', 'user-hasbirthdate')) { // #264
				$output = TRUE; ?>
			<li>
				<h4 class="ps-list-info-name creset-h"><?php _e('Birthdate', 'peepso'); ?></h4>
				<div class="ps-list-info-content"><?php peepso('profile', 'user-birthdate'); ?></div>
			</li>
			<?php } ?>
			<?php if (peepso('profile', 'user_hasbio')) {
				$output = TRUE; ?>
			<li>
				<h4 class="ps-list-info-name creset-h"><?php _e('About Me', 'peepso'); ?></h4>
				<div class="ps-list-info-content"><?php peepso('profile', 'user-bio'); ?></div>
			</li>
			<?php } ?>
			<?php if (!$output) { ?>
			<li>
				<div class="ps-list-info-content"><?php _e('No user information to show.', 'peepso'); ?></div>
			</li>
			<?php } ?>
		</ul>
	</li>

	<?php	if (peepso('profile', 'user-haswebsite')) { // #308 ?>
	<li>
		<ul class="ps-list-column cfield-list creset-list">
			<li>
				<h3 class="ps-list-info-title"><?php _e('Contact Information', 'peepso'); ?></h3>
			</li>
			<li>
				<h4 class="ps-list-info-name creset-h"><?php _e('Website', 'peepso'); ?></h4>
				<div class="ps-list-info-content"><?php peepso('profile', 'user-website'); ?></div>
			</li>
		</ul>
	</li>
	<?php } ?>
	<?php do_action('peepso_user_profile_before_about_list_close'); ?>
</ul>

<?php if (peepso('profile', 'is-current-user')) { ?>
<div class="ps-padding ps-right">
	<a class="ps-btn ps-btn-primary" href="<?php echo PeepSo::get_page('profile');?>?edit"><?php _e('Edit Profile', 'peepso'); ?></a>
</div>
<?php } ?>