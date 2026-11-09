<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function page_url(string $page, array $parameters = []): string
{
    return 'index.php?' . http_build_query(['page' => $page] + $parameters);
}

function redirect_to(string $page, array $parameters = []): never
{
    header('Location: ' . page_url($page, $parameters));
    exit;
}

function flash(string $message, string $kind = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'kind' => $kind];
}

function take_flash(): ?array
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $message;
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(400);
        exit('Permintaan tidak valid. Muat ulang halaman, lalu coba lagi.');
    }
}

function current_user(mysqli $connection): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $statement = $connection->prepare('SELECT id, nama, email, role FROM users WHERE id = ?');
    $statement->bind_param('i', $_SESSION['user_id']);
    $statement->execute();
    $user = $statement->get_result()->fetch_assoc();
    $statement->close();

    if (!$user) {
        unset($_SESSION['user_id']);
        return null;
    }

    return $user;
}

function require_login(?array $user): array
{
    if (!$user) {
        flash('Silakan masuk terlebih dahulu.', 'error');
        redirect_to('login');
    }
    return $user;
}

function is_admin(array $user): bool
{
    return in_array($user['role'], ['admin', 'super_admin'], true);
}

function is_super_admin(array $user): bool
{
    return $user['role'] === 'super_admin';
}

function can_manage_project(array $user, int $ownerId): bool
{
    return is_admin($user) || (int)$user['id'] === $ownerId;
}

function valid_web_url(string $value): bool
{
    if (!filter_var($value, FILTER_VALIDATE_URL)) {
        return false;
    }

    return in_array(strtolower((string)parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true);
}

function uploaded_project_image(?string $currentImage = null): string
{
    $file = $_FILES['gambar'] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        if ($currentImage !== null) {
            return $currentImage;
        }
        throw new RuntimeException('Gambar project wajib diunggah.');
    }

    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Unggahan gagal atau ukuran gambar melebihi 5 MB.');
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    $mime = $imageInfo['mime'] ?? '';
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($extensions[$mime]) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Gunakan gambar berformat JPG, PNG, atau WebP.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    $uploadDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
        throw new RuntimeException('Folder unggahan tidak dapat dibuat.');
    }
    if (!move_uploaded_file($file['tmp_name'], $uploadDirectory . DIRECTORY_SEPARATOR . $filename)) {
        throw new RuntimeException('Gambar tidak dapat disimpan.');
    }

    return $filename;
}

function remove_project_image(?string $filename): void
{
    if (!$filename || basename($filename) !== $filename) {
        return;
    }

    $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $filename;
    if (is_file($path) && !unlink($path)) {
        error_log('Could not remove project image: ' . $path);
    }
}

function project_by_id(mysqli $connection, int $id): ?array
{
    $statement = $connection->prepare('SELECT * FROM projects WHERE id = ?');
    $statement->bind_param('i', $id);
    $statement->execute();
    $project = $statement->get_result()->fetch_assoc();
    $statement->close();
    return $project ?: null;
}

function project_form_error(string $message, int $id = 0): never
{
    flash($message, 'error');
    redirect_to('project-form', $id ? ['id' => $id] : []);
}

