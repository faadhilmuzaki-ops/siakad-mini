<?php
// public/dosen_create.php — Form tambah dosen baru
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../classes/AuditLog.php';

requireLogin();

$pdo    = getDB();
$audit  = new AuditLog();
$errors = [];
$input  = ['nidn'=>'','nama'=>'','email'=>'','program_studi'=>'','status'=>'aktif'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    // Ambil & trim input
    $input = [
        'nidn'          => trim($_POST['nidn']          ?? ''),
        'nama'          => trim($_POST['nama']          ?? ''),
        'email'         => trim($_POST['email']         ?? ''),
        'program_studi' => trim($_POST['program_studi'] ?? ''),
        'status'        => trim($_POST['status']        ?? 'aktif'),
    ];

    // Validasi
    if ($input['nidn'] === '')  $errors[] = 'NIDN wajib diisi.';
    elseif (!preg_match('/^\d{10}$/', $input['nidn'])) $errors[] = 'NIDN harus 10 digit angka.';

    if ($input['nama'] === '')  $errors[] = 'Nama wajib diisi.';

    if ($input['email'] === '') $errors[] = 'Email wajib diisi.';
    elseif (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';

    $prodi_valid = ['Teknik Informatika','Sistem Informasi','Teknik Elektro'];
    if (!in_array($input['program_studi'], $prodi_valid, true)) $errors[] = 'Program studi tidak valid.';

    if (!in_array($input['status'], ['aktif','nonaktif'], true)) $errors[] = 'Status tidak valid.';

    // Cek NIDN & email duplikat
    if (empty($errors)) {
        $cekNidn = $pdo->prepare('SELECT id FROM dosen WHERE nidn = :nidn');
        $cekNidn->execute([':nidn' => $input['nidn']]);
        if ($cekNidn->fetch()) $errors[] = 'NIDN sudah terdaftar.';

        $cekEmail = $pdo->prepare('SELECT id FROM dosen WHERE email = :email');
        $cekEmail->execute([':email' => $input['email']]);
        if ($cekEmail->fetch()) $errors[] = 'Email sudah terdaftar.';
    }

    // Upload foto
    $namaFoto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $tmpFile  = $_FILES['foto']['tmp_name'];
        $ukuran   = $_FILES['foto']['size'];

        // Cek MIME type pakai finfo (bukan cuma ekstensi!)
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mime     = finfo_file($finfo, $tmpFile);
        finfo_close($finfo);

        $mimeValid = ['image/jpeg','image/png','image/gif','image/webp'];
        $eksMime   = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];

        if (!in_array($mime, $mimeValid, true)) {
            $errors[] = 'File foto harus berupa gambar (JPG, PNG, GIF, WEBP).';
        } elseif ($ukuran > 2 * 1024 * 1024) {
            $errors[] = 'Ukuran foto maksimal 2 MB.';
        } else {
            // Hash nama file — cegah path traversal & nama duplikat
            $ext      = $eksMime[$mime];
            $namaFoto = hash('sha256', uniqid('foto_', true)) . '.' . $ext;
            $tujuan   = __DIR__ . '/../uploads/' . $namaFoto;

            if (!move_uploaded_file($tmpFile, $tujuan)) {
                $errors[] = 'Gagal menyimpan foto. Cek permission folder uploads/.';
                $namaFoto = null;
            }
        }
    }

    // Simpan ke database
    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'INSERT INTO dosen (nidn, nama, email, program_studi, foto, status)
             VALUES (:nidn, :nama, :email, :program_studi, :foto, :status)'
        );
        $stmt->execute([
            ':nidn'          => $input['nidn'],
            ':nama'          => $input['nama'],
            ':email'         => $input['email'],
            ':program_studi' => $input['program_studi'],
            ':foto'          => $namaFoto,
            ':status'        => $input['status'],
        ]);

        $audit->catat('CREATE', 'dosen', 'Tambah dosen: ' . $input['nama']);
        $_SESSION['flash'] = 'Dosen ' . $input['nama'] . ' berhasil ditambahkan.';
        header('Location: /siakad-mini/public/dosen.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Dosen — SIAKAD Mini</title>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Segoe UI',sans-serif; background:#f0f2f5; }
        .navbar { background:#2563eb; color:#fff; padding:14px 24px; display:flex; justify-content:space-between; align-items:center; }
        .navbar h1 { font-size:18px; font-weight:600; }
        .navbar a { color:#fff; text-decoration:none; background:rgba(255,255,255,0.2); padding:6px 14px; border-radius:6px; font-size:13px; }
        .container { max-width:620px; margin:1.5rem auto; padding:0 1rem; }
        .card { background:#fff; border-radius:12px; padding:1.5rem 2rem; box-shadow:0 2px 8px rgba(0,0,0,0.06); }
        .card h2 { font-size:18px; margin-bottom:1.25rem; color:#1a1a1a; }
        .form-group { margin-bottom:1rem; }
        label { display:block; font-size:13px; font-weight:500; color:#333; margin-bottom:5px; }
        input[type=text], input[type=email], select {
            width:100%; padding:9px 12px; border:1px solid #ddd; border-radius:8px; font-size:14px; color:#1a1a1a;
        }
        input:focus, select:focus { outline:none; border-color:#2563eb; }
        input[type=file] { width:100%; padding:7px 0; font-size:13px; color:#555; }
        .hint { font-size:11px; color:#94a3b8; margin-top:3px; }
        .btn-row { display:flex; gap:8px; margin-top:1.25rem; }
        .btn { padding:9px 20px; border-radius:8px; font-size:14px; font-weight:500; cursor:pointer; border:none; text-decoration:none; }
        .btn-primary { background:#2563eb; color:#fff; }
        .btn-primary:hover { background:#1d4ed8; }
        .btn-secondary { background:#f1f5f9; color:#334155; border:1px solid #e2e8f0; }
        .btn-secondary:hover { background:#e2e8f0; }
        .alert-error { background:#fef2f2; border:1px solid #fca5a5; color:#b91c1c; border-radius:8px; padding:10px 14px; font-size:13px; margin-bottom:1rem; }
        .alert-error ul { padding-left:16px; margin-top:4px; }
    </style>
</head>
<body>
<nav class="navbar">
    <h1>SIAKAD Mini</h1>
    <a href="/siakad-mini/public/dosen.php">← Kembali</a>
</nav>

<div class="container">
    <div class="card">
        <h2>Tambah Dosen Baru</h2>

        <?php if (!empty($errors)): ?>
        <div class="alert-error">
            <strong>Terdapat kesalahan:</strong>
            <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul>
        </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <?php csrfInput(); ?>

            <div class="form-group">
                <label for="nidn">NIDN <span style="color:red">*</span></label>
                <input type="text" id="nidn" name="nidn" maxlength="10"
                       value="<?= htmlspecialchars($input['nidn'], ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="10 digit angka">
            </div>

            <div class="form-group">
                <label for="nama">Nama Lengkap <span style="color:red">*</span></label>
                <input type="text" id="nama" name="nama" maxlength="100"
                       value="<?= htmlspecialchars($input['nama'], ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="Nama lengkap beserta gelar">
            </div>

            <div class="form-group">
                <label for="email">Email <span style="color:red">*</span></label>
                <input type="email" id="email" name="email" maxlength="120"
                       value="<?= htmlspecialchars($input['email'], ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="contoh@unsiq.ac.id">
            </div>

            <div class="form-group">
                <label for="program_studi">Program Studi <span style="color:red">*</span></label>
                <select id="program_studi" name="program_studi">
                    <option value="">-- Pilih Program Studi --</option>
                    <?php foreach (['Teknik Informatika','Sistem Informasi','Teknik Elektro'] as $p): ?>
                    <option value="<?= $p ?>" <?= $input['program_studi'] === $p ? 'selected' : '' ?>><?= $p ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="aktif"    <?= $input['status'] === 'aktif'    ? 'selected' : '' ?>>Aktif</option>
                    <option value="nonaktif" <?= $input['status'] === 'nonaktif' ? 'selected' : '' ?>>Non-aktif</option>
                </select>
            </div>

            <div class="form-group">
                <label for="foto">Foto (opsional)</label>
                <input type="file" id="foto" name="foto" accept="image/*">
                <p class="hint">Format: JPG, PNG, WEBP. Maks. 2 MB.</p>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="/siakad-mini/public/dosen.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
<?php // audit-create
