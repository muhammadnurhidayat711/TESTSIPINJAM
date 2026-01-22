<?php
// ========================================
// DETAIL PEMINJAMAN GEDUNG - MODERN MINIMALIST
// ========================================

// Helper Functions
function fmt_tgl($ymd) {
    if(!$ymd || $ymd === '0000-00-00') return '-';
    $parts = explode('-', $ymd);
    if(count($parts) !== 3) return htmlspecialchars($ymd);
    [$y, $m, $d] = $parts;
    $bulan = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", 
              "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
    $month_index = (int)$m;
    if($month_index < 1 || $month_index > 12) return htmlspecialchars($ymd);
    return sprintf("%d %s %s", (int)$d, $bulan[$month_index], $y);
}

function fmt_waktu($hms) {
    if(!$hms) return '-';
    return substr($hms, 0, 5) . ' WIB';
}

function getStatusBadge($status) {
    $status_lower = strtolower(trim($status));
    switch ($status_lower) {
        case 'approve':
        case 'disetujui':
            return '<span class="status-badge success"><i class="fa fa-check-circle"></i> Disetujui</span>';
        case 'menunggu':
        case 'pending':
            return '<span class="status-badge warning"><i class="fa fa-clock"></i> Menunggu</span>';
        case 'selesai':
        case 'completed':
            return '<span class="status-badge info"><i class="fa fa-flag-checkered"></i> Selesai</span>';
        case 'ditolak':
        case 'reject':
            return '<span class="status-badge danger"><i class="fa fa-times-circle"></i> Ditolak</span>';
        default:
            return '<span class="status-badge">' . ucfirst(htmlspecialchars($status)) . '</span>';
    }
}

// Validasi ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('ID tidak valid'); window.location.href='?view=datapinjambarang';</script>";
    exit;
}

$id_pinjam = (int)$_GET['id'];

// Prepared statement untuk keamanan
$stmt = $conn->prepare("SELECT 
    pb.*,
    u.nama_lengkap,
    b.nama_barang,
    b.stok,
    b.deskripsi as gedung_deskripsi,
    b.foto as gedung_foto
FROM pinjambarang pb
INNER JOIN user u ON u.id = pb.id_user
INNER JOIN barang b ON b.id = pb.id_barang
WHERE pb.id_pinjam = ?
LIMIT 1");

if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    echo "<script>alert('Terjadi kesalahan sistem'); window.location.href='?view=datapinjambarang';</script>";
    exit;
}

$stmt->bind_param("i", $id_pinjam);

if(!$stmt->execute()) {
    error_log("Execute failed: " . $stmt->error);
    echo "<script>alert('Gagal mengambil data'); window.location.href='?view=datapinjambarang';</script>";
    exit;
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('Data tidak ditemukan'); window.location.href='?view=datapinjambarang';</script>";
    exit;
}

$d = $result->fetch_assoc();
$stmt->close();
?>

<style>
/* ========================================
   MODERN MINIMALIST DETAIL - GEDUNG
   ======================================== */

.detail-gedung {
  --primary: #0ea5e9;
  --success: #10b981;
  --warning: #f59e0b;
  --danger: #ef4444;
  --info: #06b6d4;
  --muted: #64748b;
  --txt: #0f172a;
  --bg: #f8fafc;
  --card: #ffffff;
  --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
  --shadow: 0 1px 3px rgba(0,0,0,0.1);
  --shadow-md: 0 4px 6px rgba(0,0,0,0.07);
  --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
  --border: #e2e8f0;
  --radius: 12px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  max-width: 1000px;
  margin: 0 auto;
  padding: 24px;
}

/* Header */
.detail-gedung .page-header {
  background: linear-gradient(135deg, var(--primary) 0%, #0284c7 100%);
  padding: 32px;
  border-radius: var(--radius);
  margin-bottom: 32px;
  box-shadow: var(--shadow-lg);
  color: white;
  position: relative;
  overflow: hidden;
}

.detail-gedung .page-header::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -10%;
  width: 300px;
  height: 300px;
  background: rgba(255,255,255,0.1);
  border-radius: 50%;
}

.detail-gedung .page-header h2 {
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0 0 8px 0;
  position: relative;
  z-index: 1;
}

.detail-gedung .page-header p {
  margin: 0;
  opacity: 0.9;
  font-size: 0.875rem;
  position: relative;
  z-index: 1;
}

/* Info Grid */
.detail-gedung .info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}

/* Info Card */
.detail-gedung .info-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 24px;
  box-shadow: var(--shadow-sm);
  transition: all 0.2s ease;
}

.detail-gedung .info-card:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}

