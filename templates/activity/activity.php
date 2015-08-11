<div class="peepso">
	<section id="mainbody" class="ps-wrapper clearfix">
		<section id="component" role="article" class="clearfix">
			<?php peepso('load-template', 'general/navbar'); ?>
<?php /*	<h2 class="ps-page-title"><?php echo PeepSo::get_option('site_frontpage_title', __('Recent Activities', 'peepso')); ?></h2><?php */ ?>
			<?php peepso('load-template', 'general/register-panel'); ?>

			<div class="ps-body">
			<!--<div class="ps-sidebar"></div>-->
				<div class="ps-main ps-main-full">
					<?php peepso('load-template', 'general/postbox'); ?>
					<!-- stream activity -->
					<div class="ps-stream-wrapper">
						<?php if (peepso('activity', 'has-posts')) { ?>
							<div id="ps-activitystream" class="ps-stream-container" data-filter="all" data-filterid="0" data-groupid data-eventid data-profileid>
							<?php
								// display all posts
								while (peepso('activity', 'next-post')) {
									peepso('activity', 'show-post'); // display post and any comments
								}

								peepso('activity', 'show-more-posts-link');
							?>
							</div>
						<?php } else if (0 === PeepSo::get_option('site_activity_hide_stream_from_guest', 0)) { ?>
							<div id="ps-no-posts" class="ps-alert"><?php _e('No posts found.' ,'peepso'); ?></div>
							<div id="ps-activitystream" class="ps-stream-container" style="display:none" data-filter="all" data-filterid="0" data-groupid data-eventid data-profileid>
							</div>
						<?php } ?>
						<?php peepso('load-template', 'activity/dialogs'); ?>
					</div>
				</div>
			</div>
		</section><!--end component-->
	</section><!--end mainbody-->
</div><!--end row-->
