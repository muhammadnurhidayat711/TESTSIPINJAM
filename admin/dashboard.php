<?php
// ===============================
// Dashboard Admin — SIPINJAM
// ===============================
include 'cek.php';
if (!isset($conn)) die('Koneksi DB belum diinisialisasi.');
if (session_status() === PHP_SESSION_NONE) session_start();

// -------- Helpers --------
function current_admin_name() {
  return $_SESSION['username'] ?? $_SESSION['nama_lengkap'] ?? 'Admin';
}
function nf($n) { return number_format((int)$n, 0, ',', '.'); }
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function pct($free, $total) { return $total > 0 ? round(($free / $total) * 100) : 0; }

function scalar($conn, $sql, $params = []) {
  $stmt = mysqli_prepare($conn, $sql);
  if (!$stmt) return 0;
  if ($params) {
    $types = '';
    foreach ($params as $p) $types .= is_int($p) ? 'i' : 's';
    mysqli_stmt_bind_param($stmt, $types, ...$params);
  }
  mysqli_stmt_execute($stmt);
  mysqli_stmt_bind_result($stmt, $val);
  $val = 0;
  mysqli_stmt_fetch($stmt);
  mysqli_stmt_close($stmt);
  return (int)$val;
}

$admin_name = current_admin_name();

// -------- Status --------
$APPROVED = "'approve','approved','disetujui','acc','setuju','active','dipinjam','selesai'";
$PENDING  = "'menunggu','pending'";

// -------- Total Statistics --------
$total_barang    = scalar($conn, "SELECT COUNT(*) FROM barang");
$total_kendaraan = scalar($conn, "SELECT COUNT(*) FROM kendaraan");
$total_user      = scalar($conn, "SELECT COUNT(*) FROM user");
$total_kolam     = scalar($conn, "SELECT COUNT(*) FROM kolam");
$total_studio    = scalar($conn, "SELECT COUNT(*) FROM studio");

// -------- Ketersediaan Real-time --------
$used_barang = scalar(
  $conn,
  "SELECT COUNT(DISTINCT id_barang) 
   FROM pinjambarang 
   WHERE id_barang IS NOT NULL 
     AND LOWER(status) IN ($APPROVED)
     AND tgl_mulai <= CURDATE()
     AND tgl_selesai >= CURDATE()"
);

$used_kendaraan = scalar(
  $conn,
  "SELECT COUNT(DISTINCT id_kendaraan) 
   FROM pinjamkendaraan 
   WHERE id_kendaraan IS NOT NULL 
     AND LOWER(status) IN ($APPROVED)
     AND tgl_mulai <= CURDATE()
     AND tgl_selesai >= CURDATE()"
);

$used_kolam = scalar(
  $conn,
  "SELECT COUNT(DISTINCT id_kolam) 
   FROM pinjamkolam 
   WHERE id_kolam IS NOT NULL 
     AND LOWER(status) IN ($APPROVED)
     AND tgl_mulai <= CURDATE()
     AND tgl_selesai >= CURDATE()"
);

$used_studio = scalar(
  $conn,
  "SELECT COUNT(DISTINCT id_studio) 
   FROM pinjamstudio 
   WHERE id_studio IS NOT NULL 
     AND LOWER(status) IN ($APPROVED)
     AND tgl_mulai <= CURDATE()
     AND tgl_selesai >= CURDATE()"
);

$stats = [
  'barang_tersedia'    => max(0, $total_barang - $used_barang),
  'barang_total'       => $total_barang,
  'kendaraan_tersedia' => max(0, $total_kendaraan - $used_kendaraan),
  'kendaraan_total'    => $total_kendaraan,
  'kolam_tersedia'     => max(0, $total_kolam - $used_kolam),
  'kolam_total'        => $total_kolam,
  'studio_tersedia'    => max(0, $total_studio - $used_studio),
  'studio_total'       => $total_studio,
];

