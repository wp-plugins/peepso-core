<div class="row">

	<section id="mainbody" class="clearfix">

		<section id="component" role="article" class="clearfix">

			<?php peepso('load-template', 'general/navbar'); ?>

			<h4><?php _e('Change Avatar', 'peepso'); ?></h4>

			<?php peepso('load-template', 'profile/submenu'); ?>

			<div class="clayout cProfile-UploadAvatar">

				<div class="app-box upload-avatar">

					<form name="jsform-profile-uploadavatar" action="<?php echo PeepSo::get_page('profile'); ?>?avatar" id="uploadForm" method="post" enctype="multipart/form-data">
						<input type="file" id="file-upload" name="filedata" />
						<input class="btn btn-primary" size="30" type="submit" id="file-upload-submit" value="Upload" />
						<input type="hidden" name="action" value="doUpload" />
						<input type="hidden" name="user_id" value="<?php peepso('profile', 'user-id'); ?>" />
						<input type="hidden" name="profileType" value="0" />
						<?php wp_nonce_field('profile-photo', '_photononce'); ?>
					</form>

					<div class="small"><?php printf(__('Maximum File Size for Upload is %s ', 'peepso'), '<strong>' . peepso('profile', 'upload-size') . '</strong>'); ?></div>

					<?php if (peepso('profile', 'has-errors')) { ?>
					<div class="calert">
						<?php peepso('profile', 'error-message'); ?>
					</div>
					<?php } ?>

					<div class="app-box-footer">
						<a href="javascript:void(0);" onclick="profile.confirm_remove_avatar();" class="btn btn-danger"><?php _e('Remove Profile Picture', 'peepso'); ?></a>
					</div>

				</div>

				<div class="app-box show-avatar">

					<h3 class="app-box-header"><?php _e('Profile Picture', 'peepso'); ?></h3>

					<?php if (peepso('profile', 'has-avatar')) { ?>

						<div id="imagePreview" class="imagePreview">
							<img id="large-profile-pic" src="<?php peepso('profile', 'avatar-orig'); ?>" alt="<?php _e('Automatically Generated. (Maximum width: 160px)', 'peepso'); ?>"
								class="ps-name-tips" />
						</div>

						<div class="app-box-footer">
							<a xonclick="return updateThumbnail();" xhref="javascript:updateThumbnail()" id="update-thumbnail" class="btn btn-primary" style="display: inline-block;"><?php _e('Crop Image', 'peepso'); ?></a>
							<a href="javascript:saveThumbnail()" id="update-thumbnail-save" class="btn btn-primary" style="display: none;"><?php _e('Save Thumbnail', 'peepso'); ?></a>
							<div id="update-thumbnail-guide" style="display: none;">
								<?php _e('Click and drag your mouse on the avatar to enable cropping tool.', 'peepso'); ?>
							</div>
						</div>
					<?php } else { ?>
						
						<div class="calert" style="margin-top: 10px"><?php _e('No avatar uploaded. Use the form above to select and upload one.' ,'peepso'); ?></div>
					
					<?php } ?>
				</div>

				<div class="app-box show-thumbnail">

					<h3 class="app-box-header"><?php _e('Thumbnail', 'peeps'); ?></h3>

					<div class="app-box-content">
						<img id="thumbnail-profile-pic" src="<?php peepso('profile', 'avatar_image'); ?>" alt="" title="">
					</div>

					<p></p>
					<p><?php _e('This is how your Avatar will appear in your Activity Stream.', 'peepso'); ?></p>
				</div>

			</div><!-- clayout -->

		</section><!--end compnent-->

	</section><!--end mainbody-->

</div><!--end row-->


<div id="ps-dialogs" style="display:none">
	<div id="delete-confirmation" class="hidden">
		<div id="delete-title"><?php _e('Delete Avatar', 'peepso'); ?></div>
		<div id="delete-content">
			<div><?php _e('Are you sure you want to remove your Avatar?', 'peepso'); ?></div>
			<div>
				<button type="button" class="ps-button-cancel" onclick="pswindow.hide(); return false;"><?php _e('Cancel', 'peepso'); ?></button>
				<button type="button" class="ps-button-action" onclick="profile.remove_avatar(<?php peepso('profile', 'user-id'); ?>); return false;"><?php _e('Remove', 'peepso'); ?></button>
			</div>
		</div>
	</div>
</div>