<?php
/**
 * Detail Peminjaman Studio - Clean Grid Layout
 * Dengan tombol print yang membuka halaman baru
 */

// Aktifkan error reporting untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Output buffering
ob_start();

// ========================================
// FIX: Path cek.php yang benar
// ========================================
if (!isset($conn)) {
    $possible_paths = [
        __DIR__ . '/../../../cek.php',
        __DIR__ . '/../../../admin/cek.php',
        __DIR__ . '/../../cek.php',
        __DIR__ . '/../../../config/cek.php',
    ];
    
    $cek_loaded = false;
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $cek_loaded = true;
            break;
        }
    }
    
    if (!$cek_loaded) {
        ob_end_clean();
        http_response_code(500);
        exit('File cek.php tidak ditemukan. Path: ' . __DIR__);
    }
}

// Validasi koneksi database
if (!isset($conn) || !($conn instanceof mysqli)) {
  ob_end_clean();
  http_response_code(500);
  exit('Koneksi database tidak tersedia.');
}

// Set charset
if (!mysqli_set_charset($conn, 'utf8mb4')) {
  ob_end_clean();
  http_response_code(500);
  exit('Error loading character set utf8mb4: ' . mysqli_error($conn));
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* ========================= Helpers ========================= */
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function show($v){ $v = trim((string)$v); return $v === '' ? '-' : e($v); }

function fmtDateId($ymd){
  if (!$ymd || $ymd === '0000-00-00') return '-';
  static $bulan = [1=>"Jan","Feb","Mar","Apr","Mei","Jun","Jul","Agu","Sep","Okt","Nov","Des"];
  $t = strtotime($ymd);
  if ($t === false) return '-';
  return date('j', $t) . ' ' . $bulan[(int)date('n',$t)] . ' ' . date('Y',$t);
}

function fmtTime($hhmmss){
  if (!$hhmmss) return '-';
  $t = strlen($hhmmss) === 5 ? $hhmmss.':00' : $hhmmss;
  $p = explode(':', $t);
  return sprintf('%02d:%02d', (int)$p[0], (int)$p[1]);
}

function rangeWaktu($mulai, $selesai){
  $a = fmtTime($mulai); $b = fmtTime($selesai);
  if ($a!=='-' && $b!=='-') return "$a - $b WIB";
  if ($a!=='-') return $a . ' WIB';
  return '-';
}

/* ========================= Parameter ========================= */
$idRaw = $_GET['id'] ?? '';
if ($idRaw === '' || !is_numeric($idRaw)) { 
  ob_end_clean();
  http_response_code(400); 
  exit('ID tidak valid atau tidak diberikan.'); 
}
$id = (int)$idRaw;

// Cek jika mode print
$isPrintMode = isset($_GET['print']) && $_GET['print'] === '1';

/* ========================= Fetch Data ========================= */
$sql = "SELECT ps.id_pinjamstudio, u.nama_lengkap, 
               ps.nama AS pic_nama, ps.no_hp AS pic_wa,
               s.jenis_studio, ps.tgl_mulai, ps.waktu_mulai, ps.waktu_selesai,
               ps.deskripsi_peminjaman, ps.status
        FROM pinjamstudio ps
        LEFT JOIN user u ON u.id=ps.id_user
        LEFT JOIN studio s ON s.id_studio=ps.id_studio
        WHERE ps.id_pinjamstudio=? LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt === false) {
  ob_end_clean();
  http_response_code(500);
  exit('Error preparing statement: ' . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $id); 
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if (!$res || !$res->num_rows) { 
  mysqli_stmt_close($stmt);
  ob_end_clean();
  http_response_code(404); 
  exit('Data peminjaman studio tidak ditemukan.'); 
}
$data = $res->fetch_assoc();
mysqli_stmt_close($stmt);

$judul = 'Detail Peminjaman Studio';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title><?= e($judul) ?> #<?= $data['id_pinjamstudio'] ?> - SIPINJAM</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    /* ========================= Base ========================= */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
      background: #f5f5f5;
      padding: 20px;
      line-height: 1.5;
    }

    .page {
      max-width: 1200px;
      margin: 0 auto;
      background: white;
      box-shadow: 0 1px 3px rgba(0,0,0,0.12);
    }

    /* ========================= Header ========================= */
    .header {
      background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
      color: white;
      padding: 20px 32px;
      position: relative;
    }

    .header h1 {
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 2px;
    }

    .header .subtitle {
      font-size: 0.875rem;
      opacity: 0.95;
    }

    .header .date {
      position: absolute;
      top: 20px;
      right: 32px;
      font-size: 0.875rem;
    }

    /* ========================= Content ========================= */
    .content {
      padding: 32px;
    }

    .grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 48px;
    }

    /* ========================= Section ========================= */
    .section {
      margin-bottom: 32px;
    }

    .section-title {
      font-size: 1rem;
      font-weight: 600;
      color: #2563eb;
      padding-bottom: 8px;
      border-bottom: 2px solid #2563eb;
      margin-bottom: 16px;
    }

    /* ========================= Row ========================= */
    .row {
      display: flex;
      padding: 10px 0;
      gap: 16px;
    }

    .row-label {
      min-width: 100px;
      flex-shrink: 0;
      color: #374151;
      font-size: 0.938rem;
    }

    .row-value {
      flex: 1;
      color: #111827;
      font-size: 0.938rem;
    }

    .row-value.highlight {
      color: #2563eb;
      font-weight: 600;
    }

    .row-value.strong {
      font-weight: 600;
    }

    /* ========================= Status Badge ========================= */
    .badge {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 4px;
      font-size: 0.875rem;
      font-weight: 500;
      background: #f3f4f6;
      color: #374151;
    }

    .badge.pending {
      background: #fef3c7;
      color: #92400e;
    }

    .badge.approved {
      background: #d1fae5;
      color: #065f46;
    }

    .badge.rejected {
      background: #fee2e2;
      color: #991b1b;
    }

    /* ========================= Action Buttons ========================= */
    .action-bar {
      padding: 0 32px 32px 32px;
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }

    .btn {
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
      transition: all 0.2s ease;
      font-family: inherit;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .btn-primary {
      background: #2563eb;
      color: white;
    }

    .btn-primary:hover {
      background: #1d4ed8;
    }

    .btn-secondary {
      background: white;
      color: #374151;
      border: 1px solid #d1d5db;
    }

    .btn-secondary:hover {
      background: #f9fafb;
    }

    .btn i {
      font-size: 1rem;
    }

    /* ========================= Print Mode ========================= */
    <?php if ($isPrintMode): ?>
    body {
      background: white;
      padding: 0;
    }

    .page {
      max-width: 100%;
      box-shadow: none;
    }

    .action-bar {
      display: none !important;
    }

    .grid {
      grid-template-columns: 1fr;
      gap: 24px;
    }
    <?php endif; ?>

    /* ========================= Print ========================= */
    @page {
      size: A4 portrait;
      margin: 15mm;
    }

    @media print {
      * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }

      body {
        background: white;
        padding: 0;
      }

      .page {
        box-shadow: none;
        max-width: 100%;
      }

      .header {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
      }

      .header .date {
        position: static;
        display: block;
        margin-top: 8px;
      }

      .content {
        padding: 24px;
      }

      .grid {
        grid-template-columns: 1fr;
        gap: 24px;
      }

      .section {
        page-break-inside: avoid;
        margin-bottom: 20px;
      }

      .row {
        padding: 8px 0;
      }

      .action-bar {
        display: none !important;
      }
    }

    @media screen and (max-width: 768px) {
      .grid {
        grid-template-columns: 1fr;
        gap: 0;
      }

      .header .date {
        position: static;
        margin-top: 8px;
      }

      .content {
        padding: 20px;
      }

      .action-bar {
        flex-direction: column;
        padding: 0 20px 20px 20px;
      }

      .btn {
        width: 100%;
        justify-content: center;
      }
    }
  </style>
