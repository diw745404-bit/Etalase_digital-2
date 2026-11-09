<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

try {
    require_once __DIR__ . '/database/connection.php';
    require_once __DIR__ . '/database/setup.php';
} catch (mysqli_sql_exception $exception) {
    http_response_code(503);
    $databaseError = $exception->getMessage();
    require __DIR__ . '/views/errors/database.php';
    exit;
}

$user = current_user($connection);
$page = (string)($_GET['page'] ?? ($user ? 'projects' : 'login'));
$allowedPages = ['login', 'register', 'projects', 'project-form', 'users', 'user-form'];
if (!in_array($page, $allowedPages, true)) {
    $page = $user ? 'projects' : 'login';
}
if ($user && in_array($page, ['login', 'register'], true)) {
    redirect_to('projects');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'login') {
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $statement = $connection->prepare('SELECT id, password FROM users WHERE email = ?');
        $statement->bind_param('s', $email);
        $statement->execute();
        $account = $statement->get_result()->fetch_assoc();
        $statement->close();

        if ($account && password_verify($password, $account['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$account['id'];
            flash('Selamat datang kembali.');
            redirect_to('projects');
        }

        flash('Email atau password tidak sesuai.', 'error');
        redirect_to('login');
    }

    if ($action === 'register') {
        $name = trim((string)($_POST['nama'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');

        if (
            $name === ''
            || mb_strlen($name) > 100
            || mb_strlen($email) > 100
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($password) < 8
            || strlen($password) > 72
        ) {
            flash('Isi nama, email valid, dan password 8–72 karakter.', 'error');
            redirect_to('register');
        }

        $check = $connection->prepare('SELECT id FROM users WHERE email = ?');
        $check->bind_param('s', $email);
        $check->execute();
        $emailExists = $check->get_result()->num_rows > 0;
        $check->close();
        if ($emailExists) {
            flash('Email tersebut sudah terdaftar.', 'error');
            redirect_to('register');
        }

        $count = (int)$connection->query('SELECT COUNT(*) AS total FROM users')->fetch_assoc()['total'];
        $role = $count === 0 ? 'super_admin' : 'siswa';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $statement = $connection->prepare('INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)');
        $statement->bind_param('ssss', $name, $email, $hash, $role);

        try {
            $statement->execute();
        } catch (mysqli_sql_exception $exception) {
            $statement->close();
            if ($exception->getCode() !== 1062) {
                throw $exception;
            }
            flash('Email tersebut sudah terdaftar.', 'error');
            redirect_to('register');
        }

        $newUserId = $connection->insert_id;
        $statement->close();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $newUserId;
        flash($role === 'super_admin'
            ? 'Akun pertama menjadi Super Admin. Silakan buat akun Admin dan Siswa dari menu Pengguna.'
            : 'Akun berhasil dibuat. Selamat datang di Etalase Digital.');
        redirect_to('projects');
    }

    if ($action === 'logout') {
        $_SESSION = [];
        session_regenerate_id(true);
        flash('Anda telah keluar.');
        redirect_to('login');
    }

    $user = require_login($user);
    if ($action === 'save-project') {
        handle_project_save($connection, $user);
    }
    if ($action === 'delete-project') {
        handle_project_delete($connection, $user);
    }
    if ($action === 'save-user') {
        handle_user_save($connection, $user);
    }
    if ($action === 'delete-user') {
        handle_user_delete($connection, $user);
    }

    flash('Aksi tidak dikenali.', 'error');
    redirect_to('projects');
}

$notice = take_flash();
$title = match ($page) {
    'login' => 'Masuk',
    'register' => 'Buat akun',
    'project-form' => 'Project',
    'users' => 'Kelola pengguna',
    'user-form' => 'Data pengguna',
    default => 'Etalase karya',
};

if ($page === 'projects') {
    $search = trim((string)($_GET['q'] ?? ''));
    if ($search !== '') {
        $like = '%' . $search . '%';
        $statement = $connection->prepare(
            'SELECT p.*, u.nama AS pemilik
             FROM projects p
             JOIN users u ON u.id = p.user_id
             WHERE p.nama_project LIKE ? OR p.deskripsi LIKE ? OR u.nama LIKE ?
             ORDER BY p.created_at DESC'
        );
        $statement->bind_param('sss', $like, $like, $like);
    } else {
        $statement = $connection->prepare(
            'SELECT p.*, u.nama AS pemilik
             FROM projects p
             JOIN users u ON u.id = p.user_id
             ORDER BY p.created_at DESC'
        );
    }
    $statement->execute();
    $projects = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();
}

if ($page === 'project-form') {
    $id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
    $project = $id ? project_by_id($connection, $id) : null;
    $formError = '';
    if ($id && (!$project || !can_manage_project($user, (int)$project['user_id']))) {
        http_response_code(403);
        $project = null;
        $formError = 'Project tidak ditemukan atau Anda tidak memiliki akses.';
    }
}

if ($page === 'users' && is_admin($user)) {
    $users = $connection->query('SELECT id, nama, email, role FROM users ORDER BY id DESC')->fetch_all(MYSQLI_ASSOC);
    $students = count(array_filter($users, static fn(array $item): bool => $item['role'] === 'siswa'));
}

if ($page === 'user-form') {
    $id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
    $target = is_admin($user) && $id ? user_by_id($connection, $id) : null;
    $formError = '';
    if (!is_admin($user)) {
        http_response_code(403);
        $formError = 'Anda tidak memiliki izin untuk mengelola pengguna.';
    } elseif ($id && (!$target || !can_manage_user($user, $target))) {
        http_response_code(403);
        $formError = 'Pengguna tidak ditemukan atau Anda tidak memiliki akses.';
    }
}

require __DIR__ . ($user ? '/views/app.php' : '/views/auth.php');