.detail-gedung .info-card-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 2px solid var(--border);
}

.detail-gedung .info-card-icon {
  width: 44px;
  height: 44px;
  background: linear-gradient(135deg, var(--primary) 0%, #0284c7 100%);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.detail-gedung .info-card-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--txt);
  margin: 0;
}

/* Info Row */
.detail-gedung .info-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 12px 0;
  gap: 16px;
  border-bottom: 1px solid var(--border);
}

.detail-gedung .info-row:last-child {
  border-bottom: none;
}

.detail-gedung .info-label {
  font-size: 0.813rem;
  font-weight: 600;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 0.025em;
  flex-shrink: 0;
  min-width: 120px;
}

.detail-gedung .info-value {
  font-size: 0.938rem;
  font-weight: 500;
  color: var(--txt);
  text-align: right;
  word-break: break-word;
}

/* Facility Pills */
.detail-gedung .facility-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

.detail-gedung .facility-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: #e0f2fe;
  color: #0369a1;
  border-radius: 999px;
  font-size: 0.813rem;
  font-weight: 600;
  border: 1px solid rgba(3, 105, 161, 0.2);
}

.detail-gedung .facility-pill i {
  font-size: 0.875rem;
}

/* Status Badge */
.detail-gedung .status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  letter-spacing: 0.025em;
}

.detail-gedung .status-badge.success {
  background: #d1fae5;
  color: #065f46;
}

.detail-gedung .status-badge.warning {
  background: #fef3c7;
  color: #92400e;
}

.detail-gedung .status-badge.info {
  background: #cffafe;
  color: #0e7490;
}

.detail-gedung .status-badge.danger {
  background: #fee2e2;
  color: #991b1b;
}

/* Layout Image */
.detail-gedung .layout-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 24px;
  box-shadow: var(--shadow-sm);
}

.detail-gedung .layout-card h3 {
  font-size: 1rem;
  font-weight: 600;
  color: var(--txt);
  margin: 0 0 16px 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

.detail-gedung .layout-card img {
  width: 100%;
  height: auto;
  border-radius: 8px;
  box-shadow: var(--shadow);
  max-height: 500px;
  object-fit: contain;
}

/* Keterangan Box */
.detail-gedung .keterangan-box {
  background: var(--bg);
  border: 1px solid var(--border);
  border-left: 4px solid var(--primary);
  border-radius: 8px;
  padding: 16px;
  margin-top: 8px;
}

.detail-gedung .keterangan-box p {
  margin: 0;
  color: var(--txt);
  font-size: 0.875rem;
  line-height: 1.6;
}

/* Action Buttons */
.detail-gedung .action-bar {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  margin-top: 32px;
}

.detail-gedung .btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  font-size: 0.938rem;
  font-weight: 500;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.15s ease;
  white-space: nowrap;
}

.detail-gedung .btn:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
  text-decoration: none;
}

.detail-gedung .btn-primary {
  background: var(--primary);
  color: white;
}

.detail-gedung .btn-primary:hover {
  background: #0284c7;
}

.detail-gedung .btn-success {
  background: var(--success);
  color: white;
}

.detail-gedung .btn-success:hover {
  background: #059669;
}

.detail-gedung .btn-secondary {
  background: var(--card);
  color: var(--txt);
  border: 1px solid var(--border);
}

.detail-gedung .btn-secondary:hover {
  background: var(--bg);
}

/* Footer */
.detail-gedung .footer {
  margin-top: 48px;
  padding-top: 24px;
  text-align: center;
  color: var(--muted);
  font-size: 0.813rem;
  border-top: 1px solid var(--border);
}

/* Dark Mode */
body[data-theme="dark"] .detail-gedung {
  --txt: #f1f5f9;
  --bg: #0f172a;
  --card: #1e293b;
  --border: #334155;
  --muted: #94a3b8;
}

body[data-theme="dark"] .detail-gedung .facility-pill {
  background: rgba(14, 165, 233, 0.15);
  color: #67e8f9;
  border-color: rgba(14, 165, 233, 0.3);
}

body[data-theme="dark"] .detail-gedung .status-badge.success {
  background: rgba(16, 185, 129, 0.2);
  color: #6ee7b7;
}

body[data-theme="dark"] .detail-gedung .status-badge.warning {
  background: rgba(245, 158, 11, 0.2);
  color: #fbbf24;
}

body[data-theme="dark"] .detail-gedung .status-badge.info {
  background: rgba(6, 182, 212, 0.2);
  color: #67e8f9;
}

body[data-theme="dark"] .detail-gedung .status-badge.danger {
  background: rgba(239, 68, 68, 0.2);
  color: #fca5a5;
}

