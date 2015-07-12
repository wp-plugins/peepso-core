<div id="ps-member-search-html" class="ps-form">
	<div class="ps-form-row">
        <input type="search" name="query" class="ps-input full" placeholder="<?php _e('Search for members', 'peepso'); ?>&hellip;" />
        <?php wp_nonce_field('member-search', '_wpnonce'); ?>
	</div>
	<div class="ps-padding ps-text-center hidden member-search-notice">
		<?php _e('No results found.', 'peepso'); ?>
	</div>
</div>