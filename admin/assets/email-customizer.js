/* global jQuery, returndesk_admin */
(function ($) {
	'use strict';

	var $form = $('#gg-settings-form');
	var $toast = $('#gg-toast');

	function showToast(message, isError) {
		if (!$toast.length) {
			return;
		}
		$toast.text(message).toggleClass('gg-toast-error', !!isError).addClass('gg-toast-show');
		setTimeout(function () {
			$toast.removeClass('gg-toast-show');
		}, 3000);
	}

	function collectSettings() {
		var data = {};
		if (!$form.length) {
			return data;
		}

		var arr = $form.serializeArray();
		$.each(arr, function (_, field) {
			var match = field.name.match(/returndesk_settings\[(.+?)\]/);
			if (!match) {
				return;
			}
			data[match[1]] = field.value;
		});

		['email_enable_new_request_admin', 'email_enable_new_request_customer', 'email_enable_status_update_customer'].forEach(function (key) {
			if (!data[key]) {
				data[key] = 'no';
			}
		});

		return data;
	}

	function setActiveTemplate(template) {
		template = (template || 'new_request_admin').toString();
		$('.gg-email-template-panel').each(function () {
			var $panel = $(this);
			$panel.toggleClass('gg-hidden', ($panel.data('template') || '').toString() !== template);
		});
		updatePreview();
	}

	var previewTimer = null;
	function updatePreviewSoon() {
		clearTimeout(previewTimer);
		previewTimer = setTimeout(updatePreview, 60);
	}

	function updatePreview() {
		if (!$form.length) {
			return;
		}

		var template = ($('#gg-email-template').val() || 'new_request_customer').toString();

		$.post(returndesk_admin.ajax_url, {
			action: 'returndesk_email_preview',
			nonce: returndesk_admin.preview_nonce,
			template: template,
			settings: collectSettings()
		}).done(function (resp) {
			if (!resp || !resp.success || !resp.data) {
				return;
			}

			$('#gg-email-preview-to').text((resp.data.to || '').toString());
			$('#gg-email-preview-subject').text((resp.data.subject || '').toString());

			var $iframe = $('#gg-email-preview-iframe');
			if ($iframe.length) {
				$iframe.attr('srcdoc', (resp.data.html || '').toString());
			}
		});
	}

	$(document).on('change', '#gg-email-template', function () {
		setActiveTemplate($(this).val());
	});

	$(document).on('click', '.gg-email-device-btn', function () {
		var $btn = $(this);
		var device = ($btn.data('preview-device') || 'desktop').toString();
		$('.gg-email-device-btn').removeClass('is-active');
		$btn.addClass('is-active');
		$('.gg-email-preview-wrap').attr('data-device', device);
	});

	if ($form.length) {
		$form.on(
			'input change',
			'.gg-email-customizer-inline input, .gg-email-customizer-inline textarea, .gg-email-customizer-inline select',
			updatePreviewSoon
		);
	}

	$(document).on('click', '#gg-email-test-btn', function () {
		var $btn = $(this);
		var targetEmail = ($('#gg-email-test-target').val() || '').toString();
		var template = ($('#gg-email-template').val() || 'status_approved').toString();

		$btn.prop('disabled', true);
		$.post(returndesk_admin.ajax_url, {
			action: 'returndesk_send_test_email',
			nonce: returndesk_admin.test_email_nonce,
			target_email: targetEmail,
			template: template,
			settings: collectSettings()
		}).done(function (response) {
			if (response && response.success) {
				showToast((response.data && response.data.message) ? response.data.message : 'Test email sent.', false);
			} else {
				showToast((response && response.data && response.data.message) || (returndesk_admin && returndesk_admin.i18n && returndesk_admin.i18n.test_email_error) || 'Could not send test email.', true);
			}
		}).fail(function () {
			showToast((returndesk_admin && returndesk_admin.i18n && returndesk_admin.i18n.test_email_error) || 'Could not send test email.', true);
		}).always(function () {
			$btn.prop('disabled', false);
		});
	});

	// Init.
	if ($('#gg-email-template').length) {
		setActiveTemplate($('#gg-email-template').val() || 'new_request_admin');
	}
})(jQuery);
