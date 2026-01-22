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


/* =========================
   Helpers
========================= */

/** format dd-mm-YYYY */
function formatDate($ymd) {
    if (empty($ymd) || $ymd === '0000-00-00') return '';
    $dt = DateTime::createFromFormat('Y-m-d', $ymd);
    if (!$dt) return $ymd;
    return $dt->format('d-m-Y');
}

/** Waktu HH:MM:SS -> HH:MM */
function formatTime($timeStr) {
    if (!$timeStr) return '';
    if (preg_match('/^\d{2}:\d{2}/', $timeStr, $m)) return $m[0];
    return $timeStr;
}

/** Jadwal smart: 1 hari tampil sekali, beda hari tampil range */
function formatJadwalSmart($tglMulai, $tglSelesai) {
    $fm = formatDate($tglMulai);
    $fs = formatDate($tglSelesai);
    if ($tglMulai === $tglSelesai || empty($tglSelesai) || $tglSelesai === '0000-00-00') {
        return $fm;
    }
    return $fm . ' s.d. ' . $fs;
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

/** Parse kolom recurring_days (isi angka 1–7) */
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
        'Wed' => 'Rabu',   'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu',
    ];
    $dt = DateTime::createFromFormat('Y-m-d', $ymd);
    if (!$dt) return '';
    return $map[$dt->format('D')] ?? '';
}

/** Clean text for Excel */
function cleanExcel($text) {
    $text = str_replace(["\r\n", "\r", "\n"], ' ', (string)$text);
    $text = str_replace(['"', "'"], '', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

/** bind_param helper */
function refValues($arr){
    $refs = [];
    foreach($arr as $k=>$v){ $refs[$k] = &$arr[$k]; }
    return $refs;
}


/* =========================
   Ambil filter
========================= */
$keyword  = isset($_GET['q'])      ? trim($_GET['q'])      : '';
$status_f = isset($_GET['status']) ? trim($_GET['status']) : '';
$dari     = isset($_GET['dari'])   ? trim($_GET['dari'])   : '';
$sampai   = isset($_GET['sampai']) ? trim($_GET['sampai']) : '';


/* =========================
   Query data kendaraan
========================= */
$sql = "SELECT pk.*,
               u.nama_lengkap,
               k.nama_kendaraan,
               k.deskripsi,
               COALESCE(pk.is_recurring, 'no')  AS is_recurring,
               COALESCE(pk.recurring_days, '')  AS recurring_days
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
if ($dari !== '') {
    $sql .= " AND pk.tgl_mulai >= ?";
    $params[] = &$dari; $types .= "s";
}
if ($sampai !== '') {
    $sql .= " AND pk.tgl_mulai <= ?";
    $params[] = &$sampai; $types .= "s";
}

$sql .= " ORDER BY pk.tgl_mulai ASC, pk.waktu_mulai ASC";

$stmt = $conn->prepare($sql);
if ($types !== "") {
    $bind = [$types];
    foreach ($params as $p) { $bind[] = $p; }
    call_user_func_array([$stmt,'bind_param'], refValues($bind));
}
$stmt->execute();
$res = $stmt->get_result();


/* =========================
   Export to Excel (HTML Table)
========================= */
$exporter = $_SESSION['username'] ?? 'Unknown';
date_default_timezone_set('Asia/Jakarta');
$filename = 'laporan_peminjaman_kendaraan_' . date('Ymd_His') . '.xls';

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

echo "\xEF\xBB\xBF";

echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
echo '<head>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
echo '<!--[if gte mso 9]>';
echo '<xml>';
echo '<x:ExcelWorkbook>';
echo '<x:ExcelWorksheets>';
echo '<x:ExcelWorksheet>';
echo '<x:Name>Laporan Peminjaman Kendaraan</x:Name>';
echo '<x:WorksheetOptions>';
echo '<x:Print>';
echo '<x:ValidPrinterInfo/>';
echo '</x:Print>';
echo '</x:WorksheetOptions>';
echo '</x:ExcelWorksheet>';
echo '</x:ExcelWorksheets>';
echo '</x:ExcelWorkbook>';
echo '</xml>';
echo '<![endif]-->';
echo '<style>';
echo 'table { border-collapse: collapse; width: 100%; }';
echo 'th { background-color: #4472C4; color: white; font-weight: bold; border: 1px solid black; padding: 8px; text-align: center; }';
echo 'td { border: 1px solid black; padding: 6px; vertical-align: top; text-align: center; }';
echo '.nowrap { white-space: nowrap; }';
echo '</style>';
echo '</head>';
echo '<body>';

echo '<h2 style="text-align:center;">Laporan Peminjaman Kendaraan</h2>';
echo '<h4 style="text-align:center;">SIPINJAM</h4>';
echo '<br>';

echo '<table border="1">';
echo '<thead>';
echo '<tr>';
echo '<th style="width:40px;">No</th>';
echo '<th style="width:150px;">Jadwal</th>';
echo '<th style="width:100px;">Waktu</th>';
echo '<th style="width:150px;">Kendaraan</th>';
echo '<th style="width:150px;">Tujuan</th>';
echo '<th style="width:150px;">Peminjam / Bagian</th>';
echo '<th style="width:130px;">Pengemudi</th>';
echo '<th style="width:130px;">Hari Rutin</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

$no = 1;
while ($d = $res->fetch_assoc()) {
    $isRecurring  = (strtolower(trim($d['is_recurring'])) === 'yes');
    $recNums      = parseRecurringDays($d['recurring_days']);
    $recNames     = mapHariByNumber($recNums);

    if ($isRecurring) {
        if (empty($recNames) && !empty($d['tgl_mulai'])) {
            $recNames = [ hariIndo($d['tgl_mulai']) ];
        }
        $hariRutin = implode(', ', $recNames);
    } else {
        $hariRutin = '-';
    }

    $jadwal = formatJadwalSmart($d['tgl_mulai'], $d['tgl_selesai']);
    $waktu  = formatTime($d['waktu_mulai']) . ' - ' . formatTime($d['waktu_selesai']);

    $kend = cleanExcel($d['nama_kendaraan']);
    if (!empty($d['deskripsi'])) {
        $kend .= ' - ' . cleanExcel($d['deskripsi']);
    }

    $tujuan = !empty($d['tujuan']) ? cleanExcel($d['tujuan']) : '-';

    $peminjam = cleanExcel($d['nama_lengkap']);
    if (!empty($d['bagian'])) {
        $peminjam .= ' / ' . cleanExcel($d['bagian']);
    }

    $pengemudi = !empty($d['pengemudi']) ? cleanExcel($d['pengemudi']) : '-';

    echo '<tr>';
    echo '<td>' . $no++ . '</td>';
    echo '<td class="nowrap">' . htmlspecialchars($jadwal, ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td class="nowrap">' . htmlspecialchars($waktu, ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($kend, ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($tujuan, ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($peminjam, ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($pengemudi, ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($hariRutin, ENT_QUOTES, 'UTF-8') . '</td>';
    echo '</tr>';
}

echo '</tbody>';
echo '</table>';

echo '<br><br>';
echo '<p>Dicetak oleh: ' . htmlspecialchars($exporter, ENT_QUOTES, 'UTF-8') . '</p>';
echo '<p>Dicetak pada: ' . date('d-m-Y H:i:s') . '</p>';

echo '</body>';
echo '</html>';

exit;
