<?php
// ========= Pastikan koneksi $conn (mysqli) sudah dibuat sebelum file ini =========
// Include FCM Helper
include_once '../../fcm_helper.php';

// ================== PROSES AKSI ==================
if (isset($_GET['id_approve'])) {
  $id_approve = $_GET['id_approve'];
  
  // Ambil data peminjaman untuk notifikasi
  $query = "SELECT pb.*, u.id as user_id, u.nama_lengkap, b.nama_barang 
            FROM pinjambarang pb 
            JOIN user u ON pb.id_user = u.id 
            JOIN barang b ON pb.id_barang = b.id 
            WHERE pb.id_pinjam = ?";
  $stmt_data = $conn->prepare($query);
  $stmt_data->bind_param("s", $id_approve);
  $stmt_data->execute();
  $result = $stmt_data->get_result();
  $data = $result->fetch_assoc();
  $stmt_data->close();
  
  // Update status
  $stmtA = $conn->prepare("UPDATE pinjambarang SET status='approve' WHERE id_pinjam=?");
  $stmtA->bind_param("s", $id_approve);
  $stmtA->execute();
  $stmtA->close();
  
  // Kirim notifikasi ke user jika data ditemukan
  if ($data) {
    $notifTitle = "✅ Peminjaman Gedung Disetujui";
    $notifBody = "Peminjaman " . $data['nama_barang'] . " pada tanggal " . date('d/m/Y', strtotime($data['tgl_mulai'])) . " telah disetujui oleh admin.";
    $clickAction = "http://localhost/TESTSIPINJAM/user/?view=databooking";
    
    $notifResult = sendFCMNotification($data['user_id'], $notifTitle, $notifBody, $clickAction);
    
    // Log hasil notifikasi (opsional)
    error_log("FCM Approval Result: " . json_encode($notifResult));
  }
  
  $redirect_params = $_GET;
  unset($redirect_params['id_approve']);
  $redirect_url = '?' . http_build_query($redirect_params);
  
  echo "<script>window.location.href='" . htmlspecialchars($redirect_url, ENT_QUOTES) . "';</script>";
  exit;
}

if (isset($_GET['id_tolak'])) {
  $id_tolak = $_GET['id_tolak'];
  
  // Ambil data untuk notifikasi
  $query = "SELECT pb.*, u.id as user_id, u.nama_lengkap, b.nama_barang 
            FROM pinjambarang pb 
            JOIN user u ON pb.id_user = u.id 
            JOIN barang b ON pb.id_barang = b.id 
            WHERE pb.id_pinjam = ?";
  $stmt_data = $conn->prepare($query);
  $stmt_data->bind_param("s", $id_tolak);
  $stmt_data->execute();
  $result = $stmt_data->get_result();
  $data = $result->fetch_assoc();
  $stmt_data->close();
  
  // Update status
  $stmtT = $conn->prepare("UPDATE pinjambarang SET status='ditolak' WHERE id_pinjam=?");
  $stmtT->bind_param("s", $id_tolak);
  $stmtT->execute();
  $stmtT->close();
  
  // Kirim notifikasi penolakan
  if ($data) {
    $notifTitle = "❌ Peminjaman Gedung Ditolak";
    $notifBody = "Maaf, peminjaman " . $data['nama_barang'] . " pada tanggal " . date('d/m/Y', strtotime($data['tgl_mulai'])) . " ditolak oleh admin.";
    $clickAction = "http://localhost/TESTSIPINJAM/user/?view=databooking";
    
    sendFCMNotification($data['user_id'], $notifTitle, $notifBody, $clickAction);
  }
  
  $redirect_params = $_GET;
  unset($redirect_params['id_tolak']);
  $redirect_url = '?' . http_build_query($redirect_params);
  
  echo "<script>window.location.href='" . htmlspecialchars($redirect_url, ENT_QUOTES) . "';</script>";
  exit;
}

if (isset($_GET['id_batal'])) {
  $id_batal = $_GET['id_batal'];
  $stmtC = $conn->prepare("UPDATE pinjambarang SET status='menunggu' WHERE id_pinjam=?");
  $stmtC->bind_param("s", $id_batal);
  $stmtC->execute();
  $stmtC->close();
  
  $redirect_params = $_GET;
  unset($redirect_params['id_batal']);
  $redirect_url = '?' . http_build_query($redirect_params);
  
  echo "<script>window.location.href='" . htmlspecialchars($redirect_url, ENT_QUOTES) . "';</script>";
  exit;
}

if (isset($_POST['hapus']) && isset($_POST['id_pinjam'])) {
  $id_pinjam = $_POST['id_pinjam'];
  $stmtD = $conn->prepare("DELETE FROM pinjambarang WHERE id_pinjam=?");
  $stmtD->bind_param("s", $id_pinjam);
  $stmtD->execute();
  $stmtD->close();
  
  $redirect_url = '?' . http_build_query($_GET);
  
  echo "<script>window.location.href='" . htmlspecialchars($redirect_url, ENT_QUOTES) . "';</script>";
  exit;
}

// ================== HELPER VALIDASI / FORMAT ==================
function normalize_date_input($v) {
  $v = trim((string)$v);
  if ($v === '') return '';
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
    return '';
  }
  return $v;
}

function fmt_tgl($ymd){
  if(!$ymd || $ymd==='0000-00-00') return '-';
  $parts = explode('-', $ymd);
  if (count($parts) !== 3) return '-';
  [$y,$m,$d] = $parts;
  $bulan = ["","Jan","Feb","Mar","Apr","Mei","Jun","Jul","Agu","Sep","Okt","Nov","Des"];
  $mi = (int)$m;
  if ($mi < 1 || $mi > 12) return '-';
  return sprintf("%02d %s %s",(int)$d,$bulan[$mi],$y);
}

