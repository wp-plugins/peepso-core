<?php
	$peepso_admin = PeepSoAdmin::get_instance();
	$peepso_admin->admin_notices();
?>
<div  id="peepso" class="wrap">
	<?php
	echo $config->form_open();
	?>
		<div class="row">
			<div class="col-xs-12">
				<div class="row">
					<!-- Left column -->
					<div class="col-xs-12 col-sm-6">
						<?php do_meta_boxes('peepso_page_peepso-config', 'left', null); ?>
					</div>
					<!-- Right column -->
					<div class="col-xs-12 col-sm-6">
						<?php do_meta_boxes('peepso_page_peepso-config', 'right', null); ?>
					</div>
				</div>
				<div class="clearfix"></div>
			</div>
		</div>
		<div class="clearfix"></div>
		<div class="row">
			<div class="col-xs-12">
				<div class="row">
					<div class="col-xs-12">
					<?php do_meta_boxes('peepso_page_peepso-config', 'full', null); ?>
					</div>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-xs-12">
				<div class="form-actions center">
					<button class="btn btn-info" type="submit">
						<i class="ace-icon fa fa-check bigger-110"></i>
						<?php _e('Save Settings', 'peepso'); ?>
					</button>

					&nbsp; &nbsp; &nbsp;
					<button class="btn" type="reset">
						<i class="ace-icon fa fa-undo bigger-110"></i>
						<?php _e('Reset', 'peepso'); ?>
					</button>
				</div>
			</div>
		</div>
	</form>
</div>
<style>
	input[type=checkbox].ace.ace-switch.ace-switch-2 + .lbl::before {
		/* translators: Use dashes around and between YES & NO to align them */
		content: "<?php echo str_replace('-', '\a0', __('YES---------NO','peepso'));?>"
	}
</style>