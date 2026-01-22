<?php
// ========================================
// DETAIL PEMINJAMAN KOLAM - MODERN MINIMALIST
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
    // 15:04 WIB
    return substr($hms, 0, 5) . ' WIB';
}

function getStatusBadge($status) {
    $status_lower = strtolower(trim($status));
    switch ($status_lower) {
        case 'approve':
        case 'disetujui':
            return '<span class="status-badge success">Disetujui</span>';
        case 'menunggu':
        case 'pending':
            return '<span class="status-badge warning">Menunggu</span>';
        case 'selesai':
        case 'completed':
            return '<span class="status-badge info">Selesai</span>';
        case 'ditolak':
        case 'reject':
            return '<span class="status-badge danger">Ditolak</span>';
        default:
            return '<span class="status-badge">' . ucfirst(htmlspecialchars($status)) . '</span>';
    }
}

/** Map angka 1–7 ke nama hari Indonesia */
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
    $order = [1,2,3,4,5,6,7];
    usort($out, function($a,$b) use ($map,$order){
        $ia = array_search(array_search($a, $map), $order);
        $ib = array_search(array_search($b, $map), $order);
        return $ia <=> $ib;
    });
    return $out;
}

/** Parse kolom recurring_days (isi "1,3,5" / "135" / "1|3|5" dll) */
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

// Validasi ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('ID tidak valid'); window.location.href='?view=datapinjamkolam';</script>";
    exit;
}

$id_pinjam = (int)$_GET['id'];

// Prepared statement untuk keamanan
$stmt = $conn->prepare("SELECT 
    pk.*,
    u.nama_lengkap,
    k.jenis_kolam,
    kl.nama_kelas
FROM pinjamkolam pk
INNER JOIN user u ON u.id = pk.id_user
INNER JOIN kolam k ON k.id_kolam = pk.id_kolam
LEFT JOIN kelas kl ON kl.id_kelas = pk.id_kelas
WHERE pk.id_pinjamkolam = ?
LIMIT 1");

if (!$stmt) {
    echo "<script>alert('Terjadi kesalahan sistem'); window.location.href='?view=datapinjamkolam';</script>";
    exit;
}

$stmt->bind_param("i", $id_pinjam);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('Data tidak ditemukan'); window.location.href='?view=datapinjamkolam';</script>";
    exit;
}

$d = $result->fetch_assoc();
$stmt->close();

// Ambil field jadwal & rutin
$tgl_mulai   = $d['tgl_mulai']   ?? '';
$tgl_selesai = $d['tgl_selesai'] ?? '';
$wkt_mulai   = $d['waktu_mulai'] ?? '';
$wkt_selesai = $d['waktu_selesai'] ?? '';
$is_rec      = strtolower(trim($d['is_recurring'] ?? 'no')) === 'yes';
$rec_days    = $d['recurring_days'] ?? '';
$rec_nums    = parseRecurringDays($rec_days);
$rec_names   = mapHariByNumber($rec_nums);

// Periode tanggal → mudah dibaca
if (!empty($tgl_selesai) && $tgl_selesai !== '0000-00-00' && $tgl_selesai !== $tgl_mulai) {
    $periode_tanggal = fmt_tgl($tgl_mulai) . ' → ' . fmt_tgl($tgl_selesai);
} else {
    $periode_tanggal = fmt_tgl($tgl_mulai);
}

// Periode waktu → mudah dibaca
if (!empty($wkt_mulai) && !empty($wkt_selesai)) {
    $periode_waktu = fmt_waktu($wkt_mulai) . ' → ' . fmt_waktu($wkt_selesai);
} else {
    $periode_waktu = fmt_waktu($wkt_mulai);
}
?>
<style>
/* ========================================
   MODERN MINIMALIST DETAIL PAGE
   ======================================== */

.detail-kolam {
  --primary: #2563eb;
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
  --border: #e2e8f0;
  --radius: 12px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  max-width: 900px;
  margin: 0 auto;
  padding: 24px;
}

/* Header Section */
.detail-kolam .page-header {
  background: linear-gradient(135deg, var(--primary) 0%, #1d4ed8 100%);
  padding: 32px;
  border-radius: var(--radius);
  margin-bottom: 32px;
  box-shadow: var(--shadow-md);
  color: white;
}

.detail-kolam .page-header h2 {
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0 0 8px 0;
}

.detail-kolam .page-header p {
  margin: 0;
  opacity: 0.9;
  font-size: 0.875rem;
}

/* Info Grid */
.detail-kolam .info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}

/* Info Card */
.detail-kolam .info-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 24px;
  box-shadow: var(--shadow-sm);
  transition: all 0.2s ease;
}

.detail-kolam .info-card:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}

