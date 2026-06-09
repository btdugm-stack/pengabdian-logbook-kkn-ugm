<?php
session_start();
require_once __DIR__ . '/db.php';

$page = $_GET['page'] ?? 'home';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login_simulation') {
        $email = trim($_POST['email'] ?? '');
        $stmt = db()->prepare("SELECT id FROM students WHERE email = ?");
        $stmt->execute([$email]);
        $student = $stmt->fetch();
        if ($student) {
            $_SESSION['user_id'] = $student['id'];
            flash('Login SSO Google simulasi berhasil.');
            redirect('index.php?page=dashboard');
        }
        flash('Email tidak ditemukan pada data mahasiswa demo.', 'error');
        redirect('index.php?page=login');
    }

    if ($action === 'save_profile') {
        require_login();
        $u = current_user();
        $stmt = db()->prepare("UPDATE students SET name=?, birth_place=?, birth_date=?, faculty=?, study_program=?, phone=?, emergency_contact=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([
            $_POST['name'], $_POST['birth_place'], $_POST['birth_date'], $_POST['faculty'], $_POST['study_program'],
            $_POST['phone'], $_POST['emergency_contact'], $u['id']
        ]);
        flash('Biodata berhasil diperbarui.');
        redirect('index.php?page=data_kkn');
    }

    if ($action === 'save_logbook') {
        require_login();
        $u = current_user();

        [$lat, $lng] = parse_coord($_POST['coordinate'] ?? '');

        $themeName = trim($_POST['theme_new'] ?? '') ?: ($_POST['theme_name'] ?? '');
        $programName = trim($_POST['program_new'] ?? '') ?: ($_POST['program_name'] ?? '');
        $activityTypeName = trim($_POST['activity_type_new'] ?? '') ?: ($_POST['activity_type_name'] ?? '');
        $locationName = trim($_POST['location_new'] ?? '') ?: ($_POST['location_name'] ?? '');

        $themeId = get_or_create_master('themes', $themeName);
        $programId = get_or_create_master('programs', $programName);
        $activityTypeId = get_or_create_master('activity_types', $activityTypeName);
        $locationId = get_or_create_master('locations', $locationName, ['latitude' => $lat, 'longitude' => $lng]);

        if (!$themeId || !$programId || !$activityTypeId || !$locationId) {
            flash('Tema, program kerja, jenis kegiatan, dan lokasi wajib diisi.', 'error');
            redirect('index.php?page=logbook_form');
        }

        if ($lat !== null && $lng !== null) {
            $stmt = db()->prepare("UPDATE locations SET latitude=?, longitude=? WHERE id=?");
            $stmt->execute([$lat, $lng, $locationId]);
        }

        $status = $_POST['submit_type'] === 'draft' ? 'Draft' : 'Submitted';

        $stmt = db()->prepare("
            INSERT INTO logbooks(student_id, theme_id, program_id, activity_type_id, location_id, log_date, community_count, health_status, progress_note, personal_info, documentation, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $u['id'], $themeId, $programId, $activityTypeId, $locationId,
            $_POST['log_date'], (int)$_POST['community_count'], $_POST['health_status'],
            $_POST['progress_note'], $_POST['personal_info'], $_POST['documentation'], $status
        ]);

        flash('Logbook berhasil disimpan. Input baru masuk DB dan akan muncul sebagai pilihan dropdown berikutnya.');
        redirect('index.php?page=dashboard');
    }
}

if ($page === 'logout') {
    session_destroy();
    redirect('index.php');
}

