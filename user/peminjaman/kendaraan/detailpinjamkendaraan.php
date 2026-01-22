<?php
// ========================================
// OPTIMIZED CODE - FIXED INFINITE LOADING
// ========================================

// Helper Functions untuk format tanggal dan waktu
function fmt_tgl($ymd) {
    if (!$ymd || $ymd === '0000-00-00') return '-';
    $parts = explode('-', $ymd);
    if (count($parts) !== 3) return htmlspecialchars($ymd);
    [$y, $m, $d] = $parts;
    $bulan = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", 
              "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
    $month_index = (int)$m;
    if ($month_index < 1 || $month_index > 12) return htmlspecialchars($ymd);
    return sprintf("%d %s %s", (int)$d, $bulan[$month_index], $y);
}

function fmt_waktu($hms) {
    if (!$hms) return '-';
    return substr($hms, 0, 5) . ' WIB';
}

// Fungsi untuk mendapatkan class badge berdasarkan status
function getStatusBadgeClass($status) {
    $status_lower = strtolower(trim($status));
    switch ($status_lower) {
        case 'approve':
        case 'disetujui':
            return 'badge-success';
        case 'ditolak':
        case 'reject':
            return 'badge-danger';
        case 'selesai':
        case 'completed':
            return 'badge-info';
        case 'menunggu':
        case 'pending':
        default:
            return 'badge-warning';
    }
}

// ========================================
// PERBAIKAN UTAMA: Validasi tanpa redirect
// ========================================
$error_message = '';
$d = null;

// Validasi ID
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || $_GET['id'] <= 0) {
    $error_message = 'ID tidak valid atau tidak ditemukan';
} else {
    $id_pk = (int)$_GET['id'];
    
    // PREPARED STATEMENT untuk mencegah SQL Injection
    $stmt = $conn->prepare("SELECT 
        pk.*,
        k.nama_kendaraan,
        k.deskripsi as nomor_polisi,
        k.foto,
        u.nama_lengkap
    FROM pinjamkendaraan pk
    INNER JOIN kendaraan k ON k.id_kendaraan = pk.id_kendaraan
    INNER JOIN user u ON u.id = pk.id_user
    WHERE pk.id_pk = ?
    LIMIT 1");
    
    if ($stmt) {
        $stmt->bind_param("i", $id_pk);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            
            // Cek apakah data ditemukan
            if ($result->num_rows > 0) {
                $d = $result->fetch_assoc();
            } else {
                $error_message = 'Data peminjaman tidak ditemukan';
            }
        } else {
            error_log("Execute failed: " . $stmt->error);
            $error_message = 'Gagal mengambil data dari database';
        }
        
        $stmt->close();
    } else {
        error_log("Prepare failed: " . $conn->error);
        $error_message = 'Terjadi kesalahan sistem';
    }
}

// Jika ada error, tampilkan pesan error tanpa redirect
if ($error_message) {
    ?>
    <div class="page-detail-pinjam">
        <div class="page-inner">
            <div class="alert alert-danger" style="background:#fee2e2;color:#991b1b;padding:20px;border-radius:12px;border:1px solid #fecaca;">
                <h4 style="margin:0 0 8px 0;"><i class="fa fa-exclamation-triangle"></i> Error</h4>
                <p style="margin:0;"><?php echo htmlspecialchars($error_message); ?></p>
                <div style="margin-top:16px;">
                    <a href="?view=datapinjamkendaraan" class="btn btn-primary">
                        <i class="fa fa-arrow-left"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php
    // PENTING: Return di sini, jangan tampilkan sisanya
    return;
}

// Set status class dan text
$status_class = getStatusBadgeClass($d['status'] ?? 'menunggu');
$status_text = htmlspecialchars(ucfirst($d['status'] ?? 'Menunggu'));
?>

<style>
/* Modern Detail Page Styling - Optimized */
.page-detail-pinjam {
  --primary: #3b82f6;
  --success: #22c55e;
  --warning: #f59e0b;
  --danger: #ef4444;
  --info: #06b6d4;
  --muted: #6b7280;
  --txt: #1f2937;
  --card: #fff;
  --shadow: 0 6px 16px rgba(0,0,0,.06);
  --shadow-lg: 0 10px 25px rgba(0,0,0,.1);
  --border: #e5e7eb;
}

.page-detail-pinjam .detail-header {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  padding: 32px;
  border-radius: 16px;
  margin-bottom: 24px;
  box-shadow: var(--shadow-lg);
  position: relative;
  overflow: hidden;
}

.page-detail-pinjam .detail-header::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -10%;
  width: 300px;
  height: 300px;
  background: rgba(255,255,255,0.1);
  border-radius: 50%;
}

