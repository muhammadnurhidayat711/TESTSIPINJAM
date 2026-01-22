<?php
// =========================
// Session & bootstrap
// =========================
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

/** dd-mm-YYYY dengan non-breaking hyphen */
function formatDateNoWrap($ymd) {
    if (empty($ymd) || $ymd === '0000-00-00') return '';
    $dt = DateTime::createFromFormat('Y-m-d', $ymd);
    if (!$dt) return htmlspecialchars($ymd, ENT_QUOTES, 'UTF-8');
    $s = $dt->format('d-m-Y');
    $nbh = "\xE2\x80\x91"; // U+2011
    return str_replace('-', $nbh, $s);
}

/** HH:MM:SS -> HH:MM */
function formatTimeShort($timeStr) {
    if (!$timeStr) return '';
    if (preg_match('/^\d{2}:\d{2}/', $timeStr, $m)) return $m[0];
    return htmlspecialchars($timeStr, ENT_QUOTES, 'UTF-8');
}

/** Format jadwal: jika 1 hari tampilkan sekali, jika beda tampilkan range */
function formatJadwalSmart($tglMulai, $tglSelesai) {
    $fmtMulai = formatDateNoWrap($tglMulai);
    $fmtSelesai = formatDateNoWrap($tglSelesai);
    
    // Jika tanggal sama atau selesai kosong = 1 hari saja
    if ($tglMulai === $tglSelesai || empty($tglSelesai) || $tglSelesai === '0000-00-00') {
        return $fmtMulai;
    }
    
    // Jika beda tanggal = range
    return $fmtMulai . ' s.d.<br>' . $fmtSelesai;
}

/** Map 1–7 ke nama hari Indonesia */
function mapHariByNumberKendaraan(array $nums) {
    $map = [
        1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
        4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
    ];
    $out = [];
    foreach ($nums as $n) {
        $n = (int)$n;
        if (isset($map[$n])) $out[] = $map[$n];
    }
    usort($out, function($a, $b) use ($map) {
        return array_search($a, $map, true) <=> array_search($b, $map, true);
    });
    return $out;
}

/** parse kolom recurring_days (isi angka 1–7) */
function parseRecurringDaysKendaraan(?string $str) {
    if (!$str) return [];
    if (preg_match_all('/[1-7]/', $str, $m)) {
        $nums = array_map('intval', $m[0]);
        $nums = array_values(array_unique($nums));
        sort($nums, SORT_NUMERIC);
        return $nums;
    }
    return [];
}

/** nama hari Indonesia dari Y-m-d */
function hariIndoKendaraan($ymd) {
    $map = [
        'Sun' => 'Minggu', 'Mon' => 'Senin', 'Tue' => 'Selasa',
        'Wed' => 'Rabu',   'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu',
    ];
    $dt = DateTime::createFromFormat('Y-m-d', $ymd);
    if (!$dt) return '';
    return $map[$dt->format('D')] ?? '';
}

/** helper bind_param */
function refValues($arr) {
    $refs = [];
    foreach ($arr as $k => $v) { $refs[$k] = &$arr[$k]; }
    return $refs;
}


/* =========================
   Ambil filter
========================= */
$keyword   = isset($_GET['q'])      ? trim($_GET['q'])      : '';
$status_f  = isset($_GET['status']) ? trim($_GET['status']) : '';
$dari      = isset($_GET['dari'])   ? trim($_GET['dari'])   : '';
$sampai    = isset($_GET['sampai']) ? trim($_GET['sampai']) : '';


/* =========================
   Query data peminjaman kendaraan
   URUTAN: tgl_mulai ASC, waktu_mulai ASC (terlama ke terbaru)
========================= */
$sql = "SELECT pk.*,
               u.nama_lengkap,
               k.nama_kendaraan,
               k.deskripsi,
               COALESCE(pk.is_recurring, 'no')   AS is_recurring,
               COALESCE(pk.recurring_days, '')   AS recurring_days
        FROM pinjamkendaraan pk
        INNER JOIN user u      ON u.id = pk.id_user
        INNER JOIN kendaraan k ON k.id_kendaraan = pk.id_kendaraan
        WHERE 1=1";

