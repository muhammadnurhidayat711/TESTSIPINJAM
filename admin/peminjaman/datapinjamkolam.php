<?php
include '../koneksi.php';

// ================== PROSES APPROVE ==================
if (isset($_GET['id_approve'])) {
    $id_approve = (int)$_GET['id_approve'];
    $stmtApprove = $conn->prepare("UPDATE pinjamkolam SET status = 'approve' WHERE id_pinjamkolam = ?");
    $stmtApprove->bind_param("i", $id_approve);
    $stmtApprove->execute();
    $stmtApprove->close();
    echo "<script>window.location.href='?view=datapinjamkolam';</script>";
    exit;
}

// ================== PROSES BATAL APPROVE ==================
if (isset($_GET['id_batal'])) {
    $id_batal = (int)$_GET['id_batal'];
    $stmtBatal = $conn->prepare("UPDATE pinjamkolam SET status = 'menunggu' WHERE id_pinjamkolam = ?");
    $stmtBatal->bind_param("i", $id_batal);
    $stmtBatal->execute();
    $stmtBatal->close();
    echo "<script>window.location.href='?view=datapinjamkolam';</script>";
    exit;
}

// ================== PROSES HAPUS (DIRECT DELETE) ==================
if (isset($_GET['id_hapus'])) {
    $id_pinjamkolam = (int)$_GET['id_hapus'];
    $stmtHapus = $conn->prepare("DELETE FROM pinjamkolam WHERE id_pinjamkolam = ?");
    $stmtHapus->bind_param("i", $id_pinjamkolam);
    $stmtHapus->execute();
    $stmtHapus->close();
    echo "<script>window.location.href='?view=datapinjamkolam';</script>";
    exit;
}

