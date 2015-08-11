<div id="ps-dialogs" style="display:none">
	<div id="ajax-loader-gif" style="display:none;">
		<div class="ps-loading-image">
			<img src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>" alt="">
			<div> </div>
		</div>
	</div>
	<div id="ps-dialog-comment">
		<div data-type="stream-newcomment" class="cstream-form stream-form wallform " data-formblock="true" style="display: block;">
			<form class="reset-gap">
				<div class="cstream-form-submit">
					<a data-action="cancel" onclick="return activity.comment_cancel();" class="ps-btn ps-btn-small cstream-form-cancel" href="javascript:"><?php _e('Cancel', 'peepso'); ?></a>
					<button data-action="save" onclick="return activity.comment_save();" class="ps-btn ps-btn-small ps-btn-primary"><?php _e('Post Comment', 'peepso'); ?></button>
				</div>
			</form>
		</div>
	</div>

	<div id="ps-report-dialog">
		<div id="activity-report-title"><?php _e('Report Content to Admin', 'peepso'); ?></div>
		<div id="activity-report-content">
			<div id="postbox-report-popup">
				<div><?php _e('Reason for Report:', 'peepso'); ?></div>
				<div class="ps-text-danger"><?php peepso('activity', 'report-reasons'); ?></div>
				<div class="ps-alert" style="display:none"></div>
				<input type="hidden" id="postbox-post-id" name="post_id" value="{post-id}" />
			</div>
		</div>
		<div id="activity-report-actions">
			<button type="button" name="rep_cacel" class="ps-btn ps-btn-small ps-button-cancel" onclick="pswindow.hide(); return false;"><?php _e('Cancel', 'peepso'); ?></button>	
			<button type="button" name="rep_submit" class="ps-btn ps-btn-small ps-button-action" onclick="activity.submit_report(); return false;"><?php _e('Submit Report', 'peepso'); ?></button>
		</div>
	</div>
	
	<span id="report-error-select-reason"><?php _e('ERROR: Please select Reason for Report.', 'peepso'); ?></span>

	<div id="ps-share-dialog">
		<div id="share-dialog-title"><?php _e('Share This', 'peepso'); ?></div>
		<div id="share-dialog-content">
			<h5 class="reset-gap"><?php _e('Share this via Link:', 'peepso'); ?></h5>
			<div class="ps-gap"></div>
			<?php peepso('share', 'show-links');?>
			<div class="clearfix"></div>
		</div>
	</div>

	<div id="default-delete-dialog">
		<div id="default-delete-title"><?php _e('Confirm Delete', 'peepso'); ?></div>
		<div id="default-delete-content">
			<?php _e('Are you sure you want to delete this?', 'peepso'); ?>
		</div>
		<div id="default-delete-actions">
			<button type="button" class="ps-btn ps-btn-small ps-button-cancel" onclick="pswindow.hide(); return false;"><?php _e('Cancel', 'peepso'); ?></button>
			<button type="button" class="ps-btn ps-btn-small ps-button-action" onclick="pswindow.do_delete();"><?php _e('Delete', 'peepso'); ?></button>
		</div>
	</div>

	<div id="default-acknowledge-dialog">
		<div id="default-acknowledge-title"><?php _e('Confirm', 'peepso'); ?></div>
		<div id="default-acknowledge-content">
			<div>{content}</div>
		</div>
		<div id="default-acknowledge-actions">
			<button type="button" class="ps-btn ps-btn-small ps-button-action" onclick="return pswindow.hide();"><?php _e('Okay', 'peepso'); ?></button>
		</div>
	</div>	
	<?php peepso('load-template', 'activity/dialog-repost'); ?>
	<?php peepso('load-template', 'members/search-popover-input'); ?>
	<?php peepso('activity', 'dialogs'); // give add-ons a chance to output some HTML ?>
</div>