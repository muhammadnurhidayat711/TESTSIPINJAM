<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once __DIR__ . '/../cek.php';
require_once dirname(__DIR__, 1) . '/../koneksi.php';

// ================== HELPER ==================
if (!function_exists('e')) {
  function e($v)
  {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
  }
}

function fmt_tgl($ymd)
{
  if (!$ymd || $ymd === '0000-00-00')
    return '-';
  $parts = explode('-', $ymd);
  if (count($parts) !== 3)
    return e($ymd);
  [$y, $m, $d] = $parts;
  $bulan = ["", "Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
  $mi = (int) $m;
  if ($mi < 1 || $mi > 12)
    return e($ymd);
  return sprintf("%02d %s %s", (int) $d, $bulan[$mi], $y);
}

function fmt_waktu($hms)
{
  return $hms ? substr($hms, 0, 5) : '-';
}

function parseRecurringDaysStudio($str)
{
  if (!$str)
    return [];
  if (preg_match_all('/[1-7]/', (string) $str, $m)) {
    $nums = array_map('intval', $m[0]);
    $nums = array_values(array_unique($nums));
    sort($nums, SORT_NUMERIC);
    return $nums;
  }
  return [];
}

function mapHariByNumberStudio($nums)
{
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
    $n = (int) $n;
    if (isset($map[$n]))
      $out[] = $map[$n];
  }
  return $out;
}

// ================== PROSES APPROVE ==================
if (isset($_GET['id_approve'])) {
  $id_approve = (int) $_GET['id_approve'];
  if ($id_approve > 0) {
    $stmtA = $conn->prepare("UPDATE pinjamstudio SET status = 'approve' WHERE id_pinjamstudio = ?");
    $stmtA->bind_param("i", $id_approve);
    $stmtA->execute();
    $stmtA->close();
  }
  echo "<script>window.location.href='?view=datapinjamstudio';</script>";
  exit;
}

// ================== PROSES BATAL APPROVE ==================
if (isset($_GET['id_batal'])) {
  $id_batal = (int) $_GET['id_batal'];
  if ($id_batal > 0) {
    $stmtB = $conn->prepare("UPDATE pinjamstudio SET status = 'menunggu' WHERE id_pinjamstudio = ?");
    $stmtB->bind_param("i", $id_batal);
    $stmtB->execute();
    $stmtB->close();
  }
  echo "<script>window.location.href='?view=datapinjamstudio';</script>";
  exit;
}

// ================== PROSES HAPUS (DIRECT DELETE) ==================
if (isset($_GET['id_hapus'])) {
  $id_pinjamstudio = (int) $_GET['id_hapus'];
  if ($id_pinjamstudio > 0) {
    $stmtH = $conn->prepare("DELETE FROM pinjamstudio WHERE id_pinjamstudio = ?");
    $stmtH->bind_param("i", $id_pinjamstudio);
    $stmtH->execute();
    $stmtH->close();
  }
  echo "<script>window.location.href='?view=datapinjamstudio';</script>";
  exit;
}

// ================== FILTER (GET) ==================
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$status_f = isset($_GET['status']) ? trim($_GET['status']) : '';
$dari = isset($_GET['dari']) ? trim($_GET['dari']) : '';
$sampai = isset($_GET['sampai']) ? trim($_GET['sampai']) : '';
$id_studio_f = isset($_GET['id_studio']) ? trim($_GET['id_studio']) : '';
$id_kelas_f = isset($_GET['id_kelas']) ? trim($_GET['id_kelas']) : '';
$id_user_f = isset($_GET['id_user']) ? trim($_GET['id_user']) : '';

// ================== DROPDOWN OPTIONS ==================
$studio_opts = [];
$kelas_opts = [];
$user_opts = [];

$res = $conn->query("SELECT id_studio, jenis_studio FROM studio ORDER BY jenis_studio ASC");
while ($r = $res->fetch_assoc()) {
  $studio_opts[] = $r;
}
$res->free();

