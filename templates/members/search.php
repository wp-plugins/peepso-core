<div class="peepso">
	<?php peepso('load-template', 'general/navbar'); ?>
	<?php peepso('load-template', 'general/register-panel'); ?>
	<section id="mainbody" class="ps-page">
		<section id="component" role="article" class="clearfix">
			<h4 class="ps-page-title"><?php _e('Search', 'peepso'); ?></h4>

			<form class="ps-form ps-form-search" role="form" name="form-peepso-search" action="<?php the_permalink(); ?>" method="GET" onsubmit="return psmembers.submit_search(this);">
				<div class="ps-form-row">
					<input placeholder="<?php _e('Search', 'peepso');?>" type="text" class="ps-input" name="query" value="<?php echo esc_attr($search); ?>" />
					<button type="submit" class="ps-btn"><?php _e('Search', 'peepso'); ?></button>
				</div>
				<?php wp_nonce_field('member-search', '_wpnonce'); ?>
			</form>

			<div class="clearfix mb-20"></div>
<?php
				if (peepso('membersearch', 'found-members')) {
					echo '<div class="ps-alert">' . $num_results . _n(' member found', ' members found', $num_results, 'peepso') . '</div>';
					echo '<div class="ps-members creset-list">';
					while ($member = peepso('memberSearch', 'get-next-member')) {
						?>
							<div id="" class="ps-members-item">
								<?php peepso('memberSearch', 'show-member', $member); ?>
							</div>
<?php				}
					echo '</div>';
					echo '<div class="clearfix"></div>';
				} else if (isset($_GET['search'])) { ?>
					<div id="ps-no-posts" class="ps-alert reset-gap"><?php _e('No users found.' ,'peepso'); ?></div>
<?php			} ?>
		</section>
	</section>
</div><!--end row-->
<?php peepso('load-template', 'activity/dialogs'); ?>
