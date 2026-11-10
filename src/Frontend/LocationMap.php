<?php

declare(strict_types=1);

namespace EventManagerKit\Frontend;

use EventManagerKit\Entity\Location;

/**
 * Map helpers for a location. The map itself is rendered by the optional
 * `mapbox` package (MapboxKit); without it the page shows the address and a
 * link to an external map instead.
 */
final class LocationMap
{
    /**
     * Config for MapboxKit's <mapbox-component>, or null without coordinates.
     *
     * @return array<string,mixed>|null
     */
    public static function config(Location $location): ?array
    {
        if (!$location->hasCoordinates()) {
            return null;
        }

        return [
            'centerLatitude' => $location->getLatitude(),
            'centerLongitude' => $location->getLongitude(),
            'zoom' => 15,
            'pitch' => 0,
            'theme' => 'mapbox://styles/mapbox/streets-v12',
            'interactive' => true,
            'show_controls' => true,
            'control_placement' => 'top-right',
            'showBuildings' => false,
            'extrusionColor' => '#aaaaaa',
            'markers' => [['latitude' => $location->getLatitude(), 'longitude' => $location->getLongitude(), 'markerColor' => '#d33f49']],
        ];
    }

    /** Link that opens the place in a maps app: by coordinates, else by address. */
    public static function externalUrl(Location $location): ?string
    {
        $query = $location->hasCoordinates() ? $location->getLatitude() . ',' . $location->getLongitude() : $location->getAddress();

        return $query ? 'https://www.google.com/maps/search/?' . http_build_query(['api' => 1, 'query' => $query], '', '&', PHP_QUERY_RFC3986) : null;
    }

    /**
     * [latitude, longitude] from a Mapbox geocoding feature (JSON, `center` is [lng, lat]).
     *
     * @return array{0:float,1:float}|null
     */
    public static function centerFromFeature(string $json): ?array
    {
        $center = json_decode($json, true)['center'] ?? null;

        return is_array($center) && count($center) === 2 && is_numeric($center[0]) && is_numeric($center[1])
            ? [(float) $center[1], (float) $center[0]]
            : null;
    }

    /**
     * Best-effort geocode of a free-text address via MapboxKit; null when it
     * is not installed, unconfigured, has no confident match or fails.
     *
     * @return array{0:float,1:float}|null
     */
    public static function geocode(string $address): ?array
    {
        if (!class_exists(\MapboxKit\Api\Mapbox::class)) {
            return null;
        }
        try {
            $response = app(\MapboxKit\Api\Mapbox::class)->getLocationDetails($address);

            return $response && $response->getStatusCode() === 200 ? self::centerFromFeature((string) $response->getContent()) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
