<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f6f7f4">
    <title><?= e($title) ?> · Etalase Digital</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <a class="brand auth-brand" href="<?= e(page_url('login')) ?>">
            <span class="brand-mark">e<span>.</span></span>
            <span>etalase<span class="brand-light">digital</span></span>
        </a>
        <div class="auth-layout">
            <section class="auth-intro">
                <p class="eyebrow">RUANG KARYA SISWA</p>
                <h1>Karya hebat layak <em>dipamerkan.</em></h1>
                <p>Satu tempat untuk menemukan, berbagi, dan merayakan karya digital siswa.</p>
                <div class="intro-note"><span class="note-dot"></span> Ide hari ini, karya yang menginspirasi esok.</div>
            </section>
            <section class="auth-card">
                <p class="eyebrow"><?= $page === 'register' ? 'MULAI BERKARYA' : 'SELAMAT DATANG KEMBALI' ?></p>
                <h2><?= $page === 'register' ? 'Buat akun' : 'Masuk ke etalase' ?></h2>
                <?php if ($notice): ?>
                    <div class="alert alert-<?= e($notice['kind']) ?>" role="alert"><?= e($notice['message']) ?></div>
                <?php endif; ?>
                <form method="post" class="form-stack">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $page === 'register' ? 'register' : 'login' ?>">
                    <?php if ($page === 'register'): ?>
                        <label>Nama lengkap<input name="nama" maxlength="100" autocomplete="name" required placeholder="Nama Anda"></label>
                    <?php endif; ?>
                    <label>Email<input type="email" name="email" maxlength="100" autocomplete="email" required placeholder="nama@sekolah.sch.id"></label>
                    <label>Password<input type="password" name="password" minlength="<?= $page === 'register' ? '8' : '1' ?>" maxlength="72" autocomplete="<?= $page === 'register' ? 'new-password' : 'current-password' ?>" required placeholder="<?= $page === 'register' ? 'Minimal 8 karakter' : 'Masukkan password' ?>"></label>
                    <button class="button button-primary button-wide" type="submit"><?= $page === 'register' ? 'Buat akun' : 'Masuk' ?><span>→</span></button>
                </form>
                <p class="auth-switch">
                    <?= $page === 'register' ? 'Sudah punya akun?' : 'Belum punya akun?' ?>
                    <a href="<?= e(page_url($page === 'register' ? 'login' : 'register')) ?>"><?= $page === 'register' ? 'Masuk' : 'Daftar sekarang' ?></a>
                </p>
                <?php if ($page === 'register'): ?>
                    <p class="form-hint">Pendaftar pertama menjadi Super Admin. Akun berikutnya otomatis berperan sebagai Siswa.</p>
                <?php endif; ?>
            </section>
        </div>
        <footer class="auth-footer">ETALASE DIGITAL <span>·</span> TEMPAT KARYA BERTUMBUH</footer>
    </main>
</body>
</html>
