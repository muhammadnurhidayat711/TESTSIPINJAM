<?php
// =====================================
// Detail Pinjam Gedung (SIPINJAM)
// - Aman (prepared statement)
// - UI informatif & responsif
// - Ringkasan, badge status, format tanggal & jam
// - Aksi cepat (WA peminjam + WA per PIC perlengkapan, print)
// =====================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../koneksi.php';

// ---------- Helpers ----------
function safe($s) {
    return htmlspecialchars((string)$s ?? '', ENT_QUOTES, 'UTF-8');
}
function boolLabel($v) {
    $truthy = ['1','ya','y','true','on','iya','ada'];
    return in_array(strtolower((string)$v), $truthy, true);
}
function fmtDateId($ymd) {
    if (!$ymd || $ymd === '0000-00-00') return '—';
    $bulan = [1=>"Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
    $t = strtotime($ymd);
    return date('j', $t) . ' ' . $bulan[(int)date('n',$t)] . ' ' . date('Y',$t);
}
function fmtTime($hhmmss) {
    if (!$hhmmss) return '—';
    $t = strlen($hhmmss) === 5 ? $hhmmss.':00' : $hhmmss;
    $parts = explode(':', $t);
    return sprintf('%02d:%02d', (int)$parts[0], (int)$parts[1]);
}
function statusBadge($statusRaw) {
    $status = strtolower(trim((string)$statusRaw));
    $map = [
        'pending'   => ['warning','Pending'],
        'ditolak'   => ['danger','Ditolak'],
        'rejected'  => ['danger','Ditolak'],
        'disetujui' => ['success','Disetujui'],
        'approved'  => ['success','Disetujui'],
        'dibatalkan'=> ['secondary','Dibatalkan'],
        'canceled'  => ['secondary','Dibatalkan'],
        'selesai'   => ['info','Selesai'],
        'returned'  => ['info','Selesai'],
    ];
    [$cls,$label] = $map[$status] ?? ['secondary', ucfirst($status ?: 'Tidak Diketahui')];
    return '<span class="badge badge-'.$cls.'">'.$label.'</span>';
}
function phoneDigits($s) {
    return preg_replace('/[^0-9+]/', '', (string)$s);
}
function waLink($phone, $text) {
    $p = phoneDigits($phone);
    if ($p === '') return '#';
    $q = urlencode($text);
    return "https://wa.me/{$p}?text={$q}";
}
function splitContacts($raw) {
    $raw = (string)$raw;
    if ($raw === '') return [];
    $list = preg_split('/[\s,;\/\r\n]+/u', $raw, -1, PREG_SPLIT_NO_EMPTY);
    $out = [];
    foreach ($list as $it) {
        $it = trim($it);
        if ($it === '') continue;
        $out[] = $it;
    }
    $seen = [];
    $uniq = [];
    foreach ($out as $it) {
        $key = phoneDigits($it) ?: strtolower($it);
        if (!isset($seen[$key])) {
            $uniq[] = $it;
            $seen[$key] = true;
        }
    }
    return $uniq;
}

// ---------- Ambil Data ----------
$id = $_GET['id'] ?? '';
if (!preg_match('/^\d+$/', $id)) {
    http_response_code(400);
    exit('<div style="padding:16px;color:#b91c1c;background:#fee2e2;border:1px solid #fecaca;border-radius:8px">Parameter ID tidak valid.</div>');
}

$sql = "SELECT p.*, 
               u.nama_lengkap, 
               p.nama  AS pic_nama,
               p.nohp AS pic_wa,
               b.nama_barang
        FROM pinjambarang p
        INNER JOIN user u   ON u.id = p.id_user
        INNER JOIN barang b ON b.id = p.id_barang
        WHERE p.id_pinjam = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$d = $res->fetch_assoc();
$stmt->close();

if (!$d) {
    http_response_code(404);
    exit('<div style="padding:16px;color:#92400e;background:#fef3c7;border:1px solid #fde68a;border-radius:8px">Data tidak ditemukan.</div>');
}

// Data turunan & tampilan
$tglMulai    = fmtDateId($d['tgl_mulai'] ?? null);
$tglSelesai  = fmtDateId($d['tgl_selesai'] ?? null);
$wMulai      = fmtTime($d['waktu_mulai'] ?? null);
$wSelesai    = fmtTime($d['waktu_selesai'] ?? null);
$rentangHari = ($tglMulai === $tglSelesai || !$d['tgl_selesai'])
             ? $tglMulai
             : $tglMulai . ' → ' . $tglSelesai;
$rentangJam  = ($wMulai && $wSelesai && $wMulai !== '—' && $wSelesai !== '—')
             ? $wMulai . ' → ' . $wSelesai
             : ($wMulai ?: '—');

// Multi PIC PEMINJAM
$picNamesRaw = trim((string)($d['pic_nama'] ?? ''));
$picWarsRaw  = trim((string)($d['pic_wa']   ?? ''));

$picNames = $picNamesRaw !== '' ? preg_split('/\s*,\s*|\s*;\s*|\s*\/\s*|\n|\r/u', $picNamesRaw, -1, PREG_SPLIT_NO_EMPTY) : [];
$picNums  = splitContacts($picWarsRaw);

$picPairs = [];
if ($picNums) {
    $nNums  = count($picNums);
    $nNames = count($picNames);
    for ($i=0; $i<$nNums; $i++) {
        $nm = ($nNames === $nNums) ? ($picNames[$i] ?? '') : ($picNamesRaw ?: '');
        $nm = trim($nm);
        if ($nm === '' && $nNums > 1) $nm = "PIC #".($i+1);
        $picPairs[] = ['name'=>$nm ?: 'PIC', 'phone'=>$picNums[$i]];
    }
}

$waBaseMsg = "Halo, saya menindaklanjuti peminjaman gedung.\n"
           . "ID: {$d['id_pinjam']}\n"
           . "Nama Peminjam: {$d['nama_lengkap']}\n"
           . "Gedung: {$d['nama_barang']}\n"
           . "Jadwal: {$rentangHari}, {$rentangJam}";

$layoutImg = !empty($d['layout']) 
           ? "../user/peminjaman/barang/layout/".basename($d['layout'])
           : null;

// PIC PERLENGKAPAN
$picMapping = [
    'meja'      => boolLabel($d['meja'] ?? ''),
    'kursi'     => boolLabel($d['kursi'] ?? ''),
    'sound'     => boolLabel($d['sound'] ?? ''),
    'proyektor' => boolLabel($d['proyektor'] ?? '')
];

$picTypes = [];
foreach ($picMapping as $jenis => $needed) {
    if ($needed) $picTypes[] = $jenis;
}

$neededPICs = [];

if (!empty($picTypes)) {
    $placeholders = implode(',', array_fill(0, count($picTypes), '?'));
    $sqlPIC = "SELECT nama_pic, jenis_pic, no_whatsapp 
               FROM pic_kontak 
               WHERE jenis_pic IN ($placeholders) 
                 AND status = 'aktif'";
    $stmtPIC = $conn->prepare($sqlPIC);
    $typesStr = str_repeat('s', count($picTypes));
    $stmtPIC->bind_param($typesStr, ...$picTypes);
    $stmtPIC->execute();
    $resPIC = $stmtPIC->get_result();

    while ($pic = $resPIC->fetch_assoc()) {
        $neededPICs[] = [
            'name'  => (string)$pic['nama_pic'],
            'type'  => (string)$pic['jenis_pic'],
            'phone' => (string)$pic['no_whatsapp']
        ];
    }
    $stmtPIC->close();
}

// MERGE PIC
$mergedPICs = [];
foreach ($neededPICs as $p) {
    $key = phoneDigits($p['phone']);
    if ($key === '') $key = strtolower(trim($p['name']));
    if ($key === '') continue;
    if (!isset($mergedPICs[$key])) {
        $mergedPICs[$key] = [
            'name'   => $p['name'],
            'phone'  => $p['phone'],
            'types'  => [],
        ];
    }
    if (!in_array($p['type'], $mergedPICs[$key]['types'], true)) {
        $mergedPICs[$key]['types'][] = $p['type'];
    }
}
$neededPICs = array_values($mergedPICs);

$priority = ['meja'=>1,'kursi'=>2,'sound'=>3,'proyektor'=>4];
usort($neededPICs, function($a,$b) use($priority){
    $pa = min(array_map(fn($t)=>$priority[$t]??9, $a['types']));
    $pb = min(array_map(fn($t)=>$priority[$t]??9, $b['types']));
    return $pa <=> $pb;
});

$fitur = [
    ['label'=>'Meja',        'enabled'=>boolLabel($d['meja'] ?? '') , 'qty'=>$d['jumlah_meja'] ?? ''],
    ['label'=>'Kursi',       'enabled'=>boolLabel($d['kursi'] ?? ''), 'qty'=>$d['jumlah_kursi'] ?? ''],
    ['label'=>'Sound System','enabled'=>boolLabel($d['sound'] ?? '')],
    ['label'=>'Proyektor',   'enabled'=>boolLabel($d['proyektor'] ?? '')],
];

?>

<!-- Reset CSS untuk Detail Gedung -->
<style>
/* ========== RESET INHERITANCE ========== */
.page-inner .detail-gedung-content {
  all: revert !important;
}

.detail-gedung-content,
.detail-gedung-content * {
  box-sizing: border-box !important;
  min-width: 0 !important; /* Fix grid/flex overflow */
}

/* Reset elemen utama */
.detail-gedung-content .page-header,
.detail-gedung-content .card,
.detail-gedung-content .card-header,
.detail-gedung-content .card-body,
.detail-gedung-content .row {
  margin: revert !important;
  padding: revert !important;
}

/* ========== SCOPED CUSTOM PROPERTIES ========== */
.detail-gedung-content {
  --dg-primary: #3b82f6;
  --dg-success: #22c55e;
  --dg-danger: #ef4444;
  --dg-warning: #f59e0b;
  --dg-info: #06b6d4;
  --dg-secondary: #6b7280;
  --dg-text: #0f172a;
  --dg-muted: #64748b;
  --dg-border: #eef2f7;
  --dg-bg: #f8fafc;
  --dg-shadow: 0 8px 24px rgba(0,0,0,.06);
}

/* ========== LAYOUT ========== */
.detail-gedung-content .page-header {
  display: flex !important;
  justify-content: space-between !important;
  align-items: flex-start !important;
  margin-bottom: 25px !important;
  padding-bottom: 15px !important;
  border-bottom: 2px solid var(--dg-border) !important;
}

.detail-gedung-content .page-header .page-title {
  font-size: 1.75rem !important;
  font-weight: 700 !important;
  color: var(--dg-text) !important;
  margin: 0 !important;
}

.detail-gedung-content .breadcrumbs {
  list-style: none !important;
  padding: 0 !important;
  margin: 0 !important;
  display: flex !important;
  gap: 8px !important;
  align-items: center !important;
  font-size: 0.9rem !important;
  color: var(--dg-muted) !important;
}

.detail-gedung-content .breadcrumbs li {
  display: flex !important;
  align-items: center !important;
}

.detail-gedung-content .breadcrumbs li.separator {
  margin: 0 5px !important;
}

.detail-gedung-content .breadcrumbs a {
  color: var(--dg-primary) !important;
  text-decoration: none !important;
}

.detail-gedung-content .breadcrumbs a:hover {
  text-decoration: underline !important;
}

.detail-gedung-content .row {
  display: flex !important;
  flex-wrap: wrap !important;
  margin: 0 -15px !important;
}

.detail-gedung-content .col-md-12 {
  flex: 0 0 100% !important;
  max-width: 100% !important;
  padding: 0 15px !important;
}

.detail-gedung-content .card {
  border: 0 !important;
  box-shadow: var(--dg-shadow) !important;
  border-radius: 14px !important;
  background: #fff !important;
  margin-bottom: 20px !important;
}

.detail-gedung-content .card-header {
  border-bottom: 1px solid var(--dg-border) !important;
  padding: 20px !important;
  background: #fff !important;
}

.detail-gedung-content .card-header .card-title {
  font-size: 1.3rem !important;
  font-weight: 700 !important;
  margin: 0 0 8px 0 !important;
  color: var(--dg-text) !important;
}

.detail-gedung-content .card-body {
  padding: 20px !important;
}

.detail-gedung-content .d-flex {
  display: flex !important;
}

.detail-gedung-content .align-items-center {
  align-items: center !important;
}

.detail-gedung-content .justify-content-between {
  justify-content: space-between !important;
}

.detail-gedung-content .mb-1 {
  margin-bottom: 8px !important;
}

/* ========== GRID LAYOUT ========== */
.detail-gedung-content .grid-2 {
  display: grid !important;
  grid-template-columns: 1.1fr 0.9fr !important;
  gap: 16px !important;
}

@media (max-width: 992px) {
  .detail-gedung-content .grid-2 {
    grid-template-columns: 1fr !important;
  }
}

/* ========== SUMMARY ========== */
.detail-gedung-content .summary {
  display: grid !important;
  grid-template-columns: repeat(4, 1fr) !important;
  gap: 12px !important;
  margin-bottom: 20px !important;
}

@media (max-width: 992px) {
  .detail-gedung-content .summary {
    grid-template-columns: repeat(2, 1fr) !important;
  }
}

.detail-gedung-content .sum-item {
  background: var(--dg-bg) !important;
  border: 1px solid var(--dg-border) !important;
  border-radius: 12px !important;
  padding: 12px !important;
}

.detail-gedung-content .sum-item .t {
  font-size: 12px !important;
  color: var(--dg-secondary) !important;
  text-transform: uppercase !important;
  letter-spacing: 0.06em !important;
}

.detail-gedung-content .sum-item .v {
  font-weight: 700 !important;
  font-size: 16px !important;
  margin-top: 6px !important;
  color: var(--dg-text) !important;
}

/* ========== SECTION TITLE ========== */
.detail-gedung-content .section-title {
  font-size: 14px !important;
  color: var(--dg-muted) !important;
  margin: 0 0 12px 0 !important;
  text-transform: uppercase !important;
  letter-spacing: 0.06em !important;
  font-weight: 600 !important;
}

/* ========== ACTIONS ========== */
.detail-gedung-content .actions {
  display: flex !important;
  flex-wrap: wrap !important;
  gap: 10px !important;
  position: sticky !important;
  top: 12px !important;
  z-index: 5 !important;
}

.detail-gedung-content .btn {
  display: inline-flex !important;
  align-items: center !important;
  gap: 6px !important;
  padding: 8px 14px !important;
  font-size: 0.875rem !important;
  font-weight: 600 !important;
  border: none !important;
  border-radius: 10px !important;
  cursor: pointer !important;
  text-decoration: none !important;
  transition: all 0.2s ease !important;
  white-space: nowrap !important;
}

.detail-gedung-content .btn-sm {
  padding: 6px 12px !important;
  font-size: 0.8rem !important;
}

.detail-gedung-content .btn-success {
  background: var(--dg-success) !important;
  color: white !important;
}

.detail-gedung-content .btn-success:hover {
  background: #16a34a !important;
  transform: translateY(-2px) !important;
}

.detail-gedung-content .btn-primary {
  background: var(--dg-primary) !important;
  color: white !important;
}

.detail-gedung-content .btn-primary:hover {
  background: #2563eb !important;
  transform: translateY(-2px) !important;
}

.detail-gedung-content .btn-secondary {
  background: var(--dg-secondary) !important;
  color: white !important;
}

.detail-gedung-content .btn-secondary:hover {
  background: #4b5563 !important;
  transform: translateY(-2px) !important;
}

/* ========== BADGES ========== */
.detail-gedung-content .badge {
  display: inline-block !important;
  padding: 6px 12px !important;
  font-size: 0.8rem !important;
  font-weight: 600 !important;
  border-radius: 6px !important;
}

.detail-gedung-content .badge-success {
  background: #f0fdf4 !important;
  color: #166534 !important;
  border: 1px solid #86efac !important;
}

.detail-gedung-content .badge-warning {
  background: #fffbeb !important;
  color: #92400e !important;
  border: 1px solid #fcd34d !important;
}

.detail-gedung-content .badge-danger {
  background: #fef2f2 !important;
  color: #991b1b !important;
  border: 1px solid #fca5a5 !important;
}

.detail-gedung-content .badge-info {
  background: #ecfeff !important;
  color: #155e75 !important;
  border: 1px solid #67e8f9 !important;
}

.detail-gedung-content .badge-secondary {
  background: #f1f5f9 !important;
  color: #475569 !important;
  border: 1px solid #cbd5e1 !important;
}

/* ========== TABLE ========== */
.detail-gedung-content .table-responsive {
  overflow-x: auto !important;
  -webkit-overflow-scrolling: touch !important;
}

.detail-gedung-content .table-clean {
  width: 100% !important;
  border-collapse: collapse !important;
}

.detail-gedung-content .table-clean tr td {
  padding: 10px 8px !important;
  vertical-align: top !important;
  border-bottom: 1px dashed var(--dg-border) !important;
  font-size: 0.9rem !important;
}

.detail-gedung-content .table-clean tr td:first-child {
  color: var(--dg-muted) !important;
  width: 180px !important;
  font-weight: 500 !important;
}

/* ========== CHIPS ========== */
.detail-gedung-content .chips {
  display: flex !important;
  flex-wrap: wrap !important;
  gap: 8px !important;
}

.detail-gedung-content .chip {
  display: inline-flex !important;
  align-items: center !important;
  gap: 8px !important;
  padding: 8px 12px !important;
  border-radius: 999px !important;
  border: 1px solid var(--dg-border) !important;
  background: #fff !important;
  font-size: 0.85rem !important;
}

.detail-gedung-content .chip b {
  font-weight: 600 !important;
  color: var(--dg-text) !important;
}

.detail-gedung-content .chip .qty {
  font-size: 12px !important;
  color: var(--dg-muted) !important;
}

.detail-gedung-content .chip a {
  color: var(--dg-primary) !important;
  text-decoration: none !important;
}

.detail-gedung-content .chip a:hover {
  text-decoration: underline !important;
}

.detail-gedung-content .copy-btn {
  border: 1px solid var(--dg-border) !important;
  background: #fff !important;
  border-radius: 6px !important;
  padding: 4px 8px !important;
  font-size: 11px !important;
  cursor: pointer !important;
  transition: all 0.2s ease !important;
}

.detail-gedung-content .copy-btn:hover {
  background: var(--dg-bg) !important;
}

/* ========== PIC SECTION ========== */
.detail-gedung-content .pic-section {
  margin-top: 20px !important;
  padding: 16px !important;
  background: var(--dg-bg) !important;
  border: 1px solid var(--dg-border) !important;
  border-radius: 12px !important;
}

.detail-gedung-content .pic-grid {
  display: grid !important;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)) !important;
  gap: 12px !important;
  margin-top: 12px !important;
}

