<div id="dialog-upload-avatar">
	<div id="dialog-upload-avatar-title"><?php _e('Change Avatar', 'peepso'); ?></div>
	<div id="dialog-upload-avatar-content">
		<div class="ps-loading-image" style="display: none;">
			<img src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>">
			<div> </div>
		</div>

		<div class="ps-page-split">
			<div class="ps-page-half upload-avatar">
				<a class="ps-btn ps-btn-small full-mobile fileinput-button" href="javascript:void(0);">
					<?php _e('Upload Photo', 'peepso'); ?>
					<input class="fileupload" type="file" name="filedata" />
				</a>
				<a id="div-remove-avatar"
				style="<?php if (!peepso('profile', 'has-avatar')) { ?>display:none;<?php } ?> overflow:hidden;"
				href="javascript:void(0);"
				onclick="profile.remove_avatar(<?php peepso('profile', 'user-id'); ?>);"
				class="ps-btn ps-btn-danger ps-btn-small full-mobile">
					<?php _e('Remove Photo', 'peepso'); ?>
				</a>

				<div class="ps-gap"></div>

				<div class="ps-js-has-avatar" <?php echo peepso('profile', 'has-avatar') ? '' : 'style="display:none"' ?>>
					<h5 class="ps-page-title"><?php _e('Uploaded Photo', 'peepso'); ?></h5>
					<div id="imagePreview" class="imagePreview">
						<img src="<?php peepso('profile', 'avatar-orig'); ?>?<?php echo time();?>" alt="<?php _e('Automatically Generated. (Maximum width: 160px)', 'peepso'); ?>"
							class="ps-image-preview large-profile-pic ps-name-tips" xwidth="100%"/>
					</div>
					<div class="ps-page-footer">
						<a xonclick="return profileavatar.updateThumbnail();" href="javascript:profileavatar.updateThumbnail()" id="" class="update-thumbnail ps-btn ps-btn-success ps-btn-small full-mobile ps-avatar-crop"><?php _e('Crop Image', 'peepso'); ?></a>
						<a href="javascript:profileavatar.saveThumbnail()" id="" class="update-thumbnail-save ps-btn ps-btn-small ps-btn-success full-mobile" style="display: none;"><?php _e('Save Thumbnail', 'peepso'); ?></a>
					</div>
				</div>

				<div class="ps-js-no-avatar" <?php echo peepso('profile', 'has-avatar') ? 'style="display:none"' : '' ?>>
					<div class="ps-alert"><?php _e('No avatar uploaded. Use the button above to select and upload one.' ,'peepso'); ?></div>
				</div>

			</div>

			<div class="ps-page-half ps-text-center show-avatar show-thumbnail">
				<h5 class="ps-page-title"><?php _e('Avatar Preview', 'peeps'); ?></h5>

				<div class="ps-avatar js-focus-avatar">
					<img src="<?php peepso('profile', 'avatar_image'); ?>?<?php echo time();?>" alt="" title="">
				</div>
				<div class="ps-gap"></div>
				<p class="reset-gap ps-text-muted"><?php _e('This is how your Avatar will appear throughout the entire community.', 'peepso'); ?></p>
			</div>
		</div>

		<div class="errors error-container ps-text-danger"></div>
	</div>

	<div class="dialog-action">
		<button class="ps-btn ps-btn-small ps-btn-primary" type="button" name="rep_submit" onclick="profile.confirm_avatar();return false;"><?php _e('Done', 'peepso'); ?></button>
	</div>
</div>
<div style="display:none">
	<div id="profile-avatar-error-filetype"><?php _e('The file type you uploaded is not allowed. Only JPEG/PNG allowed.', 'peepso'); ?></div>
	<div id="profile-avatar-error-filesize"><?php printf(__('The file size you uploaded is too big. The maximum file size is %s.', 'peepso'), '<strong>' . peepso('profile', 'upload-size') . '</strong>'); ?></div>
	<iframe id="ps-profile-avatar-iframe" src="<?php peepso('profile', 'avatar_image'); ?>"></iframe>
</div>