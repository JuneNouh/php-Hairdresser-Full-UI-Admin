<?php
/**
 * Hairdresser Pro - 404 Not Found Page
 */
require_once __DIR__ . '/functions.php';
define('PAGE_TITLE', 'Page Not Found - ' . SITE_NAME);

http_response_code(404);
include __DIR__ . '/includes/header.php';
?>

<section class="error-page" style="position: relative;">
    <div style="position:absolute;inset:0;z-index:0;opacity:0.15;">
        <img src="https://images.unsplash.com/photo-1585747860019-8e91e30e32cc?w=1400&q=80" alt="" style="width:100%;height:100%;object-fit:cover;" loading="lazy">
    </div>
    <div style="position:relative;z-index:1;">
        <div class="error-code">404</div>
        <h2>Page Not Found</h2>
        <p>Oops! The page you're looking for doesn't exist or has been moved.</p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="index.php" class="btn btn-primary">🏠 Go Home</a>
            <a href="booking.php" class="btn btn-secondary">📅 Book Now</a>
            <a href="contact.php" class="btn btn-secondary">✉️ Contact Us</a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
