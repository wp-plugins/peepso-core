(function($) {
	$(document).ready(function() {
		/**********************************************************************************		 
		* Demographic metabox 
		**********************************************************************************/
		var _demographic_data = [];

		$.each(demographic_data, function(i, e) {
			_demographic_data.push({
				label: e.label,
				data: e.value,
				color: (undefined !== e.color) ? e.color : null
			});
		});

		$("#demographic-pie").css({ "width": "90%" , "min-height": "150px"});

		var demographic_chart = $.plot("#demographic-pie", _demographic_data, demographic_options);


		/**********************************************************************************
		* User Engagement metabox (Stream tab)
		**********************************************************************************/	
		var engagement_chart;
		var $date_range_select = $("#peepso_dashboard_user_engagement .tab-pane.active .engagement_date_range");

		function render_engagement_chart()
		{
			var _engagement_data = [];
			var stats = [];

			$.each($('#peepso_dashboard_user_engagement .tab-pane.active input[name="stats[]"]').serializeArray(), function(i, s) {
				stats.push(s.value);
			});
			stats = stats.join(",");

			req = {
				date_range: $date_range_select.val(),
				module_id: $("#peepso_dashboard_user_engagement .nav-tabs .active").data("module-id"),
				stats: stats
			};

			$date_range_select.attr("disabled", "disabled");

			$PeepSo.getJson("adminEngagementDashboard.get_graph_data", req, 
				function(response) {
					$.each(response.data.series, function(i, series) {
						series.label = $('#peepso_dashboard_user_engagement .tab-pane.active input[value="' + series.type + '"]').siblings('.lbl').text();
						_engagement_data.push(series);
					});

					var engagement_chart = $.plot(
						"#peepso_dashboard_user_engagement .tab-pane.active .graph-container",
						_engagement_data,
						{
							yaxis: {
								min: 0
							},
							xaxis: {
								tickDecimals: 0,
								mode: "time",
								timeformat: "%m/%d",
								min: response.data.min,
								max: response.data.max
							},
							legend: {
								labelFormatter: function(label, series) {
									return (series.total + " " + label);
								}
							},
							series: {
								lines: {
									show: true
								},
								points: {
									show: true
								}
							},
							grid: {
								hoverable: true,
								clickable: true
							}
						}
					);

					$date_range_select.removeAttr("disabled");
				}
			);
		}

		$date_range_select.on("change", render_engagement_chart).trigger("change");

		$('#peepso_dashboard_user_engagement .tab-pane.active input[name="stats[]"]').on("click", render_engagement_chart);
		$('#peepso_dashboard_user_engagement a[data-toggle="tab"]').on("shown.bs.tab", render_engagement_chart);
	});
})(jQuery);

// EOF