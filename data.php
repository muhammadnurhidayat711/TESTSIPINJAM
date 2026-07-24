<?php
require_once __DIR__ . '/helpers.php';
include "koneksi.php";

/* Pastikan koneksi & output UTF-8 */
if (function_exists('mysqli_set_charset')) { @mysqli_set_charset($conn, 'utf8mb4'); }
if (!headers_sent()) {
  header('Content-Type: text/html; charset=UTF-8');
}
if (function_exists('mb_internal_encoding')) { @mb_internal_encoding('UTF-8'); }

/* =========================
   Ambil & normalisasi filter
   ========================= */
$status     = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : '';
$dari_str   = isset($_GET['dari'])   ? trim($_GET['dari'])   : '';
$sampai_str = isset($_GET['sampai']) ? trim($_GET['sampai']) : '';
$gedung     = isset($_GET['gedung']) ? trim($_GET['gedung']) : '';
$peminjam   = isset($_GET['peminjam']) ? trim($_GET['peminjam']) : '';

$dari_dt    = parse_date($dari_str);
$sampai_dt  = parse_date($sampai_str);
$dari_sql   = $dari_dt ? $dari_dt->format('Y-m-d') : '';
$sampai_sql = $sampai_dt ? $sampai_dt->format('Y-m-d') : '';

$filters = compact('status', 'dari_sql', 'sampai_sql', 'peminjam');

/* ======================================
   Build WHERE untuk 4 tipe booking
   ====================================== */
$whereHall   = buildWhere('pinjambarang', $filters, $conn);
$whereCar    = buildWhere('pinjamkendaraan', $filters, $conn);
$whereKolam  = buildWhere('pinjamkolam', $filters, $conn);
$whereStudio = buildWhere('pinjamstudio', $filters, $conn);

// Filter gedung khusus untuk pinjambarang
if ($gedung !== '') {
  $whereHall[] = "barang.nama_barang = " . qstr($conn, $gedung);
}

$sqlHall = "SELECT pinjambarang.*, user.nama_lengkap, barang.nama_barang
            FROM pinjambarang
            JOIN user   ON user.id = pinjambarang.id_user
            JOIN barang ON barang.id = pinjambarang.id_barang";
if ($whereHall) $sqlHall .= " WHERE " . implode(' AND ', $whereHall);
$sqlHall .= " ORDER BY pinjambarang.tgl_mulai DESC, pinjambarang.waktu_mulai DESC";
$qh = mysqli_query($conn, $sqlHall);
if (!$qh) die('Query error (hall): ' . htmlspecialchars(mysqli_error($conn), ENT_QUOTES, 'UTF-8'));

$sqlCar = "SELECT pinjamkendaraan.*, kendaraan.nama_kendaraan, user.nama_lengkap
           FROM pinjamkendaraan
           JOIN kendaraan ON kendaraan.id_kendaraan = pinjamkendaraan.id_kendaraan
           JOIN user      ON user.id = pinjamkendaraan.id_user";
if ($whereCar) $sqlCar .= " WHERE " . implode(' AND ', $whereCar);
$sqlCar .= " ORDER BY pinjamkendaraan.tgl_mulai DESC, pinjamkendaraan.waktu_mulai DESC";
$qc = mysqli_query($conn, $sqlCar);
if (!$qc) die('Query error (kendaraan): ' . htmlspecialchars(mysqli_error($conn), ENT_QUOTES, 'UTF-8'));

$sqlKolam = "SELECT pinjamkolam.*, kolam.jenis_kolam, user.nama_lengkap
             FROM pinjamkolam
             JOIN kolam ON kolam.id_kolam = pinjamkolam.id_kolam
             JOIN user  ON user.id = pinjamkolam.id_user";
if ($whereKolam) $sqlKolam .= " WHERE " . implode(' AND ', $whereKolam);
$sqlKolam .= " ORDER BY pinjamkolam.tgl_mulai DESC, pinjamkolam.waktu_mulai DESC";
$qk = mysqli_query($conn, $sqlKolam);
if (!$qk) die('Query error (kolam): ' . htmlspecialchars(mysqli_error($conn), ENT_QUOTES, 'UTF-8'));

$sqlStudio = "SELECT pinjamstudio.*, studio.jenis_studio, user.nama_lengkap
              FROM pinjamstudio
              JOIN studio ON studio.id_studio = pinjamstudio.id_studio
              JOIN user   ON user.id = pinjamstudio.id_user";
if ($whereStudio) $sqlStudio .= " WHERE " . implode(' AND ', $whereStudio);
$sqlStudio .= " ORDER BY pinjamstudio.tgl_mulai DESC, pinjamstudio.waktu_mulai DESC";
$qs = mysqli_query($conn, $sqlStudio);
if (!$qs) die('Query error (studio): ' . htmlspecialchars(mysqli_error($conn), ENT_QUOTES, 'UTF-8'));