.detail-gedung-content .pic-card {
  background: #fff !important;
  border: 1px solid var(--dg-border) !important;
  border-radius: 10px !important;
  padding: 12px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: space-between !important;
}

.detail-gedung-content .pic-info .pic-name {
  font-weight: 600 !important;
  color: var(--dg-text) !important;
  font-size: 0.95rem !important;
}

.detail-gedung-content .pic-info .pic-type {
  font-size: 12px !important;
  color: var(--dg-muted) !important;
  text-transform: capitalize !important;
  margin-top: 2px !important;
}

/* ========== LAYOUT BOX ========== */
.detail-gedung-content .layout-box {
  border: 1px solid var(--dg-border) !important;
  border-radius: 12px !important;
  overflow: hidden !important;
  background: #fff !important;
}

.detail-gedung-content .layout-box img {
  display: block !important;
  width: 100% !important;
  height: auto !important;
  cursor: pointer !important;
  transition: transform 0.3s ease !important;
}

.detail-gedung-content .layout-box img:hover {
  transform: scale(1.02) !important;
}

.detail-gedung-content .empty {
  padding: 20px !important;
  color: var(--dg-muted) !important;
  background: var(--dg-bg) !important;
  border: 1px dashed #cbd5e1 !important;
  border-radius: 10px !important;
  text-align: center !important;
  font-size: 0.9rem !important;
}