// -------- Pending Count (semua tabel) --------
$pending_total = scalar($conn, "
  SELECT COUNT(*) FROM (
    SELECT id_pinjam AS id_any FROM pinjambarang   WHERE LOWER(status) IN ($PENDING)
    UNION ALL 
    SELECT id_pk     AS id_any FROM pinjamkendaraan WHERE LOWER(status) IN ($PENDING)
    UNION ALL 
    SELECT id_pinjamkolam AS id_any FROM pinjamkolam WHERE LOWER(status) IN ($PENDING)
    UNION ALL 
    SELECT id_pinjamstudio AS id_any FROM pinjamstudio WHERE LOWER(status) IN ($PENDING)
  ) AS pending
");

// ========================================
// ✅ JADWAL MENDATANG (7 HARI KE DEPAN)
// ========================================
$upcoming_bookings = [];
$today      = date('Y-m-d');
$week_later = date('Y-m-d', strtotime('+7 days'));

$query_upcoming = "
  -- RUANGAN
  SELECT 
    'ruangan' AS type,
    'fas fa-building' AS icon,
    IFNULL(b.nama_barang, '-') AS item_name,
    IFNULL(u.nama_lengkap, 'User') AS user_name,
    pb.tgl_mulai,
    pb.waktu_mulai,
    pb.tgl_selesai,
    pb.waktu_selesai,
    IFNULL(pb.is_recurring,'no')    AS is_recurring,
    IFNULL(pb.recurring_days,'')    AS recurring_days,
    pb.status,
    NULL AS supir_name,
    pb.tujuan_barang AS tujuan
  FROM pinjambarang pb
  LEFT JOIN barang b ON b.id = pb.id_barang
  LEFT JOIN user   u ON u.id = pb.id_user
  WHERE LOWER(pb.status) IN ($APPROVED, $PENDING)
    AND pb.tgl_mulai BETWEEN '$today' AND '$week_later'

  UNION ALL

  -- KENDARAAN
  SELECT 
    'kendaraan' AS type,
    'fas fa-car' AS icon,
    IFNULL(k.nama_kendaraan, '-') AS item_name,
    IFNULL(u.nama_lengkap, 'User') AS user_name,
    pk.tgl_mulai,
    pk.waktu_mulai,
    pk.tgl_selesai,
    pk.waktu_selesai,
    IFNULL(pk.is_recurring,'no')    AS is_recurring,
    IFNULL(pk.recurring_days,'')    AS recurring_days,
    pk.status,
    pk.pengemudi AS supir_name,
    pk.tujuan AS tujuan
  FROM pinjamkendaraan pk
  LEFT JOIN kendaraan k ON k.id_kendaraan = pk.id_kendaraan
  LEFT JOIN user      u ON u.id = pk.id_user
  WHERE LOWER(pk.status) IN ($APPROVED, $PENDING)
    AND pk.tgl_mulai BETWEEN '$today' AND '$week_later'

  UNION ALL

  -- KOLAM
  SELECT 
    'kolam' AS type,
    'fas fa-water' AS icon,
    IFNULL(kl.jenis_kolam, '-') AS item_name,
    IFNULL(u.nama_lengkap, 'User') AS user_name,
    pkl.tgl_mulai,
    pkl.waktu_mulai,
    pkl.tgl_selesai,
    pkl.waktu_selesai,
    IFNULL(pkl.is_recurring,'no')   AS is_recurring,
    IFNULL(pkl.recurring_days,'')   AS recurring_days,
    pkl.status,
    NULL AS supir_name,
    CONCAT('Kelas: ', IFNULL(kls.nama_kelas, '-')) AS tujuan
  FROM pinjamkolam pkl
  LEFT JOIN kolam kl ON kl.id_kolam = pkl.id_kolam
  LEFT JOIN user u   ON u.id = pkl.id_user
  LEFT JOIN kelas kls ON kls.id_kelas = pkl.id_kelas
  WHERE LOWER(pkl.status) IN ($APPROVED, $PENDING)
    AND pkl.tgl_mulai BETWEEN '$today' AND '$week_later'

  UNION ALL

  -- STUDIO
  SELECT 
    'studio' AS type,
    'fas fa-video' AS icon,
    IFNULL(s.jenis_studio, '-') AS item_name,
    IFNULL(u.nama_lengkap, 'User') AS user_name,
    ps.tgl_mulai,
    ps.waktu_mulai,
    ps.tgl_selesai,
    ps.waktu_selesai,
    IFNULL(ps.is_recurring,'no')    AS is_recurring,
    IFNULL(ps.recurring_days,'')    AS recurring_days,
    ps.status,
    NULL AS supir_name,
    IFNULL(ps.deskripsi_peminjaman, '-') AS tujuan
  FROM pinjamstudio ps
  LEFT JOIN studio s ON s.id_studio = ps.id_studio
  LEFT JOIN user  u  ON u.id = ps.id_user
  WHERE LOWER(ps.status) IN ($APPROVED, $PENDING)
    AND ps.tgl_mulai BETWEEN '$today' AND '$week_later'

  ORDER BY tgl_mulai ASC, waktu_mulai ASC
  LIMIT 12
";

$result_upcoming = mysqli_query($conn, $query_upcoming);
if ($result_upcoming) {
  while ($row = mysqli_fetch_assoc($result_upcoming)) {
    $upcoming_bookings[] = $row;
  }
}

// ========================================
// ✅ LIST PENDING (SEMUA TABEL) + TUJUAN
// ========================================
$pending_query = "
  SELECT 
    'ruangan' AS type,
    'fas fa-building' AS icon,
    IFNULL(b.nama_barang, '-') AS item_name,
    IFNULL(u.nama_lengkap, 'User') AS user_name,
    pb.tgl_mulai,
    pb.waktu_mulai,
    CONCAT(pb.tgl_mulai,' ',IFNULL(pb.waktu_mulai,'00:00')) AS waktu_gabung,
    pb.tujuan_barang AS tujuan
  FROM pinjambarang pb
  LEFT JOIN barang b ON b.id = pb.id_barang
  LEFT JOIN user   u ON u.id = pb.id_user
  WHERE LOWER(pb.status) IN ($PENDING)

  UNION ALL

  SELECT 
    'kendaraan' AS type,
    'fas fa-car' AS icon,
    IFNULL(k.nama_kendaraan, '-') AS item_name,
    IFNULL(u.nama_lengkap, 'User') AS user_name,
    pk.tgl_mulai,
    pk.waktu_mulai,
    CONCAT(pk.tgl_mulai,' ',IFNULL(pk.waktu_mulai,'00:00')) AS waktu_gabung,
    pk.tujuan AS tujuan
  FROM pinjamkendaraan pk
  LEFT JOIN kendaraan k ON k.id_kendaraan = pk.id_kendaraan
  LEFT JOIN user      u ON u.id = pk.id_user
  WHERE LOWER(pk.status) IN ($PENDING)

  UNION ALL

  SELECT 
    'kolam' AS type,
    'fas fa-water' AS icon,
    IFNULL(kl.jenis_kolam, '-') AS item_name,
    IFNULL(u.nama_lengkap, 'User') AS user_name,
    pkl.tgl_mulai,
    pkl.waktu_mulai,
    CONCAT(pkl.tgl_mulai,' ',IFNULL(pkl.waktu_mulai,'00:00')) AS waktu_gabung,
    CONCAT('Kelas: ', IFNULL(kls.nama_kelas,'-')) AS tujuan
  FROM pinjamkolam pkl
  LEFT JOIN kolam kl ON kl.id_kolam = pkl.id_kolam
  LEFT JOIN user u   ON u.id = pkl.id_user
  LEFT JOIN kelas kls ON kls.id_kelas = pkl.id_kelas
  WHERE LOWER(pkl.status) IN ($PENDING)

  UNION ALL

  SELECT 
    'studio' AS type,
    'fas fa-video' AS icon,
    IFNULL(s.jenis_studio, '-') AS item_name,
    IFNULL(u.nama_lengkap, 'User') AS user_name,
    ps.tgl_mulai,
    ps.waktu_mulai,
    CONCAT(ps.tgl_mulai,' ',IFNULL(ps.waktu_mulai,'00:00')) AS waktu_gabung,
    IFNULL(ps.deskripsi_peminjaman,'') AS tujuan
  FROM pinjamstudio ps
  LEFT JOIN studio s ON s.id_studio = ps.id_studio
  LEFT JOIN user  u  ON u.id = ps.id_user
  WHERE LOWER(ps.status) IN ($PENDING)

  ORDER BY waktu_gabung ASC
  LIMIT 20
";

$pending_result = mysqli_query($conn, $pending_query);
$pending_list = [];
if ($pending_result) {
  while($row = mysqli_fetch_assoc($pending_result)) {
    $pending_list[] = $row;
  }
}

// ========================================
// ✅ DATA UNTUK MINI KALENDER (SEMUA TABEL + RUTIN + TUJUAN)
// ========================================
$bookings_query = "
  -- RUANGAN
  SELECT 
    'ruangan' AS type,
    pb.tgl_mulai,
    pb.tgl_selesai,
    IFNULL(b.nama_barang, '-') AS item_name,
    IFNULL(u.nama_lengkap, 'User') AS user_name,
    IFNULL(pb.is_recurring,'no')  AS is_recurring,
    IFNULL(pb.recurring_days,'')  AS recurring_days,
    pb.status,
    'fas fa-building' AS icon,
    pb.tujuan_barang AS tujuan
  FROM pinjambarang pb
  LEFT JOIN barang b ON b.id = pb.id_barang
  LEFT JOIN user   u ON u.id = pb.id_user

  UNION ALL

  -- KENDARAAN
  SELECT 
    'kendaraan' AS type,
    pk.tgl_mulai,
    pk.tgl_selesai,
    IFNULL(k.nama_kendaraan, '-') AS item_name,
    IFNULL(u.nama_lengkap, 'User') AS user_name,
    IFNULL(pk.is_recurring,'no')   AS is_recurring,
    IFNULL(pk.recurring_days,'')   AS recurring_days,
    pk.status,
    'fas fa-car' AS icon,
    pk.tujuan AS tujuan
  FROM pinjamkendaraan pk
  LEFT JOIN kendaraan k ON k.id_kendaraan = pk.id_kendaraan
  LEFT JOIN user      u ON u.id = pk.id_user

  UNION ALL

  -- KOLAM
  SELECT 
    'kolam' AS type,
    pkl.tgl_mulai,
    pkl.tgl_selesai,
    IFNULL(kl.jenis_kolam, '-') AS item_name,
    IFNULL(u.nama_lengkap, 'User') AS user_name,
    IFNULL(pkl.is_recurring,'no')  AS is_recurring,
    IFNULL(pkl.recurring_days,'')  AS recurring_days,
    pkl.status,
    'fas fa-water' AS icon,
    CONCAT('Kelas: ', IFNULL(kls.nama_kelas,'-')) AS tujuan
  FROM pinjamkolam pkl
  LEFT JOIN kolam kl ON kl.id_kolam = pkl.id_kolam
  LEFT JOIN user u   ON u.id = pkl.id_user
  LEFT JOIN kelas kls ON kls.id_kelas = pkl.id_kelas

  UNION ALL

  -- STUDIO
  SELECT 
    'studio' AS type,
    ps.tgl_mulai,
    ps.tgl_selesai,
    IFNULL(s.jenis_studio, '-') AS item_name,
    IFNULL(u.nama_lengkap, 'User') AS user_name,
    IFNULL(ps.is_recurring,'no')   AS is_recurring,
    IFNULL(ps.recurring_days,'')   AS recurring_days,
    ps.status,
    'fas fa-video' AS icon,
    IFNULL(ps.deskripsi_peminjaman,'') AS tujuan
  FROM pinjamstudio ps
  LEFT JOIN studio s ON s.id_studio = ps.id_studio
  LEFT JOIN user  u  ON u.id = ps.id_user
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
      'item_name'     => $row['item_name'],
      'user_name'     => $row['user_name'],
      'is_recurring'  => $isRecurring,
      'recurring_days'=> $row['recurring_days'] ?? '',
      'status'        => $row['status'],
      'icon'          => $row['icon'],
      'tujuan'        => $row['tujuan'] ?? '',
    ];
  }
}
$all_bookings_json = json_encode($all_bookings);
?>

