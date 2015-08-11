<?php if (is_user_logged_in() && FALSE === peepso('activityshortcode', 'is-permalink-page')) { ?>
<div id="postbox-main" class="ps-postbox clearfix" style="">
	<?php peepso('postbox', 'before-postbox'); ?>
	<div id="ps-postbox-status" class="ps-postbox-content">
		<div class="ps-postbox-tabs">
			<?php peepso('postbox', 'postbox-tabs'); ?>
		</div>
		<?php peepso('load-template', 'general/postbox-status', NULL); ?>
	</div>

	<div class="ps-postbox-tab ps-postbox-tab-root clearfix">
		<ul class="ps-list-inline">
			<?php peepso('general', 'post-types'); ?>
		</ul>
	</div>

	<nav class="ps-postbox-tab selected interactions" style="display: none;">
		<ul class="ps-list-inline">
			<?php peepso('postbox', 'post-interactions'); ?>
		</ul>
		<div class="ps-postbox-action" style="display: block;">
			<button type="button" class="ps-btn ps-btn-small ps-button-cancel"><?php _e('Cancel', 'peepso'); ?></button>
			<button type="button" class="ps-btn ps-btn-small ps-button-action postbox-submit" style="display: none;"><?php _e('Post', 'peepso'); ?></button>
		</div>
		<div class="ps-postbox-loading" style="display: none;">
			<img src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>">
			<div> </div>
		</div>
	</nav>
<?php peepso('postbox', 'after-postbox'); ?>
</div>
<?php } // is_user_logged_in() ?>