$res = $conn->query("SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas ASC");
while ($r = $res->fetch_assoc()) {
  $kelas_opts[] = $r;
}
$res->free();

$res = $conn->query("SELECT id, nama_lengkap FROM user ORDER BY nama_lengkap ASC");
while ($r = $res->fetch_assoc()) {
  $user_opts[] = $r;
}
$res->free();

// ================== QUERY DATA ==================
$sql = "SELECT 
          ps.*,
          u.nama_lengkap,
          s.jenis_studio,
          kls.nama_kelas
        FROM pinjamstudio ps
        INNER JOIN user   u   ON u.id = ps.id_user
        INNER JOIN studio s   ON s.id_studio = ps.id_studio
        INNER JOIN kelas  kls ON kls.id_kelas = ps.id_kelas
        WHERE 1=1";

$bind_types = '';
$bind_vals = [];
$bind_refs = [];

if ($keyword !== '') {
  $sql .= " AND (ps.nama LIKE ? OR u.nama_lengkap LIKE ? OR s.jenis_studio LIKE ? OR kls.nama_kelas LIKE ?)";
  $like = "%{$keyword}%";
  $bind_types .= "ssss";
  $bind_vals[] = &$like;
  $bind_vals[] = &$like;
  $bind_vals[] = &$like;
  $bind_vals[] = &$like;
}

if ($status_f !== '') {
  $sql .= " AND ps.status = ?";
  $bind_types .= "s";
  $bind_vals[] = &$status_f;
}

if ($dari !== '') {
  $sql .= " AND ps.tgl_mulai >= ?";
  $bind_types .= "s";
  $bind_vals[] = &$dari;
}

if ($sampai !== '') {
  $sql .= " AND ps.tgl_mulai <= ?";
  $bind_types .= "s";
  $bind_vals[] = &$sampai;
}

if ($id_studio_f !== '') {
  $sql .= " AND ps.id_studio = ?";
  $bind_types .= "s";
  $bind_vals[] = &$id_studio_f;
}

if ($id_kelas_f !== '') {
  $sql .= " AND ps.id_kelas = ?";
  $bind_types .= "s";
  $bind_vals[] = &$id_kelas_f;
}

if ($id_user_f !== '') {
  $sql .= " AND ps.id_user = ?";
  $bind_types .= "s";
  $bind_vals[] = &$id_user_f;
}

$sql .= " ORDER BY ps.tgl_mulai DESC, ps.waktu_mulai DESC";

$stmt = $conn->prepare($sql);
if ($bind_types !== '') {
  $bind_refs[] = &$bind_types;
  foreach ($bind_vals as &$v) {
    $bind_refs[] = &$v;
  }
  call_user_func_array([$stmt, 'bind_param'], $bind_refs);
}
$stmt->execute();
$result = $stmt->get_result();

$export_qs = $_GET;
unset($export_qs['view']);
$qs_str = http_build_query($export_qs);
?>