/* =========================
   Data dropdown nama gedung
   ========================= */
$gedungOpts = [];
$rg = mysqli_query($conn, "SELECT DISTINCT nama_barang FROM barang ORDER BY nama_barang ASC");
if($rg){ while($r = mysqli_fetch_assoc($rg)){ $gedungOpts[] = $r['nama_barang']; } }

/* =========================
   Datalist Nama Peminjam
   ========================= */
$peminjamOpts = [];
$ru = mysqli_query($conn, "SELECT DISTINCT nama_lengkap FROM user WHERE nama_lengkap IS NOT NULL AND nama_lengkap<>'' ORDER BY nama_lengkap ASC");
if($ru){ while($r = mysqli_fetch_assoc($ru)){ $peminjamOpts[] = $r['nama_lengkap']; } }

/* ========================================
   DATA UNTUK KALENDER (SEMUA TABEL + RUTIN)
   ======================================== */
$APPROVED = ['approve','approved','acc','disetujui','setuju','selesai','active','dipinjam'];
$PENDING  = ['menunggu','pending','submitted','waiting'];
$APPROVED_SQL = "'" . implode("','", $APPROVED) . "'";
$PENDING_SQL  = "'" . implode("','", $PENDING) . "'";

$bookings_query = "
  -- RUANGAN
  SELECT 
    'ruangan' AS type,
    pinjambarang.tgl_mulai,
    pinjambarang.tgl_selesai,
    pinjambarang.waktu_mulai,
    pinjambarang.waktu_selesai,
    IFNULL(barang.nama_barang, '-') AS item_name,
    IFNULL(user.nama_lengkap, 'User') AS user_name,
    IFNULL(pinjambarang.is_recurring,'no')  AS is_recurring,
    IFNULL(pinjambarang.recurring_days,'')  AS recurring_days,
    pinjambarang.status,
    pinjambarang.tujuan_barang AS tujuan,
    '🏢' AS icon
  FROM pinjambarang
  LEFT JOIN barang ON barang.id = pinjambarang.id_barang
  LEFT JOIN user   ON user.id = pinjambarang.id_user

  UNION ALL

  -- KENDARAAN
  SELECT 
    'kendaraan' AS type,
    pinjamkendaraan.tgl_mulai,
    pinjamkendaraan.tgl_selesai,
    pinjamkendaraan.waktu_mulai,
    pinjamkendaraan.waktu_selesai,
    IFNULL(kendaraan.nama_kendaraan, '-') AS item_name,
    IFNULL(user.nama_lengkap, 'User') AS user_name,
    IFNULL(pinjamkendaraan.is_recurring,'no')   AS is_recurring,
    IFNULL(pinjamkendaraan.recurring_days,'')   AS recurring_days,
    pinjamkendaraan.status,
    pinjamkendaraan.tujuan AS tujuan,
    '🚗' AS icon
  FROM pinjamkendaraan
  LEFT JOIN kendaraan ON kendaraan.id_kendaraan = pinjamkendaraan.id_kendaraan
  LEFT JOIN user      ON user.id = pinjamkendaraan.id_user

  UNION ALL

  -- KOLAM
  SELECT 
    'kolam' AS type,
    pinjamkolam.tgl_mulai,
    pinjamkolam.tgl_selesai,
    pinjamkolam.waktu_mulai,
    pinjamkolam.waktu_selesai,
    IFNULL(kolam.jenis_kolam, '-') AS item_name,
    IFNULL(user.nama_lengkap, 'User') AS user_name,
    IFNULL(pinjamkolam.is_recurring,'no')  AS is_recurring,
    IFNULL(pinjamkolam.recurring_days,'')  AS recurring_days,
    pinjamkolam.status,
    pinjamkolam.tujuan AS tujuan,
    '🏊' AS icon
  FROM pinjamkolam
  LEFT JOIN kolam ON kolam.id_kolam = pinjamkolam.id_kolam
  LEFT JOIN user  ON user.id = pinjamkolam.id_user

  UNION ALL

  -- STUDIO
  SELECT 
    'studio' AS type,
    pinjamstudio.tgl_mulai,
    pinjamstudio.tgl_selesai,
    pinjamstudio.waktu_mulai,
    pinjamstudio.waktu_selesai,
    IFNULL(studio.jenis_studio, '-') AS item_name,
    IFNULL(user.nama_lengkap, 'User') AS user_name,
    IFNULL(pinjamstudio.is_recurring,'no')   AS is_recurring,
    IFNULL(pinjamstudio.recurring_days,'')   AS recurring_days,
    pinjamstudio.status,
    pinjamstudio.deskripsi_peminjaman AS tujuan,
    '🎬' AS icon
  FROM pinjamstudio
  LEFT JOIN studio ON studio.id_studio = pinjamstudio.id_studio
  LEFT JOIN user   ON user.id = pinjamstudio.id_user
