<div data-type="stream-more" class="ps-comment-more" data-commentmore="true">
	<a onclick="return activity.show_comments(<?php global $post; echo $post->act_id; ?>)" href="#showallcomments">
		<?php peepso('activity', 'show-more-comments-link');?>
	</a>
	<img class="hidden comment-ajax-loader" src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>" alt="" />
</div>
