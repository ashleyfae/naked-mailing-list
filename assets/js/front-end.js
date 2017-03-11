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

		var data = {
			action: 'nml_process_signup',
			email: form.find('input[name="nml_email_address"]').val(),
			first_name: form.find('input[name="nml_first_name"]').val(),
			last_name: form.find('input[name="nml_last_name"]').val(),
			referrer: form.find('input[name="_wp_http_referer"]').val(),
			form_name: form.find('input[name="nml_form_name"]').val()
		};

		console.log(data);

	});

})(jQuery);