";

$bookings_result = mysqli_query($conn, $bookings_query);
$all_bookings = [];
if ($bookings_result) {
  while($row = mysqli_fetch_assoc($bookings_result)) {
    $isRecurringValue = strtolower(trim($row['is_recurring'] ?? 'no'));
    $isRecurring = in_array($isRecurringValue, ['yes','y','1'], true);

    $all_bookings[] = [
      'type'          => $row['type'],
      'tgl_mulai'     => $row['tgl_mulai'],
      'tgl_selesai'   => $row['tgl_selesai'],
      'waktu_mulai'   => $row['waktu_mulai'],
      'waktu_selesai' => $row['waktu_selesai'],
      'item_name'     => $row['item_name'],
      'user_name'     => $row['user_name'],
      'is_recurring'  => $isRecurring,
      'recurring_days'=> $row['recurring_days'] ?? '',
      'status'        => $row['status'],
      'tujuan'        => $row['tujuan'],
      'icon'          => $row['icon'],
    ];
  }
}

// Build list jadwal mendatang (7 hari ke depan)
$today = new DateTime('today');
$weekLater = (clone $today)->modify('+7 days');
$upcoming_list = [];

foreach($all_bookings as $b){
  $start = $b['tgl_mulai'] ? DateTime::createFromFormat('Y-m-d', $b['tgl_mulai']) : null;
  $end   = $b['tgl_selesai'] ? DateTime::createFromFormat('Y-m-d', $b['tgl_selesai']) : $start;

  if($start && $end){
    if($start >= $today && $start <= $weekLater){
      $upcoming_list[] = $b;
    }
  }
}

// Sort by date ascending
usort($upcoming_list, function($a, $b){
  return strcmp($a['tgl_mulai'], $b['tgl_mulai']);
});

// Build list jadwal rutin
$recurring_list = array_filter($all_bookings, function($b){
  return $b['is_recurring'] === true;
});

// Limit display
$upcoming_list_display = array_slice($upcoming_list, 0, 10);
$recurring_list_display = array_slice($recurring_list, 0, 10);

