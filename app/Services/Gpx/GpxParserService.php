<?php

namespace App\Services\Gpx;

use App\Exceptions\GpxParseException;
use Carbon\Carbon;

class GpxParserService
{
    /**
     * Parse un fichier GPX et retourne la liste des trackpoints normalisés.
     *
     * @param  string $filePath Chemin absolu vers le fichier GPX
     * @return array<int, array{lat: float, lon: float, ele: float|null, time: \Carbon\Carbon|null}>
     * @throws \App\Exceptions\GpxParseException Si le fichier est introuvable, invalide ou vide
     */
    public function parse(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new GpxParseException("Fichier GPX introuvable : {$filePath}");
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($filePath);

        if ($xml === false) {
            throw new GpxParseException("Fichier GPX invalide ou mal formé.");
        }

        $points = [];

        foreach ($xml->trk as $track) {
            foreach ($track->trkseg as $segment) {
                foreach ($segment->trkpt as $point) {
                    $points[] = [
                        'lat'  => (float) $point['lat'],
                        'lon'  => (float) $point['lon'],
                        'ele'  => isset($point->ele) ? (float) $point->ele : null,
                        'time' => isset($point->time)
                            ? Carbon::parse((string) $point->time)
                            : null,
                    ];
                }
            }
        }

        if (empty($points)) {
            throw new GpxParseException("Aucun trackpoint trouvé dans le fichier GPX.");
        }

        return $points;
    }
}