// ================== Helper Functions ==================
function fmt_tgl($ymd)
{
    if (!$ymd || $ymd === '0000-00-00') return '-';
    $parts = explode('-', $ymd);
    if (count($parts) !== 3) return htmlspecialchars($ymd);
    [$y, $m, $d] = $parts;
    $bulan = ["", "Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
    $month_index = (int)$m;
    if ($month_index < 1 || $month_index > 12) return htmlspecialchars($ymd);
    return sprintf("%02d %s %s", (int)$d, $bulan[$month_index], $y);
}

function fmt_waktu($hms)
{
    return $hms ? substr($hms, 0, 5) : '-';
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

// ================== AMBIL DATA FILTER ==================
$filter_status   = $_GET['status']    ?? '';
$filter_tgl1     = $_GET['tgl1']      ?? '';
$filter_tgl2     = $_GET['tgl2']      ?? '';
$filter_rec      = $_GET['recurring'] ?? '';
$filter_kolam    = $_GET['id_kolam']  ?? '';

// ================== OPTION KOLAM UNTUK FILTER ==================
$kolam_opts = [];
$qKolam = $conn->query("SELECT id_kolam, jenis_kolam FROM kolam ORDER BY jenis_kolam");
if ($qKolam) {
    while ($r = $qKolam->fetch_assoc()) {
        $kolam_opts[] = $r;
    }
    $qKolam->close();
}

// ================== BUILD QUERY DENGAN FILTER ==================
$sql = "SELECT 
            pk.*,
            u.nama_lengkap,
            k.jenis_kolam,
            kl.nama_kelas
        FROM pinjamkolam pk
        INNER JOIN user u  ON u.id       = pk.id_user
        INNER JOIN kolam k ON k.id_kolam = pk.id_kolam
        INNER JOIN kelas kl ON kl.id_kelas = pk.id_kelas
        WHERE 1";

$params = [];
$types  = "";

if ($filter_status !== '') {
    $sql    .= " AND pk.status = ?";
    $types  .= "s";
    $params[] = $filter_status;
}

if ($filter_tgl1 !== '' && $filter_tgl2 !== '') {
    $sql    .= " AND pk.tgl_mulai BETWEEN ? AND ?";
    $types  .= "ss";
    $params[] = $filter_tgl1;
    $params[] = $filter_tgl2;
} elseif ($filter_tgl1 !== '' && $filter_tgl2 === '') {
    $sql    .= " AND pk.tgl_mulai >= ?";
    $types  .= "s";
    $params[] = $filter_tgl1;
} elseif ($filter_tgl1 === '' && $filter_tgl2 !== '') {
    $sql    .= " AND pk.tgl_mulai <= ?";
    $types  .= "s";
    $params[] = $filter_tgl2;
}

if ($filter_kolam !== '') {
    $sql    .= " AND pk.id_kolam = ?";
    $types  .= "i";
    $params[] = (int)$filter_kolam;
}

if ($filter_rec === 'yes') {
    $sql .= " AND pk.is_recurring = 'yes'";
} elseif ($filter_rec === 'no') {
    $sql .= " AND pk.is_recurring = 'no'";
}

$sql .= " ORDER BY pk.tgl_mulai DESC, pk.waktu_mulai DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<style>
.page-inner .kolam-content {
  all: revert !important;
}

.kolam-content,
.kolam-content * {
  box-sizing: border-box !important;
}

.kolam-content .card,
.kolam-content .card-header,
.kolam-content .card-body,
.kolam-content .row {
  margin: revert !important;
  padding: revert !important;
}

.kolam-content {
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
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.kolam-content .card {
  background: var(--card) !important;
  border: 1px solid var(--border) !important;
  border-radius: 10px !important;
  box-shadow: var(--shadow) !important;
  margin-bottom: 18px !important;
  overflow: hidden !important;
}

.kolam-content .card-header {
  padding: 14px 18px !important;
  background: #f9fafb !important;
  border-bottom: 1px solid var(--border) !important;
}

.kolam-content .card-header h4 {
  font-size: 1rem !important;
  font-weight: 700 !important;
  color: var(--txt) !important;
  margin: 0 0 10px 0 !important;
}

.kolam-content .card-body {
  padding: 16px !important;
}

.kolam-content .filter-row {
  margin-top: 12px !important;
}

.kolam-content .filter-row .row {
  display: grid !important;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)) !important;
  gap: 10px !important;
}

.kolam-content .filter-row label {
  font-size: 0.7rem !important;
  font-weight: 600 !important;
  color: var(--muted) !important;
  display: block !important;
  margin-bottom: 5px !important;
}

.kolam-content .form-control {
  padding: 6px 10px !important;
  border: 1px solid var(--border) !important;
  border-radius: 6px !important;
  font-size: 0.8rem !important;
  width: 100% !important;
}

.kolam-content .export-buttons {
  display: flex !important;
  flex-wrap: wrap !important;
  gap: 8px !important;
  margin-top: 12px !important;
}

.kolam-content .table-wrapper {
  overflow-x: auto !important;
  -webkit-overflow-scrolling: touch !important;
  margin-top: 12px !important;
}

.kolam-content .table {
  width: 100% !important;
  border-collapse: separate !important;
  border-spacing: 0 8px !important;
}

.kolam-content .table thead th {
  font-weight: 700 !important;
  color: var(--muted) !important;
  font-size: 0.72rem !important;
  text-transform: uppercase !important;
  letter-spacing: 0.04em !important;
  padding: 10px 12px !important;
  text-align: center !important;
  vertical-align: middle !important;
  border-bottom: 2px solid var(--border) !important;
  white-space: nowrap !important;
}

.kolam-content .table tbody td {
  padding: 12px 14px !important;
  vertical-align: middle !important;
  color: var(--txt) !important;
  font-size: 0.82rem !important;
  text-align: center !important;
  line-height: 1.4 !important;
}

.kolam-content .table tbody tr {
  background: var(--card) !important;
  box-shadow: var(--shadow) !important;
  transition: background 0.15s ease !important;
}

.kolam-content .table tbody tr:hover {
  background: #f9fafb !important;
}

.kolam-content .row-pending {
  background: var(--warning-soft) !important;
}

.kolam-content .row-approve {
  background: var(--success-soft) !important;
}

.kolam-content .badge {
  display: inline-block !important;
  padding: 4px 10px !important;
  border-radius: 999px !important;
  font-weight: 700 !important;
  font-size: 0.7rem !important;
}

.kolam-content .badge-success {
  background: var(--success-soft) !important;
  color: #065f46 !important;
  border: 1px solid #86efac !important;
}

.kolam-content .badge-danger {
  background: #fee2e2 !important;
  color: #991b1b !important;
  border: 1px solid #fca5a5 !important;
}

.kolam-content .badge-warning {
  background: var(--warning-soft) !important;
  color: #b45309 !important;
  border: 1px solid #fbbf24 !important;
}

.kolam-content .badge-info {
  background: #cffafe !important;
  color: #0e7490 !important;
  border: 1px solid #67e8f9 !important;
}

.kolam-content .badge-recurring {
  background: #e0f2fe !important;
  color: #0369a1 !important;
  padding: 4px 8px !important;
  border-radius: 4px !important;
  font-size: 0.7rem !important;
  border-left: 3px solid #0369a1 !important;
  font-weight: 500 !important;
  white-space: nowrap !important;
}

.kolam-content .badge-outline {
  background: #f3f4f6 !important;
  color: var(--muted) !important;
  border: 1px solid var(--border) !important;
}

.kolam-content .tujuan-badge {
  display: inline-block !important;
  background: #fef3c7 !important;
  color: #b45309 !important;
  padding: 4px 8px !important;
  border-radius: 4px !important;
  font-size: 0.72rem !important;
  border-left: 3px solid #f59e0b !important;
  font-weight: 500 !important;
  white-space: nowrap !important;
}

.kolam-content .btn {
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: 6px !important;
  padding: 6px 12px !important;
  border: none !important;
  border-radius: 4px !important;
  font-size: 0.78rem !important;
  font-weight: 600 !important;
  cursor: pointer !important;
  text-decoration: none !important;
  transition: all 0.2s ease !important;
  white-space: nowrap !important;
}

.kolam-content .btn:hover {
  transform: translateY(-2px) !important;
  box-shadow: var(--shadow) !important;
  text-decoration: none !important;
}

.kolam-content .btn-icon {
  width: 32px !important;
  height: 32px !important;
  padding: 0 !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  border-radius: 4px !important;
  font-size: 0.9rem !important;
}

.kolam-content .btn-sm {
  padding: 5px 10px !important;
  font-size: 0.75rem !important;
}

.kolam-content .btn-primary {
  background: var(--info) !important;
  color: white !important;
}

.kolam-content .btn-primary:hover {
  background: #2563eb !important;
}

.kolam-content .btn-success {
  background: var(--success) !important;
  color: white !important;
}

.kolam-content .btn-success:hover {
  background: #16a34a !important;
}

.kolam-content .btn-danger {
  background: var(--danger) !important;
  color: white !important;
}

.kolam-content .btn-danger:hover {
  background: #dc2626 !important;
}

.kolam-content .btn-warning {
  background: var(--warning) !important;
  color: white !important;
}

.kolam-content .btn-warning:hover {
  background: #d97706 !important;
}

.kolam-content .btn-secondary {
  background: var(--muted) !important;
  color: white !important;
}

.kolam-content .btn-secondary:hover {
  background: #4b5563 !important;
}

.kolam-content .action-buttons {
  display: flex !important;
  flex-direction: column !important;
  gap: 5px !important;
  align-items: center !important;
  justify-content: center !important;
}

.kolam-content .schedule-display {
  display: flex !important;
  flex-direction: column !important;
  gap: 6px !important;
  font-size: 0.8rem !important;
  align-items: center !important;
}

.kolam-content .schedule-row {
  display: flex !important;
  align-items: center !important;
  gap: 6px !important;
  justify-content: center !important;
  white-space: nowrap !important;
}

.kolam-content .schedule-content {
  display: flex !important;
  align-items: center !important;
  gap: 6px !important;
  font-weight: 500 !important;
  justify-content: center !important;
  white-space: nowrap !important;
}

.kolam-content .schedule-content span {
  white-space: nowrap !important;
}

.kolam-content .schedule-arrow {
  color: var(--muted) !important;
  font-weight: bold !important;
  font-size: 0.85rem !important;
  flex-shrink: 0 !important;
}

.kolam-content .footer {
  margin-top: 20px !important;
  padding: 14px 0 !important;
  color: var(--muted) !important;
  font-size: 0.8rem !important;
  text-align: center !important;
}

.kolam-content .empty-state {
  text-align: center !important;
  padding: 40px 20px !important;
  color: var(--muted) !important;
}

.kolam-content .empty-state i {
  font-size: 2.5rem !important;
  opacity: 0.2 !important;
  display: block !important;
  margin-bottom: 14px !important;
}

.kolam-content .empty-state p {
  margin: 0 !important;
  font-size: 0.85rem !important;
}

.kolam-content .mt-3 {
  margin-top: 12px !important;
}

.kolam-content .d-flex {
  display: flex !important;
}

.kolam-content .btn-block {
  width: 100% !important;
}

@media (max-width: 768px) {
  .kolam-content .card-header h4 {
    font-size: 0.95rem !important;
  }

  .kolam-content .filter-row .row {
    grid-template-columns: 1fr !important;
  }

  .kolam-content .export-buttons {
    flex-direction: column !important;
  }

  .kolam-content .export-buttons .btn {
    width: 100% !important;
  }

  .kolam-content .table-wrapper {
    overflow-x: visible !important;
  }

  .kolam-content .table {
    border-spacing: 0 !important;
  }

  .kolam-content .table thead {
    display: none !important;
  }

  .kolam-content .table tbody tr {
    display: block !important;
    margin-bottom: 16px !important;
    border: 1px solid var(--border) !important;
    border-radius: 8px !important;
    overflow: hidden !important;
    box-shadow: var(--shadow) !important;
  }

  .kolam-content .table tbody td {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    padding: 12px 14px !important;
    border-bottom: 1px solid var(--border) !important;
    text-align: left !important;
  }

  .kolam-content .table tbody td:last-child {
    border-bottom: none !important;
    justify-content: center !important;
  }

  .kolam-content .table tbody td::before {
    content: attr(data-label) !important;
    font-weight: 600 !important;
    color: var(--muted) !important;
    font-size: 0.7rem !important;
    text-transform: uppercase !important;
    margin-right: 10px !important;
  }

  .kolam-content .table tbody td:last-child::before {
    display: none !important;
  }

  .kolam-content .action-buttons {
    width: 100% !important;
    align-items: center !important;
  }

  .kolam-content .schedule-display {
    align-items: flex-start !important;
  }
}
</style>

<div class="kolam-content">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Data Peminjaman Kolam</h4>

      <!-- FILTER FORM -->
      <form method="GET" class="filter-row">
        <input type="hidden" name="view" value="datapinjamkolam" />
        <div class="row">
          <div>
            <label>Status</label>
            <select name="status" class="form-control">
              <option value="">Semua</option>
              <option value="menunggu" <?= $filter_status=='menunggu'?'selected':''; ?>>Menunggu</option>
              <option value="approve"  <?= $filter_status=='approve'?'selected':''; ?>>Approve</option>
              <option value="selesai"  <?= $filter_status=='selesai'?'selected':''; ?>>Selesai</option>
              <option value="batal"    <?= $filter_status=='batal'?'selected':''; ?>>Batal</option>
            </select>
          </div>

          <div>
            <label>Tgl Mulai</label>
            <input type="date" name="tgl1" class="form-control" value="<?= htmlspecialchars($filter_tgl1); ?>">
          </div>

          <div>
            <label>Tgl Sampai</label>
            <input type="date" name="tgl2" class="form-control" value="<?= htmlspecialchars($filter_tgl2); ?>">
          </div>

          <div>
            <label>Kolam</label>
            <select name="id_kolam" class="form-control">
              <option value="">Semua</option>
              <?php foreach ($kolam_opts as $ko): ?>
                <option value="<?= $ko['id_kolam']; ?>" <?= $filter_kolam == $ko['id_kolam'] ? 'selected' : ''; ?>>
                  <?= htmlspecialchars($ko['jenis_kolam']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label>Jadwal Rutin</label>
            <select name="recurring" class="form-control">
              <option value="">Semua</option>
              <option value="yes" <?= $filter_rec=='yes'?'selected':''; ?>>Rutin</option>
              <option value="no"  <?= $filter_rec=='no'?'selected':'';  ?>>Tidak</option>
            </select>
          </div>

          <div>
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-primary btn-block">
              <i class="fa fa-filter"></i> Filter
            </button>
          </div>
        </div>
      </form>

      <!-- TOMBOL EXPORT -->
      <div class="export-buttons">
        <?php $queryString = http_build_query($_GET); ?>
        <a href="peminjaman/export_pinjamkolam_pdf.php?<?= $queryString; ?>" class="btn btn-danger btn-sm">
          <i class="fa fa-file-pdf"></i> Export PDF
        </a>
        <a href="peminjaman/export_pinjamkolam_excel.php?<?= $queryString; ?>" class="btn btn-success btn-sm">
          <i class="fa fa-file-excel"></i> Export Excel
        </a>
      </div>
    </div>

    <div class="card-body">
      <div class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>No</th>
              <th>Nama</th>
              <th>Divisi</th>
              <th>Kelas</th>
              <th>Kolam</th>
              <th>Jadwal</th>
              <th>Tujuan</th>
              <th>Jadwal Rutin</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $no = 1;
            if ($result && $result->num_rows > 0):
              while ($pinjamkolam = $result->fetch_assoc()):
              $tgl_mulai_raw   = $pinjamkolam['tgl_mulai'] ?? '';
              $tgl_selesai_raw = $pinjamkolam['tgl_selesai'] ?? '';

              $tgl_mulai_f     = fmt_tgl($tgl_mulai_raw);
              $tgl_selesai_f   = fmt_tgl($tgl_selesai_raw);

              $wkt_mulai_f     = fmt_waktu($pinjamkolam['waktu_mulai']);
              $wkt_selesai_f   = fmt_waktu($pinjamkolam['waktu_selesai']);

              $status_lc = strtolower(trim($pinjamkolam['status']));
              $rowClass = '';
              if ($status_lc === 'menunggu') {
                $rowClass = 'row-pending';
              } elseif ($status_lc === 'approve') {
                $rowClass = 'row-approve';
              }

              $is_rec   = $pinjamkolam['is_recurring'] ?? 'no';
              $rec_days = $pinjamkolam['recurring_days'] ?? '';
              
              $rec_nums = parseRecurringDays($rec_days);
              $rec_names = mapHariByNumber($rec_nums);
              
              $tujuan = $pinjamkolam['tujuan'] ?? '-';
            ?>
            <tr class="<?= $rowClass; ?>">
              <td data-label="No"><strong><?php echo $no++; ?></strong></td>
              <td data-label="Nama"><?php echo htmlspecialchars($pinjamkolam['nama']); ?></td>
              <td data-label="Divisi"><?php echo htmlspecialchars($pinjamkolam['nama_lengkap']); ?></td>
              <td data-label="Kelas"><?php echo htmlspecialchars($pinjamkolam['nama_kelas']); ?></td>
              <td data-label="Kolam"><?php echo htmlspecialchars($pinjamkolam['jenis_kolam']); ?></td>
              <td data-label="Jadwal">
                <div class="schedule-display">
                  <div class="schedule-row">
                    <span class="schedule-content">
                      <?= $tgl_mulai_f; ?>
                      <?php if (!empty($tgl_selesai_raw) && $tgl_selesai_raw !== '0000-00-00' && $tgl_selesai_raw !== $tgl_mulai_raw): ?>
                        <span class="schedule-arrow">→</span>
                        <?= $tgl_selesai_f; ?>
                      <?php endif; ?>
                    </span>
                  </div>
                  <div class="schedule-row">
                    <span class="schedule-content">
                      <?= $wkt_mulai_f; ?> <span class="schedule-arrow">→</span> <?= $wkt_selesai_f; ?>
                    </span>
                  </div>
                </div>
              </td>
              
              <td data-label="Tujuan">
                <?php if ($tujuan !== '-' && !empty($tujuan)): ?>
                  <span class="tujuan-badge">
                    <i class="fa fa-bullseye"></i> <?= htmlspecialchars($tujuan); ?>
                  </span>
                <?php else: ?>
                  <span style="color: var(--muted);">-</span>
                <?php endif; ?>
              </td>
              
              <td data-label="Jadwal Rutin">
                <?php if ($is_rec === 'yes'): ?>
                  <span class="badge-recurring">
                    <i class="fa fa-repeat"></i> 
                    <?php if (!empty($rec_names)): ?>
                      <?= implode(', ', $rec_names); ?>
                    <?php else: ?>
                      Rutin
                    <?php endif; ?>
                  </span>
                <?php else: ?>
                  <span class="badge badge-outline">Tidak</span>
                <?php endif; ?>
              </td>
              
              <td data-label="Status">
                <?php if ($pinjamkolam['status'] == 'menunggu') { ?>
                  <span class="badge badge-warning">Menunggu</span>
                <?php } elseif ($pinjamkolam['status'] == 'approve') { ?>
                  <span class="badge badge-success">Disetujui</span>
                <?php } elseif ($pinjamkolam['status'] == 'selesai') { ?>
                  <span class="badge badge-info">Selesai</span>
                <?php } else { ?>
                  <span class="badge badge-danger"><?php echo ucfirst(htmlspecialchars($pinjamkolam['status'])); ?></span>
                <?php } ?>
              </td>
              
              <td data-label="Aksi">
                <div class="action-buttons">
                  <a href="?view=detailpinjamkolam&id=<?php echo $pinjamkolam['id_pinjamkolam']; ?>" 
                     title="Detail" 
                     class="btn btn-primary btn-icon">
                    <i class="fa fa-eye"></i>
                  </a>
                  
                  <?php if ($pinjamkolam['status'] == 'menunggu') { ?>
                    <a href="?view=datapinjamkolam&id_approve=<?php echo $pinjamkolam['id_pinjamkolam']; ?>" 
                       title="Setujui" 
                       class="btn btn-success btn-icon"
                       onclick="return confirm('✅ SETUJUI PEMINJAMAN\n\nYakin ingin menyetujui peminjaman ini?\n\n👤 Peminjam: <?= htmlspecialchars($pinjamkolam['nama']); ?>\n🏊 Kolam: <?= htmlspecialchars($pinjamkolam['jenis_kolam']); ?>\n📅 <?= $tgl_mulai_f; ?> (<?= $wkt_mulai_f; ?> - <?= $wkt_selesai_f; ?>)')">
                      <i class="fa fa-check"></i>
                    </a>
                  <?php } ?>

                  <?php if ($pinjamkolam['status'] == 'approve') { ?>
                    <a href="?view=datapinjamkolam&id_batal=<?php echo $pinjamkolam['id_pinjamkolam']; ?>" 
                       title="Batal Approve" 
                       class="btn btn-warning btn-icon"
                       onclick="return confirm('↩️ BATAL PERSETUJUAN\n\nYakin membatalkan persetujuan?\nStatus akan kembali ke \"Menunggu\"\n\n👤 <?= htmlspecialchars($pinjamkolam['nama']); ?>\n🏊 <?= htmlspecialchars($pinjamkolam['jenis_kolam']); ?>')">
                      <i class="fa fa-undo"></i>
                    </a>
                  <?php } ?>
                  
                  <a href="?view=datapinjamkolam&id_hapus=<?php echo $pinjamkolam['id_pinjamkolam']; ?>" 
                     title="Hapus" 
                     class="btn btn-danger btn-icon"
                     onclick="return confirm('🗑️ HAPUS PERMANENT 🗑️\n\n⚠️ Data akan hilang selamanya!\n\n👤 Peminjam: <?= htmlspecialchars($pinjamkolam['nama']); ?>\n🏊 Kolam: <?= htmlspecialchars($pinjamkolam['jenis_kolam']); ?>\n📅 <?= $tgl_mulai_f; ?> <?= $wkt_mulai_f; ?> - <?= $wkt_selesai_f; ?>\n<?= ($tujuan !== '-' && !empty($tujuan)) ? '🎯 Tujuan: ' . htmlspecialchars($tujuan) : ''; ?>\n\nYAKIN HAPUS?')">
                    <i class="fa fa-trash"></i>
                  </a>
                </div>
              </td>
            </tr>
            <?php 
              endwhile;
            else:
            ?>
            <tr>
              <td colspan="10">
                <div class="empty-state">
                  <i class="fa fa-inbox"></i>
                  <p>Belum ada data peminjaman kolam</p>
                </div>
              </td>
            </tr>
            <?php endif; 
            $stmt->close();
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <h6 class="footer"><i class="fa fa-copyright"></i> Copyright@2025 | <strong>SIPINJAM</strong></h6>
</div>
