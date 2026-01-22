<?php
if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/../cek.php';
require_once dirname(__DIR__, 2) . '/koneksi.php';
require_once dirname(__DIR__, 2) . '/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/* =========================
   Helpers
========================= */

/** ganti "-" jadi non-breaking hyphen dan format dd-mm-YYYY */
function formatDateNoWrap($ymd) {
    if (empty($ymd) || $ymd === '0000-00-00') return '';
    $dt = DateTime::createFromFormat('Y-m-d', $ymd);
    if (!$dt) return htmlspecialchars($ymd, ENT_QUOTES, 'UTF-8');
    $s = $dt->format('d-m-Y');
    $nbh = "\xE2\x80\x91"; // U+2011
    return str_replace('-', $nbh, $s);
}

/** Waktu HH:MM:SS -> HH:MM */
function formatTime($timeStr) {
    if (!$timeStr) return '';
    if (preg_match('/^\d{2}:\d{2}/', $timeStr, $m)) return $m[0];
    return htmlspecialchars($timeStr, ENT_QUOTES, 'UTF-8');
}

/** Map angka 1–7 ke nama hari Indonesia */
function mapHariByNumber($nums) {
    $map = [
        1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
        4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
    ];
    $out = [];
    foreach ($nums as $n) {
        $n = (int)$n;
        if (isset($map[$n])) $out[] = $map[$n];
    }
    usort($out, function($a,$b) use ($map){
        return array_search($a, $map) <=> array_search($b, $map);
    });
    return $out;
}

/** Parse kolom recurring_days */
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

/** Nama hari Indonesia dari Y-m-d */
function hariIndo($ymd) {
    $map = [
        'Sun' => 'Minggu', 'Mon' => 'Senin', 'Tue' => 'Selasa',
        'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu',
    ];
    $dt = DateTime::createFromFormat('Y-m-d', $ymd);
    if (!$dt) return '';
    return $map[$dt->format('D')] ?? '';
}

/** Extrak hanya Meja, Kursi, Proyektor */
function formatPerlengkapanUtama($d) {
    $items = [];
    
    if (strtolower($d['meja']) === 'iya') {
        $jml = trim($d['jumlah_meja']);
        $items[] = $jml && $jml !== '0' && $jml !== '' ? "Meja ({$jml})" : "Meja";
    }
    
    if (strtolower($d['kursi']) === 'iya') {
        $jml = trim($d['jumlah_kursi']);
        $items[] = $jml && $jml !== '0' && $jml !== '' ? "Kursi ({$jml})" : "Kursi";
    }
    
    if (strtolower($d['proyektor']) === 'iya') {
        $items[] = "Proyektor";
    }
    
    return empty($items) ? '-' : implode(', ', $items);
}

/** call_user_func_array bind helper */
function refValues($arr){
    $refs = [];
    foreach($arr as $k=>$v){ $refs[$k] = &$arr[$k]; }
    return $refs;
}

/* =========================
   Ambil filter
========================= */
$keyword     = isset($_GET['q']) ? trim($_GET['q']) : '';
$status_f    = isset($_GET['status']) ? trim($_GET['status']) : '';
$dari        = isset($_GET['dari']) ? trim($_GET['dari']) : '';
$sampai      = isset($_GET['sampai']) ? trim($_GET['sampai']) : '';
$id_barang_f = isset($_GET['id_barang']) ? trim($_GET['id_barang']) : '';
$id_user_f   = isset($_GET['id_user']) ? trim($_GET['id_user']) : '';

// AMBIL PARAMETER SORT
$sort_by     = isset($_GET['sort_by']) ? trim($_GET['sort_by']) : 'gedung';

/* =========================
   Query data - WITH LEFT JOIN USER
========================= */
$sql = "SELECT p.*, 
               b.nama_barang,
               COALESCE(u.nama_lengkap, p.nama) AS nama_lengkap_user,
               COALESCE(p.is_recurring, '')      AS is_recurring,
               COALESCE(p.recurring_days, '')    AS recurring_days
        FROM pinjambarang p
        INNER JOIN barang b ON b.id = p.id_barang
        LEFT JOIN user u ON u.id = p.id_user
        WHERE 1=1";
$params = [];
$types  = "";

if ($keyword !== '')       { $sql .= " AND (p.nama LIKE ? OR b.nama_barang LIKE ?)"; $kw = "%{$keyword}%"; $params[]=&$kw; $params[]=&$kw; $types.="ss"; }
if ($status_f !== '')      { $sql .= " AND p.status = ?";                                    $params[]=&$status_f;          $types.="s"; }
if ($dari !== '')          { $sql .= " AND p.tgl_mulai >= ?";                                $params[]=&$dari;               $types.="s"; }
if ($sampai !== '')        { $sql .= " AND p.tgl_mulai <= ?";                                $params[]=&$sampai;             $types.="s"; }
if ($id_barang_f !== '')   { $sql .= " AND p.id_barang = ?";                                 $params[]=&$id_barang_f;        $types.="s"; }
if ($id_user_f !== '')     { $sql .= " AND p.id_user = ?";                                   $params[]=&$id_user_f;          $types.="s"; }

