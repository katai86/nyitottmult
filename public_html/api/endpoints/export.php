<?php
declare(strict_types=1);

use NyitottMult\Database;
use NyitottMult\PrivacyEngine;

$parts = explode('/', trim($uri, '/'));
$format = $parts[2] ?? '';

if ($format === 'documents.json') {
    $docs = Database::query("SELECT id, archive_code, document_type, title, date_created, license_note FROM source_documents ORDER BY archive_code ASC");
    header('Content-Disposition: attachment; filename="oroshaza_source_documents.json"');
    echo json_encode($docs, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
} elseif ($format === 'documents.csv') {
    $docs = Database::query("SELECT id, archive_code, document_type, title, date_created FROM source_documents ORDER BY archive_code ASC");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="oroshaza_source_documents.csv"');
    
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Leveltari_Jelzet', 'Irattipus', 'Cim', 'Keletkezes_Datuma']);
    foreach ($docs as $d) {
        fputcsv($out, [$d['id'], $d['archive_code'], $d['document_type'], $d['title'], $d['date_created']]);
    }
    fclose($out);
    exit;
} elseif ($format === 'claims.json') {
    $claims = Database::query(
        "SELECT c.id, c.statement, c.quote, d.archive_code, p.page_number
         FROM claims c
         JOIN source_documents d ON c.document_id = d.id
         JOIN pages p ON c.page_id = p.id
         WHERE c.status = 'ellenorzott'"
    );
    header('Content-Disposition: attachment; filename="oroshaza_verified_claims.json"');
    echo json_encode($claims, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
} elseif ($format === 'places.geojson') {
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
                'name' => $p['entity_name'],
                'type' => $p['entity_type'],
                'address' => $sanitized['address_text'],
                'is_blurred' => $sanitized['is_blurred']
            ]
        ];
    }
    header('Content-Disposition: attachment; filename="oroshaza_places.geojson"');
    echo json_encode(['type' => 'FeatureCollection', 'features' => $features], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
} else {
    echo json_encode([
        'available_exports' => [
            '/api/v1/export/documents.json',
            '/api/v1/export/documents.csv',
            '/api/v1/export/places.geojson',
            '/api/v1/export/claims.json'
        ]
    ], JSON_PRETTY_PRINT);
}
