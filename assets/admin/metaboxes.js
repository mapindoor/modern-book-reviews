/**
 * Modern Book Reviews – Metabox-Verhalten (Vanilla JS, ohne Build-Schritt):
 *  - 5-Sterne-Bewertung (Klick = Wert 1–5, erneuter Klick = 0)
 *  - Zeilen zu Kriterien-/Kauflink-Tabellen hinzufügen/entfernen
 *  - Buchdaten per ISBN über den REST-Endpoint abrufen
 *  - Buchdeckel über die WordPress-Mediathek wählen/entfernen
 */
(function (window, document) {
	'use strict';

	var ADMIN = window.MBR_ADMIN || {};

	function ready(fn) {
		if (document.readyState !== 'loading') {
			fn();
		} else {
			document.addEventListener('DOMContentLoaded', fn);
		}
	}

	ready(function () {
		initStars();
		initRows();
		initFreeStores();
		initIsbn();
		initCover();
	});

	/* ---------------- 5-Sterne-Bewertung ---------------- */

	function initStars() {
		var wrap = document.getElementById('mbr-star-rating');
		var input = document.getElementById('rcno_admin_rating');

		if (!wrap || !input) {
			return;
		}

		var current = parseFloat(input.value) || 0;
		var stars = [];

		for (var i = 1; i <= 5; i++) {
			(function (value) {
				var star = document.createElement('span');
				star.className = 'mbr-star';
				star.setAttribute('data-value', value);
				star.setAttribute('title', String(value) + ' von 5');

				var bg = document.createElement('span');
				bg.className = 'mbr-star-bg';
				bg.textContent = '★';

				var fill = document.createElement('span');
				fill.className = 'mbr-star-fill';
				fill.textContent = '★';

				star.appendChild(bg);
				star.appendChild(fill);

				star.addEventListener('mouseenter', function () {
					paint(value);
				});

				star.addEventListener('mouseleave', function () {
					paint(current);
				});

				star.addEventListener('click', function () {
					current = (current === value) ? 0 : value;
					input.value = current ? String(current) : '';
					paint(current);
				});

				wrap.appendChild(star);
				stars.push({ el: star, value: value });
			})(i);
		}

		function paint(value) {
			stars.forEach(function (star) {
				var fill = star.el.querySelector('.mbr-star-fill');
				var percent = Math.max(0, Math.min(1, value - (star.value - 1)));
				fill.style.width = (percent * 100) + '%';
			});
		}

		paint(current);
	}

	/* ---------------- Tabellenzeilen hinzufügen/entfernen ---------------- */

	function initRows() {
		document.addEventListener('click', function (event) {
			var target = event.target;

			while (target && target !== document.body && !(target.matches && target.matches('a, button'))) {
				target = target.parentElement;
			}

			if (!target || !target.matches || !target.matches('a, button')) {
				return;
			}

			if (target.classList.contains('mbr-add-row')) {
				event.preventDefault();

				var selector = target.getAttribute('data-target');
				var table = null;

				if (selector) {
					table = document.querySelector(selector);
				} else {
					table = target.closest('table');
				}

				var template = table ? table.querySelector('tr.mbr-empty-row') : null;

				if (!template) {
					return;
				}

				var row = template.cloneNode(true);
				row.classList.remove('mbr-empty-row', 'hidden');
				template.parentNode.insertBefore(row, template);

				return;
			}

			if (target.classList.contains('mbr-remove-row')) {
				event.preventDefault();

				var tr = target.closest('tr');

				if (tr && !tr.classList.contains('mbr-empty-row')) {
					tr.remove();
				}
			}
		});
	}

	/* ---------------- Freie Shop-Auswahl („Anderer Shop …“) ---------------- */

	function initFreeStores() {
		document.addEventListener('change', function (event) {
			var select = event.target;

			if (!select.matches || !select.matches('select.mbr-buy-link-store')) {
				return;
			}

			if (select.value !== '__custom__') {
				return;
			}

			var input = document.createElement('input');
			input.type = 'text';
			input.className = 'widefat mbr-buy-link-store-free';
			input.name = 'store[]';
			input.placeholder = 'Shop (frei)';

			select.parentNode.replaceChild(input, select);
			input.focus();
		});
	}

	/* ---------------- ISBN-Abruf ---------------- */

	function setIsbnError(message) {
		var box = document.querySelector('.mbr-isbn-metabox-error');

		if (!box) {
			return;
		}

		if (message) {
			box.querySelector('p').textContent = message;
			box.classList.remove('hidden');
		} else {
			box.classList.add('hidden');
		}
	}

	function fillField(id, value) {
		var field = document.getElementById(id);

		if (field && value) {
			field.value = value;
		}
	}

	function initIsbn() {
		var button = document.getElementById('mbr-isbn-fetch');
		var isbnInput = document.getElementById('rcno_book_isbn');

		if (!button || !isbnInput || !window.fetch) {
			return;
		}

		button.addEventListener('click', function () {
			var isbn = isbnInput.value.trim();

			if (!isbn) {
				setIsbnError('Bitte zuerst eine ISBN eingeben.');
				return;
			}

			var original = button.textContent;
			button.disabled = true;
			button.textContent = 'Suche …';
			setIsbnError(null);

			window.fetch(
				ADMIN.restUrl + 'isbn/' + encodeURIComponent(isbn),
				{ headers: { 'X-WP-Nonce': ADMIN.nonce || '' } }
			).then(function (response) {
				return response.json().then(function (data) {
					return { ok: response.ok, data: data };
				});
			}).then(function (result) {
				if (!result.ok || !result.data || !result.data.title) {
					setIsbnError('Dein Buch wurde nicht gefunden.');
					return;
				}

				var book = result.data;

				fillField('rcno_book_title', book.title);
				fillField('rcno_book_isbn13', book.isbn13);

				fillField('rcno_book_pub_date', book.pub_date);
				fillField('rcno_book_page_count', book.page_count ? String(book.page_count) : '');

				var description = document.getElementById('rcno_book_description');
				if (description && book.description) {
					description.value = book.description;
				}

				if (book.cover_url) {
					var src = document.getElementById('rcno-reviews-book-cover-src');
					var preview = document.getElementById('mbr-cover-preview');

					if (src) {
						src.value = book.cover_url;
					}
					if (preview) {
						preview.setAttribute('src', book.cover_url);
					}
					var container = document.getElementById('mbr-book-cover-container');
					var remove = document.getElementById('mbr-remove-book-cover');
					if (container) {
						container.classList.remove('hidden');
					}
					if (remove) {
						remove.parentElement.classList.remove('hidden');
					}
				}
			}).catch(function () {
				setIsbnError('Abruf fehlgeschlagen. Bitte später erneut versuchen.');
			}).then(function () {
				button.disabled = false;
				button.textContent = original;
			});
		});
	}

	/* ---------------- Buchdeckel ---------------- */

	function initCover() {
		var addLink = document.getElementById('mbr-add-book-cover');
		var removeLink = document.getElementById('mbr-remove-book-cover');

		if (!addLink || !window.wp || !window.wp.media) {
			return;
		}

		addLink.addEventListener('click', function (event) {
			event.preventDefault();

			var frame = wp.media({
				title: 'Buchdeckel wählen',
				button: { text: 'Buchdeckel verwenden' },
				multiple: false,
				library: { type: 'image' }
			});

			frame.on('select', function () {
				var attachment = frame.state().get('selection').first().toJSON();
				var url = attachment.url;

				var src = document.getElementById('rcno-reviews-book-cover-src');
				var alt = document.getElementById('rcno-reviews-book-cover-alt');
				var title = document.getElementById('rcno-reviews-book-cover-title');
				var id = document.getElementById('rcno-reviews-book-cover-id');

				if (src) {
					src.value = url;
				}
				if (alt) {
					alt.value = attachment.alt || '';
				}
				if (title) {
					title.value = attachment.title || '';
				}
				if (id) {
					id.value = attachment.id || '';
				}

				var preview = document.getElementById('mbr-cover-preview');
				if (preview) {
					preview.setAttribute('src', url);
					preview.setAttribute('alt', attachment.alt || '');
				}

				var container = document.getElementById('mbr-book-cover-container');
				if (container) {
					container.classList.remove('hidden');
				}
				if (removeLink && removeLink.parentElement) {
					removeLink.parentElement.classList.remove('hidden');
				}
			});

			frame.open();
		});

		if (removeLink) {
			removeLink.addEventListener('click', function (event) {
				event.preventDefault();

				['rcno-reviews-book-cover-src', 'rcno-reviews-book-cover-alt',
					'rcno-reviews-book-cover-title', 'rcno-reviews-book-cover-id'
				].forEach(function (fieldId) {
					var field = document.getElementById(fieldId);
					if (field) {
						field.value = '';
					}
				});

				var container = document.getElementById('mbr-book-cover-container');
				if (container) {
					container.classList.add('hidden');
				}
				removeLink.parentElement.classList.add('hidden');
			});
		}
	}
})(window, document);
