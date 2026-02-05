<?php $isAdmin = strpos($_SERVER['PHP_SELF'], '/admin/') !== false; ?>
    <?php if (!$isAdmin): ?>
        </div><!-- /.container -->
    </main>

    <!-- Footer -->
    <footer class="footer" role="contentinfo">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h4><?= SITE_NAME ?></h4>
                    <p>Your premium destination for professional hair care. Book your appointment today and let our expert stylists transform your look.</p>
                    <div class="social-links">
                        <a href="#" class="social-link" aria-label="Facebook" title="Facebook">f</a>
                        <a href="#" class="social-link" aria-label="Instagram" title="Instagram">📷</a>
                        <a href="#" class="social-link" aria-label="Twitter" title="Twitter">𝕏</a>
                        <a href="#" class="social-link" aria-label="TikTok" title="TikTok">♪</a>
                    </div>
                </div>
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <a href="/index.php">Home</a>
                    <a href="/services.php">Services</a>
                    <a href="/booking.php">Book Appointment</a>
                    <a href="/contact.php">Contact Us</a>
                </div>
                <div class="footer-col">
                    <h4>Opening Hours</h4>
                    <p>Monday - Friday: 9:00 AM - 6:00 PM</p>
                    <p>Saturday: 10:00 AM - 2:00 PM</p>
                    <p>Sunday: Closed</p>
                </div>
                <div class="footer-col">
                    <h4>Contact Info</h4>
                    <p>📍 123 Style Avenue, Beauty City, BC 12345</p>
                    <p>📞 (555) 123-4567</p>
                    <p>✉️ info@hairdresserpro.com</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <?php endif; ?>

    <!-- Scroll to Top -->
    <button class="scroll-top" id="scroll-top" aria-label="Scroll to top" title="Scroll to top">↑</button>

    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <!-- Main Script -->
    <script src="/js/script.js"></script>

    <!-- Apply saved theme immediately -->
    <script>
        (function(){
            var theme = localStorage.getItem('theme') || 'dark';
            if (theme === 'light') {
                document.body.classList.add('light-mode');
            }
            document.documentElement.classList.remove('light-mode-preload');
        })();
    </script>
</body>
</html>
