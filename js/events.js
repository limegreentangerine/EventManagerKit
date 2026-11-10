'use strict';
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
	const KEYS = ['month', 'type', 'location', 'tag', 'page'];
	function load(section, search, push) {
		var _a;
		const content = section.querySelector('.em-events__content');
		const params = new URLSearchParams();
		const view = section.getAttribute('data-em-view');
		if (!content) {
			return;
		}
		params.set('view', view !== null && view !== void 0 ? view : '');
		params.set(
			'locale',
			(_a = section.getAttribute('data-em-locale')) !== null && _a !== void 0 ? _a : ''
		);
		if (view === 'list') {
			params.set('limit', section.getAttribute('data-em-limit') || '');
		} else {
			params.set('filters', section.getAttribute('data-em-filters') === '1' ? '1' : '0');
			KEYS.forEach((key) => {
				const value = search.get(key);
				if (value) {
					params.set(key, value);
				}
			});
		}
		section.setAttribute('aria-busy', 'true');
		fetch(section.getAttribute('data-em-endpoint') + '?' + params.toString(), {
			headers: { Accept: 'application/json' },
			credentials: 'same-origin'
		})
			.then((response) => {
				if (!response.ok) {
					throw new Error('HTTP ' + response.status);
				}
				return response.json();
			})
			.then((data) => {
				// Server-rendered, escaped markup from our own endpoint.
				content.innerHTML = data.html;
				if (push && view === 'calendar') {
					const shown = new URLSearchParams();
					KEYS.forEach((key) => {
						const value = params.get(key);
						if (value) {
							shown.set(key, value);
						}
					});
					const query = shown.toString();
					history.pushState(
						null,
						'',
						window.location.pathname + (query ? '?' + query : '')
					);
				}
			})
			.catch(() => {
				content.textContent = section.getAttribute('data-em-error');
			})
			.then(() => {
				section.removeAttribute('aria-busy');
			});
	}
	function init(section) {
		const calendar = section.getAttribute('data-em-view') === 'calendar';
		load(section, new URLSearchParams(window.location.search), false);
		if (!calendar) {
			return;
		}
		// Month and paging links look like "?month=2026-12".
		section.addEventListener('click', (event) => {
			var _a;
			const link = event.target.closest('a[href^="?"]');
			if (link && !event.metaKey && !event.ctrlKey && !event.shiftKey) {
				event.preventDefault();
				load(
					section,
					new URLSearchParams(
						(_a = link.getAttribute('href')) !== null && _a !== void 0 ? _a : ''
					),
					true
				);
			}
		});
		section.addEventListener('change', (event) => {
			const form = event.target.closest('.em-events__filters');
			if (form) {
				load(section, new URLSearchParams(new FormData(form)), true);
			}
		});
		section.addEventListener('submit', (event) => {
			event.preventDefault();
			load(section, new URLSearchParams(new FormData(event.target)), true);
		});
		window.addEventListener('popstate', () => {
			load(section, new URLSearchParams(window.location.search), false);
		});
	}
	document.querySelectorAll('[data-em-endpoint]').forEach(init);
})();
