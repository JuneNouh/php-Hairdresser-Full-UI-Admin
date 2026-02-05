<?php
/**
 * Hairdresser Pro - Login / Register Page
 */
require_once __DIR__ . '/functions.php';
define('PAGE_TITLE', 'Login / Register - ' . SITE_NAME);

// Handle logout
if (($_GET['action'] ?? '') === 'logout') {
    session_destroy();
    session_start();
    set_flash('success', 'You have been logged out.');
    redirect('index.php');
}

// Redirect if already logged in
if (is_logged_in() && !isset($_GET['action'])) {
    redirect('index.php');
}

$loginErrors = [];
$regErrors = [];
$activeTab = 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $loginErrors[] = 'Invalid security token. Please try again.';
    } else {
        $action = $_POST['form_action'] ?? '';

        if ($action === 'login') {
            $username = trim($_POST['login_username'] ?? '');
            $password = $_POST['login_password'] ?? '';

            if ($username === '') $loginErrors[] = 'Username is required.';
            if ($password === '') $loginErrors[] = 'Password is required.';

            if (empty($loginErrors)) {
                try {
                    $db = get_db();
                    $stmt = $db->prepare('SELECT * FROM users WHERE username = ? OR email = ?');
                    $stmt->execute([$username, $username]);
                    $user = $stmt->fetch();

                    if ($user && password_verify($password, $user['password'])) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['user_role'] = $user['role'];
                        set_flash('success', 'Welcome back, ' . $user['username'] . '!');
                        redirect('index.php');
                    } else {
                        $loginErrors[] = 'Invalid username or password.';
                    }
                } catch (PDOException $e) {
                    error_log('Login error: ' . $e->getMessage());
                    $loginErrors[] = 'An error occurred. Please try again.';
                }
            }
        } elseif ($action === 'register') {
            $activeTab = 'register';
            $username = trim($_POST['reg_username'] ?? '');
            $email = trim($_POST['reg_email'] ?? '');
            $password = $_POST['reg_password'] ?? '';
            $password2 = $_POST['reg_password2'] ?? '';

            if ($username === '') $regErrors[] = 'Username is required.';
            if (strlen($username) < 3) $regErrors[] = 'Username must be at least 3 characters.';
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) $regErrors[] = 'Username can only contain letters, numbers, and underscores.';
            if (!is_valid_email($email)) $regErrors[] = 'Valid email is required.';
            if (strlen($password) < 6) $regErrors[] = 'Password must be at least 6 characters.';
            if ($password !== $password2) $regErrors[] = 'Passwords do not match.';

            if (empty($regErrors)) {
                try {
                    $db = get_db();

                    // Check if username exists
                    $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
                    $stmt->execute([$username]);
                    if ($stmt->fetch()) {
                        $regErrors[] = 'Username already taken.';
                    }

                    // Check if email exists
                    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $regErrors[] = 'Email already registered.';
                    }

                    if (empty($regErrors)) {
                        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                        $stmt = $db->prepare('INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)');
                        $stmt->execute([$username, $hashedPassword, $email, 'user']);

                        $userId = (int)$db->lastInsertId();
                        $_SESSION['user_id'] = $userId;
                        $_SESSION['username'] = $username;
                        $_SESSION['user_role'] = 'user';

                        set_flash('success', 'Account created successfully! Welcome, ' . $username . '!');
                        redirect('index.php');
                    }
                } catch (PDOException $e) {
                    error_log('Registration error: ' . $e->getMessage());
                    $regErrors[] = 'An error occurred. Please try again.';
                }
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="auth-container">
        <!-- Auth Hero Image -->
        <div style="border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 2rem; height: 160px; position: relative;">
            <img src="https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=800&q=80" alt="Welcome" style="width:100%;height:100%;object-fit:cover;" loading="lazy">
            <div style="position:absolute;inset:0;background:linear-gradient(to top, rgba(11,11,11,0.95), rgba(11,11,11,0.3));display:flex;align-items:flex-end;padding:1.5rem;">
                <div>
                    <h1 style="color:#f5f0e8;font-size:1.8rem;margin-bottom:0.25rem;font-family:'Raleway',sans-serif;letter-spacing:0.04em;">Welcome</h1>
                    <p style="color:rgba(212,168,83,0.7);font-size:0.9rem;margin:0;">Sign in or create a new account</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body" style="padding: 2rem;">
                <!-- Tabs -->
                <div class="auth-tabs">
                    <button class="auth-tab <?= $activeTab === 'login' ? 'active' : '' ?>" data-tab="login-form" type="button">Login</button>
                    <button class="auth-tab <?= $activeTab === 'register' ? 'active' : '' ?>" data-tab="register-form" type="button">Register</button>
                </div>

                <!-- Login Form -->
                <div id="login-form" class="auth-form <?= $activeTab === 'login' ? 'active' : '' ?>">
                    <?php foreach ($loginErrors as $err): ?>
                        <div class="alert alert-error" role="alert">
                            <span class="alert-icon">✕</span> <?= h($err) ?>
                        </div>
                    <?php endforeach; ?>

                    <form method="post" action="/auth.php" data-validate>
                        <?= csrf_field() ?>
                        <input type="hidden" name="form_action" value="login">
                        <div class="form-group">
                            <label for="login_username" class="form-label">Username or Email</label>
                            <input type="text" id="login_username" name="login_username" class="form-control" required
                                   placeholder="Enter username or email" value="<?= h($username ?? '') ?>" aria-required="true">
                            <div class="form-error"></div>
                        </div>
                        <div class="form-group">
                            <label for="login_password" class="form-label">Password</label>
                            <input type="password" id="login_password" name="login_password" class="form-control" required
                                   placeholder="Enter password" aria-required="true">
                            <div class="form-error"></div>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">Login →</button>
                    </form>

                    <div style="text-align: center; margin-top: 1rem; font-size: 0.85rem; color: var(--text-muted);">
                        Demo: admin / admin123 &nbsp;|&nbsp; johndoe / user123
                    </div>
                </div>

                <!-- Register Form -->
                <div id="register-form" class="auth-form <?= $activeTab === 'register' ? 'active' : '' ?>">
                    <?php foreach ($regErrors as $err): ?>
                        <div class="alert alert-error" role="alert">
                            <span class="alert-icon">✕</span> <?= h($err) ?>
                        </div>
                    <?php endforeach; ?>

                    <form method="post" action="/auth.php" data-validate>
                        <?= csrf_field() ?>
                        <input type="hidden" name="form_action" value="register">
                        <div class="form-group">
                            <label for="reg_username" class="form-label">Username</label>
                            <input type="text" id="reg_username" name="reg_username" class="form-control" required
                                   placeholder="Choose a username" minlength="3" aria-required="true">
                            <div class="form-hint">At least 3 characters, letters, numbers, underscores only.</div>
                            <div class="form-error"></div>
                        </div>
                        <div class="form-group">
                            <label for="reg_email" class="form-label">Email Address</label>
                            <input type="email" id="reg_email" name="reg_email" class="form-control" required
                                   placeholder="your@email.com" aria-required="true">
                            <div class="form-error"></div>
                        </div>
                        <div class="form-group">
                            <label for="reg_password" class="form-label">Password</label>
                            <input type="password" id="reg_password" name="reg_password" class="form-control" required
                                   placeholder="Create a password" minlength="6" aria-required="true">
                            <div class="form-hint">At least 6 characters.</div>
                            <div class="form-error"></div>
                        </div>
                        <div class="form-group">
                            <label for="reg_password2" class="form-label">Confirm Password</label>
                            <input type="password" id="reg_password2" name="reg_password2" class="form-control" required
                                   placeholder="Confirm your password" aria-required="true">
                            <div class="form-error"></div>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">Create Account →</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
