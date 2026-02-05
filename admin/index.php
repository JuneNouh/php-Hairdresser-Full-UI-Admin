<?php
/**
 * Hairdresser Pro - Admin Dashboard
 */
require_once __DIR__ . '/../functions.php';
require_admin();

$section = $_GET['section'] ?? 'dashboard';
define('PAGE_TITLE', 'Admin Dashboard - ' . SITE_NAME);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid security token.');
        redirect('admin/index.php?section=' . $section);
    }

    $action = $_POST['action'] ?? '';

    try {
        $db = get_db();

        // ===== SERVICE ACTIONS =====
        if ($action === 'add_service') {
            $stmt = $db->prepare('INSERT INTO services (name, description, duration, price, icon) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([
                trim($_POST['name']),
                trim($_POST['description'] ?? ''),
                (int)$_POST['duration'],
                (float)$_POST['price'],
                trim($_POST['icon'] ?? 'scissors')
            ]);
            set_flash('success', 'Service added successfully.');
        } elseif ($action === 'update_service') {
            $stmt = $db->prepare('UPDATE services SET name = ?, description = ?, duration = ?, price = ?, icon = ? WHERE id = ?');
            $stmt->execute([
                trim($_POST['name']),
                trim($_POST['description'] ?? ''),
                (int)$_POST['duration'],
                (float)$_POST['price'],
                trim($_POST['icon'] ?? 'scissors'),
                (int)$_POST['id']
            ]);
            set_flash('success', 'Service updated.');
        } elseif ($action === 'delete_service') {
            $stmt = $db->prepare('DELETE FROM services WHERE id = ?');
            $stmt->execute([(int)$_POST['id']]);
            set_flash('success', 'Service deleted.');
        }

        // ===== HAIRDRESSER ACTIONS =====
        elseif ($action === 'add_hairdresser') {
            $photo = 'images/default-avatar.png';
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $photo = handleUpload($_FILES['photo']);
            }
            $stmt = $db->prepare('INSERT INTO hairdressers (name, bio, photo_url, specialty) VALUES (?, ?, ?, ?)');
            $stmt->execute([
                trim($_POST['name']),
                trim($_POST['bio'] ?? ''),
                $photo,
                trim($_POST['specialty'] ?? '')
            ]);
            set_flash('success', 'Hairdresser added.');
        } elseif ($action === 'update_hairdresser') {
            $fields = 'name = ?, bio = ?, specialty = ?';
            $params = [trim($_POST['name']), trim($_POST['bio'] ?? ''), trim($_POST['specialty'] ?? '')];

            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $photo = handleUpload($_FILES['photo']);
                $fields .= ', photo_url = ?';
                $params[] = $photo;
            }

            $params[] = (int)$_POST['id'];
            $stmt = $db->prepare("UPDATE hairdressers SET {$fields} WHERE id = ?");
            $stmt->execute($params);
            set_flash('success', 'Hairdresser updated.');
        } elseif ($action === 'delete_hairdresser') {
            $stmt = $db->prepare('DELETE FROM hairdressers WHERE id = ?');
            $stmt->execute([(int)$_POST['id']]);
            set_flash('success', 'Hairdresser deleted.');
        }

        // ===== BOOKING ACTIONS =====
        elseif ($action === 'update_booking_status') {
            $status = $_POST['status'] ?? '';
            if (in_array($status, ['pending', 'confirmed', 'cancelled'])) {
                $stmt = $db->prepare('UPDATE bookings SET status = ? WHERE id = ?');
                $stmt->execute([$status, (int)$_POST['id']]);
                set_flash('success', 'Booking status updated.');
            }
        } elseif ($action === 'delete_booking') {
            $stmt = $db->prepare('DELETE FROM bookings WHERE id = ?');
            $stmt->execute([(int)$_POST['id']]);
            set_flash('success', 'Booking deleted.');
        }

        // ===== AVAILABILITY ACTIONS =====
        elseif ($action === 'add_availability') {
            $stmt = $db->prepare('INSERT INTO availability (hairdresser_id, day_of_week, start_time, end_time, is_holiday, holiday_date) VALUES (?, ?, ?, ?, ?, ?)');
            $isHoliday = isset($_POST['is_holiday']) ? 1 : 0;
            $stmt->execute([
                (int)$_POST['hairdresser_id'],
                (int)$_POST['day_of_week'],
                $_POST['start_time'],
                $_POST['end_time'],
                $isHoliday,
                $isHoliday ? ($_POST['holiday_date'] ?? null) : null
            ]);
            set_flash('success', 'Availability added.');
        } elseif ($action === 'delete_availability') {
            $stmt = $db->prepare('DELETE FROM availability WHERE id = ?');
            $stmt->execute([(int)$_POST['id']]);
            set_flash('success', 'Availability removed.');
        }

    } catch (PDOException $e) {
        error_log('Admin action error: ' . $e->getMessage());
        set_flash('error', 'Database error: ' . $e->getMessage());
    }

    redirect('admin/index.php?section=' . $section);
}

