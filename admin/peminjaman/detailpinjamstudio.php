<?php
include '../koneksi.php';

// ========== SECURITY: Prepared Statement untuk Prevent SQL Injection ==========
$id = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($id)) {
    echo "<div style='padding:20px; background:#fee2e2; border:1px solid #fca5a5; border-radius:8px; color:#991b1b; margin:20px;'>
            <strong>⚠️ Error:</strong> ID Peminjaman tidak ditemukan.
          </div>";
    echo "<a href='javascript:window.history.back()' style='display:inline-block; margin:20px; padding:10px 20px; background:#6b7280; color:white; border-radius:8px; text-decoration:none;'>← Kembali</a>";
    exit;
}

$sql = "SELECT 
          p.id_pinjamkolam,
          p.id_user,
          p.id_kolam,
          p.id_kelas,
          p.tgl_mulai,
          p.waktu_mulai,
          p.waktu_selesai,
          p.status,
          u.nama_lengkap,
          u.nama AS pic_nama,
          u.no_hp,
          k.jenis_kolam,
          kl.nama_kelas
        FROM pinjamkolam p
        INNER JOIN user u ON u.id = p.id_user
        INNER JOIN kolam k ON k.id_kolam = p.id_kolam
        INNER JOIN kelas kl ON kl.id_kelas = p.id_kelas
        WHERE p.id_pinjamkolam = ? LIMIT 1";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("<div style='padding:20px; background:#fee2e2; color:#991b1b;'><strong>Database Error:</strong> " . htmlspecialchars($conn->error) . "</div>");
}

$stmt->bind_param("s", $id);

if (!$stmt->execute()) {
    die("<div style='padding:20px; background:#fee2e2; color:#991b1b;'><strong>Query Error:</strong> " . htmlspecialchars($stmt->error) . "</div>");
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<div style='padding:20px; background:#fee2e2; color:#991b1b;'><strong>Data tidak ditemukan</strong></div>";
    echo "<a href='javascript:window.history.back()'>← Kembali</a>";
    exit;
}

$d = $result->fetch_assoc();
$stmt->close();

