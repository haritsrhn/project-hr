<?php

namespace App\Helpers;

class GeoHelper
{
    /**
     * Calculate the distance between two GPS coordinates using the Haversine formula.
     *
     * @param  float  $lat1  Latitude of first point (degrees)
     * @param  float  $lng1  Longitude of first point (degrees)
     * @param  float  $lat2  Latitude of second point (degrees)
     * @param  float  $lng2  Longitude of second point (degrees)
     * @return float  Distance in meters
     */
    public static function distanceInMeters(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