$all_bookings_json = json_encode($all_bookings, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>SIPINJAM - Data Peminjaman</title>
  <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <script src="assets/js/app.js"></script>
  <link rel="stylesheet" href="assets/css/data.css">
</head>
<body>

<!-- ========== HEADER ========== -->
<div class="header">
  <div class="header-content">
    <div class="brand">
      <div class="brand-icon">📋</div>
      <div>
        <h1>SIPINJAM</h1>
        <div class="brand-subtitle">Sistem Informasi Peminjaman Pelita Cemerlang School</div>
      </div>
    </div>
    <a href="index.php" class="btn-login">
      <i class="fas fa-sign-in-alt"></i>
      <span>Login</span>
    </a>
  </div>
</div>

<div class="container">

  <!-- ========== KALENDER & JADWAL ========== -->
  <div class="calendar-wrapper">
    <!-- LEFT: KALENDER -->
    <div class="calendar-section">
      <div class="calendar-header">
        <div class="calendar-title">
          <i class="fas fa-calendar-alt"></i>
          Kalender
        </div>
        <div class="calendar-controls">
          <button onclick="prevMonth()"><i class="fas fa-chevron-left"></i></button>
          <span class="calendar-month-label" id="calendarMonth"></span>
          <button onclick="nextMonth()"><i class="fas fa-chevron-right"></i></button>
        </div>
      </div>
      
      <div class="calendar-grid" id="calendarGrid"></div>
      
      <div class="calendar-legend">
        <span><div class="legend-box has-booking"></div> Jadwal Biasa</span>
        <span><div class="legend-box has-recurring"></div> Jadwal Rutin</span>
      </div>
    </div>

    <!-- RIGHT: JADWAL LISTS -->
    <div class="schedule-lists">
      <!-- Jadwal Mendatang (7 Hari) -->
      <div class="schedule-list-section">
        <div class="schedule-list-header">
          <div class="schedule-list-title">
            <i class="fas fa-calendar-week"></i>
            Jadwal Mendatang
          </div>
          <span class="schedule-count"><?php echo count($upcoming_list_display); ?></span>
        </div>
        <div class="schedule-items">
          <?php if(empty($upcoming_list_display)): ?>
            <div class="empty-schedule">
              <i class="fas fa-calendar-times"></i>
              <div style="font-size: clamp(11px, 2.2vw, 12px);">Tidak ada jadwal 7 hari ke depan</div>
            </div>
          <?php else: ?>
            <?php foreach($upcoming_list_display as $item): 
              $st = strtolower($item['status']);
              $statusClass = (in_array($st, ['menunggu','pending'])) ? 'menunggu' : 'approve';
            ?>
            <div class="schedule-item <?php echo h($item['type']); ?>">
              <div class="schedule-item-header">
                <div class="schedule-item-name">
                  <?php echo $item['icon']; ?> <?php echo h($item['item_name']); ?>
                  <?php if($item['is_recurring']): ?>
                    <span class="recurring-label"><i class="fas fa-redo"></i> Rutin</span>
                  <?php endif; ?>
                </div>
                <span class="schedule-badge <?php echo $statusClass; ?>"><?php echo h($item['status']); ?></span>
              </div>
              <div class="schedule-item-meta">
                <div><i class="fas fa-calendar"></i> <?php echo fmt_date($item['tgl_mulai']); ?></div>
                <div><i class="fas fa-clock"></i> <?php echo fmt_time($item['waktu_mulai']).' - '.fmt_time($item['waktu_selesai']); ?></div>
                <div><i class="fas fa-user"></i> <?php echo h($item['user_name']); ?></div>
                <?php if (!empty($item['tujuan'])): ?>
                  <div><i class="fas fa-bullseye"></i> <?php echo h($item['tujuan']); ?></div>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Jadwal Rutin -->
      <div class="schedule-list-section">
        <div class="schedule-list-header">
          <div class="schedule-list-title">
            <i class="fas fa-redo"></i>
            Jadwal Rutin
          </div>
          <span class="schedule-count"><?php echo count($recurring_list_display); ?></span>
        </div>
        <div class="schedule-items">
          <?php if(empty($recurring_list_display)): ?>
            <div class="empty-schedule">
              <i class="fas fa-calendar-check"></i>
              <div style="font-size: clamp(11px, 2.2vw, 12px);">Tidak ada jadwal rutin</div>
            </div>
          <?php else: ?>
            <?php foreach($recurring_list_display as $item): 
              $st = strtolower($item['status']);
              $statusClass = (in_array($st, ['menunggu','pending'])) ? 'menunggu' : 'approve';
            ?>
            <div class="schedule-item <?php echo h($item['type']); ?>">
              <div class="schedule-item-header">
                <div class="schedule-item-name">
                  <?php echo $item['icon']; ?> <?php echo h($item['item_name']); ?>
                  <span class="recurring-label"><i class="fas fa-redo"></i> Rutin</span>
                </div>
                <span class="schedule-badge <?php echo $statusClass; ?>"><?php echo h($item['status']); ?></span>
              </div>
              <div class="schedule-item-meta">
                <div><i class="fas fa-calendar"></i> <?php echo fmt_date($item['tgl_mulai']).' - '.fmt_date($item['tgl_selesai']); ?></div>
                <div><i class="fas fa-clock"></i> <?php echo fmt_time($item['waktu_mulai']).' - '.fmt_time($item['waktu_selesai']); ?></div>
                <div><i class="fas fa-user"></i> <?php echo h($item['user_name']); ?></div>
                <?php if($item['recurring_days']): ?>
                  <div style="color:#a855f7;"><i class="fas fa-repeat"></i> <?php echo label_hari_recurring($item['recurring_days']); ?></div>
                <?php endif; ?>
                <?php if (!empty($item['tujuan'])): ?>
                  <div><i class="fas fa-bullseye"></i> <?php echo h($item['tujuan']); ?></div>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- ========== FILTER CARD ========== -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <span class="card-title-icon">🔍</span>
        Filter Peminjaman
      </div>
    </div>
    <div class="card-body">
      <form method="GET" action="">
        <div class="filter-grid">
          <div class="form-group">
            <label class="form-label">Dari Tanggal</label>
            <input type="date" name="dari" class="form-control" value="<?php echo h($dari_str); ?>" />
          </div>
          <div class="form-group">
            <label class="form-label">Sampai Tanggal</label>
            <input type="date" name="sampai" class="form-control" value="<?php echo h($sampai_str); ?>" />
          </div>
          <div class="form-group">
            <label class="form-label">Gedung</label>
            <select name="gedung" class="form-control">
              <option value="">Semua Gedung</option>
              <?php foreach($gedungOpts as $opt): ?>
                <option value="<?php echo h($opt); ?>" <?php echo ($gedung === $opt) ? 'selected' : ''; ?>>
                  <?php echo h($opt); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Peminjam</label>
            <input type="text" name="peminjam" class="form-control" list="peminjam-list" value="<?php echo h($peminjam); ?>" placeholder="Cari nama peminjam..." />
            <datalist id="peminjam-list">
              <?php foreach($peminjamOpts as $opt): ?>
                <option value="<?php echo h($opt); ?>">
              <?php endforeach; ?>
            </datalist>
          </div>
        </div>
        
        <div style="margin-top: 14px;">
          <label class="form-label" style="margin-bottom: 10px; display: block;">Status Peminjaman</label>
          <div class="status-pills">
            <label class="pill <?php echo ($status === '' || $status === 'semua') ? 'active' : ''; ?>">
              <input type="radio" name="status" value="semua" style="display: none;" <?php echo ($status === '' || $status === 'semua') ? 'checked' : ''; ?> onchange="this.form.submit()" />
              Semua
            </label>
            <label class="pill <?php echo ($status === 'menunggu') ? 'active' : ''; ?>">
              <input type="radio" name="status" value="menunggu" style="display: none;" <?php echo ($status === 'menunggu') ? 'checked' : ''; ?> onchange="this.form.submit()" />
              Menunggu
            </label>
            <label class="pill <?php echo ($status === 'approve') ? 'active' : ''; ?>">
              <input type="radio" name="status" value="approve" style="display: none;" <?php echo ($status === 'approve') ? 'checked' : ''; ?> onchange="this.form.submit()" />
              Disetujui
            </label>
            <label class="pill <?php echo ($status === 'selesai') ? 'active' : ''; ?>">
              <input type="radio" name="status" value="selesai" style="display: none;" <?php echo ($status === 'selesai') ? 'checked' : ''; ?> onchange="this.form.submit()" />
              Selesai
            </label>
          </div>
        </div>
        
        <div class="btn-actions" style="margin-top: 16px;">
          <button type="submit" class="btn btn-primary">🔍 Terapkan Filter</button>
          <a href="?" class="btn btn-secondary">🔄 Reset Filter</a>
        </div>
      </form>
    </div>
  </div>

  <!-- ========== DATA PEMINJAMAN (TABS & TABLES) ========== -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <span class="card-title-icon">📊</span>
        Data Peminjaman
      </div>
    </div>
    <div class="card-body">
      
      <!-- TABS -->
      <div class="tabs">
        <button class="tab-btn active" onclick="switchTab(event, 'tab-hall')">🏢 Ruangan</button>
        <button class="tab-btn" onclick="switchTab(event, 'tab-car')">🚗 Kendaraan</button>
        <button class="tab-btn" onclick="switchTab(event, 'tab-pool')">🏊 Kolam</button>
        <button class="tab-btn" onclick="switchTab(event, 'tab-studio')">🎬 Studio</button>
      </div>

      <!-- TAB RUANGAN -->
      <div id="tab-hall" class="tab-content active">
        <div class="search-box">
          <span class="search-icon">🔍</span>
          <input type="text" class="search-input" id="search-hall" placeholder="Cari nama gedung, peminjam, atau tujuan..." onkeyup="searchTable('search-hall', 'table-hall')" />
        </div>
        
        <div class="table-wrapper">
          <table id="table-hall">
            <thead>
              <tr>
                <th>No</th>
                <th>Gedung</th>
                <th>Peminjam</th>
                <th>Tanggal</th>
                <th>Waktu</th>
                <th>Durasi</th>
                <th>Tujuan</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $no = 1;
              $hasData = false;
              while($row = mysqli_fetch_assoc($qh)): 
                $hasData = true;
                $st = strtolower($row['status']);
                $rowClass = '';
                if(in_array($st, $PENDING)) $rowClass = 'row-pending';
                elseif(in_array($st, $APPROVED)) $rowClass = 'row-approve';
                elseif($st === 'selesai') $rowClass = 'row-selesai';
                
                $badgeClass = 'badge-pending';
                if(in_array($st, $APPROVED)) $badgeClass = 'badge-approve';
                elseif($st === 'selesai') $badgeClass = 'badge-selesai';
              ?>
              <tr class="<?php echo $rowClass; ?>">
                <td><?php echo $no++; ?></td>
                <td class="font-bold"><?php echo h($row['nama_barang']); ?></td>
                <td><?php echo h($row['nama_lengkap']); ?></td>
                <td class="nowrap"><?php echo fmt_date($row['tgl_mulai']).' - '.fmt_date($row['tgl_selesai']); ?></td>
                <td class="nowrap"><?php echo fmt_time($row['waktu_mulai']).' - '.fmt_time($row['waktu_selesai']); ?></td>
                <td><?php echo durasi_jam($row['tgl_mulai'], $row['waktu_mulai'], $row['tgl_selesai'], $row['waktu_selesai']); ?></td>
                <td><?php echo h($row['tujuan_barang']); ?></td>
                <td><span class="badge <?php echo $badgeClass; ?>"><?php echo h($row['status']); ?></span></td>
              </tr>
              <?php endwhile; ?>
              <?php if(!$hasData): ?>
              <tr>
                <td colspan="8">
                  <div class="empty-state">
                    <div class="empty-icon">📭</div>
                    <div>Tidak ada data peminjaman ruangan</div>
                  </div>
                </td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- TAB KENDARAAN -->
      <div id="tab-car" class="tab-content">
        <div class="search-box">
          <span class="search-icon">🔍</span>
          <input type="text" class="search-input" id="search-car" placeholder="Cari nama kendaraan, peminjam, atau tujuan..." onkeyup="searchTable('search-car', 'table-car')" />
        </div>
        
        <div class="table-wrapper">
          <table id="table-car">
            <thead>
              <tr>
                <th>No</th>
                <th>Kendaraan</th>
                <th>Peminjam</th>
                <th>Tanggal</th>
                <th>Waktu</th>
                <th>Durasi</th>
                <th>Tujuan</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $no = 1;
              $hasData = false;
              while($row = mysqli_fetch_assoc($qc)): 
                $hasData = true;
                $st = strtolower($row['status']);
                $rowClass = '';
                if(in_array($st, $PENDING)) $rowClass = 'row-pending';
                elseif(in_array($st, $APPROVED)) $rowClass = 'row-approve';
                elseif($st === 'selesai') $rowClass = 'row-selesai';
                
                $badgeClass = 'badge-pending';
                if(in_array($st, $APPROVED)) $badgeClass = 'badge-approve';
                elseif($st === 'selesai') $badgeClass = 'badge-selesai';
              ?>
              <tr class="<?php echo $rowClass; ?>">
                <td><?php echo $no++; ?></td>
                <td class="font-bold"><?php echo h($row['nama_kendaraan']); ?></td>
                <td><?php echo h($row['nama_lengkap']); ?></td>
                <td class="nowrap"><?php echo fmt_date($row['tgl_mulai']).' - '.fmt_date($row['tgl_selesai']); ?></td>
                <td class="nowrap"><?php echo fmt_time($row['waktu_mulai']).' - '.fmt_time($row['waktu_selesai']); ?></td>
                <td><?php echo durasi_jam($row['tgl_mulai'], $row['waktu_mulai'], $row['tgl_selesai'], $row['waktu_selesai']); ?></td>
                <td><?php echo h($row['tujuan']); ?></td>
                <td><span class="badge <?php echo $badgeClass; ?>"><?php echo h($row['status']); ?></span></td>
              </tr>
              <?php endwhile; ?>
              <?php if(!$hasData): ?>
              <tr>
                <td colspan="8">
                  <div class="empty-state">
                    <div class="empty-icon">📭</div>
                    <div>Tidak ada data peminjaman kendaraan</div>
                  </div>
                </td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- TAB KOLAM -->
      <div id="tab-pool" class="tab-content">
        <div class="search-box">
          <span class="search-icon">🔍</span>
          <input type="text" class="search-input" id="search-pool" placeholder="Cari jenis kolam, peminjam, atau tujuan..." onkeyup="searchTable('search-pool', 'table-pool')" />
        </div>
        
        <div class="table-wrapper">
          <table id="table-pool">
            <thead>
              <tr>
                <th>No</th>
                <th>Jenis Kolam</th>
                <th>Peminjam</th>
                <th>Tanggal</th>
                <th>Waktu</th>
                <th>Durasi</th>
                <th>Tujuan</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $no = 1;
              $hasData = false;
              while($row = mysqli_fetch_assoc($qk)): 
                $hasData = true;
                $st = strtolower($row['status']);
                $rowClass = '';
                if(in_array($st, $PENDING)) $rowClass = 'row-pending';
                elseif(in_array($st, $APPROVED)) $rowClass = 'row-approve';
                elseif($st === 'selesai') $rowClass = 'row-selesai';
                
                $badgeClass = 'badge-pending';
                if(in_array($st, $APPROVED)) $badgeClass = 'badge-approve';
                elseif($st === 'selesai') $badgeClass = 'badge-selesai';
              ?>
              <tr class="<?php echo $rowClass; ?>">
                <td><?php echo $no++; ?></td>
                <td class="font-bold"><?php echo h($row['jenis_kolam']); ?></td>
                <td><?php echo h($row['nama_lengkap']); ?></td>
                <td class="nowrap"><?php echo fmt_date($row['tgl_mulai']).' - '.fmt_date($row['tgl_selesai']); ?></td>
                <td class="nowrap"><?php echo fmt_time($row['waktu_mulai']).' - '.fmt_time($row['waktu_selesai']); ?></td>
                <td><?php echo durasi_jam($row['tgl_mulai'], $row['waktu_mulai'], $row['tgl_selesai'], $row['waktu_selesai']); ?></td>
                <td><?php echo h($row['tujuan']); ?></td>
                <td><span class="badge <?php echo $badgeClass; ?>"><?php echo h($row['status']); ?></span></td>
              </tr>
              <?php endwhile; ?>
              <?php if(!$hasData): ?>
              <tr>
                <td colspan="8">
                  <div class="empty-state">
                    <div class="empty-icon">📭</div>
                    <div>Tidak ada data peminjaman kolam renang</div>
                  </div>
                </td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- TAB STUDIO -->
      <div id="tab-studio" class="tab-content">
        <div class="search-box">
          <span class="search-icon">🔍</span>
          <input type="text" class="search-input" id="search-studio" placeholder="Cari jenis studio, peminjam, atau tujuan/deskripsi..." onkeyup="searchTable('search-studio', 'table-studio')" />
        </div>
        
        <div class="table-wrapper">
          <table id="table-studio">
            <thead>
              <tr>
                <th>No</th>
                <th>Jenis Studio</th>
                <th>Peminjam</th>
                <th>Tanggal</th>
                <th>Waktu</th>
                <th>Durasi</th>
                <th>Tujuan / Deskripsi</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $no = 1;
              $hasData = false;
              while($row = mysqli_fetch_assoc($qs)): 
                $hasData = true;
                $st = strtolower($row['status']);
                $rowClass = '';
                if(in_array($st, $PENDING)) $rowClass = 'row-pending';
                elseif(in_array($st, $APPROVED)) $rowClass = 'row-approve';
                elseif($st === 'selesai') $rowClass = 'row-selesai';
                
                $badgeClass = 'badge-pending';
                if(in_array($st, $APPROVED)) $badgeClass = 'badge-approve';
                elseif($st === 'selesai') $badgeClass = 'badge-selesai';
              ?>
              <tr class="<?php echo $rowClass; ?>">
                <td><?php echo $no++; ?></td>
                <td class="font-bold"><?php echo h($row['jenis_studio']); ?></td>
                <td><?php echo h($row['nama_lengkap']); ?></td>
                <td class="nowrap"><?php echo fmt_date($row['tgl_mulai']).' - '.fmt_date($row['tgl_selesai']); ?></td>
                <td class="nowrap"><?php echo fmt_time($row['waktu_mulai']).' - '.fmt_time($row['waktu_selesai']); ?></td>
                <td><?php echo durasi_jam($row['tgl_mulai'], $row['waktu_mulai'], $row['tgl_selesai'], $row['waktu_selesai']); ?></td>
                <td><?php echo h($row['deskripsi_peminjaman']); ?></td>
                <td><span class="badge <?php echo $badgeClass; ?>"><?php echo h($row['status']); ?></span></td>
              </tr>
              <?php endwhile; ?>
              <?php if(!$hasData): ?>
              <tr>
                <td colspan="8">
                  <div class="empty-state">
                    <div class="empty-icon">📭</div>
                    <div>Tidak ada data peminjaman studio</div>
                  </div>
                </td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>

</div>

<!-- ========== MODAL DETAIL PEMINJAMAN ========== -->
<div class="modal-calendar" id="bookingModal">
  <div class="modal-content-cal">
    <div class="modal-header-cal">
      <h2 id="modalTitle">Detail Peminjaman</h2>
      <button class="modal-close-cal" onclick="closeModal()">×</button>
    </div>
    <div id="modalBody"></div>
  </div>
</div>

<script>
// Data booking dari PHP
const bookings = <?php echo $all_bookings_json; ?>;
let currentDate = new Date();

const monthNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
const dayNames = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
const dayNameMap = {1:'Senin',2:'Selasa',3:'Rabu',4:'Kamis',5:'Jumat',6:'Sabtu',7:'Minggu'};

function jsToDayNumber(jsDay) { 
  return jsDay === 0 ? 7 : jsDay; 
}

// Escape HTML untuk teks dari DB (termasuk tujuan)
function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function renderCalendar() {
  const year = currentDate.getFullYear();
  const month = currentDate.getMonth();
  
  document.getElementById('calendarMonth').textContent = `${monthNames[month]} ${year}`;
  
  const firstDay = new Date(year, month, 1).getDay();
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const daysInPrevMonth = new Date(year, month, 0).getDate();
  
  const grid = document.getElementById('calendarGrid');
  grid.innerHTML = '';
  
  // Header hari
  dayNames.forEach(day => {
    const header = document.createElement('div');
    header.className = 'calendar-day-header';
    header.textContent = day;
    grid.appendChild(header);
  });
  
  // Hari bulan sebelumnya
  for (let i = firstDay - 1; i >= 0; i--) {
    const day = document.createElement('div');
    day.className = 'calendar-day other-month';
    day.textContent = daysInPrevMonth - i;
    grid.appendChild(day);
  }
  
  const today = new Date();
  for (let i = 1; i <= daysInMonth; i++) {
    const day = document.createElement('div');
    day.className = 'calendar-day';
    
    const isToday = (
      i === today.getDate() &&
      month === today.getMonth() &&
      year === today.getFullYear()
    );
    if (isToday) day.classList.add('today');
    
    const dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(i).padStart(2,'0')}`;
    const dateObj = new Date(year, month, i);
    const dayBookings = getBookingsForDate(dateStr, dateObj);
    
    const normalBookings = dayBookings.filter(b => !b.is_recurring);
    const recurringBookings = dayBookings.filter(b => b.is_recurring);
    
    if (normalBookings.length > 0) day.classList.add('has-booking');
    if (recurringBookings.length > 0) day.classList.add('has-recurring');
    
    if (dayBookings.length > 0) {
      const dayContent = document.createElement('div');
      dayContent.style.position = 'relative';
      dayContent.style.zIndex = '1';
      dayContent.textContent = i;
      day.appendChild(dayContent);
      
      const badge = document.createElement('div');
      badge.className = 'booking-badge';
      badge.textContent = dayBookings.length;
      day.appendChild(badge);
      
      day.onclick = () => showModal(dateStr, dayBookings);
    } else {
      day.textContent = i;
    }
    
    grid.appendChild(day);
  }
  
  // Sisa kotak bulan berikutnya
  const totalCells = 7 + firstDay + daysInMonth;
  const remainingDays = (Math.ceil(totalCells / 7) * 7) - totalCells;
  for (let i = 1; i <= remainingDays; i++) {
    const day = document.createElement('div');
    day.className = 'calendar-day other-month';
    day.textContent = i;
    grid.appendChild(day);
  }
}

function getBookingsForDate(dateStr, dateObj) {
  const checkDate = new Date(dateStr);
  const dayNumber = jsToDayNumber(dateObj.getDay());
  
  return bookings.filter(b => {
    if (!b.tgl_mulai) return false;
    
    const start = new Date(String(b.tgl_mulai).split(' ')[0]);
    const end = b.tgl_selesai ? new Date(String(b.tgl_selesai).split(' ')[0]) : start;
    const inRange = (checkDate >= start && checkDate <= end);
    
    if (!inRange) return false;
    
    if (!b.is_recurring) return true;
    
    if (!b.recurring_days) return false;
    
    const allowedDays = b.recurring_days.split(',').map(d => parseInt(d.trim(), 10));
    return allowedDays.includes(dayNumber);
  });
}

function formatRecurringDays(daysStr) {
  if (!daysStr) return '-';
  return daysStr
    .split(',')
    .map(d => dayNameMap[parseInt(d.trim(), 10)] || d)
    .join(', ');
}

function showModal(dateStr, list) {
  const modal = document.getElementById('bookingModal');
  const title = document.getElementById('modalTitle');
  const body = document.getElementById('modalBody');
  
  const d = new Date(dateStr);
  const formatted = `${d.getDate()} ${monthNames[d.getMonth()]} ${d.getFullYear()}`;
  title.textContent = `Peminjaman pada ${formatted}`;
  
  if (!list || list.length === 0) {
    body.innerHTML = '<p style="text-align:center;color:#94a3b8;padding:30px;">Tidak ada peminjaman</p>';
  } else {
    body.innerHTML = list.map(b => {
      const statusRaw = String(b.status || '').toLowerCase();
      const statusClass = (statusRaw === 'menunggu' || statusRaw === 'pending') ? 'menunggu' : 'approve';
      const recurringInfo = (b.is_recurring && b.recurring_days)
        ? `<div style="font-size:12px;color:#a855f7;margin-top:8px;">
             <i class="fas fa-redo"></i> Setiap: ${formatRecurringDays(b.recurring_days)}
           </div>`
        : '';
      const tujuanText = (b.tujuan && String(b.tujuan).trim() !== '') ? escapeHtml(b.tujuan) : '-';
      const tujuanLine = `
        <div style="font-size:12px;color:#475569;margin-top:6px;display:flex;align-items:center;gap:6px;">
          <i class="fas fa-bullseye"></i> Tujuan: ${tujuanText}
        </div>
      `;
      
      return `
        <div class="booking-detail-card ${b.type}">
          <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:8px;flex-wrap:wrap;gap:8px;">
            <div>
              <div class="booking-detail-title">
                ${b.icon} ${escapeHtml(b.item_name || '-')}
                ${b.is_recurring ? '<span class="recurring-badge-inline">🔄 Rutin</span>' : ''}
              </div>
              <div class="booking-detail-user">
                <i class="fas fa-user"></i> ${escapeHtml(b.user_name || '-')}
              </div>
            </div>
            <span class="booking-status-badge ${statusClass}">
              ${escapeHtml(b.status || '-')}
            </span>
          </div>
          ${tujuanLine}
          ${recurringInfo}
        </div>
      `;
    }).join('');
  }
  
  modal.classList.add('show');
}

function prevMonth() {
  currentDate.setMonth(currentDate.getMonth() - 1);
  renderCalendar();
}

function nextMonth() {
  currentDate.setMonth(currentDate.getMonth() + 1);
  renderCalendar();
}

// Event listeners
document.getElementById('bookingModal').onclick = (e) => {
  if (e.target.id === 'bookingModal') closeModal();
};

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') closeModal();
});

// Initialize calendar
renderCalendar();
</script>

</body>
</html>
