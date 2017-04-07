jQuery(document).ready(function ($) {

	/**
	 * General NML
	 */
	var Naked_Mailing_List = {

		init: function () {
			$('.nml-add-new-list').on('click', '.button', this.addListCheckbox);
			$('#nml-add-new-list').keypress(this.addListCheckbox);
		},

		/**
		 * Add new list checkbox
		 * @param e
		 * @returns {boolean}
		 */
		addListCheckbox: function (e) {
			if ('click' == e.type) {
				e.preventDefault();
			}

			if ('keypress' == e.type && 13 != e.which) {
				return true;
			} else {
				e.preventDefault();
			}

			var wrap = $(this).parents('.nml-lists-wrap'),
				checkboxWrap = wrap.find('.nml-multicheck-wrap'),
				checkboxName = checkboxWrap.data('name'),
				newTerm = wrap.find('#nml-add-new-list');

			checkboxWrap.append('<label><input type="checkbox" name="' + checkboxName + '" value="' + newTerm.val() + '" checked="checked"> ' + newTerm.val() + '</label>');
			newTerm.val('');
		}

	};
	Naked_Mailing_List.init();

	/**
	 * Export JavaScript
	 */
	var NML_Export = {

		init: function () {
			this.submit();
			this.dismiss_message();
		},

		submit: function () {

			var self = this;

			$(document.body).on('submit', '.nml-export-form', function (e) {
				e.preventDefault();

				var submitButton = $(this).find('input[type="submit"]');

				if (!submitButton.hasClass('button-disabled')) {

					var data = $(this).serialize();

					submitButton.addClass('button-disabled');
					$(this).find('.notice-wrap').remove();
					$(this).append('<div class="notice-wrap"><span class="spinner is-active"></span><div class="nml-progress"><div></div></div></div>');

					// start the process
					self.process_step(1, data, self);

				}
			});

		},

		process_step: function (step, data, self) {

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					form: data,
					action: 'nml_do_ajax_export',
					step: step
				},
				dataType: "json",
				success: function (response) {
					if ('done' == response.step || response.error || response.success) {

						// We need to get the actual in progress form, not all forms on the page
						var export_form = $('.nml-export-form').find('.nml-progress').parent().parent();
						var notice_wrap = export_form.find('.notice-wrap');

						export_form.find('.button-disabled').removeClass('button-disabled');

						if (response.error) {

							var error_message = response.message;
							notice_wrap.html('<div class="updated error"><p>' + error_message + '</p></div>');

						} else if (response.success) {

							var success_message = response.message;
							notice_wrap.html('<div id="nml-batch-success" class="updated notice is-dismissible"><p>' + success_message + '<span class="notice-dismiss"></span></p></div>');

						} else {

							notice_wrap.remove();
							window.location = response.url;

						}

					} else {

						$('.nml-progress div').animate({
							width: response.percentage + '%'
						}, 50, function () {
							// Animation complete.
						});
						self.process_step(parseInt(response.step), data, self);

					}
				}
			}).fail(function (response) {
				if (window.console && window.console.log) {
					console.log(response);
				}
			})

		},

		dismiss_message: function () {

			$('body').on('click', '#nml-batch-success .notice-dismiss', function () {
				$('#nml-batch-success').parent().slideUp('fast');
			});

		}

	};
	NML_Export.init();

	/**
	 * Import JavaScript
	 */
	var NML_Import = {

		init: function () {
			this.submit();
		},

		submit: function () {

			var self = this;

			$('.nml-import-form').ajaxForm({
				beforeSubmit: self.before_submit,
				success: self.success,
				complete: self.complete,
				dataType: 'json',
				error: self.error
			});

		},

		before_submit: function (arr, $form, options) {

			$form.find('.notice-wrap').remove();
			$form.append('<div class="notice-wrap"><span class="spinner is-active"></span><div class="nml-progress"><div></div></div></div>');

			// Check whether client browser fully supports all File API
			if (window.File && window.FileReader && window.FileList && window.Blob) {

				// HTML5 File API is supported by browser

			} else {

				var import_form = $('.nml-import-form').find('.nml-progress').parent().parent();
				var notice_wrap = import_form.find('.notice-wrap');

				import_form.find('.button-disabled').removeClass('button-disabled');

				// Error for older unsupported browsers that doesn't support HTML5 File API
				notice_wrap.html('<div class="update error"><p>' + nml_vars.unsupported_browser + '</p></div>');

				return false;

			}

		},

		success: function (responseText, statusText, xhr, $form) {

		},

		complete: function (xhr) {
			console.log('complete');

			var response = jQuery.parseJSON(xhr.responseText);

			if (response.success) {

				var $form = $('.nml-import-form .notice-wrap').parent();

				$form.find('.nml-import-file-wrap,.notice-wrap').remove();

				$form.find('.nml-import-options').slideDown();

				// Show column mapping
				var select = $form.find('select.nml-import-csv-column');
				var row = select.parent().parent();
				var options = '';

				var columns = response.data.columns.sort(function (a, b) {
					if (a < b) return -1;
					if (a > b) return 1;
					return 0;
				});

				$.each(columns, function (key, value) {
					options += '<option value="' + value + '">' + value + '</option>';
				});

				select.append(options);

				select.on('change', function () {
					var $key = $(this).val();

					if (!$key) {

						$(this).parent().next().html('');

					} else {

						if (false != response.data.first_row[$key]) {
							$(this).parent().next().html(response.data.first_row[$key]);
						} else {
							$(this).parent().next().html('');
						}

					}
				});

				$('body').on('click', '.nml-import-proceed', function (e) {

					e.preventDefault();

					$form.append('<div class="notice-wrap"><span class="spinner is-active"></span><div class="nml-progress"><div></div></div></div>');

					response.data.mapping = $form.serialize();

					NML_Import.process_step(1, response.data, self);

				});

			} else {

				NML_Import.error(xhr);

			}

		},

		error: function (xhr) {

			// Something went wrong - display an error.

			var response = jQuery.parseJSON(xhr.responseText);
			console.log(response);
			var import_form = $('.nml-import-form').find('.nml-progress').parent().parent();
			var notice_wrap = import_form.find('.notice-wrap');

			import_form.find('.button-disabled').removeClass('button-disabled');

			if (response.data.error) {
				notice_wrap.html('<div class="update error"><p>' + response.data.error + '</p></div>');
			} else {
				notice_wrap.remove();
				if (window.console && window.console.log) {
					console.log(response);
				}
			}

		},

		process_step: function (step, import_data, self) {

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					form: import_data.form,
					nonce: import_data.nonce,
					class: import_data.class,
					upload: import_data.upload,
					mapping: import_data.mapping,
					action: 'nml_do_ajax_import',
					step: step
				},
				dataType: "json",
				success: function (response) {

					if ('done' == response.data.step || response.data.error) {

						// We need to get the actual in progress form, not all forms on the page
						var import_form = $('.nml-import-form').find('.nml-progress').parent().parent();
						var notice_wrap = import_form.find('.notice-wrap');

						import_form.find('.button-disabled').removeClass('button-disabled');

						if (response.data.error) {

							notice_wrap.html('<div class="update error"><p>' + response.data.error + '</p></div>');


						} else {

							import_form.find('.nml-import-options').hide();
							$('html, body').animate({
								scrollTop: import_form.parent().offset().top
							}, 500);

							notice_wrap.html('<div class="updated"><p>' + response.data.message + '</p></div>');

						}

					} else {

						$('.nml-progress div').animate({
							width: response.data.percentage + '%'
						}, 50, function () {
							// Animation complete.
						});

						NML_Import.process_step(parseInt(response.data.step), import_data, self);

					}

				}
			}).fail(function (response) {
				if (window.console && window.console.log) {
					console.log(response);
				}
			});

		}

	};
	NML_Import.init();

	var NML_Chart;

	/**
	 * Graph Reports
	 */
	var NML_Reports = {

		init: function () {
			this.date_options();
			this.load_graph();
			this.update_graph();
		},

		date_options: function () {

			$('#nml-graphs-date-options').change(function () {
				var self = $(this),
					date_range_options = $('#nml-date-range-options');

				if ('other' == self.val()) {
					date_range_options.show();
				} else {
					date_range_options.hide();
				}
			});

		},

		load_graph: function () {

			if ('undefined' == typeof Chart) {
				return;
			}

			var data = {
				action: 'nml_reports_get_signups_data',
				nonce: $('#nnl_get_signups_data_nonce').val()
			};

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: data,
				dataType: "json",
				success: function (response) {

					if (response.success == true) {

						var data = response.data,
							range = $('#nml-graphs-date-options').val();

						NML_Reports.draw_graph(data, range);

						// Set total
						$('#nml-reports-total-signups').find('span').text(response.data.total);

					} else {

						if (window.console && window.console.log) {
							console.log(response);
						}

					}

				}
			}).fail(function (response) {
				if (window.console && window.console.log) {
					console.log(response);
				}
			});

		},

		draw_graph: function (data, range) {

			console.log(range);

			NML_Chart = new Chart($('#nml-signups-graph'), {
				type: 'line',
				data: {
					labels: data.labels,
					datasets: [
						{
							label: 'New Signups', // @todo
							data: data.data,
							fill: false,
							lineTension: 0.1,
							backgroundColor: "rgba(75,192,192,0.4)",
							borderColor: "rgba(75,192,192,1)",
							borderCapStyle: 'butt',
							borderDash: [],
							borderDashOffset: 0.0,
							borderJoinStyle: 'miter',
							pointBorderColor: "rgba(75,192,192,1)",
							pointBackgroundColor: "#fff",
							pointBorderWidth: 1,
							pointHoverRadius: 5,
							pointHoverBackgroundColor: "rgba(75,192,192,1)",
							pointHoverBorderColor: "rgba(220,220,220,1)",
							pointHoverBorderWidth: 2,
							pointHitRadius: 30
						}
					]
				},
				options: {
					maintainAspectRatio: false,
					scales: {
						yAxes: [{
							ticks: {
								beginAtZero: true,
								userCallback: function(label, index, labels) {
									// when the floored value is the same as the value we have a whole number
									if (Math.floor(label) === label) {
										return label;
									}

								}
							}
						}]
					}
				}
			});

		},

		update_graph: function () {

			if ('undefined' == typeof Chart) {
				return;
			}

			$('#nml-reports-filter').on('submit', function (e) {

				e.preventDefault();

				var form_input = $(this).serialize(),
					range = $('#nml-graphs-date-options').val();

				var data = {
					action: 'nml_reports_update_data_ajax',
					date: form_input,
					nonce: $('#nnl_get_signups_data_nonce').val()
				};

				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: data,
					dataType: "json",
					success: function (response) {

						if (response.success == true) {

							var data = response.data;

							NML_Chart.destroy();
							NML_Reports.draw_graph(data, range);

							// Set total
							$('#nml-reports-total-signups').find('span').text(response.data.total);

						} else {
							console.log(response);
						}

					}
				}).fail(function (response) {
					if (window.console && window.console.log) {
						console.log(response);
					}
				});

			});

		}

	};
	NML_Reports.init();

	/**
	 * Autocomplete for Tags
	 */
	var NML_Tags = {

		/**
		 * Initialize
		 */
		init: function () {
			var self = this,
				ajaxtag = $('.nml-ajaxtag'),
				wrapper = ajaxtag.parents('.nml-tags-wrap');

			$('.nml-tags-wrap').each(function () {
				NML_Tags.quickClicks($(this));
			});

			$('.button', ajaxtag).click(function () {
				self.flushTags($(this).closest('.nml-tags-wrap'));
			});

			ajaxtag.each(function () {
				var newTag = $('.nml-new-tag', $(this));
				var type = $(this).parents('.nml-tags-wrap').data('type');

				newTag.keyup(function (e) {
					if (e.which == 13) {
						NML_Tags.flushTags($(this).closest('.nml-tags-wrap'));
						return false;
					}
				}).keypress(function (e) {
					if (13 == e.which) {
						e.preventDefault();
						return false;
					}
				}).suggest(ajaxurl + '?action=nml_suggest_tags&type=' + type);
			});

			// Save tags on save/publish.
			$('#nml-newsletters-page-wrapper, #nml-subscribers-page-wrapper > form').submit(function (e) {
				$('.nml-tags-wrap').each(function () {
					NML_Tags.flushTags(this, false, 1);
				});
			});
		},

		/**
		 * Clean Tags
		 */
		clean: function (tags) {
			return tags.replace(/\s*,\s*/g, ',').replace(/,+/g, ',').replace(/[,\s]+$/, '').replace(/^[,\s]+/, '');
		},

		/**
		 * Parse Tags
		 */
		parseTags: function (el) {
			var id = el.id,
				num = id.split('-check-num-')[1],
				tagbox = $(el).closest('.nml-tags-wrap'),
				thetags = tagbox.find('textarea'),
				current_tags = thetags.val().split(','),
				new_tags = [];

			delete current_tags[num];

			$.each(current_tags, function (key, val) {
				val = $.trim(val);
				if (val) {
					new_tags.push(val);
				}
			});

			thetags.val(this.clean(new_tags.join(',')));

			this.quickClicks(tagbox);

			return false;
		},

		/**
		 * Quick Links
		 *
		 * Handles adding tags.
		 *
		 * @param el
		 */
		quickClicks: function (el) {
			var thetags = $('textarea', el),
				tagchecklist = $('.nml-tags-checklist', el),
				id = $(el).attr('id'),
				current_tags,
				disabled;

			if (!thetags.length)
				return;

			disabled = thetags.prop('disabled');

			current_tags = thetags.val().split(',');
			tagchecklist.empty();

			$.each(current_tags, function (key, val) {
				var span, xbutton;

				val = $.trim(val);

				if (!val)
					return;

				// Create a new span, and ensure the text is properly escaped.
				span = $('<span />').text(val);

				// If tags editing isn't disabled, create the X button.
				if (!disabled) {
					xbutton = $('<a id="' + id + '-check-num-' + key + '" class="ntdelbutton">X</a>');
					xbutton.click(function () {
						NML_Tags.parseTags(this);
					});
					span.prepend('&nbsp;').prepend(xbutton);
				}

				// Append the span to the tag list.
				tagchecklist.append(span);
			});
		},

		/**
		 * Flush Tags
		 *
		 * Called on add tag and save.
		 *
		 * @param el
		 * @param a
		 * @param f
		 */
		flushTags: function (el, a, f) {
			a = a || false;

			var text,
				tags = $('textarea', el),
				newtag = $('.nml-new-tag', el),
				tagsval,
				newtags;

			text = a ? $(a).text() : newtag.val();

			tagsval = tags.val();
			newtags = tagsval ? tagsval + ',' + text : text;

			newtags = this.clean(newtags);
			newtags = NML_Tags.uniqueArray(newtags.split(',')).join(',');

			tags.val(newtags);
			this.quickClicks(el);

			if (!a)
				newtag.val('');
			if ('undefined' == typeof(f))
				newtag.focus();

			return false;
		},

		/**
		 * Unique Array, No Empty
		 *
		 * @param array
		 * @returns {Array}
		 */
		uniqueArray: function (array) {
			var out = [];

			$.each(array, function (key, val) {
				val = $.trim(val);

				if (val && $.inArray(val, out) === -1) {
					out.push(val);
				}
			});

			return out;
		}

	};
	NML_Tags.init();

});