function handle_project_save(mysqli $connection, array $user): never
{
    $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
    $project = $id ? project_by_id($connection, $id) : null;
    if ($id && (!$project || !can_manage_project($user, (int)$project['user_id']))) {
        flash('Project tidak ditemukan atau Anda tidak memiliki akses.', 'error');
        redirect_to('projects');
    }

    $name = trim((string)($_POST['nama_project'] ?? ''));
    $description = trim((string)($_POST['deskripsi'] ?? ''));
    $supportingLink = trim((string)($_POST['link_project'] ?? ''));
    $demoLink = trim((string)($_POST['link_demo'] ?? ''));
    $technology = trim((string)($_POST['bahasa'] ?? ''));
    if ($name === '' || mb_strlen($name) > 150 || $description === '') {
        project_form_error('Nama project (maks. 150 karakter) dan deskripsi wajib diisi.', $id);
    }
    if (strlen($supportingLink) > 255 || strlen($demoLink) > 255 || mb_strlen($technology) > 255 ||
        !valid_web_url($supportingLink) || ($demoLink !== '' && !valid_web_url($demoLink))) {
        project_form_error('Masukkan tautan pendukung dan demo dengan URL HTTP atau HTTPS yang valid.', $id);
    }

    try {
        $image = uploaded_project_image($project['gambar'] ?? null);
    } catch (RuntimeException $exception) {
        project_form_error($exception->getMessage(), $id);
    }

    if ($id) {
        $statement = $connection->prepare(
            'UPDATE projects SET nama_project = ?, deskripsi = ?, bahasa = ?, gambar = ?, link_project = ?, link_demo = ? WHERE id = ?'
        );
        $statement->bind_param('ssssssi', $name, $description, $technology, $image, $supportingLink, $demoLink, $id);
        $statement->execute();
        $statement->close();
        if ($image !== $project['gambar']) {
            remove_project_image($project['gambar']);
        }
        flash('Project berhasil diperbarui.');
    } else {
        $ownerId = (int)$user['id'];
        $statement = $connection->prepare(
            'INSERT INTO projects (user_id, nama_project, deskripsi, bahasa, gambar, link_project, link_demo) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->bind_param('issssss', $ownerId, $name, $description, $technology, $image, $supportingLink, $demoLink);
        $statement->execute();
        $statement->close();
        flash('Project berhasil ditambahkan ke etalase.');
    }

    redirect_to('projects');
}

function handle_project_delete(mysqli $connection, array $user): never
{
    $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
    $project = $id ? project_by_id($connection, (int)$id) : null;
    if (!$project || !can_manage_project($user, (int)$project['user_id'])) {
        flash('Project tidak ditemukan atau Anda tidak memiliki akses.', 'error');
        redirect_to('projects');
    }

    $statement = $connection->prepare('DELETE FROM projects WHERE id = ?');
    $statement->bind_param('i', $id);
    $statement->execute();
    $statement->close();
    remove_project_image($project['gambar']);
    flash('Project berhasil dihapus.');
    redirect_to('projects');
}

function user_by_id(mysqli $connection, int $id): ?array
{
    $statement = $connection->prepare('SELECT id, nama, email, role FROM users WHERE id = ?');
    $statement->bind_param('i', $id);
    $statement->execute();
    $target = $statement->get_result()->fetch_assoc();
    $statement->close();
    return $target ?: null;
}

function can_manage_user(array $user, array $target): bool
{
    if ((int)$user['id'] === (int)$target['id']) {
        return false;
    }

    return is_super_admin($user) || ($user['role'] === 'admin' && $target['role'] === 'siswa');
}

function handle_user_save(mysqli $connection, array $user): never
{
    if (!is_admin($user)) {
        http_response_code(403);
        exit('Anda tidak memiliki izin untuk mengelola pengguna.');
    }

    $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
    $target = $id ? user_by_id($connection, $id) : null;
    if ($id && (!$target || !can_manage_user($user, $target))) {
        flash('Pengguna tidak ditemukan atau Anda tidak memiliki akses.', 'error');
        redirect_to('users');
    }

    $name = trim((string)($_POST['nama'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $role = is_super_admin($user) ? (string)($_POST['role'] ?? 'siswa') : 'siswa';
    if ($name === '' || mb_strlen($name) > 100 || mb_strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('Nama dan alamat email yang valid wajib diisi.', 'error');
        redirect_to('user-form', $id ? ['id' => $id] : []);
    }
    if (!in_array($role, ['super_admin', 'admin', 'siswa'], true)) {
        flash('Peran yang dipilih tidak valid.', 'error');
        redirect_to('user-form', $id ? ['id' => $id] : []);
    }
    if ((!$id && (strlen($password) < 8 || strlen($password) > 72)) ||
        ($password !== '' && (strlen($password) < 8 || strlen($password) > 72))) {
        flash('Password baru wajib berisi 8–72 karakter.', 'error');
        redirect_to('user-form', $id ? ['id' => $id] : []);
    }

    $check = $connection->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
    $check->bind_param('si', $email, $id);
    $check->execute();
    $emailExists = $check->get_result()->num_rows > 0;
    $check->close();
    if ($emailExists) {
        flash('Email tersebut sudah digunakan oleh pengguna lain.', 'error');
        redirect_to('user-form', $id ? ['id' => $id] : []);
    }

    if ($id) {
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $statement = $connection->prepare('UPDATE users SET nama = ?, email = ?, role = ?, password = ? WHERE id = ?');
            $statement->bind_param('ssssi', $name, $email, $role, $hash, $id);
        } else {
            $statement = $connection->prepare('UPDATE users SET nama = ?, email = ?, role = ? WHERE id = ?');
            $statement->bind_param('sssi', $name, $email, $role, $id);
        }
        $statement->execute();
        $statement->close();
        flash('Data pengguna berhasil diperbarui.');
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $statement = $connection->prepare('INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)');
        $statement->bind_param('ssss', $name, $email, $hash, $role);
        $statement->execute();
        $statement->close();
        flash('Akun pengguna berhasil dibuat.');
    }

    redirect_to('users');
}

function handle_user_delete(mysqli $connection, array $user): never
{
    if (!is_admin($user)) {
        http_response_code(403);
        exit('Anda tidak memiliki izin untuk mengelola pengguna.');
    }

    $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
    $target = $id ? user_by_id($connection, (int)$id) : null;
    if (!$target || !can_manage_user($user, $target)) {
        flash('Pengguna tidak ditemukan atau Anda tidak memiliki akses.', 'error');
        redirect_to('users');
    }

    if ($target['role'] === 'super_admin') {
        $result = $connection->query("SELECT COUNT(*) AS total FROM users WHERE role = 'super_admin'");
        if ((int)$result->fetch_assoc()['total'] <= 1) {
            flash('Akun Super Admin terakhir tidak dapat dihapus.', 'error');
            redirect_to('users');
        }
    }

    $statement = $connection->prepare('DELETE FROM users WHERE id = ?');
    $statement->bind_param('i', $id);
    $statement->execute();
    $statement->close();
    flash('Akun pengguna dan project miliknya berhasil dihapus.');
    redirect_to('users');
}