<style>
  .page-inner .studio-content {
    all: revert !important;
  }

  .studio-content,
  .studio-content * {
    box-sizing: border-box !important;
  }

  .studio-content .card,
  .studio-content .card-header,
  .studio-content .card-body,
  .studio-content .row {
    margin: revert !important;
    padding: revert !important;
  }

  .studio-content {
    --success: #22c55e;
    --danger: #ef4444;
    --warning: #f59e0b;
    --info: #3b82f6;
    --success-soft: #e9f8ef;
    --warning-soft: #fff7e6;
    --muted: #6b7280;
    --txt: #1f2937;
    --card: #fff;
    --shadow: 0 6px 16px rgba(0, 0, 0, .06);
    --border: #e5e7eb;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  }

  .studio-content .card {
    background: var(--card) !important;
    border: 1px solid var(--border) !important;
    border-radius: 10px !important;
    box-shadow: var(--shadow) !important;
    margin-bottom: 18px !important;
    overflow: hidden !important;
  }

  .studio-content .card-header {
    padding: 14px 18px !important;
    background: #f9fafb !important;
    border-bottom: 1px solid var(--border) !important;
  }

  .studio-content .card-header h4 {
    font-size: 1rem !important;
    font-weight: 700 !important;
    color: var(--txt) !important;
    margin: 0 0 10px 0 !important;
  }

  .studio-content .card-body {
    padding: 16px !important;
  }

  .studio-content .filter-bar {
    padding: 12px 18px !important;
    background: #fff !important;
    border-bottom: 1px solid var(--border) !important;
  }

  .studio-content .filter-row {
    display: grid !important;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)) !important;
    gap: 10px !important;
    margin-bottom: 10px !important;
  }

  .studio-content .filter-row .form-control,
  .studio-content .filter-row select {
    padding: 6px 10px !important;
    border: 1px solid var(--border) !important;
    border-radius: 6px !important;
    font-size: 0.8rem !important;
    width: 100% !important;
  }

  .studio-content .filter-actions {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 8px !important;
  }

  .studio-content .export-buttons {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 8px !important;
    margin-top: 10px !important;
  }

  .studio-content .table-wrapper {
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch !important;
    margin-top: 12px !important;
  }

  .studio-content .table {
    width: 100% !important;
    border-collapse: separate !important;
    border-spacing: 0 8px !important;
  }

  .studio-content .table thead th {
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

  .studio-content .table tbody td {
    padding: 12px 14px !important;
    vertical-align: middle !important;
    color: var(--txt) !important;
    font-size: 0.82rem !important;
    text-align: center !important;
    line-height: 1.4 !important;
    border-bottom: 1px solid var(--border) !important;
  }

  .studio-content .table tbody tr {
    background: var(--card) !important;
    box-shadow: var(--shadow) !important;
    transition: background 0.15s ease !important;
  }

  .studio-content .table tbody tr:hover {
    background: #f9fafb !important;
  }

  .studio-content .table tbody tr:last-child td {
    border-bottom: none !important;
  }

  .studio-content .row-pending {
    background: var(--warning-soft) !important;
  }

  .studio-content .row-approve {
    background: var(--success-soft) !important;
  }

  .studio-content .user-cell {
    display: flex !important;
    flex-direction: column !important;
    gap: 2px !important;
    align-items: center !important;
  }

  .studio-content .user-name {
    font-weight: 600 !important;
    font-size: 0.85rem !important;
  }

  .studio-content .user-fullname {
    font-size: 0.75rem !important;
    color: var(--muted) !important;
  }

  .studio-content .badge {
    display: inline-block !important;
    padding: 4px 10px !important;
    border-radius: 999px !important;
    font-weight: 700 !important;
    font-size: 0.7rem !important;
  }

  .studio-content .badge-success {
    background: var(--success-soft) !important;
    color: #065f46 !important;
    border: 1px solid #86efac !important;
  }

  .studio-content .badge-danger {
    background: #fee2e2 !important;
    color: #991b1b !important;
    border: 1px solid #fca5a5 !important;
  }

  .studio-content .badge-warning {
    background: var(--warning-soft) !important;
    color: #b45309 !important;
    border: 1px solid #fbbf24 !important;
  }

  .studio-content .badge-info {
    background: #cffafe !important;
    color: #0e7490 !important;
    border: 1px solid #67e8f9 !important;
  }

  .studio-content .badge-rutin {
    display: inline-block !important;
    padding: 4px 8px !important;
    border-radius: 4px !important;
    font-size: 0.7rem !important;
    font-weight: 500 !important;
    white-space: normal !important;
    text-align: center !important;
    line-height: 1.3 !important;
    max-width: 100px !important;
  }

  .studio-content .badge-rutin.yes {
    background: #e0f2fe !important;
    color: #0369a1 !important;
    border: 1px solid #bae6fd !important;
  }

  .studio-content .badge-rutin.no {
    background: #f3f4f6 !important;
    color: var(--muted) !important;
    border: 1px solid var(--border) !important;
  }

  .studio-content .tujuan-badge {
    display: inline-block !important;
    padding: 4px 8px !important;
    border-radius: 4px !important;
    font-size: 0.72rem !important;
    border-left: 3px solid #f59e0b !important;
    font-weight: 500 !important;
    background: #fef3c7 !important;
    color: #b45309 !important;
    white-space: nowrap !important;
    max-width: 150px !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
  }

  .studio-content .btn {
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

  .studio-content .btn:hover {
    transform: translateY(-2px) !important;
    box-shadow: var(--shadow) !important;
    text-decoration: none !important;
  }

  .studio-content .btn-icon {
    width: 32px !important;
    height: 32px !important;
    padding: 0 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    border-radius: 4px !important;
    font-size: 0.9rem !important;
  }

  .studio-content .btn-sm {
    padding: 5px 10px !important;
    font-size: 0.75rem !important;
  }

  .studio-content .btn-primary {
    background: var(--info) !important;
    color: white !important;
  }

  .studio-content .btn-primary:hover {
    background: #2563eb !important;
  }

  .studio-content .btn-success {
    background: var(--success) !important;
    color: white !important;
  }

  .studio-content .btn-success:hover {
    background: #16a34a !important;
  }

  .studio-content .btn-danger {
    background: var(--danger) !important;
    color: white !important;
  }

  .studio-content .btn-danger:hover {
    background: #dc2626 !important;
  }

  .studio-content .btn-warning {
    background: var(--warning) !important;
    color: white !important;
  }

  .studio-content .btn-warning:hover {
    background: #d97706 !important;
  }

  .studio-content .btn-info {
    background: #06b6d4 !important;
    color: white !important;
  }

  .studio-content .btn-info:hover {
    background: #0891b2 !important;
  }

  .studio-content .btn-secondary {
    background: var(--muted) !important;
    color: white !important;
  }

  .studio-content .btn-secondary:hover {
    background: #4b5563 !important;
  }

  .studio-content .btn-outline-primary {
    background: transparent !important;
    color: var(--info) !important;
    border: 1px solid var(--info) !important;
  }

  .studio-content .btn-outline-primary:hover {
    background: var(--info) !important;
    color: white !important;
  }

  .studio-content .action-buttons {
    display: flex !important;
    flex-direction: column !important;
    gap: 5px !important;
    align-items: center !important;
    justify-content: center !important;
  }

  .studio-content .jadwal-flat {
    display: flex !important;
    flex-direction: column !important;
    gap: 6px !important;
    align-items: center !important;
  }

  .studio-content .jadwal-row {
    display: flex !important;
    align-items: center !important;
    gap: 6px !important;
    justify-content: center !important;
    font-size: 0.82rem !important;
    font-weight: 500 !important;
    white-space: nowrap !important;
  }

  .studio-content .jadwal-row span {
    white-space: nowrap !important;
  }

  .studio-content .jadwal-arrow {
    color: var(--muted) !important;
    font-size: 0.75rem !important;
    font-weight: bold !important;
    flex-shrink: 0 !important;
  }

  .studio-content .footer {
    margin-top: 20px !important;
    padding: 14px 0 !important;
    color: var(--muted) !important;
    font-size: 0.8rem !important;
    text-align: center !important;
  }

  .studio-content .empty-state {
    text-align: center !important;
    padding: 40px 20px !important;
    color: var(--muted) !important;
  }

  .studio-content .empty-state i {
    font-size: 2.5rem !important;
    opacity: 0.2 !important;
    display: block !important;
    margin-bottom: 14px !important;
  }

  .studio-content .d-flex {
    display: flex !important;
  }

  .studio-content .flex-wrap {
    flex-wrap: wrap !important;
  }

  .studio-content .gap-2 {
    gap: 8px !important;
  }

  @media (max-width: 768px) {
    .studio-content .card-header h4 {
      font-size: 0.95rem !important;
    }

    .studio-content .filter-row {
      grid-template-columns: 1fr !important;
    }

    .studio-content .filter-actions,
    .studio-content .export-buttons {
      flex-direction: column !important;
    }

    .studio-content .filter-actions .btn,
    .studio-content .export-buttons .btn {
      width: 100% !important;
    }

    .studio-content .table-wrapper {
      overflow-x: visible !important;
    }

    .studio-content .table {
      border-spacing: 0 !important;
    }

    .studio-content .table thead {
      display: none !important;
    }

    .studio-content .table tbody tr {
      display: block !important;
      margin-bottom: 16px !important;
      border: 1px solid var(--border) !important;
      border-radius: 8px !important;
      overflow: hidden !important;
      box-shadow: var(--shadow) !important;
    }

    .studio-content .table tbody td {
      display: flex !important;
      justify-content: space-between !important;
      align-items: center !important;
      padding: 12px 14px !important;
      border-bottom: 1px solid var(--border) !important;
      text-align: left !important;
    }

    .studio-content .table tbody td:last-child {
      border-bottom: none !important;
      justify-content: center !important;
    }

    .studio-content .table tbody td::before {
      content: attr(data-label) !important;
      font-weight: 600 !important;
      color: var(--muted) !important;
      font-size: 0.7rem !important;
      text-transform: uppercase !important;
      margin-right: 10px !important;
    }

    .studio-content .table tbody td:last-child::before {
      display: none !important;
    }

    .studio-content .action-buttons {
      width: 100% !important;
    }

    .studio-content .jadwal-flat,
    .studio-content .user-cell {
      align-items: flex-start !important;
    }
  }
</style>

<div class="studio-content">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Data Peminjaman Studio</h4>
    </div>

    <!-- FILTER BAR -->
    <div class="filter-bar">
      <form method="GET" class="filter-form">
        <input type="hidden" name="view" value="datapinjamstudio">
        <div class="filter-row">
          <div>
            <input type="text" name="q" class="form-control" placeholder="Cari nama / studio / kelas..."
              value="<?php echo e($keyword); ?>">
          </div>
          <div>
            <select name="status" class="form-control">
              <option value="">- Semua Status -</option>
              <option value="menunggu" <?php if ($status_f === 'menunggu')
                echo 'selected'; ?>>Menunggu</option>
              <option value="approve" <?php if ($status_f === 'approve')
                echo 'selected'; ?>>Approve</option>
              <option value="selesai" <?php if ($status_f === 'selesai')
                echo 'selected'; ?>>Selesai</option>
              <option value="ditolak" <?php if ($status_f === 'ditolak')
                echo 'selected'; ?>>Ditolak</option>
            </select>
          </div>
          <div>
            <input type="date" name="dari" class="form-control" value="<?php echo e($dari); ?>" placeholder="Dari">
          </div>
          <div>
            <input type="date" name="sampai" class="form-control" value="<?php echo e($sampai); ?>"
              placeholder="Sampai">
          </div>
          <div>
            <select name="id_studio" class="form-control">
              <option value="">- Semua Studio -</option>
              <?php foreach ($studio_opts as $opt): ?>
                <option value="<?php echo e($opt['id_studio']); ?>" <?php if ($id_studio_f == $opt['id_studio'])
                     echo 'selected'; ?>>
                  <?php echo e($opt['jenis_studio']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <select name="id_kelas" class="form-control">
              <option value="">- Semua Kelas -</option>
              <?php foreach ($kelas_opts as $opt): ?>
                <option value="<?php echo e($opt['id_kelas']); ?>" <?php if ($id_kelas_f == $opt['id_kelas'])
                     echo 'selected'; ?>>
                  <?php echo e($opt['nama_kelas']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <select name="id_user" class="form-control">
              <option value="">- Semua User -</option>
              <?php foreach ($user_opts as $opt): ?>
                <option value="<?php echo e($opt['id']); ?>" <?php if ($id_user_f == $opt['id'])
                     echo 'selected'; ?>>
                  <?php echo e($opt['nama_lengkap']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="filter-actions">
          <button type="submit" class="btn btn-sm btn-primary">
            <i class="fa fa-search"></i> Terapkan Filter
          </button>
          <a href="?view=datapinjamstudio" class="btn btn-sm btn-outline-primary">
            <i class="fa fa-refresh"></i> Reset
          </a>

          <a href="peminjaman/export_pinjamstudio_pdf.php<?php echo $qs_str ? '?' . e($qs_str) : ''; ?>"
            target="_blank" class="btn btn-sm btn-outline-primary">
            <i class="fa fa-file-pdf-o"></i> Export PDF
          </a>
          <a href="peminjaman/export_pinjamstudio_excel.php<?php echo $qs_str ? '?' . e($qs_str) : ''; ?>"
            target="_blank" class="btn btn-sm btn-primary">
            <i class="fa fa-file-excel-o"></i> Export Excel
          </a>
        </div>
      </form>
    </div>

    <div class="card-body">
      <div class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>No</th>
              <th>Peminjam</th>
              <th>Kelas</th>
              <th>Studio</th>
              <th>Jadwal</th>
              <th>Tujuan</th>
              <th>Rutin</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if ($result->num_rows > 0):
              $no = 1;
              while ($row = $result->fetch_assoc()):
                $tgl_mulai_raw = $row['tgl_mulai'] ?? '';
                $tgl_selesai_raw = $row['tgl_selesai'] ?? '';

                $tgl_mulai = fmt_tgl($tgl_mulai_raw);
                $tgl_selesai = ($tgl_selesai_raw && $tgl_selesai_raw !== '0000-00-00')
                  ? fmt_tgl($tgl_selesai_raw)
                  : null;

                $waktu_mulai = fmt_waktu($row['waktu_mulai'] ?? '');
                $waktu_selesai = fmt_waktu($row['waktu_selesai'] ?? '');

                $is_recurring = strtolower(trim($row['is_recurring'] ?? 'no')) === 'yes';
                $rec_nums = parseRecurringDaysStudio($row['recurring_days'] ?? '');
                $rec_names = mapHariByNumberStudio($rec_nums);

                $status_lc = strtolower(trim($row['status']));
                $rowClass = '';
                if ($status_lc === 'menunggu') {
                  $rowClass = 'row-pending';
                } elseif ($status_lc === 'approve') {
                  $rowClass = 'row-approve';
                }

                $tujuan = $row['tujuan'] ?? '-';
            ?>
            <tr class="<?= $rowClass; ?>">
              <td data-label="No"><strong><?php echo $no++; ?></strong></td>
              <td data-label="Peminjam">
                <div class="user-cell">
                  <div class="user-name"><?php echo e($row['nama']); ?></div>
                  <div class="user-fullname"><?php echo e($row['nama_lengkap']); ?></div>
                </div>
              </td>
              <td data-label="Kelas"><?php echo e($row['nama_kelas']); ?></td>
              <td data-label="Studio"><?php echo e($row['jenis_studio']); ?></td>
              <td data-label="Jadwal">
                <div class="jadwal-flat">
                  <div class="jadwal-row">
                    <span><?php echo $tgl_mulai; ?></span>
                    <?php if ($tgl_selesai): ?>
                      <span class="jadwal-arrow">→</span>
                      <span><?php echo $tgl_selesai; ?></span>
                    <?php endif; ?>
                  </div>
                  <div class="jadwal-row">
                    <span><?php echo $waktu_mulai; ?></span>
                    <?php if ($waktu_selesai && $waktu_selesai !== $waktu_mulai): ?>
                      <span class="jadwal-arrow">→</span>
                      <span><?php echo $waktu_selesai; ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </td>

              <td data-label="Tujuan">
                <?php if ($tujuan !== '-' && !empty($tujuan)): ?>
                  <span class="tujuan-badge" title="<?= e($tujuan); ?>">
                    <i class="fa fa-bullseye"></i> <?= e($tujuan); ?>
                  </span>
                <?php else: ?>
                  <span style="color: var(--muted);">-</span>
                <?php endif; ?>
              </td>

              <td data-label="Rutin">
                <?php if ($is_recurring && !empty($rec_names)): ?>
                  <span class="badge-rutin yes">
                    <?php echo e(implode(', ', $rec_names)); ?>
                  </span>
                <?php else: ?>
                  <span class="badge-rutin no">Tidak</span>
                <?php endif; ?>
              </td>

              <td data-label="Status">
                <?php if ($row['status'] === 'menunggu'): ?>
                  <span class="badge badge-warning">Menunggu</span>
                <?php elseif ($row['status'] === 'approve'): ?>
                  <span class="badge badge-success">Disetujui</span>
                <?php elseif ($row['status'] === 'selesai'): ?>
                  <span class="badge badge-info">Selesai</span>
                <?php else: ?>
                  <span class="badge badge-danger"><?php echo ucfirst(e($row['status'])); ?></span>
                <?php endif; ?>
              </td>
              <td data-label="Aksi">
                <div class="action-buttons">
                  <a href="?view=detailpinjamstudio&id=<?php echo e($row['id_pinjamstudio']); ?>"
                    class="btn btn-primary btn-icon" title="Detail">
                    <i class="fa fa-eye"></i>
                  </a>

                  <?php if ($row['status'] === 'menunggu'): ?>
                    <a href="?view=datapinjamstudio&id_approve=<?php echo e($row['id_pinjamstudio']); ?>"
                      class="btn btn-success btn-icon" 
                      title="Setujui"
                      onclick="return confirm('✅ SETUJUI PEMINJAMAN\n\nYakin ingin menyetujui?\n\n👤 <?= e($row['nama']); ?>\n🎙️ <?= e($row['jenis_studio']); ?>\n📅 <?= $tgl_mulai; ?> (<?= $waktu_mulai; ?> - <?= $waktu_selesai; ?>)')">
                      <i class="fa fa-check"></i>
                    </a>
                  <?php endif; ?>

                  <?php if ($row['status'] === 'approve'): ?>
                    <a href="?view=datapinjamstudio&id_batal=<?php echo e($row['id_pinjamstudio']); ?>"
                      class="btn btn-warning btn-icon" 
                      title="Batal Approve"
                      onclick="return confirm('↩️ BATAL PERSETUJUAN\n\nYakin membatalkan persetujuan?\nStatus akan kembali ke \"Menunggu\"\n\n👤 <?= e($row['nama']); ?>\n🎙️ <?= e($row['jenis_studio']); ?>')">
                      <i class="fa fa-undo"></i>
                    </a>
                  <?php endif; ?>

                  <a href="?view=datapinjamstudio&id_hapus=<?php echo e($row['id_pinjamstudio']); ?>"
                     class="btn btn-danger btn-icon" 
                     title="Hapus"
                     onclick="return confirm('🗑️ HAPUS PERMANENT 🗑️\n\n⚠️ Data akan hilang selamanya!\n\n👤 Peminjam: <?= e($row['nama']); ?>\n🎙️ Studio: <?= e($row['jenis_studio']); ?>\n📅 <?= $tgl_mulai; ?> (<?= $waktu_mulai; ?> → <?= $waktu_selesai; ?>)\n<?= ($tujuan !== '-' && !empty($tujuan)) ? '🎯 Tujuan: ' . e($tujuan) : ''; ?>\n\nYAKIN HAPUS?')">
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
              <td colspan="9">
                <div class="empty-state">
                  <i class="fa fa-inbox"></i>
                  <div>Belum ada data peminjaman studio</div>
                </div>
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="footer">
    <strong>SIPINJAM</strong> &copy; 2025
  </div>
</div>

<?php
$stmt->close();
?>
