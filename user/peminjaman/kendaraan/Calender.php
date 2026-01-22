<?php
// Include koneksi dari root
require_once('../koneksi.php');
session_start();

// Cek login
if (!isset($_SESSION['id'])) {
    header("Location: ../cek_login.php");
    exit();
}

// Debug mode - hapus setelah berhasil
error_reporting(E_ALL);
ini_set('display_errors', 1);

$id_user = $_SESSION['id'];
$level = $_SESSION['level'];

// Get current month & year
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

// Hitung statistik
$stats_gedung = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pinjambarang WHERE status IN ('menunggu','approve')"))['total'];
$stats_kendaraan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pinjamkendaraan WHERE status IN ('menunggu','disetujui','dipinjam')"))['total'];
$stats_kolam = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pinjamkolam WHERE status IN ('menunggu','approve')"))['total'];
$stats_studio = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pinjamstudio WHERE status IN ('menunggu','approve')"))['total'];

// Function untuk get semua peminjaman per tanggal
function getPeminjamanByDate($conn, $tanggal) {
    $result = [];
    
    // Gedung
    $query_gedung = mysqli_query($conn, "
        SELECT 'Gedung' as jenis, b.nama_barang as nama, pb.status
        FROM pinjambarang pb
        LEFT JOIN barang b ON pb.id_barang = b.id
        WHERE DATE(pb.tgl_mulai) <= '$tanggal' AND DATE(pb.tgl_selesai) >= '$tanggal'
        AND pb.status IN ('menunggu', 'approve', 'disetujui')
    ");
    while ($row = mysqli_fetch_assoc($query_gedung)) {
        $result[] = $row;
    }
    
    // Kendaraan
    $query_kendaraan = mysqli_query($conn, "
        SELECT 'Kendaraan' as jenis, k.nama_kendaraan as nama, pk.status
        FROM pinjamkendaraan pk
        LEFT JOIN kendaraan k ON pk.id_kendaraan = k.id_kendaraan
        WHERE DATE(pk.tgl_mulai) <= '$tanggal' AND DATE(pk.tgl_selesai) >= '$tanggal'
        AND pk.status IN ('menunggu', 'disetujui', 'dipinjam')
    ");
    while ($row = mysqli_fetch_assoc($query_kendaraan)) {
        $result[] = $row;
    }
    
    // Kolam
    $query_kolam = mysqli_query($conn, "
        SELECT 'Kolam' as jenis, ko.jenis_kolam as nama, pk.status
        FROM pinjamkolam pk
        LEFT JOIN kolam ko ON pk.id_kolam = ko.id_kolam
        WHERE DATE(pk.tgl_mulai) = '$tanggal'
        AND pk.status IN ('menunggu', 'approve')
    ");
    while ($row = mysqli_fetch_assoc($query_kolam)) {
        $result[] = $row;
    }
    
    // Studio
    $query_studio = mysqli_query($conn, "
        SELECT 'Studio' as jenis, s.jenis_studio as nama, ps.status
        FROM pinjamstudio ps
        LEFT JOIN studio s ON ps.id_studio = s.id_studio
        WHERE DATE(ps.tgl_mulai) = '$tanggal'
        AND ps.status IN ('menunggu', 'approve')
    ");
    while ($row = mysqli_fetch_assoc($query_studio)) {
        $result[] = $row;
    }
    
    return $result;
}

$nama_bulan = array('', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalender Peminjaman</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background: #f5f7fa; padding: 20px; }
        .calendar-container { max-width: 1400px; margin: 0 auto; background: white; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; }
        .header h2 { margin: 0; font-size: 24px; }
        .stats { display: flex; gap: 15px; margin-top: 20px; flex-wrap: wrap; }
        .stat { background: rgba(255,255,255,0.2); padding: 10px 20px; border-radius: 20px; font-size: 14px; }
        .calendar-controls { display: flex; justify-content: space-between; padding: 20px 30px; border-bottom: 1px solid #e0e0e0; align-items: center; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); padding: 20px; gap: 10px; }
        .calendar-header { text-align: center; padding: 10px; font-weight: 600; color: #667eea; font-size: 14px; }
        .calendar-day { border: 1px solid #e0e0e0; border-radius: 10px; min-height: 100px; padding: 8px; background: white; transition: all 0.3s; cursor: pointer; }
        .calendar-day:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.1); transform: translateY(-2px); }
        .calendar-day.other-month { background: #f5f5f5; opacity: 0.5; }
        .calendar-day.today { border: 2px solid #667eea; background: #f0f4ff; }
        .day-number { font-weight: 600; margin-bottom: 5px; font-size: 14px; }
        .event-badge { font-size: 10px; padding: 3px 8px; border-radius: 5px; margin-bottom: 3px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .event-gedung { background: #667eea; color: white; }
        .event-kendaraan { background: #f5576c; color: white; }
        .event-kolam { background: #4facfe; color: white; }
        .event-studio { background: #43e97b; color: white; }
        .btn-nav { background: #667eea; color: white; border: none; padding: 8px 15px; border-radius: 8px; margin: 0 5px; text-decoration: none; display: inline-block; }
        .btn-nav:hover { background: #764ba2; color: white; }
        .legend { display: flex; gap: 15px; padding: 20px 30px; border-top: 1px solid #e0e0e0; flex-wrap: wrap; }
        .legend-item { display: flex; align-items: center; gap: 8px; font-size: 13px; }
        .legend-color { width: 20px; height: 20px; border-radius: 5px; }
    </style>
</head>
<body>

<div class="calendar-container">
    <!-- Header -->
    <div class="header">
        <h2><i class="fas fa-calendar-alt"></i> Kalender Peminjaman Fasilitas</h2>
        <div class="stats">
            <div class="stat"><i class="fas fa-building"></i> <strong><?php echo $stats_gedung; ?></strong> Gedung</div>
            <div class="stat"><i class="fas fa-car"></i> <strong><?php echo $stats_kendaraan; ?></strong> Kendaraan</div>
            <div class="stat"><i class="fas fa-swimming-pool"></i> <strong><?php echo $stats_kolam; ?></strong> Kolam</div>
            <div class="stat"><i class="fas fa-music"></i> <strong><?php echo $stats_studio; ?></strong> Studio</div>
        </div>
    </div>

    <!-- Controls -->
    <div class="calendar-controls">
        <h4 style="margin:0;"><?php echo $nama_bulan[(int)$bulan] . ' ' . $tahun; ?></h4>
        <div>
            <?php
            $prev_bulan = $bulan - 1;
            $prev_tahun = $tahun;
            if ($prev_bulan < 1) { $prev_bulan = 12; $prev_tahun--; }
            
            $next_bulan = $bulan + 1;
            $next_tahun = $tahun;
            if ($next_bulan > 12) { $next_bulan = 1; $next_tahun++; }
            ?>
            <a href="?bulan=<?php echo $prev_bulan; ?>&tahun=<?php echo $prev_tahun; ?>" class="btn-nav"><i class="fas fa-chevron-left"></i></a>
            <a href="?" class="btn-nav">Hari Ini</a>
            <a href="?bulan=<?php echo $next_bulan; ?>&tahun=<?php echo $next_tahun; ?>" class="btn-nav"><i class="fas fa-chevron-right"></i></a>
        </div>
    </div>

    <!-- Calendar -->
    <div class="calendar-grid">
        <div class="calendar-header">Min</div>
        <div class="calendar-header">Sen</div>
        <div class="calendar-header">Sel</div>
        <div class="calendar-header">Rab</div>
        <div class="calendar-header">Kam</div>
        <div class="calendar-header">Jum</div>
        <div class="calendar-header">Sab</div>

        <?php
        $first_day = mktime(0, 0, 0, $bulan, 1, $tahun);
        $days_in_month = date('t', $first_day);
        $day_of_week = date('w', $first_day);
        $prev_month_days = date('t', mktime(0, 0, 0, $bulan - 1, 1, $tahun));
        
        // Fill previous month days
        for ($i = $day_of_week - 1; $i >= 0; $i--) {
            $day = $prev_month_days - $i;
            echo '<div class="calendar-day other-month"><div class="day-number">' . $day . '</div></div>';
        }
        
        // Current month days
        for ($day = 1; $day <= $days_in_month; $day++) {
            $tanggal = sprintf("%04d-%02d-%02d", $tahun, $bulan, $day);
            $is_today = ($tanggal == date('Y-m-d')) ? 'today' : '';
            
            $peminjaman = getPeminjamanByDate($conn, $tanggal);
            
            echo '<div class="calendar-day ' . $is_today . '">';
            echo '<div class="day-number">' . $day . '</div>';
            
            // Group by jenis
            $grouped = [];
            foreach ($peminjaman as $p) {
                $jenis = $p['jenis'];
                if (!isset($grouped[$jenis])) {
                    $grouped[$jenis] = [];
                }
                $grouped[$jenis][] = $p;
            }
            
            // Display badges
            $count = 0;
            foreach ($grouped as $jenis => $items) {
                if ($count >= 3) break;
                
                $class = 'event-' . strtolower($jenis);
                $nama = htmlspecialchars($items[0]['nama']);
                if (strlen($nama) > 12) {
                    $nama = substr($nama, 0, 12) . '...';
                }
                
                echo '<div class="event-badge ' . $class . '">' . $jenis . ': ' . $nama . '</div>';
                $count++;
            }
            
            $total = count($peminjaman);
            if ($total > 3) {
                echo '<small style="color:#999;font-size:10px;">+' . ($total - 3) . ' lainnya</small>';
            }
            
            echo '</div>';
        }
        
        // Fill next month
        $remaining_days = 7 - (($days_in_month + $day_of_week) % 7);
        if ($remaining_days < 7) {
            for ($day = 1; $day <= $remaining_days; $day++) {
                echo '<div class="calendar-day other-month"><div class="day-number">' . $day . '</div></div>';
            }
        }
        ?>
    </div>

    <!-- Legend -->
    <div class="legend">
        <div class="legend-item"><div class="legend-color" style="background:#667eea;"></div><span>Gedung</span></div>
        <div class="legend-item"><div class="legend-color" style="background:#f5576c;"></div><span>Kendaraan</span></div>
        <div class="legend-item"><div class="legend-color" style="background:#4facfe;"></div><span>Kolam</span></div>
        <div class="legend-item"><div class="legend-color" style="background:#43e97b;"></div><span>Studio</span></div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
