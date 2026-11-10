# Changelog

Notable changes to this project are documented here.

## 1.0.0 (unreleased)

### Added

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
