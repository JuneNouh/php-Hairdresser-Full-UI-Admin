/**
 * Hairdresser Pro - Main JavaScript
 * Handles dark mode, Flatpickr, AJAX, multi-step booking, mobile nav, animations
 */

document.addEventListener('DOMContentLoaded', () => {
    initDarkMode();
    initMobileNav();
    initScrollTop();
    initAlertDismiss();
    initAdminSidebar();
    initAnimations();

    // Page-specific inits
    if (document.getElementById('booking-form')) {
        initBookingForm();
    }
    if (document.querySelector('.auth-tabs')) {
        initAuthTabs();
    }
    if (document.querySelector('.carousel-track')) {
        initCarousel();
    }
});

/* ============================================
   Dark Mode Toggle
   ============================================ */
function initDarkMode() {
    const toggle = document.getElementById('theme-toggle');
    if (!toggle) return;

    // Default to dark mode
    const savedTheme = localStorage.getItem('theme') || 'dark';
    applyTheme(savedTheme);

    toggle.addEventListener('click', () => {
        const current = document.body.classList.contains('light-mode') ? 'light' : 'dark';
        const next = current === 'dark' ? 'light' : 'dark';
        applyTheme(next);
        localStorage.setItem('theme', next);
    });
}

function applyTheme(theme) {
    const toggle = document.getElementById('theme-toggle');
    if (theme === 'light') {
        document.body.classList.add('light-mode');
        if (toggle) toggle.innerHTML = '🌙 <span>Dark Mode</span>';
    } else {
        document.body.classList.remove('light-mode');
        if (toggle) toggle.innerHTML = '✦ <span>Light Mode</span>';
    }
}

/* ============================================
   Mobile Navigation
   ============================================ */
function initMobileNav() {
    const toggle = document.getElementById('nav-toggle');
    const links = document.getElementById('nav-links');
    if (!toggle || !links) return;

    toggle.addEventListener('click', () => {
        links.classList.toggle('open');
        toggle.setAttribute('aria-expanded', links.classList.contains('open'));
    });

    // Close on link click
    links.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            links.classList.remove('open');
            toggle.setAttribute('aria-expanded', 'false');
        });
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
        if (!toggle.contains(e.target) && !links.contains(e.target)) {
            links.classList.remove('open');
            toggle.setAttribute('aria-expanded', 'false');
        }
    });
}

/* ============================================
   Scroll to Top Button
   ============================================ */
function initScrollTop() {
    const btn = document.getElementById('scroll-top');
    if (!btn) return;

    window.addEventListener('scroll', () => {
        btn.classList.toggle('visible', window.scrollY > 300);
    });

    btn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

/* ============================================
   Alert Auto-Dismiss
   ============================================ */
function initAlertDismiss() {
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
}

/* ============================================
   Admin Sidebar Toggle (Mobile)
   ============================================ */
function initAdminSidebar() {
    const toggle = document.getElementById('admin-sidebar-toggle');
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.getElementById('admin-sidebar-overlay');

    if (!toggle || !sidebar) return;

    function openSidebar() {
        sidebar.classList.add('open');
        if (overlay) overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    toggle.addEventListener('click', () => {
        if (sidebar.classList.contains('open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    // Close sidebar on link click (mobile)
    sidebar.querySelectorAll('.admin-sidebar-link').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 1024) {
                closeSidebar();
            }
        });
    });

    // Close on escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });

    // Close on window resize to desktop
    window.addEventListener('resize', () => {
        if (window.innerWidth > 1024) {
            closeSidebar();
        }
    });
}

/* ============================================
   Auth Tabs (Login/Register)
   ============================================ */
function initAuthTabs() {
    const tabs = document.querySelectorAll('.auth-tab');
    const forms = document.querySelectorAll('.auth-form');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.tab;
            tabs.forEach(t => t.classList.remove('active'));
            forms.forEach(f => f.classList.remove('active'));
            tab.classList.add('active');
            const targetForm = document.getElementById(target);
            if (targetForm) targetForm.classList.add('active');
        });
    });

    // Check URL hash
    if (window.location.hash === '#register') {
        const regTab = document.querySelector('[data-tab="register-form"]');
        if (regTab) regTab.click();
    }
}

/* ============================================
   Carousel
   ============================================ */
function initCarousel() {
    const track = document.querySelector('.carousel-track');
    const prevBtn = document.querySelector('.carousel-prev');
    const nextBtn = document.querySelector('.carousel-next');
    if (!track || !prevBtn || !nextBtn) return;

    let position = 0;
    const items = track.children;
    const itemWidth = items[0]?.offsetWidth + 24 || 300; // gap included
    const visibleItems = Math.floor(track.parentElement.offsetWidth / itemWidth) || 1;
    const maxPosition = Math.max(0, items.length - visibleItems);

    function updatePosition() {
        track.style.transform = `translateX(-${position * itemWidth}px)`;
        prevBtn.disabled = position === 0;
        nextBtn.disabled = position >= maxPosition;
    }

    prevBtn.addEventListener('click', () => {
        if (position > 0) position--;
        updatePosition();
    });

    nextBtn.addEventListener('click', () => {
        if (position < maxPosition) position++;
        updatePosition();
    });

    updatePosition();
}