function yes($v){
  $v = strtolower(trim((string)$v));
  return in_array($v, ['ya','iya','yes','y','true','1'], true);
}

function build_query_keep(array $extra = []){
  $q = $_GET;
  foreach($extra as $k=>$v){
    if ($v === null) {
      unset($q[$k]);
    } else {
      $q[$k] = $v;
    }
  }
  return http_build_query($q);
}

function parseRecurringDays($str) {
  if (!$str) return [];
  if (preg_match_all('/[1-7]/', (string)$str, $m)) {
    $nums = array_map('intval', $m[0]);
    $nums = array_values(array_unique($nums));
    sort($nums, SORT_NUMERIC);
    return $nums;
  }
  return [];
}

function mapHariByNumber($nums) {
  $map = [
    1 => 'Senin',
    2 => 'Selasa',
    3 => 'Rabu',
    4 => 'Kamis',
    5 => 'Jumat',
    6 => 'Sabtu',
    7 => 'Minggu',
  ];
  $out = [];
  foreach ($nums as $n) {
    $n = (int)$n;
    if (isset($map[$n])) $out[] = $map[$n];
  }
  return $out;
}

// ================== AMBIL PARAMETER FILTER & PAGINATION ==================
$keyword   = trim($_GET['q']        ?? '');
$status_f  = trim($_GET['status']   ?? '');
$dari_raw  = $_GET['dari']          ?? '';
$sampai_raw= $_GET['sampai']        ?? '';

$dari      = normalize_date_input($dari_raw);
$sampai    = normalize_date_input($sampai_raw);

$id_barang_f = trim($_GET['id_barang'] ?? '');
$id_user_f   = trim($_GET['id_user']   ?? '');

$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$start    = ($page - 1) * $per_page;

// ================== Pilihan dropdown dari DB ==================
$barang_opts = [];
$resb = $conn->query("SELECT id, nama_barang FROM barang ORDER BY nama_barang ASC");
if ($resb) {
  while ($r = $resb->fetch_assoc()) { $barang_opts[] = $r; }
  $resb->free();
}

$user_opts = [];
$resu = $conn->query("SELECT id, nama_lengkap FROM user ORDER BY nama_lengkap ASC");
if ($resu) {
  while ($u = $resu->fetch_assoc()) { $user_opts[] = $u; }
  $resu->free();
}

// ================== Build WHERE DINAMIS (FILTER) ==================
$where_sql    = " WHERE 1=1";
$where_types  = "";
$where_values = [];

if ($keyword !== '') {
  $where_sql .= " AND (
      pinjambarang.nama LIKE ?
      OR barang.nama_barang LIKE ?
      OR IFNULL(pinjambarang.tujuan_barang,'') LIKE ?
      OR IFNULL(pinjambarang.ket,'') LIKE ?
  )";
  $kw = "%{$keyword}%";
  $where_types   .= "ssss";
  $where_values[] = $kw;
  $where_values[] = $kw;
  $where_values[] = $kw;
  $where_values[] = $kw;
}

if ($status_f !== '') {
  $st = strtolower($status_f);
  $allowed_status = ['menunggu','approve','cancel','ditolak','selesai'];
  if (in_array($st, $allowed_status, true)) {
    $where_sql     .= " AND TRIM(LOWER(pinjambarang.status)) = ?";
    $where_types   .= "s";
    $where_values[] = $st;
  }
}

$have_dari   = ($dari   !== '');
$have_sampai = ($sampai !== '');

if ($have_dari && $have_sampai) {
  $where_sql .= " AND pinjambarang.tgl_mulai <= ?
                  AND IFNULL(NULLIF(pinjambarang.tgl_selesai,''), pinjambarang.tgl_mulai) >= ?";
  $where_types   .= "ss";
  $where_values[] = $sampai;
  $where_values[] = $dari;
} elseif ($have_dari && !$have_sampai) {
  $where_sql .= " AND IFNULL(NULLIF(pinjambarang.tgl_selesai,''), pinjambarang.tgl_mulai) >= ?";
  $where_types   .= "s";
  $where_values[] = $dari;
} elseif (!$have_dari && $have_sampai) {
  $where_sql .= " AND pinjambarang.tgl_mulai <= ?";
  $where_types   .= "s";
  $where_values[] = $sampai;
}

if ($id_barang_f !== '') {
  $where_sql     .= " AND pinjambarang.id_barang = ?";
  $where_types   .= "s";
  $where_values[] = $id_barang_f;
}

if ($id_user_f !== '') {
  $where_sql     .= " AND pinjambarang.id_user = ?";
  $where_types   .= "s";
  $where_values[] = $id_user_f;
}

// ================== Hitung total (untuk pagination) ==================
$sql_count = "
  SELECT COUNT(*) AS total
  FROM pinjambarang
  INNER JOIN user   ON user.id = pinjambarang.id_user
  INNER JOIN barang ON barang.id = pinjambarang.id_barang
  {$where_sql}
";

$stmt_cnt = $conn->prepare($sql_count);
if ($where_types !== "" && !empty($where_values)) {
  $bind_params = array_merge([$where_types], $where_values);
  $refs = [];
  foreach ($bind_params as $key => $value) {
    $refs[$key] = &$bind_params[$key];
  }
  call_user_func_array([$stmt_cnt, 'bind_param'], $refs);
}
$stmt_cnt->execute();
$result_count = $stmt_cnt->get_result();
$total_rows = 0;
if ($result_count) {
  $row_count  = $result_count->fetch_assoc();
  $total_rows = (int)($row_count['total'] ?? 0);
}
$stmt_cnt->close();

$total_pages = max(1, (int)ceil($total_rows / $per_page));