/* ========== LIGHTBOX ========== */
.detail-gedung-content #lb-backdrop {
  position: fixed !important;
  inset: 0 !important;
  background: rgba(15,23,42,0.85) !important;
  display: none !important;
  align-items: center !important;
  justify-content: center !important;
  z-index: 9999 !important;
  backdrop-filter: blur(4px) !important;
}

.detail-gedung-content #lb-backdrop img {
  max-width: min(96vw, 1400px) !important;
  max-height: 90vh !important;
  border-radius: 12px !important;
  box-shadow: 0 20px 60px rgba(0,0,0,0.6) !important;
}

/* ========== FOOTER ========== */
.detail-gedung-content .footer-mini {
  margin: 20px 0 10px 0 !important;
  text-align: center !important;
  color: var(--dg-secondary) !important;
  font-size: 0.9rem !important;
}

/* ========== RESPONSIVE ========== */
@media (max-width: 768px) {
  .detail-gedung-content .page-header {
    flex-direction: column !important;
    gap: 10px !important;
  }

  .detail-gedung-content .actions {
    position: static !important;
    width: 100% !important;
  }

  .detail-gedung-content .btn-sm {
    padding: 5px 10px !important;
    font-size: 0.75rem !important;
  }

  .detail-gedung-content .table-clean tr td:first-child {
    width: 120px !important;
  }
}
</style>

