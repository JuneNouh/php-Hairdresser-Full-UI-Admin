<?php
/**
 * Hairdresser Pro - Booking Confirmation Page
 */
require_once __DIR__ . '/functions.php';
define('PAGE_TITLE', 'Booking Confirmed - ' . SITE_NAME);

$booking_id = (int)($_GET['id'] ?? 0);

if (!$booking_id) {
    set_flash('error', 'Invalid booking reference.');
    redirect('index.php');
}

$booking = get_booking($booking_id);

if (!$booking) {
    set_flash('error', 'Booking not found.');
    redirect('index.php');
}

// Handle ICS download
if (isset($_GET['download_ics'])) {
    $ics = generate_ics($booking);
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="appointment-' . $booking_id . '.ics"');
    echo $ics;
    exit;
}

// Send confirmation email (simulated)
send_confirmation_email($booking_id);

include __DIR__ . '/includes/header.php';
?>

<section class="section" style="text-align: center;">
    <div style="width:120px;height:120px;border-radius:50%;overflow:hidden;margin:0 auto 1.5rem;border:3px solid var(--gold);box-shadow:0 0 30px rgba(212,168,83,0.25);">
        <img src="https://images.unsplash.com/photo-1560066984-138dadb4c035?w=300&q=80" alt="Booking confirmed" style="width:100%;height:100%;object-fit:cover;">
    </div>
    <h1>Booking Confirmed!</h1>
    <p style="font-size: 1.1rem; max-width: 500px; margin: 0.5rem auto 2rem;">Your appointment has been booked successfully. We look forward to seeing you!</p>

    <div class="booking-summary">
        <h3 style="margin-bottom: 1.5rem; text-align: center;">Booking Summary</h3>
        <div class="summary-row">
            <span class="summary-label">Booking #</span>
            <span class="summary-value"><?= (int)$booking['id'] ?></span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Service</span>
            <span class="summary-value"><?= h($booking['service_name']) ?></span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Stylist</span>
            <span class="summary-value"><?= h($booking['hairdresser_name']) ?></span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Date</span>
            <span class="summary-value"><?= format_date($booking['booking_date']) ?></span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Time</span>
            <span class="summary-value"><?= format_time($booking['booking_time']) ?></span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Duration</span>
            <span class="summary-value"><?= (int)$booking['duration'] ?> minutes</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Status</span>
            <span class="summary-value"><?= status_badge($booking['status']) ?></span>
        </div>
        <div class="divider"></div>
        <div class="summary-row">
            <span class="summary-label">Name</span>
            <span class="summary-value"><?= h($booking['user_name']) ?></span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Email</span>
            <span class="summary-value"><?= h($booking['user_email']) ?></span>
        </div>
        <?php if ($booking['user_phone']): ?>
        <div class="summary-row">
            <span class="summary-label">Phone</span>
            <span class="summary-value"><?= h($booking['user_phone']) ?></span>
        </div>
        <?php endif; ?>
        <div class="divider"></div>
        <div class="summary-row">
            <span class="summary-label" style="font-size: 1.1rem; font-weight: 600;">Total</span>
            <span class="summary-value summary-total"><?= format_price($booking['price']) ?></span>
        </div>
    </div>

    <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
        <a href="/confirmation.php?id=<?= $booking_id ?>&download_ics=1" class="btn btn-secondary">📅 Add to Calendar</a>
        <?php if (is_logged_in()): ?>
            <a href="/my_bookings.php" class="btn btn-secondary">📋 My Bookings</a>
        <?php endif; ?>
        <a href="/index.php" class="btn btn-primary">🏠 Back to Home</a>
    </div>

    <div class="alert alert-info" style="max-width: 600px; margin: 2rem auto 0; font-size: 0.85rem;">
        <span class="alert-icon">ℹ</span>
        A confirmation email has been sent to <strong><?= h($booking['user_email']) ?></strong>. (Dev: Check PHP error logs for simulated email.)
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