// ================== Query data (dengan filter + pagination) ==================
$sql = "
  SELECT pinjambarang.*, 
         pinjambarang.nama AS nama_peminjam, 
         user.nama_lengkap, 
         barang.nama_barang
  FROM pinjambarang
  INNER JOIN user   ON user.id = pinjambarang.id_user
  INNER JOIN barang ON barang.id = pinjambarang.id_barang
  {$where_sql}
  ORDER BY 
    CASE 
      WHEN LOWER(TRIM(pinjambarang.status))='menunggu' THEN 0
      WHEN LOWER(TRIM(pinjambarang.status))='approve'  THEN 1
      ELSE 2
    END ASC,
    pinjambarang.tgl_mulai DESC,
    pinjambarang.waktu_mulai DESC
  LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);

$types  = $where_types . "ii";
$values = array_merge($where_values, [$per_page, $start]);

$bind_params = array_merge([$types], $values);
$refs = [];
foreach ($bind_params as $key => $value) {
  $refs[$key] = &$bind_params[$key];
}
call_user_func_array([$stmt, 'bind_param'], $refs);

$stmt->execute();
$result = $stmt->get_result();
?>

<style>
.page-inner .barang-content {
  all: revert !important;
}

.barang-content,
.barang-content * {
  box-sizing: border-box !important;
}

.barang-content .card,
.barang-content .card-header,
.barang-content .card-body,
.barang-content .row {
  margin: revert !important;
  padding: revert !important;
}

/* ========================================
   TEMA COMPACT RESPONSIF
   ======================================== */
.barang-content {
  --success: #22c55e;
  --danger: #ef4444;
  --warning: #f59e0b;
  --info: #3b82f6;
  --success-soft: #e9f8ef;
  --warning-soft: #fff7e6;
  --muted: #6b7280;
  --txt: #1f2937;
  --card: #fff;
  --shadow: 0 4px 12px rgba(0,0,0,.05);
  --border: #e5e7eb;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  font-size: 13px;
}

.barang-content .card {
  background: var(--card) !important;
  border: 1px solid var(--border) !important;
  border-radius: 8px !important;
  box-shadow: var(--shadow) !important;
  margin-bottom: 16px !important;
  overflow: hidden !important;
}

.barang-content .card-body {
  padding: 14px !important;
}

/* Filter Row - RESPONSIF */
.barang-content .row {
  display: grid !important;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)) !important;
  gap: 10px !important;
}

.barang-content .form-label {
  font-size: 0.65rem !important;
  font-weight: 600 !important;
  color: var(--muted) !important;
  display: block !important;
  margin-bottom: 4px !important;
  text-transform: uppercase !important;
}

.barang-content .form-control {
  padding: 5px 8px !important;
  border: 1px solid var(--border) !important;
  border-radius: 6px !important;
  font-size: 0.75rem !important;
  width: 100% !important;
}

/* Buttons */
.barang-content .btn {
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: 5px !important;
  padding: 5px 10px !important;
  border: none !important;
  border-radius: 4px !important;
  font-size: 0.72rem !important;
  font-weight: 600 !important;
  cursor: pointer !important;
  text-decoration: none !important;
  transition: all 0.2s ease !important;
  white-space: nowrap !important;
}

.barang-content .btn:hover {
  transform: translateY(-2px) !important;
  box-shadow: var(--shadow) !important;
}

.barang-content .btn-icon {
  width: 28px !important;
  height: 28px !important;
  padding: 0 !important;
  border-radius: 4px !important;
  font-size: 0.8rem !important;
}

.barang-content .btn-primary {
  background: var(--info) !important;
  color: white !important;
}

.barang-content .btn-success {
  background: var(--success) !important;
  color: white !important;
}

.barang-content .btn-danger {
  background: var(--danger) !important;
  color: white !important;
}

.barang-content .btn-warning {
  background: var(--warning) !important;
  color: white !important;
}

.barang-content .btn-secondary {
  background: var(--muted) !important;
  color: white !important;
}

/* Table */
.barang-content .table-wrapper {
  overflow-x: auto !important;
  border: 1px solid var(--border) !important;
  border-radius: 8px !important;
  background: var(--card) !important;
}

.barang-content .table-modern {
  width: 100% !important;
  border-collapse: collapse !important;
}

.barang-content .table-modern thead {
  background: #f8f9fb !important;
  border-bottom: 2px solid var(--border) !important;
}

.barang-content .table-modern thead th {
  font-weight: 700 !important;
  color: var(--muted) !important;
  font-size: 0.65rem !important;
  text-transform: uppercase !important;
  letter-spacing: 0.04em !important;
  padding: 10px 12px !important;
  text-align: center !important;
  white-space: nowrap !important;
}

.barang-content .table-modern tbody td {
  padding: 12px !important;
  vertical-align: middle !important;
  color: var(--txt) !important;
  font-size: 0.75rem !important;
  text-align: center !important;
  border-bottom: 1px solid var(--border) !important;
}

.barang-content .table-modern tbody tr {
  transition: background 0.15s ease !important;
}

.barang-content .table-modern tbody tr:hover {
  background: #f9fafb !important;
}

.barang-content .table-modern tbody tr:last-child td {
  border-bottom: none !important;
}

.barang-content .row-approve {
  background: var(--success-soft) !important;
  border-left: 3px solid var(--success) !important;
}

.barang-content .row-menunggu {
  background: var(--warning-soft) !important;
  border-left: 3px solid var(--warning) !important;
}

/* Info Cards */
.barang-content .info-card {
  display: flex !important;
  flex-direction: column !important;
  gap: 3px !important;
  align-items: center !important;
}

.barang-content .info-main {
  font-weight: 600 !important;
  font-size: 0.78rem !important;
}

