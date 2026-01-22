<?php
include "koneksi.php";

/* Pastikan koneksi & output UTF-8 */
if (function_exists('mysqli_set_charset')) { @mysqli_set_charset($conn, 'utf8mb4'); }
if (!headers_sent()) {
  header('Content-Type: text/html; charset=UTF-8');
}
if (function_exists('mb_internal_encoding')) { @mb_internal_encoding('UTF-8'); }

/* =========================
   Helper (format & sanitasi)
   ========================= */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function parse_date($s){
  if(!$s) return null;
  $s = trim($s);
  $fmt = null;
  if (preg_match('~^\d{4}-\d{2}-\d{2}$~', $s)) $fmt = 'Y-m-d';
  if (preg_match('~^\d{2}/\d{2}/\d{4}$~', $s)) $fmt = 'd/m/Y';
  if (!$fmt) return null;
  $dt = DateTime::createFromFormat($fmt, $s);
  return $dt ?: null;
}

function fmt_date($s){
  if(!$s) return '';
  $dt = DateTime::createFromFormat('Y-m-d', $s);
  if(!$dt) return h($s);
  return $dt->format('d M Y');
}

function fmt_time($s){
  if(!$s) return '';
  if (!preg_match('~^\d{2}:\d{2}~', $s)) return h($s);
  return substr($s,0,5);
}

function durasi_jam($tgl_mulai,$wkt_mulai,$tgl_selesai,$wkt_selesai){
  if(!$tgl_mulai || !$wkt_mulai || !$tgl_selesai || !$wkt_selesai) return '';
  $start = DateTime::createFromFormat('Y-m-d H:i', "$tgl_mulai $wkt_mulai");
  $end   = DateTime::createFromFormat('Y-m-d H:i', "$tgl_selesai $wkt_selesai");
  if(!$start || !$end || $end < $start) return '';
  $diff = $start->diff($end);
  $label = [];
  if($diff->days) $label[] = $diff->days.'h';
  $label[] = $diff->h.'j';
  if($diff->i) $label[] = $diff->i.'m';
  return implode(' ', $label);
}

/* Label hari untuk recurring */
function label_hari_recurring($daysStr){
  if(!$daysStr) return '';
  $map = [
    1 => 'Sen', 2 => 'Sel', 3 => 'Rab',
    4 => 'Kam', 5 => 'Jum', 6 => 'Sab',
    7 => 'Min'
  ];
  $parts = array_filter(array_map('trim', explode(',', $daysStr)));
  $labels = [];
  foreach($parts as $p){
    $n = (int)$p;
    $labels[] = $map[$n] ?? $p;
  }
  return implode(', ', $labels);
}

/* =========================
   Ambil & normalisasi filter
   ========================= */
$APPROVED = ['approve','approved','acc','disetujui','setuju','selesai','active','dipinjam'];
$PENDING  = ['menunggu','pending','submitted','waiting'];

$status     = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : '';
$dari_str   = isset($_GET['dari'])   ? trim($_GET['dari'])   : '';
$sampai_str = isset($_GET['sampai']) ? trim($_GET['sampai']) : '';
$gedung     = isset($_GET['gedung']) ? trim($_GET['gedung']) : '';
$peminjam   = isset($_GET['peminjam']) ? trim($_GET['peminjam']) : '';

$dari_dt    = parse_date($dari_str);
$sampai_dt  = parse_date($sampai_str);
$dari_sql   = $dari_dt ? $dari_dt->format('Y-m-d') : '';
$sampai_sql = $sampai_dt ? $sampai_dt->format('Y-m-d') : '';

/* ======================================
   Build WHERE pinjambarang (Hall)
   ====================================== */
$whereHall = [];
if ($status === 'menunggu') {
  $in = array_map(function($s) use ($conn){ return "'".mysqli_real_escape_string($conn,$s)."'"; }, $PENDING);
  $whereHall[] = "LOWER(pinjambarang.status) IN (".implode(',', $in).")";
} elseif ($status === 'approve' || $status === 'selesai') {
  $in = array_map(function($s) use ($conn){ return "'".mysqli_real_escape_string($conn,$s)."'"; }, $APPROVED);
  $whereHall[] = "LOWER(pinjambarang.status) IN (".implode(',', $in).")";
} elseif ($status !== '' && $status !== 'semua') {
  $whereHall[] = "LOWER(pinjambarang.status) = '".mysqli_real_escape_string($conn, strtolower($status))."'";
}
if ($dari_sql && $sampai_sql){
  $whereHall[] = "NOT (pinjambarang.tgl_selesai < '$dari_sql' OR pinjambarang.tgl_mulai > '$sampai_sql')";
} elseif ($dari_sql){
  $whereHall[] = "pinjambarang.tgl_selesai >= '$dari_sql'";
} elseif ($sampai_sql){
  $whereHall[] = "pinjambarang.tgl_mulai <= '$sampai_sql'";
}
if ($gedung !== ''){
  $whereHall[] = "barang.nama_barang = '".mysqli_real_escape_string($conn, $gedung)."'";
}
if ($peminjam !== ''){
  $like = '%'.mysqli_real_escape_string($conn, strtolower($peminjam)).'%';
  $whereHall[] = "LOWER(user.nama_lengkap) LIKE '$like'";
}

