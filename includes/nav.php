<?php
/**
 * Navigation Include
 */
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<nav class="navbar" role="navigation" aria-label="Main navigation">
    <div class="container">
        <a href="/index.php" class="navbar-brand" aria-label="<?= SITE_NAME ?> Home">
            <span class="brand-icon">✂️</span>
            <span><?= SITE_NAME ?></span>
        </a>

        <button class="nav-toggle" id="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>

        <ul class="nav-links" id="nav-links">
            <li><a href="/index.php" class="<?= $currentPage === 'index' ? 'active' : '' ?>">Home</a></li>
            <li><a href="/services.php" class="<?= $currentPage === 'services' ? 'active' : '' ?>">Services</a></li>
            <li><a href="/booking.php" class="<?= $currentPage === 'booking' ? 'active' : '' ?>">Book Now</a></li>
            <li><a href="/contact.php" class="<?= $currentPage === 'contact' ? 'active' : '' ?>">Contact</a></li>
            <?php if (is_logged_in()): ?>
                <li><a href="/my_bookings.php" class="<?= $currentPage === 'my_bookings' ? 'active' : '' ?>">My Bookings</a></li>
                <?php if (is_admin()): ?>
                    <li><a href="/admin/index.php" class="<?= strpos($_SERVER['PHP_SELF'], 'admin/') !== false ? 'active' : '' ?>">Admin</a></li>
                <?php endif; ?>
                <li><a href="/auth.php?action=logout">Logout (<?= h($_SESSION['username'] ?? '') ?>)</a></li>
            <?php else: ?>
                <li><a href="/auth.php" class="<?= $currentPage === 'auth' ? 'active' : '' ?>">Login</a></li>
            <?php endif; ?>
            <li>
                <button class="theme-toggle" id="theme-toggle" aria-label="Toggle dark/light mode">
                    ✦ <span>Light Mode</span>
                </button>
            </li>
        </ul>
    </div>
</nav>
