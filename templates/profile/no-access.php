<div class="peepso">
	<section id="mainbody" class="ps-wrapper">
		<section id="component" role="article" class="clearfix">
			<?php peepso('load-template', 'general/navbar'); ?>
			<div id="cProfileWrapper" class="ps-profile-noaccess">
				<div class="ps-focus js-focus">
					<div class="ps-focus-cover js-focus-cover">
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

						<!-- Focus Title , Avatar, Add as friend button -->
						<div class="ps-focus-header js-focus-content">
							<div class="ps-avatar-focus">
								<img src="<?php peepso('profile', 'avatar-full'); //image'); ?>" alt="<?php peepso('profile', 'user-name'); ?>">
							</div>
							<div class="ps-focus-title">
								<span><?php peepso('profile', 'user-display-name'); ?></span>
							</div>
							<div class="ps-focus-actions js-focus-actions">
							</div>
						</div>
					</div><!-- .js-focus-cover --> <!-- end js-focus-content -->
				</div><!-- .js-focus -->

				<!-- Profile actions - mobile -->
				<div class="ps-focus-actions-mobile js-focus-actions"></div>

				<div class="ps-body">
					<div class="ps-main <?php if (peepso('profile', 'get.has-sidebar')) echo ''; else echo 'ps-main-full'; ?>">
						<!-- js_profile_feed_top -->
						<div class="activity-stream-front ps-page ps-text-center">
							<div id="ps-no-posts"><?php _e('This user has decided to keep their profile private.' ,'peepso'); ?></div>
							<div class="ps-gap"></div>
							<a href="#" class="ps-btn ps-btn-primary"><?php _e('Login' ,'peepso'); ?></a>
							<span class="ps-text-muted">or</span>
							<a href="#" class="ps-btn ps-btn-success"><?php _e('Register' ,'peepso'); ?></a>
						</div><!-- end activity-stream-front -->
					</div><!--end col-->

					<?php if (peepso('profile', 'get.has-sidebar')) { ?>
						<?php peepso('load-template', 'sidebar/sidebar'); ?>
					<?php } ?>
				</div><!-- end row -->
			</div><!-- end cProfileWrapper --><!-- js_bottom -->
		</section><!--end component-->
	</section><!--end mainbody-->
</div><!--end row-->