$sqlHall = "SELECT pinjambarang.*, user.nama_lengkap, barang.nama_barang
            FROM pinjambarang
            JOIN user   ON user.id = pinjambarang.id_user
            JOIN barang ON barang.id = pinjambarang.id_barang";
if ($whereHall){ $sqlHall .= " WHERE ".implode(' AND ', $whereHall); }
$sqlHall .= " ORDER BY pinjambarang.tgl_mulai DESC, pinjambarang.waktu_mulai DESC";
$qh = mysqli_query($conn, $sqlHall);
if(!$qh){ die('Query error (hall): '.h(mysqli_error($conn))); }

/* ======================================
   Build WHERE pinjamkendaraan (Car)
   ====================================== */
$whereCar = [];
if ($status === 'menunggu') {
  $in = array_map(function($s) use ($conn){ return "'".mysqli_real_escape_string($conn,$s)."'"; }, $PENDING);
  $whereCar[] = "LOWER(pinjamkendaraan.status) IN (".implode(',', $in).")";
} elseif ($status === 'approve' || $status === 'selesai') {
  $in = array_map(function($s) use ($conn){ return "'".mysqli_real_escape_string($conn,$s)."'"; }, $APPROVED);
  $whereCar[] = "LOWER(pinjamkendaraan.status) IN (".implode(',', $in).")";
} elseif ($status !== '' && $status !== 'semua') {
  $whereCar[] = "LOWER(pinjamkendaraan.status) = '".mysqli_real_escape_string($conn, strtolower($status))."'";
}
if ($dari_sql && $sampai_sql){
  $whereCar[] = "NOT (pinjamkendaraan.tgl_selesai < '$dari_sql' OR pinjamkendaraan.tgl_mulai > '$sampai_sql')";
} elseif ($dari_sql){
  $whereCar[] = "pinjamkendaraan.tgl_selesai >= '$dari_sql'";
} elseif ($sampai_sql){
  $whereCar[] = "pinjamkendaraan.tgl_mulai <= '$sampai_sql'";
}
if ($peminjam !== ''){
  $like = '%'.mysqli_real_escape_string($conn, strtolower($peminjam)).'%';
  $whereCar[] = "LOWER(user.nama_lengkap) LIKE '$like'";
}

$sqlCar = "SELECT pinjamkendaraan.*, kendaraan.nama_kendaraan, user.nama_lengkap
           FROM pinjamkendaraan
           JOIN kendaraan ON kendaraan.id_kendaraan = pinjamkendaraan.id_kendaraan
           JOIN user      ON user.id = pinjamkendaraan.id_user";
if ($whereCar){ $sqlCar .= " WHERE ".implode(' AND ', $whereCar); }
$sqlCar .= " ORDER BY pinjamkendaraan.tgl_mulai DESC, pinjamkendaraan.waktu_mulai DESC";
$qc = mysqli_query($conn, $sqlCar);
if(!$qc){ die('Query error (kendaraan): '.h(mysqli_error($conn))); }

/* ======================================
   Build WHERE pinjamkolam (Pool)
   ====================================== */
$whereKolam = [];
if ($status === 'menunggu') {
  $in = array_map(function($s) use ($conn){ return "'".mysqli_real_escape_string($conn,$s)."'"; }, $PENDING);
  $whereKolam[] = "LOWER(pinjamkolam.status) IN (".implode(',', $in).")";
} elseif ($status === 'approve' || $status === 'selesai') {
  $in = array_map(function($s) use ($conn){ return "'".mysqli_real_escape_string($conn,$s)."'"; }, $APPROVED);
  $whereKolam[] = "LOWER(pinjamkolam.status) IN (".implode(',', $in).")";
} elseif ($status !== '' && $status !== 'semua') {
  $whereKolam[] = "LOWER(pinjamkolam.status) = '".mysqli_real_escape_string($conn, strtolower($status))."'";
}
if ($dari_sql && $sampai_sql){
  $whereKolam[] = "NOT (pinjamkolam.tgl_selesai < '$dari_sql' OR pinjamkolam.tgl_mulai > '$sampai_sql')";
} elseif ($dari_sql){
  $whereKolam[] = "pinjamkolam.tgl_selesai >= '$dari_sql'";
} elseif ($sampai_sql){
  $whereKolam[] = "pinjamkolam.tgl_mulai <= '$sampai_sql'";
}
if ($peminjam !== ''){
  $like = '%'.mysqli_real_escape_string($conn, strtolower($peminjam)).'%';
  $whereKolam[] = "LOWER(user.nama_lengkap) LIKE '$like'";
}

