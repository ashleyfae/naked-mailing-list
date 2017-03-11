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

		console.log(data);

		$.ajax({
			type: "POST",
			url: NML.ajaxurl,
			dataType: "json",
			data: data,
			xhrFields: {
				withCredentials: true
			},
			success: function (response) {

				console.log(response);

				if (true == response.success) {
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