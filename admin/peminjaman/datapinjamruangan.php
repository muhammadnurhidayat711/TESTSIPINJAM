<?php
// ========= Pastikan koneksi $conn (mysqli) sudah dibuat sebelum file ini =========
include '../koneksi.php';

// ================== Ambil parameter filter (GET) ==================
$keyword     = isset($_GET['q']) ? trim($_GET['q']) : '';
$status_f    = isset($_GET['status']) ? trim($_GET['status']) : '';
$dari        = isset($_GET['dari']) ? trim($_GET['dari']) : '';
$sampai      = isset($_GET['sampai']) ? trim($_GET['sampai']) : '';
$page        = max(1, (int)($_GET['page'] ?? 1));
$per_page    = 10;
$start       = ($page - 1) * $per_page;

// ================== Helper Functions ==================
function refValues($arr){
  $refs = [];
  foreach($arr as $k => $v){ $refs[$k] = &$arr[$k]; }
  return $refs;
}

function fmt_tgl($ymd){
  if(!$ymd || $ymd==='0000-00-00') return '-';
  [$y,$m,$d]=explode('-',$ymd);
  $bulan = ["","Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
  return sprintf("%02d %s %s",(int)$d,$bulan[(int)$m],$y);
}

function fmt_waktu($hms){
  return $hms ? substr($hms, 0, 5) : '-';
}

function build_query_keep(array $extra = []){
  $q = $_GET;
  foreach($extra as $k=>$v){ $q[$k] = $v; }
  return http_build_query($q);
}

// ================== Build WHERE (reusable for COUNT & DATA) ==================
$where_sql = " WHERE 1=1";
$where_params = [];
$where_types  = "";

if ($keyword !== '') {
  $where_sql .= " AND (user.nama_lengkap LIKE ? OR kendaraan.nama_kendaraan LIKE ?)";
  $kw = "%{$keyword}%";
  $where_params[] = &$kw; $where_params[] = &$kw; $where_types .= "ss";
}

if ($status_f !== '') {
  $where_sql .= " AND TRIM(LOWER(pinjamkendaraan.status)) = ?";
  $st = strtolower(trim($status_f));
  $where_params[] = &$st; $where_types .= "s";
}

$have_dari   = ($dari   !== '');
$have_sampai = ($sampai !== '');

if ($have_dari && $have_sampai) {
  $where_sql .= " AND pinjamkendaraan.tgl_mulai <= ? 
                  AND IFNULL(NULLIF(pinjamkendaraan.tgl_selesai,''), pinjamkendaraan.tgl_mulai) >= ?";
  $where_params[] = &$sampai; $where_types .= "s";
  $where_params[] = &$dari;   $where_types .= "s";
} elseif ($have_dari && !$have_sampai) {
  $where_sql .= " AND IFNULL(NULLIF(pinjamkendaraan.tgl_selesai,''), pinjamkendaraan.tgl_mulai) >= ?";
  $where_params[] = &$dari; $where_types .= "s";
} elseif (!$have_dari && $have_sampai) {
  $where_sql .= " AND pinjamkendaraan.tgl_mulai <= ?";
  $where_params[] = &$sampai; $where_types .= "s";
}

// ================== Hitung total (COUNT) ==================
$sql_count = "
  SELECT COUNT(*) AS total
  FROM pinjamkendaraan
  INNER JOIN user ON user.id = pinjamkendaraan.id_user
  INNER JOIN kendaraan ON kendaraan.id_kendaraan = pinjamkendaraan.id_kendaraan
  {$where_sql}
";
$stmt_cnt = $conn->prepare($sql_count);
if ($where_types !== "") {
  $bind_count = [];
  $bind_count[] = $where_types;
  foreach ($where_params as $p) { $bind_count[] = $p; }
  call_user_func_array([$stmt_cnt, 'bind_param'], refValues($bind_count));
}
$stmt_cnt->execute();
$total_rows = (int)($stmt_cnt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt_cnt->close();
$total_pages = max(1, (int)ceil($total_rows / $per_page));

// ================== Query data + ORDER + LIMIT ==================
$sql = "
  SELECT pinjamkendaraan.*, user.nama_lengkap, kendaraan.nama_kendaraan, kendaraan.deskripsi
  FROM pinjamkendaraan
  INNER JOIN user ON user.id = pinjamkendaraan.id_user
  INNER JOIN kendaraan ON kendaraan.id_kendaraan = pinjamkendaraan.id_kendaraan
  {$where_sql}
  ORDER BY 
    pinjamkendaraan.tgl_mulai DESC,
    pinjamkendaraan.waktu_mulai DESC
  LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($sql);

$types  = $where_types . "ii";
$params = $where_params;
$limit_i  = (int)$per_page;
$offset_i = (int)$start;
$params[] = &$limit_i;
$params[] = &$offset_i;

$bind_names   = [];
$bind_names[] = $types;
for ($i=0; $i<count($params); $i++) { $bind_names[] = $params[$i]; }
call_user_func_array([$stmt, 'bind_param'], refValues($bind_names));

$stmt->execute();
$result = $stmt->get_result();
$data_list = [];
while($row = $result->fetch_assoc()) {
  $data_list[] = $row;
}

// ================== PROSES AKSI (Hapus, Edit, Batal Approve) ==================
if (isset($_POST['hapus']) && isset($_POST['id_pk'])) {
  $id_pk = mysqli_real_escape_string($conn, $_POST['id_pk']);
  $stmtD = $conn->prepare("DELETE FROM pinjamkendaraan WHERE id_pk=?");
  $stmtD->bind_param("s", $id_pk);
  $stmtD->execute();
  $stmtD->close();
  echo "<script>alert('Data Berhasil Dihapus');</script>";
  echo '<meta http-equiv="Refresh" content="0; url=?'.build_query_keep().'" />';
  exit;
}

if (isset($_POST['edit']) && isset($_POST['id_pk'])) {
  $id_pk        = mysqli_real_escape_string($conn, $_POST['id_pk']);
  $id_kendaraan = mysqli_real_escape_string($conn, $_POST['id_kendaraan']);
  $pengemudi    = mysqli_real_escape_string($conn, $_POST['pengemudi']);

  // status hanya di tabel pinjamkendaraan
  $status_baru = (!empty($pengemudi)) ? 'approve' : 'menunggu';

  $stmtU = $conn->prepare("UPDATE pinjamkendaraan SET id_kendaraan=?, pengemudi=?, status=? WHERE id_pk=?");
  $stmtU->bind_param("ssss", $id_kendaraan, $pengemudi, $status_baru, $id_pk);
  $stmtU->execute();
  $stmtU->close();

  // NOTE:
  // Kolom status di tabel kendaraan sudah dihapus,
  // jadi update kendaraan SET status=? dihilangkan supaya tidak error.
  /*
  $stmtV = $conn->prepare("UPDATE kendaraan SET status=? WHERE id_kendaraan=?");
  $status_upd = 'dipinjam';
  $stmtV->bind_param("ss", $status_upd, $id_kendaraan);
  $stmtV->execute();
  $stmtV->close();
  */

  $status_msg = (!empty($pengemudi))
    ? 'Data Berhasil Diperbarui dan Status berubah menjadi Approve'
    : 'Data Berhasil Diperbarui';

  echo "<script>alert('".$status_msg."');</script>";
  echo '<meta http-equiv="Refresh" content="0; url=?'.build_query_keep().'" />';
  exit;
}

if (isset($_POST['batal_approve']) && isset($_POST['id_pk'])) {
  $id_pk        = mysqli_real_escape_string($conn, $_POST['id_pk']);
  $id_kendaraan = mysqli_real_escape_string($conn, $_POST['id_kendaraan']);

  // Kembalikan status di tabel pinjamkendaraan saja
  $stmtBatal = $conn->prepare("UPDATE pinjamkendaraan SET status=? WHERE id_pk=?");
  $status_menunggu = 'menunggu';
  $stmtBatal->bind_param("ss", $status_menunggu, $id_pk);
  $stmtBatal->execute();
  $stmtBatal->close();

  // Kolom status di kendaraan sudah dihapus → jangan diupdate lagi
  /*
  $stmtKendaraan = $conn->prepare("UPDATE kendaraan SET status=? WHERE id_kendaraan=?");
  $status_tersedia = 'tersedia';
  $stmtKendaraan->bind_param("ss", $status_tersedia, $id_kendaraan);
  $stmtKendaraan->execute();
  $stmtKendaraan->close();
  */

  echo "<script>alert('Approval berhasil dibatalkan, Status kembali ke Menunggu');</script>";
  echo '<meta http-equiv="Refresh" content="0; url=?'.build_query_keep().'" />';
  exit;
}
?>

<!-- RESET CSS untuk Konten yang Di-include -->
<?php
// ... (Kode PHP tetap sama, tidak ada perubahan)
?>

<!-- RESET CSS untuk Konten yang Di-include -->
<style>
.page-inner .kendaraan-content {
  all: revert !important;
}

.kendaraan-content,
.kendaraan-content * {
  box-sizing: border-box !important;
}

.kendaraan-content .page-header,
.kendaraan-content .card,
.kendaraan-content .card-header,
.kendaraan-content .card-body,
.kendaraan-content .row {
  margin: revert !important;
  padding: revert !important;
}

.kendaraan-content {
  --success: #22c55e;
  --danger: #ef4444;
  --warning: #f59e0b;
  --info: #3b82f6;
  --success-soft: #e9f8ef;
  --warning-soft: #fff7e6;
  --muted: #6b7280;
  --txt: #1f2937;
  --card: #fff;
  --shadow: 0 6px 16px rgba(0,0,0,.06);
  --border: #e5e7eb;
}

.kendaraan-content .page-header {
  display: flex !important;
  justify-content: space-between !important;
  align-items: center !important;
  margin-bottom: 24px !important;
  padding-bottom: 16px !important;
  border-bottom: 2px solid var(--border) !important;
}

.kendaraan-content .page-header .page-title {
  font-size: 1.75rem !important;
  font-weight: 700 !important;
  color: var(--txt) !important;
  margin: 0 !important;
}

.kendaraan-content .breadcrumbs {
  list-style: none !important;
  padding: 0 !important;
  margin: 0 !important;
  display: flex !important;
  gap: 8px !important;
  align-items: center !important;
  font-size: 0.9rem !important;
  color: var(--muted) !important;
}

.kendaraan-content .breadcrumbs li { 
  display: flex !important; 
  align-items: center !important; 
  gap: 8px !important; 
}

.kendaraan-content .breadcrumbs a { 
  color: var(--info) !important; 
  text-decoration: none !important; 
}

.kendaraan-content .breadcrumbs a:hover { 
  text-decoration: underline !important; 
}

.kendaraan-content .card {
  background: var(--card) !important;
  border: 1px solid var(--border) !important;
  border-radius: 12px !important;
  box-shadow: var(--shadow) !important;
  margin-bottom: 20px !important;
  overflow: hidden !important;
}

.kendaraan-content .card-body {
  padding: 20px !important;
}

.kendaraan-content .card-header {
  padding: 20px 24px !important;
  background: #f9fafb !important;
  border-bottom: 1px solid var(--border) !important;
}

.kendaraan-content .card-header h4 {
  font-size: 1.1rem !important;
  font-weight: 700 !important;
  color: var(--txt) !important;
  margin: 0 !important;
}

.kendaraan-content .form-label {
  font-weight: 600 !important;
  color: var(--txt) !important;
  font-size: 0.9rem !important;
  margin-bottom: 6px !important;
  display: block !important;
}

.kendaraan-content .form-control {
  padding: 8px 12px !important;
  border: 1px solid var(--border) !important;
  border-radius: 8px !important;
  font-size: 0.9rem !important;
  width: 100% !important;
}

.kendaraan-content .form-group {
  margin-bottom: 16px !important;
}

.kendaraan-content .form-group label {
  display: block !important;
  margin-bottom: 6px !important;
  font-weight: 600 !important;
  color: var(--txt) !important;
}

.kendaraan-content .btn {
  display: inline-flex !important;
  align-items: center !important;
  gap: 8px !important;
  padding: 8px 16px !important;
  border: none !important;
  border-radius: 8px !important;
  font-size: 0.9rem !important;
  font-weight: 600 !important;
  cursor: pointer !important;
  text-decoration: none !important;
  transition: all 0.2s ease !important;
  white-space: nowrap !important;
}

.kendaraan-content .btn:hover { 
  transform: translateY(-2px) !important; 
  box-shadow: var(--shadow) !important; 
}

.kendaraan-content .btn-primary {
  background: var(--info) !important;
  color: white !important;
}

.kendaraan-content .btn-primary:hover { 
  background: #2563eb !important; 
}

.kendaraan-content .btn-success {
  background: var(--success) !important;
  color: white !important;
}

.kendaraan-content .btn-success:hover { 
  background: #16a34a !important; 
}

.kendaraan-content .btn-info {
  background: #06b6d4 !important;
  color: white !important;
}

.kendaraan-content .btn-info:hover { 
  background: #0891b2 !important; 
}

.kendaraan-content .btn-warning {
  background: var(--warning) !important;
  color: white !important;
}

.kendaraan-content .btn-warning:hover { 
  background: #d97706 !important; 
}

.kendaraan-content .btn-danger {
  background: var(--danger) !important;
  color: white !important;
}

.kendaraan-content .btn-danger:hover { 
  background: #dc2626 !important; 
}

.kendaraan-content .btn-secondary {
  background: var(--muted) !important;
  color: white !important;
}

.kendaraan-content .btn-secondary:hover { 
  background: #4b5563 !important; 
}

.kendaraan-content .btn-xs { 
  padding: 6px 10px !important; 
  font-size: 0.8rem !important; 
}

.kendaraan-content .btn-round { 
  border-radius: 50px !important; 
}

.kendaraan-content .mr-2 { 
  margin-right: 8px !important; 
}

.kendaraan-content .row { 
  display: flex !important; 
  flex-wrap: wrap !important; 
  gap: 12px !important; 
  margin: -6px !important; 
}

.kendaraan-content .col-md-3 { 
  flex: 0 0 calc(25% - 12px) !important; 
  margin: 6px !important; 
}

.kendaraan-content .col-md-2 { 
  flex: 0 0 calc(20% - 12px) !important; 
  margin: 6px !important; 
}

.kendaraan-content .col-md-12 {
  flex: 0 0 100% !important;
  width: 100% !important;
}

.kendaraan-content .d-flex { 
  display: flex !important; 
}

.kendaraan-content .align-items-center { 
  align-items: center !important; 
}

.kendaraan-content .align-items-end { 
  align-items: flex-end !important; 
}

.kendaraan-content .g-2 { 
  gap: 12px !important; 
}

.kendaraan-content .mb-3 { 
  margin-bottom: 20px !important; 
}

.kendaraan-content .mt-2 { 
  margin-top: 12px !important; 
}

.kendaraan-content .mt-4 { 
  margin-top: 20px !important; 
}

.kendaraan-content .table-responsive {
  overflow-x: auto !important;
  -webkit-overflow-scrolling: touch !important;
}

.kendaraan-content .table-modern {
  width: 100% !important;
  border-collapse: separate !important;
  border-spacing: 0 10px !important;
}

.kendaraan-content .table-modern thead th {
  font-weight: 700 !important;
  color: var(--muted) !important;
  font-size: 0.85rem !important;
  text-transform: uppercase !important;
  letter-spacing: 0.04em !important;
  padding: 12px 12px !important;
  text-align: center !important;
  vertical-align: middle !important;
  border-bottom: 2px solid var(--border) !important;
  white-space: nowrap !important; /* ✅ PREVENT WRAP HEADER */
}

.kendaraan-content .table-modern tbody td {
  padding: 14px 16px !important;
  vertical-align: middle !important;
  color: var(--txt) !important;
  font-size: 0.95rem !important;
  text-align: center !important;
}

.kendaraan-content .table-modern tbody tr {
  background: var(--card) !important;
  box-shadow: var(--shadow) !important;
}

.kendaraan-content .row-pending { 
  background: var(--warning-soft) !important; 
}

.kendaraan-content .row-approve { 
  background: var(--success-soft) !important; 
}

.kendaraan-content .status-pill {
  display: inline-block !important;
  padding: 6px 12px !important;
  border-radius: 999px !important;
  font-weight: 700 !important;
  font-size: 0.82rem !important;
  white-space: nowrap !important; /* ✅ PREVENT WRAP */
}

.kendaraan-content .status-menunggu {
  background: var(--warning-soft) !important;
  color: #b45309 !important;
  border: 1px solid #fbbf24 !important;
}

.kendaraan-content .status-approve {
  background: var(--success-soft) !important;
  color: #065f46 !important;
  border: 1px solid #86efac !important;
}

.kendaraan-content .vehicle-info {
  display: flex !important;
  flex-direction: column !important;
  gap: 4px !important;
  align-items: center !important;
}

.kendaraan-content .vehicle-name {
  font-weight: 600 !important;
  color: var(--txt) !important;
}

.kendaraan-content .vehicle-desc {
  font-size: 0.85rem !important;
  color: var(--muted) !important;
}

.kendaraan-content .pengemudi-badge {
  display: inline-block !important;
  background: #f0f4f8 !important;
  color: #1e40af !important;
  padding: 6px 10px !important;
  border-radius: 6px !important;
  font-size: 0.85rem !important;
  border-left: 3px solid #1e40af !important;
  font-weight: 500 !important;
  white-space: nowrap !important; /* ✅ PREVENT WRAP */
}

.kendaraan-content .pengemudi-empty {
  color: var(--muted) !important;
  font-style: italic !important;
}

/* ✅ PERBAIKAN JADWAL: PREVENT WRAP */
.kendaraan-content .schedule-display {
  display: flex !important;
  flex-direction: column !important;
  gap: 8px !important;
  font-size: 0.9rem !important;
  align-items: center !important;
}

.kendaraan-content .schedule-row {
  display: flex !important;
  align-items: center !important;
  gap: 8px !important;
  justify-content: center !important;
  white-space: nowrap !important; /* ✅ PREVENT WRAP */
}

.kendaraan-content .schedule-label {
  color: var(--muted) !important;
  font-size: 0.75rem !important;
  text-transform: uppercase !important;
  font-weight: 600 !important;
  min-width: 50px !important;
  white-space: nowrap !important; /* ✅ PREVENT WRAP */
}

.kendaraan-content .schedule-content {
  display: flex !important;
  align-items: center !important;
  gap: 8px !important;
  font-weight: 500 !important;
  justify-content: center !important;
  white-space: nowrap !important; /* ✅ PREVENT WRAP */
}

.kendaraan-content .schedule-content span {
  white-space: nowrap !important; /* ✅ PREVENT WRAP EACH ITEM */
}

.kendaraan-content .schedule-arrow {
  color: var(--muted) !important;
  font-weight: bold !important;
  white-space: nowrap !important; /* ✅ PREVENT WRAP */
  flex-shrink: 0 !important; /* ✅ PREVENT SHRINK */
}

.kendaraan-content .tujuan-badge {
  display: inline-block !important;
  background: #e0f2fe !important;
  color: #0369a1 !important;
  padding: 6px 10px !important;
  border-radius: 6px !important;
  font-size: 0.85rem !important;
  border-left: 3px solid #0369a1 !important;
  font-weight: 500 !important;
  white-space: nowrap !important; /* ✅ PREVENT WRAP */
}

/* ✅ PERBAIKAN AKSI: VERTIKAL */
.kendaraan-content .actions {
  display: flex !important;
  flex-direction: column !important; /* ✅ VERTIKAL */
  gap: 6px !important;
  justify-content: center !important;
  align-items: center !important; /* ✅ CENTER ALIGNMENT */
}

.kendaraan-content .pg {
  display: flex !important;
  justify-content: space-between !important;
  align-items: center !important;
  margin-top: 20px !important;
  flex-wrap: wrap !important;
  gap: 12px !important;
}

.kendaraan-content .pg .info {
  color: var(--muted) !important;
  font-size: 0.9rem !important;
}

.kendaraan-content .pg .pages {
  display: flex !important;
  gap: 6px !important;
  flex-wrap: wrap !important;
}

.kendaraan-content .pg a, 
.kendaraan-content .pg span {
  display: inline-block !important;
  padding: 6px 10px !important;
  border: 1px solid var(--border) !important;
  border-radius: 8px !important;
  text-decoration: none !important;
  color: var(--txt) !important;
}

.kendaraan-content .pg a:hover { 
  background: #f3f4f6 !important; 
}

.kendaraan-content .pg .active {
  background: #111827 !important;
  color: #fff !important;
  border-color: #111827 !important;
}

.kendaraan-content .copyright {
  margin-top: 24px !important;
  color: var(--muted) !important;
  font-size: 0.9rem !important;
  text-align: center !important;
}

@media (max-width: 768px) {
  .kendaraan-content .col-md-3 { 
    flex: 0 0 calc(50% - 12px) !important; 
  }
  
  .kendaraan-content .col-md-2 { 
    flex: 0 0 calc(100% - 12px) !important; 
  }
  
  .kendaraan-content .actions { 
    flex-direction: column !important; /* ✅ TETAP VERTIKAL DI MOBILE */
    width: 100% !important;
  }
  
  .kendaraan-content .schedule-row {
    flex-direction: row !important; /* ✅ TETAP HORIZONTAL TAPI NOWRAP */
    align-items: center !important;
    justify-content: center !important;
    gap: 4px !important;
    white-space: nowrap !important;
  }
  
  .kendaraan-content .schedule-content {
    flex-wrap: nowrap !important;
    white-space: nowrap !important;
  }
}
</style>

<!-- Wrapper untuk Isolasi CSS -->
<div class="kendaraan-content">
  <!-- FILTER -->
  <div class="card mb-3">
    <div class="card-body">
      <form class="row g-2" method="get" action="">
        <?php if (isset($_GET['view'])): ?>
          <input type="hidden" name="view" value="<?php echo htmlspecialchars($_GET['view']); ?>">
        <?php endif; ?>

        <div class="col-md-3">
          <label class="form-label">Cari (Peminjam/Kendaraan)</label>
          <input type="text" class="form-control" name="q" placeholder="Ketik kata kunci..." value="<?php echo htmlspecialchars($keyword); ?>">
        </div>

        <div class="col-md-2">
          <label class="form-label">Status</label>
          <select class="form-control" name="status">
            <option value="">-- Semua --</option>
            <option value="menunggu" <?php echo $status_f==='menunggu'?'selected':''; ?>>menunggu</option>
            <option value="approve" <?php echo $status_f==='approve'?'selected':''; ?>>approve</option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label">Dari Tgl Mulai</label>
          <input type="date" class="form-control" name="dari" value="<?php echo htmlspecialchars($dari); ?>">
        </div>

        <div class="col-md-2">
          <label class="form-label">Sampai Tgl Mulai</label>
          <input type="date" class="form-control" name="sampai" value="<?php echo htmlspecialchars($sampai); ?>">
        </div>

        <div class="col-md-3 mt-4 d-flex align-items-end">
          <button type="submit" class="btn btn-primary mr-2"><i class="fa fa-filter"></i> Terapkan Filter</button>
          <a href="?<?php echo isset($_GET['view']) ? 'view='.urlencode($_GET['view']) : ''; ?>" class="btn btn-secondary">
            <i class="fa fa-undo"></i> Reset
          </a>
            <a href="peminjaman/export_pinjamkendaraan_pdf.php?<?php echo build_query_keep(); ?>" 
              class="btn btn-danger" title="Download PDF">
              <i class="fa fa-file-pdf"></i> PDF
            </a>
            <a href="peminjaman/export_pinjamkendaraan_csv.php?<?php echo build_query_keep(); ?>" 
              class="btn btn-success mr-2" title="Download Excel">
              <i class="fa fa-file-excel"></i> Excel
            </a>
          </a>
        </div>
      </form>
    </div>
  </div>

  <!-- DATA TABLE -->
  <div class="row">
    <div class="col-md-12">
      <div class="card">

        <div class="card-body">
          <div class="table-responsive">
            <table class="table-modern">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Peminjam</th>
                  <th>Kendaraan</th>
                  <th>Pengemudi</th>
                  <th>Tanggal & Jadwal</th>
                  <th>Tujuan</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $no = $start + 1;
                foreach($data_list as $pin):
                  $status_lc = strtolower(trim($pin['status']));
                  $rowClass = ($status_lc === 'approve') ? 'row-approve' : 'row-pending';
                  $tglMulai = fmt_tgl($pin['tgl_mulai']);
                  $tglSelesai = fmt_tgl($pin['tgl_selesai']);
                  $wm = fmt_waktu($pin['waktu_mulai']);
                  $ws = fmt_waktu($pin['waktu_selesai']);
                  $tujuan = htmlspecialchars($pin['tujuan'] ?? '-');
                  $pengemudi = htmlspecialchars($pin['pengemudi'] ?? '-');
                ?>
                  <tr class="<?php echo $rowClass; ?>">
                    <td><strong><?php echo $no++; ?></strong></td>
                    <td><?php echo htmlspecialchars($pin['nama_lengkap']); ?></td>
                    <td>
                      <div class="vehicle-info">
                        <div class="vehicle-name"><?php echo htmlspecialchars($pin['nama_kendaraan']); ?></div>
                        <div class="vehicle-desc"><?php echo htmlspecialchars($pin['deskripsi']); ?></div>
                      </div>
                    </td>
                    <td>
                      <?php if ($pengemudi !== '-'): ?>
                        <span class="pengemudi-badge">
                          <i class="fa fa-user-circle"></i> <?php echo $pengemudi; ?>
                        </span>
                      <?php else: ?>
                        <span class="pengemudi-empty">-</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="schedule-display">
                        <div class="schedule-row">
                          <span class="schedule-content">
                            <?php echo $tglMulai; ?> <span class="schedule-arrow">→</span> <?php echo $tglSelesai; ?>
                          </span>
                        </div>
                        <div class="schedule-row">
                          <span class="schedule-content">
                            <?php echo $wm; ?> <span class="schedule-arrow">→</span> <?php echo $ws; ?>
                          </span>
                        </div>
                      </div>
                    </td>
                    <td>
                      <?php if ($tujuan !== '-'): ?>
                        <span class="tujuan-badge">
                          <i class="fa fa-map-marker"></i> <?php echo $tujuan; ?>
                        </span>
                      <?php else: ?>
                        <span style="color: var(--muted);">-</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php 
                      if ($status_lc === 'menunggu') {
                        $status_class = 'status-menunggu';
                        $status_label = 'Menunggu';
                      } else {
                        $status_class = 'status-approve';
                        $status_label = 'Disetujui';
                      }
                      ?>
                      <span class="status-pill <?php echo $status_class; ?>"><?php echo $status_label; ?></span>
                    </td>
                    <td>
                      <div class="actions">
                        <?php if ($status_lc !== 'approve') { ?>
                          <button type="button" class="btn btn-xs btn-info" title="Edit" 
                            onclick='openEditModal(<?php echo json_encode($pin); ?>)'>
                            <i class="fa fa-pencil-alt"></i>
                          </button>
                        <?php } ?>

                        <a href="?view=detailpinjamkendaraan&id=<?php echo urlencode($pin['id_pk']); ?>" class="btn btn-xs btn-success" title="Detail"><i class="fa fa-eye"></i></a>

                        <?php if ($status_lc === 'approve') { ?>
                          <button type="button" class="btn btn-xs btn-warning" title="Batal Approve" 
                            onclick='openBatalModal(<?php echo json_encode($pin); ?>)'>
                            <i class="fa fa-undo"></i>
                          </button>
                        <?php } ?>

                        <button type="button" class="btn btn-xs btn-danger" title="Hapus" 
                          onclick='openHapusModal(<?php echo json_encode($pin); ?>)'>
                          <i class="fa fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <?php
          $show_from = $total_rows ? ($start + 1) : 0;
          $show_to   = min($start + $per_page, $total_rows);
          ?>
          <div class="pg">
            <div class="info">Menampilkan <?php echo $show_from; ?>–<?php echo $show_to; ?> dari <?php echo $total_rows; ?> data</div>
            <div class="pages">
              <?php
              $first = 1;
              $prev  = max(1, $page-1);
              $next  = min($total_pages, $page+1);
              $last  = $total_pages;

              $make = function($p,$label=null,$active=false){
                $label = $label ?? $p;
                if ($active) {
                  return '<span class="active">'.$label.'</span>';
                }
                return '<a href="?'.build_query_keep(['page'=>$p]).'">'.$label.'</a>';
              };

              echo $make($first,'« Awal');
              echo $make($prev,'‹ Prev');

              $win = 2;
              $start_p = max(1, $page-$win);
              $end_p   = min($total_pages, $page+$win);
              for($p=$start_p; $p<=$end_p; $p++){
                echo $make($p, (string)$p, $p===$page);
              }

              echo $make($next,'Next ›');
              echo $make($last,'Akhir »');
              ?>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <h6 class="copyright"><i class="fa fa-copyright"></i> Copyright@2025 | <strong>SIPINJAM</strong></h6>
</div>

<!-- SINGLE MODAL EDIT (Populated by JavaScript) -->
<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa fa-pencil-alt"></i> Edit Kendaraan</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form method="POST" action="" id="formEdit">
        <div class="modal-body">
          <input type="hidden" name="id_pk" id="edit_id_pk">
          <div class="form-group">
            <label>Nama Kendaraan</label>
            <select class="form-control" name="id_kendaraan" id="edit_id_kendaraan" required>
              <option value="" hidden>-- Pilih Kendaraan --</option>
              <?php
              $kend = $conn->query("SELECT id_kendaraan, nama_kendaraan, deskripsi FROM kendaraan ORDER BY nama_kendaraan ASC");
              while ($k = $kend->fetch_assoc()):
              ?>
                <option value="<?php echo htmlspecialchars($k['id_kendaraan']); ?>">
                  <?php echo htmlspecialchars($k['nama_kendaraan'] . ' - ' . $k['deskripsi']); ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Pengemudi</label>
            <input type="text" name="pengemudi" id="edit_pengemudi" placeholder="Nama pengemudi..." class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <i class="fa fa-times"></i> Batal
          </button>
          <button type="submit" name="edit" class="btn btn-info">
            <i class="fa fa-check"></i> Setujui
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- SINGLE MODAL HAPUS -->
<div class="modal fade" id="modalHapus" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa fa-trash"></i> Hapus Peminjaman</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form method="POST" action="">
        <div class="modal-body">
          <input type="hidden" name="id_pk" id="hapus_id_pk">
          <h4>Apakah Anda ingin menghapus data ini?</h4>
          <div id="hapus_info"></div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="hapus" class="btn btn-danger"><i class="fa fa-trash"></i> Ya, Hapus</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times"></i> Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- SINGLE MODAL BATAL APPROVE -->
<div class="modal fade" id="modalBatal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa fa-undo"></i> Batal Approve</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form method="POST" action="">
        <div class="modal-body">
          <input type="hidden" name="id_pk" id="batal_id_pk">
          <input type="hidden" name="id_kendaraan" id="batal_id_kendaraan">
          <h4>Batal Approval Peminjaman?</h4>
          <p>Apakah Anda yakin ingin membatalkan approval untuk:</p>
          <div id="batal_info"></div>
          <p style="color: #666; font-size: 0.9rem; margin-top: 12px;">
            <i class="fa fa-info-circle"></i> Status akan kembali menjadi "Menunggu Approve" dan kendaraan akan tersedia kembali.
          </p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <i class="fa fa-times"></i> Batal
          </button>
          <button type="submit" name="batal_approve" class="btn btn-warning">
            <i class="fa fa-undo"></i> Ya, Batalkan Approval
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Helper function untuk escape HTML
function escapeHtml(text) {
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return text.replace(/[&<>"']/g, m => map[m]);
}

function openEditModal(data) {
  document.getElementById('edit_id_pk').value = data.id_pk;
  document.getElementById('edit_id_kendaraan').value = data.id_kendaraan;
  document.getElementById('edit_pengemudi').value = data.pengemudi || '';
  $('#modalEdit').modal('show');
}

function openHapusModal(data) {
  document.getElementById('hapus_id_pk').value = data.id_pk;
  const pengemudi = data.pengemudi || '-';
  const tujuan = data.tujuan || '-';
  
  let html = '<p><strong>' + escapeHtml(data.nama_kendaraan) + '</strong> oleh <strong>' + escapeHtml(data.nama_lengkap) + '</strong></p>';
  
  if (pengemudi !== '-') {
    html += '<p style="color: var(--muted); font-size: 0.9rem;"><i class="fa fa-user-circle"></i> Pengemudi: <strong>' + escapeHtml(pengemudi) + '</strong></p>';
  }
  
  if (tujuan !== '-') {
    html += '<p style="color: var(--muted); font-size: 0.9rem;"><i class="fa fa-map-marker"></i> Tujuan: <strong>' + escapeHtml(tujuan) + '</strong></p>';
  }
  
  document.getElementById('hapus_info').innerHTML = html;
  $('#modalHapus').modal('show');
}

function openBatalModal(data) {
  document.getElementById('batal_id_pk').value = data.id_pk;
  document.getElementById('batal_id_kendaraan').value = data.id_kendaraan;
  
  const pengemudi = data.pengemudi || '-';
  
  let html = '<div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px 16px; border-radius: 6px; margin: 12px 0; font-size: 0.9rem;">';
  html += '<div style="margin-bottom: 8px;"><strong>' + escapeHtml(data.nama_kendaraan) + '</strong></div>';
  html += '<div style="font-size: 0.85rem; color: #6b5b05;">';
  html += '<div><i class="fa fa-user-circle"></i> Pengemudi: <strong>' + escapeHtml(pengemudi) + '</strong></div>';
  html += '<div><i class="fa fa-user"></i> Peminjam: <strong>' + escapeHtml(data.nama_lengkap) + '</strong></div>';
  html += '</div></div>';
  
  document.getElementById('batal_info').innerHTML = html;
  $('#modalBatal').modal('show');
}
</script>
