# Event Manager Kit

[![Code Standards](https://github.com/limegreentangerine/EventManagerKit/actions/workflows/CodeStandards.yml/badge.svg)](https://github.com/limegreentangerine/EventManagerKit/actions/workflows/CodeStandards.yml)
[![System Tests](https://github.com/limegreentangerine/EventManagerKit/actions/workflows/SystemTests.yml/badge.svg)](https://github.com/limegreentangerine/EventManagerKit/actions/workflows/SystemTests.yml)

Multilingual event manager for Concrete CMS 9: admins create events in the
dashboard and they appear on the site. Package handle `event_manager_kit`,
namespace `EventManagerKit`.

## Features

- **Dashboard** under `/dashboard/event_manager/*`: events, types, tags,
  locations and CSV import, each with search.
- **Multilingual**: names and text are stored per locale and fall back to the
  same language, the default locale, then any translation.
- **Repeating events**: daily, weekly or monthly rules with skipped dates,
  expanded into occurrences.
- **Locations**: address, coordinates, online URL, and an optional Mapbox map
  when the `mapbox` package is installed.
- **Blocks**: `event_calendar`, `em_event_list`, `em_featured_events` (events
  with the Featured flag) and `em_single_event` (one chosen event). They load events by
  `fetch` from `GET /ajax/events-manager/events`, so pages stay cacheable.
  Exclude `/ajax/events-manager/*` from CDN caching.
- **Event page** at `/events/{slug}` (slugs are per language; drafts 404 except
  for admins) with schema.org markup, canonical/hreflang/Open Graph tags and
  Add to calendar links.
- **iCalendar**: `GET /ajax/events-manager/ics/{slug}` and
  `GET /ajax/events-manager/feed.ics`.
- **Sitemap**: published events are added to Concrete's `sitemap.xml`.
- **CSV import**: validated up front and written in one transaction;
  re-uploads skip events already imported. The upload limit follows the
  server's PHP settings and is shown on the import page.

Status: not yet released (see `CHANGELOG.md`).

## Requirements

- PHP 8.4 or later.
- Composer.
- Node.js and npm, used by the Composer post-install script and JavaScript
  formatting commands.
- Access to the private Composer registry at
  `https://packages.limegreentangerine.net` to install private dependencies.

The package requires `limegreentangerine/class_kit`, `theme_kit` and
`mapbox_kit` (MapboxKit), and uses Concrete CMS 9.2 and PHPUnit 11 for
development. Maps and address geocoding only work once the `mapbox` package is
installed in Concrete and has an API key; without it the event page falls back
to a "View on map" link.

After changing entity columns, bump `$pkgVersion` in `controller.php`, then
upgrade the package in the dashboard and clear the cache.

## Install

```bash
composer install
```

Composer runs `npm install` after installation. If you need to authenticate to
the private registry, configure Composer to send the Cloudflare Access service
token headers for `packages.limegreentangerine.net`:

- `CF-ACCESS-CLIENT-ID` -> Cloudflare Access service-token client ID.
- `CF-ACCESS-CLIENT-SECRET` -> matching client secret.

Keep these credentials private; do not commit them to the repository.

## Development commands

Run these commands from the repository root:

| Command                                             | Purpose                                                                                                                        |
| --------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------ |
| `composer test`                                     | Run PHPUnit tests.                                                                                                             |
| `composer test-coverage`                            | Run PHPUnit and print a coverage report.                                                                                       |
| `composer typecheck`                                | Run PHPStan using `phpstan.neon`. Findings are displayed, but the command exits successfully even when PHPStan reports errors. |
| `composer format:check`                             | Check PHP and JavaScript formatting.                                                                                           |
| `composer format`                                   | Format PHP and JavaScript files.                                                                                               |
| `composer format:php:check` / `composer format:php` | Check or format PHP files with PHP-CS-Fixer.                                                                                   |
| `composer format:js:check` / `composer format:js`   | Check or format files with Prettier.                                                                                           |
| `npm run build`                                     | Compile `src-ts/events.ts` to `js/events.js` (commit both).                                                                    |

PHPStan analyzes `src` and `tests` at level 4, with bootstrap configuration
from `phpstan-bootstrap.php`. If you add package directories, update the
`paths` in `phpstan.neon` and the directories in `.php-cs-fixer.dist.php` so
they are included in analysis and formatting.

## GitHub Actions

- **CodeStandards** runs formatting checks and PHPStan on pushes and pull
  requests.
- **SystemTests** runs PHPUnit on pull requests.

Both workflows install dependencies from the private Composer registry. Add
these **repository secrets** under **Settings > Secrets and variables >
Actions** so the workflows can authenticate:

- `CF_ACCESS_CLIENT_ID` -> Cloudflare Access service-token client ID.
- `CF_ACCESS_CLIENT_SECRET` -> matching client secret.