.page-detail-pinjam .detail-header h2 {
  margin: 0 0 8px 0;
  font-size: 1.75rem;
  font-weight: 700;
  position: relative;
  z-index: 1;
}

.page-detail-pinjam .detail-header p {
  margin: 0;
  opacity: 0.9;
  font-size: 0.95rem;
  position: relative;
  z-index: 1;
}

.page-detail-pinjam .info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 24px;
  margin-bottom: 24px;
}

.page-detail-pinjam .info-card {
  background: var(--card);
  border-radius: 12px;
  padding: 20px;
  box-shadow: var(--shadow);
  border-left: 4px solid var(--primary);
  transition: all 0.3s ease;
}

.page-detail-pinjam .info-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
}

.page-detail-pinjam .info-card-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 2px solid var(--border);
}

.page-detail-pinjam .info-card-icon {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.2rem;
  flex-shrink: 0;
}

.page-detail-pinjam .info-card-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--txt);
  margin: 0;
}

.page-detail-pinjam .info-row {
  display: flex;
  justify-content: space-between;
  padding: 10px 0;
  border-bottom: 1px solid var(--border);
  gap: 16px;
}

.page-detail-pinjam .info-row:last-child {
  border-bottom: none;
}

.page-detail-pinjam .info-label {
  font-weight: 600;
  color: var(--muted);
  font-size: 0.9rem;
  flex-shrink: 0;
}

.page-detail-pinjam .info-value {
  font-weight: 600;
  color: var(--txt);
  text-align: right;
  font-size: 0.95rem;
  word-break: break-word;
}

.page-detail-pinjam .image-card {
  background: var(--card);
  border-radius: 12px;
  padding: 20px;
  box-shadow: var(--shadow);
  text-align: center;
}

.page-detail-pinjam .image-card img {
  width: 100%;
  max-width: 600px;
  height: auto;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  object-fit: cover;
  max-height: 400px;
}

.page-detail-pinjam .image-card h4 {
  margin: 0 0 16px 0;
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--txt);
}

.page-detail-pinjam .badge {
  display: inline-block;
  padding: 6px 16px;
  border-radius: 999px;
  font-weight: 700;
  font-size: 0.85rem;
}

.page-detail-pinjam .badge-success {
  background: #d1fae5;
  color: #065f46;
  border: 1px solid #a7f3d0;
}

.page-detail-pinjam .badge-warning {
  background: #fef3c7;
  color: #92400e;
  border: 1px solid #fde68a;
}

.page-detail-pinjam .badge-danger {
  background: #fee2e2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

.page-detail-pinjam .badge-info {
  background: #cffafe;
  color: #0e7490;
  border: 1px solid #a5f3fc;
}

.page-detail-pinjam .btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.2s ease;
}

.page-detail-pinjam .btn:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow);
  text-decoration: none;
}

.page-detail-pinjam .btn-primary {
  background: var(--primary);
  color: white;
}

.page-detail-pinjam .btn-primary:hover {
  background: #2563eb;
  color: white;
}

.page-detail-pinjam .btn-secondary {
  background: var(--muted);
  color: white;
}

.page-detail-pinjam .btn-secondary:hover {
  background: #4b5563;
  color: white;
}

.page-detail-pinjam .action-buttons {
  display: flex;
  gap: 12px;
  margin-top: 24px;
  flex-wrap: wrap;
}

.page-detail-pinjam .copyright {
  margin-top: 32px;
  padding-top: 16px;
  border-top: 2px solid var(--border);
  color: var(--muted);
  font-size: 0.9rem;
  text-align: center;
}

.page-detail-pinjam .alert-danger {
  padding: 20px;
  border-radius: 12px;
  margin-bottom: 24px;
}

/* Dark Mode Support */
body[data-theme="dark"] .page-detail-pinjam .info-card,
body[data-theme="dark"] .page-detail-pinjam .image-card {
  background: #1e293b;
}

body[data-theme="dark"] .page-detail-pinjam .info-card-title,
body[data-theme="dark"] .page-detail-pinjam .info-value {
  color: #e5e7eb;
}

body[data-theme="dark"] .page-detail-pinjam .info-label {
  color: #94a3b8;
}

body[data-theme="dark"] .page-detail-pinjam .info-row {
  border-bottom-color: #334155;
}

body[data-theme="dark"] .page-detail-pinjam .info-card-header {
  border-bottom-color: #334155;
}

body[data-theme="dark"] .page-detail-pinjam .copyright {
  border-top-color: #334155;
  color: #94a3b8;
}

/* Print Styles */
@media print {
  .page-detail-pinjam .action-buttons,
  .page-detail-pinjam .detail-header::before {
    display: none;
  }
  
  .page-detail-pinjam .info-card {
    break-inside: avoid;
  }
}

