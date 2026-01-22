<?php
// ========= DATABASE CONNECTION =========
// Pastikan koneksi $conn sudah tersedia sebelum file ini di-include

// ================== Ambil ID dari URL ==================
$id_pk = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($id_pk)) {
    echo "<div style='padding:20px; background:#fee2e2; border:1px solid #fca5a5; border-radius:8px; color:#991b1b; margin:20px;'>
            <strong>⚠️ Error:</strong> ID Peminjaman tidak ditemukan.
          </div>";
    echo "<a href='javascript:window.history.back()' style='display:inline-block; margin:20px; padding:10px 20px; background:#6b7280; color:white; border-radius:8px; text-decoration:none;'>← Kembali</a>";
    exit;
}

// ================== Query Data Detail ==================
$sql = "SELECT 
          p.id_pk,
          p.id_user,
          p.id_kendaraan,
          p.tgl_mulai,
          p.waktu_mulai,
          p.tgl_selesai,
          p.waktu_selesai,
          p.tujuan,
          p.pengemudi,
          p.status,
          u.nama_lengkap,
          u.email,
          k.nama_kendaraan,
          k.deskripsi,
          k.foto,
          k.status as status_kendaraan
        FROM pinjamkendaraan p
        LEFT JOIN user u ON u.id = p.id_user
        LEFT JOIN kendaraan k ON k.id_kendaraan = p.id_kendaraan
        WHERE p.id_pk = ? LIMIT 1";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("<div style='padding:20px; background:#fee2e2; color:#991b1b;'><strong>Database Error:</strong> " . htmlspecialchars($conn->error) . "</div>");
}

$stmt->bind_param("s", $id_pk);

if (!$stmt->execute()) {
    die("<div style='padding:20px; background:#fee2e2; color:#991b1b;'><strong>Query Error:</strong> " . htmlspecialchars($stmt->error) . "</div>");
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<div style='padding:20px; background:#fee2e2; color:#991b1b;'><strong>Data tidak ditemukan</strong></div>";
    echo "<a href='javascript:window.history.back()'>← Kembali</a>";
    exit;
}

$data = $result->fetch_assoc();
$stmt->close();

