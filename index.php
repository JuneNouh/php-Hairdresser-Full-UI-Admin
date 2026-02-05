<?php
/**
 * Hairdresser Pro - Home Page
 */
require_once __DIR__ . '/functions.php';
define('PAGE_TITLE', SITE_NAME . ' - Book Your Perfect Hair Appointment');

$services = get_services();
$hairdressers = get_hairdressers();

include __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero" style="background-image: url('https://images.unsplash.com/photo-1560066984-138dadb4c035?w=1920&q=80'); background-size: cover; background-position: center;">
    <div style="position:absolute;inset:0;background:linear-gradient(160deg, rgba(11,11,11,0.93) 0%, rgba(20,20,20,0.85) 40%, rgba(11,11,11,0.88) 100%);z-index:1;"></div>
    <div class="hero-content">
        <h1>Book Your Perfect Hair Appointment</h1>
        <p>Experience professional hair care with our talented stylists. From classic cuts to creative coloring, we bring out the best in your hair.</p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="/booking.php" class="btn btn-primary btn-lg">Book Appointment</a>
            <a href="/services.php" class="btn btn-secondary btn-lg">Our Services →</a>
        </div>
    </div>
</section>

<!-- Featured Services -->
<section class="section">
    <h2 class="section-title">Our Services</h2>
    <div class="carousel" style="overflow: hidden;">
        <div class="carousel-track">
            <?php
            $serviceCarouselImages = [
                'scissors' => 'https://images.unsplash.com/photo-1605497788044-5a32c7078486?w=500&q=80',
                'palette'  => 'https://images.unsplash.com/photo-1562322140-8baeececf3df?w=500&q=80',
                'wind'     => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=500&q=80',
                'spa'      => 'https://images.unsplash.com/photo-1519823551278-64ac92734314?w=500&q=80',
                'cut'      => 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?w=500&q=80',
            ];
            foreach ($services as $service): 
                $svcImg = $serviceCarouselImages[$service['icon']] ?? $serviceCarouselImages['scissors'];
            ?>
                <div class="card" style="min-width: 280px; flex-shrink: 0;">
                    <img src="<?= $svcImg ?>" alt="<?= h($service['name']) ?>" loading="lazy" style="width:100%;height:160px;object-fit:cover;">
                    <div class="card-body" style="text-align: center;">
                        <h3 class="card-title"><?= h($service['name']) ?></h3>
                        <p class="card-text"><?= h($service['description']) ?></p>
                        <div style="margin-top: 1rem;">
                            <span class="price-tag"><?= format_price($service['price']) ?></span>
                            <span class="duration-tag">⏱ <?= (int)$service['duration'] ?> min</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="carousel-controls">
            <button class="carousel-btn carousel-prev" aria-label="Previous">‹</button>
            <button class="carousel-btn carousel-next" aria-label="Next">›</button>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="section">
    <h2 class="section-title">About Us</h2>
    <div class="about-content">
        <div>
            <h3>Welcome to <?= SITE_NAME ?></h3>
            <p>Founded in 2010, we've been transforming hair and boosting confidence for over a decade. Our salon combines modern techniques with a warm, inviting atmosphere to create an exceptional experience.</p>
            <p>Our team of skilled professionals stays at the forefront of hair trends and techniques, ensuring you always leave our chair looking and feeling your absolute best.</p>
            <a href="/booking.php" class="btn btn-primary" style="margin-top: 1rem;">Book an Appointment</a>
        </div>
        <div class="about-image">
            <img src="https://images.unsplash.com/photo-1521590832167-7bcbfaa6381f?w=800&q=80" alt="Our modern salon interior" loading="lazy" style="width:100%;height:400px;object-fit:cover;">
        </div>
    </div>
</section>

<!-- Meet Our Team -->
<section class="section">
    <h2 class="section-title">Meet Our Stylists</h2>
    <div class="grid grid-3">
        <?php foreach ($hairdressers as $hd): ?>
            <div class="card" style="text-align: center;">
                <div style="padding: 2rem 2rem 0;">
                    <img src="/<?= h($hd['photo_url']) ?>" alt="<?= h($hd['name']) ?>" style="width: 120px; height: 120px; border-radius: 50%; margin: 0 auto; object-fit: cover; background: var(--bg-tertiary); border: 2px solid var(--border-gold);" loading="lazy" onerror="this.outerHTML='<div style=\'width:120px;height:120px;border-radius:50%;background:linear-gradient(135deg,#d4a853,#b8922e);display:flex;align-items:center;justify-content:center;margin:0 auto;color:#0b0b0b;font-size:2.5rem;font-weight:700;\'><?= substr(h($hd["name"]), 0, 1) ?></div>'">
                </div>
                <div class="card-body">
                    <h3 class="card-title"><?= h($hd['name']) ?></h3>
                    <p class="text-accent" style="font-size: 0.9rem; margin-bottom: 0.5rem;"><?= h($hd['specialty']) ?></p>
                    <p class="card-text"><?= h($hd['bio']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Testimonials -->
<section class="section">
    <h2 class="section-title">What Our Clients Say</h2>
    <div class="testimonials-grid">
        <div class="testimonial-card">
            <p class="testimonial-text">Sophie is absolutely amazing! She understood exactly what I wanted and gave me the best haircut I've ever had. Highly recommend!</p>
            <div class="testimonial-author">
                <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100&q=80" alt="Amanda K." style="width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid var(--border-gold);">
                <div>
                    <div class="testimonial-name">Amanda K.</div>
                    <div class="testimonial-rating">★★★★★</div>
                </div>
            </div>
        </div>
        <div class="testimonial-card">
            <p class="testimonial-text">The online booking system is so convenient, and the service is always top-notch. My go-to salon for years now!</p>
            <div class="testimonial-author">
                <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&q=80" alt="Michael R." style="width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid var(--border-gold);">
                <div>
                    <div class="testimonial-name">Michael R.</div>
                    <div class="testimonial-rating">★★★★★</div>
                </div>
            </div>
        </div>
        <div class="testimonial-card">
            <p class="testimonial-text">Elena worked magic on my damaged hair. After the treatment, my hair felt healthier than ever. Will definitely be back!</p>
            <div class="testimonial-author">
                <img src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=100&q=80" alt="Sarah L." style="width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid var(--border-gold);">
                <div>
                    <div class="testimonial-name">Sarah L.</div>
                    <div class="testimonial-rating">★★★★★</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section" style="text-align: center;">
    <div style="background: linear-gradient(135deg, rgba(11,11,11,0.94), rgba(20,20,20,0.9)), url('https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=1200&q=80') center/cover; backdrop-filter: blur(12px); border: 1px solid var(--border-gold); border-radius: var(--radius-lg); padding: 4rem 3rem; max-width: 800px; margin: 0 auto; position: relative; overflow: hidden;">
        <h2>Ready to Transform Your Look?</h2>
        <p style="font-size: 1.1rem; margin-bottom: 1.5rem;">Book your appointment today and let our professionals take care of you.</p>
        <a href="/booking.php" class="btn btn-primary btn-lg">Book Your Appointment</a>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