</head>
<body>
  <div class="page">
    <!-- Header -->
    <div class="header">
      <h1><?= e($judul) ?></h1>
      <div class="subtitle">Sistem Informasi Peminjaman</div>
      <div class="date"><?= date('d/m/Y - H:i') ?> WIB</div>
    </div>

    <!-- Content -->
    <div class="content">
      <div class="grid">
        <!-- Left Column -->
        <div class="column-left">
          <!-- Informasi Umum -->
          <div class="section">
            <div class="section-title">Informasi Umum</div>
            <div class="row">
              <div class="row-label">ID</div>
              <div class="row-value">#<?= show($data['id_pinjamstudio']) ?></div>
            </div>
            <div class="row">
              <div class="row-label">Status</div>
              <div class="row-value">
                <?php
                $status = strtolower(trim($data['status'] ?? ''));
                $badgeClass = 'badge';
                $badgeText = ucfirst($data['status'] ?? 'Menunggu');
                
                if (in_array($status, ['disetujui', 'approved', 'approve'])) {
                  $badgeClass .= ' approved';
                  $badgeText = 'Disetujui';
                } elseif (in_array($status, ['ditolak', 'rejected', 'reject'])) {
                  $badgeClass .= ' rejected';
                  $badgeText = 'Ditolak';
                } elseif (in_array($status, ['menunggu', 'pending'])) {
                  $badgeClass .= ' pending';
                  $badgeText = 'Menunggu';
                }
                ?>
                <span class="<?= $badgeClass ?>"><?= $badgeText ?></span>
              </div>
            </div>
            <div class="row">
              <div class="row-label">Studio</div>
              <div class="row-value highlight"><?= show($data['jenis_studio'] ?? '-') ?></div>
            </div>
            <div class="row">
              <div class="row-label">Tanggal</div>
              <div class="row-value strong"><?= e(fmtDateId($data['tgl_mulai'])) ?></div>
            </div>
            <div class="row">
              <div class="row-label">Waktu</div>
              <div class="row-value strong"><?= e(rangeWaktu($data['waktu_mulai'], $data['waktu_selesai'])) ?></div>
            </div>
          </div>
        </div>

        <!-- Right Column -->
        <div class="column-right">
          <!-- Peminjam -->
          <div class="section">
            <div class="section-title">Peminjam</div>
            <div class="row">
              <div class="row-label">Nama</div>
              <div class="row-value"><?= show($data['nama_lengkap'] ?? '-') ?></div>
            </div>
            <div class="row">
              <div class="row-label">PIC</div>
              <div class="row-value"><?= show($data['pic_nama'] ?? '-') ?></div>
            </div>
            <div class="row">
              <div class="row-label">Kontak</div>
              <div class="row-value"><?= show($data['pic_wa'] ?? '-') ?></div>
            </div>
          </div>

          <!-- Detail -->
          <div class="section">
            <div class="section-title">Detail</div>
            <div class="row">
              <div class="row-label">Deskripsi</div>
              <div class="row-value"><?= nl2br(show($data['deskripsi_peminjaman'] ?? '-')) ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Action Buttons (Hidden in print mode) -->
    <?php if (!$isPrintMode): ?>
    <div class="action-bar">
      <button onclick="openPrintPage()" class="btn btn-primary">
        <i class="fa fa-print"></i>
        Cetak Detail
      </button>
      <a href="?view=datapinjamstudio" class="btn btn-secondary">
        <i class="fa fa-arrow-left"></i>
        Kembali
      </a>
    </div>
    <?php endif; ?>
  </div>

  <?php if ($isPrintMode): ?>
  <!-- Auto print saat halaman loaded -->
  <script>
    window.onload = function() {
      window.print();
    };

    // Close window setelah print (optional)
    window.onafterprint = function() {
      // Uncomment jika ingin auto close setelah print/cancel
      // window.close();
    };
  </script>
  <?php else: ?>
  <!-- Script untuk halaman normal -->
  <script>
    function openPrintPage() {
      // Ambil URL saat ini
      const currentUrl = window.location.href;
      
      // Tambahkan parameter print=1
      let printUrl;
      if (currentUrl.includes('?')) {
        printUrl = currentUrl + '&print=1';
      } else {
        printUrl = currentUrl + '?print=1';
      }
      
      // Buka di tab/window baru
      window.open(printUrl, '_blank');
    }

    // Keyboard shortcut: Ctrl+P
    document.addEventListener('keydown', function(e) {
      if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        openPrintPage();
      }
    });
  </script>
  <?php endif; ?>
</body>
</html>
<?php
ob_end_flush();
?>