/* ============================================
   Multi-Step Booking Form
   ============================================ */
function initBookingForm() {
    const form = document.getElementById('booking-form');
    const steps = document.querySelectorAll('.step-content');
    const indicators = document.querySelectorAll('.step-indicator');
    let currentStep = 0;

    // State
    const bookingState = {
        service_id: null,
        hairdresser_id: null,
        booking_date: null,
        booking_time: null,
        service_duration: 30,
    };

    // Step navigation
    window.nextStep = function() {
        if (!validateStep(currentStep)) return;
        if (currentStep < steps.length - 1) {
            currentStep++;
            showStep(currentStep);
            onStepEnter(currentStep);
        }
    };

    window.prevStep = function() {
        if (currentStep > 0) {
            currentStep--;
            showStep(currentStep);
        }
    };

    function showStep(step) {
        steps.forEach((s, i) => {
            s.classList.toggle('active', i === step);
        });
        indicators.forEach((ind, i) => {
            ind.classList.remove('active', 'completed');
            if (i === step) ind.classList.add('active');
            else if (i < step) ind.classList.add('completed');
        });
    }

    function validateStep(step) {
        switch (step) {
            case 0: // Service
                const serviceSelect = document.getElementById('service-select');
                if (!serviceSelect || !serviceSelect.value) {
                    showStepError('Please select a service.');
                    return false;
                }
                bookingState.service_id = serviceSelect.value;
                // Get duration from selected option
                const selectedOpt = serviceSelect.options[serviceSelect.selectedIndex];
                bookingState.service_duration = parseInt(selectedOpt.dataset.duration) || 30;
                return true;

            case 1: // Hairdresser
                if (!bookingState.hairdresser_id) {
                    showStepError('Please select a hairdresser.');
                    return false;
                }
                return true;

            case 2: // Date
                if (!bookingState.booking_date) {
                    showStepError('Please select a date.');
                    return false;
                }
                return true;

            case 3: // Time
                if (!bookingState.booking_time) {
                    showStepError('Please select a time slot.');
                    return false;
                }
                return true;

            case 4: // User details - handled by form submit
                return true;

            default:
                return true;
        }
    }

    function showStepError(msg) {
        let errDiv = document.getElementById('step-error');
        if (!errDiv) {
            errDiv = document.createElement('div');
            errDiv.id = 'step-error';
            errDiv.className = 'alert alert-error';
            errDiv.setAttribute('role', 'alert');
            const activeStep = document.querySelector('.step-content.active');
            if (activeStep) activeStep.prepend(errDiv);
        }
        errDiv.innerHTML = '<span class="alert-icon">✕</span> ' + msg + '<button class="alert-close" onclick="this.parentElement.remove()">×</button>';
        setTimeout(() => errDiv?.remove(), 4000);
    }

    function onStepEnter(step) {
        if (step === 2) {
            initDatePicker();
        }
        if (step === 3) {
            loadTimeSlots();
        }
    }

    // Hairdresser selection
    document.querySelectorAll('.hairdresser-card').forEach(card => {
        card.addEventListener('click', () => {
            document.querySelectorAll('.hairdresser-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            bookingState.hairdresser_id = card.dataset.id;
            // Reset date and time when hairdresser changes
            bookingState.booking_date = null;
            bookingState.booking_time = null;
        });

        // Keyboard support
        card.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                card.click();
            }
        });
    });

    // Date Picker
    let flatpickrInstance = null;

    function initDatePicker() {
        const dateInput = document.getElementById('booking-date');
        if (!dateInput) return;

        // Fetch available dates
        fetch(`/api/get_dates.php?hairdresser_id=${bookingState.hairdresser_id}`)
            .then(r => r.json())
            .then(data => {
                if (flatpickrInstance) flatpickrInstance.destroy();

                flatpickrInstance = flatpickr(dateInput, {
                    minDate: 'today',
                    maxDate: new Date().fp_incr(60),
                    dateFormat: 'Y-m-d',
                    enable: data.dates || [],
                    disableMobile: false,
                    inline: true,
                    onChange: function(selectedDates, dateStr) {
                        bookingState.booking_date = dateStr;
                        bookingState.booking_time = null; // Reset time
                    }
                });
            })
            .catch(() => {
                showStepError('Failed to load available dates. Please try again.');
            });
    }

    // Time Slots
    function loadTimeSlots() {
        const container = document.getElementById('time-slots-container');
        if (!container) return;

        container.innerHTML = '<div class="loading-overlay"><span class="spinner"></span> Loading available slots...</div>';

        fetch(`/api/get_slots.php?hairdresser_id=${bookingState.hairdresser_id}&date=${bookingState.booking_date}&duration=${bookingState.service_duration}`)
            .then(r => r.json())
            .then(data => {
                if (!data.slots || data.slots.length === 0) {
                    container.innerHTML = '<div class="no-slots-msg">No available slots for this date. Please select another date.</div>';
                    return;
                }

                let html = '<div class="time-slots">';
                data.slots.forEach(slot => {
                    html += `<button type="button" class="time-slot" data-time="${slot}" onclick="selectTimeSlot(this)">${formatTime(slot)}</button>`;
                });
                html += '</div>';
                container.innerHTML = html;
            })
            .catch(() => {
                container.innerHTML = '<div class="no-slots-msg">Failed to load time slots. Please try again.</div>';
            });
    }

    // Select time slot
    window.selectTimeSlot = function(el) {
        document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
        el.classList.add('selected');
        bookingState.booking_time = el.dataset.time;
    };

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // Validate user details
        const name = document.getElementById('user-name');
        const email = document.getElementById('user-email');
        const phone = document.getElementById('user-phone');

        if (!name?.value.trim()) {
            showStepError('Please enter your name.');
            return;
        }
        if (!email?.value.trim() || !isValidEmail(email.value)) {
            showStepError('Please enter a valid email address.');
            return;
        }
        if (phone?.value && !isValidPhone(phone.value)) {
            showStepError('Please enter a valid phone number.');
            return;
        }

        // CSRF token
        const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;

        // Submit
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> Booking...';
        }

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('service_id', bookingState.service_id);
        formData.append('hairdresser_id', bookingState.hairdresser_id);
        formData.append('booking_date', bookingState.booking_date);
        formData.append('booking_time', bookingState.booking_time);
        formData.append('user_name', name.value.trim());
        formData.append('user_email', email.value.trim());
        formData.append('user_phone', phone?.value.trim() || '');
        formData.append('notes', document.getElementById('user-notes')?.value || '');

        fetch('/api/create_booking.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.location.href = '/confirmation.php?id=' + data.booking_id;
            } else {
                showStepError(data.error || 'Booking failed. Please try again.');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '✓ Confirm Booking';
                }
            }
        })
        .catch(() => {
            showStepError('An error occurred. Please try again.');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '✓ Confirm Booking';
            }
        });
    });

    // Init first step
    showStep(0);
}

