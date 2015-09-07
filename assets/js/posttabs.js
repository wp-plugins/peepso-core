(function($) {
	$.fn.ps_posttabs = function(options) {
		this._tabs = null;
		this._tabs_div = {};
		this.tab_objects = {};
		this.orig_html = {};
		this.options = {container: this};

		this.init = function(options) {
			var that = this;
			$.extend(true, this.options, options);
			sel = this.selector;
			$("[data-tab-id]", this.options.container).each(function(index, e) {
				that._tabs_div[$(e).data("tab-id")] = $(e);
			});

			this._tabs = $("[data-tab]", sel);

			this._tabs
				.on("click", function(e) {
					var $tab = $(e.currentTarget);
					that.current_tab_id = $tab.data("tab");
					that.show_tab($tab);
				})
				.first()
					.trigger("click");

			jQuery(this.options.container).on("click", ".ps-postbox-tab.interactions .ps-button-cancel", function () {
				that.on_cancel();
			});

			jQuery(this.options.container).on("click", ".ps-postbox-tab.interactions .postbox-submit", function () {
				that.on_submit();
			});					
		};
		
		this.show_tab = function(e) {
			var that = this;
			var $current_tab = this.current_tab();

			$current_tab.removeClass("active");
			$current_displayed = this.get_tab($current_tab.data("tab"));
			$current_displayed.hide();

			$(this).trigger("peepso_posttabs_cancel-" + $current_tab.data("tab"), [$current_displayed, this])

			//this.on_cancel();

			$(e).addClass("active");

			$display = this.get_tab($(e).data("tab"));

			$(this).trigger("peepso_posttabs_show-" + $(e).data("tab"), [$display, this]);
		};

		this.current_tab = function() {
			return $("[data-tab]", this.options.container).filter(".active");
		};

		this.get_tab = function(tab) {
			if (_.isUndefined(this._tabs_div[tab]))
				return $("");
			return this._tabs_div[tab];
		};

		this.on_submit = function() {
			$current = this.current_tab();
			ps_observer.apply_filters("peepso_posttabs_submit-" + $current.data("tab"));
			$(this).trigger("peepso_posttabs_submit-" + $current.data("tab"));
			$(this).trigger("peepso_posttabs_submit", [$current, this]);
		};

		this.on_cancel = function() {
			$current = this.current_tab();

			jQuery(".ps-postbox-tab-root", this.options.container).show();
			jQuery(".ps-postbox-tab.interactions", this.options.container).hide();

			$(this).trigger("peepso_posttabs_cancel-" + $current.data("tab"), [$current, this]);
			$(this).trigger("peepso_posttabs_cancel", [$current, this]);

			$current.trigger("click");
		}

		this.init(options);

		return this;
	};
})(jQuery);