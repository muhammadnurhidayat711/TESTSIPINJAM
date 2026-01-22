<?php
// ========= DATABASE CONNECTION - Pastikan sudah tersedia =========
// Koneksi database dari sistem Anda

// ================== Helper Functions ==================
function fmt_tgl($ymd){
  if(!$ymd || $ymd==='0000-00-00') return '-';
  $parts = explode('-', $ymd);
  if(count($parts) !== 3) return $ymd;
  [$y,$m,$d] = $parts;
  $bulan = ["","Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
  return sprintf("%02d %s %s", (int)$d, $bulan[(int)$m], $y);
}

function fmt_waktu($hms){
  if(!$hms || $hms === '00:00:00') return '-';
  return substr($hms, 0, 5);
}

// ================== Ambil Parameter Filter ==================
$tgl_cari = isset($_GET['tgl']) ? trim($_GET['tgl']) : '';
$kendaraan_cari = isset($_GET['kendaraan']) ? trim($_GET['kendaraan']) : '';

// ================== QUERY 1: SEMUA JADWAL PEMINJAMAN ==================
$sql_jadwal = "
  SELECT 
    p.id_pk,
    p.tgl_mulai,
    p.waktu_mulai,
    p.tgl_selesai,
    p.waktu_selesai,
    p.tujuan,
    p.pengemudi,
    p.status,
    u.nama_lengkap,
    k.nama_kendaraan,
    k.id_kendaraan
  FROM pinjamkendaraan p
  LEFT JOIN user u ON u.id = p.id_user
  LEFT JOIN kendaraan k ON k.id_kendaraan = p.id_kendaraan
  WHERE 1=1
";

// Filter berdasarkan tanggal jika ada
if (!empty($tgl_cari)) {
    $sql_jadwal .= " AND (p.tgl_mulai = ? OR p.tgl_selesai = ? OR (p.tgl_mulai <= ? AND p.tgl_selesai >= ?))";
}

// Filter berdasarkan kendaraan jika ada
if (!empty($kendaraan_cari)) {
    $sql_jadwal .= " AND p.id_kendaraan = ?";
}

$sql_jadwal .= " ORDER BY p.tgl_mulai DESC, p.waktu_mulai DESC";

// Prepare statement
$stmt_jadwal = $conn->prepare($sql_jadwal);

// Bind parameters
$bind_types = "";
$bind_params = [];

if (!empty($tgl_cari) && !empty($kendaraan_cari)) {
    $bind_types = "ssssss";
    $bind_params = [&$tgl_cari, &$tgl_cari, &$tgl_cari, &$tgl_cari, &$kendaraan_cari];
} elseif (!empty($tgl_cari)) {
    $bind_types = "ssss";
    $bind_params = [&$tgl_cari, &$tgl_cari, &$tgl_cari, &$tgl_cari];
} elseif (!empty($kendaraan_cari)) {
    $bind_types = "s";
    $bind_params = [&$kendaraan_cari];
}

if (!empty($bind_types)) {
    $stmt_jadwal->bind_param($bind_types, ...$bind_params);
}

$stmt_jadwal->execute();
$result_jadwal = $stmt_jadwal->get_result();
$all_jadwal = [];
while ($row = $result_jadwal->fetch_assoc()) {
    $all_jadwal[] = $row;
}
$stmt_jadwal->close();

// ================== QUERY 2: DAFTAR SEMUA KENDARAAN ==================
$sql_kendaraan = "SELECT id_kendaraan, nama_kendaraan, status FROM kendaraan ORDER BY nama_kendaraan ASC";
$result_kendaraan = $conn->query($sql_kendaraan);
$all_kendaraan = [];
while ($row = $result_kendaraan->fetch_assoc()) {
    $all_kendaraan[] = $row;
}

// ================== LOGIC: Cari Jadwal Kosong ==================
// Untuk setiap kendaraan, cari jadwal yang tersedia
$kendaraan_availability = [];

foreach ($all_kendaraan as $kendaraan) {
    $id_kendaraan = $kendaraan['id_kendaraan'];

    // Filter jadwal untuk kendaraan ini
    $jadwal_kendaraan = array_filter($all_jadwal, function($j) use ($id_kendaraan) {
        return $j['id_kendaraan'] == $id_kendaraan && strtolower($j['status']) === 'disetujui';
    });

    // Urutkan by tanggal
    usort($jadwal_kendaraan, function($a, $b) {
        return strtotime($a['tgl_selesai']) - strtotime($b['tgl_selesai']);
    });

    $kendaraan_availability[] = [
        'id_kendaraan' => $id_kendaraan,
        'nama_kendaraan' => $kendaraan['nama_kendaraan'],
        'status' => $kendaraan['status'],
        'jadwal_peminjaman' => $jadwal_kendaraan,
        'jadwal_kosong_dari' => count($jadwal_kendaraan) > 0 
            ? date('Y-m-d', strtotime($jadwal_kendaraan[count($jadwal_kendaraan)-1]['tgl_selesai'] . ' + 1 day'))
            : date('Y-m-d')
    ];
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal & Availability Kendaraan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
          --success: #22c55e;
          --danger: #ef4444;
          --warning: #f59e0b;
          --info: #3b82f6;
          --success-soft: #e9f8ef;
          --warning-soft: #fff7e6;
          --danger-soft: #fee2e2;
          --info-soft: #e0f2fe;
          --muted: #6b7280;
          --txt: #1f2937;
          --card: #fff;
          --shadow: 0 4px 12px rgba(0,0,0,.08);
          --border: #e5e7eb;
        }
        * { box-sizing: border-box; }
        body { 
          font-family: 'Segoe UI', sans-serif; 
          background: #f9fafb; 
          margin: 0; 
          padding: 20px; 
          color: var(--txt); 
        }
        .container { max-width: 1200px; margin: 0 auto; }

        .page-header {
          margin-bottom: 24px;
          padding-bottom: 16px;
          border-bottom: 2px solid var(--border);
        }

        .page-header h1 {
          margin: 0;
          font-size: 1.75rem;
          font-weight: 700;
        }

        .filter-section {
          background: var(--card);
          border: 1px solid var(--border);
          border-radius: 12px;
          padding: 20px;
          margin-bottom: 24px;
          box-shadow: var(--shadow);
        }

        .filter-group {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
          gap: 16px;
          margin-bottom: 16px;
        }

        .form-group {
          display: flex;
          flex-direction: column;
          gap: 6px;
        }

        .form-label {
          font-weight: 600;
          color: var(--txt);
          font-size: 0.9rem;
        }

        .form-control {
          padding: 8px 12px;
          border: 1px solid var(--border);
          border-radius: 8px;
          font-size: 0.9rem;
        }

        .filter-actions {
          display: flex;
          gap: 8px;
          flex-wrap: wrap;
        }

        .btn {
          padding: 8px 16px;
          border: none;
          border-radius: 8px;
          font-size: 0.9rem;
          font-weight: 600;
          cursor: pointer;
          display: inline-flex;
          align-items: center;
          gap: 6px;
        }

        .btn-primary { background: var(--info); color: white; }
        .btn-primary:hover { background: #2563eb; }
        .btn-secondary { background: var(--muted); color: white; }
        .btn-secondary:hover { background: #4b5563; }

        .card {
          background: var(--card);
          border: 1px solid var(--border);
          border-radius: 12px;
          box-shadow: var(--shadow);
          margin-bottom: 20px;
          overflow: hidden;
        }

        .card-header {
          padding: 16px 20px;
          background: linear-gradient(135deg, var(--info) 0%, #2563eb 100%);
          color: white;
        }

        .card-header h3 {
          margin: 0;
          font-size: 1rem;
          display: flex;
          align-items: center;
          gap: 10px;
        }

        .card-body { padding: 20px; }

        .tabs {
          display: flex;
          gap: 0;
          border-bottom: 2px solid var(--border);
          margin-bottom: 20px;
        }

        .tab-btn {
          padding: 12px 16px;
          border: none;
          background: transparent;
          color: var(--muted);
          font-weight: 600;
          cursor: pointer;
          border-bottom: 3px solid transparent;
          margin-bottom: -2px;
        }

        .tab-btn.active {
          color: var(--info);
          border-bottom-color: var(--info);
        }

        .tab-content {
          display: none;
        }

        .tab-content.active {
          display: block;
        }

        .table-simple {
          width: 100%;
          border-collapse: collapse;
        }

        .table-simple thead {
          background: #f9fafb;
          border-bottom: 2px solid var(--border);
        }

        .table-simple th {
          padding: 12px;
          text-align: left;
          font-weight: 600;
          font-size: 0.9rem;
          text-transform: uppercase;
          color: var(--muted);
        }

        .table-simple td {
          padding: 12px;
          border-bottom: 1px solid var(--border);
          font-size: 0.9rem;
        }

        .table-simple tbody tr:hover {
          background: #f9fafb;
        }

        .status-badge {
          display: inline-block;
          padding: 4px 12px;
          border-radius: 999px;
          font-weight: 600;
          font-size: 0.8rem;
        }

        .status-disetujui {
          background: var(--success-soft);
          color: #065f46;
        }

        .status-menunggu {
          background: var(--warning-soft);
          color: #b45309;
        }

        .kendaraan-item {
          background: #f9fafb;
          border: 1px solid var(--border);
          border-radius: 12px;
          padding: 16px;
          margin-bottom: 16px;
        }

        .kendaraan-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 12px;
        }

        .kendaraan-name {
          font-size: 1.1rem;
          font-weight: 700;
          color: var(--txt);
        }

        .kendaraan-status {
          display: inline-block;
          padding: 4px 12px;
          border-radius: 999px;
          font-size: 0.8rem;
          font-weight: 600;
        }

        .status-tersedia {
          background: var(--success-soft);
          color: #065f46;
        }

        .status-dipinjam {
          background: var(--danger-soft);
          color: #7f1d1d;
        }

        .jadwal-list {
          display: flex;
          flex-direction: column;
          gap: 8px;
          margin-bottom: 12px;
        }

        .jadwal-item {
          background: white;
          border-left: 4px solid var(--warning);
          padding: 8px 12px;
          border-radius: 4px;
          font-size: 0.85rem;
        }

        .jadwal-item.disetujui {
          border-left-color: var(--success);
        }

        .jadwal-time {
          font-weight: 700;
          color: var(--info);
        }

        .availability-box {
          background: var(--success-soft);
          border: 2px solid var(--success);
          border-radius: 8px;
          padding: 12px;
          margin-top: 8px;
        }

        .availability-label {
          font-size: 0.8rem;
          color: var(--muted);
          text-transform: uppercase;
          font-weight: 600;
          margin-bottom: 4px;
        }

        .availability-date {
          font-size: 1.1rem;
          font-weight: 700;
          color: #065f46;
        }

        .no-data {
          text-align: center;
          padding: 40px 20px;
          color: var(--muted);
        }

        .stats-grid {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
          gap: 16px;
          margin-bottom: 24px;
        }

        .stat-card {
          background: var(--card);
          border: 1px solid var(--border);
          border-radius: 12px;
          padding: 16px;
          text-align: center;
          box-shadow: var(--shadow);
        }

        .stat-value {
          font-size: 2rem;
          font-weight: 700;
          color: var(--info);
          margin-bottom: 4px;
        }

        .stat-label {
          font-size: 0.9rem;
          color: var(--muted);
        }

        @media (max-width: 768px) {
          .filter-group { grid-template-columns: 1fr; }
          .tabs { flex-wrap: wrap; }
          .table-simple { font-size: 0.8rem; }
          .table-simple td, .table-simple th { padding: 8px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-calendar-alt"></i> Jadwal & Availability Kendaraan</h1>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo count($all_kendaraan); ?></div>
                <div class="stat-label">Total Kendaraan</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo count($all_jadwal); ?></div>
                <div class="stat-label">Total Peminjaman</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo count(array_filter($all_kendaraan, function($k) { return $k['status'] === 'tersedia'; })); ?></div>
                <div class="stat-label">Kendaraan Tersedia</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo count(array_filter($all_kendaraan, function($k) { return $k['status'] === 'dipinjam'; })); ?></div>
                <div class="stat-label">Kendaraan Dipinjam</div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="">
                <div class="filter-group">
                    <div class="form-group">
                        <label class="form-label">Cari Tanggal</label>
                        <input type="date" class="form-control" name="tgl" value="<?php echo htmlspecialchars($tgl_cari); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Pilih Kendaraan</label>
                        <select class="form-control" name="kendaraan">
                            <option value="">-- Semua Kendaraan --</option>
                            <?php foreach ($all_kendaraan as $kend): ?>
                                <option value="<?php echo htmlspecialchars($kend['id_kendaraan']); ?>" 
                                    <?php echo $kendaraan_cari == $kend['id_kendaraan'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($kend['nama_kendaraan']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Cari
                    </button>
                    <a href="?" class="btn btn-secondary">
                        <i class="fas fa-undo"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Main Content -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> Informasi Jadwal & Availability</h3>
            </div>
            <div class="card-body">
                <div class="tabs">
                    <button class="tab-btn active" onclick="switchTab(event, 'tab-availability')">
                        <i class="fas fa-check-circle"></i> Availability
                    </button>
                    <button class="tab-btn" onclick="switchTab(event, 'tab-jadwal')">
                        <i class="fas fa-calendar"></i> Jadwal Lengkap
                    </button>
                </div>

                <!-- TAB 1: AVAILABILITY -->
                <div id="tab-availability" class="tab-content active">
                    <?php if (count($kendaraan_availability) > 0): ?>
                        <?php foreach ($kendaraan_availability as $avail): ?>
                            <div class="kendaraan-item">
                                <div class="kendaraan-header">
                                    <div class="kendaraan-name">
                                        <i class="fas fa-car"></i> <?php echo htmlspecialchars($avail['nama_kendaraan']); ?>
                                    </div>
                                    <div class="kendaraan-status status-<?php echo htmlspecialchars($avail['status']); ?>">
                                        <?php echo htmlspecialchars(ucfirst($avail['status'])); ?>
                                    </div>
                                </div>

                                <?php if (count($avail['jadwal_peminjaman']) > 0): ?>
                                    <div>
                                        <strong style="font-size: 0.9rem; color: var(--muted);">📅 Jadwal Terbaru:</strong>
                                        <div class="jadwal-list">
                                            <?php foreach (array_slice($avail['jadwal_peminjaman'], -3) as $jadwal): ?>
                                                <div class="jadwal-item disetujui">
                                                    <div class="jadwal-time">
                                                        <?php echo fmt_tgl($jadwal['tgl_mulai']); ?> 
                                                        (<?php echo fmt_waktu($jadwal['waktu_mulai']); ?> - <?php echo fmt_waktu($jadwal['waktu_selesai']); ?>)
                                                    </div>
                                                    <div style="font-size: 0.8rem; color: var(--txt); margin-top: 2px;">
                                                        📌 <?php echo htmlspecialchars($jadwal['pengemudi'] ?? '-'); ?> • 🎯 <?php echo htmlspecialchars($jadwal['tujuan'] ?? '-'); ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="availability-box">
                                    <div class="availability-label">✅ Jadwal Kosong Mulai Dari:</div>
                                    <div class="availability-date">
                                        <?php echo fmt_tgl($avail['jadwal_kosong_dari']); ?>
                                    </div>
                                    <div style="font-size: 0.85rem; color: #065f46; margin-top: 6px;">
                                        Kendaraan siap untuk peminjaman baru
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-info-circle" style="font-size: 2rem; margin-bottom: 10px;"></i>
                            <p>Tidak ada data kendaraan</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- TAB 2: JADWAL LENGKAP -->
                <div id="tab-jadwal" class="tab-content">
                    <?php if (count($all_jadwal) > 0): ?>
                        <table class="table-simple">
                            <thead>
                                <tr>
                                    <th>Tanggal Mulai</th>
                                    <th>Jam</th>
                                    <th>Kendaraan</th>
                                    <th>Pengemudi</th>
                                    <th>Tujuan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_jadwal as $jadwal): ?>
                                    <tr>
                                        <td><?php echo fmt_tgl($jadwal['tgl_mulai']); ?></td>
                                        <td class="jadwal-time">
                                            <?php echo fmt_waktu($jadwal['waktu_mulai']); ?> - <?php echo fmt_waktu($jadwal['waktu_selesai']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($jadwal['nama_kendaraan'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($jadwal['pengemudi'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($jadwal['tujuan'] ?? '-'); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($jadwal['status']); ?>">
                                                <?php echo htmlspecialchars(ucfirst($jadwal['status'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-calendar-times" style="font-size: 2rem; margin-bottom: 10px;"></i>
                            <p>Tidak ada jadwal peminjaman</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <script>
        function switchTab(event, tabName) {
            // Hide all tabs
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));

            // Remove active from all buttons
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));

            // Show selected tab and mark button as active
            document.getElementById(tabName).classList.add('active');
            event.target.closest('.tab-btn').classList.add('active');
        }
    </script>
</body>
</html>