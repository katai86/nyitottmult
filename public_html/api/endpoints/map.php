<?php
declare(strict_types=1);

use NyitottMult\Database;
use NyitottMult\PrivacyEngine;

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $places = Database::query(
        "SELECT pg.*, e.canonical_name as entity_name, e.type as entity_type
         FROM place_geometries pg
         JOIN entities e ON pg.entity_id = e.id"
    );

    $features = [];

    foreach ($places as $p) {
        $sanitized = PrivacyEngine::sanitizeGeometryForPublic($p);

        $features[] = [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [(float)$sanitized['longitude'], (float)$sanitized['latitude']]
            ],
            'properties' => [
                'id' => $p['id'],
                'entity_id' => $p['entity_id'],
                'name' => $p['entity_name'],
                'type' => $p['entity_type'],
                'address_text' => $sanitized['address_text'],
                'is_blurred' => $sanitized['is_blurred'],
                'precision_level' => $p['precision_level'],
                'district_id' => $p['district_id']
            ]
        ];
    }

    $geoJson = [
        'type' => 'FeatureCollection',
        'features' => $features
    ];

    echo json_encode($geoJson, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