/**
 * Handle file upload
 */
function handleUpload(array $file): string {
    $uploadDir = __DIR__ . '/../images/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($ext, $allowed)) {
        return 'images/default-avatar.png';
    }

    if ($file['size'] > 5 * 1024 * 1024) { // 5MB max
        return 'images/default-avatar.png';
    }

    $filename = 'upload_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destination = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return 'images/uploads/' . $filename;
    }

    return 'images/default-avatar.png';
}

// Fetch data
$db = get_db();
$stats = get_admin_stats();
$allServices = $db->query('SELECT * FROM services ORDER BY name')->fetchAll();
$allHairdressers = $db->query('SELECT * FROM hairdressers ORDER BY name')->fetchAll();

// Bookings with filters
$bookingFilter = '';
$bookingParams = [];
$filterStatus = $_GET['status'] ?? '';
$filterDate = $_GET['date'] ?? '';

if ($filterStatus && in_array($filterStatus, ['pending', 'confirmed', 'cancelled'])) {
    $bookingFilter .= ' AND b.status = ?';
    $bookingParams[] = $filterStatus;
}
if ($filterDate) {
    $bookingFilter .= ' AND b.booking_date = ?';
    $bookingParams[] = $filterDate;
}

$bookingsPage = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($bookingsPage - 1) * $perPage;

$countStmt = $db->prepare("SELECT COUNT(*) FROM bookings b WHERE 1=1 {$bookingFilter}");
$countStmt->execute($bookingParams);
$totalBookings = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalBookings / $perPage));

$stmt = $db->prepare("SELECT b.*, s.name as service_name, s.price, h.name as hairdresser_name FROM bookings b JOIN services s ON b.service_id = s.id JOIN hairdressers h ON b.hairdresser_id = h.id WHERE 1=1 {$bookingFilter} ORDER BY b.created_at DESC LIMIT {$perPage} OFFSET {$offset}");
$stmt->execute($bookingParams);
$allBookings = $stmt->fetchAll();

// Availability
$allAvailability = $db->query('SELECT a.*, h.name as hairdresser_name FROM availability a JOIN hairdressers h ON a.hairdresser_id = h.id ORDER BY h.name, a.day_of_week')->fetchAll();

// Messages
$messages = $db->query('SELECT * FROM contacts ORDER BY created_at DESC LIMIT 20')->fetchAll();

include __DIR__ . '/../includes/header.php';

