<div class="peepso">
	<?php peepso('load-template', 'general/navbar'); ?>
	<?php peepso('load-template', 'general/register-panel'); ?>
	<?php if(PeepSo::get_user_id()) { ?>
	<section id="mainbody" class="ps-page">
		<section id="component" role="article" class="clearfix">
			<h4 class="ps-page-title"><?php _e('Members', 'peepso'); ?></h4>

			<form class="ps-form ps-form-search" role="form" name="form-peepso-search" onsubmit="return false;">
				<div class="ps-form-row">
					<input placeholder="<?php _e('Start typing to search...', 'peepso');?>" type="text" class="ps-input full ps-js-members-query" name="query" value="<?php echo esc_attr($search); ?>" />
				</div>
				<a href="javascript:" class="ps-form-search-opt">
					<span class="ps-icon-cog"></span>
				</a>
			</form>
			<?php
			$default_sorting = '';
			if(!strlen(esc_attr($search)))
			{
				$default_sorting = PeepSo::get_option('site_memberspage_default_sorting','');
			}
			?>
			<div class="ps-js-page-filters" style="display:none;">
				<div class="ps-page-filters">
					<div class="ps-filters-row">
						<label><?php _e('Gender', 'peepso'); ?></label>
						<select class="ps-select ps-js-members-gender" onchange="ps_membersearch.filter();" style="margin-bottom:5px">
							<option value=""><?php _e('Any', 'peepso'); ?></option>
							<option value="m"><?php _e('Male', 'peepso'); ?></option>
							<option value="f"><?php _e('Female', 'peepso'); ?></option>
						</select>
					</div>

					<div class="ps-filters-row">
						<label><?php _e('Sort', 'peepso'); ?></label>
						<select class="ps-select ps-js-members-sortby" onchange="ps_membersearch.filter();" style="margin-bottom:5px">
							<option value=""><?php _e('Alphabetical', 'peepso'); ?></option>
							<option <?php echo ('peepso_last_activity' == $default_sorting) ? ' selected="selected" ' : '';?> value="peepso_last_activity|asc"><?php _e('Recently online', 'peepso'); ?></option>
							<option <?php echo ('registered' == $default_sorting) ? ' selected="selected" ' : '';?>value="registered|desc"><?php _e('Latest members', 'peepso'); ?></option>
						</select>
					</div>

					<div class="ps-filters-row">
						<label><?php _e('Avatars', 'peepso');?></label>
						<div class="ps-checkbox">
							<input type="checkbox" name="avatar" value="1" class="ps-js-members-avatar" onclick="ps_membersearch.filter();" />
							<span style="font-weight:normal"><?php _e('Only users with avatars', 'peepso'); ?></span>
						</div>
					</div>
				</div>
			</div>

			<div class="clearfix mb-20"></div>
			<div class="ps-gallery ps-js-members"></div>
			<div class="ps-gallery-scroll ps-js-members-triggerscroll">
				<img class="post-ajax-loader ps-js-members-loading" src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>" alt="" style="display:none" />
			</div>
		</section>
		<?php } ?>
	</section>
</div><!--end row-->
<?php if(PeepSo::get_user_id()) { peepso('load-template', 'activity/dialogs'); } ?>
