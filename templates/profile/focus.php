<?php 
	if (FALSE === peepso('profile', 'has-cover')) {
		$reposition_style = 'display:none;';
		$cover_class = 'default';
	} else {
		$reposition_style = '';
		$cover_class = 'has-cover';
	}

	$is_profile_segment = isset($current) ? TRUE : FALSE;
?>
<div class="ps-focus js-focus <?php if($is_profile_segment) { echo 'ps-focus-mini'; } ?> ps-js-focus ps-js-focus--<?php peepso('profile', 'user-id') ?>">
	<div class="ps-focus-cover js-focus-cover ">
		<div class="ps-focus-image">
			<img id="<?php peepso('profile', 'user-id'); ?>" 
				data-cover-context="profile" 
				class="focusbox-image cover-image <?php echo $cover_class; ?>" 
				src="<?php peepso('profile', 'cover-photo'); ?>" 
				alt="cover photo" 
				style="<?php peepso('profile', 'cover-photo-position'); ?>"
			/>
		</div>

		<div class="ps-focus-image-mobile" style="background:url(<?php peepso('profile', 'cover-photo'); ?>) no-repeat center center;">
		</div>

		<div class="js-focus-gradient" data-cover-context="profile" data-cover-type="cover"></div>

		<?php if (peepso('profile', 'can-edit') && !$is_profile_segment) { ?>
		
		<?php wp_nonce_field('profile-photo', '_photononce'); ?>
		<!-- Cover options dropdown -->
		<div class="ps-focus-options ps-dropdown ps-dropdown-focus">
			<a href="javascript:" class="ps-dropdown-toggle">
				<span class="ps-icon-cog"></span>
			</a>
			<ul class="dropdown-menu">
				<li class="ps-reposition-cover"><a id="profile-reposition-cover" href="javascript:void(0)" style="<?php echo $reposition_style;?>" data-cover-context="profile" onclick="profile.reposition_cover();"><i class="ps-icon-cog"></i><?php _e('Reposition Cover', 'peepso'); ?></a></li>
				<li><a href="javascript:void(0)" data-cover-context="profile" onclick="profile.show_cover_dialog();"><i class="ps-icon-picture"></i><?php _e('Modify Cover', 'peepso'); ?></a></li>
			</ul>
		</div>
		<!-- Reposition cover - buttons -->
		<div class="ps-focus-change js-focus-change-cover" data-cover-type="cover">
			<div class="reposition-cover-actions" style="display: none;">
				<a href="javascript:void(0)" class="ps-btn" onclick="profile.cancel_reposition_cover();"><?php _e('Cancel', 'peepso'); ?></a>
				<a href="javascript:void(0)" class="ps-btn ps-btn-success" onclick="profile.save_reposition_cover();"><?php _e('Save', 'peepso'); ?></a>
			</div>
			<div class="ps-reposition-loading" style="display: none;">
				<img src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>">
				<div> </div>
			</div>
		</div>
		<?php } ?>

		<!-- Focus Title , Avatar, Add as friend button -->
		<div class="ps-focus-header js-focus-content">
			<div class="ps-avatar-focus js-focus-avatar">
				<img src="<?php peepso('profile', 'avatar-full'); //image'); ?>" alt="<?php peepso('profile', 'user-name'); ?>">
				<?php if (peepso('profile', 'can-edit')) { ?>
					<span class="ps-avatar-change js-focus-avatar-option">
						<a href="javascript:void(0);" onclick="profile.show_avatar_dialog()">
							<i class="ps-icon-camera"></i>
						</a>
					</span>
				<?php } ?>
			</div>
			<div class="ps-focus-title">
				<span><?php peepso('profile', 'user-display-name'); ?></span>
			</div>
			<div class="ps-focus-actions">
				<?php peepso('profile', 'profile-actions'); ?>
			</div>
		</div>
	</div><!-- .js-focus-cover -->

	<?php
	if(!$is_profile_segment)
	{
		$current='profile';
	}
	?>

	<!-- Profile actions - mobile -->
	<div class="ps-focus-actions-mobile">
		<?php peepso('profile', 'profile-actions'); ?>
	</div>

	<!-- Focus Menu & Interactions -->
	<div class="ps-focus-link">
		<?php echo peepso('profile','profile_segment_menu', array('current'=>$current)); ?>
		<?php //peepso('profile', 'user-activities'); ?>
		<ul class="ps-list profile-interactions">
			<?php peepso('profile', 'interactions'); ?>
		</ul>
	</div>

	<!-- Unavailable content -->
	<div class="ps-focus-about js-collapse-about" style="display:none;">
		<div class="cModule cProfile-About app-box">
			<h3 class="app-box-header creset-h"><?php _e('About Me', 'peepso'); ?></h3>
			<div class="app-box-content">
				<div class="cfield">
					<?php peepso('profile', 'user-profile-fields'); ?>
				</div>
				<div class="cfield">
					<h3 class="cfield-title creset-h"><?php _e('Contact Information', 'peepso'); ?></h3>
					<ul class="cfield-list creset-list">
						<li>
							<h3 class="cfield-name creset-h"><?php _e('Website', 'peepso'); ?></h3>
							<div class="cfield-Content"><?php peepso('profile', 'user-website'); ?></div>
						</li>
					</ul>
				</div>
			</div>
			<div class="app-box-footer"></div>
		</div>
		<dl class="dl-horizontal">
			<dt><?php _e('Member Since', 'peepso'); ?></dt>
			<dd><?php peepso('profile', 'user-registered'); ?></dd>
			<dt><?php _e('Last Online', 'peepso'); ?></dt>
			<dd><?php peepso('profile', 'user-last-online'); ?></dd>
		</dl>
	</div>
	<div class="js-focus-actions">
	</div><!-- .js-focus-actions -->
</div><!-- .js-focus -->