$sqlKolam = "SELECT pinjamkolam.*, kolam.jenis_kolam, user.nama_lengkap
             FROM pinjamkolam
             JOIN kolam ON kolam.id_kolam = pinjamkolam.id_kolam
             JOIN user  ON user.id = pinjamkolam.id_user";
if ($whereKolam){ $sqlKolam .= " WHERE ".implode(' AND ', $whereKolam); }
$sqlKolam .= " ORDER BY pinjamkolam.tgl_mulai DESC, pinjamkolam.waktu_mulai DESC";
$qk = mysqli_query($conn, $sqlKolam);
if(!$qk){ die('Query error (kolam): '.h(mysqli_error($conn))); }

/* ======================================
   Build WHERE pinjamstudio (Studio)
   ====================================== */
$whereStudio = [];
if ($status === 'menunggu') {
  $in = array_map(function($s) use ($conn){ return "'".mysqli_real_escape_string($conn,$s)."'"; }, $PENDING);
  $whereStudio[] = "LOWER(pinjamstudio.status) IN (".implode(',', $in).")";
} elseif ($status === 'approve' || $status === 'selesai') {
  $in = array_map(function($s) use ($conn){ return "'".mysqli_real_escape_string($conn,$s)."'"; }, $APPROVED);
  $whereStudio[] = "LOWER(pinjamstudio.status) IN (".implode(',', $in).")";
} elseif ($status !== '' && $status !== 'semua') {
  $whereStudio[] = "LOWER(pinjamstudio.status) = '".mysqli_real_escape_string($conn, strtolower($status))."'";
}
if ($dari_sql && $sampai_sql){
  $whereStudio[] = "NOT (pinjamstudio.tgl_selesai < '$dari_sql' OR pinjamstudio.tgl_mulai > '$sampai_sql')";
} elseif ($dari_sql){
  $whereStudio[] = "pinjamstudio.tgl_selesai >= '$dari_sql'";
} elseif ($sampai_sql){
  $whereStudio[] = "pinjamstudio.tgl_mulai <= '$sampai_sql'";
}
if ($peminjam !== ''){
  $like = '%'.mysqli_real_escape_string($conn, strtolower($peminjam)).'%';
  $whereStudio[] = "LOWER(user.nama_lengkap) LIKE '$like'";
}

$sqlStudio = "SELECT pinjamstudio.*, studio.jenis_studio, user.nama_lengkap
              FROM pinjamstudio
              JOIN studio ON studio.id_studio = pinjamstudio.id_studio
              JOIN user   ON user.id = pinjamstudio.id_user";