/* ============================================
   Utility Functions
   ============================================ */
function formatTime(timeStr) {
    const [h, m] = timeStr.split(':');
    const hour = parseInt(h);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${m} ${ampm}`;
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function isValidPhone(phone) {
    return /^[\+]?[0-9\s\-\(\)]{7,20}$/.test(phone);
}

/* ============================================
   Form Validation (generic)
   ============================================ */
document.querySelectorAll('form[data-validate]').forEach(form => {
    form.addEventListener('submit', function(e) {
        let valid = true;
        form.querySelectorAll('[required]').forEach(field => {
            const err = field.parentElement.querySelector('.form-error');
            if (!field.value.trim()) {
                valid = false;
                field.style.borderColor = 'var(--danger)';
                if (err) err.textContent = 'This field is required.';
            } else {
                field.style.borderColor = '';
                if (err) err.textContent = '';
            }
        });

        // Email validation
        form.querySelectorAll('input[type="email"]').forEach(field => {
            if (field.value && !isValidEmail(field.value)) {
                valid = false;
                field.style.borderColor = 'var(--danger)';
                const err = field.parentElement.querySelector('.form-error');
                if (err) err.textContent = 'Please enter a valid email.';
            }
        });

        if (!valid) e.preventDefault();
    });
});

/* ============================================
   Admin: Delete Confirmation
   ============================================ */
window.confirmDelete = function(msg) {
    return confirm(msg || 'Are you sure you want to delete this item?');
};

/* ============================================
   Admin: Modal
   ============================================ */
window.openModal = function(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.add('active');
};

window.closeModal = function(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.remove('active');
};

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
    });
});

// ESC to close modals
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
    }
});

/* ============================================
   Scroll & Entrance Animations
   ============================================ */
function initAnimations() {
    const animatedElements = document.querySelectorAll('.fade-in, .fade-in-left, .fade-in-right, .scale-in');

    if (!animatedElements.length) return;

    // For admin pages: trigger animations immediately with stagger
    if (document.body.classList.contains('admin-page')) {
        animatedElements.forEach((el, index) => {
            setTimeout(() => {
                el.classList.add('visible');
            }, 60 + index * 70);
        });
        return;
    }

    // For public pages: use IntersectionObserver
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -40px 0px'
        });

        animatedElements.forEach(el => observer.observe(el));
    } else {
        // Fallback: just show everything
        animatedElements.forEach(el => el.classList.add('visible'));
    }
}
