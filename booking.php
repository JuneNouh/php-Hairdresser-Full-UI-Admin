<?php
/**
 * Hairdresser Pro - Booking Page (Multi-Step)
 */
require_once __DIR__ . '/functions.php';
define('PAGE_TITLE', 'Book an Appointment - ' . SITE_NAME);

$services = get_services();
$hairdressers = get_hairdressers();
$user = current_user();

include __DIR__ . '/includes/header.php';
?>

<section class="section">
    <!-- Booking Hero Banner -->
    <div style="border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 3rem; position: relative; height: 260px; width: 100%;">
        <img src="https://images.unsplash.com/photo-1595476108010-b4d1f102b1b1?w=1400&q=80" alt="Book your appointment" style="width:100%;height:100%;object-fit:cover;" loading="lazy">
        <div style="position:absolute;inset:0;background:linear-gradient(160deg, rgba(11,11,11,0.93), rgba(20,20,20,0.8));display:flex;align-items:center;justify-content:center;">
            <h1 style="color:#f5f0e8;font-size:2.8rem;margin:0;font-family:'Raleway',sans-serif;letter-spacing:0.06em;text-transform:uppercase;">Book Your Appointment</h1>
        </div>
    </div>

    <!-- Step Indicators -->
    <div class="booking-steps" role="navigation" aria-label="Booking steps">
        <div class="step-indicator active" id="step-ind-0">
            <div class="step-number">1</div>
            <div class="step-label">Service</div>
        </div>
        <div class="step-indicator" id="step-ind-1">
            <div class="step-number">2</div>
            <div class="step-label">Stylist</div>
        </div>
        <div class="step-indicator" id="step-ind-2">
            <div class="step-number">3</div>
            <div class="step-label">Date</div>
        </div>
        <div class="step-indicator" id="step-ind-3">
            <div class="step-number">4</div>
            <div class="step-label">Time</div>
        </div>
        <div class="step-indicator" id="step-ind-4">
            <div class="step-number">5</div>
            <div class="step-label">Details</div>
        </div>
    </div>

    <form id="booking-form" style="max-width: 960px; margin: 0 auto;">
        <?= csrf_field() ?>

        <!-- Step 1: Select Service -->
        <div class="step-content active" id="step-0">
            <h3 style="margin-bottom: 1.5rem; font-size: 1.5rem;">Choose a Service</h3>
            <div class="form-group">
                <label for="service-select" class="form-label">Select a service *</label>
                <select id="service-select" name="service_id" class="form-control" required aria-required="true">
                    <option value="">-- Select a service --</option>
                    <?php foreach ($services as $s): ?>
                        <option value="<?= (int)$s['id'] ?>" data-duration="<?= (int)$s['duration'] ?>" data-price="<?= number_format($s['price'], 2) ?>">
                            <?= h($s['name']) ?> — <?= format_price($s['price']) ?> (<?= (int)$s['duration'] ?> min)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="step-actions">
                <div></div>
                <button type="button" class="btn btn-primary" onclick="nextStep()">Next →</button>
            </div>
        </div>

        <!-- Step 2: Select Hairdresser -->
        <div class="step-content" id="step-1">
            <h3 style="margin-bottom: 1.5rem; font-size: 1.5rem;">Choose Your Stylist</h3>
            <div class="hairdresser-grid">
                <?php foreach ($hairdressers as $hd): ?>
                    <div class="hairdresser-card" data-id="<?= (int)$hd['id'] ?>" tabindex="0" role="button" aria-label="Select <?= h($hd['name']) ?>">
                        <img src="/<?= h($hd['photo_url']) ?>" alt="<?= h($hd['name']) ?>" loading="lazy" onerror="this.outerHTML='<div style=\'width:100px;height:100px;border-radius:50%;background:linear-gradient(135deg,#d4a853,#b8922e);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;color:#0b0b0b;font-size:2.2rem;font-weight:700;\'><?= substr(h($hd["name"]), 0, 1) ?></div>'">
                        <h4><?= h($hd['name']) ?></h4>
                        <div class="specialty"><?= h($hd['specialty']) ?></div>
                        <div class="bio"><?= h(mb_strimwidth($hd['bio'], 0, 100, '...')) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="step-actions">
                <button type="button" class="btn btn-secondary" onclick="prevStep()">← Back</button>
                <button type="button" class="btn btn-primary" onclick="nextStep()">Next →</button>
            </div>
        </div>

        <!-- Step 3: Select Date -->
        <div class="step-content" id="step-2">
            <h3 style="margin-bottom: 1.5rem; font-size: 1.5rem;">Pick a Date</h3>
            <div class="form-group" style="display: flex; justify-content: center;">
                <input type="text" id="booking-date" class="form-control" placeholder="Select a date" readonly style="display: none;" aria-label="Booking date">
            </div>
            <div class="step-actions">
                <button type="button" class="btn btn-secondary" onclick="prevStep()">← Back</button>
                <button type="button" class="btn btn-primary" onclick="nextStep()">Next →</button>
            </div>
        </div>

        <!-- Step 4: Select Time -->
        <div class="step-content" id="step-3">
            <h3 style="margin-bottom: 1.5rem; font-size: 1.5rem;">Choose a Time Slot</h3>
            <div id="time-slots-container">
                <div class="loading-overlay"><span class="spinner"></span> Select a date first</div>
            </div>
            <div class="step-actions">
                <button type="button" class="btn btn-secondary" onclick="prevStep()">← Back</button>
                <button type="button" class="btn btn-primary" onclick="nextStep()">Next →</button>
            </div>
        </div>

        <!-- Step 5: User Details -->
        <div class="step-content" id="step-4">
            <h3 style="margin-bottom: 1.5rem; font-size: 1.5rem;">Your Details</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="user-name" class="form-label">Full Name *</label>
                    <input type="text" id="user-name" name="user_name" class="form-control" required
                           value="<?= $user ? h($user['username']) : '' ?>"
                           placeholder="Enter your name" aria-required="true">
                    <div class="form-error"></div>
                </div>
                <div class="form-group">
                    <label for="user-email" class="form-label">Email Address *</label>
                    <input type="email" id="user-email" name="user_email" class="form-control" required
                           value="<?= $user ? h($user['email']) : '' ?>"
                           placeholder="your@email.com" aria-required="true">
                    <div class="form-error"></div>
                </div>
            </div>
            <div class="form-group">
                <label for="user-phone" class="form-label">Phone Number</label>
                <input type="tel" id="user-phone" name="user_phone" class="form-control"
                       placeholder="(555) 123-4567">
                <div class="form-error"></div>
            </div>
            <div class="form-group">
                <label for="user-notes" class="form-label">Special Requests (optional)</label>
                <textarea id="user-notes" name="notes" class="form-control" rows="3" placeholder="Any special requests or notes..."></textarea>
            </div>

            <?php if (!is_logged_in()): ?>
                <div class="alert alert-info" role="alert" style="font-size: 0.85rem;">
                    <span class="alert-icon">ℹ</span>
                    <a href="/auth.php" style="font-weight: 600;">Log in</a> to save bookings to your account and manage them later.
                </div>
            <?php endif; ?>

            <div class="step-actions">
                <button type="button" class="btn btn-secondary" onclick="prevStep()">← Back</button>
                <button type="submit" class="btn btn-primary btn-lg">✓ Confirm Booking</button>
            </div>
        </div>
    </form>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