<style>
/* ===========================
   DASHBOARD RESPONSIVE STYLES - OPTIMIZED 2025
   =========================== */

/* Base Reset */
* { box-sizing: border-box; }

/* Container utama */
.dashboard-wrapper {
  width: 100%;
  max-width: 100%;
  margin: 0 auto;
  padding: clamp(0.875rem, 2vw, 1.5rem);
}

/* HERO SECTION */
.user-dashboard { 
  background: linear-gradient(135deg, #1b91ffff , #126b9eff, #0b639eff, #00579eff);
  min-height: clamp(10px, 15vh, 10px);
  max-height: clamp(200px, 25vh, 250px);
  border-radius: clamp(16px, 2vw, 20px);
  color: white; 
  position: relative; 
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3); 
  margin-bottom: clamp(1rem, 2vw, 1.5rem);
  padding: clamp(1rem, 2vw, 1.5rem);
}
.user-dashboard::before { 
  content: ''; 
  position: absolute; 
  inset: 0; 
  background: radial-gradient(circle at 20% 80%, rgba(255,255,255,0.1) 0%, transparent 50%); 
}
.user-dashboard::after { 
  content: ''; 
  position: absolute; 
  inset: 0;
  background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,.05) 50%, transparent 70%);
  transform: translateX(-100%); 
  animation: shimmer 3s infinite; 
}
@keyframes shimmer { 100% { transform: translateX(100%); } }
.user-dashboard .content { position: relative; z-index: 2; }
.welcome-text { 
  font-size: clamp(1.25rem, 3vw, 1.75rem); 
  font-weight: 800; 
  margin-bottom: 0.25rem; 
  letter-spacing: -0.02em; 
}
.welcome-subtitle { 
  opacity: .9; 
  font-size: clamp(0.813rem, 2vw, 0.9rem); 
  font-weight: 400; 
}

/* JUDUL SECTION */
.section-title {
  font-size: clamp(1rem, 2.5vw, 1.125rem); 
  font-weight: 700; 
  color: #0f172a; 
  margin: clamp(1rem, 2vw, 1.5rem) 0 clamp(0.75rem, 1.5vw, 1rem) 0;
  display: flex; 
  align-items: center; 
  gap: 0.5rem;
}
.section-title::before {
  content: ''; 
  width: 4px; 
  height: 20px;
  background: linear-gradient(135deg, #1b91ffff , #126b9eff, #0b639eff, #00579eff);
  border-radius: 2px;
}

/* GRID UTAMA (Calendar + Pending) */
.main-content-grid { 
  display: grid; 
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 400px), 1fr));
  gap: clamp(1rem, 2vw, 1.25rem);
  margin-bottom: clamp(1rem, 2vw, 1.5rem);
}

/* ========================================
   JADWAL MENDATANG (7 HARI)
   ======================================== */
.upcoming-schedule-section {
  background: linear-gradient(135deg, #ffffff, #f0f9ff);
  border-radius: clamp(8px, 1.2vw, 12px);
  border: 1.5px solid #bae6fd;
  box-shadow: 0 6px 16px rgba(56, 189, 248, 0.12);
  padding: clamp(0.5rem, 1vw, 0.75rem);
  margin-bottom: clamp(0.625rem, 1.2vw, 0.875rem);
  animation: slideInUp 0.4s ease-out;
}

.upcoming-schedule-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: clamp(0.375rem, 0.8vw, 0.5rem);
  padding-bottom: clamp(0.375rem, 0.8vw, 0.5rem);
  border-bottom: 1px solid #e0f2fe;
  flex-wrap: wrap;
  gap: 6px;
}

.upcoming-schedule-title {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: clamp(0.8rem, 1.8vw, 0.9rem);
  font-weight: 700;
  color: #0c4a6e;
}

.upcoming-schedule-title i {
  background: linear-gradient(135deg, #0ea5e9, #0284c7);
  color: white;
  width: clamp(24px, 3.5vw, 28px);
  height: clamp(24px, 3.5vw, 28px);
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: clamp(0.75rem, 1.8vw, 0.875rem);
  box-shadow: 0 2px 6px rgba(14, 165, 233, 0.2);
}

.upcoming-count-badge {
  background: linear-gradient(135deg, #0ea5e9, #0284c7);
  color: white;
  padding: clamp(2px, 0.6vw, 3px) clamp(6px, 1.2vw, 8px);
  border-radius: 999px;
  font-size: clamp(0.6rem, 1.3vw, 0.7rem);
  font-weight: 600;
  box-shadow: 0 2px 6px rgba(14, 165, 233, 0.2);
  white-space: nowrap;
}

.upcoming-schedule-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 220px), 1fr));
  gap: clamp(0.375rem, 0.8vw, 0.5rem);
  max-height: 220px;
  overflow-y: auto;
  padding-right: 3px;
}