// ORDER BY DINAMIS BERDASARKAN PILIHAN SORT
if ($sort_by === 'tanggal') {
    // Urutkan berdasarkan tanggal (kronologis)
    $sql .= " ORDER BY p.tgl_mulai ASC, p.waktu_mulai ASC, b.nama_barang ASC";
    $subtitle = "Diurutkan berdasarkan Tanggal";
} else {
    // Urutkan berdasarkan gedung (default)
    $sql .= " ORDER BY b.nama_barang ASC, p.tgl_mulai ASC, p.waktu_mulai ASC";
    $subtitle = "Diurutkan berdasarkan Gedung";
}

$stmt = $conn->prepare($sql);
if ($types !== "") {
    $bind = [$types];
    foreach ($params as $p) { $bind[] = $p; }
    call_user_func_array([$stmt,'bind_param'], refValues($bind));
}
$stmt->execute();
$res = $stmt->get_result();

/* =========================
   Bangun tabel + fitur tanda
========================= */
$rows_html     = '';
$no            = 1;
$notes = [];
$haveFlagged = false;

// Untuk grouping gedung
$lastGedung = null;
$gedungCounter = 0;

while ($d = $res->fetch_assoc()) {

    // === Jika sort berdasarkan gedung, tambahkan separator ===
    if ($sort_by === 'gedung' && $lastGedung !== $d['nama_barang']) {
        if ($lastGedung !== null) {
            // Baris spacing antar gedung
            $rows_html .= '<tr><td colspan="8" style="height:8px; background:#f0f0f0; border:none;"></td></tr>';
        }
        
        // Header gedung
        $gedungCounter++;
        $rows_html .= '<tr style="background:#e3f2fd;">
            <td colspan="8" style="font-weight:bold; font-size:11px; padding:8px; text-align:left; border:1px solid #1976d2;">
                <span style="color:#1976d2;">■</span> '.htmlspecialchars($d['nama_barang'], ENT_QUOTES, 'UTF-8').'
            </td>
        </tr>';
        
        $lastGedung = $d['nama_barang'];
        $no = 1; // Reset nomor per gedung
    }

    // === Deteksi kegiatan berkelanjutan ===
    $isRecurring = (strtolower(trim($d['is_recurring'])) === 'yes');
    $recDaysNums = parseRecurringDays($d['recurring_days']);
    $recDaysNames = mapHariByNumber($recDaysNums);

    $hariRutin = '';
    if ($isRecurring) {
        $haveFlagged = true;
        
        if (empty($recDaysNames)) {
            $recDaysNames = [ hariIndo($d['tgl_mulai']) ];
        }

        $hariRutin = implode(', ', $recDaysNames);

        $notes[] = sprintf(
            '☑ %s — %s: kegiatan rutin setiap %s, periode %s s.d. %s',
            htmlspecialchars($d['nama_barang'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($d['nama_lengkap_user'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($hariRutin, ENT_QUOTES, 'UTF-8'),
            formatDateNoWrap($d['tgl_mulai']),
            formatDateNoWrap($d['tgl_selesai'])
        );
    }

    // Format kolom
    $jadwal = formatDateNoWrap($d['tgl_mulai']) . ' s.d. ' . formatDateNoWrap($d['tgl_selesai']);
    $waktu = formatTime($d['waktu_mulai']) . ' - ' . formatTime($d['waktu_selesai']);
    
    // Jika sort by gedung, kolom gedung tidak perlu ditampilkan (sudah di header)
    // Jika sort by tanggal, tetap tampilkan kolom gedung
    $gedungDisplay = $sort_by === 'gedung' 
                    ? '' 
                    : '<td>'.htmlspecialchars($d['nama_barang'], ENT_QUOTES, 'UTF-8').'</td>';
    
    $perlengkapan = formatPerlengkapanUtama($d);
    $tujuan = !empty($d['tujuan_barang']) 
            ? htmlspecialchars($d['tujuan_barang'], ENT_QUOTES, 'UTF-8') 
            : '-';
    
    $pic_lengkap = htmlspecialchars($d['nama_lengkap_user'], ENT_QUOTES, 'UTF-8');
    $pic_nama = htmlspecialchars($d['nama'], ENT_QUOTES, 'UTF-8');
    $pic = $pic_lengkap . '<br><small>(' . $pic_nama . ')</small>';
    
    $hariRutinDisplay = $isRecurring 
                        ? htmlspecialchars($hariRutin, ENT_QUOTES, 'UTF-8')
                        : '-';

    // Baris data
    if ($sort_by === 'gedung') {
        // Tanpa kolom gedung
        $rows_html .= '<tr>
            <td style="text-align:center; width:4%;">'.($no++).'</td>
            <td class="nowrap" style="width:17%;">'.$jadwal.'</td>
            <td class="nowrap" style="width:12%;">'.$waktu.'</td>
            <td style="width:17%;">'.$perlengkapan.'</td>
            <td style="width:17%;">'.$tujuan.'</td>
            <td style="width:17%;">'.$pic.'</td>
            <td style="text-align:center; width:16%;">'.$hariRutinDisplay.'</td>
        </tr>';
    } else {
        // Dengan kolom gedung
        $rows_html .= '<tr>
            <td style="text-align:center; width:4%;">'.($no++).'</td>
            <td class="nowrap" style="width:15%;">'.$jadwal.'</td>
            <td class="nowrap" style="width:11%;">'.$waktu.'</td>
            <td style="width:13%;">'.htmlspecialchars($d['nama_barang'], ENT_QUOTES, 'UTF-8').'</td>
            <td style="width:15%;">'.$perlengkapan.'</td>
            <td style="width:15%;">'.$tujuan.'</td>
            <td style="width:13%;">'.$pic.'</td>
            <td style="text-align:center; width:14%;">'.$hariRutinDisplay.'</td>
        </tr>';
    }
}

/* =========================
   Footer
========================= */
$exporter = $_SESSION['username'] ?? 'Unknown';
$exporter_safe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $exporter);

date_default_timezone_set('Asia/Jakarta');
$printedOn = date('d-m-Y H:i:s');
$printedOnNoWrap = str_replace('-', "\xE2\x80\x91", $printedOn);

/* =========================
   HTML PDF
========================= */

// Catatan
$catatan_html = '';
if ($haveFlagged) {
    $notes_unique = array_values(array_unique($notes));
    $items = '';
    foreach ($notes_unique as $n) {
        $items .= '<li style="margin-bottom:5px;">'.$n.'</li>';
    }
    $catatan_html = '
      <div style="margin-top:15px; font-size:10px;">
        <strong>Catatan Jadwal Rutin:</strong>
        <ul style="margin-top:5px;">'.$items.'</ul>
      </div>
    ';
}

// Header tabel berbeda tergantung sort
if ($sort_by === 'gedung') {
    $table_header = '
      <tr>
        <th style="width:4%;">No</th>
        <th style="width:17%;">Jadwal</th>
        <th style="width:12%;">Waktu</th>
        <th style="width:17%;">Perlengkapan</th>
        <th style="width:17%;">Tujuan</th>
        <th style="width:17%;">PIC</th>
        <th style="width:16%;">Hari Rutin</th>
      </tr>';
} else {
    $table_header = '
      <tr>
        <th style="width:4%;">No</th>
        <th style="width:15%;">Jadwal</th>
        <th style="width:11%;">Waktu</th>
        <th style="width:13%;">Gedung</th>
        <th style="width:15%;">Perlengkapan</th>
        <th style="width:15%;">Tujuan</th>
        <th style="width:13%;">PIC</th>
        <th style="width:14%;">Hari Rutin</th>
      </tr>';
}

$html = '
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; }
    h2,h4 { margin: 0; padding: 0; }
    table { border-collapse: collapse; width: 100%; margin-top:10px; }
    th, td { border: 1px solid #444; padding: 6px; vertical-align: top; }
    th { background: #e8e8e8; font-weight: bold; text-align: center; }
    .nowrap { white-space: nowrap; }
    .footer { margin-top:15px; font-size:9px; text-align:right; }
    small { font-size: 8px; color: #666; }
    .subtitle { text-align:center; color:#666; font-size:9px; margin-top:3px; font-style:italic; }
  </style>
</head>
<body>
  <h2 style="text-align:center;">Laporan Peminjaman Gedung</h2>
  <h4 style="text-align:center;">SIPINJAM</h4>
  <div class="subtitle">'.$subtitle.'</div>
  <table>
    <thead>'.$table_header.'</thead>
    <tbody>'.$rows_html.'</tbody>
  </table>
  '.$catatan_html.'
  <div class="footer">
    Dicetak oleh: '.htmlspecialchars($exporter, ENT_QUOTES, 'UTF-8').'<br>
    Dicetak pada: <span class="nowrap">'.$printedOnNoWrap.'</span>
  </div>
</body>
</html>
';

/* =========================
   Render PDF
========================= */
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

if (ob_get_length()) { @ob_end_clean(); }

$sort_suffix = $sort_by === 'tanggal' ? 'tanggal' : 'gedung';
$filename = 'laporan_peminjaman_'.$sort_suffix.'_'.$exporter_safe.'_'.date('Ymd_His').'.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
exit;
