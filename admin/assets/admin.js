/* global jQuery, returndesk_admin */
(function ($) {
	'use strict';

	var $saveBtn = $('#gg-save-btn');
	var $resetBtn = $('#gg-reset-btn');
	var $toast = $('#gg-toast');
	var $form = $('#gg-settings-form');
	var ggToastTimer = null;
	var attachmentModalState = { items: [], index: 0 };
	var requestPagerState = { page: 1, perPage: 10 };

	// Prevent Select2/SelectWoo from opening the dropdown when clicking the "x" remove icon on a selected item.
	// Select2 opens on mousedown of the selection container, so we need to suppress the next open event too.
	var ggSuppressSelectOpen = false;
	$(document).on('mousedown click', '.select2-selection__choice__remove', function (e) {
		ggSuppressSelectOpen = true;
		e.preventDefault();
		e.stopImmediatePropagation();
	});
	$(document).on('select2:opening', 'select.gg-multi-select', function (e) {
		if (ggSuppressSelectOpen) {
			ggSuppressSelectOpen = false;
			e.preventDefault();
		}
	});

	function showToast(message, type) {
		if (ggToastTimer) {
			clearTimeout(ggToastTimer);
			ggToastTimer = null;
		}

		$toast.removeClass('gg-toast-success gg-toast-error gg-toast-reset');
		if (type === 'error') {
			$toast.addClass('gg-toast-error');
		} else if (type === 'reset') {
			$toast.addClass('gg-toast-reset');
		} else {
			$toast.addClass('gg-toast-success');
		}

		$toast.removeClass('gg-toast-show');
		$toast.text(message);
		if ($toast[0]) { void $toast[0].offsetHeight; }
		$toast.addClass('gg-toast-show');

		ggToastTimer = setTimeout(function () {
			$toast.removeClass('gg-toast-show');
			ggToastTimer = null;
		}, 3000);
	}

	function getSelectPlugin(preferWoo) {
		if (preferWoo && $.fn.selectWoo) { return 'selectWoo'; }
		if ($.fn.select2) { return 'select2'; }
		if ($.fn.selectWoo) { return 'selectWoo'; }
		return null;
	}

	function initMultiSelects($context) {
		var plugin = getSelectPlugin(true) || getSelectPlugin();
		if (!plugin) {
			return;
		}

		function parseCsvIds(csv) {
			return (csv || '')
				.toString()
				.split(',')
				.map(function (v) { return parseInt(v, 10); })
				.filter(function (v) { return Number.isFinite(v) && v > 0; })
				.map(function (v) { return v.toString(); });
		}

		function setAjaxSelectFromCsv($select, csv) {
			if (!$select || !$select.length) {
				return;
			}

			var ids = parseCsvIds(csv);
			$select.find('option').remove();
			ids.forEach(function (id) {
				$select.append(new Option('#' + id, id, true, true));
			});
			$select.val(ids).trigger('change');
			setInlinePlaceholder($select);

			var lookupAction = $select.data('lookup-action');
			if (!lookupAction || ids.length === 0 || !window.returndesk_admin) {
				return;
			}

			$.post(returndesk_admin.ajax_url, {
				action: lookupAction,
				nonce: returndesk_admin.search_nonce,
				ids: ids
			}).done(function (resp) {
				if (!resp || !resp.success || !resp.data || !Array.isArray(resp.data.results)) {
					return;
				}
				resp.data.results.forEach(function (item) {
					var id = (item && item.id !== undefined) ? item.id.toString() : '';
					var text = (item && item.text) ? item.text.toString() : '';
					if (!id || !text) {
						return;
					}
					var $opt = $select.find('option[value="' + id.replace(/"/g, '\\"') + '"]');
					if ($opt.length) {
						$opt.text(text);
					} else {
						$select.append(new Option(text, id, true, true));
					}
				});
				$select.trigger('change');
			});
		}

		function setInlinePlaceholder($select) {
			var ph = $select.data('placeholder') || '';
			var values = $select.val() || [];
			var hasSelection = Array.isArray(values) ? values.length > 0 : !!values;
			var active = hasSelection ? '' : ph;
			var $field = $select.next('.select2').find('.select2-search__field');
			if ($field.length) {
				$field.attr('placeholder', active);
			}
			$('.select2-container--open .select2-search__field').attr('placeholder', active);
		}

		var $scope = ($context && $context.length) ? $context : $(document);
		$scope
			.find('select.gg-multi-select[multiple]')
			.filter(function () { return $(this).is(':visible'); })
			.each(function () {
				var $select = $(this);

				if ($select.hasClass('select2-hidden-accessible')) {
					var $container = $select.next('.select2');
					if ($container.length && $container.outerWidth() > 10) {
						return;
					}
					try { $select[plugin]('destroy'); } catch (e) { /* ignore */ }
				}

				var options = {
					width: '100%',
					closeOnSelect: true,
					placeholder: $select.data('placeholder') || ''
				};

				var action = $select.data('search-action');
				if (action && window.returndesk_admin) {
					options.minimumInputLength = 1;
					options.ajax = {
						url: returndesk_admin.ajax_url,
						type: 'POST',
						dataType: 'json',
						delay: 250,
						data: function (params) {
							return {
								action: action,
								nonce: returndesk_admin.search_nonce,
								term: params.term || '',
								page: params.page || 1
							};
						},
						processResults: function (data) {
							return data || { results: [] };
						},
						cache: true
					};
				}

				$select[plugin](options);

				$select.off('change.ggplaceholder').on('change.ggplaceholder', function () {
					setInlinePlaceholder($(this));
				});
				setInlinePlaceholder($select);

				$select
					.off('select2:select.ggclear select2:close.ggclear select2:open.ggclear select2:unselect.ggclear')
					.on('select2:select.ggclear select2:close.ggclear select2:open.ggclear select2:unselect.ggclear', function () {
						var $s = $(this);
						$s.next('.select2').find('.select2-search__field').val('');
						$('.select2-container--open .select2-search__field').val('');
						setInlinePlaceholder($s);
					});

			});

		initMultiSelects.setAjaxSelectFromCsv = setAjaxSelectFromCsv;
	}

	function syncStoreCountryState($context) {
		var $scope = ($context && $context.length) ? $context : $(document);
		$scope.find('select[name="returndesk_settings[return_store_country_state]"]').each(function () {
			var $select = $(this);
			var $form = $select.closest('form');
			var $country = $form.find('input[name="returndesk_settings[return_store_country]"]').first();
			var $state = $form.find('input[name="returndesk_settings[return_store_state]"]').first();

			if (!$country.length || !$state.length) {
				return;
			}

			function parse(value) {
				var raw = (value || '').toString();
				var country = raw;
				var state = '';
				if (raw.indexOf(':') !== -1) {
					var parts = raw.split(':');
					country = parts[0] || '';
					state = parts[1] || '';
				}
				country = country.toUpperCase().replace(/[^A-Z]/g, '');
				state = state.toUpperCase().replace(/[^A-Z0-9]/g, '');
				return { country: country, state: state };
			}

			function setHiddenFromSelect() {
				var parsed = parse($select.val());
				$country.val(parsed.country);
				$state.val(parsed.state);
			}

			var desired = ($country.val() || '').toString();
			var desiredState = ($state.val() || '').toString();
			var combined = desired && desiredState ? (desired + ':' + desiredState) : desired;

			if (combined && $select.find('option[value="' + combined.replace(/"/g, '\\"') + '"]').length) {
				$select.val(combined);
			} else if (desired) {
				$select.val(desired);
			}
			$select.trigger('change');

			$select.off('change.ggcountry').on('change.ggcountry', setHiddenFromSelect);
			setHiddenFromSelect();
		});
	}

	function reorderGeneralSections($panel) {
		var $general = ($panel && $panel.length) ? $panel.filter('[data-panel="general"]') : $('.gg-tab-panel[data-panel="general"]');
		if (!$general.length) {
			return;
		}

		$general.each(function () {
			var $root = $(this);
			var $common = $root.find('.gg-card[data-section="common-settings"]').first();
			var $returns = $root.find('.gg-card[data-section="return-settings"]').first();
			var $address = $root.find('.gg-card[data-section="common-address"]').first();

			if (!$common.length || !$returns.length) {
				return;
			}

			$returns.insertAfter($common);
			if ($address.length) {
				$address.appendTo($root);
			}
		});
	}

	function initCommonSettingsAccordion() {
		$('.gg-tab-panel[data-panel="general"] .gg-card, .gg-tab-panel[data-panel="advanced"] .gg-card').each(function (cardIndex) {
			var $card = $(this);
			if ($card.data('ggAccordionInit')) {
				return;
			}

			var $header = $card.find('.gg-card-header').first();
			if (!$header.length) {
				return;
			}

			$card.data('ggAccordionInit', true).addClass('gg-card-accordion-enabled');

			var $actions = $header.find('.gg-card-header-actions').first();
			if (!$actions.length) {
				$actions = $('<div class="gg-card-header-actions"></div>').appendTo($header);
			}

			var $toggle = $actions.find('[data-card-accordion-toggle]').first();
			if (!$toggle.length) {
				$toggle = $('<button type="button" class="gg-card-accordion-toggle" data-card-accordion-toggle aria-expanded="true"><span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span></button>');
				$actions.append($toggle);
			}

			var $content = $card.find('.gg-card-accordion-content').first();
			if (!$content.length) {
				$content = $('<div class="gg-card-accordion-content"></div>');
				$header.nextAll().appendTo($content);
				$header.after($content);
			}

			var panelName = $card.closest('.gg-tab-panel').data('panel') || 'panel';
			var storageKey = 'returndesk_free_card_collapsed_' + panelName + '_' + cardIndex;

			function setCollapsed(collapsed, skipAnimation) {
				$card.toggleClass('is-collapsed', !!collapsed);
				$toggle.attr('aria-expanded', collapsed ? 'false' : 'true');

				if (skipAnimation) {
					$content.toggle(!collapsed);
					return;
				}
				if (collapsed) {
					$content.stop(true, true).slideUp(160);
				} else {
					$content.stop(true, true).slideDown(180, function () {
						initMultiSelects($card);
					});
				}
			}

			var isCollapsed = false;
			try {
				isCollapsed = window.localStorage.getItem(storageKey) === '1';
			} catch (e) {
				isCollapsed = false;
			}
			setCollapsed(isCollapsed, true);

			function toggleAccordion() {
				var collapsed = !$card.hasClass('is-collapsed');
				setCollapsed(collapsed, false);
				try {
					window.localStorage.setItem(storageKey, collapsed ? '1' : '0');
				} catch (e) {
					/* no-op */
				}
			}

			$header.on('click', function () {
				toggleAccordion();
			});
			$toggle.on('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				toggleAccordion();
			});
		});
	}

	$('.gg-tab-btn').on('click', function () {
		var target = $(this).data('tab');
		$('.gg-tab-btn').removeClass('gg-tab-active');
		$(this).addClass('gg-tab-active');
		$('.gg-tab-panel').removeClass('gg-tab-panel-active');
		var $panel = $('[data-panel="' + target + '"]').addClass('gg-tab-panel-active');
		$('#gg-breadcrumb-current').text($(this).data('breadcrumb'));

		var visible = ($('#gg-footer-bar').data('tab-visible') || '').toString().split(',');
		$('#gg-footer-bar').toggle(visible.indexOf(target) !== -1);
		reorderGeneralSections($panel);
		syncStoreCountryState($panel);
		initMultiSelects($panel);
	});

	reorderGeneralSections($('.gg-tab-panel-active'));
	initCommonSettingsAccordion();
	syncStoreCountryState($('.gg-tab-panel-active'));
	initMultiSelects($('.gg-tab-panel-active'));

	function collectSettings() {
		var data = {};
		var arr = $form.serializeArray();
		$.each(arr, function (_, field) {
			var match = field.name.match(/returndesk_settings\[(.+?)\]/);
			if (!match) {
				return;
			}

			var key = match[1];
			if (key === 'allowed_statuses[]') {
				key = 'allowed_statuses';
			}

			if (key === 'allowed_statuses') {
				if (!Array.isArray(data[key])) {
					data[key] = [];
				}
				data[key].push(field.value);
			} else {
				data[key] = field.value;
			}
		});

		if (!data.enable_returns) data.enable_returns = 'no';
		if (!data.return_allow_sale_products) data.return_allow_sale_products = 'no';
		if (!data.email_enable_new_request_admin) data.email_enable_new_request_admin = 'no';
		if (!data.email_enable_new_request_customer) data.email_enable_new_request_customer = 'no';
		if (!data.email_enable_status_update_customer) data.email_enable_status_update_customer = 'no';
		if (!data.allowed_statuses) data.allowed_statuses = [];

		return data;
	}

	$saveBtn.on('click', function () {
		$saveBtn.prop('disabled', true);
		$.post(returndesk_admin.ajax_url, {
			action: 'returndesk_save_settings',
			nonce: returndesk_admin.save_nonce,
			settings: collectSettings()
		})
			.done(function (response) {
				if (response && response.success) {
					showToast(returndesk_admin.i18n.saved, 'success');
				} else {
					showToast((response && response.data && response.data.message) || returndesk_admin.i18n.save_error, 'error');
				}
			})
			.fail(function () {
				showToast(returndesk_admin.i18n.save_error, 'error');
			})
			.always(function () {
				$saveBtn.prop('disabled', false);
			});
	});

	$resetBtn.on('click', function () {
		if (!window.confirm(returndesk_admin.i18n.reset_confirm)) {
			return;
		}

		$.post(returndesk_admin.ajax_url, {
			action: 'returndesk_reset_settings',
			nonce: returndesk_admin.reset_nonce
		}).done(function (response) {
			if (response && response.success) {
				var settings = (response.data && response.data.settings) ? response.data.settings : null;
				if (settings) {
					$('input[name="returndesk_settings[enable_returns]"]').prop('checked', settings.enable_returns === 'yes');
					$('input[name="returndesk_settings[return_allow_sale_products]"]').prop('checked', settings.return_allow_sale_products === 'yes');
					$('input[name="returndesk_settings[email_enable_new_request_admin]"]').prop('checked', settings.email_enable_new_request_admin === 'yes');
					$('input[name="returndesk_settings[email_enable_new_request_customer]"]').prop('checked', settings.email_enable_new_request_customer === 'yes');
					$('input[name="returndesk_settings[email_enable_status_update_customer]"]').prop('checked', settings.email_enable_status_update_customer === 'yes');
					$('input[name="returndesk_settings[return_window_days]"]').val(settings.return_window_days || 7);
					$('textarea[name="returndesk_settings[return_guidelines]"]').val(settings.return_guidelines || '');
					$('textarea[name="returndesk_settings[return_reasons]"]').val(settings.return_reasons || '');
					$('select[name="returndesk_settings[terms_page_id]"]').val((settings.terms_page_id || 0).toString());
					$('input[name="returndesk_settings[return_store_address_1]"]').val(settings.return_store_address_1 || '');
					$('input[name="returndesk_settings[return_store_address_2]"]').val(settings.return_store_address_2 || '');
					$('input[name="returndesk_settings[return_store_city]"]').val(settings.return_store_city || '');
					$('input[name="returndesk_settings[return_store_state]"]').val(settings.return_store_state || '');
					$('input[name="returndesk_settings[return_store_country]"]').val(settings.return_store_country || '');
					$('input[name="returndesk_settings[return_store_postcode]"]').val(settings.return_store_postcode || '');
					$('input[name="returndesk_settings[return_store_phone]"]').val(settings.return_store_phone || '');
					syncStoreCountryState($form);
					$('input[name="returndesk_settings[customer_message]"]').val(settings.customer_message || '');
					$('input[name="returndesk_settings[admin_notification_email]"]').val(settings.admin_notification_email || '');
					$('input[name="returndesk_settings[email_subject_new_request_admin]"]').val(settings.email_subject_new_request_admin || '');
					$('textarea[name="returndesk_settings[email_body_new_request_admin]"]').val(settings.email_body_new_request_admin || '');
					$('input[name="returndesk_settings[email_heading_new_request_admin]"]').val(settings.email_heading_new_request_admin || '');
					$('input[name="returndesk_settings[email_items_title_new_request_admin]"]').val(settings.email_items_title_new_request_admin || '');
					$('textarea[name="returndesk_settings[email_additional_content_new_request_admin]"]').val(settings.email_additional_content_new_request_admin || '');
					$('input[name="returndesk_settings[email_subject_new_request_customer]"]').val(settings.email_subject_new_request_customer || '');
					$('textarea[name="returndesk_settings[email_body_new_request_customer]"]').val(settings.email_body_new_request_customer || '');
					$('input[name="returndesk_settings[email_heading_new_request_customer]"]').val(settings.email_heading_new_request_customer || '');
					$('input[name="returndesk_settings[email_items_title_new_request_customer]"]').val(settings.email_items_title_new_request_customer || '');
					$('textarea[name="returndesk_settings[email_additional_content_new_request_customer]"]').val(settings.email_additional_content_new_request_customer || '');
					$('input[name="returndesk_settings[email_subject_status_update_customer]"]').val(settings.email_subject_status_update_customer || '');
					$('textarea[name="returndesk_settings[email_body_status_update_customer]"]').val(settings.email_body_status_update_customer || '');
					$('input[name="returndesk_settings[email_heading_status_update_customer]"]').val(settings.email_heading_status_update_customer || '');
					$('input[name="returndesk_settings[email_items_title_status_update_customer]"]').val(settings.email_items_title_status_update_customer || '');
					$('textarea[name="returndesk_settings[email_additional_content_status_update_customer]"]').val(settings.email_additional_content_status_update_customer || '');
					$('input[name="returndesk_settings[email_subject_status_approved_customer]"]').val(settings.email_subject_status_approved_customer || '');
					$('textarea[name="returndesk_settings[email_body_status_approved_customer]"]').val(settings.email_body_status_approved_customer || '');
					$('input[name="returndesk_settings[email_heading_status_approved_customer]"]').val(settings.email_heading_status_approved_customer || '');
					$('input[name="returndesk_settings[email_items_title_status_approved_customer]"]').val(settings.email_items_title_status_approved_customer || '');
					$('textarea[name="returndesk_settings[email_additional_content_status_approved_customer]"]').val(settings.email_additional_content_status_approved_customer || '');
					$('input[name="returndesk_settings[email_subject_status_rejected_customer]"]').val(settings.email_subject_status_rejected_customer || '');
					$('textarea[name="returndesk_settings[email_body_status_rejected_customer]"]').val(settings.email_body_status_rejected_customer || '');
					$('input[name="returndesk_settings[email_heading_status_rejected_customer]"]').val(settings.email_heading_status_rejected_customer || '');
					$('input[name="returndesk_settings[email_items_title_status_rejected_customer]"]').val(settings.email_items_title_status_rejected_customer || '');
					$('textarea[name="returndesk_settings[email_additional_content_status_rejected_customer]"]').val(settings.email_additional_content_status_rejected_customer || '');
					$('input[name="returndesk_settings[email_subject_status_cancelled_customer]"]').val(settings.email_subject_status_cancelled_customer || '');
					$('textarea[name="returndesk_settings[email_body_status_cancelled_customer]"]').val(settings.email_body_status_cancelled_customer || '');
					$('input[name="returndesk_settings[email_heading_status_cancelled_customer]"]').val(settings.email_heading_status_cancelled_customer || '');
					$('input[name="returndesk_settings[email_items_title_status_cancelled_customer]"]').val(settings.email_items_title_status_cancelled_customer || '');
					$('textarea[name="returndesk_settings[email_additional_content_status_cancelled_customer]"]').val(settings.email_additional_content_status_cancelled_customer || '');
					var $statusSelect = $('select[name="returndesk_settings[allowed_statuses][]"]');
					if ($statusSelect.length) {
						$statusSelect.val(settings.allowed_statuses || []).trigger('change');
					} else {
						$('input[name="returndesk_settings[allowed_statuses][]"]').prop('checked', false);
						(settings.allowed_statuses || []).forEach(function (status) {
							$('input[name="returndesk_settings[allowed_statuses][]"][value="' + status + '"]').prop('checked', true);
						});
					}
				}
				showToast(returndesk_admin.i18n.reset_done, 'reset');
				return;
			}
			showToast(returndesk_admin.i18n.save_error, 'error');
		});
	});

	$(document).on('click', '#gg-test-email-btn', function () {
		var $btn = $(this);
		var targetEmail = $('#gg-test-email-target').val();
		var template = $('#gg-test-email-template').val() || 'status_update';

		$btn.prop('disabled', true);
		$.post(returndesk_admin.ajax_url, {
			action: 'returndesk_send_test_email',
			nonce: returndesk_admin.test_email_nonce,
			target_email: targetEmail,
			template: template,
			settings: collectSettings()
		}).done(function (response) {
			if (response && response.success) {
				showToast((response.data && response.data.message) ? response.data.message : returndesk_admin.i18n.saved, 'success');
			} else {
				showToast((response && response.data && response.data.message) || returndesk_admin.i18n.test_email_error, 'error');
			}
		}).fail(function () {
			showToast(returndesk_admin.i18n.test_email_error, 'error');
		}).always(function () {
			$btn.prop('disabled', false);
		});
	});

	function setStatusBadge($row, status, label) {
		var $badge = $row.find('[data-request-status]');
		if (!$badge.length) {
			return;
		}
		$badge
			.removeClass('gg-status-pending gg-status-approved gg-status-rejected gg-status-cancelled')
			.addClass('gg-status-' + status)
			.text(label || status);
	}

	function getRequestActionsHtml(status, requestId) {
		var i18n = (window.returndesk_admin && returndesk_admin.i18n) ? returndesk_admin.i18n : {};
		var approve = (i18n.approve || 'Approve').toString();
		var reject = (i18n.reject || 'Reject').toString();
		var remove = (i18n.delete || 'Delete').toString();
		var idAttr = String(parseInt(requestId, 10) || 0);
		var html = '';

		if (status === 'pending') {
			html += '<button type="button" class="gg-btn-mini gg-btn-mini-icon gg-btn-mini-approve" data-request-action="approved" data-request-id="' + idAttr + '" title="' + approve + '" aria-label="' + approve + '">';
			html += '<span class="dashicons dashicons-yes-alt gg-action-icon" aria-hidden="true"></span>';
			html += '<span class="screen-reader-text">' + approve + '</span>';
			html += '</button>';
			html += '<button type="button" class="gg-btn-mini gg-btn-mini-icon gg-btn-mini-reject" data-request-action="rejected" data-request-id="' + idAttr + '" title="' + reject + '" aria-label="' + reject + '">';
			html += '<span class="dashicons dashicons-no-alt gg-action-icon" aria-hidden="true"></span>';
			html += '<span class="screen-reader-text">' + reject + '</span>';
			html += '</button>';
			return html;
		}

		if (status === 'rejected' || status === 'cancelled') {
			html += '<button type="button" class="gg-btn-mini gg-btn-mini-icon gg-btn-mini-danger" data-request-delete="1" data-request-id="' + idAttr + '" title="' + remove + '" aria-label="' + remove + '">';
			html += '<span class="dashicons dashicons-trash gg-action-icon" aria-hidden="true"></span>';
			html += '<span class="screen-reader-text">' + remove + '</span>';
			html += '</button>';
		}
		return html;
	}

	function setRequestActions($row, status, requestId) {
		var $actions = $row.find('.gg-request-actions').first();
		if (!$actions.length) {
			return;
		}
		$actions.html(getRequestActionsHtml(status, requestId));
	}

	function setActionLoading($btn, isLoading) {
		if (!$btn || !$btn.length) {
			return;
		}
		$btn.toggleClass('is-loading', !!isLoading);
		$btn.prop('disabled', !!isLoading);
	}

	function ensureAttachmentModal() {
		var $existing = $('#gg-attachment-modal');
		if ($existing.length) {
			return $existing;
		}
		var i18n = (window.returndesk_admin && returndesk_admin.i18n) ? returndesk_admin.i18n : {};
		var closeText = (i18n.close || 'Close').toString();
		var backText = (i18n.back || 'Back').toString();
		var nextText = (i18n.next || 'Next').toString();
		var titleText = (i18n.attachment_preview || 'Attachment Preview').toString();
		var html = '';
		html += '<div id="gg-attachment-modal" class="gg-attachment-modal" aria-hidden="true">';
		html += '<div class="gg-attachment-modal-backdrop" data-attachment-close="1"></div>';
		html += '<div class="gg-attachment-modal-dialog" role="dialog" aria-modal="true" aria-label="' + titleText + '">';
		html += '<div class="gg-attachment-modal-head"><strong>' + titleText + '</strong><button type="button" class="gg-btn-mini" data-attachment-close="1">' + closeText + '</button></div>';
		html += '<div class="gg-attachment-modal-body"></div>';
		html += '<div class="gg-attachment-modal-foot"><span class="gg-attachment-step" data-attachment-step></span><div class="gg-attachment-nav"><button type="button" class="gg-btn-mini" data-attachment-back="1">' + backText + '</button><button type="button" class="gg-btn-mini" data-attachment-next="1">' + nextText + '</button></div></div>';
		html += '</div>';
		html += '</div>';
		$('body').append(html);
		return $('#gg-attachment-modal');
	}

	function renderCurrentAttachment() {
		var $modal = ensureAttachmentModal();
		var $body = $modal.find('.gg-attachment-modal-body').first();
		var $step = $modal.find('[data-attachment-step]').first();
		var $back = $modal.find('[data-attachment-back]').first();
		var $next = $modal.find('[data-attachment-next]').first();
		$body.empty();

		var items = Array.isArray(attachmentModalState.items) ? attachmentModalState.items : [];
		if (!items.length) {
			$step.text('');
			$back.hide();
			$next.hide();
			return;
		}

		var index = Math.max(0, Math.min(items.length - 1, attachmentModalState.index || 0));
		var item = items[index] || {};
		var url = (item.url || '').toString();
		var name = (item.name || '').toString();
		var isImage = !!item.is_image;
		var safeName = name || url.split('/').pop() || 'Attachment';
		var $row = $('<div class="gg-attachment-item"></div>');

		if (isImage) {
			$row.append($('<img class="gg-attachment-preview-img" alt="">').attr('src', url).attr('alt', safeName));
		} else {
			$row.append($('<a target="_blank" rel="noopener noreferrer"></a>').attr('href', url).text(safeName));
		}
		$body.append($row);
		$step.text((index + 1) + ' / ' + items.length);
		$back.toggle(index > 0);
		$next.toggle(index < (items.length - 1));
	}

	function openAttachmentModal(items) {
		attachmentModalState.items = (items || []).filter(function (item) {
			return item && item.url;
		});
		attachmentModalState.index = 0;
		renderCurrentAttachment();

		var $modal = ensureAttachmentModal();
		$modal.attr('aria-hidden', 'false').addClass('is-open');
	}

	function closeAttachmentModal() {
		var $modal = $('#gg-attachment-modal');
		if (!$modal.length) {
			return;
		}
		$modal.removeClass('is-open').attr('aria-hidden', 'true');
		attachmentModalState.items = [];
		attachmentModalState.index = 0;
	}

	function ensureRequestPaginationControls() {
		var $panel = $('[data-panel="requests"]').first();
		if (!$panel.length) {
			return $();
		}
		var $wrap = $panel.find('.gg-requests-pagination').first();
		if ($wrap.length) {
			return $wrap;
		}

		var html = '';
		html += '<div class="gg-requests-pagination" aria-label="Requests pagination">';
		html += '<button type="button" class="button button-secondary gg-requests-page-prev" data-requests-page="prev">Prev</button>';
		html += '<span class="gg-requests-page-info" data-requests-page-info>Page 1 of 1</span>';
		html += '<button type="button" class="button button-secondary gg-requests-page-next" data-requests-page="next">Next</button>';
		html += '</div>';

		var $table = $panel.find('table.gg-requests-table').first();
		if ($table.length) {
			$table.after(html);
		} else {
			$panel.append(html);
		}
		return $panel.find('.gg-requests-pagination').first();
	}

	function updateRequestPaginationUi(totalRows, totalPages) {
		var $pager = ensureRequestPaginationControls();
		if (!$pager.length) {
			return;
		}

		var hasRows = totalRows > 0;
		var page = requestPagerState.page;
		$pager.toggle(hasRows);
		$pager.find('[data-requests-page-info]').text('Page ' + page + ' of ' + totalPages);
		$pager.find('[data-requests-page="prev"]').prop('disabled', !hasRows || page <= 1);
		$pager.find('[data-requests-page="next"]').prop('disabled', !hasRows || page >= totalPages);
	}

	function applyRequestFilters(resetPage) {
		var $tbody = $('[data-panel="requests"] table tbody').first();
		var $rows = $tbody.find('tr[data-request-row]');
		var $detailRows = $tbody.find('tr[data-request-details-row]');
		if (!$rows.length) {
			updateRequestPaginationUi(0, 1);
			return;
		}
		if (resetPage === true) {
			requestPagerState.page = 1;
		}

		var query = ($('#gg-requests-search').val() || '').toString().toLowerCase().trim();
		var statusFilter = ($('#gg-requests-status-filter').val() || 'all').toString();
		var matchedRows = [];
		$detailRows.hide().removeClass('is-open');

		$rows.each(function () {
			var $row = $(this);
			var status = ($row.data('request-status-val') || '').toString();
			var text = (
				($row.data('request-title') || '') + ' ' +
				($row.data('request-order') || '') + ' ' +
				($row.data('request-reason') || '') + ' ' +
				$row.text()
			).toLowerCase();

			var matchStatus = (statusFilter === 'all' || status === statusFilter);
			var matchQuery = (!query || text.indexOf(query) !== -1);
			if (matchStatus && matchQuery) {
				matchedRows.push($row);
			}
		});

		$rows.hide();
		$tbody.find('#gg-requests-no-match').remove();
		if (matchedRows.length === 0) {
			updateRequestPaginationUi(0, 1);
			$tbody.append('<tr id="gg-requests-no-match"><td colspan="9">No matching requests found.</td></tr>');
			return;
		}

		var perPage = Math.max(1, parseInt(requestPagerState.perPage, 10) || 10);
		var totalPages = Math.max(1, Math.ceil(matchedRows.length / perPage));
		if (requestPagerState.page > totalPages) {
			requestPagerState.page = totalPages;
		}
		if (requestPagerState.page < 1) {
			requestPagerState.page = 1;
		}

		var start = (requestPagerState.page - 1) * perPage;
		var end = start + perPage;
		matchedRows.slice(start, end).forEach(function ($row) {
			$row.show();
		});

		updateRequestPaginationUi(matchedRows.length, totalPages);
	}

	function ensureRequestDetailsModal() {
		var $existing = $('#gg-request-details-modal');
		if ($existing.length) {
			return $existing;
		}
		var html = '';
		html += '<div id="gg-request-details-modal" class="gg-request-details-modal" aria-hidden="true">';
		html += '<div class="gg-request-details-modal-backdrop" data-request-details-close="1"></div>';
		html += '<div class="gg-request-details-modal-dialog" role="dialog" aria-modal="true" aria-label="Request Details">';
		html += '<div class="gg-request-details-modal-head"><strong>Request Details</strong><button type="button" class="gg-btn-mini" data-request-details-close="1">Close</button></div>';
		html += '<div class="gg-request-details-modal-body"></div>';
		html += '</div></div>';
		$('body').append(html);
		return $('#gg-request-details-modal');
	}

	function openRequestDetailsModal($row) {
		var requestId = ($row.data('request-row') || '').toString();
		if (!requestId) {
			return;
		}
		var $tbody = $row.closest('tbody');
		var $detailsRow = $tbody.find('tr[data-request-details-row="' + requestId + '"]').first();
		if (!$detailsRow.length) {
			return;
		}
		var $card = $detailsRow.find('.gg-request-details-card').first();
		if (!$card.length) {
			return;
		}
		var $modal = ensureRequestDetailsModal();
		$modal.find('.gg-request-details-modal-body').html($card.prop('outerHTML') || '');
		$modal.attr('aria-hidden', 'false').addClass('is-open');
	}

	function closeRequestDetailsModal() {
		var $modal = $('#gg-request-details-modal');
		if (!$modal.length) {
			return;
		}
		$modal.removeClass('is-open').attr('aria-hidden', 'true');
		$modal.find('.gg-request-details-modal-body').empty();
	}

	$(document).on('click', '[data-request-action]', function () {
		var $btn = $(this);
		var requestId = parseInt($btn.data('request-id'), 10);
		var nextStatus = ($btn.data('request-action') || '').toString();
		var $row = $btn.closest('[data-request-row]');
		var label = $btn.text().trim();

		if (!requestId || !nextStatus) {
			return;
		}

		setActionLoading($btn, true);
		$.post(returndesk_admin.ajax_url, {
			action: 'returndesk_update_request_status',
			nonce: returndesk_admin.status_nonce,
			request_id: requestId,
			status: nextStatus
		}).done(function (response) {
			if (response && response.success) {
				setStatusBadge($row, nextStatus, (response.data && response.data.status_label) ? response.data.status_label : label);
				$row.attr('data-request-status-val', nextStatus);
				setRequestActions($row, nextStatus, requestId);
				applyRequestFilters(false);
				showToast((response.data && response.data.message) ? response.data.message : returndesk_admin.i18n.saved, 'success');
			} else {
				showToast((response && response.data && response.data.message) || returndesk_admin.i18n.request_update_error, 'error');
			}
		}).fail(function () {
			showToast(returndesk_admin.i18n.request_update_error, 'error');
			}).always(function () {
				setActionLoading($btn, false);
			});
	});

	$(document).on('click', '[data-request-delete]', function () {
		var $btn = $(this);
		var requestId = parseInt($btn.data('request-id'), 10);
		var $row = $btn.closest('[data-request-row]');

		if (!requestId) {
			return;
		}
			if (!window.confirm(returndesk_admin.i18n.request_delete_confirm)) {
				return;
			}

			setActionLoading($btn, true);
			$.post(returndesk_admin.ajax_url, {
			action: 'returndesk_delete_request',
			nonce: returndesk_admin.delete_nonce,
			request_id: requestId
		}).done(function (response) {
			if (response && response.success) {
				var rowId = ($row.data('request-row') || '').toString();
				if (rowId) {
					$row.closest('tbody').find('tr[data-request-details-row="' + rowId + '"]').remove();
				}
				$row.remove();
				applyRequestFilters(false);
				showToast((response.data && response.data.message) ? response.data.message : returndesk_admin.i18n.saved, 'success');
			} else {
				showToast((response && response.data && response.data.message) || returndesk_admin.i18n.request_delete_error, 'error');
			}
		}).fail(function () {
			showToast(returndesk_admin.i18n.request_delete_error, 'error');
			}).always(function () {
				setActionLoading($btn, false);
			});
		});

	$(document).on('keyup', '#gg-requests-search', function () { applyRequestFilters(true); });
	$(document).on('change', '#gg-requests-status-filter', function () { applyRequestFilters(true); });
	$(document).on('click', '[data-request-status]', function () {
		openRequestDetailsModal($(this).closest('tr[data-request-row]'));
	});
	$(document).on('click', '[data-request-details-close]', closeRequestDetailsModal);
	$(document).on('click', '[data-requests-page]', function () {
		var direction = ($(this).data('requests-page') || '').toString();
		if (direction === 'prev') {
			requestPagerState.page = Math.max(1, requestPagerState.page - 1);
		} else if (direction === 'next') {
			requestPagerState.page = requestPagerState.page + 1;
		}
		applyRequestFilters(false);
	});
	$(document).on('click', '.gg-attachment-view', function () {
		var raw = ($(this).attr('data-attachments') || '').toString();
		var items = [];
		if (raw) {
			try {
				items = JSON.parse(raw);
			} catch (e) {
				items = [];
			}
		}
		openAttachmentModal(items);
	});
	$(document).on('click', '[data-attachment-close]', closeAttachmentModal);
	$(document).on('click', '[data-attachment-next]', function () {
		var items = Array.isArray(attachmentModalState.items) ? attachmentModalState.items : [];
		if (!items.length) {
			return;
		}
		if (attachmentModalState.index < (items.length - 1)) {
			attachmentModalState.index += 1;
			renderCurrentAttachment();
		}
	});
	$(document).on('click', '[data-attachment-back]', function () {
		var items = Array.isArray(attachmentModalState.items) ? attachmentModalState.items : [];
		if (!items.length) {
			return;
		}
		if (attachmentModalState.index > 0) {
			attachmentModalState.index -= 1;
			renderCurrentAttachment();
		}
	});
	$(document).on('keydown', function (e) {
		if (e.key === 'Escape') {
			closeAttachmentModal();
			closeRequestDetailsModal();
		}
	});

	applyRequestFilters(true);

	// ── Review notice dismiss ─────────────────────────────────────────────
	if ( typeof returndesk_notices !== 'undefined' && returndesk_notices.nonce ) {
		$( document ).on( 'click', '[data-gg-review-action]', function () {
			var action  = $( this ).data( 'gg-review-action' );
			var $notice = $( '#gg-review-notice' );
			$.post( returndesk_notices.ajaxUrl, {
				action:         'returndesk_dismiss_review',
				nonce:          returndesk_notices.nonce,
				dismiss_action: action,
			} ).always( function () {
				$notice.slideUp( 200 );
			} );
		} );

		// WP's native X button — treat as "Maybe Later" (14-day hide).
		$( '#gg-review-notice' ).on( 'click', '.notice-dismiss', function () {
			$.post( returndesk_notices.ajaxUrl, {
				action:         'returndesk_dismiss_review',
				nonce:          returndesk_notices.nonce,
				dismiss_action: 'later',
			} );
		} );
	}

	// ── Help dropdown ─────────────────────────────────────────────────────────
	var $helpBtn      = $( '#gg-help-btn' );
	var $helpDropdown = $( '#gg-help-dropdown' );

	$helpBtn.on( 'click', function ( e ) {
		e.stopPropagation();
		var opening = $helpDropdown.attr( 'hidden' ) !== undefined;
		if ( opening ) {
			$helpDropdown.removeAttr( 'hidden' );
			$helpBtn.addClass( 'is-open' ).attr( 'aria-expanded', 'true' );
		} else {
			$helpDropdown.attr( 'hidden', '' );
			$helpBtn.removeClass( 'is-open' ).attr( 'aria-expanded', 'false' );
		}
	} );

	$( document ).on( 'click', function () {
		$helpDropdown.attr( 'hidden', '' );
		$helpBtn.removeClass( 'is-open' ).attr( 'aria-expanded', 'false' );
	} );

	$helpDropdown.on( 'click', function ( e ) {
		e.stopPropagation();
	} );
})(jQuery);