// Helper function
function safe($v) {
    return htmlspecialchars((string)$v ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!-- Reset CSS untuk Konten yang Di-include -->
<style>
/* ========== RESET INHERITANCE ========== */
.page-inner .kolam-detail-content {
  all: revert !important;
}

.kolam-detail-content,
.kolam-detail-content * {
  box-sizing: border-box !important;
}

.kolam-detail-content .page-header,
.kolam-detail-content .card,
.kolam-detail-content .card-header,
.kolam-detail-content .card-body,
.kolam-detail-content .row {
  margin: revert !important;
  padding: revert !important;
}

/* ========== CUSTOM PROPERTIES ========== */
.kolam-detail-content {
  --kd-primary: #3b82f6;
  --kd-success: #22c55e;
  --kd-text: #1f2937;
  --kd-muted: #6b7280;
  --kd-border: #e5e7eb;
  --kd-bg: #f9fafb;
  --kd-card: #ffffff;
  --kd-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

/* ========== LAYOUT ========== */
.kolam-detail-content .page-header {
  display: flex !important;
  justify-content: space-between !important;
  align-items: flex-start !important;
  margin-bottom: 25px !important;
  padding-bottom: 15px !important;
  border-bottom: 2px solid var(--kd-border) !important;
}

.kolam-detail-content .page-header .page-title {
  font-size: 1.75rem !important;
  font-weight: 700 !important;
  color: var(--kd-text) !important;
  margin: 0 !important;
}

.kolam-detail-content .breadcrumbs {
  list-style: none !important;
  padding: 0 !important;
  margin: 0 !important;
  display: flex !important;
  gap: 8px !important;
  align-items: center !important;
  font-size: 0.9rem !important;
  color: var(--kd-muted) !important;
}

.kolam-detail-content .breadcrumbs li {
  display: flex !important;
  align-items: center !important;
}

.kolam-detail-content .breadcrumbs li.separator {
  margin: 0 5px !important;
}

.kolam-detail-content .breadcrumbs a {
  color: var(--kd-primary) !important;
  text-decoration: none !important;
}

.kolam-detail-content .breadcrumbs a:hover {
  text-decoration: underline !important;
}

.kolam-detail-content .row {
  display: flex !important;
  flex-wrap: wrap !important;
  margin: 0 -15px !important;
}

.kolam-detail-content .col-md-12 {
  flex: 0 0 100% !important;
  max-width: 100% !important;
  padding: 0 15px !important;
}

/* ========== CARD ========== */
.kolam-detail-content .card {
  background: var(--kd-card) !important;
  border: 1px solid var(--kd-border) !important;
  border-radius: 12px !important;
  box-shadow: var(--kd-shadow) !important;
  margin-bottom: 20px !important;
  overflow: hidden !important;
}

.kolam-detail-content .card-header {
  padding: 20px !important;
  background: linear-gradient(135deg, var(--kd-primary) 0%, #2563eb 100%) !important;
  color: white !important;
  border-bottom: none !important;
}

.kolam-detail-content .card-header .card-title {
  font-size: 1.1rem !important;
  font-weight: 700 !important;
  margin: 0 !important;
  color: white !important;
}

.kolam-detail-content .card-body {
  padding: 24px !important;
}

.kolam-detail-content .d-flex {
  display: flex !important;
}

.kolam-detail-content .align-items-center {
  align-items: center !important;
}

/* ========== TABLE ========== */
.kolam-detail-content .table-responsive {
  overflow-x: auto !important;
  -webkit-overflow-scrolling: touch !important;
}

.kolam-detail-content .table {
  width: 100% !important;
  margin-bottom: 0 !important;
  border-collapse: collapse !important;
}

.kolam-detail-content .table tr {
  border-bottom: 1px solid var(--kd-border) !important;
}

.kolam-detail-content .table tr:last-child {
  border-bottom: none !important;
}

.kolam-detail-content .table td {
  padding: 14px 12px !important;
  vertical-align: middle !important;
  font-size: 0.9rem !important;
  color: var(--kd-text) !important;
}

.kolam-detail-content .table td:first-child {
  font-weight: 600 !important;
  color: var(--kd-muted) !important;
  width: 180px !important;
}

.kolam-detail-content .table td:nth-child(2) {
  width: 20px !important;
  text-align: center !important;
  color: var(--kd-muted) !important;
}

.kolam-detail-content .table td:nth-child(3) {
  font-weight: 500 !important;
}

/* ========== BUTTON ========== */
.kolam-detail-content .btn {
  display: inline-flex !important;
  align-items: center !important;
  gap: 8px !important;
  padding: 10px 20px !important;
  font-size: 0.9rem !important;
  font-weight: 600 !important;
  border: none !important;
  border-radius: 8px !important;
  text-decoration: none !important;
  cursor: pointer !important;
  transition: all 0.2s ease !important;
  margin-top: 10px !important;
}

.kolam-detail-content .btn-xl {
  padding: 12px 24px !important;
  font-size: 1rem !important;
}

.kolam-detail-content .btn-success {
  background: var(--kd-success) !important;
  color: white !important;
}

.kolam-detail-content .btn-success:hover {
  background: #16a34a !important;
  transform: translateY(-2px) !important;
  box-shadow: 0 4px 12px rgba(34,197,94,0.3) !important;
}

/* ========== FOOTER ========== */
.kolam-detail-content center {
  margin-top: 30px !important;
  padding: 20px 0 !important;
  color: var(--kd-muted) !important;
  font-size: 0.9rem !important;
}

/* ========== RESPONSIVE ========== */
@media (max-width: 768px) {
  .kolam-detail-content .page-header {
    flex-direction: column !important;
    gap: 10px !important;
  }

  .kolam-detail-content .table td:first-child {
    width: 120px !important;
  }

  .kolam-detail-content .btn-xl {
    width: 100% !important;
    justify-content: center !important;
  }
}
</style>

<!-- Wrapper untuk Isolasi CSS -->
<div class="kolam-detail-content">
  <div class="page-header">
    <h4 class="page-title">Data</h4>
    <ul class="breadcrumbs">
      <li class="nav-home">
        <a href="#">
          <i class="flaticon-home"></i>
        </a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="#">Pinjam</a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="#">Kolam</a>
      </li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <div class="d-flex align-items-center">
            <h4 class="card-title">Detail Pinjam Kolam</h4>
          </div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table">
              <tr>
                <td>Nama Peminjam</td>
                <td>:</td>
                <td><?= safe($d['nama_lengkap']) ?></td>
              </tr>
              <tr>
                <td>Nama PIC</td>
                <td>:</td>
                <td><?= safe($d['pic_nama']) ?></td>
              </tr>
              <tr>
                <td>No. WA PIC</td>
                <td>:</td>
                <td><?= safe($d['no_hp']) ?></td>
              </tr>
              <tr>
                <td>Kelas</td>
                <td>:</td>
                <td><?= safe($d['nama_kelas']) ?></td>
              </tr>
              <tr>
                <td>Nama Kolam</td>
                <td>:</td>
                <td><?= safe($d['jenis_kolam']) ?></td>
              </tr>
              <tr>
                <td>Tgl Mulai</td>
                <td>:</td>
                <td><?= safe($d['tgl_mulai']) ?></td>
              </tr>
              <tr>
                <td>Waktu Mulai</td>
                <td>:</td>
                <td><?= safe($d['waktu_mulai']) ?></td>
              </tr>
              <tr>
                <td>Waktu Selesai</td>
                <td>:</td>
                <td><?= safe($d['waktu_selesai']) ?></td>
              </tr>
              <tr>
                <td>Status</td>
                <td>:</td>
                <td>
                  <span style="display:inline-block; padding:6px 12px; border-radius:6px; font-weight:600; font-size:0.85rem; <?= strtolower($d['status']) === 'menunggu' ? 'background:#fff7e6; color:#b45309; border:1px solid #fbbf24;' : 'background:#e9f8ef; color:#065f46; border:1px solid #86efac;' ?>">
                    <?= safe(ucfirst($d['status'])) ?>
                  </span>
                </td>
              </tr>
            </table>
          </div>
        </div>
      </div>

      <a href="./peminjaman/cetakdetail.php?id=<?= safe($d['id_pinjamkolam']) ?>" target="_blank" rel="noopener noreferrer" title="Print Detail" class="btn btn-xl btn-success">
        <i class="fa fa-print"></i> Print Detail
      </a>
    </div>
  </div>

  <center>
    <h6><b>&copy; Copyright@2025 | SIPINJAM |</b></h6>
  </center>
</div>
