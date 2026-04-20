(function () {
	'use strict';

	var openClass = 'is-open';

	var forceNativeSelects = function (root) {
		var scope = root || document;
		if (!scope || !scope.querySelectorAll) return;
		var selects = scope.querySelectorAll('select.returndesk-select, .returndesk-item-qty select');
		selects.forEach(function (select) {
			try {
				select.style.setProperty('display', 'block', 'important');
				select.style.setProperty('appearance', 'auto', 'important');
				select.style.setProperty('-webkit-appearance', 'menulist', 'important');
				select.style.setProperty('-moz-appearance', 'menulist', 'important');
				select.style.setProperty('pointer-events', 'auto', 'important');
				select.style.setProperty('cursor', 'pointer', 'important');
				select.style.setProperty('background', '#fff', 'important');
				select.style.setProperty('border', '1.5px solid #cbd5e1', 'important');
				select.style.setProperty('border-radius', '10px', 'important');
				select.style.setProperty('padding', '6px 10px', 'important');
				select.style.setProperty('min-height', '38px', 'important');
			} catch (e) {}
		});
	};

	var updateReasonOther = function (select) {
		if (!select) return;
		var form = select.closest('form');
		if (!form) return;
		var wrap = form.querySelector('.returndesk-reason-other');
		var input = form.querySelector('input[name=\"reason_other\"]');
		var isOther = select.value === '__other__';
		if (wrap) wrap.style.display = isOther ? 'block' : 'none';
		if (input) {
			input.required = !!isOther;
			if (!isOther) input.value = '';
		}
	};

	var updateFileLabel = function (input) {
		if (!input) return;
		var label = input.closest('.returndesk-file');
		if (!label) return;
		var textEl = label.querySelector('.returndesk-file__text');
		if (!textEl) return;
		var defaultText = textEl.getAttribute('data-returndesk-default-text') || textEl.textContent;
		var files = input.files ? Array.prototype.slice.call(input.files) : [];
		if (!files.length) {
			textEl.textContent = defaultText;
		} else if (files.length === 1) {
			textEl.textContent = files[0].name || defaultText;
		} else {
			textEl.textContent = files.length + ' files selected';
		}

		var field = input.closest('.returndesk-field--file') || label.parentElement;
		var preview = field ? field.querySelector('.returndesk-file-preview') : null;
		if (!preview) return;
		preview.innerHTML = '';

		files.forEach(function (file) {
			if (!file) return;
			var isImage = file.type && file.type.indexOf('image/') === 0;
			if (isImage) {
				var img = document.createElement('img');
				img.className = 'returndesk-file-thumb';
				var url = URL.createObjectURL(file);
				img.src = url;
				img.alt = file.name || 'Selected image';
				img.onload = function () { try { URL.revokeObjectURL(url); } catch (e) {} };
				preview.appendChild(img);
				return;
			}

			var chip = document.createElement('div');
			chip.className = 'returndesk-file-chip';
			chip.textContent = file.name || 'File';
			preview.appendChild(chip);
		});
	};

	var getOpenModal = function () {
		return document.querySelector('.returndesk-modal.' + openClass);
	};

	var setBodyLocked = function (locked) {
		if (!document.body) return;
		document.body.style.overflow = locked ? 'hidden' : '';
	};

	var openModal = function (modal) {
		if (!modal) return;
		modal.classList.add(openClass);
		modal.setAttribute('aria-hidden', 'false');
		setBodyLocked(true);
		forceNativeSelects(modal);

		var firstFocusable = modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex=\"-1\"])');
		if (firstFocusable) firstFocusable.focus();
	};

	var closeModal = function (modal) {
		if (!modal) return;
		modal.classList.remove(openClass);
		modal.setAttribute('aria-hidden', 'true');
		setBodyLocked(false);
	};

	var handleOpen = function (btn) {
		var modalId = btn.getAttribute('data-returndesk-modal');
		var modal = modalId ? document.getElementById(modalId) : null;
		if (!modal) return;

		openModal(modal);
	};

	document.addEventListener('click', function (e) {
		var target = e.target;
		if (!target) return;

		var openBtn = target.closest('[data-returndesk-open-modal]');
		if (openBtn) {
			e.preventDefault();
			handleOpen(openBtn);
			return;
		}

		var closeBtn = target.closest('[data-returndesk-close-modal]');
		if (closeBtn) {
			e.preventDefault();
			closeModal(closeBtn.closest('.returndesk-modal'));
			return;
		}

		if (target.classList && target.classList.contains('returndesk-modal__backdrop')) {
			closeModal(target.closest('.returndesk-modal'));
		}
	});

	document.addEventListener('keydown', function (e) {
		if (e.key !== 'Escape') return;
		var modal = getOpenModal();
		if (modal) closeModal(modal);
	});

	document.addEventListener('change', function (e) {
		var target = e.target;
		if (!target) return;
		if (target.matches && target.matches('select[name=\"reason\"]')) {
			updateReasonOther(target);
		}
		if (target.matches && target.matches('.returndesk-file input[type=\"file\"]')) {
			updateFileLabel(target);
		}
	});
	document.querySelectorAll('select[name=\"reason\"]').forEach(updateReasonOther);
	document.querySelectorAll('.returndesk-file input[type=\"file\"]').forEach(updateFileLabel);
	forceNativeSelects(document);
})();
