<?php
/**
 * Hairdresser Pro - Contact Page
 */
require_once __DIR__ . '/functions.php';
define('PAGE_TITLE', 'Contact Us - ' . SITE_NAME);

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($name === '') $errors[] = 'Name is required.';
        if (!is_valid_email($email)) $errors[] = 'Valid email is required.';
        if ($message === '') $errors[] = 'Message is required.';
        if (strlen($message) < 10) $errors[] = 'Message must be at least 10 characters.';

        if (empty($errors)) {
            try {
                $db = get_db();
                $stmt = $db->prepare('INSERT INTO contacts (name, email, message) VALUES (?, ?, ?)');
                $stmt->execute([$name, $email, $message]);
                $success = true;
                // Clear form
                $name = $email = $message = '';
            } catch (PDOException $e) {
                error_log('Contact form error: ' . $e->getMessage());
                $errors[] = 'An error occurred. Please try again later.';
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<section class="section">
    <!-- Contact Hero Banner -->
    <div style="border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 3rem; position: relative; height: 220px;">
        <img src="https://images.unsplash.com/photo-1633681926022-84c23e8cb2d6?w=1400&q=80" alt="Contact us" style="width:100%;height:100%;object-fit:cover;" loading="lazy">
        <div style="position:absolute;inset:0;background:linear-gradient(160deg, rgba(11,11,11,0.92), rgba(20,20,20,0.8));display:flex;align-items:center;justify-content:center;flex-direction:column;">
            <h1 style="color:#f5f0e8;font-size:2.5rem;margin-bottom:0.5rem;font-family:'Raleway',sans-serif;letter-spacing:0.06em;text-transform:uppercase;">Get in Touch</h1>
            <p style="color:rgba(212,168,83,0.7);font-size:1.05rem;">We'd love to hear from you</p>
        </div>
    </div>

    <div class="contact-grid">
        <!-- Contact Form -->
        <div>
            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <span class="alert-icon">✓</span>
                    Thank you for your message! We'll get back to you soon.
                </div>
            <?php endif; ?>

            <?php foreach ($errors as $err): ?>
                <div class="alert alert-error" role="alert">
                    <span class="alert-icon">✕</span>
                    <?= h($err) ?>
                </div>
            <?php endforeach; ?>

            <form method="post" action="/contact.php" data-validate>
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="contact-name" class="form-label">Your Name *</label>
                    <input type="text" id="contact-name" name="name" class="form-control" required
                           value="<?= h($name ?? '') ?>" placeholder="Enter your name" aria-required="true">
                    <div class="form-error"></div>
                </div>
                <div class="form-group">
                    <label for="contact-email" class="form-label">Email Address *</label>
                    <input type="email" id="contact-email" name="email" class="form-control" required
                           value="<?= h($email ?? '') ?>" placeholder="your@email.com" aria-required="true">
                    <div class="form-error"></div>
                </div>
                <div class="form-group">
                    <label for="contact-message" class="form-label">Message *</label>
                    <textarea id="contact-message" name="message" class="form-control" rows="6" required
                              placeholder="How can we help you?" aria-required="true"><?= h($message ?? '') ?></textarea>
                    <div class="form-error"></div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">Send Message ✉️</button>
            </form>
        </div>

        <!-- Contact Info -->
        <div>
            <div style="display: grid; gap: 1rem;">
                <div class="contact-info-card">
                    <div class="contact-icon">📍</div>
                    <div>
                        <h4 style="margin-bottom: 0.25rem;">Our Location</h4>
                        <p style="margin: 0; font-size: 0.9rem;">123 Style Avenue<br>Beauty City, BC 12345</p>
                    </div>
                </div>
                <div class="contact-info-card">
                    <div class="contact-icon">📞</div>
                    <div>
                        <h4 style="margin-bottom: 0.25rem;">Phone</h4>
                        <p style="margin: 0; font-size: 0.9rem;">(555) 123-4567</p>
                    </div>
                </div>
                <div class="contact-info-card">
                    <div class="contact-icon">✉️</div>
                    <div>
                        <h4 style="margin-bottom: 0.25rem;">Email</h4>
                        <p style="margin: 0; font-size: 0.9rem;">info@hairdresserpro.com</p>
                    </div>
                </div>
                <div class="contact-info-card">
                    <div class="contact-icon">🕐</div>
                    <div>
                        <h4 style="margin-bottom: 0.25rem;">Opening Hours</h4>
                        <p style="margin: 0; font-size: 0.9rem;">
                            Mon-Fri: 9:00 AM - 6:00 PM<br>
                            Saturday: 10:00 AM - 2:00 PM<br>
                            Sunday: Closed
                        </p>
                    </div>
                </div>
                <!-- Map / Location Image -->
                <div style="border-radius: var(--radius); overflow: hidden; margin-top: 0.5rem; border: 1px solid var(--border-color);">
                    <img src="https://images.unsplash.com/photo-1600948836101-f9ffda59d250?w=700&q=80" alt="Our salon location" loading="lazy" style="width:100%;height:200px;object-fit:cover;">
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
