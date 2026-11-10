/**
 * Loads Event Manager blocks from the events endpoint.
 *
 * The page holding a block may be cached, so it ships an empty shell
 * (<section data-em-endpoint ...>) and the events are fetched on load.
 * Calendar links and filters are fetched too, and the address bar is kept in
 * sync so a month or filter stays a shareable URL.
 */
(function () {
	'use strict';

	// Query-string keys the calendar understands; anything else is ignored.
	var KEYS = ['month', 'type', 'location', 'tag', 'page'];

	function load(section, search, push) {
		var content = section.querySelector('.em-events__content');
		var params = new URLSearchParams();
		var view = section.getAttribute('data-em-view');

		params.set('view', view);
		params.set('locale', section.getAttribute('data-em-locale'));
		if (view === 'list') {
			params.set('limit', section.getAttribute('data-em-limit') || '');
		} else {
			params.set('filters', section.getAttribute('data-em-filters') === '1' ? '1' : '0');
			KEYS.forEach(function (key) {
				if (search.get(key)) {
					params.set(key, search.get(key));
				}
			});
		}

		section.setAttribute('aria-busy', 'true');

		fetch(section.getAttribute('data-em-endpoint') + '?' + params.toString(), {
			headers: { Accept: 'application/json' },
			credentials: 'same-origin'
		})
			.then(function (response) {
				if (!response.ok) {
					throw new Error('HTTP ' + response.status);
				}
				return response.json();
			})
			.then(function (data) {
				// Server-rendered, escaped markup from our own endpoint.
				content.innerHTML = data.html;

				if (push && view === 'calendar') {
					var shown = new URLSearchParams();
					KEYS.forEach(function (key) {
						if (params.get(key)) {
							shown.set(key, params.get(key));
						}
					});
					var query = shown.toString();
					history.pushState(
						null,
						'',
						window.location.pathname + (query ? '?' + query : '')
					);
				}
			})
			.catch(function () {
				content.textContent = section.getAttribute('data-em-error');
			})
			.then(function () {
				section.removeAttribute('aria-busy');
			});
	}

	function init(section) {
		var calendar = section.getAttribute('data-em-view') === 'calendar';

		load(section, new URLSearchParams(window.location.search), false);

		if (!calendar) {
			return;
		}

		// Month and paging links look like "?month=2026-12".
		section.addEventListener('click', function (event) {
			var link = event.target.closest('a[href^="?"]');
			if (link && !event.metaKey && !event.ctrlKey && !event.shiftKey) {
				event.preventDefault();
				load(section, new URLSearchParams(link.getAttribute('href')), true);
			}
		});

		section.addEventListener('change', function (event) {
			var form = event.target.closest('.em-events__filters');
			if (form) {
				load(section, new URLSearchParams(new FormData(form)), true);
			}
		});

		section.addEventListener('submit', function (event) {
			event.preventDefault();
			load(section, new URLSearchParams(new FormData(event.target)), true);
		});

		window.addEventListener('popstate', function () {
			load(section, new URLSearchParams(window.location.search), false);
		});
	}

	document.querySelectorAll('[data-em-endpoint]').forEach(init);
})();
