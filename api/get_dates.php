<?php
/**
 * API: Get available dates for a hairdresser
 * GET params: hairdresser_id
 */
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json');

try {
    $hairdresser_id = (int)($_GET['hairdresser_id'] ?? 0);

    if (!$hairdresser_id) {
        echo json_encode(['error' => 'Missing hairdresser_id.', 'dates' => []]);
        exit;
    }

    $dates = get_available_dates($hairdresser_id);

    echo json_encode([
        'success' => true,
        'dates' => $dates,
        'hairdresser_id' => $hairdresser_id
    ]);

} catch (Exception $e) {
    error_log('API get_dates error: ' . $e->getMessage());
    echo json_encode(['error' => 'Internal server error.', 'dates' => []]);
}