.detail-kolam .info-card-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 2px solid var(--border);
}

.detail-kolam .info-card-icon {
  width: 44px;
  height: 44px;
  background: linear-gradient(135deg, var(--primary) 0%, #1d4ed8 100%);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.detail-kolam .info-card-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--txt);
  margin: 0;
}

/* Info Row */
.detail-kolam .info-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 12px 0;
  gap: 16px;
  border-bottom: 1px solid var(--border);
}

.detail-kolam .info-row:last-child {
  border-bottom: none;
}

.detail-kolam .info-label {
  font-size: 0.813rem;
  font-weight: 600;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 0.025em;
  flex-shrink: 0;
  min-width: 120px;
}

.detail-kolam .info-value {
  font-size: 0.938rem;
  font-weight: 500;
  color: var(--txt);
  text-align: right;
  word-break: break-word;
}

/* Status Badge */
.detail-kolam .status-badge {
  display: inline-flex;
  align-items: center;
  padding: 6px 14px;
  border-radius: 999px;
  font-size: 0.813rem;
  font-weight: 600;
  letter-spacing: 0.025em;
}

.detail-kolam .status-badge.success {
  background: #d1fae5;
  color: #065f46;
}

.detail-kolam .status-badge.warning {
  background: #fef3c7;
  color: #92400e;
}

.detail-kolam .status-badge.info {
  background: #cffafe;
  color: #0e7490;
}

.detail-kolam .status-badge.danger {
  background: #fee2e2;
  color: #991b1b;
}

/* Rutin badge */
.detail-kolam .badge-rutin {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
}
.detail-kolam .badge-rutin.yes {
  background:#e0f2fe;
  color:#0369a1;
}
.detail-kolam .badge-rutin.no {
  background:#f3f4f6;
  color:#4b5563;
}

/* Action Buttons */
.detail-kolam .action-bar {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  margin-top: 32px;
}

.detail-kolam .btn {
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

.detail-kolam .btn:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
  text-decoration: none;
}

.detail-kolam .btn-primary {
  background: var(--primary);
  color: white;
}

.detail-kolam .btn-primary:hover {
  background: #1d4ed8;
}

.detail-kolam .btn-success {
  background: var(--success);
  color: white;
}

.detail-kolam .btn-success:hover {
  background: #059669;
}

.detail-kolam .btn-secondary {
  background: var(--card);
  color: var(--txt);
  border: 1px solid var(--border);
}

.detail-kolam .btn-secondary:hover {
  background: var(--bg);
}

/* Footer */
.detail-kolam .footer {
  margin-top: 48px;
  padding-top: 24px;
  text-align: center;
  color: var(--muted);
  font-size: 0.813rem;
  border-top: 1px solid var(--border);
}

/* Dark Mode Support */
body[data-theme="dark"] .detail-kolam {
  --txt: #f1f5f9;
  --bg: #0f172a;
  --card: #1e293b;
  --border: #334155;
  --muted: #94a3b8;
}

body[data-theme="dark"] .detail-kolam .status-badge.success {
  background: rgba(16, 185, 129, 0.2);
  color: #6ee7b7;
}

body[data-theme="dark"] .detail-kolam .status-badge.warning {
  background: rgba(245, 158, 11, 0.2);
  color: #fbbf24;
}

body[data-theme="dark"] .detail-kolam .status-badge.info {
  background: rgba(6, 182, 212, 0.2);
  color: #67e8f9;
}

body[data-theme="dark"] .detail-kolam .status-badge.danger {
  background: rgba(239, 68, 68, 0.2);
  color: #fca5a5;
}

/* Responsive */
@media (max-width: 768px) {
  .detail-kolam {
    padding: 16px;
  }
  
  .detail-kolam .page-header {
    padding: 24px 20px;
  }
  
  .detail-kolam .info-grid {
    grid-template-columns: 1fr;
  }
  
  .detail-kolam .info-card {
    padding: 20px;
  }
  
  .detail-kolam .info-row {
    flex-direction: column;
    gap: 4px;
  }
  
  .detail-kolam .info-label {
    min-width: auto;
  }
  
  .detail-kolam .info-value {
    text-align: left;
  }
  
  .detail-kolam .action-bar {
    flex-direction: column;
  }
  
  .detail-kolam .btn {
    width: 100%;
    justify-content: center;
  }
}