$params = [];
$types  = "";

if ($keyword !== '') {
    $sql .= " AND (u.nama_lengkap LIKE ? OR k.nama_kendaraan LIKE ?)";
    $kw = "%{$keyword}%";
    $params[] = &$kw; $params[] = &$kw; $types .= "ss";
}

if ($status_f !== '') {
    $sql .= " AND TRIM(LOWER(pk.status)) = ?";
    $st = strtolower($status_f);
    $params[] = &$st; $types .= "s";
}

if ($dari !== '' && $sampai !== '') {
    $sql .= " AND pk.tgl_mulai <= ?
              AND IFNULL(NULLIF(pk.tgl_selesai,''), pk.tgl_mulai) >= ?";
    $params[] = &$sampai; $types .= "s";
    $params[] = &$dari;   $types .= "s";
} elseif ($dari !== '' && $sampai === '') {
    $sql .= " AND IFNULL(NULLIF(pk.tgl_selesai,''), pk.tgl_mulai) >= ?";
    $params[] = &$dari; $types .= "s";
} elseif ($dari === '' && $sampai !== '') {
    $sql .= " AND pk.tgl_mulai <= ?";
    $params[] = &$sampai; $types .= "s";
}

// URUTAN: tanggal mulai ASC, jam mulai ASC (dari yang paling awal)
$sql .= " ORDER BY pk.tgl_mulai ASC, pk.waktu_mulai ASC";

$stmt = $conn->prepare($sql);
if ($types !== "") {
    $bind = [$types];
    foreach ($params as $p) { $bind[] = $p; }
    call_user_func_array([$stmt, 'bind_param'], refValues($bind));
}
$stmt->execute();
$res = $stmt->get_result();


/* =========================
   Bangun baris HTML + catatan rutin
========================= */
$rows_html   = '';
$notes       = [];
$haveFlagged = false;
$no          = 1;