// ================== Format Functions ==================
function fmt_tgl($ymd){
  if(!$ymd || $ymd==='0000-00-00') return '-';
  $parts = explode('-', $ymd);
  if(count($parts) !== 3) return $ymd;
  [$y,$m,$d] = $parts;
  $bulan = ["","Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
  return sprintf("%02d %s %s", (int)$d, isset($bulan[(int)$m]) ? $bulan[(int)$m] : $m, $y);
}

function fmt_waktu($hms){
  if(!$hms || $hms === '00:00:00') return '-';
  return substr($hms, 0, 5);
}

$status_lc = strtolower(trim($data['status'] ?? 'menunggu'));
$tglMulai = fmt_tgl($data['tgl_mulai']);
$tglSelesai = fmt_tgl($data['tgl_selesai']);
$wMulai = fmt_waktu($data['waktu_mulai']);
$wSelesai = fmt_waktu($data['waktu_selesai']);
$pengemudi = $data['pengemudi'] ?: 'Belum ditentukan';
$tujuan = $data['tujuan'] ?: '-';
$email = $data['email'] ?: '-';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Peminjaman Kendaraan - SIPINJAM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ========== MODERN CSS RESET ========== */
        *, *::before, *::after {
          box-sizing: border-box;
          margin: 0;
          padding: 0;
        }

        html {
          -webkit-text-size-adjust: 100%;
          -moz-text-size-adjust: 100%;
        }

        body {
          margin: 0;
          padding: 0;
          line-height: 1.5;
          -webkit-font-smoothing: antialiased;
          -moz-osx-font-smoothing: grayscale;
        }

        img, picture, video, canvas, svg {
          display: block;
          max-width: 100%;
          height: auto;
        }

        input, button, textarea, select {
          font: inherit;
        }

        p, h1, h2, h3, h4, h5, h6 {
          overflow-wrap: break-word;
        }

        /* ========== CUSTOM PROPERTIES ========== */
        :root {
          --primary: #3b82f6;
          --primary-dark: #2563eb;
          --success: #22c55e;
          --success-soft: #e9f8ef;
          --warning: #f59e0b;
          --warning-soft: #fff7e6;
          --danger: #ef4444;
          --info: #06b6d4;
          --info-soft: #e0f2fe;
          --muted: #6b7280;
          --text: #1f2937;
          --text-light: #64748b;
          --card: #ffffff;
          --shadow: 0 4px 12px rgba(0,0,0,.08);
          --shadow-lg: 0 10px 40px rgba(0,0,0,.12);
          --border: #e5e7eb;
          --bg-page: #f9fafb;
          --bg-light: #f8fafc;
          --radius: 12px;
          --radius-sm: 8px;
        }

        /* ========== BASE STYLES ========== */
        body { 
          font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
          background: var(--bg-page);
          color: var(--text);
          padding: 20px;
        }

        .container { 
          max-width: 900px;
          margin: 0 auto;
        }

        /* ========== BACK BUTTON ========== */
        .back-btn { 
          display: inline-flex;
          align-items: center;
          gap: 8px;
          padding: 10px 18px;
          background: var(--muted);
          color: white;
          border-radius: var(--radius-sm);
          text-decoration: none;
          margin-bottom: 20px;
          font-size: 0.9rem;
          font-weight: 600;
          cursor: pointer;
          transition: all 0.2s ease;
        }

        .back-btn:hover { 
          background: #4b5563;
          transform: translateY(-2px);
          box-shadow: var(--shadow);
        }

        /* ========== CARD ========== */
        .card { 
          background: var(--card);
          border: 1px solid var(--border);
          border-radius: var(--radius);
          box-shadow: var(--shadow);
          margin-bottom: 20px;
          overflow: hidden;
        }

        .card-header { 
          padding: 18px 24px;
          background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
          color: white;
        }

        .card-header h4 { 
          margin: 0;
          font-size: 1.05rem;
          display: flex;
          align-items: center;
          gap: 10px;
          font-weight: 700;
        }

        .card-body { 
          padding: 24px;
        }

        /* ========== GRID ========== */
        .grid-2 { 
          display: grid;
          grid-template-columns: repeat(2, 1fr);
          gap: 16px;
        }

        @media (max-width: 768px) { 
          .grid-2 { 
            grid-template-columns: 1fr;
          }
        }

        /* ========== INFO ITEM ========== */
        .info-item { 
          padding: 14px;
          background: var(--bg-light);
          border-radius: var(--radius-sm);
          border: 1px solid var(--border);
          transition: all 0.2s ease;
        }

        .info-item:hover {
          box-shadow: 0 2px 8px rgba(0,0,0,0.05);
          transform: translateY(-1px);
        }

        .info-label { 
          font-size: 0.75rem;
          font-weight: 700;
          color: var(--text-light);
          text-transform: uppercase;
          letter-spacing: 0.05em;
          margin-bottom: 6px;
        }

        .info-value { 
          font-size: 0.95rem;
          font-weight: 600;
          display: flex;
          align-items: center;
          gap: 8px;
          word-break: break-word;
          color: var(--text);
        }

        .info-value i { 
          color: var(--primary);
          flex-shrink: 0;
          font-size: 1.1rem;
        }

        /* ========== STATUS BADGE ========== */
        .status-badge { 
          display: inline-flex;
          align-items: center;
          gap: 8px;
          padding: 10px 18px;
          border-radius: 999px;
          font-weight: 700;
          font-size: 0.9rem;
        }

        .status-menunggu { 
          background: var(--warning-soft);
          color: #b45309;
          border: 1px solid #fbbf24;
        }

        .status-approve { 
          background: var(--success-soft);
          color: #065f46;
          border: 1px solid #86efac;
        }

        /* ========== TIMELINE ========== */
        .timeline { 
          display: flex;
          align-items: center;
          gap: 16px;
          padding: 16px;
          background: var(--bg-light);
          border-radius: var(--radius-sm);
          border: 1px solid var(--border);
          margin: 16px 0;
          flex-wrap: wrap;
        }

        .timeline-item { 
          display: flex;
          flex-direction: column;
          gap: 4px;
        }

        .timeline-label { 
          font-size: 0.7rem;
          font-weight: 700;
          color: var(--text-light);
          text-transform: uppercase;
          letter-spacing: 0.05em;
        }

        .timeline-value { 
          font-size: 0.95rem;
          font-weight: 700;
          color: var(--text);
        }

        .timeline-arrow { 
          font-size: 1.5rem;
          color: var(--primary);
          font-weight: bold;
        }

        @media (max-width: 768px) {
          .timeline { 
            flex-direction: column;
            align-items: flex-start;
          }
          
          .timeline-arrow { 
            transform: rotate(90deg);
            margin: 8px 0;
          }
        }

        /* ========== DIVIDER ========== */
        .divider { 
          height: 1px;
          background: var(--border);
          margin: 18px 0;
        }

        /* ========== SECTION TITLE ========== */
        .section-title { 
          font-size: 0.95rem;
          font-weight: 700;
          margin-bottom: 14px;
          display: flex;
          align-items: center;
          gap: 8px;
          color: var(--text);
        }

        .section-title i { 
          color: var(--primary);
        }

        /* ========== ACTIONS ========== */
        .actions { 
          display: flex;
          gap: 10px;
          margin-top: 0;
          flex-wrap: wrap;
        }

        .btn { 
          padding: 10px 18px;
          border: none;
          border-radius: var(--radius-sm);
          font-size: 0.9rem;
          font-weight: 600;
          cursor: pointer;
          text-decoration: none;
          display: inline-flex;
          align-items: center;
          gap: 8px;
          transition: all 0.2s ease;
        }

        .btn:hover {
          transform: translateY(-2px);
          box-shadow: var(--shadow);
        }

        .btn-secondary { 
          background: var(--muted);
          color: white;
        }

        .btn-secondary:hover { 
          background: #4b5563;
        }

        .btn-print { 
          background: var(--primary);
          color: white;
        }

        .btn-print:hover { 
          background: var(--primary-dark);
        }

        /* ========== PRINT STYLES ========== */
        @media print {
          body { 
            background: white;
            padding: 0;
            font-size: 11pt;
          }

          .container {
            max-width: 100%;
          }

          .back-btn, 
          .actions { 
            display: none !important;
          }

          .card { 
            box-shadow: none;
            page-break-inside: avoid;
            border: 1px solid #000;
          }

          .card-header {
            background: #f0f0f0 !important;
            color: #000 !important;
            border-bottom: 2px solid #000;
          }

          .status-badge {
            border: 1px solid #000 !important;
          }

          .info-item {
            border: 1px solid #ccc !important;
            background: white !important;
          }

          .timeline {
            border: 1px solid #ccc !important;
            background: white !important;
          }

          a {
            color: #000 !important;
            text-decoration: none !important;
          }

          /* Page breaks */
          .card {
            page-break-after: auto;
          }

          h4 {
            page-break-after: avoid;
          }
        }

        /* ========== ACCESSIBILITY ========== */
        @media (prefers-reduced-motion: reduce) {
          *,
          *::before,
          *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
          }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="javascript:history.back()" class="back-btn">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>

        <!-- STATUS -->
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-info-circle"></i> Status Peminjaman</h4>
            </div>
            <div class="card-body">
                <?php 
                if ($status_lc === 'menunggu') {
                    echo '<div class="status-badge status-menunggu"><i class="fas fa-clock"></i> Menunggu Persetujuan</div>';
                } else {
                    echo '<div class="status-badge status-approve"><i class="fas fa-check-circle"></i> Disetujui</div>';
                }
                ?>
            </div>
        </div>

        <!-- PEMINJAM -->
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-user"></i> Informasi Peminjam</h4>
            </div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="info-item">
                        <div class="info-label">Nama Lengkap</div>
                        <div class="info-value">
                            <i class="fas fa-user"></i> 
                            <?php echo htmlspecialchars($data['nama_lengkap'] ?? '-'); ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value">
                            <i class="fas fa-envelope"></i> 
                            <?php echo htmlspecialchars($email); ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">ID Peminjaman</div>
                        <div class="info-value">
                            <i class="fas fa-hashtag"></i> 
                            <?php echo htmlspecialchars($data['id_pk']); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KENDARAAN -->
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-car"></i> Informasi Kendaraan</h4>
            </div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="info-item">
                        <div class="info-label">Nama Kendaraan</div>
                        <div class="info-value">
                            <i class="fas fa-car"></i> 
                            <?php echo htmlspecialchars($data['nama_kendaraan'] ?? '-'); ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Status Kendaraan</div>
                        <div class="info-value">
                            <i class="fas fa-cogs"></i> 
                            <?php echo htmlspecialchars(ucfirst($data['status_kendaraan'] ?? '-')); ?>
                        </div>
                    </div>
                </div>
                <?php if (!empty($data['deskripsi'])): ?>
                    <div class="divider"></div>
                    <div class="info-item">
                        <div class="info-label">Deskripsi Kendaraan</div>
                        <div class="info-value" style="font-weight: normal;">
                            <i class="fas fa-align-left"></i> 
                            <?php echo htmlspecialchars($data['deskripsi']); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- JADWAL PEMINJAMAN -->
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-calendar-alt"></i> Jadwal Peminjaman</h4>
            </div>
            <div class="card-body">
                <div class="section-title">
                    <i class="fas fa-calendar-check"></i>
                    Tanggal & Waktu
                </div>

                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-label">Mulai</div>
                        <div class="timeline-value"><?php echo $tglMulai; ?></div>
                        <div style="font-size: 0.85rem; color: var(--primary); font-weight: 600;">
                            <?php echo $wMulai; ?> WIB
                        </div>
                    </div>
                    <div class="timeline-arrow">→</div>
                    <div class="timeline-item">
                        <div class="timeline-label">Selesai</div>
                        <div class="timeline-value"><?php echo $tglSelesai; ?></div>
                        <div style="font-size: 0.85rem; color: var(--primary); font-weight: 600;">
                            <?php echo $wSelesai; ?> WIB
                        </div>
                    </div>
                </div>

                <div class="divider"></div>

                <div class="grid-2">
                    <div class="info-item">
                        <div class="info-label">Tujuan Peminjaman</div>
                        <div class="info-value">
                            <i class="fas fa-map-marker-alt"></i> 
                            <?php echo htmlspecialchars($tujuan); ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Nama Pengemudi</div>
                        <div class="info-value">
                            <i class="fas fa-user-tie"></i> 
                            <?php echo htmlspecialchars($pengemudi); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="card">
            <div class="card-body">
                <div class="actions">
                    <a href="javascript:history.back()" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali ke Daftar
                    </a>
                    <a href="javascript:window.print()" class="btn btn-print">
                        <i class="fas fa-print"></i> Cetak Detail
                    </a>
                </div>
            </div>
        </div>

    </div>
</body>
</html>