.barang-content .info-sub {
  font-size: 0.68rem !important;
  color: var(--muted) !important;
}

.barang-content .info-tujuan {
  font-size: 0.68rem !important;
  color: #ef4444 !important;
  font-weight: 500 !important;
  font-style: italic !important;
  margin-top: 2px !important;
}

/* Jadwal */
.barang-content .jadwal-box {
  display: flex !important;
  flex-direction: column !important;
  gap: 6px !important;
  align-items: center !important;
}

.barang-content .jadwal-row {
  display: flex !important;
  align-items: center !important;
  gap: 6px !important;
  white-space: nowrap !important;
}

.barang-content .jadwal-item {
  display: flex !important;
  align-items: center !important;
  gap: 6px !important;
  font-size: 0.72rem !important;
  font-weight: 500 !important;
  color: var(--txt) !important;
  white-space: nowrap !important;
}

.barang-content .jadwal-item span {
  white-space: nowrap !important;
}

.barang-content .jadwal-arrow {
  color: var(--info) !important;
  font-weight: bold !important;
  font-size: 0.85rem !important;
  flex-shrink: 0 !important;
}

/* Item Chips */
.barang-content .item-chips {
  display: flex !important;
  flex-wrap: wrap !important;
  gap: 4px !important;
  justify-content: center !important;
}

.barang-content .item-chip {
  display: inline-flex !important;
  align-items: center !important;
  gap: 3px !important;
  padding: 3px 6px !important;
  border-radius: 4px !important;
  background: #f8f9fb !important;
  border: 1px solid var(--border) !important;
  font-size: 0.65rem !important;
  font-weight: 500 !important;
  white-space: nowrap !important;
}

.barang-content .chip-qty {
  font-weight: 700 !important;
  color: var(--info) !important;
}

/* Status Pills */
.barang-content .status-pill {
  display: inline-block !important;
  padding: 4px 10px !important;
  border-radius: 999px !important;
  font-weight: 700 !important;
  font-size: 0.65rem !important;
  text-transform: uppercase !important;
  white-space: nowrap !important;
}

.barang-content .status-approve {
  background: var(--success-soft) !important;
  color: #065f46 !important;
  border: 1px solid #86efac !important;
}

.barang-content .status-menunggu {
  background: var(--warning-soft) !important;
  color: #b45309 !important;
  border: 1px solid #fbbf24 !important;
}

/* Badge Rutin */
.barang-content .badge-rutin {
  display: inline-block !important;
  padding: 4px 8px !important;
  border-radius: 4px !important;
  font-size: 0.68rem !important;
  font-weight: 600 !important;
  white-space: normal !important;
  text-align: center !important;
  line-height: 1.3 !important;
  max-width: 100px !important;
}

.barang-content .badge-rutin.yes {
  background: #e0f2fe !important;
  color: #0369a1 !important;
  border: 1px solid #bae6fd !important;
}

.barang-content .badge-rutin.no {
  background: #f3f4f6 !important;
  color: var(--muted) !important;
  border: 1px solid var(--border) !important;
}

/* Actions - VERTIKAL */
.barang-content .actions {
  display: flex !important;
  flex-direction: column !important;
  gap: 4px !important;
  align-items: center !important;
}

/* Pagination */
.barang-content .pagination-wrapper {
  display: flex !important;
  justify-content: space-between !important;
  align-items: center !important;
  margin-top: 16px !important;
  padding: 12px !important;
  background: var(--card) !important;
  border-radius: 8px !important;
  border: 1px solid var(--border) !important;
  flex-wrap: wrap !important;
  gap: 10px !important;
}

.barang-content .pagination-info {
  color: var(--muted) !important;
  font-size: 0.72rem !important;
}

.barang-content .pagination-pages {
  display: flex !important;
  gap: 4px !important;
  flex-wrap: wrap !important;
}

.barang-content .pagination-pages a,
.barang-content .pagination-pages span {
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  min-width: 28px !important;
  height: 28px !important;
  padding: 0 8px !important;
  border: 1px solid var(--border) !important;
  border-radius: 4px !important;
  text-decoration: none !important;
  color: var(--muted) !important;
  font-weight: 600 !important;
  font-size: 0.7rem !important;
  transition: all 0.2s ease !important;
}

.barang-content .pagination-pages a:hover {
  background: #f8f9fb !important;
  border-color: var(--info) !important;
  color: var(--info) !important;
}

.barang-content .pagination-pages .active {
  background: var(--info) !important;
  color: white !important;
  border-color: var(--info) !important;
}

.barang-content .footer-credit {
  text-align: center !important;
  margin-top: 20px !important;
  padding: 12px !important;
  color: var(--muted) !important;
  font-size: 0.72rem !important;
}

/* Modal Export Styles */
.barang-content .modal-content {
  border-radius: 8px !important;
  border: none !important;
  box-shadow: 0 10px 40px rgba(0,0,0,0.15) !important;
}

