	// Keep-alive
	let keepAlive = setInterval(function(){
		$.get({
			url: window._env.platform.path + 'ajax/keep_alive',
			cache: false
		});
	}, 60e3);

	// Stylesheet Loader
	$.loadStylesheet = function(url, callback, fallback) {
		$('<link/>', {rel: 'stylesheet', href: url}).appendTo('head');
	}

	// JavaScript Loader
	$.loadScript = function(url, options) {

		options = $.extend(options || {}, {
			mtehod: 'GET',
			dataType: 'script',
			cache: true
		});

		return jQuery.ajax(url, options);
	};

	// Alerts
	$('body').on('click', '.alert .close', function(e){
		e.preventDefault();
		$(this).closest('.alert').fadeOut('fast', function(){
			$(this).remove()
		});
	});

	// Form required asterix
	$(':input[required]').closest('.form-group').addClass('required');

	// AJAX Search
	let timer_ajax_search = null;
	let xhr_search = null;
	$('#search input[name="query"]').on('input', function(){

		let search_field = this;

		if (xhr_search) xhr_search.abort();

		if ($(this).val() == '') {
			$('#search .results').hide().html('');
			$('#box-apps-menu').fadeIn('fast');
			return;
		}

		if (!$('#search .loader-wrapper').length) {
			$('#box-apps-menu').fadeOut('fast');
			$('#search .results').show().html('<div class="loader-wrapper text-center"><div class="loader" style="width: 48px; height: 48px;"></div></div>');
		}

		clearTimeout(timer_ajax_search);
		timer_ajax_search = setTimeout(function() {
			xhr_search = $.ajax({
				type: 'get',
				async: true,
				cache: false,
				url: window._env.backend.url + 'search_results.json?query=' + $(search_field).val(),
				dataType: 'json',

				beforeSend: function(jqXHR) {
					jqXHR.overrideMimeType('text/html;charset=' + $('html meta[charset]').attr('charset'));
				},

				error: function(jqXHR, textStatus, errorThrown) {
					$('#search .results').text(textStatus + ': ' + errorThrown);
				},

				success: function(json) {

					$('#search .results').html('');

					if (!$('#search input[name="query"]').val()) return;

					$.each(json, function(i, group){

						if (group.results.length) {
							$('#search .results').append(
								'<h4>'+ group.name +'</h4>' +
								'<ul class="list-group" data-group="'+ group.name +'"></ul>'
							);

							$.each(group.results, function(i, result){
								$('#search .results ul[data-group="'+ group.name +'"]').append(
									'<li class="result">' +
									'  <a class="list-group-item" href="'+ result.link +'" style="border-inline-start: 3px solid '+ group.theme.color +';">' +
									'    <small class="id float-end">#'+ result.id +'</small>' +
									'    <div class="title">'+ result.title +'</div>' +
									'    <div class="description"><small>'+ result.description +'</small></div>' +
									'  </a>' +
									'</li>'
								);
							});
						}
					});

					if ($('#search .results').html() == '') {
						$('#search .results').html('<p class="text-center no-results"><em>:(</em></p>');
					}
				},
			});
		}, 500);
	});

	// Tabs (data-toggle="tab")
	$('.nav-tabs').each(function(){
		if (!$(this).find('.active').length) {
			$(this).find('[data-toggle="tab"]:first').addClass('active');
		}

		$(this).on('select', '[data-toggle="tab"]', function() {
			$(this).siblings().removeClass('active');
			$(this).addClass('active');
			$($(this).attr('href')).show().siblings().hide();
		});

		$(this).on('click', '[data-toggle="tab"]', function(e) {
			e.preventDefault();
			$(this).trigger('select');
			history.replaceState({}, '', location.toString().replace(/#.*$/, '') + $(this).attr('href'));
		});

		$(this).find('.active').trigger('select');
	});

	if (document.location.hash != '') {
		$('a[data-toggle="tab"][href="' + document.location.hash + '"]').click();
	}

	// Toggle Buttons (data-toggle="buttons")
	$('body').on('click', '[data-toggle="buttons"] :checkbox', function(){
		if ($(this).is(':checked')) {
			$(this).closest('.btn').addClass('active');
		} else {
			$(this).closest('.btn').removeClass('active');
		}
	});

	$('body').on('click', '[data-toggle="buttons"] :radio', function(){
		$(this).closest('.btn').addClass('active').siblings().removeClass('active');
	});

	// Dropdown select
	$('.dropdown .form-select + .dropdown-menu :input').on('input', function(e){
		let $dropdown = $(this).closest('.dropdown');
		let $input = $dropdown.find(':input:checked');

		if (!$dropdown.find(':input:checked').length) return;

		$dropdown.find('li.active').removeClass('active');

		if ($input.data('title')) {
			$dropdown.find('.form-select').text( $input.data('title') );
		} else if ($input.closest('.option').find('.title').length) {
			$dropdown.find('.form-select').text( $input.closest('.option').find('.title').text() );
		} else {
			$dropdown.find('.form-select').text( $input.parent().text() );
		}

		$input.closest('li').addClass('active');
		$dropdown.trigger('click.bs.dropdown');

	}).trigger('input');

	// Data-Table Toggle Checkboxes
	$('body').on('click', '.data-table *[data-toggle="checkbox-toggle"]', function() {
		$(this).closest('.data-table').find('tbody tr td:first :checkbox').each(function() {
			$(this).prop('checked', !$(this).prop('checked')).trigger('change');
		});
		return false;
	});

	$('body').on('click', '.data-table tbody tr', function(e) {
		if ($(e.target).is('a, .btn, :input, th, .fa-star, .fa-star-o')) return;
		if ($(e.target).parents('a, .btn, :input, th, .fa-star, .fa-star-o').length) return;
		$(this).find('td:first :checkbox, :radio').trigger('click');
	});

	// Data-Table Shift Check Multiple Checkboxes
	let lastTickedCheckbox = null;
	$('.data-table td:first :checkbox').click(function(e){

		let $chkboxes = $('.data-table td:first :checkbox');

		if (!lastTickedCheckbox) {
			lastTickedCheckbox = this;
			return;
		}

		if (e.shiftKey) {
			let start = $chkboxes.index(this);
			let end = $chkboxes.index(lastTickedCheckbox);
			$chkboxes.slice(Math.min(start,end), Math.max(start,end)+ 1).prop('checked', lastTickedCheckbox.checked);
		}

		lastTickedCheckbox = this;
	});

	// Data-Table Dragable
	$('body').on('click', '.table-dragable tbody .grabable', function(e){
		e.preventDefault();
		return false;
	});
	$('body').on('mousedown', '.table-dragable tbody .grabable', function(e){
		let tr = $(e.target).closest('tr'), sy = e.pageY, drag;
		if ($(e.target).is('tr')) tr = $(e.target);
		let index = tr.index();
		$(tr).addClass('grabbed');
		$(tr).closest('tbody').css('unser-input', 'unset');
		function move(e) {
			if (!drag && Math.abs(e.pageY - sy) < 10) return;
			drag = true;
			tr.siblings().each(function() {
				let s = $(this), i = s.index(), y = s.offset().top;
				if (e.pageY >= y && e.pageY < y + s.outerHeight()) {
					if (i < tr.index()) s.insertAfter(tr);
					else s.insertBefore(tr);
					return false;
				}
			});
		}
		function up(e) {
			if (drag && index != tr.index()) {
				drag = false;
			}
			$(document).off('mousemove', move).off('mouseup', up);
			$(tr).removeClass('grabbed');
			$(tr).closest('tbody').css('unser-input', '');
		}
		$(document).mousemove(move).mouseup(up);
	});

	// Data-Table Sorting (Page Reload)
	$('.table-sortable thead th[data-sort]').click(function(){
		let params = {};

		window.location.search.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(str, key, value) {
			params[key] = value;
		});

		params.sort = $(this).data('sort');

		window.location.search = $.param(params);
	});

/*
 * Escape HTML
 */
function escapeHTML(string) {
	let entityMap = {
			"&": "&amp;",
			"<": "&lt;",
			">": "&gt;",
			'"': '&quot;',
			"'": '&#39;',
			"/": '&#x2F;'
	};
	return String(string).replace(/[&<>"'\/]/g, function (s) {
			return entityMap[s];
	});
};

/* ========================================================================
 * Bootstrap: dropdown.js v3.3.7
 * http://getbootstrap.com/javascript/#dropdowns
 * ========================================================================
 * Copyright 2011-2016 Twitter, Inc.
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 * ======================================================================== */

+function ($) {
	'use strict';

		// DROPDOWN CLASS DEFINITION
		// =========================

	let backdrop = '.dropdown-backdrop'
	let toggle   = '[data-toggle="dropdown"]'
	let Dropdown = function (element) {
		$(element).on('click.bs.dropdown', this.toggle)
	}

	Dropdown.VERSION = '3.3.7'

	function getParent($this) {
		let selector = $this.attr('data-target')

		if (!selector) {
			selector = $this.attr('href')
			selector = selector && /#[A-Za-z]/.test(selector) && selector.replace(/.*(?=#[^\s]*$)/, '') // strip for ie7
		}

		let $parent = selector && $(selector)

		return $parent && $parent.length ? $parent : $this.closest('.dropdown')
	}

	function clearMenus(e) {
		if (e && e.which === 3) return
		$(backdrop).remove()
		$(toggle).each(function () {
			let $this         = $(this)
			let $parent       = getParent($this)
			let relatedTarget = { relatedTarget: this }

			if (!$parent.hasClass('open')) return

			if (e && e.type == 'click' && /input|textarea/i.test(e.target.tagName) && $.contains($parent[0], e.target)) return

			$parent.trigger(e = $.Event('hide.bs.dropdown', relatedTarget))

			if (e.isDefaultPrevented()) return

			$this.attr('aria-expanded', 'false')
			$parent.removeClass('open').trigger($.Event('hidden.bs.dropdown', relatedTarget))
		})
	}

	Dropdown.prototype.toggle = function (e) {
		let $this = $(this)

		if ($this.is('.disabled, :disabled')) return

		let $parent  = getParent($this)
		let isActive = $parent.hasClass('open')

		clearMenus()

		if (!isActive) {
			if ('ontouchstart' in document.documentElement && !$parent.closest('.navbar-nav').length) {
					// if mobile we use a backdrop because click events don't delegate
				$(document.createElement('div'))
					.addClass('dropdown-backdrop')
					.insertAfter($(this))
					.on('click', clearMenus)
			}

			let relatedTarget = { relatedTarget: this }
			$parent.trigger(e = $.Event('show.bs.dropdown', relatedTarget))

			if (e.isDefaultPrevented()) return

			$this
				.trigger('focus')
				.attr('aria-expanded', 'true')

			$parent
				.toggleClass('open')
				.trigger($.Event('shown.bs.dropdown', relatedTarget))
		}

		return false
	}

	Dropdown.prototype.keydown = function (e) {
		if (!/(38|40|27|32)/.test(e.which) || /input|textarea/i.test(e.target.tagName)) return

		let $this = $(this)

		e.preventDefault()
		e.stopPropagation()

		if ($this.is('.disabled, :disabled')) return

		let $parent  = getParent($this)
		let isActive = $parent.hasClass('open')

		if (!isActive && e.which != 27 || isActive && e.which == 27) {
			if (e.which == 27) $parent.find(toggle).trigger('focus')
			return $this.trigger('click')
		}

		let desc = ' li:not(.disabled):visible a'
		let $items = $parent.find('.dropdown-menu' + desc)

		if (!$items.length) return

		let index = $items.index(e.target)

		if (e.which == 38 && index > 0)                 index--         // up
		if (e.which == 40 && index < $items.length - 1) index++         // down
		if (!~index)                                    index = 0

		$items.eq(index).trigger('focus')
	}


		// DROPDOWN PLUGIN DEFINITION
		// ==========================

	function Plugin(option) {
		return this.each(function () {
			let $this = $(this)
			let data  = $this.data('bs.dropdown')

			if (!data) $this.data('bs.dropdown', (data = new Dropdown(this)))
			if (typeof option == 'string') data[option].call($this)
		})
	}

	let old = $.fn.dropdown

	$.fn.dropdown             = Plugin
	$.fn.dropdown.Constructor = Dropdown


		// DROPDOWN NO CONFLICT
		// ====================

	$.fn.dropdown.noConflict = function () {
		$.fn.dropdown = old
		return this
	}


		// APPLY TO STANDARD DROPDOWN ELEMENTS
		// ===================================

	$(document)
		.on('click.bs.dropdown.data-api', clearMenus)
		.on('click.bs.dropdown.data-api', '.dropdown form', function (e) { e.stopPropagation() })
		.on('click.bs.dropdown.data-api', toggle, Dropdown.prototype.toggle)
		.on('keydown.bs.dropdown.data-api', toggle, Dropdown.prototype.keydown)
		.on('keydown.bs.dropdown.data-api', '.dropdown-menu', Dropdown.prototype.keydown)

}(jQuery);