<!-- Wrapper untuk Isolasi CSS -->
<div class="detail-gedung-content">
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <h4 class="card-title mb-1"><?= safe($d['nama_barang']) ?></h4>
              <div>Status: <?= statusBadge($d['status'] ?? '') ?></div>
            </div>
            <div class="actions">
              <?php if (!empty($picPairs)): ?>
                <?php foreach ($picPairs as $i => $pp): 
                  $url = waLink($pp['phone'], $waBaseMsg);
                  $label = trim($pp['name']) !== '' ? ("WA ". $pp['name']) : ("WA PIC #".($i+1));
                ?>
                  <a class="btn btn-success btn-sm" target="_blank" rel="noopener noreferrer" href="<?= safe($url) ?>">
                    <i class="fa fa-whatsapp"></i> <?= safe($label) ?>
                  </a>
                <?php endforeach; ?>
              <?php endif; ?>
              
              <a class="btn btn-secondary btn-sm" href="javascript:window.history.back()">
                <i class="fa fa-arrow-left"></i> Kembali
              </a>
              <a href="./peminjaman/cetakdetail.php?id=<?= (int)$d['id_pinjam'] ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm">
                <i class="fa fa-print"></i> Print
              </a>
            </div>
          </div>
        </div>

        <div class="card-body">
          <!-- Summary -->
          <div class="summary">
            <div class="sum-item">
              <div class="t">ID Pinjam</div>
              <div class="v"><?= (int)$d['id_pinjam'] ?></div>
            </div>
            <div class="sum-item">
              <div class="t">Peminjam</div>
              <div class="v"><?= safe($d['nama_lengkap']) ?></div>
            </div>
            <div class="sum-item">
              <div class="t">Jadwal</div>
              <div class="v"><?= safe($rentangHari) ?></div>
            </div>
            <div class="sum-item">
              <div class="t">Waktu</div>
              <div class="v"><?= safe($rentangJam) ?> WIB</div>
            </div>
          </div>

          <div class="grid-2">
            <!-- Kolom kiri -->
            <div>
              <h6 class="section-title">Detail Peminjaman</h6>
              <div class="table-responsive">
                <table class="table-clean">
                  <tr><td>Nama Peminjam</td><td>:</td><td><?= safe($d['nama_lengkap']) ?></td></tr>

                  <tr>
                    <td>Nama PIC</td><td>:</td>
                    <td>
                      <?php if (!empty($picPairs)): ?>
                        <div class="chips">
                          <?php foreach ($picPairs as $pp): ?>
                            <div class="chip">
                              <b><?= safe($pp['name']) ?></b>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      <?php else: ?>
                        <?= $picNamesRaw !== '' ? safe($picNamesRaw) : '—' ?>
                      <?php endif; ?>
                    </td>
                  </tr>

                  <tr>
                    <td>Kontak WA PIC</td><td>:</td>
                    <td>
                      <?php if (!empty($picPairs)): ?>
                        <div class="chips">
                          <?php foreach ($picPairs as $pp): 
                            $pd = phoneDigits($pp['phone']);
                            $url = waLink($pp['phone'], $waBaseMsg);
                          ?>
                            <div class="chip">
                              <a href="<?= safe($url) ?>" target="_blank" rel="noopener noreferrer">
                                <?= safe($pd) ?>
                              </a>
                              <button class="copy-btn" data-copy="<?= safe($pd) ?>">Copy</button>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      <?php else: ?>
                        —
                      <?php endif; ?>
                    </td>
                  </tr>

                  <tr><td>Nama Gedung</td><td>:</td><td><?= safe($d['nama_barang']) ?></td></tr>
                  <tr><td>Tanggal</td><td>:</td><td><?= safe($rentangHari) ?></td></tr>
                  <tr><td>Waktu</td><td>:</td><td><?= safe($rentangJam) ?> WIB</td></tr>
                  <tr><td>Status</td><td>:</td><td><?= statusBadge($d['status'] ?? '') ?></td></tr>
                  <tr><td>Tujuan</td><td>:</td><td><?= nl2br(safe($d['tujuan_barang'] ?? '—')) ?></td></tr>
                  <tr><td>Keterangan</td><td>:</td><td><?= nl2br(safe($d['ket'] ?? '—')) ?></td></tr>
                </table>
              </div>

              <h6 class="section-title" style="margin-top:18px">Kebutuhan Perlengkapan</h6>
              <div class="chips">
                <?php foreach ($fitur as $f): ?>
                  <?php if ($f['enabled']): ?>
                    <div class="chip">
                      <b><?= safe($f['label']) ?></b>
                      <?php if (!empty($f['qty'])): ?>
                        <span class="qty">x <?= (int)$f['qty'] ?></span>
                      <?php endif; ?>
                    </div>
                  <?php else: ?>
                    <div class="chip" style="opacity:.55">
                      <b><?= safe($f['label']) ?></b>
                      <span class="qty">Tidak</span>
                    </div>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>

              <!-- PIC Perlengkapan -->
              <?php if (!empty($neededPICs)): ?>
                <div class="pic-section">
                  <h6 class="section-title" style="margin:0 0 12px 0">PIC Perlengkapan yang Terlibat</h6>
                  <div class="pic-grid">
                    <?php
                    $labelMapping = [
                      'meja'      => 'Meja',
                      'kursi'     => 'Kursi',
                      'sound'     => 'Sound System',
                      'proyektor' => 'Proyektor'
                    ];
                    foreach ($neededPICs as $pic):
                      $labels = [];
                      foreach ($pic['types'] as $t) {
                        $labels[] = $labelMapping[$t] ?? ucfirst($t);
                      }
                      $picTypeLabel = implode(' + ', $labels);

                      $items = [];
                      if (in_array('meja', $pic['types'], true) && boolLabel($d['meja'] ?? '')) {
                        $items[] = 'Meja' . (!empty($d['jumlah_meja']) ? ' ('.$d['jumlah_meja'].' unit)' : '');
                      }
                      if (in_array('kursi', $pic['types'], true) && boolLabel($d['kursi'] ?? '')) {
                        $items[] = 'Kursi' . (!empty($d['jumlah_kursi']) ? ' ('.$d['jumlah_kursi'].' unit)' : '');
                      }
                      if (in_array('sound', $pic['types'], true) && boolLabel($d['sound'] ?? '')) {
                        $items[] = 'Sound System';
                      }
                      if (in_array('proyektor', $pic['types'], true) && boolLabel($d['proyektor'] ?? '')) {
                        $items[] = 'Proyektor';
                      }
                      $perlengkapanLine = !empty($items) ? implode(', ', $items) : $picTypeLabel;

                      $picMsg = "Halo {$pic['name']},\n\n"
                              . "Saya menghubungi Anda terkait peminjaman gedung dengan kebutuhan perlengkapan berikut:\n"
                              . "{$perlengkapanLine}\n\n"
                              . "Detail Peminjaman:\n"
                              . "ID: {$d['id_pinjam']}\n"
                              . "Peminjam: {$d['nama_lengkap']}\n"
                              . "Gedung: {$d['nama_barang']}\n"
                              . "Jadwal: {$rentangHari}\n"
                              . "Waktu: {$rentangJam} WIB\n\n"
                              . "Mohon konfirmasinya. Terima kasih.";
                      $waUrl = waLink($pic['phone'], $picMsg);
                      $phoneClean = phoneDigits($pic['phone']);
                    ?>
                      <div class="pic-card">
                        <div class="pic-info">
                          <div class="pic-name"><?= safe($pic['name']) ?></div>
                          <div class="pic-type"><?= safe($picTypeLabel) ?></div>
                          <div style="font-size:12px;color:#6b7280;margin-top:4px">
                            <?= safe($phoneClean) ?>
                            <button class="copy-btn" data-copy="<?= safe($phoneClean) ?>" style="margin-left:6px">Copy</button>
                          </div>
                        </div>
                        <div>
                          <a class="btn btn-success btn-sm" target="_blank" rel="noopener noreferrer" href="<?= safe($waUrl) ?>">
                            <i class="fa fa-whatsapp"></i> Chat
                          </a>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>
            </div>

            <!-- Kolom kanan: Layout -->
            <div>
              <h6 class="section-title">Layout Penataan</h6>
              <?php if ($layoutImg && file_exists($layoutImg)): ?>
                <div class="layout-box">
                  <img id="layout-preview" src="<?= safe($layoutImg) ?>" alt="Layout" loading="lazy">
                </div>
              <?php else: ?>
                <div class="empty">Belum ada layout yang diunggah.</div>
              <?php endif; ?>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <div class="footer-mini">&copy; <?= date('Y') ?> | SIPINJAM</div>
</div>


<script>
(function(){
  // Copy nomor
  document.querySelectorAll('.copy-btn').forEach(btn=>{
    btn.addEventListener('click', async ()=>{
      const v = btn.getAttribute('data-copy') || '';
      try{
        await navigator.clipboard.writeText(v);
        btn.textContent = 'Tersalin';
        setTimeout(()=>btn.textContent='Copy', 1500);
      }catch(e){ alert('Gagal menyalin'); }
    })
  });

  // Lightbox
  const img = document.getElementById('layout-preview');
  const lb  = document.getElementById('lb-backdrop');
  if(img && lb){
    const lbImg = lb.querySelector('img');
    img.addEventListener('click', ()=>{
      lbImg.src = img.src;
      lb.style.display = 'flex';
    });
    lb.addEventListener('click', (e)=>{
      if(e.target === lb) lb.style.display = 'none';
    });
    document.addEventListener('keydown', (e)=>{
      if(e.key==='Escape') lb.style.display='none';
    });
  }
})();
</script>
