# Changelog

Notable changes to this project are documented here.

## 1.0.0 (unreleased)

### Added

- Calendar subscriptions: `GET /ajax/events-manager/ics/{slug}` (one event) and
  `GET /ajax/events-manager/feed.ics` (upcoming published events) return
  iCalendar files, and the event page has Add to calendar links (iCal,
  Google, Outlook). Times are floating local times until timezone support
  lands.
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

### Changed

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
