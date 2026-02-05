<?php
/**
 * API: Create a booking
 * POST params: csrf_token, service_id, hairdresser_id, booking_date, booking_time, user_name, user_email, user_phone, notes
 */
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

try {
    // CSRF validation
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        echo json_encode(['error' => 'Invalid security token. Please refresh the page.']);
        exit;
    }

    // Validate inputs
    $service_id = (int)($_POST['service_id'] ?? 0);
    $hairdresser_id = (int)($_POST['hairdresser_id'] ?? 0);
    $booking_date = $_POST['booking_date'] ?? '';
    $booking_time = $_POST['booking_time'] ?? '';
    $user_name = trim($_POST['user_name'] ?? '');
    $user_email = trim($_POST['user_email'] ?? '');
    $user_phone = trim($_POST['user_phone'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    $errors = [];

    if (!$service_id) $errors[] = 'Please select a service.';
    if (!$hairdresser_id) $errors[] = 'Please select a hairdresser.';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $booking_date)) $errors[] = 'Invalid date.';
    if (!preg_match('/^\d{2}:\d{2}$/', $booking_time)) $errors[] = 'Invalid time.';
    if ($user_name === '') $errors[] = 'Name is required.';
    if (!is_valid_email($user_email)) $errors[] = 'Valid email is required.';
    if ($user_phone !== '' && !is_valid_phone($user_phone)) $errors[] = 'Invalid phone number.';

    // Check date is not in the past
    if ($booking_date < date('Y-m-d')) $errors[] = 'Cannot book past dates.';

    if (!empty($errors)) {
        echo json_encode(['error' => implode(' ', $errors)]);
        exit;
    }

    // Verify service and hairdresser exist
    $service = get_service($service_id);
    if (!$service) {
        echo json_encode(['error' => 'Invalid service selected.']);
        exit;
    }

    $hairdresser = get_hairdresser($hairdresser_id);
    if (!$hairdresser) {
        echo json_encode(['error' => 'Invalid hairdresser selected.']);
        exit;
    }

    // Check slot is still available
    $available_slots = get_available_slots($hairdresser_id, $booking_date, $service['duration']);
    if (!in_array($booking_time, $available_slots)) {
        echo json_encode(['error' => 'This time slot is no longer available. Please select another.']);
        exit;
    }

    // Create booking
    $booking_id = create_booking([
        'user_id' => $_SESSION['user_id'] ?? null,
        'user_name' => $user_name,
        'user_email' => $user_email,
        'user_phone' => $user_phone,
        'service_id' => $service_id,
        'hairdresser_id' => $hairdresser_id,
        'booking_date' => $booking_date,
        'booking_time' => $booking_time,
        'notes' => $notes
    ]);

    echo json_encode([
        'success' => true,
        'booking_id' => $booking_id,
        'message' => 'Booking created successfully!'
    ]);

} catch (Exception $e) {
    error_log('API create_booking error: ' . $e->getMessage());
    echo json_encode(['error' => 'An error occurred while creating the booking. Please try again.']);
}
