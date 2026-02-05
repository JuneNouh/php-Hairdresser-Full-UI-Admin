<?php
/**
 * Hairdresser Pro - Configuration & Database Setup
 * Handles SQLite connection, migrations, and seeding.
 */

// Error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Site configuration
define('SITE_NAME', 'Hairdresser Pro');
define('SITE_URL', 'http://localhost:8000');
define('DB_PATH', __DIR__ . '/hairdresser.db');
define('UPLOAD_DIR', __DIR__ . '/images/uploads/');

// CSRF Token generation
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generate_csrf_token()) . '">';
}

/**
 * Get PDO database connection (singleton pattern)
 */
function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $isNew = !file_exists(DB_PATH);
        try {
            $pdo = new PDO('sqlite:' . DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('PRAGMA journal_mode=WAL');
            $pdo->exec('PRAGMA foreign_keys=ON');

            if ($isNew) {
                run_migrations($pdo);
                seed_data($pdo);
            } else {
                // Ensure tables exist even if DB file existed but was empty
                $check = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='services'")->fetch();
                if (!$check) {
                    run_migrations($pdo);
                    seed_data($pdo);
                }
            }
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            die('Database connection failed. Please check error logs.');
        }
    }
    return $pdo;
}

/**
 * Run database migrations
 */
function run_migrations(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            role TEXT NOT NULL DEFAULT 'user',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS services (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT DEFAULT '',
            duration INTEGER NOT NULL DEFAULT 30,
            price REAL NOT NULL DEFAULT 0.00,
            icon TEXT DEFAULT 'scissors',
            image_url TEXT DEFAULT '',
            active INTEGER DEFAULT 1
        );

        CREATE TABLE IF NOT EXISTS hairdressers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            bio TEXT DEFAULT '',
            photo_url TEXT DEFAULT 'images/default-avatar.png',
            specialty TEXT DEFAULT '',
            active INTEGER DEFAULT 1
        );

        CREATE TABLE IF NOT EXISTS availability (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            hairdresser_id INTEGER NOT NULL,
            day_of_week INTEGER NOT NULL,
            start_time TEXT NOT NULL DEFAULT '09:00',
            end_time TEXT NOT NULL DEFAULT '18:00',
            is_holiday INTEGER DEFAULT 0,
            holiday_date TEXT DEFAULT NULL,
            FOREIGN KEY (hairdresser_id) REFERENCES hairdressers(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS bookings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER DEFAULT NULL,
            user_name TEXT NOT NULL,
            user_email TEXT NOT NULL,
            user_phone TEXT NOT NULL DEFAULT '',
            service_id INTEGER NOT NULL,
            hairdresser_id INTEGER NOT NULL,
            booking_date TEXT NOT NULL,
            booking_time TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'pending',
            notes TEXT DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
            FOREIGN KEY (hairdresser_id) REFERENCES hairdressers(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS contacts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            message TEXT NOT NULL,
            is_read INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE INDEX IF NOT EXISTS idx_bookings_date ON bookings(booking_date);
        CREATE INDEX IF NOT EXISTS idx_bookings_hairdresser ON bookings(hairdresser_id);
        CREATE INDEX IF NOT EXISTS idx_bookings_status ON bookings(status);
        CREATE INDEX IF NOT EXISTS idx_availability_hairdresser ON availability(hairdresser_id);
    ");
}

/**
 * Seed initial data
 */
function seed_data(PDO $pdo): void {
    // Admin user (password: admin123)
    $adminPass = password_hash('admin123', PASSWORD_BCRYPT);
    $userPass = password_hash('user123', PASSWORD_BCRYPT);

    $pdo->exec("
        INSERT INTO users (username, password, email, role) VALUES
        ('admin', '{$adminPass}', 'admin@hairdresserpro.com', 'admin'),
        ('johndoe', '{$userPass}', 'john@example.com', 'user');
    ");

    // Services
    $pdo->exec("
        INSERT INTO services (name, description, duration, price, icon, image_url) VALUES
        ('Classic Haircut', 'A professional haircut tailored to your style. Includes wash and blow-dry.', 30, 35.00, 'scissors', 'images/service-haircut.png'),
        ('Hair Coloring', 'Full color treatment with premium products. Includes consultation.', 90, 85.00, 'palette', 'images/service-coloring.png'),
        ('Blow Dry & Styling', 'Professional blow-dry and styling for any occasion.', 45, 45.00, 'wind', 'images/service-blowdry.png'),
        ('Hair Treatment', 'Deep conditioning and repair treatment for damaged hair.', 60, 55.00, 'spa', 'images/service-treatment.png'),
        ('Beard Trim', 'Professional beard trimming and shaping.', 20, 20.00, 'cut', 'images/service-beard.png');
    ");

    // Hairdressers
    $pdo->exec("
        INSERT INTO hairdressers (name, bio, photo_url, specialty) VALUES
        ('Sophie Martin', 'With over 15 years of experience, Sophie specializes in modern cuts and creative coloring. She has trained in Paris and Milan.', 'images/hairdresser-1.png', 'Coloring & Cuts'),
        ('James Wilson', 'James brings 10 years of barbering excellence. Known for precise fades and classic gentleman cuts.', 'images/hairdresser-2.png', 'Barbering & Styling'),
        ('Elena Rodriguez', 'Elena is our treatment specialist with expertise in hair restoration and bridal styling. Certified trichologist.', 'images/hairdresser-3.png', 'Treatments & Bridal');
    ");

    // Availability (Mon=1 to Fri=5, 9AM-6PM for all hairdressers)
    for ($h = 1; $h <= 3; $h++) {
        for ($d = 1; $d <= 5; $d++) {
            $pdo->exec("INSERT INTO availability (hairdresser_id, day_of_week, start_time, end_time) VALUES ({$h}, {$d}, '09:00', '18:00')");
        }
        // Saturday half day
        $pdo->exec("INSERT INTO availability (hairdresser_id, day_of_week, start_time, end_time) VALUES ({$h}, 6, '10:00', '14:00')");
    }
}
