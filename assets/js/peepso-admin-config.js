(function($) {

	// Check license.
	function checkLicense() {
		var statuses = $(".license_status_check");
		var plugins = {};

		if (!statuses.length) {
			return;
		}

		statuses.each(function() {
			var el = $(this);
			plugins[ el.attr("id") ] = el.data("plugin-name");
		});

		function periodicalCheckLicense() {
			$PeepSo.postJson("adminConfigLicense.check_license", { plugins: plugins }, function(json) {
				var valid, prop, icon;
				if (json.success) {
					valid = json.data && json.data.valid || {};
					for ( prop in valid ) {
						if (+valid[prop]) {
							icon = '<i class="ace-icon fa fa-check bigger-110" style="color:green"></i>';
							$("#error_" + prop).hide();
						} else {
							icon = '<i class="ace-icon fa fa-times bigger-110" style="color:red"></i>';
							$("#error_" + prop).show();
						}
						statuses.filter("#" + prop).html( icon );
					}
				}
			});
		}

		periodicalCheckLicense();
		setInterval(function() {
			periodicalCheckLicense();
		}, 1000 * 30 );
	}

	$(document).ready(function() {
		var $limit_comments = $("input[name='site_activity_limit_comments']");
		// Handle toggling of limit comments readonly state
		if ($limit_comments.size() > 0) {
			$limit_comments.on("change", function() {
				if ($(this).is(":checked")) {
					$("input[name='site_activity_comments_allowed']").removeAttr('readonly');
				} else {
					$("input[name='site_activity_comments_allowed']").attr('readonly', 'readonly');
				}
			}).trigger("change");
		}

		checkLicense();
	});

})(jQuery);