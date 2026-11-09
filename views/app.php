<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f6f7f4">
    <title><?= e($title) ?> · Etalase Digital</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="<?= e(page_url('projects')) ?>">
                <span class="brand-mark">e<span>.</span></span><span>etalase<span class="brand-light">digital</span></span>
            </a>
            <nav class="main-nav" aria-label="Navigasi utama">
                <a class="<?= in_array($page, ['projects', 'project-form'], true) ? 'is-active' : '' ?>" href="<?= e(page_url('projects')) ?>">Karya</a>
                <?php if (is_admin($user)): ?>
                    <a class="<?= in_array($page, ['users', 'user-form'], true) ? 'is-active' : '' ?>" href="<?= e(page_url('users')) ?>">Pengguna</a>
                <?php endif; ?>
            </nav>
            <div class="account">
                <span class="avatar"><?= e(mb_strtoupper(mb_substr($user['nama'], 0, 1))) ?></span>
                <span class="account-name"><strong><?= e($user['nama']) ?></strong><small><?= e(str_replace('_', ' ', $user['role'])) ?></small></span>
                <form method="post" class="logout-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="logout">
                    <button class="icon-button" aria-label="Keluar" title="Keluar">↗</button>
                </form>
            </div>
        </div>
    </header>
    <main class="page-shell">
        <?php if ($notice): ?>
            <div class="alert alert-<?= e($notice['kind']) ?> page-alert" role="status"><?= e($notice['message']) ?></div>
        <?php endif; ?>

        <?php if ($page === 'projects'): ?>
            <section class="hero">
                <div class="hero-copy">
                    <p class="eyebrow">GALERI KARYA SISWA</p>
                    <h1>Ide jadi karya.<br><em>Karya jadi inspirasi.</em></h1>
                    <p>Jelajahi project pilihan dari para kreator muda. Temukan ide baru, dukung teman, dan tunjukkan karyamu.</p>
                    <div class="hero-meta"><span class="live-dot"></span> <strong><?= count($projects) ?></strong> karya terkurasi di etalase</div>
                </div>
                <div class="hero-stamp"><span>RUANG</span><strong>IDE<br>BARU</strong><small>EST. 2026</small><span class="stamp-star">✳</span></div>
            </section>
            <section class="gallery-section">
                <div class="section-heading">
                    <div><p class="eyebrow">JELAJAHI ETALASE</p><h2>Semua karya <span><?= count($projects) ?></span></h2></div>
                    <div class="gallery-actions">
                        <form class="search-form" method="get">
                            <input type="hidden" name="page" value="projects">
                            <input name="q" value="<?= e($search) ?>" placeholder="Cari karya atau siswa..." aria-label="Cari karya">
                            <button aria-label="Cari">⌕</button>
                        </form>
                        <a class="button button-primary" href="<?= e(page_url('project-form')) ?>">＋ <span>Tambah karya</span></a>
                    </div>
                </div>
                <?php if (!$projects): ?>
                    <div class="empty-state">
                        <span class="empty-icon">✳</span>
                        <h3><?= $search === '' ? 'Etalase masih kosong' : 'Karya tidak ditemukan' ?></h3>
                        <p><?= $search === '' ? 'Jadilah yang pertama membagikan karya di sini.' : 'Coba kata kunci lain, atau tambahkan karya baru.' ?></p>
                        <?php if ($search === ''): ?><a class="button button-primary" href="<?= e(page_url('project-form')) ?>">Tambah karya pertama</a><?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="project-grid">
                        <?php foreach ($projects as $index => $project): ?>
                            <article class="project-card">
                                <a class="project-image" href="<?= e(valid_web_url($project['link_project']) ? $project['link_project'] : '#') ?>" target="_blank" rel="noopener noreferrer">
                                    <img src="<?= e('uploads/' . rawurlencode(basename($project['gambar']))) ?>" alt="Gambar project <?= e($project['nama_project']) ?>" loading="lazy">
                                    <span class="project-index"><?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                                    <span class="image-arrow">↗</span>
                                </a>
                                <div class="project-body">
                                    <div class="project-kicker"><span><?= e($project['bahasa'] ?: 'PROJECT SISWA') ?></span><time><?= e(date('d M Y', strtotime($project['created_at']))) ?></time></div>
                                    <h3><?= e($project['nama_project']) ?></h3>
                                    <p class="project-description"><?= e($project['deskripsi']) ?></p>
                                    <div class="project-footer">
                                        <span class="creator"><span class="creator-avatar"><?= e(mb_strtoupper(mb_substr($project['pemilik'], 0, 1))) ?></span><?= e($project['pemilik']) ?></span>
                                        <div class="project-links">
                                            <?php if (valid_web_url($project['link_project'])): ?><a href="<?= e($project['link_project']) ?>" target="_blank" rel="noopener noreferrer">Project ↗</a><?php endif; ?>
                                            <?php if (valid_web_url($project['link_demo'] ?? '')): ?><a href="<?= e($project['link_demo']) ?>" target="_blank" rel="noopener noreferrer">Demo ↗</a><?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if (can_manage_project($user, (int)$project['user_id'])): ?>
                                        <div class="card-management">
                                            <a href="<?= e(page_url('project-form', ['id' => (int)$project['id']])) ?>">Edit project</a>
                                            <form method="post" onsubmit="return confirm('Hapus project ini dari etalase?')">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete-project">
                                                <input type="hidden" name="id" value="<?= (int)$project['id'] ?>">
                                                <button type="submit">Hapus</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php elseif ($page === 'project-form'): ?>
            <section class="form-page">
                <a class="back-link" href="<?= e(page_url('projects')) ?>">← Kembali ke etalase</a>
                <div class="form-heading">
                    <p class="eyebrow"><?= $id ? 'PERBARUI KARYA' : 'BAGIKAN KARYA' ?></p>
                    <h1><?= $id ? 'Edit project' : 'Tambah project baru' ?></h1>
                    <p>Lengkapi detail karya agar teman-teman dapat mengenal dan mencobanya.</p>
                </div>
                <?php if ($formError !== ''): ?>
                    <div class="alert alert-error"><?= e($formError) ?></div>
                <?php else: ?>
                    <form method="post" enctype="multipart/form-data" class="panel form-stack project-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="save-project">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <div class="form-row">
                            <label>Nama project <span>*</span><input name="nama_project" maxlength="150" required value="<?= e($project['nama_project'] ?? '') ?>" placeholder="Contoh: EcoTrack — aplikasi pemilah sampah"></label>
                            <label>Teknologi / kategori<input name="bahasa" maxlength="255" value="<?= e($project['bahasa'] ?? '') ?>" placeholder="Contoh: Website · HTML, CSS, JavaScript"></label>
                        </div>
                        <label>Deskripsi project <span>*</span><textarea name="deskripsi" rows="5" required placeholder="Ceritakan ide, tujuan, dan cara kerja project ini..."><?= e($project['deskripsi'] ?? '') ?></textarea></label>
                        <div class="form-row">
                            <label>Tautan project / repository <span>*</span><input type="url" name="link_project" required value="<?= e($project['link_project'] ?? '') ?>" placeholder="https://github.com/..."></label>
                            <label>Tautan demo <small>(opsional)</small><input type="url" name="link_demo" value="<?= e($project['link_demo'] ?? '') ?>" placeholder="https://..."></label>
                        </div>
                        <label>Gambar project <span>*</span><input type="file" name="gambar" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" <?= $id ? '' : 'required' ?>><small>Format JPG, PNG, atau WebP · Maksimal 5 MB<?= $id ? ' · Kosongkan jika tidak ingin mengganti gambar' : '' ?></small></label>
                        <div class="form-actions">
                            <a class="button button-quiet" href="<?= e(page_url('projects')) ?>">Batal</a>
                            <button class="button button-primary" type="submit"><?= $id ? 'Simpan perubahan' : 'Terbitkan karya' ?> <span>→</span></button>
                        </div>
                    </form>
                <?php endif; ?>
            </section>
        <?php elseif ($page === 'users'): ?>
            <section class="users-page">
                <?php if (!is_admin($user)): ?>
                    <div class="empty-state"><h2>Akses ditolak</h2><p>Menu ini hanya tersedia untuk Admin dan Super Admin.</p></div>
                <?php else: ?>
                    <div class="section-heading users-heading">
                        <div><p class="eyebrow">ADMINISTRASI AKUN</p><h1>Kelola pengguna</h1><p>Atur data siswa dan hak akses di etalase digital.</p></div>
                        <a class="button button-primary" href="<?= e(page_url('user-form')) ?>">＋ <span>Tambah pengguna</span></a>
                    </div>
                    <div class="stats-row">
                        <div class="stat-card"><span>SELURUH PENGGUNA</span><strong><?= count($users) ?></strong></div>
                        <div class="stat-card"><span>SISWA</span><strong><?= $students ?></strong></div>
                        <div class="stat-card"><span>PERAN ANDA</span><strong class="stat-role"><?= e(str_replace('_', ' ', $user['role'])) ?></strong></div>
                    </div>
                    <div class="panel table-panel"><div class="table-wrap">
                        <table>
                            <thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th class="table-action">Aksi</th></tr></thead>
                            <tbody>
                                <?php foreach ($users as $target): ?>
                                    <tr>
                                        <td><span class="table-person"><span class="creator-avatar"><?= e(mb_strtoupper(mb_substr($target['nama'], 0, 1))) ?></span><strong><?= e($target['nama']) ?></strong></span></td>
                                        <td><?= e($target['email']) ?></td>
                                        <td><span class="role-badge role-<?= e($target['role']) ?>"><?= e(str_replace('_', ' ', $target['role'])) ?></span></td>
                                        <td class="table-action">
                                            <?php if (can_manage_user($user, $target)): ?>
                                                <a class="table-edit" href="<?= e(page_url('user-form', ['id' => (int)$target['id']])) ?>">Edit</a>
                                                <form method="post" class="inline-form" onsubmit="return confirm('Hapus akun ini beserta semua project miliknya?')">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="delete-user">
                                                    <input type="hidden" name="id" value="<?= (int)$target['id'] ?>">
                                                    <button class="table-delete" type="submit">Hapus</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div></div>
                    <p class="table-note"><?= is_super_admin($user) ? 'Super Admin dapat mengelola semua peran. Akun yang sedang digunakan tidak dapat diubah atau dihapus.' : 'Admin hanya dapat mengelola akun dengan peran Siswa.' ?></p>
                <?php endif; ?>
            </section>
        <?php elseif ($page === 'user-form'): ?>
            <section class="form-page">
                <a class="back-link" href="<?= e(page_url('users')) ?>">← Kembali ke pengguna</a>
                <div class="form-heading">
                    <p class="eyebrow">ADMINISTRASI AKUN</p>
                    <h1><?= $target ? 'Edit pengguna' : 'Tambah pengguna' ?></h1>
                    <p>Data akun digunakan untuk masuk dan mengelola karya.</p>
                </div>
                <?php if ($formError !== ''): ?>
                    <div class="alert alert-error"><?= e($formError) ?></div>
                <?php else: ?>
                    <form method="post" class="panel form-stack project-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="save-user">
                        <input type="hidden" name="id" value="<?= (int)($target['id'] ?? 0) ?>">
                        <label>Nama lengkap <span>*</span><input name="nama" maxlength="100" required value="<?= e($target['nama'] ?? '') ?>" placeholder="Nama siswa"></label>
                        <label>Email <span>*</span><input type="email" name="email" maxlength="100" required value="<?= e($target['email'] ?? '') ?>" placeholder="nama@sekolah.sch.id"></label>
                        <?php if (is_super_admin($user)): ?>
                            <label>Peran
                                <select name="role">
                                    <?php foreach (['siswa' => 'Siswa', 'admin' => 'Admin', 'super_admin' => 'Super Admin'] as $value => $label): ?>
                                        <option value="<?= e($value) ?>" <?= ($target['role'] ?? 'siswa') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        <?php else: ?>
                            <input type="hidden" name="role" value="siswa">
                            <p class="role-hint">Akun yang dibuat atau dikelola Admin hanya memiliki peran Siswa.</p>
                        <?php endif; ?>
                        <label>Password <?= $target ? '<small>(kosongkan jika tidak diubah)</small>' : '<span>*</span>' ?><input type="password" name="password" minlength="8" maxlength="72" <?= $target ? '' : 'required' ?> autocomplete="new-password" placeholder="Minimal 8 karakter"></label>
                        <div class="form-actions">
                            <a class="button button-quiet" href="<?= e(page_url('users')) ?>">Batal</a>
                            <button class="button button-primary" type="submit">Simpan pengguna <span>→</span></button>
                        </div>
                    </form>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
    <footer class="site-footer"><span>ETALASE DIGITAL</span><span>Tempat karya bertumbuh.</span><span>© <?= date('Y') ?></span></footer>
</body>
</html>
