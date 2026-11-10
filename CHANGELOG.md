# Changelog

Notable changes to this project are documented here.

## 1.0.0 (unreleased)

### Added

- Block accessibility (WCAG 2.2 AA): focus is kept on filters and moved to the month heading after month/page navigation, updates and errors are announced through a status region instead of re-reading the whole block, decorative arrows are hidden from screen readers, dates use `<time>`, and titled blocks are labelled regions.
- Repeating events (schema version 1.0.7; upgrade the package, then clear the
  cache). An event can repeat daily, weekly (same weekday) or monthly (same day
  of the month, months without that day are skipped), every N, until an
  optional date, with skipped dates. One row per series; the calendar and list
  expand it into dated occurrences (a series is walked for at most a year ahead
  in the list and feed), the event page shows the next date and "Repeats
  weekly until...", and the ICS exports an `RRULE` and `EXDATE` instead of
  separate events.
- Locations have an address, latitude/longitude and an optional online event
  URL (schema version 1.0.6; upgrade the package to add the columns). Leaving
  the coordinates blank looks them up from the address when the optional
  `mapbox` package (MapboxKit) is installed and has an API key. The event page
  shows the address, a Mapbox map (MapboxKit's `<mapbox-component>`) or, without
  Mapbox, a "View on map" link, a "Join online" link, and schema.org `Place`
  with `GeoCoordinates` / `VirtualLocation` and `eventAttendanceMode`.
- Calendar subscriptions: `GET /ajax/events-manager/ics/{slug}` (one event) and
  `GET /ajax/events-manager/feed.ics` (upcoming published events) return
  iCalendar files, and the event page has Add to calendar links (iCal,
  Google, Outlook). Timed events are exported in UTC.
- Site timezone: stored event times mean local time in the Concrete site's
  timezone. The ICS, web calendar links and schema.org `startDate`/`endDate`
  (with UTC offset) use it, so daylight saving is handled.
- SEO on the event page: canonical URL, `hreflang` alternates for each
  language with a slug, and Open Graph/Twitter tags. Published events are added
  to Concrete's `sitemap.xml` (via `on_sitemap_xml_ready`), once per language
  with hreflang alternates and a `lastmod` from the event's updated date. Every
  dashboard save now refreshes that date, including text-only edits.
- The calendar and list blocks load their events by `fetch` from
  `GET /ajax/events-manager/events` (HTML fragment in JSON, `Cache-Control:
no-store`), so pages holding them can be cached without showing stale
  events. Exclude `/ajax/events-manager/*` from CDN caching. The calendar's
  month links, paging and filters fetch too and keep the address bar in sync.
  Without JavaScript the blocks show a "please enable JavaScript" message.
- Public event page at `/events/{slug}` with per-language URL slugs
  (generated from the name, editable on the event form, unique per language,
  kept stable when the name changes). The calendar and list link to it. A slug
  from another language redirects to the visitor's own. Drafts return "not
  found", except for administrators, who see a preview. Events saved before
  slugs existed have no slug and no link until they are re-saved.
- Event Calendar and Event List blocks (published events only; the calendar
  has month links, type/location/tag filters and paging via sanitised GET
  parameters), and a Published checkbox/Status column for events.
- Typed Doctrine entities: `Event`, `EventContent`, `EventType`, `EventTag`,
  `Location` and their per-locale name rows.
- `Translations::pick()`: exact locale, then same language, then the default
  locale, then any row, so a missing translation is never blank.
- `TimeOfDay` enum, with unit tests for all of the above.
- Dashboard under `/dashboard/event_manager`: Events, Event Types, Event Tags
  and Locations, each with Concrete's search list (sortable, paginated, name
  filter; events also filter by type and location), add/edit with one field per
  site locale, and delete.
- CSV import: header-driven, one column per locale, whole-file validation with
  a per-line error report, "check only" mode, all-or-nothing transaction, and
  re-uploads skip events already imported.

### Fixed

- CSV import reported unknown locales only for `name_` columns; it now checks
  every locale column (location, type, tags) and reports each locale once.
- The import page's example header lists every site locale, not just two.

### Changed

- `limegreentangerine/mapbox_kit` is now a Composer requirement (the `mapbox`
  Concrete package is still only needed for maps and geocoding).
- The CSV import upload limit now follows the server's PHP upload limit instead
  of a fixed 5 MB, and the import page shows it.
- Event page images render through Concrete's `picture` element (responsive
  and themeable).
- Event List block handle is `em_event_list`: Concrete core already owns
  `event_list`, which silently shadowed ours.
- Renamed the template placeholders to `event_manager_kit` / `EventManagerKit`.

## 0.0.0

### Added

- Separate GitHub Actions workflows for code standards and system tests.

### Changed

- Code standards checks run on pushes and pull requests; system tests run on
  pull requests.
- Updated Composer dependencies and development tooling for the package
  template.

## 2026-09-25

### Added

- PHPStan static analysis configuration and a Composer command for type checks.
- Cloudflare Access authentication for installing dependencies from the private
  Composer registry.

### Changed

- Updated the template to use the published `limegreentangerine/class_kit`
  Composer package.
- Refined the GitHub Actions workflows and development dependencies.

## 2026-09-15

### Added

- Initial Concrete CMS Composer package template, including Composer
  autoloading, PHPUnit test setup, and a GitHub Actions test workflow.
