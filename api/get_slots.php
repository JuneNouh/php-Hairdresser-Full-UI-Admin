<?php
/**
 * API: Get available time slots
 * GET params: hairdresser_id, date, duration (optional)
 */
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json');

try {
    $hairdresser_id = (int)($_GET['hairdresser_id'] ?? 0);
    $date = $_GET['date'] ?? '';
    $duration = (int)($_GET['duration'] ?? 30);

    if (!$hairdresser_id || !$date) {
        echo json_encode(['error' => 'Missing required parameters.', 'slots' => []]);
        exit;
    }

    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        echo json_encode(['error' => 'Invalid date format.', 'slots' => []]);
        exit;
    }

    // Don't allow past dates
    if ($date < date('Y-m-d')) {
        echo json_encode(['error' => 'Cannot book past dates.', 'slots' => []]);
        exit;
    }

    $slots = get_available_slots($hairdresser_id, $date, $duration);

    echo json_encode([
        'success' => true,
        'slots' => $slots,
        'date' => $date,
        'hairdresser_id' => $hairdresser_id
    ]);

} catch (Exception $e) {
    error_log('API get_slots error: ' . $e->getMessage());
    echo json_encode(['error' => 'Internal server error.', 'slots' => []]);
}
