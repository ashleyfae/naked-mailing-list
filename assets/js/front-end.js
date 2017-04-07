/**
 * Front-End Scripts
 *
 * @package   naked-mailing-list
 * @copyright Copyright (c) 2017, Ashley Gibson
 * @license   GPL2+
 * @since     1.0
 */

(function ($) {

	/**
	 * Submit subscribe form
	 */
	$('.nml-subscribe-form').submit(function (e) {

		e.preventDefault();

		var form = $(this);
		var response_field = form.find('.nml-subscribe-response');
		var button = form.find('button');
		var button_text = button.html();

		// Setup loading.
		button.attr('disabled', true);
		button.empty().append('<i class="fa fa-spinner fa-spin"></i>');

		response_field.empty();

		var data = {
			action: 'nml_process_signup',
			email: form.find('input[name="nml_email_address"]').val(),
			first_name: form.find('input[name="nml_first_name"]').val(),
			last_name: form.find('input[name="nml_last_name"]').val(),
			referer: form.find('input[name="_wp_http_referer"]').val(),
			form_name: form.find('input[name="nml_form_name"]').val(),
			list: form.find('input[name="nml_list"]').val(),
			tags: form.find('input[name="nml_tags"]').val()
		};

		$.ajax({
			type: "POST",
			url: NML.ajaxurl,
			dataType: "json",
			data: data,
			xhrFields: {
				withCredentials: true
			},
			success: function (response) {

				button.attr('disabled', false);
				button.empty().append(button_text);

				if (true == response.success) {
					// Clear input fields.
					form.find('input').each(function () {
						if ('hidden' == $(this).attr('type') || 'submit' == $(this).attr('type')) {
							return true; // Skip
						}

						$(this).val('');
					});

					// Send Google Analytics event.
					if (typeof ga != 'undefined') {
						ga('send', 'event', 'Email', 'subscribe', 'Email Subscribe ' + data.form_name);
					}

					// Show success message.
					response_field.append('<div class="nml-success">' + response.data + '</div>');
				} else if (false == response.success) {
					response_field.append(response.data);
				} else {
					console.log(response);
				}

			}
		});

	});

})(jQuery);