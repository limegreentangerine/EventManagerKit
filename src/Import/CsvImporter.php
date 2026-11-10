<?php

declare(strict_types=1);

namespace EventManagerKit\Import;

use DateTimeImmutable;

/**
 * Header-driven, per-locale CSV parser. Pure: no database, no Concrete.
 *
 * Columns (case-insensitive, any order):
 *   start, end                      required; Y-m-d[ H:i[:s]] or d/m/Y[ H:i[:s]]
 *   all_day                         optional; 1/yes/true. Also implied when neither date has a time
 *   name_<locale>                   required for at least one locale, e.g. name_en_GB, name_cy_GB
 *   description_<locale>            optional
 *   location_<locale>, sub_location_<locale>   optional location (and child) names
 *   type_<locale>                   optional event type name
 *   tags_<locale>                   optional; semicolon-separated, same order in every locale
 */
final class CsvImporter
{
    private const FORMATS = ['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d', 'd/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y'];

    private const LOCALE_COLUMNS = ['name', 'description', 'sub_location', 'location', 'type', 'tags'];

    /**
     * @return array<string,mixed> start/end/all_day => index; name/... => [locale => index]
     */
    private function mapHeader(array $header): array
    {
        $map = [];
        foreach ($header as $index => $title) {
            $title = trim((string) $title);
            $key = strtolower($title);
            if (in_array($key, ['start', 'end', 'all_day'], true)) {
                $map[$key] = $index;
                continue;
            }
            foreach (self::LOCALE_COLUMNS as $kind) {
                if (preg_match('/^' . $kind . '_([a-z]{2,3}_[A-Za-z]{2,4})$/i', $title, $m)) {
                    $locale = $this->normaliseLocale($m[1]);
                    $map[$kind][$locale] = $index;
                    break;
                }
            }
        }

        return $map;
    }

    private function normaliseLocale(string $locale): string
    {
        [$language, $region] = explode('_', $locale, 2) + [1 => ''];

        return strtolower($language) . '_' . strtoupper($region);
    }

    private function parseRow(int $line, array $data, array $columns, ImportPlan $plan): ?ImportRow
    {
        $errors = count($plan->errors);
        $cell = fn(int $i): string => trim((string) ($data[$i] ?? ''));
        $byLocale = function (string $kind) use ($columns, $cell): array {
            $values = [];
            foreach ($columns[$kind] ?? [] as $locale => $i) {
                if ($cell($i) !== '') {
                    $values[$locale] = $cell($i);
                }
            }

            return $values;
        };

        $startHasTime = $endHasTime = false;
        $start = $this->parseDate($cell($columns['start']), $startHasTime);
        $end = $this->parseDate($cell($columns['end']), $endHasTime);
        if (!$start) {
            $plan->addError($line, 'Invalid start date "' . $cell($columns['start']) . '".');
        }
        if (!$end) {
            $plan->addError($line, 'Invalid end date "' . $cell($columns['end']) . '".');
        }
        if ($start && $end && $end < $start) {
            $plan->addError($line, 'End date is before the start date.');
        }

        $names = $byLocale('name');
        if (!$names) {
            $plan->addError($line, 'No event name given in any locale.');
        }

        $location = $byLocale('location');
        $subLocation = $byLocale('sub_location');
        if ($subLocation && !$location) {
            $plan->addError($line, 'A sub location needs a location.');
        }

        $tags = $this->parseTags($line, $byLocale('tags'), $plan);

        if (count($plan->errors) > $errors) {
            return null;
        }

        $allDay = isset($columns['all_day'])
            ? in_array(strtolower($cell($columns['all_day'])), ['1', 'yes', 'true', 'y'], true)
            : false;

        return new ImportRow(
            $line,
            $start,
            $end,
            $allDay || (!$startHasTime && !$endHasTime),
            $names,
            $byLocale('description'),
            $location,
            $subLocation,
            $byLocale('type'),
            $tags,
        );
    }

    /**
     * "a;b" in en_GB and "x;y" in cy_GB become [[en_GB=>a, cy_GB=>x], [en_GB=>b, cy_GB=>y]].
     *
     * @param  array<string,string>       $cells
     * @return list<array<string,string>>
     */
    private function parseTags(int $line, array $cells, ImportPlan $plan): array
    {
        $split = array_map(fn(string $v) => array_values(array_filter(array_map('trim', explode(';', $v)), 'strlen')), $cells);
        $counts = array_values(array_unique(array_map('count', $split)));
        if (count($counts) > 1) {
            $plan->addError($line, 'Every locale must list the same number of tags.');

            return [];
        }

        $tags = [];
        for ($i = 0; $i < ($counts[0] ?? 0); $i++) {
            $tags[] = array_map(fn(array $values) => $values[$i], $split);
        }

        return $tags;
    }

    private function parseDate(string $value, bool &$hasTime = false): ?DateTimeImmutable
    {
        foreach (self::FORMATS as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
            $errors = DateTimeImmutable::getLastErrors();
            if ($date && (!$errors || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                $hasTime = str_contains($format, 'H');

                return $date;
            }
        }
        $hasTime = false;

        return null;
    }

    /**
     * @param resource     $handle      open CSV stream
     * @param list<string> $siteLocales locales the site is translated into
     */
    public function parse($handle, array $siteLocales): ImportPlan
    {
        $plan = new ImportPlan();

        $header = fgetcsv($handle, escape: '');
        if (!$header || $header === [null]) {
            $plan->addError(1, 'The file is empty.');

            return $plan;
        }

        $columns = $this->mapHeader($header);
        foreach (['start', 'end'] as $required) {
            if (!isset($columns[$required])) {
                $plan->addError(1, sprintf('Missing required column "%s".', $required));
            }
        }
        if (!isset($columns['name'])) {
            $plan->addError(1, 'Missing a name column, e.g. "name_en_GB".');
        }
        $unknown = [];
        foreach (self::LOCALE_COLUMNS as $kind) {
            foreach (array_keys($columns[$kind] ?? []) as $locale) {
                if (!in_array($locale, $siteLocales, true) && !isset($unknown[$locale])) {
                    $unknown[$locale] = true;
                    $plan->addError(1, sprintf('Column locale "%s" is not a locale of this site.', $locale));
                }
            }
        }
        if ($plan->errors) {
            return $plan;
        }

        $line = 1;
        while (($data = fgetcsv($handle, escape: '')) !== false) {
            $line++;
            if ($data === [null] || trim(implode('', $data)) === '') {
                continue;
            }
            $row = $this->parseRow($line, $data, $columns, $plan);
            if ($row) {
                $plan->rows[] = $row;
            }
        }

        return $plan;
    }

    /**
     * Lowercase, underscore-separated handle used to match types, tags and locations.
     */
    public static function handleFor(string $name): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', '_', strtolower(trim($name))), '_');
    }
}
