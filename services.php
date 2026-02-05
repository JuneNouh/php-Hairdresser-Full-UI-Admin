<?php
/**
 * Hairdresser Pro - Services Page
 */
require_once __DIR__ . '/functions.php';
define('PAGE_TITLE', 'Our Services - ' . SITE_NAME);

$services = get_services();

// Search
$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM services WHERE active = 1 AND (name LIKE ? OR description LIKE ?) ORDER BY name");
    $like = '%' . $search . '%';
    $stmt->execute([$like, $like]);
    $services = $stmt->fetchAll();
}

include __DIR__ . '/includes/header.php';
?>

<section class="section">
    <h1 class="section-title">Our Services</h1>
    <p class="text-center" style="max-width: 600px; margin: -1.5rem auto 2rem;">Discover our range of professional hair care services. Each service is performed by our skilled stylists using premium products.</p>

    <!-- Search -->
    <div style="max-width: 400px; margin: 0 auto 2rem;">
        <form method="get" action="/services.php" class="search-bar">
            <input type="search" name="q" class="form-control" placeholder="Search services..." value="<?= h($search) ?>" aria-label="Search services" style="padding-left: 40px;">
        </form>
    </div>

    <?php if (empty($services)): ?>
        <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
            <p style="font-size: 2rem;">🔍</p>
            <p>No services found<?= $search ? ' for "' . h($search) . '"' : '' ?>.</p>
            <?php if ($search): ?>
                <a href="/services.php" class="btn btn-secondary" style="margin-top: 1rem;">View All Services</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="grid grid-3">
            <?php foreach ($services as $service): ?>
                <div class="card">
                    <?php
                    $serviceImages = [
                        'scissors' => 'https://images.unsplash.com/photo-1605497788044-5a32c7078486?w=600&q=80',
                        'palette'  => 'https://images.unsplash.com/photo-1562322140-8baeececf3df?w=600&q=80',
                        'wind'     => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=600&q=80',
                        'spa'      => 'https://images.unsplash.com/photo-1519823551278-64ac92734314?w=600&q=80',
                        'cut'      => 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?w=600&q=80',
                    ];
                    $imgUrl = $serviceImages[$service['icon']] ?? $serviceImages['scissors'];
                    ?>
                    <img src="<?= $imgUrl ?>" alt="<?= h($service['name']) ?>" class="card-img" loading="lazy" style="height:180px;width:100%;object-fit:cover;">
                    <div class="card-body">
                        <h3 class="card-title"><?= h($service['name']) ?></h3>
                        <p class="card-text"><?= h($service['description']) ?></p>
                    </div>
                    <div class="card-footer">
                        <span class="price-tag"><?= format_price($service['price']) ?></span>
                        <span class="duration-tag">⏱ <?= (int)$service['duration'] ?> min</span>
                    </div>
                    <div style="padding: 0 1.5rem 1.5rem;">
                        <a href="/booking.php" class="btn btn-primary" style="width: 100%;">Book This Service</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
