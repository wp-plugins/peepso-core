<div id="ps-member-search-html" class="ps-form">
	<div class="ps-form-row">
        <input value="<?php echo isset($_GET['query']) ? $_GET['query'] : "";?>" type="search" name="query" class="ps-input full" placeholder="<?php _e('Start typing to search', 'peepso'); ?>…" style="margin-bottom:5px;" />
        <?php wp_nonce_field('member-search', '_wpnonce'); ?>
	</div>
	<div class="ps-padding ps-text-center hidden member-search-notice">
		<?php _e('No results found.', 'peepso'); ?>
	</div>
</div>