/* Responsive */
@media (max-width: 768px) {
  .page-detail-pinjam .info-grid {
    grid-template-columns: 1fr;
  }
  
  .page-detail-pinjam .detail-header {
    padding: 24px;
  }
  
  .page-detail-pinjam .detail-header h2 {
    font-size: 1.4rem;
  }
  
  .page-detail-pinjam .action-buttons {
    flex-direction: column;
  }
  
  .page-detail-pinjam .btn {
    width: 100%;
    justify-content: center;
  }
  
  .page-detail-pinjam .info-row {
    flex-direction: column;
    gap: 4px;
  }
  
  .page-detail-pinjam .info-value {
    text-align: left;
  }
}
</style>

<div class="page-detail-pinjam">
  <div class="page-inner">
    
    <!-- Header Card -->
    <div class="detail-header">
      <h2><i class="fa fa-file-text"></i> Detail Peminjaman Kendaraan</h2>
      <p>Informasi lengkap peminjaman kendaraan ID: <?php echo htmlspecialchars($id_pk); ?></p>
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
          <span class="info-label">Nama Lengkap</span>
          <span class="info-value"><?php echo htmlspecialchars($d['nama_lengkap'] ?? '-'); ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Bagian</span>
          <span class="info-value"><?php echo htmlspecialchars($d['bagian'] ?? '-'); ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Pengemudi</span>
          <span class="info-value"><?php echo htmlspecialchars($d['pengemudi'] ?? '-'); ?></span>
        </div>
      </div>

      <!-- Informasi Kendaraan -->
      <div class="info-card">
        <div class="info-card-header">
          <div class="info-card-icon">
            <i class="fa fa-car"></i>
          </div>
          <h3 class="info-card-title">Informasi Kendaraan</h3>
        </div>
        <div class="info-row">
          <span class="info-label">Nama Kendaraan</span>
          <span class="info-value"><?php echo htmlspecialchars($d['nama_kendaraan'] ?? '-'); ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Nomor Polisi</span>
          <span class="info-value"><strong><?php echo htmlspecialchars($d['nomor_polisi'] ?? '-'); ?></strong></span>
        </div>
        <div class="info-row">
          <span class="info-label">Status</span>
          <span class="info-value">
            <span class="badge <?php echo $status_class; ?>">
              <?php echo $status_text; ?>
            </span>
          </span>
        </div>
      </div>

      <!-- Waktu Peminjaman -->
      <div class="info-card">
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
      </div>

      <!-- Tujuan & Deskripsi -->
      <div class="info-card" style="grid-column: 1 / -1;">
        <div class="info-card-header">
          <div class="info-card-icon">
            <i class="fa fa-map-marker"></i>
          </div>
          <h3 class="info-card-title">Tujuan & Keterangan</h3>
        </div>
        <div class="info-row">
          <span class="info-label">Tujuan</span>
          <span class="info-value"><?php echo htmlspecialchars($d['tujuan'] ?? '-'); ?></span>
        </div>
        <?php if (!empty($d['deskripsi']) && $d['deskripsi'] !== $d['nomor_polisi']) { ?>
        <div class="info-row">
          <span class="info-label">Keterangan</span>
          <span class="info-value"><?php echo nl2br(htmlspecialchars($d['deskripsi'] ?? '-')); ?></span>
        </div>
        <?php } ?>
      </div>

    </div>

    <!-- Foto Kendaraan -->
    <?php if (!empty($d['foto'])) { 
      $foto_path = "../admin/master/ruangan/Fotoruangan/" . basename($d['foto']);
    ?>
    <div class="image-card">
      <h4><i class="fa fa-image"></i> Foto Kendaraan</h4>
      <img src="<?php echo htmlspecialchars($foto_path); ?>" 
           alt="<?php echo htmlspecialchars($d['nama_kendaraan'] ?? 'Kendaraan'); ?>"
           onerror="this.src='https://via.placeholder.com/600x300?text=Foto+Tidak+Tersedia'"
           loading="lazy">
    </div>
    <?php } ?>

    <!-- Action Buttons -->
    <div class="action-buttons">
      <button onclick="window.print()" class="btn btn-primary">
        <i class="fa fa-print"></i> Cetak Detail
      </button>
      <a href="?view=datapinjamruangan" class="btn btn-secondary">
        <i class="fa fa-arrow-left"></i> Kembali ke Daftar
      </a>
    </div>

    <!-- Copyright -->
    <div class="copyright">
      <strong>&copy; Copyright@2025 | SIPINJAM</strong>
    </div>

  </div>
</div>