while ($d = $res->fetch_assoc()) {

    $isRecurring   = (strtolower(trim($d['is_recurring'])) === 'yes');
    $recDaysNums   = parseRecurringDaysKendaraan($d['recurring_days']);
    $recDaysNames  = mapHariByNumberKendaraan($recDaysNums);

    if ($isRecurring) {
        $haveFlagged = true;

        if (empty($recDaysNames) && !empty($d['tgl_mulai'])) {
            $recDaysNames = [ hariIndoKendaraan($d['tgl_mulai']) ];
        }

        $hariRutinLabel = implode(', ', $recDaysNames);

        $notes[] = sprintf(
            '☑ %s — %s: pemakaian rutin setiap %s, periode %s s.d. %s',
            htmlspecialchars($d['nama_kendaraan'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($d['nama_lengkap'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($hariRutinLabel, ENT_QUOTES, 'UTF-8'),
            formatDateNoWrap($d['tgl_mulai']),
            formatDateNoWrap($d['tgl_selesai'])
        );
    } else {
        $hariRutinLabel = '-';
    }

    // Format kolom - JADWAL SMART (1 hari = tampil sekali, >1 hari = range)
    $jadwal = formatJadwalSmart($d['tgl_mulai'], $d['tgl_selesai']);
    $waktu  = formatTimeShort($d['waktu_mulai']) . ' - ' . formatTimeShort($d['waktu_selesai']);
    
    // Kendaraan: nama_kendaraan + deskripsi (dengan <br>)
    $kend = htmlspecialchars($d['nama_kendaraan'], ENT_QUOTES, 'UTF-8');
    if (!empty($d['deskripsi'])) {
        $kend .= '<br>' . htmlspecialchars($d['deskripsi'], ENT_QUOTES, 'UTF-8');
    }

    $tujuan = !empty($d['tujuan'])
        ? htmlspecialchars($d['tujuan'], ENT_QUOTES, 'UTF-8')
        : '-';

    // Peminjam: nama_lengkap + bagian (dengan <br>)
    $peminjam = htmlspecialchars($d['nama_lengkap'], ENT_QUOTES, 'UTF-8');
    if (!empty($d['bagian'])) {
        $peminjam .= '<br>' . htmlspecialchars($d['bagian'], ENT_QUOTES, 'UTF-8');
    }

    $pengemudi = !empty($d['pengemudi'])
        ? htmlspecialchars($d['pengemudi'], ENT_QUOTES, 'UTF-8')
        : '-';

    // SEMUA KOLOM RATA TENGAH
    $rows_html .= '
      <tr>
        <td>' . ($no++) . '</td>
        <td>' . $jadwal . '</td>
        <td>' . $waktu . '</td>
        <td>' . $kend . '</td>
        <td>' . $tujuan . '</td>
        <td>' . $peminjam . '</td>
        <td>' . $pengemudi . '</td>
        <td>' . $hariRutinLabel . '</td>
      </tr>';
}

$stmt->close();


/* =========================
   Footer & info pencetak
========================= */
$exporter      = $_SESSION['username'] ?? 'Unknown';
$exporter_safe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $exporter);

date_default_timezone_set('Asia/Jakarta');
$printedOn       = date('d-m-Y H:i:s');
$printedOnNoWrap = str_replace('-', "\xE2\x80\x91", $printedOn);


/* =========================
   Catatan jadwal rutin
========================= */
$catatan_html = '';
if ($haveFlagged) {
    $notes_unique = array_values(array_unique($notes));
    $items = '';
    foreach ($notes_unique as $n) {
        $items .= '<li style="margin-bottom:4px;">' . $n . '</li>';
    }
    $catatan_html = '
      <div style="margin-top:12px; font-size:9px;">
        <strong>Catatan Jadwal Rutin Kendaraan:</strong>
        <ul style="margin-top:4px; padding-left:18px;">' . $items . '</ul>
      </div>';
}


/* =========================
   HTML untuk Dompdf
========================= */
$html = '
<html>
<head>
  <meta charset="utf-8">
  <style>
    body  { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; }
    h2,h4 { margin: 0; padding: 0; }
    table { border-collapse: collapse; width: 100%; margin-top: 10px; }
    th,td { border: 1px solid #444; padding: 5px; vertical-align: middle; text-align: center; }
    th    { background: #e8e8e8; font-weight: bold; }
    .footer { margin-top: 10px; font-size: 8px; text-align: right; }
  </style>
</head>
<body>
  <h2 style="text-align:center;">Laporan Peminjaman Kendaraan</h2>
  <h4 style="text-align:center;">SIPINJAM</h4>

  <table>
    <thead>
      <tr>
        <th style="width:5%;">No</th>
        <th style="width:15%;">Jadwal</th>
        <th style="width:12%;">Waktu</th>
        <th style="width:18%;">Kendaraan</th>
        <th style="width:18%;">Tujuan</th>
        <th style="width:15%;">Peminjam / Bagian</th>
        <th style="width:12%;">Pengemudi</th>
        <th style="width:12%;">Hari Rutin</th>
      </tr>
    </thead>
    <tbody>' . $rows_html . '</tbody>
  </table>

  ' . $catatan_html . '

  <div class="footer">
    Dicetak oleh: ' . htmlspecialchars($exporter, ENT_QUOTES, 'UTF-8') . '<br>
    Dicetak pada: ' . $printedOnNoWrap . '
  </div>
</body>
</html>';


/* =========================
   Render & kirim PDF
========================= */
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

if (ob_get_length()) { @ob_end_clean(); }
$filename = 'laporan_peminjaman_kendaraan_' . $exporter_safe . '_' . date('Ymd_His') . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
exit;