/* Print Styles */
@media print {
  .detail-kolam .action-bar,
  .detail-kolam .footer {
    display: none;
  }
  
  .detail-kolam .page-header {
    background: #2563eb !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
}
</style>

<div class="detail-kolam">
  
  <!-- Header -->
  <div class="page-header">
    <h2><i class="fa fa-swimming-pool"></i> Detail Peminjaman Kolam</h2>
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
        <span class="info-label">Nama Divisi</span>
        <span class="info-value"><?php echo htmlspecialchars($d['nama_lengkap'] ?? '-'); ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">PIC</span>
        <span class="info-value"><?php echo htmlspecialchars($d['nama'] ?? '-'); ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">No. WhatsApp</span>
        <span class="info-value">
          <?php 
          $no_hp = htmlspecialchars($d['no_hp'] ?? '-');
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

    <!-- Informasi Fasilitas -->
    <div class="info-card">
      <div class="info-card-header">
        <div class="info-card-icon">
          <i class="fa fa-building"></i>
        </div>
        <h3 class="info-card-title">Informasi Fasilitas</h3>
      </div>
      <div class="info-row">
        <span class="info-label">Kolam</span>
        <span class="info-value"><?php echo htmlspecialchars($d['jenis_kolam'] ?? '-'); ?></span>
      </div>
      <?php if(!empty($d['nama_kelas'])) { ?>
      <div class="info-row">
        <span class="info-label">Kelas</span>
        <span class="info-value"><?php echo htmlspecialchars($d['nama_kelas']); ?></span>
      </div>
      <?php } ?>
      <div class="info-row">
        <span class="info-label">Status</span>
        <span class="info-value">
          <?php echo getStatusBadge($d['status'] ?? 'menunggu'); ?>
        </span>
      </div>
      <div class="info-row">
        <span class="info-label">Jadwal Rutin</span>
        <span class="info-value">
          <?php if ($is_rec): ?>
            <span class="badge-rutin yes">Rutin</span>
            <?php if (!empty($rec_names)): ?>
              <br><small style="color:var(--muted);">
                Setiap: <?php echo htmlspecialchars(implode(', ', $rec_names)); ?>
              </small>
            <?php endif; ?>
          <?php else: ?>
            <span class="badge-rutin no">Tidak</span>
          <?php endif; ?>
        </span>
      </div>
    </div>

    <!-- Jadwal Peminjaman -->
    <div class="info-card" style="grid-column: 1 / -1;">
      <div class="info-card-header">
        <div class="info-card-icon">
          <i class="fa fa-calendar"></i>
        </div>
        <h3 class="info-card-title">Jadwal Peminjaman</h3>
      </div>
      <div class="info-row">
        <span class="info-label">Periode Tanggal</span>
        <span class="info-value"><?php echo $periode_tanggal; ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">Periode Waktu</span>
        <span class="info-value"><?php echo $periode_waktu; ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">Durasi</span>
        <span class="info-value">
          <?php 
          if(!empty($tgl_mulai) && !empty($tgl_selesai) && !empty($wkt_mulai) && !empty($wkt_selesai)) {
            $start_dt = strtotime($tgl_mulai.' '.$wkt_mulai);
            $end_dt   = strtotime($tgl_selesai.' '.$wkt_selesai);
            if ($end_dt > $start_dt) {
              $diff_sec = $end_dt - $start_dt;
              $days  = floor($diff_sec / 86400);
              $hours = floor(($diff_sec % 86400) / 3600);
              $mins  = floor(($diff_sec % 3600) / 60);

              $parts = [];
              if ($days > 0)  $parts[] = $days.' hari';
              if ($hours > 0) $parts[] = $hours.' jam';
              if ($mins > 0)  $parts[] = $mins.' menit';
              echo $parts ? implode(' ', $parts) : 'Kurang dari 1 menit';
            } else {
              echo '-';
            }
          } elseif(!empty($wkt_mulai) && !empty($wkt_selesai) && (empty($tgl_selesai) || $tgl_selesai === $tgl_mulai)) {
            $mulai = strtotime($wkt_mulai);
            $selesai = strtotime($wkt_selesai);
            if ($selesai > $mulai) {
              $diff = ($selesai - $mulai) / 3600;
              echo number_format($diff, 1) . ' jam';
            } else {
              echo '-';
            }
          } else {
            echo '-';
          }
          ?>
        </span>
      </div>
    </div>

  </div>

  <!-- Action Buttons -->
  <div class="action-bar">
    <a href="./peminjaman/cetakdetail.php?id=<?php echo $id_pinjam; ?>" 
       target="_blank" 
       class="btn btn-success">
      <i class="fa fa-print"></i> Cetak Detail
    </a>
    <button onclick="window.print()" class="btn btn-primary">
      <i class="fa fa-file-pdf"></i> Print PDF
    </button>
    <a href="?view=datapinjamkolam" class="btn btn-secondary">
      <i class="fa fa-arrow-left"></i> Kembali
    </a>
  </div>

  <!-- Footer -->
  <div class="footer">
    <strong>SIPINJAM</strong> &copy; 2025
  </div>

</div>
