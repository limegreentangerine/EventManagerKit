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

	function load(section: HTMLElement, search: URLSearchParams, push: boolean): void {
		const content = section.querySelector<HTMLElement>('.em-events__content');
		const params = new URLSearchParams();
		const view = section.getAttribute('data-em-view');

		if (!content) {
			return;
		}

		params.set('view', view ?? '');
		params.set('locale', section.getAttribute('data-em-locale') ?? '');
		if (view === 'list' || view === 'featured') {
			params.set('limit', section.getAttribute('data-em-limit') || '');
		} else if (view === 'event') {
			params.set('event', section.getAttribute('data-em-event') || '');
		} else {
			params.set('filters', section.getAttribute('data-em-filters') === '1' ? '1' : '0');
			KEYS.forEach((key) => {
				const value = search.get(key);
				if (value) {
					params.set(key, value);
				}
			});
		}

		// The response replaces the controls, so remember what had focus to restore it.
		const active = document.activeElement as HTMLElement | null;
		const inContent = !!active && content.contains(active);
		const field = inContent ? active.getAttribute('name') : null;
		const wasLink = inContent && active.tagName === 'A';
		const status = section.querySelector<HTMLElement>('.em-events__status');
		const loaded = section.hasAttribute('data-em-loaded');

		section.setAttribute('aria-busy', 'true');

		fetch(section.getAttribute('data-em-endpoint') + '?' + params.toString(), {
			headers: { Accept: 'application/json' },
			credentials: 'same-origin'
		})
			.then((response) => {
				if (!response.ok) {
					throw new Error('HTTP ' + response.status);
				}
				return response.json() as Promise<{ html: string }>;
			})
			.then((data) => {
				// Server-rendered, escaped markup from our own endpoint.
				content.innerHTML = data.html;
				section.setAttribute('data-em-loaded', '');

				// WCAG 2.4.3 / 4.1.3: keep focus in place and announce the update.
				if (field) {
					content.querySelector<HTMLElement>('[name="' + field + '"]')?.focus();
				} else if (wasLink) {
					content.querySelector<HTMLElement>('.em-events__month')?.focus();
				}
				if (status && loaded) {
					status.textContent = section.getAttribute('data-em-updated');
				}

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
				if (status) {
					status.textContent = section.getAttribute('data-em-error');
				}
			})
			.then(() => {
				section.removeAttribute('aria-busy');
			});
	}

	function init(section: HTMLElement): void {
		const calendar = section.getAttribute('data-em-view') === 'calendar';

		load(section, new URLSearchParams(window.location.search), false);

		if (!calendar) {
			return;
		}

		// Month and paging links look like "?month=2026-12".
		section.addEventListener('click', (event) => {
			const link = (event.target as Element).closest<HTMLAnchorElement>('a[href^="?"]');
			if (link && !event.metaKey && !event.ctrlKey && !event.shiftKey) {
				event.preventDefault();
				load(section, new URLSearchParams(link.getAttribute('href') ?? ''), true);
			}
		});

		section.addEventListener('change', (event) => {
			const form = (event.target as Element).closest<HTMLFormElement>('.em-events__filters');
			if (form) {
				load(
					section,
					new URLSearchParams(new FormData(form) as unknown as Record<string, string>),
					true
				);
			}
		});

		section.addEventListener('submit', (event) => {
			event.preventDefault();
			load(
				section,
				new URLSearchParams(
					new FormData(event.target as HTMLFormElement) as unknown as Record<
						string,
						string
					>
				),
				true
			);
		});

		window.addEventListener('popstate', () => {
			load(section, new URLSearchParams(window.location.search), false);
		});
	}

	document.querySelectorAll<HTMLElement>('[data-em-endpoint]').forEach(init);
})();