$dayNames = ['', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
?>

<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar" role="navigation" aria-label="Admin navigation">
        <div class="admin-sidebar-brand">
            <span class="brand-icon">✂️</span>
            <span class="brand-text"><?= SITE_NAME ?></span>
        </div>
        <div class="admin-sidebar-nav">
            <a href="?section=dashboard" class="admin-sidebar-link <?= $section === 'dashboard' ? 'active' : '' ?>">
                <span class="sidebar-icon">📊</span>
                <span class="sidebar-text">Dashboard</span>
            </a>
            <a href="?section=services" class="admin-sidebar-link <?= $section === 'services' ? 'active' : '' ?>">
                <span class="sidebar-icon">✂️</span>
                <span class="sidebar-text">Services</span>
            </a>
            <a href="?section=hairdressers" class="admin-sidebar-link <?= $section === 'hairdressers' ? 'active' : '' ?>">
                <span class="sidebar-icon">👤</span>
                <span class="sidebar-text">Hairdressers</span>
            </a>
            <a href="?section=bookings" class="admin-sidebar-link <?= $section === 'bookings' ? 'active' : '' ?>">
                <span class="sidebar-icon">📅</span>
                <span class="sidebar-text">Bookings</span>
            </a>
            <a href="?section=availability" class="admin-sidebar-link <?= $section === 'availability' ? 'active' : '' ?>">
                <span class="sidebar-icon">🕐</span>
                <span class="sidebar-text">Availability</span>
            </a>
            <a href="?section=messages" class="admin-sidebar-link <?= $section === 'messages' ? 'active' : '' ?>">
                <span class="sidebar-icon">✉️</span>
                <span class="sidebar-text">Messages</span>
            </a>
        </div>
        <div class="admin-sidebar-footer">
            <a href="/index.php" class="admin-sidebar-link">
                <span class="sidebar-icon">🏠</span>
                <span class="sidebar-text">Back to Site</span>
            </a>
            <a href="/auth.php?action=logout" class="admin-sidebar-link">
                <span class="sidebar-icon">🚪</span>
                <span class="sidebar-text">Logout</span>
            </a>
            <button class="theme-toggle admin-theme-toggle" id="theme-toggle" aria-label="Toggle dark/light mode">
                ✦ <span>Light Mode</span>
            </button>
        </div>
    </aside>

    <!-- Sidebar overlay for mobile -->
    <div class="admin-sidebar-overlay" id="admin-sidebar-overlay"></div>

    <!-- Main Content -->
    <div class="admin-main">
        <div class="admin-topbar">
            <div class="admin-topbar-left">
                <button class="admin-sidebar-toggle" id="admin-sidebar-toggle" aria-label="Toggle sidebar">
                    ☰
                </button>
                <h1 class="admin-page-title"><?= ucfirst($section) ?></h1>
            </div>
            <div class="admin-topbar-right">
                <span class="admin-user">👋 <?= h($_SESSION['username']) ?></span>
            </div>
        </div>
        <div class="admin-content">
            <?= display_flash() ?>

        <?php if ($section === 'dashboard'): ?>
        <!-- ===== DASHBOARD ===== -->
        <div class="admin-header fade-in">
            <h2>Dashboard</h2>
            <span class="tag">Welcome, <?= h($_SESSION['username']) ?></span>
        </div>

        <div class="stats-grid stagger-children">
            <div class="stat-card scale-in">
                <div class="stat-number"><?= $stats['total_bookings'] ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card scale-in">
                <div class="stat-number"><?= $stats['pending_bookings'] ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card scale-in">
                <div class="stat-number"><?= $stats['confirmed_bookings'] ?></div>
                <div class="stat-label">Confirmed</div>
            </div>
            <div class="stat-card scale-in">
                <div class="stat-number"><?= format_price($stats['total_revenue']) ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-card scale-in">
                <div class="stat-number"><?= $stats['today_bookings'] ?></div>
                <div class="stat-label">Today's Bookings</div>
            </div>
            <div class="stat-card scale-in">
                <div class="stat-number"><?= $stats['total_users'] ?></div>
                <div class="stat-label">Registered Users</div>
            </div>
        </div>

        <!-- Revenue per service -->
        <?php if (!empty($stats['revenue_per_service'])): ?>
        <div class="card fade-in" style="margin-top: 1.5rem;">
            <div class="card-body">
                <h3 style="margin-bottom: 1rem;">Revenue by Service</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr><th>Service</th><th>Bookings</th><th>Revenue</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['revenue_per_service'] as $r): ?>
                            <tr>
                                <td><?= h($r['name']) ?></td>
                                <td><?= (int)$r['count'] ?></td>
                                <td class="text-accent"><?= format_price($r['revenue']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php elseif ($section === 'services'): ?>
        <!-- ===== SERVICES MANAGEMENT ===== -->
        <div class="admin-header fade-in">
            <h2>Manage Services</h2>
            <button class="btn btn-primary" onclick="openModal('add-service-modal')">+ Add Service</button>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr><th>ID</th><th>Name</th><th>Duration</th><th>Price</th><th>Icon</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($allServices as $s): ?>
                    <tr>
                        <td><?= (int)$s['id'] ?></td>
                        <td><strong><?= h($s['name']) ?></strong><br><small style="color: var(--text-muted);"><?= h(mb_strimwidth($s['description'], 0, 60, '...')) ?></small></td>
                        <td><?= (int)$s['duration'] ?> min</td>
                        <td class="text-accent"><?= format_price($s['price']) ?></td>
                        <td><?= h($s['icon']) ?></td>
                        <td>
                            <button class="btn btn-sm btn-secondary" onclick="editService(<?= (int)$s['id'] ?>, '<?= h(addslashes($s['name'])) ?>', '<?= h(addslashes($s['description'])) ?>', <?= (int)$s['duration'] ?>, <?= $s['price'] ?>, '<?= h($s['icon']) ?>')">Edit</button>
                            <form method="post" style="display: inline;" onsubmit="return confirmDelete('Delete this service?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_service">
                                <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Add Service Modal -->
        <div class="modal-overlay" id="add-service-modal">
            <div class="modal">
                <div class="modal-header">
                    <h3>Add Service</h3>
                    <button class="modal-close" onclick="closeModal('add-service-modal')">&times;</button>
                </div>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_service">
                    <div class="form-group">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Duration (min) *</label>
                            <input type="number" name="duration" class="form-control" required min="10" value="30">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Price ($) *</label>
                            <input type="number" name="price" class="form-control" required min="0" step="0.01" value="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Icon</label>
                        <select name="icon" class="form-control">
                            <option value="scissors">✂️ Scissors</option>
                            <option value="palette">🎨 Palette</option>
                            <option value="wind">💇 Blow Dry</option>
                            <option value="spa">💆 Spa</option>
                            <option value="cut">✂️ Cut</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Add Service</button>
                </form>
            </div>
        </div>

        <!-- Edit Service Modal -->
        <div class="modal-overlay" id="edit-service-modal">
            <div class="modal">
                <div class="modal-header">
                    <h3>Edit Service</h3>
                    <button class="modal-close" onclick="closeModal('edit-service-modal')">&times;</button>
                </div>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_service">
                    <input type="hidden" name="id" id="edit-service-id">
                    <div class="form-group">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" id="edit-service-name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="edit-service-desc" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Duration (min) *</label>
                            <input type="number" name="duration" id="edit-service-duration" class="form-control" required min="10">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Price ($) *</label>
                            <input type="number" name="price" id="edit-service-price" class="form-control" required min="0" step="0.01">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Icon</label>
                        <select name="icon" id="edit-service-icon" class="form-control">
                            <option value="scissors">✂️ Scissors</option>
                            <option value="palette">🎨 Palette</option>
                            <option value="wind">💇 Blow Dry</option>
                            <option value="spa">💆 Spa</option>
                            <option value="cut">✂️ Cut</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Update Service</button>
                </form>
            </div>
        </div>

        <script>
        function editService(id, name, desc, duration, price, icon) {
            document.getElementById('edit-service-id').value = id;
            document.getElementById('edit-service-name').value = name;
            document.getElementById('edit-service-desc').value = desc;
            document.getElementById('edit-service-duration').value = duration;
            document.getElementById('edit-service-price').value = price;
            document.getElementById('edit-service-icon').value = icon;
            openModal('edit-service-modal');
        }
        </script>

        <?php elseif ($section === 'hairdressers'): ?>
        <!-- ===== HAIRDRESSERS MANAGEMENT ===== -->
        <div class="admin-header fade-in">
            <h2>Manage Hairdressers</h2>
            <button class="btn btn-primary" onclick="openModal('add-hd-modal')">+ Add Hairdresser</button>
        </div>

        <div class="grid grid-3">
            <?php foreach ($allHairdressers as $hd): ?>
            <div class="card">
                <div style="padding: 1.5rem; text-align: center;">
                    <img src="/<?= h($hd['photo_url']) ?>" alt="<?= h($hd['name']) ?>" style="width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 1rem; object-fit: cover; background: var(--bg-input);" onerror="this.outerHTML='<div style=\'width:80px;height:80px;border-radius:50%;background:var(--accent-gradient);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;color:#fff;font-size:1.8rem;font-weight:700;\'><?= substr(h($hd["name"]), 0, 1) ?></div>'">
                    <h4><?= h($hd['name']) ?></h4>
                    <p class="text-accent" style="font-size: 0.85rem;"><?= h($hd['specialty']) ?></p>
                    <p style="font-size: 0.85rem; color: var(--text-muted);"><?= h(mb_strimwidth($hd['bio'], 0, 80, '...')) ?></p>
                    <span class="badge <?= $hd['active'] ? 'badge-success' : 'badge-danger' ?>"><?= $hd['active'] ? 'Active' : 'Inactive' ?></span>
                </div>
                <div class="card-footer">
                    <button class="btn btn-sm btn-secondary" onclick="editHD(<?= (int)$hd['id'] ?>, '<?= h(addslashes($hd['name'])) ?>', '<?= h(addslashes($hd['bio'])) ?>', '<?= h(addslashes($hd['specialty'])) ?>')">Edit</button>
                    <form method="post" style="display: inline;" onsubmit="return confirmDelete('Delete this hairdresser?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete_hairdresser">
                        <input type="hidden" name="id" value="<?= (int)$hd['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Add Hairdresser Modal -->
        <div class="modal-overlay" id="add-hd-modal">
            <div class="modal">
                <div class="modal-header">
                    <h3>Add Hairdresser</h3>
                    <button class="modal-close" onclick="closeModal('add-hd-modal')">&times;</button>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_hairdresser">
                    <div class="form-group">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Specialty</label>
                        <input type="text" name="specialty" class="form-control" placeholder="e.g., Coloring & Cuts">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bio</label>
                        <textarea name="bio" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Photo</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Add Hairdresser</button>
                </form>
            </div>
        </div>

        <!-- Edit Hairdresser Modal -->
        <div class="modal-overlay" id="edit-hd-modal">
            <div class="modal">
                <div class="modal-header">
                    <h3>Edit Hairdresser</h3>
                    <button class="modal-close" onclick="closeModal('edit-hd-modal')">&times;</button>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_hairdresser">
                    <input type="hidden" name="id" id="edit-hd-id">
                    <div class="form-group">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" id="edit-hd-name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Specialty</label>
                        <input type="text" name="specialty" id="edit-hd-specialty" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bio</label>
                        <textarea name="bio" id="edit-hd-bio" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Photo (leave empty to keep current)</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Update Hairdresser</button>
                </form>
            </div>
        </div>

        <script>
        function editHD(id, name, bio, specialty) {
            document.getElementById('edit-hd-id').value = id;
            document.getElementById('edit-hd-name').value = name;
            document.getElementById('edit-hd-bio').value = bio;
            document.getElementById('edit-hd-specialty').value = specialty;
            openModal('edit-hd-modal');
        }
        </script>

        <?php elseif ($section === 'bookings'): ?>
        <!-- ===== BOOKINGS MANAGEMENT ===== -->
        <div class="admin-header fade-in">
            <h2>All Bookings</h2>
            <span class="tag"><?= $totalBookings ?> total</span>
        </div>

        <!-- Filters -->
        <form method="get" class="filter-bar">
            <input type="hidden" name="section" value="bookings">
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="confirmed" <?= $filterStatus === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                    <option value="cancelled" <?= $filterStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control" value="<?= h($filterDate) ?>" onchange="this.form.submit()">
            </div>
            <?php if ($filterStatus || $filterDate): ?>
                <div class="form-group">
                    <a href="?section=bookings" class="btn btn-secondary btn-sm">Clear Filters</a>
                </div>
            <?php endif; ?>
        </form>

        <?php if (empty($allBookings)): ?>
            <div style="text-align: center; padding: 2rem; color: var(--text-muted);">No bookings found.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th><th>Client</th><th>Service</th><th>Stylist</th>
                        <th>Date/Time</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allBookings as $b): ?>
                    <tr>
                        <td><?= (int)$b['id'] ?></td>
                        <td>
                            <strong><?= h($b['user_name']) ?></strong><br>
                            <small style="color: var(--text-muted);"><?= h($b['user_email']) ?></small>
                        </td>
                        <td><?= h($b['service_name']) ?></td>
                        <td><?= h($b['hairdresser_name']) ?></td>
                        <td><?= h($b['booking_date']) ?><br><small><?= format_time($b['booking_time']) ?></small></td>
                        <td><?= status_badge($b['status']) ?></td>
                        <td>
                            <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                <?php if ($b['status'] !== 'confirmed'): ?>
                                <form method="post" style="display: inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update_booking_status">
                                    <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                    <input type="hidden" name="status" value="confirmed">
                                    <button type="submit" class="btn btn-sm btn-success" title="Confirm">✓</button>
                                </form>
                                <?php endif; ?>
                                <?php if ($b['status'] !== 'cancelled'): ?>
                                <form method="post" style="display: inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update_booking_status">
                                    <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                    <input type="hidden" name="status" value="cancelled">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Cancel">✕</button>
                                </form>
                                <?php endif; ?>
                                <form method="post" style="display: inline;" onsubmit="return confirmDelete('Delete this booking permanently?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_booking">
                                    <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-secondary" title="Delete">🗑</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        // Pagination
        if ($totalPages > 1) {
            $baseUrl = "admin/index.php?section=bookings";
            if ($filterStatus) $baseUrl .= "&status=" . urlencode($filterStatus);
            if ($filterDate) $baseUrl .= "&date=" . urlencode($filterDate);
            echo pagination_html($bookingsPage, $totalPages, $baseUrl);
        }
        endif; ?>

        <?php elseif ($section === 'availability'): ?>
        <!-- ===== AVAILABILITY MANAGEMENT ===== -->
        <div class="admin-header fade-in">
            <h2>Manage Availability</h2>
            <button class="btn btn-primary" onclick="openModal('add-avail-modal')">+ Add Slot / Holiday</button>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr><th>Hairdresser</th><th>Day</th><th>Start</th><th>End</th><th>Type</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($allAvailability as $a): ?>
                    <tr>
                        <td><?= h($a['hairdresser_name']) ?></td>
                        <td><?= $a['is_holiday'] ? h($a['holiday_date'] ?? 'N/A') : ($dayNames[(int)$a['day_of_week']] ?? 'Unknown') ?></td>
                        <td><?= h($a['start_time']) ?></td>
                        <td><?= h($a['end_time']) ?></td>
                        <td><?= $a['is_holiday'] ? '<span class="badge badge-danger">Holiday</span>' : '<span class="badge badge-success">Working</span>' ?></td>
                        <td>
                            <form method="post" style="display: inline;" onsubmit="return confirmDelete('Remove this availability slot?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_availability">
                                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Add Availability Modal -->
        <div class="modal-overlay" id="add-avail-modal">
            <div class="modal">
                <div class="modal-header">
                    <h3>Add Availability / Holiday</h3>
                    <button class="modal-close" onclick="closeModal('add-avail-modal')">&times;</button>
                </div>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_availability">
                    <div class="form-group">
                        <label class="form-label">Hairdresser *</label>
                        <select name="hairdresser_id" class="form-control" required>
                            <?php foreach ($allHairdressers as $hd): ?>
                            <option value="<?= (int)$hd['id'] ?>"><?= h($hd['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="is_holiday" value="1" id="avail-holiday-check"> This is a holiday/day off
                        </label>
                    </div>
                    <div class="form-group" id="avail-day-group">
                        <label class="form-label">Day of Week *</label>
                        <select name="day_of_week" class="form-control">
                            <option value="1">Monday</option>
                            <option value="2">Tuesday</option>
                            <option value="3">Wednesday</option>
                            <option value="4">Thursday</option>
                            <option value="5">Friday</option>
                            <option value="6">Saturday</option>
                            <option value="7">Sunday</option>
                        </select>
                    </div>
                    <div class="form-group" id="avail-date-group" style="display: none;">
                        <label class="form-label">Holiday Date</label>
                        <input type="date" name="holiday_date" class="form-control">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Start Time</label>
                            <input type="time" name="start_time" class="form-control" value="09:00">
                        </div>
                        <div class="form-group">
                            <label class="form-label">End Time</label>
                            <input type="time" name="end_time" class="form-control" value="18:00">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Add</button>
                </form>
            </div>
        </div>

        <script>
        document.getElementById('avail-holiday-check')?.addEventListener('change', function() {
            document.getElementById('avail-day-group').style.display = this.checked ? 'none' : '';
            document.getElementById('avail-date-group').style.display = this.checked ? '' : 'none';
        });
        </script>

        <?php elseif ($section === 'messages'): ?>
        <!-- ===== MESSAGES ===== -->
        <div class="admin-header fade-in">
            <h2>Contact Messages</h2>
            <span class="tag"><?= count($messages) ?> messages</span>
        </div>

        <?php if (empty($messages)): ?>
            <div style="text-align: center; padding: 2rem; color: var(--text-muted);">No messages yet.</div>
        <?php else: ?>
        <div style="display: grid; gap: 1rem;">
            <?php foreach ($messages as $msg): ?>
            <div class="card">
                <div class="card-body">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                        <div>
                            <strong><?= h($msg['name']) ?></strong>
                            <span style="color: var(--text-muted); font-size: 0.85rem;"> — <?= h($msg['email']) ?></span>
                        </div>
                        <small style="color: var(--text-muted);"><?= h($msg['created_at']) ?></small>
                    </div>
                    <p style="margin: 0;"><?= nl2br(h($msg['message'])) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php endif; ?>
        </div><!-- /.admin-content -->
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