if ($whereStudio){ $sqlStudio .= " WHERE ".implode(' AND ', $whereStudio); }
$sqlStudio .= " ORDER BY pinjamstudio.tgl_mulai DESC, pinjamstudio.waktu_mulai DESC";
$qs = mysqli_query($conn, $sqlStudio);
if(!$qs){ die('Query error (studio): '.h(mysqli_error($conn))); }

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
$APPROVED_SQL = "'approve','approved','acc','disetujui','setuju','selesai','active','dipinjam'";
$PENDING_SQL  = "'menunggu','pending','submitted','waiting'";

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
  
  <style>
    /* ========== RESET & BASE ========== */
    *, *::before, *::after { 
      box-sizing: border-box; 
      margin: 0; 
      padding: 0; 
    }
    
    :root {
      /* Gradient biru kalem */
      --gradient-primary: linear-gradient(135deg, #1b91ffff, #126b9eff, #0b639eff, #00579eff);
      --primary: #1b91ff;
      --primary-dark: #00579e;
      --primary-light: #6cb4ff;
      --primary-weak: rgba(27, 145, 255, 0.1);
      
      /* Neutral colors - putih kalem */
      --bg-base: #f8fafcff;
      --bg-card: #ffffff;
      --bg-card-hover: #f0f4f8;
      --text-primary: #1e293b;
      --text-secondary: #64748b;
      --text-muted: #94a3b8;
      
      /* Border & Divider */
      --border-color: #e2e8f0;
      --divider: #cbd5e1;
      
      /* Status colors */
      --status-pending-bg: #fef3c7;
      --status-pending-text: #92400e;
      --status-pending-border: #fbbf24;
      
      --status-approve-bg: #d1fae5;
      --status-approve-text: #065f46;
      --status-approve-border: #10b981;
      
      --status-selesai-bg: #dbeafe;
      --status-selesai-text: #1e3a8a;
      --status-selesai-border: #3b82f6;
      
      /* Shadows */
      --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.04);
      --shadow-md: 0 4px 12px rgba(0, 87, 158, 0.08);
      --shadow-lg: 0 10px 30px rgba(0, 87, 158, 0.12);
      
      /* Radius */
      --radius-sm: 8px;
      --radius-md: 12px;
      --radius-lg: 16px;
      --radius-xl: 20px;
      
      /* Spacing */
      --space-xs: 4px;
      --space-sm: 8px;
      --space-md: 16px;
      --space-lg: 24px;
      --space-xl: 32px;
    }
    
    html {
      font-size: 16px;
      scroll-behavior: smooth;
    }
    
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: var(--bg-base);
      color: var(--text-primary);
      line-height: 1.6;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      min-height: 100vh;
    }
    
    /* ========== LAYOUT ========== */
    .container {
      max-width: 1400px;
      margin: 0 auto;
      padding: clamp(12px, 3vw, 24px);
    }
    
    /* ========== HEADER ========== */
    .header {
      background: var(--gradient-primary);
      color: white;
      padding: clamp(16px, 3vw, 32px) clamp(12px, 3vw, 24px);
      margin-bottom: clamp(16px, 3vw, 32px);
      border-radius: 0 0 var(--radius-xl) var(--radius-xl);
      box-shadow: var(--shadow-lg);
    }
    
    .header-content {
      max-width: 1400px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: var(--space-md);
      flex-wrap: wrap;
    }
    
    .brand {
      display: flex;
      align-items: center;
      gap: clamp(8px, 2vw, 16px);
    }
    
    .brand-icon {
      width: clamp(36px, 8vw, 48px);
      height: clamp(36px, 8vw, 48px);
      background: rgba(255, 255, 255, 0.2);
      border-radius: var(--radius-md);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: clamp(18px, 4vw, 24px);
      backdrop-filter: blur(10px);
    }
    
    .brand h1 {
      font-size: clamp(20px, 4.5vw, 28px);
      font-weight: 800;
      letter-spacing: -0.5px;
    }
    
    .brand-subtitle {
      font-size: clamp(11px, 2.5vw, 13px);
      opacity: 0.9;
      font-weight: 500;
    }

    .btn-login {
      background: rgba(255, 255, 255, 0.2);
      color: white;
      padding: clamp(8px, 2vw, 10px) clamp(16px, 3vw, 20px);
      border-radius: var(--radius-md);
      border: 2px solid rgba(255, 255, 255, 0.3);
      font-size: clamp(13px, 2.5vw, 14px);
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
      backdrop-filter: blur(10px);
    }

    .btn-login:hover {
      background: rgba(255, 255, 255, 0.3);
      border-color: rgba(255, 255, 255, 0.5);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    /* ========== CARD ========== */
    .card {
      background: var(--bg-card);
      border-radius: clamp(12px, 2vw, 16px);
      box-shadow: var(--shadow-md);
      margin-bottom: clamp(16px, 3vw, 32px);
      overflow: hidden;
      border: 1px solid var(--border-color);
      transition: all 0.3s ease;
    }
    
    .card:hover {
      box-shadow: var(--shadow-lg);
    }
    
    .card-header {
      padding: clamp(12px, 2.5vw, 24px);
      border-bottom: 2px solid var(--border-color);
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: var(--space-md);
    }
    
    .card-title {
      font-size: clamp(16px, 3vw, 20px);
      font-weight: 700;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: var(--space-sm);
    }
    
    .card-title-icon {
      font-size: clamp(20px, 4vw, 24px);
    }
    
    .card-body {
      padding: clamp(12px, 2.5vw, 24px);
    }

    /* ========== CALENDAR GRID LAYOUT ========== */
    .calendar-wrapper {
      display: grid;
      grid-template-columns: 1.2fr 1fr;
      gap: clamp(12px, 2vw, 20px);
      margin-bottom: clamp(16px, 3vw, 32px);
    }

    /* ========== KALENDER SECTION (SMALLER) ========== */
    .calendar-section {
      background: #fff;
      border-radius: clamp(12px, 2vw, 16px);
      padding: clamp(10px, 2vw, 16px);
      box-shadow: var(--shadow-md);
      border: 1px solid var(--border-color);
    }

    .calendar-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: clamp(10px, 2vw, 16px);
      flex-wrap: wrap;
      gap: 6px;
    }

    .calendar-title {
      font-size: clamp(14px, 2.8vw, 16px);
      font-weight: 700;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .calendar-controls {
      display: flex;
      gap: 4px;
      align-items: center;
    }

    .calendar-controls button {
      background: var(--gradient-primary);
      color: white;
      border: none;
      width: clamp(28px, 6vw, 32px);
      height: clamp(28px, 6vw, 32px);
      border-radius: var(--radius-sm);
      cursor: pointer;
      font-weight: 700;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: clamp(12px, 2.5vw, 14px);
    }

    .calendar-controls button:hover {
      transform: scale(1.05);
      box-shadow: var(--shadow-md);
    }

    .calendar-month-label {
      font-weight: 700;
      font-size: clamp(13px, 2.8vw, 15px);
      color: var(--text-primary);
      min-width: clamp(100px, 20vw, 120px);
      text-align: center;
    }

    .calendar-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: clamp(2px, 0.5vw, 3px);
    }

    .calendar-day-header {
      text-align: center;
      font-weight: 700;
      color: var(--text-secondary);
      padding: clamp(6px, 1.5vw, 8px) 2px;
      font-size: clamp(10px, 2vw, 11px);
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }

    .calendar-day {
      aspect-ratio: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 6px;
      font-size: clamp(11px, 2.2vw, 12px);
      font-weight: 600;
      color: var(--text-primary);
      cursor: pointer;
      transition: all 0.2s ease;
      position: relative;
      border: 1.5px solid transparent;
    }

    .calendar-day:hover:not(.other-month) {
      background: var(--bg-card-hover);
      transform: scale(1.05);
    }

    .calendar-day.other-month {
      color: var(--text-muted);
      opacity: 0.3;
      cursor: default;
    }

    .calendar-day.today {
      background: var(--gradient-primary);
      color: white;
      font-weight: 700;
      box-shadow: var(--shadow-sm);
    }

    .calendar-day.has-booking {
      background: rgba(239, 68, 68, 0.1);
      font-weight: 700;
      border-color: #ef4444;
    }

    .calendar-day.has-booking:hover {
      background: rgba(239, 68, 68, 0.2);
      box-shadow: 0 2px 8px rgba(239, 68, 68, 0.25);
    }

    .calendar-day.has-recurring {
      background: rgba(168, 85, 247, 0.15);
      border: 1.5px dashed #a855f7;
    }

    .calendar-day.has-recurring:hover {
      background: rgba(168, 85, 247, 0.25);
      box-shadow: 0 2px 8px rgba(168, 85, 247, 0.3);
    }

    .booking-badge {
      position: absolute;
      top: -4px;
      right: -4px;
      background: linear-gradient(135deg, #ef4444, #dc2626);
      color: white;
      width: clamp(14px, 3vw, 16px);
      height: clamp(14px, 3vw, 16px);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: clamp(8px, 1.8vw, 9px);
      font-weight: 800;
      box-shadow: 0 2px 4px rgba(239, 68, 68, 0.4);
      border: 1.5px solid #fff;
    }

    .calendar-day.has-recurring .booking-badge {
      background: var(--gradient-primary);
    }

    .calendar-legend {
      display: flex;
      gap: clamp(8px, 2vw, 12px);
      margin-top: clamp(8px, 1.5vw, 12px);
      padding-top: clamp(8px, 1.5vw, 12px);
      border-top: 1px solid var(--border-color);
      font-size: clamp(10px, 2vw, 11px);
      color: var(--text-secondary);
      flex-wrap: wrap;
    }

    .calendar-legend span {
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .legend-box {
      width: clamp(14px, 3vw, 16px);
      height: clamp(14px, 3vw, 16px);
      border-radius: 3px;
      flex-shrink: 0;
    }

    .legend-box.has-booking {
      background: rgba(239, 68, 68, 0.1);
      border: 1.5px solid #ef4444;
    }

    .legend-box.has-recurring {
      background: rgba(168, 85, 247, 0.15);
      border: 1.5px dashed #a855f7;
    }

    /* ========== SCHEDULE LISTS ========== */
    .schedule-lists {
      display: flex;
      flex-direction: column;
      gap: clamp(12px, 2vw, 16px);
    }

    .schedule-list-section {
      background: #fff;
      border-radius: clamp(12px, 2vw, 16px);
      padding: clamp(10px, 2vw, 14px);
      box-shadow: var(--shadow-md);
      border: 1px solid var(--border-color);
    }

    .schedule-list-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: clamp(8px, 1.5vw, 12px);
      padding-bottom: clamp(6px, 1.2vw, 8px);
      border-bottom: 2px solid var(--border-color);
    }

    .schedule-list-title {
      font-size: clamp(13px, 2.5vw, 14px);
      font-weight: 700;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .schedule-count {
      background: var(--gradient-primary);
      color: white;
      padding: 2px clamp(6px, 1.5vw, 8px);
      border-radius: 999px;
      font-size: clamp(10px, 2vw, 11px);
      font-weight: 700;
    }

    .schedule-items {
      max-height: clamp(200px, 40vw, 280px);
      overflow-y: auto;
    }

    .schedule-item {
      background: var(--bg-card-hover);
      border-radius: var(--radius-sm);
      padding: clamp(8px, 1.5vw, 10px);
      margin-bottom: clamp(6px, 1.2vw, 8px);
      border-left: 3px solid var(--primary);
      transition: all 0.2s ease;
      cursor: pointer;
    }

    .schedule-item:hover {
      transform: translateX(3px);
      box-shadow: var(--shadow-sm);
    }

    .schedule-item.ruangan {
      border-left-color: #10b981;
    }

    .schedule-item.kendaraan {
      border-left-color: #3b82f6;
    }

    .schedule-item.kolam {
      border-left-color: #06b6d4;
    }

    .schedule-item.studio {
      border-left-color: #a855f7;
    }

    .schedule-item-header {
      display: flex;
      justify-content: space-between;
      align-items: start;
      margin-bottom: 5px;
      gap: 5px;
    }

    .schedule-item-name {
      font-weight: 700;
      font-size: clamp(11px, 2.2vw, 12px);
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: 4px;
      flex-wrap: wrap;
    }

    .schedule-item-meta {
      font-size: clamp(10px, 2vw, 11px);
      color: var(--text-secondary);
      display: flex;
      flex-direction: column;
      gap: 3px;
    }

    .schedule-item-meta div {
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .schedule-badge {
      padding: 2px 5px;
      border-radius: 3px;
      font-size: clamp(9px, 1.8vw, 10px);
      font-weight: 700;
      text-transform: uppercase;
      white-space: nowrap;
    }

    .schedule-badge.approve {
      background: #d1fae5;
      color: #065f46;
    }

    .schedule-badge.menunggu {
      background: #fef3c7;
      color: #92400e;
    }

    .recurring-label {
      background: var(--gradient-primary);
      color: white;
      padding: 2px 5px;
      border-radius: 3px;
      font-size: clamp(9px, 1.8vw, 10px);
      font-weight: 600;
    }

    .empty-schedule {
      text-align: center;
      padding: clamp(20px, 4vw, 32px) clamp(10px, 2vw, 16px);
      color: var(--text-muted);
    }

    .empty-schedule i {
      font-size: clamp(32px, 6vw, 42px);
      opacity: 0.3;
      margin-bottom: 8px;
    }

    /* ========== MODAL ========== */
    .modal-calendar {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.65);
      backdrop-filter: blur(4px);
      z-index: 10000;
      align-items: center;
      justify-content: center;
      padding: 16px;
      overflow-y: auto;
    }

    .modal-calendar.show {
      display: flex;
    }

    .modal-content-cal {
      background: #fff;
      border-radius: var(--radius-xl);
      padding: clamp(16px, 3vw, 28px);
      max-width: 600px;
      width: 100%;
      max-height: 85vh;
      overflow-y: auto;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(30px) scale(0.95);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    .modal-header-cal {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
      border-bottom: 2px solid var(--border-color);
      padding-bottom: 12px;
    }

    .modal-header-cal h2 {
      font-size: clamp(18px, 3.5vw, 20px);
      font-weight: 700;
      color: var(--text-primary);
    }

    .modal-close-cal {
      background: none;
      border: none;
      font-size: clamp(24px, 5vw, 28px);
      cursor: pointer;
      color: var(--text-muted);
      transition: all 0.2s ease;
      width: 32px;
      height: 32px;
      border-radius: var(--radius-sm);
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .modal-close-cal:hover {
      color: var(--text-primary);
      background: var(--bg-card-hover);
      transform: rotate(90deg);
    }

    .booking-detail-card {
      background: var(--bg-card-hover);
      border-radius: var(--radius-md);
      padding: clamp(10px, 2vw, 14px);
      margin-bottom: 10px;
      border-left: 4px solid var(--primary);
      transition: all 0.2s ease;
    }

    .booking-detail-card:hover {
      transform: translateX(4px);
      box-shadow: var(--shadow-md);
    }

    .booking-detail-card.ruangan {
      border-left-color: #10b981;
    }

    .booking-detail-card.kendaraan {
      border-left-color: #3b82f6;
    }

    .booking-detail-card.kolam {
      border-left-color: #06b6d4;
    }

    .booking-detail-card.studio {
      border-left-color: #a855f7;
    }

    .booking-detail-title {
      font-weight: 700;
      font-size: clamp(14px, 2.8vw, 15px);
      color: var(--text-primary);
      margin-bottom: 6px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .booking-detail-user {
      font-size: clamp(12px, 2.4vw, 13px);
      color: var(--text-secondary);
      display: flex;
      align-items: center;
      gap: 5px;
      flex-wrap: wrap;
    }

    .booking-status-badge {
      padding: 3px 8px;
      border-radius: 5px;
      font-size: clamp(10px, 2vw, 11px);
      font-weight: 700;
      text-transform: uppercase;
      white-space: nowrap;
    }

    .booking-status-badge.approve {
      background: linear-gradient(135deg, #10b981, #059669);
      color: white;
    }

    .booking-status-badge.menunggu {
      background: linear-gradient(135deg, #f59e0b, #d97706);
      color: white;
    }

    .recurring-badge-inline {
      background: var(--gradient-primary);
      color: white;
      padding: 2px 6px;
      border-radius: 4px;
      font-size: clamp(10px, 2vw, 11px);
      margin-left: 5px;
      font-weight: 600;
    }
    
    /* ========== FILTER FORM ========== */
    .filter-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(min(100%, 200px), 1fr));
      gap: clamp(10px, 2vw, 16px);
      margin-bottom: clamp(10px, 2vw, 16px);
    }
    
    .form-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }
    
    .form-label {
      font-size: clamp(11px, 2.2vw, 13px);
      font-weight: 600;
      color: var(--text-secondary);
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }
    
    .form-control {
      padding: clamp(10px, 2vw, 12px) clamp(12px, 2.5vw, 16px);
      border: 2px solid var(--border-color);
      border-radius: var(--radius-md);
      font-size: clamp(13px, 2.6vw, 14px);
      font-family: inherit;
      transition: all 0.2s ease;
      background: var(--bg-card);
      color: var(--text-primary);
    }
    
    .form-control:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(27, 145, 255, 0.1);
    }
    
    /* Status Pills */
    .status-pills {
      display: flex;
      gap: clamp(6px, 1.2vw, 8px);
      flex-wrap: wrap;
    }
    
    .pill {
      padding: clamp(8px, 1.6vw, 10px) clamp(14px, 2.8vw, 18px);
      border-radius: 999px;
      border: 2px solid var(--border-color);
      background: var(--bg-card);
      font-size: clamp(12px, 2.4vw, 14px);
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
      color: var(--text-secondary);
    }
    
    .pill:hover {
      border-color: var(--primary);
      color: var(--primary);
      transform: translateY(-1px);
    }
    
    .pill.active {
      background: var(--gradient-primary);
      border-color: var(--primary);
      color: white;
      box-shadow: var(--shadow-md);
    }
    
    /* ========== BUTTONS ========== */
    .btn {
      padding: clamp(10px, 2vw, 12px) clamp(18px, 3.5vw, 24px);
      border-radius: var(--radius-md);
      font-size: clamp(13px, 2.6vw, 14px);
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
      border: none;
      font-family: inherit;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      text-decoration: none;
    }
    
    .btn-primary {
      background: var(--gradient-primary);
      color: white;
      box-shadow: var(--shadow-md);
    }
    
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
    }
    
    .btn-secondary {
      background: var(--bg-card-hover);
      color: var(--text-primary);
      border: 2px solid var(--border-color);
    }
    
    .btn-secondary:hover {
      border-color: var(--primary);
      color: var(--primary);
    }
    
    .btn-actions {
      display: flex;
      gap: clamp(6px, 1.2vw, 8px);
      flex-wrap: wrap;
    }
    
    /* ========== TABS ========== */
    .tabs {
      display: flex;
      gap: 4px;
      border-bottom: 2px solid var(--border-color);
      margin-bottom: clamp(12px, 2.5vw, 20px);
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
    
    .tab-btn {
      padding: clamp(10px, 2vw, 12px) clamp(14px, 2.8vw, 18px);
      background: transparent;
      border: none;
      border-bottom: 3px solid transparent;
      font-size: clamp(13px, 2.6vw, 15px);
      font-weight: 600;
      color: var(--text-secondary);
      cursor: pointer;
      transition: all 0.2s ease;
      white-space: nowrap;
      position: relative;
    }
    
    .tab-btn:hover {
      color: var(--primary);
    }
    
    .tab-btn.active {
      color: var(--primary);
      border-bottom-color: var(--primary);
    }
    
    .tab-content {
      display: none;
    }
    
    .tab-content.active {
      display: block;
      animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    /* ========== SEARCH BOX ========== */
    .search-box {
      position: relative;
      margin-bottom: clamp(12px, 2.5vw, 20px);
    }
    
    .search-input {
      width: 100%;
      padding: clamp(12px, 2.4vw, 14px) clamp(40px, 8vw, 48px);
      border: 2px solid var(--border-color);
      border-radius: var(--radius-md);
      font-size: clamp(13px, 2.6vw, 15px);
      transition: all 0.2s ease;
    }
    
    .search-input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(27, 145, 255, 0.1);
    }
    
    .search-icon {
      position: absolute;
      left: clamp(12px, 2.5vw, 16px);
      top: 50%;
      transform: translateY(-50%);
      font-size: clamp(16px, 3.2vw, 18px);
      color: var(--text-muted);
    }
    
    /* ========== TABLE ========== */
    .table-wrapper {
      overflow-x: auto;
      border-radius: var(--radius-md);
      border: 1px solid var(--border-color);
    }
    
    table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      min-width: 700px;
    }
    
    thead {
      background: var(--gradient-primary);
      color: white;
    }
    
    thead th {
      padding: clamp(12px, 2.4vw, 14px);
      text-align: left;
      font-size: clamp(11px, 2.2vw, 12px);
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.3px;
      white-space: nowrap;
    }
    
    tbody tr {
      border-bottom: 1px solid var(--border-color);
      transition: all 0.2s ease;
    }
    
    tbody tr:hover {
      background: var(--bg-card-hover);
    }
    
    tbody td {
      padding: clamp(12px, 2.4vw, 14px);
      font-size: clamp(12px, 2.4vw, 13px);
    }
    
    tbody tr.row-pending {
      background: var(--status-pending-bg);
      border-left: 4px solid var(--status-pending-border);
    }
    
    tbody tr.row-approve {
      background: var(--status-approve-bg);
      border-left: 4px solid var(--status-approve-border);
    }
    
    tbody tr.row-selesai {
      background: var(--status-selesai-bg);
      border-left: 4px solid var(--status-selesai-border);
    }
    
    .badge {
      display: inline-block;
      padding: clamp(4px, 0.8vw, 5px) clamp(8px, 1.6vw, 10px);
      border-radius: 999px;
      font-size: clamp(10px, 2vw, 11px);
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }
    
    .badge-pending {
      background: var(--status-pending-bg);
      color: var(--status-pending-text);
      border: 1px solid var(--status-pending-border);
    }
    
    .badge-approve {
      background: var(--status-approve-bg);
      color: var(--status-approve-text);
      border: 1px solid var(--status-approve-border);
    }
    
    .badge-selesai {
      background: var(--status-selesai-bg);
      color: var(--status-selesai-text);
      border: 1px solid var(--status-selesai-border);
    }
    
    /* ========== EMPTY STATE ========== */
    .empty-state {
      text-align: center;
      padding: clamp(24px, 5vw, 40px);
      color: var(--text-muted);
    }
    
    .empty-icon {
      font-size: clamp(48px, 10vw, 60px);
      margin-bottom: clamp(10px, 2vw, 16px);
      opacity: 0.3;
    }
    
    /* ========== RESPONSIVE ========== */
    @media (max-width: 1024px) {
      .calendar-wrapper {
        grid-template-columns: 1fr;
      }

      .schedule-items {
        max-height: 250px;
      }
    }

    @media (max-width: 768px) {
      .header-content {
        flex-direction: column;
        align-items: flex-start;
      }

      .btn-login {
        width: 100%;
        justify-content: center;
      }

      .filter-grid {
        grid-template-columns: 1fr;
      }
      
      .status-pills {
        width: 100%;
      }
      
      .pill {
        flex: 1;
        text-align: center;
        min-width: 0;
      }

      .btn-actions {
        width: 100%;
      }
      
      .btn {
        flex: 1;
      }
      
      .tabs {
        gap: 0;
      }
      
      .tab-btn {
        flex: 1;
        text-align: center;
        padding: 10px 8px;
      }

      .calendar-grid {
        gap: 2px;
      }

      .schedule-items {
        max-height: 200px;
      }
    }
    
    @media (max-width: 480px) {
      .calendar-day {
        font-size: 10px;
      }

      .calendar-day-header {
        font-size: 9px;
        padding: 4px 2px;
      }

      .booking-badge {
        width: 12px;
        height: 12px;
        font-size: 7px;
      }

      .calendar-legend {
        font-size: 9px;
      }

      .legend-box {
        width: 12px;
        height: 12px;
      }
    }
    
    /* ========== UTILITIES ========== */
    .text-muted { color: var(--text-muted); }
    .text-secondary { color: var(--text-secondary); }
    .font-bold { font-weight: 700; }
    .nowrap { white-space: nowrap; }
    
    /* ========== SCROLLBAR ========== */
    ::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }
    
    ::-webkit-scrollbar-track {
      background: var(--bg-card-hover);
      border-radius: 999px;
    }
    
    ::-webkit-scrollbar-thumb {
      background: var(--primary-light);
      border-radius: 999px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
      background: var(--primary);
    }
  </style>
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

function closeModal() {
  document.getElementById('bookingModal').classList.remove('show');
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

// Tab switching
function switchTab(event, tabId) {
  const tabs = document.querySelectorAll('.tab-content');
  tabs.forEach(tab => tab.classList.remove('active'));
  
  const btns = document.querySelectorAll('.tab-btn');
  btns.forEach(btn => btn.classList.remove('active'));
  
  document.getElementById(tabId).classList.add('active');
  event.target.classList.add('active');
}

// Table search
function searchTable(inputId, tableId) {
  const input = document.getElementById(inputId);
  const filter = input.value.toLowerCase();
  const table = document.getElementById(tableId);
  const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
  
  for (let i = 0; i < rows.length; i++) {
    const row = rows[i];
    const text = row.textContent || row.innerText;
    
    if (text.toLowerCase().indexOf(filter) > -1) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  }
}

// Initialize calendar
renderCalendar();
</script>

</body>
</html>
