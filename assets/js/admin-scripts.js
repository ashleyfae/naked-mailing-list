jQuery(document).ready(function ($) {

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

});