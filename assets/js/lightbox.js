(function( $, _, peepso, factory ) {

	var PsLightbox = factory( $, _, peepso );
	var instance = new PsLightbox();

	peepso.lightbox = function( data, options ) {
		if ( data === 'close' ) {
			instance.close();
			return;
		}

		if ( typeof data !== 'function' ) {
			instance.open( data, options || {} );
			return;
		}

		instance.options = options || {};
		instance.init();
		instance.showLoading();
		data(function( data, options ) {
			instance.hideLoading();
			instance.open( data, options || {} );
		});
	};

})( jQuery || $, window._, peepso, function( $, _, peepso ) {

var clsDataOpened = 'ps-lightbox-data--opened';
var clsCloseInvert = 'ps-lightbox-close--invert';

function PsLightbox() {}

PsLightbox.prototype = {

	init: function() {
		if ( ! this.$container ) {
			this.$container = $([
				'<div class="ps-lightbox ps-js-lightbox" style="z-index:100000">',
					'<div class="ps-lightbox-padding">',
						'<div class="ps-lightbox-wrapper">',
							'<div class="ps-lightbox-content">',
								'<div class="ps-lightbox-object"></div>',
								'<button class="ps-lightbox-arrow-prev" style="display:none"></button>',
								'<button class="ps-lightbox-arrow-next" style="display:none"></button>',
								'<div class="ps-lightbox-toolbar">',
									'<div class="ps-lightbox-toolbar-actions">',
										'<button class="ps-lightbox-data-toggle">Comments</button>',
									'</div>',
								'</div>',
								'<div class="ps-lightbox-spinner"></div>',
							'</div>',
							'<div class="ps-lightbox-data"></div>',
							'<div class="ps-lightbox-close">&times;</div>',
						'</div>',
					'</div>',
				'</div>'
			].join(''));

			this.$padding = this.$container.find('.ps-lightbox-padding');
			this.$wrapper = this.$container.find('.ps-lightbox-wrapper');
			this.$close = this.$container.find('.ps-lightbox-close');
			this.$object = this.$container.find('.ps-lightbox-object');
			this.$prev = this.$container.find('.ps-lightbox-arrow-prev');
			this.$next = this.$container.find('.ps-lightbox-arrow-next');
			this.$spinner = this.$container.find('.ps-lightbox-spinner');
			this.$attachment = this.$container.find('.ps-lightbox-data');
			this.$btnattachment = this.$container.find('.ps-lightbox-data-toggle');

			this.attachevents();

			this.$container.appendTo( document.body );
		}

		// disable zooming on mobile
		if ( this.isMobile() ) {
			if ( ! this.$viewport ) {
				this.$viewport = $('meta[name=viewport]');
				if ( ! this.$viewport.length ) {
					this.$viewport = $('<meta name="viewport" content="" />').appendTo('head');
				}
			}

			this.vpNoZoom = 'width=device-width, user-scalable=no';
			if ( ! this.vpValue ) {
				this.vpValue = this.$viewport.attr('content');
				this.$viewport.attr( 'content', this.vpNoZoom );
			}
		}

		// check if we want to show simple lightbox
		if ( this.options.simple ) {
			this.$container.addClass('ps-lightbox-simple');
		} else {
			this.$container.removeClass('ps-lightbox-simple');
		}

		this.$prev.hide();
		this.$next.hide();
		this.$container.show();

		// set height on for simple lightbox
		this.resetHeight();
		if ( this.options.simple ) {
			this.setHeight();
		}

		// attach event
		var $win = $(window);
		$win.off('resize.ps-lightbox');
		if ( this.options.simple ) {
			$win.on('resize.ps-lightbox', $.proxy(this.setHeight, this));
		}
	},

	open: function( data, options ) {
		this.data = data || [];
		this.options = options || {};
		this.index = options.index || 0;

		this.init();

		this.togglenav();
		this.go( this.index );
		this.hideAttachment();
	},

	close: function( e ) {
		if ( !this.$container ) {
			return;
		}

		this.$container.hide();
		this.$object.empty();
		this.$attachment.empty();

		// reset zooming on mobile
		if ( this.isMobile() ) {
			if ( this.vpValue ) {
				this.$viewport.attr( 'content', this.vpValue );
				this.vpValue = false;
			}
		}

		// detach event
		$(window).off('resize.ps-lightbox');
	},

	go: function( index ) {
		if ( typeof this.options.beforechange === 'function' ) {
			this.options.beforechange( this );
		}

		if ( this.data[ index ] ) {
			this.index = index;
			this.$object.html( this.data[ this.index ].content );
			this.$attachment.html( this.data[ this.index ].attachment || '' );
		}

		if ( typeof this.options.afterchange === 'function' ) {
			this.options.afterchange( this );
		}
	},

	prev: function() {
		this.go( this.index <= 0 ? this.data.length - 1 : this.index - 1 );
	},

	next: function() {
		this.go( this.index >= this.data.length - 1 ? 0 : this.index + 1 );
	},

	togglenav: function() {
		var $navs = this.$prev.add( this.$next );

		// detach navigation events.
		this.$container.off('click.ps-lightbox', '.ps-lightbox-arrow-prev');
		this.$container.off('click.ps-lightbox', '.ps-lightbox-arrow-next');
		$( window ).off('keyup.ps-lightbox');

		if ( this.options.nonav || !this.data.length || this.data.length <= 1 ) {
			$navs.hide();
			return;
		}

		$navs.show();

		// attach mouse navigation events.
		this.$container.on('click.ps-lightbox', '.ps-lightbox-arrow-prev', $.proxy( this.prev, this ));
		this.$container.on('click.ps-lightbox', '.ps-lightbox-arrow-next', $.proxy( this.next, this ));

		// attach keyboard navigation events.
		$( window ).on('keyup.ps-lightbox', $.proxy(function( e ) {
			var key = e.keyCode;
			if ( key === 37 ) {
				this.prev();
			} else if ( key === 39 ) {
				this.next();
			}
		}, this ));
	},

	isAttachmentOpened: function() {
		return this.$attachment.hasClass( clsDataOpened );
	},

	showAttachment: function() {
		this.$attachment.addClass( clsDataOpened );
		this.$close.addClass( clsCloseInvert );
	},

	hideAttachment: function() {
		this.$attachment.removeClass( clsDataOpened );
		this.$close.removeClass( clsCloseInvert );
	},

	showLoading: function() {
		this.$object.hide();
		this.$spinner.show();
	},

	hideLoading: function() {
		this.$spinner.hide();
		this.$object.show();
	},

	attachevents: function() {
		// attach close popup handler.
		this.$container.on('click.ps-lightbox', '.ps-lightbox-padding', $.proxy(function( e ) {
			if ( e.target === e.currentTarget ) {
				e.stopPropagation();
				this.close();
			}
		}, this ));

		// attach toggle attachment button
		this.$btnattachment.on('click', $.proxy(function() {
			if ( this.isAttachmentOpened() ) {
				this.hideAttachment();
			} else {
				this.showAttachment();
			}
		}, this ));

		// attach close button handler
		this.$close.on('click', $.proxy(function() {
			if ( this.isAttachmentOpened() ) {
				this.hideAttachment();
			} else {
				this.close();
			}
		}, this ));
	},

	isMobile: function() {
		var mobile = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i;
		var	isMobile = mobile.test( navigator.userAgent );

		this.isMobile = isMobile ? (function() { return true }) : (function() { return false });

		return isMobile;
	},

	resetHeight: function() {
		this.$object.find('img').css('maxHeight', '');
	},

	setHeight: _.debounce(function() {
		this.$object.find('img').css('maxHeight', this.$wrapper.height());
	}, 100 )

};

return PsLightbox;

});