.upcoming-schedule-item {
  background: white;
  border-radius: 6px;
  padding: clamp(0.5rem, 1vw, 0.625rem);
  border-left: 2.5px solid #0ea5e9;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
  transition: all 0.2s ease;
  cursor: pointer;
}
.upcoming-schedule-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(14, 165, 233, 0.18);
  border-left-width: 3.5px;
}
.upcoming-schedule-item.ruangan   { border-left-color: #10b981; }
.upcoming-schedule-item.kendaraan { border-left-color: #3b82f6; }
.upcoming-schedule-item.kolam     { border-left-color: #06b6d4; }
.upcoming-schedule-item.studio    { border-left-color: #a855f7; }

.upcoming-schedule-item.status-menunggu {
  border-left-color: #f59e0b;
  background: linear-gradient(to right, #fffbeb, #ffffff);
}
.upcoming-schedule-item.status-approve {
  background: linear-gradient(to right, #f0fdf4, #ffffff);
}

.upcoming-schedule-date {
  display: flex;
  align-items: center;
  gap: 3px;
  font-size: clamp(0.6rem, 1.3vw, 0.65rem);
  color: #0369a1;
  font-weight: 600;
  margin-bottom: 4px;
}
.upcoming-schedule-date i {
  font-size: clamp(0.65rem, 1.4vw, 0.7rem);
}

.upcoming-schedule-item-name {
  font-size: clamp(0.75rem, 1.7vw, 0.8rem);
  font-weight: 700;
  color: #111827;
  margin-bottom: 4px;
  display: flex;
  align-items: center;
  gap: 4px;
  flex-wrap: wrap;
  line-height: 1.2;
}
.upcoming-schedule-item-name i {
  color: #0ea5e9;
  font-size: clamp(0.8rem, 1.8vw, 0.85rem);
  flex-shrink: 0;
}

.upcoming-schedule-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  margin-top: 5px;
  align-items: center;
}
.upcoming-meta-item {
  display: flex;
  align-items: center;
  gap: 3px;
  font-size: clamp(0.575rem, 1.2vw, 0.6rem);
  color: #6b7280;
  background: #f9fafb;
  padding: 1.5px 5px;
  border-radius: 3px;
  line-height: 1.3;
}
.upcoming-meta-item i {
  color: #0ea5e9;
  font-size: clamp(0.65rem, 1.4vw, 0.7rem);
  flex-shrink: 0;
}
.upcoming-meta-item.meta-tujuan {
  flex-basis: 100%;
  background: #eff6ff;
  color: #1e40af;
  font-weight: 500;
}
.upcoming-meta-item.meta-tujuan i {
  color: #3b82f6;
}

.upcoming-status-badge {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  padding: 1.5px 5px;
  border-radius: 999px;
  font-size: clamp(0.55rem, 1.2vw, 0.575rem);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.2px;
}
.upcoming-status-badge.menunggu {
  background: #fef3c7;
  color: #92400e;
  border: 1px solid #fde68a;
}
.upcoming-status-badge.approve {
  background: #d1fae5;
  color: #065f46;
  border: 1px solid #a7f3d0;
}

.upcoming-recurring-badge {
  background: linear-gradient(135deg, #a855f7, #9333ea);
  color: white;
  padding: 1px 4px;
  border-radius: 3px;
  font-size: clamp(0.5rem, 1.1vw, 0.55rem);
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 2px;
}

.no-upcoming-schedule {
  text-align: center;
  padding: clamp(1.25rem, 2.5vw, 1.75rem) clamp(0.625rem, 1.2vw, 0.875rem);
  color: #64748b;
}
.no-upcoming-schedule i {
  font-size: clamp(1.75rem, 3.5vw, 2.25rem);
  color: #cbd5e1;
  margin-bottom: 6px;
  opacity: 0.4;
}
.no-upcoming-schedule p {
  font-size: clamp(0.7rem, 1.6vw, 0.75rem);
  margin: 5px 0 0;
  color: #64748b;
}

@keyframes slideInUp {
  from { opacity: 0; transform: translateY(15px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Scrollbar mini untuk upcoming */
.upcoming-schedule-grid::-webkit-scrollbar {
  width: 4px;
}
.upcoming-schedule-grid::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 10px;
}
.upcoming-schedule-grid::-webkit-scrollbar-thumb {
  background: linear-gradient(135deg, #0ea5e9, #0284c7);
  border-radius: 10px;
}

/* PENDING SECTION */
.pending-approval-section { 
  background: #fff; 
  border-radius: clamp(12px, 1.5vw, 16px);
  padding: clamp(1rem, 2vw, 1.5rem);
  box-shadow: 0 2px 10px rgba(0,0,0,0.06); 
  max-height: 600px; 
  overflow-y: auto;
}
.pending-approval-section h3 { 
  font-size: clamp(1rem, 2.5vw, 1.125rem); 
  font-weight: 700; 
  color: #0f172a; 
  margin-bottom: 1rem; 
  display: flex; 
  align-items: center; 
  gap: 8px; 
}
.pending-approval-item { 
  background: #f8fafc; 
  border-radius: 10px; 
  padding: clamp(0.875rem, 1.5vw, 1rem);
  margin-bottom: 0.75rem; 
  border-left: 4px solid #f59e0b; 
  transition: all 0.2s ease; 
  cursor: pointer; 
}
.pending-approval-item:hover { 
  transform: translateX(4px); 
  box-shadow: 0 4px 12px rgba(0,0,0,0.08); 
}
.pending-approval-item.ruangan   { border-left-color: #10b981; }
.pending-approval-item.kendaraan { border-left-color: #3b82f6; }
.pending-approval-item.kolam     { border-left-color: #06b6d4; }
.pending-approval-item.studio    { border-left-color: #a855f7; }

.pending-approval-header { 
  display: flex; 
  justify-content: space-between; 
  align-items: start; 
  margin-bottom: 8px; 
  flex-wrap: wrap;
  gap: 8px;
}
.pending-approval-title { 
  font-weight: 700; 
  font-size: clamp(0.875rem, 2vw, 0.95rem); 
  color: #0f172a; 
  margin-bottom: 4px; 
}
.pending-approval-user { 
  font-size: clamp(0.75rem, 1.8vw, 0.8rem); 
  color: #64748b; 
  display: flex; 
  align-items: center; 
  gap: 4px; 
}
.pending-approval-badge { 
  background: linear-gradient(135deg, #f59e0b, #d97706); 
  color: white; 
  padding: 3px 8px; 
  border-radius: 6px; 
  font-size: 0.65rem; 
  font-weight: 700; 
  text-transform: uppercase; 
  display: flex; 
  align-items: center; 
  gap: 4px; 
  white-space: nowrap;
}
.pending-approval-info { 
  display: flex; 
  gap: 10px; 
  font-size: clamp(0.7rem, 1.8vw, 0.8rem); 
  color: #64748b; 
  margin-top: 6px; 
  flex-wrap: wrap;
}

/* CALENDAR SECTION */
.calendar-mini-section { 
  background: #fff; 
  border-radius: clamp(12px, 1.5vw, 16px);
  padding: clamp(1rem, 2vw, 1.5rem);
  box-shadow: 0 2px 10px rgba(0,0,0,0.06); 
}
.calendar-mini-section h3 { 
  font-size: clamp(1rem, 2.5vw, 1.125rem); 
  font-weight: 700; 
  color: #0f172a; 
  margin-bottom: 1rem; 
  display: flex; 
  align-items: center; 
  gap: 8px; 
}
.mini-calendar-controls { 
  display: flex; 
  justify-content: space-between; 
  align-items: center; 
  margin-bottom: 1rem; 
}
.mini-calendar-controls button { 
  background: linear-gradient(135deg, #1b91ffff , #126b9eff, #0b639eff, #00579eff);
  color: white; 
  border: none; 
  width: 32px; 
  height: 32px; 
  border-radius: 8px; 
  cursor: pointer; 
  font-weight: 700; 
  transition: all 0.2s ease; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
}
.mini-calendar-controls button:hover { 
  transform: scale(1.05); 
  box-shadow: 0 4px 12px rgba(102,126,234,0.4); 
}
.mini-calendar-month { 
  font-weight: 700; 
  font-size: clamp(0.9rem, 2.2vw, 1rem); 
  color: #0f172a; 
}
.mini-calendar-grid { 
  display: grid; 
  grid-template-columns: repeat(7, 1fr); 
  gap: clamp(2px, 0.5vw, 4px); 
}
.mini-calendar-day-header { 
  text-align: center; 
  font-weight: 700; 
  color: #64748b; 
  padding: 6px 4px; 
  font-size: clamp(0.65rem, 1.5vw, 0.7rem); 
  text-transform: uppercase; 
}
.mini-calendar-day { 
  aspect-ratio: 1; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  border-radius: 8px; 
  font-size: clamp(0.75rem, 1.8vw, 0.8rem); 
  font-weight: 600; 
  color: #0f172a; 
  cursor: pointer; 
  transition: all 0.2s ease; 
  position: relative; 
}
.mini-calendar-day:hover:not(.other-month) { 
  background: #f1f5f9; 
  transform: scale(1.05); 
}
.mini-calendar-day.other-month { 
  color: #cbd5e1; 
  cursor: default; 
}
.mini-calendar-day.today { 
  background: linear-gradient(135deg, #1b91ffff , #126b9eff, #0b639eff, #00579eff);
  color: white; 
  font-weight: 700; 
}
.mini-calendar-day.has-booking { 
  background: rgba(239, 68, 68, 0.1); 
  border-radius: 8px; 
  font-weight: 600; 
}
.mini-calendar-day.has-booking:hover { 
  background: rgba(239, 68, 68, 0.15); 
  box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3); 
}
.mini-calendar-day.has-recurring {
  background: rgba(168, 85, 247, 0.15); 
  border: 2px dashed #a855f7;
}
.mini-calendar-day.has-recurring:hover {
  background: rgba(168, 85, 247, 0.25); 
  box-shadow: 0 4px 10px rgba(168, 85, 247, 0.4);
}
.mini-calendar-day.has-recurring::before {
  content: '🔄'; 
  position: absolute; 
  top: 1px; 
  left: 1px; 
  font-size: clamp(0.5rem, 1.2vw, 0.6rem); 
  z-index: 2;
}
.booking-badge { 
  position: absolute; 
  top: -6px; 
  right: -6px; 
  background: linear-gradient(135deg, #ef4444, #dc2626); 
  color: white; 
  width: 22px; 
  height: 22px; 
  border-radius: 50%; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  font-size: clamp(0.65rem, 1.5vw, 0.7rem); 
  font-weight: 800; 
  box-shadow: 0 2px 6px rgba(239, 68, 68, 0.5); 
  border: 2px solid #fff; 
  animation: pulse 2s infinite; 
}
@keyframes pulse { 
  0%, 100% { transform: scale(1); } 
  50% { transform: scale(1.08); } 
}
.mini-calendar-day.has-recurring .booking-badge {
  background: linear-gradient(135deg, #1b91ffff , #126b9eff, #0b639eff, #00579eff);
}

.calendar-legend {
  display: flex; 
  gap: 12px; 
  margin-top: 12px; 
  padding-top: 12px; 
  border-top: 1px solid #e2e8f0;
  font-size: clamp(0.7rem, 1.6vw, 0.75rem); 
  color: #64748b; 
  flex-wrap: wrap;
}
.calendar-legend span { 
  display: flex; 
  align-items: center; 
  gap: 5px; 
}
.legend-box { 
  width: 18px; 
  height: 18px; 
  border-radius: 4px; 
  flex-shrink: 0; 
}
.legend-box.has-booking { 
  background: rgba(239, 68, 68, 0.1); 
  border: 2px solid #ef4444; 
}
.legend-box.has-recurring { 
  background: rgba(168, 85, 247, 0.15); 
  border: 2px dashed #a855f7; 
}

/* AVAILABILITY GRID */
.availability-grid { 
  display: grid; 
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 150px), 1fr));
  gap: clamp(0.75rem, 1.5vw, 1rem);
  margin-bottom: clamp(1rem, 2vw, 1.5rem);
}
.availability-item { 
  background: #fff; 
  border-radius: clamp(12px, 1.5vw, 14px);
  padding: clamp(1rem, 2vw, 1.25rem);
  text-align: center; 
  transition: all 0.3s ease; 
  box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}
.availability-item:hover { 
  transform: translateY(-4px); 
  box-shadow: 0 8px 24px rgba(0,0,0,0.1); 
}
.availability-emoji { 
  font-size: clamp(1.5rem, 4vw, 2rem); 
  margin-bottom: 0.5rem; 
}
.availability-label { 
  font-size: clamp(0.7rem, 1.6vw, 0.75rem); 
  color: #64748b; 
  font-weight: 600; 
  margin-bottom: 0.5rem; 
  text-transform: uppercase; 
  letter-spacing: 0.5px; 
}
.availability-number { 
  font-size: clamp(1.375rem, 3.5vw, 1.75rem); 
  font-weight: 800; 
  color: #0f172a; 
  line-height: 1; 
}
.availability-subtext { 
  font-size: clamp(0.65rem, 1.5vw, 0.7rem); 
  color: #94a3b8; 
  margin-top: 0.5rem; 
}
.availability-bar { 
  width: 100%; 
  height: 5px; 
  background: #e2e8f0; 
  border-radius: 3px; 
  margin-top: 0.75rem; 
  overflow: hidden; 
}
.availability-bar-fill { 
  height: 100%; 
  background: linear-gradient(135deg, #1b91ffff , #126b9eff, #0b639eff, #00579eff);
  border-radius: 3px; 
  transition: width 0.6s ease; 
}

/* STATS GRID */
.stats-grid { 
  display: grid; 
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 110px), 1fr));
  gap: clamp(0.5rem, 1vw, 0.75rem);
  margin-bottom: clamp(1rem, 2vw, 1.5rem);
}
.stat-card { 
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); 
  border: 1px solid #e2e8f0; 
  border-radius: clamp(10px, 1.2vw, 12px);
  padding: clamp(0.875rem, 1.5vw, 1rem);
  text-align: center; 
  transition: all 0.3s ease; 
}
.stat-card:hover { 
  transform: translateY(-2px); 
  box-shadow: 0 6px 20px rgba(0,0,0,0.08); 
}
.stat-label { 
  font-size: clamp(0.625rem, 1.4vw, 0.65rem); 
  color: #64748b; 
  font-weight: 700; 
  text-transform: uppercase; 
  letter-spacing: 0.5px; 
  margin-bottom: 0.5rem; 
}
.stat-value { 
  font-size: clamp(1.25rem, 3vw, 1.5rem); 
  font-weight: 800; 
  background: linear-gradient(135deg, #1b91ffff , #126b9eff, #0b639eff, #00579eff);
  -webkit-background-clip: text; 
  -webkit-text-fill-color: transparent; 
  background-clip: text; 
}

/* FOOTER */
.app-footer-mini { 
  text-align: center; 
  padding: clamp(1rem, 2vw, 1.5rem) 0.5rem;
  opacity: .7; 
  font-size: clamp(0.75rem, 1.8vw, 0.813rem); 
}

/* MODAL */
.modal-calendar { 
  display: none; 
  position: fixed; 
  top: 0; 
  left: 0; 
  width: 100%; 
  height: 100%; 
  background: rgba(0,0,0,0.65); 
  backdrop-filter: blur(4px); 
  z-index: 10000; 
  align-items: center; 
  justify-content: center; 
  padding: 20px; 
  overflow-y: auto; 
}
.modal-calendar.show { display: flex; }
.modal-content-cal { 
  background: #fff; 
  border-radius: clamp(16px, 2vw, 20px);
  padding: clamp(1.5rem, 2.5vw, 2rem);
  max-width: 650px; 
  width: 100%; 
  max-height: 85vh; 
  overflow-y: auto; 
  box-shadow: 0 20px 60px rgba(0,0,0,0.3); 
}
.modal-header-cal { 
  display: flex; 
  justify-content: space-between; 
  align-items: center; 
  margin-bottom: 1.25rem; 
  border-bottom: 1px solid #e2e8f0; 
  padding-bottom: 1rem; 
}
.modal-header-cal h2 { 
  font-size: clamp(1.125rem, 2.8vw, 1.375rem); 
  font-weight: 700; 
  color: #0f172a; 
}
.modal-close-cal { 
  background: none; 
  border: none; 
  font-size: 1.5rem; 
  cursor: pointer; 
  color: #94a3b8; 
  transition: all 0.2s ease; 
  width: 32px; 
  height: 32px; 
  border-radius: 8px; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
}
.modal-close-cal:hover { 
  color: #0f172a; 
  background: #f1f5f9; 
}
.booking-detail-card { 
  background: #f8fafc; 
  border-radius: 10px; 
  padding: clamp(0.875rem, 1.5vw, 1rem);
  margin-bottom: 0.75rem; 
  border-left: 4px solid #667eea; 
  transition: all 0.2s ease; 
}
.booking-detail-card:hover { 
  transform: translateX(4px); 
  box-shadow: 0 4px 10px rgba(0,0,0,0.08); 
}
.booking-detail-card.ruangan   { border-left-color: #10b981; }
.booking-detail-card.kendaraan { border-left-color: #3b82f6; }
.booking-detail-card.kolam     { border-left-color: #06b6d4; }
.booking-detail-card.studio    { border-left-color: #a855f7; }

.booking-detail-header { 
  display: flex; 
  justify-content: space-between; 
  align-items: start; 
  margin-bottom: 10px; 
  flex-wrap: wrap;
  gap: 8px;
}
.booking-detail-title { 
  font-weight: 700; 
  font-size: clamp(0.9rem, 2.2vw, 1rem); 
  color: #0f172a; 
  margin-bottom: 4px; 
}
.booking-detail-user { 
  font-size: clamp(0.75rem, 1.8vw, 0.813rem); 
  color: #64748b; 
  display: flex; 
  align-items: center; 
  gap: 4px; 
  flex-wrap: wrap;
}
.booking-status-badge { 
  padding: 4px 8px; 
  border-radius: 6px; 
  font-size: 0.65rem; 
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
  background: linear-gradient(135deg, #1b91ffff , #126b9eff, #0b639eff, #00579eff);
  color: white;
  padding: 2px 6px; 
  border-radius: 4px; 
  font-size: 0.65rem; 
  margin-left: 6px;
}

@media (max-width: 768px) {
  .upcoming-schedule-grid { grid-template-columns: 1fr; }
  .upcoming-schedule-header {
    flex-direction: column;
    align-items: flex-start;
  }
}

@media print {
  .dashboard-wrapper { padding: 0; }
  .user-dashboard::before,
  .user-dashboard::after { display: none; }
}
</style>

<div class="dashboard-wrapper">
  <!-- HERO -->
  <div class="user-dashboard">
    <div class="content">
      <h1 class="welcome-text">Halo, <?= h($admin_name) ?> 👋</h1>
      <p class="welcome-subtitle">Selamat Datang Di SIPINJAM, Have A Nice Day</p>
    </div>
  </div>

  <!-- JADWAL MENDATANG -->
  <?php if (!empty($upcoming_bookings)): ?>
    <div class="upcoming-schedule-section">
      <div class="upcoming-schedule-header">
        <div class="upcoming-schedule-title">
          <i class="fas fa-calendar-week"></i>
          <span>Jadwal Peminjaman Mendatang (7 Hari)</span>
        </div>
        <div class="upcoming-count-badge">
          <?= count($upcoming_bookings) ?> Booking
        </div>
      </div>

      <div class="upcoming-schedule-grid">
        <?php
        $bulan_indo = [
          'January' => 'Jan', 'February' => 'Feb', 'March' => 'Mar',
          'April' => 'Apr', 'May' => 'Mei', 'June' => 'Jun',
          'July' => 'Jul', 'August' => 'Agt', 'September' => 'Sep',
          'October' => 'Okt', 'November' => 'Nov', 'December' => 'Des'
        ];
        $hari_indo = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        foreach ($upcoming_bookings as $booking):
          $tgl_mulai_formatted = date('d F Y', strtotime($booking['tgl_mulai']));
          $tgl_mulai_formatted = str_replace(array_keys($bulan_indo), array_values($bulan_indo), $tgl_mulai_formatted);
          $hari = $hari_indo[date('w', strtotime($booking['tgl_mulai']))];

          $status_raw   = strtolower(trim($booking['status'] ?? ''));
          $status_class = ($status_raw === 'menunggu' || $status_raw === 'pending') ? 'menunggu' : 'approve';
          $status_label = ($status_class === 'menunggu') ? 'Menunggu' : 'Disetujui';

          $is_recurring_val = strtolower(trim($booking['is_recurring'] ?? 'no'));
          $is_recurring = in_array($is_recurring_val, ['yes','y','1'], true);
          $recurring_label = '';
          if ($is_recurring && !empty($booking['recurring_days'])) {
            $day_names_short = [1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum', 6 => 'Sab', 7 => 'Min'];
            $days = explode(',', $booking['recurring_days']);
            $day_labels = array_map(function ($d) use ($day_names_short) {
              return $day_names_short[(int)trim($d)] ?? $d;
            }, $days);
            $recurring_label = implode(', ', $day_labels);
          }
        ?>
        <div class="upcoming-schedule-item <?= h($booking['type']) ?> status-<?= $status_class; ?>">
          <div class="upcoming-schedule-date">
            <i class="fas fa-calendar-day"></i>
            <span><?= $hari . ', ' . $tgl_mulai_formatted; ?></span>
          </div>

          <div class="upcoming-schedule-item-name">
            <i class="<?= h($booking['icon']) ?>"></i>
            <?= h($booking['item_name']) ?>
            <?php if ($is_recurring): ?>
              <span class="upcoming-recurring-badge">
                <i class="fas fa-repeat"></i> Rutin
              </span>
            <?php endif; ?>
          </div>

          <div class="upcoming-schedule-meta">
            <div class="upcoming-meta-item">
              <i class="fas fa-clock"></i>
              <span><?= substr($booking['waktu_mulai'] ?? '00:00', 0, 5) . ' - ' . substr($booking['waktu_selesai'] ?? '23:59', 0, 5); ?></span>
            </div>

            <div class="upcoming-meta-item">
              <i class="fas fa-user"></i>
              <span><?= h($booking['user_name']) ?></span>
            </div>

            <?php if ($booking['type'] === 'kendaraan' && !empty($booking['supir_name'])): ?>
              <div class="upcoming-meta-item" style="background:#e0f2fe;color:#075985;">
                <i class="fas fa-id-card"></i>
                <span><?= h($booking['supir_name']) ?></span>
              </div>
            <?php endif; ?>

            <?php if ($is_recurring && $recurring_label): ?>
              <div class="upcoming-meta-item" style="color:#a855f7;background:#faf5ff;">
                <i class="fas fa-redo"></i>
                <span><?= $recurring_label ?></span>
              </div>
            <?php endif; ?>

            <span class="upcoming-status-badge <?= $status_class; ?>">
              <?= $status_label; ?>
            </span>

            <?php if (!empty($booking['tujuan'])): ?>
              <div class="upcoming-meta-item meta-tujuan">
                <i class="fas fa-bullseye"></i>
                <span>
                  <?php 
                    $t = $booking['tujuan']; 
                    echo h(strlen($t) > 60 ? substr($t,0,60).'...' : $t);
                  ?>
                </span>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php else: ?>
    <div class="upcoming-schedule-section">
      <div class="upcoming-schedule-header">
        <div class="upcoming-schedule-title">
          <i class="fas fa-calendar-week"></i>
          <span>Jadwal Peminjaman Mendatang (7 Hari)</span>
        </div>
      </div>
      <div class="no-upcoming-schedule">
        <i class="fas fa-calendar-times"></i>
        <p><strong>Tidak ada jadwal peminjaman</strong></p>
        <p style="font-size: 0.8rem;">dalam 7 hari ke depan</p>
      </div>
    </div>
  <?php endif; ?>

  <!-- GRID: CALENDAR + PENDING -->
  <div class="main-content-grid">
    <!-- LEFT: MINI CALENDAR -->
    <div class="calendar-mini-section">
      <h3><i class="fas fa-calendar-alt"></i> Kalender Bulan Ini</h3>
      <div class="mini-calendar-controls">
        <button onclick="prevMonth()"><i class="fas fa-chevron-left"></i></button>
        <span class="mini-calendar-month" id="miniMonth"></span>
        <button onclick="nextMonth()"><i class="fas fa-chevron-right"></i></button>
      </div>
      <div class="mini-calendar-grid" id="miniCalendar"></div>
      
      <div class="calendar-legend">
        <span><div class="legend-box has-booking"></div> Jadwal Biasa</span>
        <span><div class="legend-box has-recurring"></div> Jadwal Rutin</span>
      </div>
    </div>

    <!-- RIGHT: PENDING -->
    <div class="pending-approval-section">
      <h3><i class="fas fa-hourglass-half"></i> Menunggu Persetujuan (<?= nf($pending_total) ?>)</h3>
      <?php if (empty($pending_list)): ?>
        <div style="text-align:center;padding:40px 20px;color:#94a3b8;">
          <i class="fas fa-check-circle" style="font-size:2.5rem;margin-bottom:10px;opacity:0.3;"></i>
          <p style="color:#64748b;font-size:0.85rem;">Tidak ada peminjaman yang menunggu persetujuan</p>
        </div>
      <?php else: ?>
        <?php foreach($pending_list as $item): ?>
          <div class="pending-approval-item <?= h($item['type']) ?>">
            <div class="pending-approval-header">
              <div>
                <div class="pending-approval-title">
                  <i class="<?= h($item['icon']) ?>"></i> <?= h($item['item_name']) ?>
                </div>
                <div class="pending-approval-user">
                  <i class="fas fa-user"></i> <?= h($item['user_name']) ?>
                </div>
              </div>
              <span class="pending-approval-badge">
                <i class="fas fa-hourglass-half"></i> Pending
              </span>
            </div>
            <div class="pending-approval-info">
              <span><i class="fas fa-calendar"></i> <?= h($item['tgl_mulai']) ?></span>
              <span><i class="fas fa-clock"></i> <?= h($item['waktu_mulai']) ?></span>
              <?php if (!empty($item['tujuan'])): ?>
                <span>
                  <i class="fas fa-bullseye"></i>
                  <?php 
                    $t = $item['tujuan'];
                    echo h(strlen($t) > 60 ? substr($t, 0, 60).'...' : $t);
                  ?>
                </span>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- KETERSEDIAAN -->
  <h3 class="section-title">Ketersediaan Real-time</h3>
  <div class="availability-grid">
    <?php
    $items = [
      ['emoji' => '🏢', 'label' => 'Ruangan',  'free' => $stats['barang_tersedia'],  'total' => $stats['barang_total']],
      ['emoji' => '🚗', 'label' => 'Kendaraan','free' => $stats['kendaraan_tersedia'],'total' => $stats['kendaraan_total']],
      ['emoji' => '🏊', 'label' => 'Kolam',    'free' => $stats['kolam_tersedia'],    'total' => $stats['kolam_total']],
      ['emoji' => '📹', 'label' => 'Studio',   'free' => $stats['studio_tersedia'],   'total' => $stats['studio_total']],
    ];
    foreach ($items as $item):
      $p = pct($item['free'], $item['total']);
    ?>
      <div class="availability-item">
        <div class="availability-emoji"><?= $item['emoji'] ?></div>
        <div class="availability-label"><?= h($item['label']) ?></div>
        <div class="availability-number"><?= nf($item['free']) ?></div>
        <div class="availability-subtext">dari <?= nf($item['total']) ?></div>
        <div class="availability-bar">
          <div class="availability-bar-fill" style="width: <?= $p ?>%;"></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- STATISTIK SISTEM -->
  <h3 class="section-title">Statistik Sistem</h3>
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-label">Ruangan</div>
      <div class="stat-value"><?= nf($total_barang) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Kendaraan</div>
      <div class="stat-value"><?= nf($total_kendaraan) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">User</div>
      <div class="stat-value"><?= nf($total_user) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Kolam</div>
      <div class="stat-value"><?= nf($total_kolam) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Studio</div>
      <div class="stat-value"><?= nf($total_studio) ?></div>
    </div>
  </div>

  <div class="app-footer-mini">
    <p><b>&copy; 2025 SIPINJAM</b> — Sistem Informasi Peminjaman Pelita Cemerlang School</p>
  </div>
</div>

<!-- MODAL DETAIL PEMINJAMAN -->
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
const bookings = <?= $all_bookings_json ?>;
let currentDate = new Date();

const monthNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
const dayNames   = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
const dayNameMap = {1:'Senin',2:'Selasa',3:'Rabu',4:'Kamis',5:'Jumat',6:'Sabtu',7:'Minggu'};

function jsToDayNumber(jsDay) { return jsDay === 0 ? 7 : jsDay; }

console.log('📊 Total Bookings:', bookings.length);
console.log('🔄 Recurring Bookings:', bookings.filter(b => b.is_recurring).length);

function renderMiniCalendar() {
  const year  = currentDate.getFullYear();
  const month = currentDate.getMonth();
  document.getElementById('miniMonth').textContent = `${monthNames[month]} ${year}`;

  const firstDay       = new Date(year, month, 1).getDay();
  const daysInMonth    = new Date(year, month + 1, 0).getDate();
  const daysInPrevMonth= new Date(year, month, 0).getDate();
  const grid           = document.getElementById('miniCalendar');
  grid.innerHTML = '';

  // Header hari
  dayNames.forEach(day => {
    const header = document.createElement('div');
    header.className = 'mini-calendar-day-header';
    header.textContent = day;
    grid.appendChild(header);
  });

  // Hari bulan sebelumnya
  for (let i = firstDay - 1; i >= 0; i--) {
    const day = document.createElement('div');
    day.className = 'mini-calendar-day other-month';
    day.textContent = daysInPrevMonth - i;
    grid.appendChild(day);
  }

  const today = new Date();
  for (let i = 1; i <= daysInMonth; i++) {
    const day = document.createElement('div');
    day.className = 'mini-calendar-day';
    const isToday = (
      i === today.getDate() &&
      month === today.getMonth() &&
      year === today.getFullYear()
    );
    if (isToday) day.classList.add('today');

    const dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(i).padStart(2,'0')}`;
    const dateObj = new Date(year, month, i);
    const dayBookings = getBookingsForDate(dateStr, dateObj);

    const normalBookings   = dayBookings.filter(b => !b.is_recurring);
    const recurringBookings= dayBookings.filter(b => b.is_recurring);

    if (normalBookings.length > 0) day.classList.add('has-booking');
    if (recurringBookings.length > 0) day.classList.add('has-recurring');

    if (dayBookings.length > 0) {
      const dayContent = document.createElement('div');
      dayContent.style.position = 'relative';
      dayContent.style.zIndex   = '1';
      dayContent.textContent    = i;
      day.appendChild(dayContent);

      const badge = document.createElement('div');
      badge.className  = 'booking-badge';
      badge.textContent= dayBookings.length;
      day.appendChild(badge);

      day.onclick = () => showModal(dateStr, dayBookings);
    } else {
      day.textContent = i;
    }

    grid.appendChild(day);
  }

  // Sisa kotak bulan berikutnya
  const totalCells     = 7 + firstDay + daysInMonth;
  const remainingDays  = (Math.ceil(totalCells / 7) * 7) - totalCells;
  for (let i = 1; i <= remainingDays; i++) {
    const day = document.createElement('div');
    day.className = 'mini-calendar-day other-month';
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
    const end   = b.tgl_selesai ? new Date(String(b.tgl_selesai).split(' ')[0]) : start;
    const inRange = (checkDate >= start && checkDate <= end);

    if (!inRange) return false;

    if (!b.is_recurring) return true;

    if (!b.recurring_days) {
      console.warn('⚠️ Recurring tanpa recurring_days:', b.item_name);
      return false;
    }

    const allowedDays = b.recurring_days.split(',').map(d => parseInt(d.trim(),10));
    return allowedDays.includes(dayNumber);
  });
}

function formatRecurringDays(daysStr) {
  if (!daysStr) return '-';
  return daysStr
    .split(',')
    .map(d => dayNameMap[parseInt(d.trim(),10)] || d)
    .join(', ');
}

function showModal(dateStr, list) {
  const modal = document.getElementById('bookingModal');
  const title = document.getElementById('modalTitle');
  const body  = document.getElementById('modalBody');

  const d = new Date(dateStr);
  const formatted = `${d.getDate()} ${monthNames[d.getMonth()]} ${d.getFullYear()}`;
  title.textContent = `Peminjaman pada ${formatted}`;

  if (!list || list.length === 0) {
    body.innerHTML = '<p style="text-align:center;color:#94a3b8;padding:30px;">Tidak ada peminjaman</p>';
  } else {
    body.innerHTML = list.map(b => {
      const statusRaw   = String(b.status || '').toLowerCase();
      const statusClass = (statusRaw === 'menunggu' || statusRaw === 'pending') ? 'menunggu' : 'approve';
      const recurringInfo = (b.is_recurring && b.recurring_days)
        ? `<div style="font-size:0.75rem;color:#a855f7;margin-top:6px;">
             <i class="fas fa-redo"></i> Setiap: ${formatRecurringDays(b.recurring_days)}
           </div>`
        : '';
      const tujuanHtml = (b.tujuan && b.tujuan.trim() !== '')
        ? `<div style="font-size:0.75rem;color:#1e40af;margin-top:6px;display:flex;align-items:center;gap:4px;">
             <i class="fas fa-bullseye"></i>
             <span>${b.tujuan.length > 80 ? b.tujuan.substring(0,80) + '…' : b.tujuan}</span>
           </div>`
        : '';

      return `
        <div class="booking-detail-card ${b.type}">
          <div class="booking-detail-header">
            <div>
              <div class="booking-detail-title">
                <i class="${b.icon}"></i> ${b.item_name || '-'}
              </div>
              <div class="booking-detail-user">
                <i class="fas fa-user"></i> ${b.user_name || '-'}
                ${b.is_recurring ? '<span class="recurring-badge-inline">🔄 Rutin</span>' : ''}
              </div>
            </div>
            <span class="booking-status-badge ${statusClass}">
              ${b.status || '-'}
            </span>
          </div>
          ${recurringInfo}
          ${tujuanHtml}
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
  renderMiniCalendar();
}
function nextMonth() {
  currentDate.setMonth(currentDate.getMonth() + 1);
  renderMiniCalendar();
}

document.getElementById('bookingModal').onclick = (e) => {
  if (e.target.id === 'bookingModal') closeModal();
};
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') closeModal();
});

renderMiniCalendar();

  if ("serviceWorker" in navigator) {
    navigator.serviceWorker
      .register("/testsipinjam/firebase-messaging-sw.js")
      .then(() => {
        initFCM();
      });
  }

</script>