body[data-theme="dark"] .detail-gedung .keterangan-box {
  background: rgba(14, 165, 233, 0.1);
  border-left-color: var(--primary);
}

/* Responsive */
@media (max-width: 768px) {
  .detail-gedung {
    padding: 16px;
  }
  
  .detail-gedung .page-header {
    padding: 24px 20px;
  }
  
  .detail-gedung .page-header h2 {
    font-size: 1.25rem;
  }
  
  .detail-gedung .info-grid {
    grid-template-columns: 1fr;
  }
  
  .detail-gedung .info-card {
    padding: 20px;
  }
  
  .detail-gedung .info-row {
    flex-direction: column;
    gap: 4px;
  }
  
  .detail-gedung .info-label {
    min-width: auto;
  }
  
  .detail-gedung .info-value,
  .detail-gedung .facility-pills {
    text-align: left;
    justify-content: flex-start;
  }
  
  .detail-gedung .action-bar {
    flex-direction: column;
  }
  
  .detail-gedung .btn {
    width: 100%;
    justify-content: center;
  }
}

/* Print */
@media print {
  .detail-gedung .action-bar,
  .detail-gedung .footer {
    display: none;
  }
  
  .detail-gedung .page-header {
    background: #0ea5e9 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
}
</style>

<div class="detail-gedung">
  
  <!-- Header -->
  <div class="page-header">
    <h2><i class="fa fa-building"></i> Detail Peminjaman Gedung</h2>
    <p>ID Peminjaman: #<?php echo str_pad($id_pinjam, 5, '0', STR_PAD_LEFT); ?></p>
  </div>

  <!-- Info Grid -->
  <div class="info-grid">
    
    <!-- Informasi Peminjam -->
    <div class="info-card">
      <div class="info-card-header">
        <div class="info-card-icon">
          <i class="fa fa-user"></i>
        </div>
        <h3 class="info-card-title">Informasi Peminjam</h3>
      </div>
      <div class="info-row">
        <span class="info-label">Nama</span>
        <span class="info-value"><?php echo htmlspecialchars($d['nama_lengkap'] ?? '-'); ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">PIC</span>
        <span class="info-value"><?php echo htmlspecialchars($d['kett'] ?? '-'); ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">No. WhatsApp</span>
        <span class="info-value">
          <?php 
          $no_hp = htmlspecialchars($d['kettt'] ?? '-');
          if($no_hp !== '-') {
            echo '<a href="https://wa.me/' . preg_replace('/[^0-9]/', '', $no_hp) . '" target="_blank" style="color: var(--success); text-decoration: none;">';
            echo $no_hp . ' <i class="fa fa-whatsapp"></i>';
            echo '</a>';
          } else {
            echo $no_hp;
          }
          ?>
        </span>
      </div>
    </div>

    <!-- Informasi Gedung -->
    <div class="info-card">
      <div class="info-card-header">
        <div class="info-card-icon">
          <i class="fa fa-building"></i>
        </div>
        <h3 class="info-card-title">Informasi Gedung</h3>
      </div>
      <div class="info-row">
        <span class="info-label">Gedung</span>
        <span class="info-value"><strong><?php echo htmlspecialchars($d['nama_barang'] ?? '-'); ?></strong></span>
      </div>
      <?php if(!empty($d['stok'])) { ?>
      <div class="info-row">
        <span class="info-label">Lokasi</span>
        <span class="info-value"><?php echo htmlspecialchars($d['stok']); ?></span>
      </div>
      <?php } ?>
      <div class="info-row">
        <span class="info-label">Status</span>
        <span class="info-value">
          <?php echo getStatusBadge($d['status'] ?? 'menunggu'); ?>
        </span>
      </div>
    </div>

    <!-- Perlengkapan/Fasilitas -->
    <div class="info-card" style="grid-column: 1 / -1;">
      <div class="info-card-header">
        <div class="info-card-icon">
          <i class="fa fa-list"></i>
        </div>
        <h3 class="info-card-title">Perlengkapan yang Dipinjam</h3>
      </div>
      <div class="info-row">
        <span class="info-label">Fasilitas</span>
        <span class="info-value">
          <div class="facility-pills">
            <?php 
            // Meja
            if(!empty($d['meja']) && !empty($d['jumlah_meja'])) {
              echo '<span class="facility-pill"><i class="fa fa-table"></i> ' . htmlspecialchars($d['meja']) . ' (' . htmlspecialchars($d['jumlah_meja']) . ')</span>';
            }
            
            // Kursi
            if(!empty($d['kursi']) && !empty($d['jumlah_kursi'])) {
              echo '<span class="facility-pill"><i class="fa fa-chair"></i> ' . htmlspecialchars($d['kursi']) . ' (' . htmlspecialchars($d['jumlah_kursi']) . ')</span>';
            }
            
            // Sound
            if(!empty($d['sound']) && $d['sound'] !== '-') {
              echo '<span class="facility-pill"><i class="fa fa-volume-up"></i> ' . htmlspecialchars($d['sound']) . '</span>';
            }
            
            // Proyektor
            if(!empty($d['proyektor']) && $d['proyektor'] !== '-') {
              echo '<span class="facility-pill"><i class="fa fa-video"></i> ' . htmlspecialchars($d['proyektor']) . '</span>';
            }
            ?>
          </div>
        </span>
      </div>
    </div>

    <!-- Jadwal -->
    <div class="info-card" style="grid-column: 1 / -1;">
      <div class="info-card-header">
        <div class="info-card-icon">
          <i class="fa fa-calendar"></i>
        </div>
        <h3 class="info-card-title">Jadwal Peminjaman</h3>
      </div>
      <div class="info-row">
        <span class="info-label">Tanggal Mulai</span>
        <span class="info-value"><?php echo fmt_tgl($d['tgl_mulai'] ?? ''); ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">Waktu Mulai</span>
        <span class="info-value"><?php echo fmt_waktu($d['waktu_mulai'] ?? ''); ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">Tanggal Selesai</span>
        <span class="info-value"><?php echo fmt_tgl($d['tgl_selesai'] ?? ''); ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">Waktu Selesai</span>
        <span class="info-value"><?php echo fmt_waktu($d['waktu_selesai'] ?? ''); ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">Durasi</span>
        <span class="info-value">
          <?php 
          if(!empty($d['tgl_mulai']) && !empty($d['tgl_selesai'])) {
            $mulai = new DateTime($d['tgl_mulai'] . ' ' . $d['waktu_mulai']);
            $selesai = new DateTime($d['tgl_selesai'] . ' ' . $d['waktu_selesai']);
            $diff = $mulai->diff($selesai);
            
            $durasi = [];
            if($diff->d > 0) $durasi[] = $diff->d . ' hari';
            if($diff->h > 0) $durasi[] = $diff->h . ' jam';
            if($diff->i > 0) $durasi[] = $diff->i . ' menit';
            
            echo $durasi ? implode(' ', $durasi) : '-';
          } else {
            echo '-';
          }
          ?>
        </span>
      </div>
      <?php if(!empty($d['tujuan_barang'])) { ?>
      <div class="info-row" style="border-bottom: none;">
        <span class="info-label">Tujuan</span>
        <span class="info-value" style="text-align: left; width: 100%;">
          <div class="keterangan-box">
            <p><?php echo nl2br(htmlspecialchars($d['tujuan_barang'])); ?></p>
          </div>
        </span>
      </div>
      <?php } ?>
    </div>

  </div>

  <!-- Layout Image -->
  <?php if(!empty($d['layout'])) { ?>
  <div class="layout-card">
    <h3><i class="fa fa-image"></i> Layout Ruangan</h3>
    <img src="peminjaman/barang/layout/<?php echo htmlspecialchars(basename($d['layout'])); ?>" 
         alt="Layout Ruangan"
         onerror="this.src='https://via.placeholder.com/800x400?text=Layout+Tidak+Tersedia'">
  </div>
  <?php } ?>

  <!-- Keterangan Tambahan -->
  <?php if(!empty($d['ket'])) { ?>
  <div class="info-card">
    <div class="info-card-header">
      <div class="info-card-icon">
        <i class="fa fa-info-circle"></i>
      </div>
      <h3 class="info-card-title">Keterangan Tambahan</h3>
    </div>
    <div class="keterangan-box">
      <p><?php echo nl2br(htmlspecialchars($d['ket'])); ?></p>
    </div>
  </div>
  <?php } ?>

  <!-- Action Buttons -->
  <div class="action-bar">
    <a href="./peminjaman/barang/cetakdetail.php?id=<?php echo $id_pinjam; ?>" 
       target="_blank" 
       class="btn btn-success">
      <i class="fa fa-print"></i> Cetak Detail
    </a>
    <button onclick="window.print()" class="btn btn-primary">
      <i class="fa fa-file-pdf"></i> Print PDF
    </button>
    <a href="?view=datapinjambarang" class="btn btn-secondary">
      <i class="fa fa-arrow-left"></i> Kembali
    </a>
  </div>

  <!-- Footer -->
  <div class="footer">
    <strong>SIPINJAM</strong> &copy; 2025
  </div>

</div>
