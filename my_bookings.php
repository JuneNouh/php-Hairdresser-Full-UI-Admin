<?php
/**
 * Hairdresser Pro - My Bookings Page
 */
require_once __DIR__ . '/functions.php';
define('PAGE_TITLE', 'My Bookings - ' . SITE_NAME);

require_login();

$user = current_user();
$page = max(1, (int)($_GET['page'] ?? 1));

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid security token. Please try again.');
    } else {
        $bookingId = (int)$_POST['booking_id'];
        if (cancel_booking($bookingId, $user['id'])) {
            set_flash('success', 'Booking cancelled successfully.');
        } else {
            set_flash('error', 'Unable to cancel booking.');
        }
    }
    redirect('my_bookings.php?page=' . $page);
}

$result = get_user_bookings($user['id'], $page);

include __DIR__ . '/includes/header.php';
?>

<section class="section">
    <!-- My Bookings Banner -->
    <div style="border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 2.5rem; position: relative; height: 180px;">
        <img src="https://images.unsplash.com/photo-1516975080664-ed2fc6a32937?w=1400&q=80" alt="My Bookings" style="width:100%;height:100%;object-fit:cover;" loading="lazy">
        <div style="position:absolute;inset:0;background:linear-gradient(160deg, rgba(11,11,11,0.93), rgba(20,20,20,0.78));display:flex;align-items:center;justify-content:center;">
            <h1 style="color:#f5f0e8;font-size:2.2rem;margin:0;font-family:'Raleway',sans-serif;letter-spacing:0.06em;text-transform:uppercase;">My Bookings</h1>
        </div>
    </div>

    <?php if (empty($result['bookings'])): ?>
        <div style="text-align: center; padding: 3rem;">
            <p style="font-size: 3rem; margin-bottom: 1rem;">📅</p>
            <h3>No bookings yet</h3>
            <p>You haven't made any bookings. Ready to book your first appointment?</p>
            <a href="/booking.php" class="btn btn-primary" style="margin-top: 1rem;">Book Now</a>
        </div>
    <?php else: ?>
        <!-- Filter Tabs -->
        <div class="filter-tabs" style="margin-bottom: 1.5rem;">
            <button class="filter-tab active" onclick="filterBookings('all', this)">All</button>
            <button class="filter-tab" onclick="filterBookings('pending', this)">Pending</button>
            <button class="filter-tab" onclick="filterBookings('confirmed', this)">Confirmed</button>
            <button class="filter-tab" onclick="filterBookings('cancelled', this)">Cancelled</button>
        </div>

        <div class="table-responsive">
            <table class="table" id="bookings-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Service</th>
                        <th>Stylist</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result['bookings'] as $b): ?>
                        <tr data-status="<?= h($b['status']) ?>">
                            <td><?= (int)$b['id'] ?></td>
                            <td><?= h($b['service_name']) ?></td>
                            <td><?= h($b['hairdresser_name']) ?></td>
                            <td><?= format_date($b['booking_date']) ?></td>
                            <td><?= format_time($b['booking_time']) ?></td>
                            <td><?= format_price($b['price']) ?></td>
                            <td><?= status_badge($b['status']) ?></td>
                            <td>
                                <?php if ($b['status'] !== 'cancelled'): ?>
                                    <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                                        <button type="submit" name="cancel_booking" class="btn btn-danger btn-sm">Cancel</button>
                                    </form>
                                <?php else: ?>
                                    <span class="tag">Cancelled</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?= pagination_html($result['current_page'], $result['pages'], 'my_bookings.php') ?>
    <?php endif; ?>
</section>

<script>
function filterBookings(status, btn) {
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('#bookings-table tbody tr').forEach(row => {
        if (status === 'all' || row.dataset.status === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
