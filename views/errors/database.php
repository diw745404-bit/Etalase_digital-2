<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f6f7f4">
    <title>Database belum siap · Etalase Digital</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <main class="setup-shell">
        <a class="brand" href="index.php">
            <span class="brand-mark">e<span>.</span></span>
            <span>etalase<span class="brand-light">digital</span></span>
        </a>
        <section class="setup-card">
            <span class="setup-icon">!</span>
            <p class="eyebrow">PENYIAPAN APLIKASI</p>
            <h1>Database belum siap</h1>
            <p>Etalase Digital belum dapat terhubung ke MySQL. Pastikan MySQL berstatus <strong>Running</strong> di XAMPP, lalu muat ulang halaman ini.</p>
            <details>
                <summary>Detail masalah</summary>
                <code><?= e($databaseError) ?></code>
            </details>
            <p class="setup-hint">Database <strong>db_stella</strong> dan tabel yang diperlukan akan dibuat otomatis saat koneksi MySQL berhasil.</p>
        </section>
    </main>
</body>
</html>