if ($page === 'export') {
    $user = current_user();
    if (!$user) redirect('index.php?page=login');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=laporan-logbook-kkn.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Tanggal', 'Mahasiswa', 'Tema', 'Program', 'Jenis', 'Lokasi', 'Koordinat', 'Masyarakat Terlibat', 'Kondisi', 'Status', 'Catatan']);
    $stmt = db()->prepare("
        SELECT l.*, s.name student_name, t.name theme_name, p.name program_name, a.name activity_type_name, loc.name location_name, loc.latitude, loc.longitude
        FROM logbooks l
        JOIN students s ON s.id=l.student_id
        JOIN themes t ON t.id=l.theme_id
        JOIN programs p ON p.id=l.program_id
        JOIN activity_types a ON a.id=l.activity_type_id
        JOIN locations loc ON loc.id=l.location_id
        WHERE l.student_id=?
        ORDER BY l.log_date DESC
    ");
    $stmt->execute([$user['id']]);
    foreach ($stmt->fetchAll() as $r) {
        fputcsv($out, [
            $r['log_date'], $r['student_name'], $r['theme_name'], $r['program_name'], $r['activity_type_name'],
            $r['location_name'], $r['latitude'] . ',' . $r['longitude'], $r['community_count'], $r['health_status'], $r['status'], $r['progress_note']
        ]);
    }
    fclose($out);
    exit;
}

function page_title($page) {
    $map = [
        'home' => 'Overview',
        'login' => 'Login Mahasiswa',
        'dashboard' => 'Dashboard Mahasiswa',
        'data_kkn' => 'Data KKN & Biodata',
        'logbook_form' => 'Input Logbook',
        'my_logbooks' => 'Logbook Saya',
        'map' => 'Peta Saya',
        'public_students' => 'Search Mahasiswa',
        'public_logbooks' => 'Search Logbook',
        'public_map' => 'View Peta'
    ];
    return $map[$page] ?? 'Pengabdian: Logbook KKN';
}

function page_desc($page) {
    $map = [
        'home' => 'PoC Laragon berbasis PHP Native + MySQL.',
        'login' => 'Simulasi SSO Google untuk role mahasiswa.',
        'dashboard' => 'Ringkasan logbook, kesehatan, lokasi, dan masyarakat terlibat.',
        'data_kkn' => 'Biodata mahasiswa dan konsep input data KKN sebagai dropdown.',
        'logbook_form' => 'Input progress, kesehatan, lokasi, personal info, dan dokumentasi.',
        'my_logbooks' => 'Daftar logbook milik mahasiswa yang sedang login.',
        'map' => 'Peta lokasi berdasarkan logbook mahasiswa.',
        'public_students' => 'Role umum tanpa login: cari keseluruhan data mahasiswa.',
        'public_logbooks' => 'Role umum tanpa login: cari keseluruhan data logbook.',
        'public_map' => 'Role umum tanpa login: lihat sebaran lokasi logbook.'
    ];
    return $map[$page] ?? '';
}

function active($p) {
    global $page;
    return $page === $p ? 'active' : '';
}

function layout_start($title = null) {
    $u = current_user();
    global $page;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title><?= e($title ?: APP_NAME) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="assets/style.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</head>
<body>
<div class="shell">
  <aside class="sidebar">
    <div class="brand">
      <div class="logo">K</div>
      <div>
        <h1>Pengabdian:<br>Logbook KKN</h1>
        <p>PoC - Biro Transformasi Digital</p>
      </div>
    </div>

    <div class="role-card">
      <div class="small">Role Aktif</div>
      <?php if ($u): ?>
        <div class="name">Mahasiswa</div>
        <div class="desc"><?= e($u['name']) ?><br><?= e($u['email']) ?></div>
      <?php else: ?>
        <div class="name">Role Umum</div>
        <div class="desc">Tanpa login · view only · search keseluruhan data</div>
      <?php endif; ?>
    </div>

    <div class="nav-group">
      <div class="nav-title">Menu Utama</div>
      <nav class="menu">
        <a class="<?= active('home') ?>" href="index.php"><span class="icon">🏠</span> Overview</a>

        <?php if ($u): ?>
          <a class="<?= active('dashboard') ?>" href="index.php?page=dashboard"><span class="icon">📊</span> Dashboard</a>
          <a class="<?= active('data_kkn') ?>" href="index.php?page=data_kkn"><span class="icon">👤</span> Data KKN</a>
          <a class="<?= active('logbook_form') ?>" href="index.php?page=logbook_form"><span class="icon">📝</span> Input Logbook</a>
          <a class="<?= active('my_logbooks') ?>" href="index.php?page=my_logbooks"><span class="icon">📚</span> Logbook Saya</a>
          <a class="<?= active('map') ?>" href="index.php?page=map"><span class="icon">🗺️</span> Peta Saya</a>
          <a href="index.php?page=export"><span class="icon">⬇️</span> Export Laporan</a>
        <?php endif; ?>
      </nav>
    </div>

    <div class="nav-group">
      <div class="nav-title">Role Umum · Tanpa Login</div>
      <nav class="menu">
        <a class="<?= active('public_students') ?>" href="index.php?page=public_students"><span class="icon">🔎</span> Search Mahasiswa</a>
        <a class="<?= active('public_logbooks') ?>" href="index.php?page=public_logbooks"><span class="icon">📖</span> Search Logbook</a>
        <a class="<?= active('public_map') ?>" href="index.php?page=public_map"><span class="icon">📍</span> View Peta</a>
      </nav>
    </div>

    <div class="nav-group">
      <div class="nav-title">Akses</div>
      <nav class="menu">
        <?php if ($u): ?>
          <a href="index.php?page=logout"><span class="icon">🚪</span> Logout</a>
        <?php else: ?>
          <a class="<?= active('login') ?>" href="index.php?page=login"><span class="icon">🔐</span> Login SSO Google</a>
        <?php endif; ?>
      </nav>
    </div>
  </aside>

  <section class="content">
    <div class="topbar">
      <div>
        <h2><?= e(page_title($page)) ?></h2>
        <p><?= e(page_desc($page)) ?></p>
      </div>
      <div class="top-actions">
        <?php if ($u): ?>
          <a class="btn btn-soft" href="index.php?page=logbook_form">+ Input Logbook</a>
        <?php else: ?>
          <a class="btn btn-primary" href="index.php?page=login">Login Mahasiswa</a>
        <?php endif; ?>
      </div>
    </div>
    <main class="container">
      <?php flash(); ?>
<?php
}

function layout_end() {
?>
    </main>
    <div class="footer">PoC Logbook KKN · Sidebar navigation · Public search membaca keseluruhan data mahasiswa dan logbook.</div>
  </section>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</body>
</html>
<?php
}

function logbook_query($where = '', $params = []) {
    $sql = "
      SELECT l.*, s.name student_name, s.email, s.faculty, s.study_program,
             t.name theme_name, p.name program_name, a.name activity_type_name,
             loc.name location_name, loc.latitude, loc.longitude
      FROM logbooks l
      JOIN students s ON s.id=l.student_id
      JOIN themes t ON t.id=l.theme_id
      JOIN programs p ON p.id=l.program_id
      JOIN activity_types a ON a.id=l.activity_type_id
      JOIN locations loc ON loc.id=l.location_id
      $where
      ORDER BY l.log_date DESC
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

if ($page === 'home') {
    layout_start('Home');
    $totalStudents = db()->query("SELECT COUNT(*) c FROM students")->fetch()['c'];
    $totalLogs = db()->query("SELECT COUNT(*) c FROM logbooks")->fetch()['c'];
    $sick = db()->query("SELECT COUNT(*) c FROM logbooks WHERE health_status LIKE '%Sakit%'")->fetch()['c'];
    $loc = db()->query("SELECT COUNT(*) c FROM locations")->fetch()['c'];
?>
<section class="hero">
  <div>
    <h1>PoC Pengabdian: Logbook KKN</h1>
    <p>Versi ini menggunakan sidebar. Mahasiswa login untuk input logbook, sedangkan role umum tanpa login dapat melakukan search mahasiswa, search logbook, dan melihat peta.</p>
    <div class="hero-actions">
      <a class="btn btn-primary" href="index.php?page=login">Login Mahasiswa via SSO Google</a>
      <a class="btn btn-soft" href="index.php?page=public_students">Search Mahasiswa</a>
      <a class="btn btn-outline" href="index.php?page=public_logbooks">Search Logbook</a>
    </div>
  </div>
  <div class="grid grid-2">
    <div class="flow-box"><strong>1. Login SSO</strong><br><span>Mahasiswa masuk dengan simulasi Google SSO.</span></div>
    <div class="flow-box"><strong>2. Input Data</strong><br><span>Data baru masuk DB dan menjadi dropdown.</span></div>
    <div class="flow-box"><strong>3. Logbook</strong><br><span>Progress, kesehatan, lokasi, personal info.</span></div>
    <div class="flow-box"><strong>4. Role Umum</strong><br><span>Search seluruh mahasiswa, logbook, dan view peta.</span></div>
  </div>
</section>

<div class="grid grid-4" style="margin-top:20px">
  <div class="card kpi"><div class="label">Mahasiswa</div><div class="value"><?= e($totalStudents) ?></div><div class="note">keseluruhan data</div></div>
  <div class="card kpi"><div class="label">Logbook</div><div class="value"><?= e($totalLogs) ?></div><div class="note">tersimpan di DB</div></div>
  <div class="card kpi"><div class="label">Status Sakit</div><div class="value"><?= e($sick) ?></div><div class="note">monitoring kesehatan</div></div>
  <div class="card kpi"><div class="label">Lokasi</div><div class="value"><?= e($loc) ?></div><div class="note">muncul di peta</div></div>
</div>
<?php
    layout_end(); exit;
}

if ($page === 'login') {
    layout_start('Login');
    $students = db()->query("SELECT email, name FROM students ORDER BY name")->fetchAll();
?>
<div class="grid grid-2">
  <div class="card">
    <h2>Login Mahasiswa</h2>
    <p>PoC ini memakai simulasi SSO Google agar langsung bisa berjalan tanpa konfigurasi Google Cloud.</p>
    <form method="post">
      <input type="hidden" name="action" value="login_simulation">
      <div class="form-group">
        <label>Pilih Akun Google Demo</label>
        <select name="email" required>
          <?php foreach ($students as $s): ?>
            <option value="<?= e($s['email']) ?>"><?= e($s['name']) ?> — <?= e($s['email']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button class="btn btn-primary" type="submit">Login dengan Google SSO</button>
    </form>
  </div>
  <div class="card">
    <h3>Flow Login</h3>
    <div class="flow-box"><strong>Google SSO</strong><br>Mahasiswa login menggunakan akun Google.</div><br>
    <div class="flow-box"><strong>Ambil Profil</strong><br>Email dipetakan ke data mahasiswa.</div><br>
    <div class="flow-box"><strong>Dashboard</strong><br>Mahasiswa input data KKN dan logbook.</div>
  </div>
</div>
<?php
    layout_end(); exit;
}

if ($page === 'dashboard') {
    require_login();
    $u = current_user();
    layout_start('Dashboard');
    $stmt = db()->prepare("SELECT COUNT(*) c FROM logbooks WHERE student_id=?");
    $stmt->execute([$u['id']]); $myLogs = $stmt->fetch()['c'];
    $stmt = db()->prepare("SELECT COUNT(*) c FROM logbooks WHERE student_id=? AND health_status LIKE '%Sakit%'");
    $stmt->execute([$u['id']]); $mySick = $stmt->fetch()['c'];
    $stmt = db()->prepare("SELECT SUM(community_count) c FROM logbooks WHERE student_id=?");
    $stmt->execute([$u['id']]); $community = $stmt->fetch()['c'] ?: 0;
    $stmt = db()->prepare("SELECT COUNT(DISTINCT location_id) c FROM logbooks WHERE student_id=?");
    $stmt->execute([$u['id']]); $locations = $stmt->fetch()['c'];
    $rows = logbook_query("WHERE l.student_id=?", [$u['id']]);
?>
<div class="grid grid-4">
  <div class="card kpi"><div class="label">Logbook Saya</div><div class="value"><?= e($myLogs) ?></div><div class="note">total catatan</div></div>
  <div class="card kpi"><div class="label">Masyarakat Terlibat</div><div class="value"><?= e($community) ?></div><div class="note">akumulasi input</div></div>
  <div class="card kpi"><div class="label">Status Sakit</div><div class="value"><?= e($mySick) ?></div><div class="note">perlu monitoring</div></div>
  <div class="card kpi"><div class="label">Lokasi</div><div class="value"><?= e($locations) ?></div><div class="note">marker peta</div></div>
</div>

<div class="grid grid-2" style="margin-top:18px">
  <div class="card">
    <h2>Quick Action</h2>
    <p>Input data baru akan masuk DB dan menjadi opsi dropdown pada input berikutnya.</p>
    <div class="hero-actions">
      <a class="btn btn-primary" href="index.php?page=data_kkn">Lengkapi Data KKN</a>
      <a class="btn btn-soft" href="index.php?page=logbook_form">Input Logbook</a>
      <a class="btn btn-outline" href="index.php?page=map">Lihat Peta Saya</a>
    </div>
  </div>
  <div class="card">
    <h2>Personal Info</h2>
    <table>
      <tr><td>Nama</td><td><strong><?= e($u['name']) ?></strong></td></tr>
      <tr><td>TTL</td><td><?= e($u['birth_place']) ?>, <?= e($u['birth_date']) ?></td></tr>
      <tr><td>Fakultas</td><td><?= e($u['faculty']) ?></td></tr>
      <tr><td>Program Studi</td><td><?= e($u['study_program']) ?></td></tr>
    </table>
  </div>
</div>

<div class="card" style="margin-top:18px">
  <h2>Logbook Terbaru</h2>
  <?php render_logbook_table($rows); ?>
</div>
<?php
    layout_end(); exit;
}

if ($page === 'data_kkn') {
    require_login();
    $u = current_user();
    layout_start('Data KKN');
?>
<div class="grid grid-2">
  <div class="card">
    <h2>Biodata Mahasiswa</h2>
    <form method="post">
      <input type="hidden" name="action" value="save_profile">
      <div class="form-group"><label>Nama</label><input name="name" value="<?= e($u['name']) ?>" required></div>
      <div class="form-row">
        <div class="form-group"><label>Tempat Lahir</label><input name="birth_place" value="<?= e($u['birth_place']) ?>"></div>
        <div class="form-group"><label>Tanggal Lahir</label><input type="date" name="birth_date" value="<?= e($u['birth_date']) ?>"></div>
      </div>
      <div class="form-group"><label>Asal Fakultas / Sekolah</label><input name="faculty" value="<?= e($u['faculty']) ?>"></div>
      <div class="form-group"><label>Program Studi</label><input name="study_program" value="<?= e($u['study_program']) ?>"></div>
      <div class="form-row">
        <div class="form-group"><label>No HP</label><input name="phone" value="<?= e($u['phone']) ?>"></div>
        <div class="form-group"><label>Kontak Darurat</label><input name="emergency_contact" value="<?= e($u['emergency_contact']) ?>"></div>
      </div>
      <button class="btn btn-primary">Simpan Biodata</button>
    </form>
  </div>
  <div class="card">
    <h2>Data KKN Menjadi Dropdown</h2>
    <p>Data KKN seperti tema, program kerja, jenis kegiatan, dan lokasi diinput pada form logbook. Jika memilih input baru, data otomatis tersimpan ke database dan akan muncul sebagai pilihan dropdown berikutnya.</p>
    <div class="flow-box"><strong>Input Baru</strong> → <strong>Masuk DB</strong> → <strong>Jadi Dropdown</strong> → <strong>Dipakai Input Berikutnya</strong></div>
    <br>
    <a class="btn btn-soft" href="index.php?page=logbook_form">Coba Input Logbook</a>
  </div>
</div>
<?php
    layout_end(); exit;
}

if ($page === 'logbook_form') {
    require_login();
    layout_start('Input Logbook');
    $themes = master_options('themes');
    $programs = master_options('programs');
    $types = master_options('activity_types');
    $locations = master_options('locations');
?>
<div class="card">
  <h2>Input Logbook KKN</h2>
  <p>Field dengan input baru akan menyimpan data ke database dan menjadi opsi dropdown pada input berikutnya.</p>

  <form method="post">
    <input type="hidden" name="action" value="save_logbook">

    <div class="form-row">
      <div class="form-group"><label>Tanggal & Waktu</label><input type="datetime-local" name="log_date" value="<?= date('Y-m-d\TH:i') ?>" required></div>
      <div class="form-group"><label>Kondisi Kesehatan</label>
        <select name="health_status">
          <option>Normal</option>
          <option>Sakit Ringan</option>
          <option>Sakit - Perlu Pantauan</option>
          <option>Izin</option>
          <option>Kendala Lapangan</option>
        </select>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Tema KKN</label>
        <div class="master-inline">
          <select name="theme_name"><?php foreach ($themes as $t): ?><option><?= e($t['name']) ?></option><?php endforeach; ?></select>
          <input name="theme_new" placeholder="Input tema baru">
        </div>
        <div class="hint">Jika kolom input baru diisi, sistem akan memakai dan menyimpan data baru.</div>
      </div>
      <div class="form-group">
        <label>Program Kerja</label>
        <div class="master-inline">
          <select name="program_name"><?php foreach ($programs as $p): ?><option><?= e($p['name']) ?></option><?php endforeach; ?></select>
          <input name="program_new" placeholder="Input program baru">
        </div>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Jenis Kegiatan</label>
        <div class="master-inline">
          <select name="activity_type_name"><?php foreach ($types as $a): ?><option><?= e($a['name']) ?></option><?php endforeach; ?></select>
          <input name="activity_type_new" placeholder="Input jenis baru">
        </div>
      </div>
      <div class="form-group">
        <label>Lokasi KKN</label>
        <div class="master-inline">
          <select name="location_name"><?php foreach ($locations as $l): ?><option><?= e($l['name']) ?></option><?php endforeach; ?></select>
          <input name="location_new" placeholder="Input lokasi baru">
        </div>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group"><label>Pin Google Maps / Koordinat</label><input name="coordinate" placeholder="-7.7956, 110.3695" value="-7.7956, 110.3695"><div class="hint">Format: latitude, longitude</div></div>
      <div class="form-group"><label>Jumlah Masyarakat Terlibat</label><input type="number" name="community_count" value="0" min="0"></div>
    </div>

    <div class="form-group"><label>Progress / Catatan Kegiatan</label><textarea name="progress_note" required>Progress kegiatan hari ini...</textarea></div>
    <div class="form-group"><label>Personal Info</label><textarea name="personal_info">Kondisi pribadi, kendala, atau catatan penting...</textarea></div>
    <div class="form-group"><label>Dokumentasi</label><input name="documentation" placeholder="URL dokumentasi / nama file / catatan dokumentasi"></div>

    <div class="hero-actions">
      <button class="btn btn-outline" name="submit_type" value="draft">Simpan Draft</button>
      <button class="btn btn-primary" name="submit_type" value="submit">Submit Logbook</button>
    </div>
  </form>
</div>
<?php
    layout_end(); exit;
}

if ($page === 'my_logbooks') {
    require_login();
    $u = current_user();
    layout_start('Logbook Saya');
    $rows = logbook_query("WHERE l.student_id=?", [$u['id']]);
?>
<div class="card">
  <h2>Logbook Saya</h2>
  <?php render_logbook_table($rows); ?>
</div>
<?php
    layout_end(); exit;
}

if ($page === 'public_students') {
    layout_start('Search Mahasiswa');
    $q = trim($_GET['q'] ?? '');
    $faculty = trim($_GET['faculty'] ?? '');
    $params = [];
    $clauses = [];

    if ($q !== '') {
        $clauses[] = "(name LIKE ? OR email LIKE ? OR birth_place LIKE ? OR faculty LIKE ? OR study_program LIKE ? OR phone LIKE ?)";
        $params = array_merge($params, array_fill(0, 6, "%$q%"));
    }
    if ($faculty !== '') {
        $clauses[] = "faculty = ?";
        $params[] = $faculty;
    }

    $where = $clauses ? "WHERE " . implode(" AND ", $clauses) : '';
    $stmt = db()->prepare("SELECT * FROM students $where ORDER BY name ASC");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    $faculties = db()->query("SELECT DISTINCT faculty FROM students WHERE faculty IS NOT NULL AND faculty<>'' ORDER BY faculty ASC")->fetchAll();

    $totalAll = db()->query("SELECT COUNT(*) c FROM students")->fetch()['c'];
?>
<div class="card">
  <h2>Search Mahasiswa</h2>
  <p>Menu ini dapat diakses role umum tanpa login. Konten pencarian membaca <strong>keseluruhan data mahasiswa</strong> pada database.</p>

  <form method="get" class="form-row-3">
    <input type="hidden" name="page" value="public_students">
    <div class="form-group">
      <label>Keyword</label>
      <input name="q" value="<?= e($q) ?>" placeholder="Cari nama, email, TTL, fakultas, prodi, no HP...">
    </div>
    <div class="form-group">
      <label>Filter Fakultas</label>
      <select name="faculty">
        <option value="">Semua Fakultas</option>
        <?php foreach ($faculties as $f): ?>
          <option value="<?= e($f['faculty']) ?>" <?= $faculty===$f['faculty']?'selected':'' ?>><?= e($f['faculty']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn btn-primary">Cari</button>
  </form>
</div>

<div class="grid grid-3" style="margin-top:18px">
  <div class="card kpi"><div class="label">Total Mahasiswa DB</div><div class="value"><?= e($totalAll) ?></div><div class="note">keseluruhan data</div></div>
  <div class="card kpi"><div class="label">Hasil Pencarian</div><div class="value"><?= e(count($rows)) ?></div><div class="note">sesuai filter</div></div>
  <div class="card kpi"><div class="label">Mode</div><div class="value" style="font-size:24px">View Only</div><div class="note">tanpa login</div></div>
</div>

<div class="card" style="margin-top:18px">
  <h2>Hasil Data Mahasiswa</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Nama</th><th>Email</th><th>TTL</th><th>Fakultas</th><th>Program Studi</th><th>Kontak</th></tr></thead>
      <tbody>
        <?php if (!$rows): ?><tr><td colspan="6">Data mahasiswa tidak ditemukan.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><strong><?= e($r['name']) ?></strong></td>
            <td><?= e($r['email']) ?></td>
            <td><?= e($r['birth_place']) ?>, <?= e($r['birth_date']) ?></td>
            <td><?= e($r['faculty']) ?></td>
            <td><?= e($r['study_program']) ?></td>
            <td><?= e($r['phone']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php
    layout_end(); exit;
}

if ($page === 'public_logbooks') {
    layout_start('Search Logbook');
    $q = trim($_GET['q'] ?? '');
    $health = trim($_GET['health'] ?? '');
    $student = trim($_GET['student'] ?? '');
    $params = [];
    $clauses = [];

    if ($q !== '') {
        $clauses[] = "(s.name LIKE ? OR s.email LIKE ? OR s.faculty LIKE ? OR s.study_program LIKE ? OR t.name LIKE ? OR p.name LIKE ? OR a.name LIKE ? OR loc.name LIKE ? OR l.progress_note LIKE ? OR l.personal_info LIKE ?)";
        $params = array_merge($params, array_fill(0, 10, "%$q%"));
    }
    if ($health !== '') {
        $clauses[] = "l.health_status = ?";
        $params[] = $health;
    }
    if ($student !== '') {
        $clauses[] = "s.id = ?";
        $params[] = $student;
    }

    $where = $clauses ? "WHERE " . implode(" AND ", $clauses) : '';
    $rows = logbook_query($where, $params);
    $students = db()->query("SELECT id, name, email FROM students ORDER BY name ASC")->fetchAll();
?>
<div class="card">
  <h2>Search Logbook</h2>
  <p>Menu ini dapat diakses role umum tanpa login. Pencarian membaca keseluruhan data logbook beserta data mahasiswa, tema, program kerja, jenis kegiatan, lokasi, dan kesehatan.</p>
  <form method="get" class="form-row">
    <input type="hidden" name="page" value="public_logbooks">
    <div class="form-group">
      <label>Keyword</label>
      <input name="q" value="<?= e($q) ?>" placeholder="Cari mahasiswa, email, fakultas, tema, program, lokasi, catatan...">
    </div>
    <div class="form-group">
      <label>Mahasiswa</label>
      <select name="student">
        <option value="">Semua Mahasiswa</option>
        <?php foreach ($students as $s): ?>
          <option value="<?= e($s['id']) ?>" <?= $student==(string)$s['id']?'selected':'' ?>><?= e($s['name']) ?> — <?= e($s['email']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Kondisi</label>
      <select name="health">
        <option value="">Semua Kondisi</option>
        <?php foreach (['Normal','Sakit Ringan','Sakit - Perlu Pantauan','Izin','Kendala Lapangan'] as $h): ?>
          <option <?= $health===$h?'selected':'' ?>><?= e($h) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group" style="display:flex;align-items:end">
      <button class="btn btn-primary">Cari Logbook</button>
    </div>
  </form>
</div>

<div class="card" style="margin-top:18px">
  <h2>Hasil Search Logbook</h2>
  <?php render_logbook_table($rows); ?>
</div>
<?php
    layout_end(); exit;
}

if ($page === 'map' || $page === 'public_map') {
    $u = current_user();
    layout_start($page === 'map' ? 'Peta Saya' : 'View Peta');
    if ($page === 'map' && $u) {
        $rows = logbook_query("WHERE l.student_id=?", [$u['id']]);
    } else {
        $rows = logbook_query();
    }
    render_map($rows);
    layout_end(); exit;
}

function render_logbook_table($rows) {
?>
<div class="table-wrap">
<table>
  <thead>
    <tr>
      <th>Tanggal</th><th>Mahasiswa</th><th>Tema/Program</th><th>Lokasi</th><th>Masyarakat</th><th>Kondisi</th><th>Status</th><th>Catatan</th>
    </tr>
  </thead>
  <tbody>
  <?php if (!$rows): ?>
    <tr><td colspan="8">Data tidak ditemukan.</td></tr>
  <?php endif; ?>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= e(date('d M Y H:i', strtotime($r['log_date']))) ?></td>
      <td><strong><?= e($r['student_name']) ?></strong><br><small><?= e($r['email']) ?></small><br><small><?= e($r['faculty']) ?> · <?= e($r['study_program']) ?></small></td>
      <td><strong><?= e($r['theme_name']) ?></strong><br><?= e($r['program_name']) ?><br><small><?= e($r['activity_type_name']) ?></small></td>
      <td><?= e($r['location_name']) ?><br><small><?= e($r['latitude']) ?>, <?= e($r['longitude']) ?></small></td>
      <td><?= e($r['community_count']) ?></td>
      <td><?= health_pill($r['health_status']) ?></td>
      <td><?= status_pill($r['status']) ?></td>
      <td><?= e($r['progress_note']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php
}

function health_pill($h) {
    $class = 'pill-normal';
    if (stripos($h, 'Sakit') !== false) $class = 'pill-sick';
    if (stripos($h, 'Kendala') !== false || stripos($h, 'Izin') !== false) $class = 'pill-warn';
    return '<span class="pill ' . $class . '">' . e($h) . '</span>';
}

function status_pill($s) {
    $class = $s === 'Draft' ? 'pill-info' : ($s === 'Reviewed' ? 'pill-normal' : 'pill-warn');
    return '<span class="pill ' . $class . '">' . e($s) . '</span>';
}

function render_map($rows) {
    $markers = [];
    foreach ($rows as $r) {
        if ($r['latitude'] && $r['longitude']) {
            $markers[] = [
                'lat' => (float)$r['latitude'],
                'lng' => (float)$r['longitude'],
                'student' => $r['student_name'],
                'title' => $r['program_name'],
                'location' => $r['location_name'],
                'health' => $r['health_status'],
                'status' => $r['status'],
                'count' => $r['community_count'],
            ];
        }
    }
?>
<div class="grid grid-2">
  <div class="card">
    <h2>Peta Lokasi Logbook</h2>
    <p>Marker diambil dari lokasi yang diinput pada logbook. Role umum dapat melihat seluruh titik lokasi logbook tanpa login.</p>
  </div>
  <div class="card">
    <h2>Legenda</h2>
    <p><span class="pill pill-normal">Normal</span> <span class="pill pill-sick">Sakit</span> <span class="pill pill-warn">Izin/Kendala</span></p>
  </div>
</div>
<div class="card" style="margin-top:18px;padding:12px">
  <div id="map"></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
  const markers = <?= json_encode($markers) ?>;
  const map = L.map('map').setView([-7.81, 110.36], 11);
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap'
  }).addTo(map);

  function color(h) {
    if (h.includes('Sakit')) return '#E11D48';
    if (h.includes('Kendala') || h.includes('Izin')) return '#D97706';
    return '#176B9A';
  }

  const bounds = [];
  markers.forEach(m => {
    const marker = L.circleMarker([m.lat, m.lng], {
      radius: 10,
      color: '#fff',
      weight: 3,
      fillColor: color(m.health),
      fillOpacity: 0.9
    }).addTo(map);
    marker.bindPopup(`
      <b>${m.title}</b><br>
      ${m.student}<br>
      ${m.location}<br>
      Kondisi: ${m.health}<br>
      Masyarakat terlibat: ${m.count}<br>
      Status: ${m.status}
    `);
    bounds.push([m.lat, m.lng]);
  });

  if (bounds.length > 0) {
    map.fitBounds(bounds, {padding:[30,30]});
  }
});
</script>
<?php
}

layout_start('404');
echo '<div class="card"><h2>Halaman tidak ditemukan</h2></div>';
layout_end();
