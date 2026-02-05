<?php
/**
 * Hairdresser Pro - Helper Functions
 */

require_once __DIR__ . '/config.php';

/**
 * Sanitize output for HTML
 */
function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a URL
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Check if user is logged in
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function is_admin(): bool {
    return is_logged_in() && ($_SESSION['user_role'] ?? '') === 'admin';
}

/**
 * Require login - redirect if not logged in
 */
function require_login(): void {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = 'Please log in to access this page.';
        redirect('auth.php');
    }
}

/**
 * Require admin - redirect if not admin
 */
function require_admin(): void {
    if (!is_admin()) {
        $_SESSION['flash_error'] = 'Access denied. Admin privileges required.';
        redirect('index.php');
    }
}

/**
 * Get current user info
 */
function current_user(): ?array {
    if (!is_logged_in()) return null;
    $db = get_db();
    $stmt = $db->prepare('SELECT id, username, email, role FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

/**
 * Set flash message
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash_' . $type] = $message;
}

/**
 * Get and clear flash message
 */
function get_flash(string $type): ?string {
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}

/**
 * Display flash messages HTML
 */
function display_flash(): string {
    $html = '';
    foreach (['success', 'error', 'info'] as $type) {
        $msg = get_flash($type);
        if ($msg) {
            $icon = $type === 'success' ? '✓' : ($type === 'error' ? '✕' : 'ℹ');
            $html .= '<div class="alert alert-' . $type . '" role="alert"><span class="alert-icon">' . $icon . '</span> ' . h($msg) . '<button class="alert-close" onclick="this.parentElement.remove()" aria-label="Close">×</button></div>';
        }
    }
    return $html;
}

/**
 * Get all active services
 */
function get_services(): array {
    $db = get_db();
    return $db->query('SELECT * FROM services WHERE active = 1 ORDER BY name')->fetchAll();
}

/**
 * Get a single service
 */
function get_service(int $id): ?array {
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM services WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Get all active hairdressers
 */
function get_hairdressers(): array {
    $db = get_db();
    return $db->query('SELECT * FROM hairdressers WHERE active = 1 ORDER BY name')->fetchAll();
}

/**
 * Get a single hairdresser
 */
function get_hairdresser(int $id): ?array {
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM hairdressers WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Get available time slots for a hairdresser on a specific date
 */
function get_available_slots(int $hairdresser_id, string $date, int $service_duration = 30): array {
    $db = get_db();

    // Get day of week (1=Mon, 7=Sun)
    $dayOfWeek = (int)date('N', strtotime($date));

    // Check if it's a holiday
    $stmt = $db->prepare('SELECT * FROM availability WHERE hairdresser_id = ? AND is_holiday = 1 AND holiday_date = ?');
    $stmt->execute([$hairdresser_id, $date]);
    if ($stmt->fetch()) {
        return []; // Holiday, no slots
    }

    // Get availability for this day
    $stmt = $db->prepare('SELECT start_time, end_time FROM availability WHERE hairdresser_id = ? AND day_of_week = ? AND is_holiday = 0');
    $stmt->execute([$hairdresser_id, $dayOfWeek]);
    $avail = $stmt->fetch();

    if (!$avail) {
        return []; // No availability
    }

    // Get existing bookings for this date/hairdresser
    $stmt = $db->prepare("SELECT booking_time, s.duration FROM bookings b JOIN services s ON b.service_id = s.id WHERE b.hairdresser_id = ? AND b.booking_date = ? AND b.status != 'cancelled'");
    $stmt->execute([$hairdresser_id, $date]);
    $booked = $stmt->fetchAll();

    // Build booked time ranges
    $bookedRanges = [];
    foreach ($booked as $b) {
        $start = strtotime($b['booking_time']);
        $end = $start + ($b['duration'] * 60);
        $bookedRanges[] = ['start' => $start, 'end' => $end];
    }

    // Generate slots
    $slots = [];
    $slotStart = strtotime($avail['start_time']);
    $slotEnd = strtotime($avail['end_time']);
    $interval = 30 * 60; // 30 min intervals

    // If date is today, don't show past slots
    $now = time();
    $isToday = date('Y-m-d') === $date;

    while ($slotStart + ($service_duration * 60) <= $slotEnd) {
        $slotEndTime = $slotStart + ($service_duration * 60);
        $available = true;

        // Skip past slots if today
        if ($isToday && $slotStart < $now) {
            $slotStart += $interval;
            continue;
        }

        // Check against booked ranges
        foreach ($bookedRanges as $range) {
            if ($slotStart < $range['end'] && $slotEndTime > $range['start']) {
                $available = false;
                break;
            }
        }

        if ($available) {
            $slots[] = date('H:i', $slotStart);
        }

        $slotStart += $interval;
    }

    return $slots;
}

/**
 * Get available dates for a hairdresser (next 30 days)
 */
function get_available_dates(int $hairdresser_id): array {
    $db = get_db();
    $dates = [];

    // Get hairdresser availability days
    $stmt = $db->prepare('SELECT DISTINCT day_of_week FROM availability WHERE hairdresser_id = ? AND is_holiday = 0');
    $stmt->execute([$hairdresser_id]);
    $availDays = array_column($stmt->fetchAll(), 'day_of_week');

    // Get holidays
    $stmt = $db->prepare('SELECT holiday_date FROM availability WHERE hairdresser_id = ? AND is_holiday = 1');
    $stmt->execute([$hairdresser_id]);
    $holidays = array_column($stmt->fetchAll(), 'holiday_date');

    // Check next 60 days
    for ($i = 0; $i <= 60; $i++) {
        $date = date('Y-m-d', strtotime("+{$i} days"));
        $dow = (int)date('N', strtotime($date));

        if (in_array($dow, $availDays) && !in_array($date, $holidays)) {
            $dates[] = $date;
        }
    }

    return $dates;
}

/**
 * Create a booking
 */
function create_booking(array $data): int {
    $db = get_db();
    $stmt = $db->prepare('INSERT INTO bookings (user_id, user_name, user_email, user_phone, service_id, hairdresser_id, booking_date, booking_time, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $data['user_id'] ?? null,
        $data['user_name'],
        $data['user_email'],
        $data['user_phone'] ?? '',
        $data['service_id'],
        $data['hairdresser_id'],
        $data['booking_date'],
        $data['booking_time'],
        'pending',
        $data['notes'] ?? ''
    ]);
    return (int)$db->lastInsertId();
}

/**
 * Get booking by ID
 */
function get_booking(int $id): ?array {
    $db = get_db();
    $stmt = $db->prepare('SELECT b.*, s.name as service_name, s.price, s.duration, h.name as hairdresser_name FROM bookings b JOIN services s ON b.service_id = s.id JOIN hairdressers h ON b.hairdresser_id = h.id WHERE b.id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Get user bookings
 */
function get_user_bookings(int $user_id, int $page = 1, int $per_page = 10): array {
    $db = get_db();
    $offset = ($page - 1) * $per_page;

    $countStmt = $db->prepare('SELECT COUNT(*) FROM bookings WHERE user_id = ?');
    $countStmt->execute([$user_id]);
    $total = (int)$countStmt->fetchColumn();

    $stmt = $db->prepare('SELECT b.*, s.name as service_name, s.price, s.duration, h.name as hairdresser_name FROM bookings b JOIN services s ON b.service_id = s.id JOIN hairdressers h ON b.hairdresser_id = h.id WHERE b.user_id = ? ORDER BY b.booking_date DESC, b.booking_time DESC LIMIT ? OFFSET ?');
    $stmt->execute([$user_id, $per_page, $offset]);

    return [
        'bookings' => $stmt->fetchAll(),
        'total' => $total,
        'pages' => max(1, (int)ceil($total / $per_page)),
        'current_page' => $page
    ];
}

/**
 * Cancel a booking
 */
function cancel_booking(int $booking_id, int $user_id): bool {
    $db = get_db();
    $stmt = $db->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status != 'cancelled'");
    $stmt->execute([$booking_id, $user_id]);
    return $stmt->rowCount() > 0;
}

/**
 * Send confirmation email (simulated in dev)
 */
function send_confirmation_email(int $booking_id): bool {
    $booking = get_booking($booking_id);
    if (!$booking) return false;

    $to = $booking['user_email'];
    $subject = 'Booking Confirmation - ' . SITE_NAME;
    $message = "Dear {$booking['user_name']},\n\n";
    $message .= "Your booking has been confirmed!\n\n";
    $message .= "Service: {$booking['service_name']}\n";
    $message .= "Hairdresser: {$booking['hairdresser_name']}\n";
    $message .= "Date: {$booking['booking_date']}\n";
    $message .= "Time: {$booking['booking_time']}\n";
    $message .= "Price: \${$booking['price']}\n\n";
    $message .= "Thank you for choosing " . SITE_NAME . "!\n";

    // In development, log instead of sending
    error_log("EMAIL SIMULATION TO: {$to}\nSUBJECT: {$subject}\n{$message}");

    return true;
}

/**
 * Generate ICS calendar file content
 */
function generate_ics(array $booking): string {
    $dtStart = date('Ymd\THis', strtotime($booking['booking_date'] . ' ' . $booking['booking_time']));
    $dtEnd = date('Ymd\THis', strtotime($booking['booking_date'] . ' ' . $booking['booking_time']) + ($booking['duration'] * 60));
    $stamp = date('Ymd\THis');
    $uid = uniqid('hairdresser-pro-', true);

    return "BEGIN:VCALENDAR\r\n" .
        "VERSION:2.0\r\n" .
        "PRODID:-//Hairdresser Pro//Booking//EN\r\n" .
        "BEGIN:VEVENT\r\n" .
        "UID:{$uid}\r\n" .
        "DTSTAMP:{$stamp}\r\n" .
        "DTSTART:{$dtStart}\r\n" .
        "DTEND:{$dtEnd}\r\n" .
        "SUMMARY:{$booking['service_name']} with {$booking['hairdresser_name']}\r\n" .
        "DESCRIPTION:Booking at " . SITE_NAME . "\\nService: {$booking['service_name']}\\nPrice: \${$booking['price']}\r\n" .
        "LOCATION:" . SITE_NAME . "\r\n" .
        "STATUS:CONFIRMED\r\n" .
        "END:VEVENT\r\n" .
        "END:VCALENDAR\r\n";
}

/**
 * Validate email
 */
function is_valid_email(string $email): bool {
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate phone
 */
function is_valid_phone(string $phone): bool {
    return (bool)preg_match('/^[\+]?[0-9\s\-\(\)]{7,20}$/', $phone);
}

/**
 * Format price
 */
function format_price(float $price): string {
    return '$' . number_format($price, 2);
}

/**
 * Format date for display
 */
function format_date(string $date): string {
    return date('l, F j, Y', strtotime($date));
}

/**
 * Format time for display
 */
function format_time(string $time): string {
    return date('g:i A', strtotime($time));
}

/**
 * Get booking status badge HTML
 */
function status_badge(string $status): string {
    $classes = [
        'pending' => 'badge-warning',
        'confirmed' => 'badge-success',
        'cancelled' => 'badge-danger',
    ];
    $class = $classes[$status] ?? 'badge-info';
    return '<span class="badge ' . $class . '">' . ucfirst(h($status)) . '</span>';
}

/**
 * Simple pagination HTML
 */
function pagination_html(int $currentPage, int $totalPages, string $baseUrl): string {
    if ($totalPages <= 1) return '';

    $html = '<nav class="pagination" aria-label="Page navigation"><ul>';

    if ($currentPage > 1) {
        $html .= '<li><a href="' . $baseUrl . '?page=' . ($currentPage - 1) . '" aria-label="Previous">&laquo;</a></li>';
    }

    for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++) {
        $active = $i === $currentPage ? ' class="active"' : '';
        $html .= '<li' . $active . '><a href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
    }

    if ($currentPage < $totalPages) {
        $html .= '<li><a href="' . $baseUrl . '?page=' . ($currentPage + 1) . '" aria-label="Next">&raquo;</a></li>';
    }

    $html .= '</ul></nav>';
    return $html;
}

/**
 * Get admin stats
 */
function get_admin_stats(): array {
    $db = get_db();

    $stats = [];
    $stats['total_bookings'] = (int)$db->query('SELECT COUNT(*) FROM bookings')->fetchColumn();
    $stats['pending_bookings'] = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
    $stats['confirmed_bookings'] = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
    $stats['total_revenue'] = (float)$db->query("SELECT COALESCE(SUM(s.price), 0) FROM bookings b JOIN services s ON b.service_id = s.id WHERE b.status != 'cancelled'")->fetchColumn();
    $stats['total_users'] = (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $stats['today_bookings'] = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE booking_date = '" . date('Y-m-d') . "'")->fetchColumn();

    // Bookings per day (last 7 days)
    $stmt = $db->query("SELECT booking_date, COUNT(*) as count FROM bookings WHERE booking_date >= date('now', '-7 days') GROUP BY booking_date ORDER BY booking_date");
    $stats['bookings_per_day'] = $stmt->fetchAll();

    // Revenue per service
    $stmt = $db->query("SELECT s.name, COUNT(b.id) as count, SUM(s.price) as revenue FROM bookings b JOIN services s ON b.service_id = s.id WHERE b.status != 'cancelled' GROUP BY s.id ORDER BY revenue DESC");
    $stats['revenue_per_service'] = $stmt->fetchAll();

    return $stats;
}