.barang-content .modal-header {
  background: linear-gradient(135deg, var(--info), #2563eb) !important;
  color: white !important;
  border-bottom: none !important;
  padding: 16px 20px !important;
  border-radius: 8px 8px 0 0 !important;
}

.barang-content .modal-header .modal-title {
  font-weight: 700 !important;
  font-size: 1rem !important;
}

.barang-content .modal-header .close {
  color: white !important;
  opacity: 0.9 !important;
  text-shadow: none !important;
}

.barang-content .modal-body {
  padding: 20px !important;
}

.barang-content .modal-footer {
  border-top: 1px solid var(--border) !important;
  padding: 12px 20px !important;
}

.barang-content .export-section {
  margin-bottom: 20px !important;
}

.barang-content .export-section-title {
  font-size: 0.75rem !important;
  font-weight: 700 !important;
  color: var(--muted) !important;
  text-transform: uppercase !important;
  margin-bottom: 10px !important;
  display: flex !important;
  align-items: center !important;
  gap: 6px !important;
}

.barang-content .sort-option {
  display: flex !important;
  gap: 12px !important;
  padding: 12px !important;
  background: #f9fafb !important;
  border-radius: 8px !important;
  border: 2px solid var(--border) !important;
  transition: all 0.2s ease !important;
}

.barang-content .sort-option:has(input:checked) {
  background: #eff6ff !important;
  border-color: var(--info) !important;
}

.barang-content .sort-radio {
  display: flex !important;
  align-items: center !important;
  gap: 8px !important;
  cursor: pointer !important;
  flex: 1 !important;
}

.barang-content .sort-radio input[type="radio"] {
  width: 18px !important;
  height: 18px !important;
  cursor: pointer !important;
  margin: 0 !important;
  accent-color: var(--info) !important;
}

.barang-content .sort-radio label {
  cursor: pointer !important;
  margin: 0 !important;
  font-size: 0.8rem !important;
  font-weight: 600 !important;
  color: var(--txt) !important;
  display: flex !important;
  flex-direction: column !important;
  gap: 2px !important;
}

.barang-content .sort-radio-title {
  font-size: 0.85rem !important;
  color: var(--txt) !important;
  font-weight: 600 !important;
}

.barang-content .sort-radio-desc {
  font-size: 0.68rem !important;
  color: var(--muted) !important;
  font-weight: 400 !important;
}

.barang-content .sort-radio input:checked ~ label .sort-radio-title {
  color: var(--info) !important;
}

.barang-content .sort-icon {
  font-size: 1.2rem !important;
  color: var(--muted) !important;
  transition: color 0.2s ease !important;
}

.barang-content .sort-radio:has(input:checked) .sort-icon {
  color: var(--info) !important;
}

.barang-content .export-buttons {
  display: flex !important;
  gap: 8px !important;
  justify-content: flex-end !important;
}

/* Responsive */
@media (max-width: 768px) {
  .barang-content .row {
    grid-template-columns: 1fr !important;
  }

  .barang-content .hide-sm {
    display: none !important;
  }

  .barang-content .table-wrapper {
    overflow-x: visible !important;
  }

  .barang-content .table-modern thead {
    display: none !important;
  }

  .barang-content .table-modern tbody tr {
    display: block !important;
    margin-bottom: 14px !important;
    border: 1px solid var(--border) !important;
    border-radius: 8px !important;
    overflow: hidden !important;
  }

  .barang-content .table-modern tbody td {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    padding: 10px 12px !important;
    border-bottom: 1px solid var(--border) !important;
    text-align: left !important;
  }

  .barang-content .table-modern tbody td:last-child {
    border-bottom: none !important;
    justify-content: center !important;
  }

  .barang-content .table-modern tbody td::before {
    content: attr(data-label) !important;
    font-weight: 600 !important;
    color: var(--muted) !important;
    font-size: 0.65rem !important;
    text-transform: uppercase !important;
    margin-right: 10px !important;
  }

  .barang-content .table-modern tbody td:last-child::before {
    display: none !important;
  }

  .barang-content .actions {
    width: 100% !important;
  }

  .barang-content .sort-option {
    flex-direction: column !important;
  }

  .barang-content .export-buttons {
    flex-direction: column !important;
  }
}
</style>

<div class="barang-content">
  <!-- FILTER PANEL -->
  <div class="card">
    <div class="card-body">
      <form class="row g-2" method="get" action="">
        <?php if (isset($_GET['view'])): ?>
          <input type="hidden" name="view" value="<?php echo htmlspecialchars($_GET['view']); ?>">
        <?php endif; ?>

        <div>
          <label class="form-label">Pencarian</label>
          <input 
            type="text" 
            class="form-control" 
            name="q" 
            placeholder="Nama peminjam, gedung, tujuan..." 
            value="<?php echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div>
          <label class="form-label">Status</label>
          <select class="form-control" name="status">
            <option value="">Semua</option>
            <option value="menunggu" <?php echo $status_f==='menunggu'?'selected':''; ?>>Menunggu</option>
            <option value="approve"  <?php echo $status_f==='approve'?'selected':''; ?>>Approved</option>
            <option value="selesai"  <?php echo $status_f==='selesai'?'selected':''; ?>>Selesai</option>
            <option value="cancel"   <?php echo $status_f==='cancel'?'selected':''; ?>>Cancel</option>
            <option value="ditolak"  <?php echo $status_f==='ditolak'?'selected':''; ?>>Ditolak</option>
          </select>
        </div>

        <div>
          <label class="form-label">Dari Tanggal</label>
          <input 
            type="date" 
            class="form-control" 
            name="dari" 
            value="<?php echo htmlspecialchars($dari, ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        
        <div>
          <label class="form-label">Sampai Tanggal</label>
          <input 
            type="date" 
            class="form-control" 
            name="sampai" 
            value="<?php echo htmlspecialchars($sampai, ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div>
          <label class="form-label">Gedung</label>
          <select class="form-control" name="id_barang">
            <option value="">Semua</option>
            <?php foreach ($barang_opts as $bo): ?>
              <option 
                value="<?php echo htmlspecialchars($bo['id']); ?>" 
                <?php echo $id_barang_f==$bo['id']?'selected':''; ?>>
                <?php echo htmlspecialchars($bo['nama_barang']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="form-label">Divisi / User</label>
          <select class="form-control" name="id_user">
            <option value="">Semua</option>
            <?php foreach ($user_opts as $uo): ?>
              <option 
                value="<?php echo htmlspecialchars($uo['id']); ?>" 
                <?php echo $id_user_f==$uo['id']?'selected':''; ?>>
                <?php echo htmlspecialchars($uo['nama_lengkap']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="display:flex;gap:6px;align-items:flex-end;">
          <button type="submit" class="btn btn-primary">
            <i class="fa fa-filter"></i> Filter
          </button>
          <a 
            href="?<?php echo isset($_GET['view']) ? 'view='.urlencode($_GET['view']) : ''; ?>" 
            class="btn btn-secondary">
            <i class="fa fa-undo"></i> Reset
          </a>
        </div>

        <!-- TOMBOL EXPORT DENGAN MODAL -->
        <div style="display:flex;gap:6px;align-items:flex-end;">
          <button 
            type="button" 
            class="btn btn-danger"
            data-toggle="modal" 
            data-target="#modalExport">
            <i class="fa fa-download"></i> Export Data
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- DATA TABLE -->
  <div class="table-wrapper">
    <table class="table-modern">
      <thead>
        <tr>
          <th style="width: 40px;">No</th>
          <th style="width: 140px;">Peminjam</th>
          <th style="width: 150px;">Gedung</th>
          <th class="hide-sm" style="width: 180px;">Perlengkapan</th>
          <th style="width: 150px;">Jadwal</th>
          <th style="width: 90px;">Rutin</th>
          <th style="width: 120px;">Keterangan</th>
          <th style="width: 90px;">Status</th>
          <th style="width: 70px;">Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php 
        $no = $start + 1; 
        if ($result->num_rows === 0): 
      ?>
        <tr>
          <td colspan="9" style="text-align: center; padding: 30px; color: var(--muted);">
            <i class="fa fa-inbox" style="font-size: 36px; margin-bottom: 10px; display: block; opacity: 0.3;"></i>
            <strong>Tidak ada data</strong><br>
            <span style="font-size:0.75rem;">Coba ubah filter di atas.</span>
          </td>
        </tr>
      <?php 
        else:
        while ($pin = $result->fetch_assoc()): 
          $status_lc = strtolower(trim($pin['status']));
          $rowClass  = ($status_lc==='approve') ? 'row-approve' : 'row-menunggu';
          
          $tglMulai   = fmt_tgl($pin['tgl_mulai']);
          $tglSelesai = fmt_tgl($pin['tgl_selesai']);
          $wm = $pin['waktu_mulai']   ? substr($pin['waktu_mulai'],0,5)   : '-';
          $ws = $pin['waktu_selesai'] ? substr($pin['waktu_selesai'],0,5) : '-';
          
          $is_recurring = strtolower(trim($pin['is_recurring'] ?? 'no')) === 'yes';
          $rec_nums  = parseRecurringDays($pin['recurring_days'] ?? '');
          $rec_names = mapHariByNumber($rec_nums);
          
          $layoutName = '';
          if(isset($pin['layout_file']) && $pin['layout_file']!==''){ 
            $layoutName = $pin['layout_file']; 
          } elseif(isset($pin['layout']) && $pin['layout']!==''){ 
            $layoutName = $pin['layout']; 
          }
      ?>
        <tr class="<?php echo $rowClass; ?>">
          <td data-label="No"><strong><?php echo $no++; ?></strong></td>
          
          <td data-label="Peminjam">
            <div class="info-card">
              <div class="info-main"><?php echo htmlspecialchars($pin['nama_peminjam']); ?></div>
              <?php if(isset($pin['jumlah_peserta']) && (int)$pin['jumlah_peserta'] > 0): ?>
                <div class="info-sub">
                  <i class="fa fa-users"></i>
                  <?php echo (int)$pin['jumlah_peserta']; ?> Peserta
                </div>
              <?php endif; ?>
            </div>
          </td>
          
          <td data-label="Gedung">
            <div class="info-card">
              <div class="info-main"><?php echo htmlspecialchars($pin['nama_barang']); ?></div>
              <?php if(!empty($pin['tujuan_barang'])): ?>
                <div class="info-tujuan">
                  <i class="fa fa-bullseye"></i>
                  <?php echo htmlspecialchars($pin['tujuan_barang']); ?>
                </div>
              <?php endif; ?>
            </div>
          </td>
          
          <td class="hide-sm" data-label="Perlengkapan">
            <div class="item-chips">
              <?php if(yes($pin['meja'])): ?>
                <span class="item-chip">
                  <i class="fa fa-table"></i> Meja 
                  <?php if((int)$pin['jumlah_meja']>0): ?>
                    <span class="chip-qty">(<?php echo (int)$pin['jumlah_meja']; ?>)</span>
                  <?php endif; ?>
                </span>
              <?php endif; ?>
              <?php if(yes($pin['kursi'])): ?>
                <span class="item-chip">
                  <i class="fa fa-th"></i> Kursi
                  <?php if((int)$pin['jumlah_kursi']>0): ?>
                    <span class="chip-qty">(<?php echo (int)$pin['jumlah_kursi']; ?>)</span>
                  <?php endif; ?>
                </span>
              <?php endif; ?>
              <?php if(yes($pin['sound'])): ?>
                <span class="item-chip"><i class="fa fa-volume-up"></i> Sound</span>
              <?php endif; ?>
              <?php if(yes($pin['proyektor'])): ?>
                <span class="item-chip"><i class="fa fa-video-camera"></i> Proyektor</span>
              <?php endif; ?>
              <?php if($layoutName!==''): ?>
                <span class="item-chip"><i class="fa fa-map"></i> <?php echo htmlspecialchars($layoutName); ?></span>
              <?php endif; ?>
            </div>
          </td>
          
          <td data-label="Jadwal">
            <div class="jadwal-box">
              <div class="jadwal-row">
                <div class="jadwal-item">
                  <span><?php echo $tglMulai; ?></span>
                  <span class="jadwal-arrow">→</span>
                  <span><?php echo $tglSelesai; ?></span>
                </div>
              </div>
              <div class="jadwal-row">
                <div class="jadwal-item">
                  <span><?php echo $wm; ?></span>
                  <span class="jadwal-arrow">→</span>
                  <span><?php echo $ws; ?></span>
                </div>
              </div>
            </div>
          </td>

          <td data-label="Rutin">
            <?php if ($is_recurring && !empty($rec_names)): ?>
              <span class="badge-rutin yes">
                <?php echo implode(', ', $rec_names); ?>
              </span>
            <?php else: ?>
              <span class="badge-rutin no">Tidak</span>
            <?php endif; ?>
          </td>
          
          <td data-label="Keterangan">
            <div style="font-size: 0.72rem; color: var(--muted); line-height: 1.4;">
              <?php echo htmlspecialchars($pin['ket'] ?: '-'); ?>
            </div>
          </td>
          
          <td data-label="Status">
            <?php if($status_lc==='approve'): ?>
              <span class="status-pill status-approve">Approved</span>
            <?php else: ?>
              <span class="status-pill status-menunggu">Menunggu</span>
            <?php endif; ?>
          </td>
          
          <td data-label="Aksi">
            <div class="actions">
              <a href="?view=detailpinjambarang&id=<?php echo urlencode($pin['id_pinjam']); ?>" 
                 class="btn btn-success btn-icon" 
                 title="Detail">
                <i class="fa fa-eye"></i>
              </a>
              
              <?php if($status_lc==='menunggu'): ?>
                <a href="?<?php echo build_query_keep(['id_approve'=>$pin['id_pinjam']]); ?>" 
                   class="btn btn-primary btn-icon" 
                   title="Approve">
                  <i class="fa fa-check"></i>
                </a>
              <?php else: ?>
                <a href="?<?php echo build_query_keep(['id_batal'=>$pin['id_pinjam']]); ?>" 
                   class="btn btn-warning btn-icon" 
                   title="Batal">
                  <i class="fa fa-undo"></i>
                </a>
              <?php endif; ?>
              
              <a href="#modalHapus<?php echo $pin['id_pinjam']; ?>" 
                 data-toggle="modal"
                 class="btn btn-danger btn-icon" 
                 title="Hapus">
                <i class="fa fa-trash"></i>
              </a>
            </div>
          </td>
        </tr>

        <!-- Modal Hapus -->
        <div class="modal fade" id="modalHapus<?php echo $pin['id_pinjam']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">
                  <i class="fa fa-exclamation-circle"></i> Hapus Data
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <form method="POST" action="">
                <div class="modal-body">
                  <input type="hidden" name="id_pinjam" value="<?php echo htmlspecialchars($pin['id_pinjam']); ?>">
                  <h4>Yakin ingin menghapus data ini?</h4>
                  <p style="color:var(--muted);font-size:0.8rem;margin-top:8px;">
                    Peminjaman oleh <strong><?php echo htmlspecialchars($pin['nama_peminjam']); ?></strong> akan dihapus permanen.
                  </p>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    Batal
                  </button>
                  <button type="submit" name="hapus" class="btn btn-danger">
                    <i class="fa fa-trash"></i> Ya, Hapus
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

      <?php 
        endwhile;
        endif; 
      ?>
      </tbody>
    </table>
  </div>

  <!-- PAGINATION -->
  <?php
  $show_from = $total_rows ? ($start + 1) : 0;
  $show_to   = min($start + $per_page, $total_rows);
  ?>
  <div class="pagination-wrapper">
    <div class="pagination-info">
      <strong><?php echo $show_from; ?>-<?php echo $show_to; ?></strong> dari <strong><?php echo $total_rows; ?></strong>
    </div>
    <div class="pagination-pages">
      <?php
        $make = function($p,$label=null,$active=false){
          $label = $label ?? $p;
          if ($active) {
            return '<span class="active">'.$label.'</span>';
          }
          return '<a href="?'.build_query_keep(['page'=>$p]).'">'.$label.'</a>';
        };

        if ($page > 1) {
          echo $make(1,'«', false);
          echo $make($page-1,'‹', false);
        }

        $win = 2;
        $start_p = max(1, $page-$win);
        $end_p   = min($total_pages, $page+$win);
        
        if ($start_p > 1) {
          echo $make(1, '1', false);
          if ($start_p > 2) echo '<span>...</span>';
        }
        
        for($p=$start_p; $p<=$end_p; $p++){
          echo $make($p, (string)$p, $p===$page);
        }
        
        if ($end_p < $total_pages) {
          if ($end_p < $total_pages - 1) echo '<span>...</span>';
          echo $make($total_pages, (string)$total_pages, false);
        }

        if ($page < $total_pages) {
          echo $make($page+1,'›', false);
          echo $make($total_pages,'»', false);
        }
      ?>
    </div>
  </div>

  <!-- MODAL EXPORT -->
  <div class="modal fade" id="modalExport" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fa fa-download"></i> Pilih Format Export
          </h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form id="formExport" method="get" action="" target="_blank">
          <div class="modal-body">
            
            <!-- PILIHAN URUTAN DATA -->
            <div class="export-section">
              <div class="export-section-title">
                <i class="fa fa-sort"></i> Urutkan Data Berdasarkan
              </div>
              
              <div class="sort-option">
                <div class="sort-radio">
                  <input 
                    type="radio" 
                    name="sort_by" 
                    id="sortGedung" 
                    value="gedung" 
                    checked>
                  <i class="fa fa-building sort-icon"></i>
                  <label for="sortGedung">
                    <span class="sort-radio-title">Berdasarkan Gedung</span>
                    <span class="sort-radio-desc">Data dikelompokkan per gedung, lalu diurutkan tanggal</span>
                  </label>
                </div>
                
                <div class="sort-radio">
                  <input 
                    type="radio" 
                    name="sort_by" 
                    id="sortTanggal" 
                    value="tanggal">
                  <i class="fa fa-calendar sort-icon"></i>
                  <label for="sortTanggal">
                    <span class="sort-radio-title">Berdasarkan Tanggal</span>
                    <span class="sort-radio-desc">Data diurutkan kronologis berdasarkan tanggal peminjaman</span>
                  </label>
                </div>
              </div>
            </div>

            <!-- INFO FILTER YANG AKTIF -->
            <div class="export-section">
              <div style="padding: 12px; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 6px;">
                <p style="font-size: 0.75rem; color: #0369a1; margin: 0 0 8px 0; font-weight: 600;">
                  <i class="fa fa-info-circle"></i> Data yang akan di-export:
                </p>
                <ul style="font-size: 0.72rem; color: #0c4a6e; margin: 0; padding-left: 20px;">
                  <?php if ($keyword !== ''): ?>
                    <li>Pencarian: <strong><?php echo htmlspecialchars($keyword); ?></strong></li>
                  <?php endif; ?>
                  
                  <?php if ($status_f !== ''): ?>
                    <li>Status: <strong><?php echo ucfirst(htmlspecialchars($status_f)); ?></strong></li>
                  <?php endif; ?>
                  
                  <?php if ($dari !== '' || $sampai !== ''): ?>
                    <li>Tanggal: 
                      <strong>
                        <?php 
                          if ($dari !== '' && $sampai !== '') {
                            echo fmt_tgl($dari) . ' - ' . fmt_tgl($sampai);
                          } elseif ($dari !== '') {
                            echo 'Dari ' . fmt_tgl($dari);
                          } else {
                            echo 'Sampai ' . fmt_tgl($sampai);
                          }
                        ?>
                      </strong>
                    </li>
                  <?php endif; ?>
                  
                  <?php if ($id_barang_f !== ''): ?>
                    <li>Gedung: <strong>
                      <?php 
                        foreach($barang_opts as $bo) {
                          if($bo['id'] == $id_barang_f) {
                            echo htmlspecialchars($bo['nama_barang']);
                            break;
                          }
                        }
                      ?>
                    </strong></li>
                  <?php endif; ?>
                  
                  <?php if ($id_user_f !== ''): ?>
                    <li>User/Divisi: <strong>
                      <?php 
                        foreach($user_opts as $uo) {
                          if($uo['id'] == $id_user_f) {
                            echo htmlspecialchars($uo['nama_lengkap']);
                            break;
                          }
                        }
                      ?>
                    </strong></li>
                  <?php endif; ?>
                  
                  <?php if ($keyword === '' && $status_f === '' && $dari === '' && $sampai === '' && $id_barang_f === '' && $id_user_f === ''): ?>
                    <li><strong>Semua data peminjaman</strong></li>
                  <?php endif; ?>
                </ul>
              </div>
            </div>

          </div>
          
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">
              <i class="fa fa-times"></i> Batal
            </button>
            
            <div class="export-buttons">
              <button 
                type="button" 
                class="btn btn-danger" 
                onclick="exportData('pdf')">
                <i class="fa fa-file-pdf-o"></i> Export PDF
              </button>
              <button 
                type="button" 
                class="btn btn-success" 
                onclick="exportData('excel')">
                <i class="fa fa-file-excel-o"></i> Export Excel
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="footer-credit">
    <strong>SIPINJAM</strong> &copy; 2025
  </div>
</div>

<script>
// Function Export Data
function exportData(type) {
  // Ambil pilihan sorting
  const sortBy = document.querySelector('input[name="sort_by"]:checked').value;
  
  // Build URL export
  let exportUrl = '';
  if (type === 'pdf') {
    exportUrl = 'peminjaman/export_pinjambarang_pdf.php?';
  } else {
    exportUrl = 'peminjaman/export_pinjambarang_xls.php?';
  }
  
  // Tambahkan parameter sort
  exportUrl += 'sort_by=' + encodeURIComponent(sortBy) + '&';
  
  // Tambahkan semua filter yang sedang aktif
  <?php if ($keyword !== ''): ?>
    exportUrl += 'q=<?php echo urlencode($keyword); ?>&';
  <?php endif; ?>
  
  <?php if ($status_f !== ''): ?>
    exportUrl += 'status=<?php echo urlencode($status_f); ?>&';
  <?php endif; ?>
  
  <?php if ($dari !== ''): ?>
    exportUrl += 'dari=<?php echo urlencode($dari); ?>&';
  <?php endif; ?>
  
  <?php if ($sampai !== ''): ?>
    exportUrl += 'sampai=<?php echo urlencode($sampai); ?>&';
  <?php endif; ?>
  
  <?php if ($id_barang_f !== ''): ?>
    exportUrl += 'id_barang=<?php echo urlencode($id_barang_f); ?>&';
  <?php endif; ?>
  
  <?php if ($id_user_f !== ''): ?>
    exportUrl += 'id_user=<?php echo urlencode($id_user_f); ?>&';
  <?php endif; ?>
  
  <?php if (isset($_GET['view'])): ?>
    exportUrl += 'view=<?php echo urlencode($_GET['view']); ?>&';
  <?php endif; ?>
  
  // Hapus & terakhir
  exportUrl = exportUrl.replace(/&$/, '');
  
  // Buka di tab baru
  window.open(exportUrl, '_blank');
  
  // Tutup modal setelah 500ms
  setTimeout(() => {
    $('#modalExport').modal('hide');
  }, 500);
}
</script>
