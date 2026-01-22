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
        $items[] = $jml && $jml !== '0' && $jml !== '' ? "Meja ($jml)" : "Meja";
    }
    
    if (strtolower($d['kursi']) === 'iya') {
        $jml = trim($d['jumlah_kursi']);
        $items[] = $jml && $jml !== '0' && $jml !== '' ? "Kursi ($jml)" : "Kursi";
    }
    
    if (strtolower($d['proyektor']) === 'iya') {
        $items[] = "Proyektor";
    }
    
    return empty($items) ? '-' : implode(', ', $items);
}

/** Clean text for Excel - hapus karakter yang bisa merusak format */
function cleanExcel($text) {
    $text = str_replace(["\r\n", "\r", "\n"], ' ', $text);
    $text = str_replace(['"', "'"], '', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
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
   Export to Excel (HTML Table Format)
========================= */
$exporter = $_SESSION['username'] ?? 'Unknown';
date_default_timezone_set('Asia/Jakarta');

$sort_suffix = $sort_by === 'tanggal' ? 'tanggal' : 'gedung';
$filename = 'laporan_peminjaman_' . $sort_suffix . '_' . date('Ymd_His') . '.xls';

// Set headers untuk download
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Output BOM untuk UTF-8
echo "\xEF\xBB\xBF";

// Start HTML Table (Excel dapat membaca HTML table)
echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
echo '<head>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
echo '<!--[if gte mso 9]>';
echo '<xml>';
echo '<x:ExcelWorkbook>';
echo '<x:ExcelWorksheets>';
echo '<x:ExcelWorksheet>';
echo '<x:Name>Laporan Peminjaman</x:Name>';
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
echo 'td { border: 1px solid black; padding: 6px; vertical-align: top; }';
echo '.center { text-align: center; }';
echo '.nowrap { white-space: nowrap; }';
echo '.gedung-header { background-color: #E3F2FD; font-weight: bold; color: #1976D2; border: 2px solid #1976D2; padding: 10px; text-align: left; }';
echo '</style>';
echo '</head>';
echo '<body>';

// Title
echo '<h2 style="text-align:center;">Laporan Peminjaman Gedung</h2>';
echo '<h4 style="text-align:center;">SIPINJAM</h4>';
echo '<p style="text-align:center; color:#666; font-style:italic;">' . htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') . '</p>';
echo '<br>';

// Table
echo '<table border="1">';

// Header tabel berbeda tergantung sort
if ($sort_by === 'gedung') {
    // Tanpa kolom gedung (ada di header grup)
    echo '<thead>';
    echo '<tr>';
    echo '<th style="width:40px;">No</th>';
    echo '<th style="width:160px;">Jadwal</th>';
    echo '<th style="width:100px;">Waktu</th>';
    echo '<th style="width:150px;">Perlengkapan</th>';
    echo '<th style="width:150px;">Tujuan</th>';
    echo '<th style="width:140px;">PIC</th>';
    echo '<th style="width:130px;">Hari Rutin</th>';
    echo '</tr>';
    echo '</thead>';
} else {
    // Dengan kolom gedung
    echo '<thead>';
    echo '<tr>';
    echo '<th style="width:40px;">No</th>';
    echo '<th style="width:150px;">Jadwal</th>';
    echo '<th style="width:100px;">Waktu</th>';
    echo '<th style="width:120px;">Gedung</th>';
    echo '<th style="width:140px;">Perlengkapan</th>';
    echo '<th style="width:150px;">Tujuan</th>';
    echo '<th style="width:130px;">PIC</th>';
    echo '<th style="width:130px;">Hari Rutin</th>';
    echo '</tr>';
    echo '</thead>';
}

echo '<tbody>';

$no = 1;
$lastGedung = null;

while ($d = $res->fetch_assoc()) {
    // === Jika sort berdasarkan gedung, tambahkan header gedung ===
    if ($sort_by === 'gedung' && $lastGedung !== $d['nama_barang']) {
        if ($lastGedung !== null) {
            // Baris spacing
            echo '<tr><td colspan="7" style="height:5px; background:#f5f5f5; border:none;"></td></tr>';
        }
        
        // Header gedung
        echo '<tr>';
        echo '<td colspan="7" class="gedung-header">';
        echo '■ ' . htmlspecialchars($d['nama_barang'], ENT_QUOTES, 'UTF-8');
        echo '</td>';
        echo '</tr>';
        
        $lastGedung = $d['nama_barang'];
        $no = 1; // Reset nomor per gedung
    }

    $isRecurring = (strtolower(trim($d['is_recurring'])) === 'yes');
    $recDaysNums = parseRecurringDays($d['recurring_days']);
    $recDaysNames = mapHariByNumber($recDaysNums);

    $hariRutin = '';
    if ($isRecurring) {
        if (empty($recDaysNames)) {
            $recDaysNames = [ hariIndo($d['tgl_mulai']) ];
        }
        $hariRutin = implode(', ', $recDaysNames);
    } else {
        $hariRutin = '-';
    }

    $jadwal = formatDate($d['tgl_mulai']) . ' s.d. ' . formatDate($d['tgl_selesai']);
    $waktu = formatTime($d['waktu_mulai']) . ' - ' . formatTime($d['waktu_selesai']);
    $gedung = cleanExcel($d['nama_barang']);
    $perlengkapan = cleanExcel(formatPerlengkapanUtama($d));
    $tujuan = !empty($d['tujuan_barang']) ? cleanExcel($d['tujuan_barang']) : '-';
    
    // PIC: Gabungkan nama_lengkap dari user dan nama dari pinjambarang
    $pic_lengkap = $d['nama_lengkap_user'];
    $pic_nama = cleanExcel($d['nama']);
    $pic = $pic_lengkap . ' (' . $pic_nama . ')';

    echo '<tr>';
    echo '<td class="center">' . $no++ . '</td>';
    echo '<td class="nowrap">' . htmlspecialchars($jadwal, ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td class="center nowrap">' . htmlspecialchars($waktu, ENT_QUOTES, 'UTF-8') . '</td>';
    
    // Kolom gedung hanya ditampilkan jika sort by tanggal
    if ($sort_by !== 'gedung') {
        echo '<td>' . htmlspecialchars($gedung, ENT_QUOTES, 'UTF-8') . '</td>';
    }
    
    echo '<td>' . htmlspecialchars($perlengkapan, ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($tujuan, ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($pic, ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td class="center">' . htmlspecialchars($hariRutin, ENT_QUOTES, 'UTF-8') . '</td>';
    echo '</tr>';
}

echo '</tbody>';
echo '</table>';

// Footer
echo '<br><br>';
echo '<p>Dicetak oleh: ' . htmlspecialchars($exporter, ENT_QUOTES, 'UTF-8') . '</p>';
echo '<p>Dicetak pada: ' . date('d-m-Y H:i:s') . '</p>';

echo '</body>';
echo '</html>';

exit;
