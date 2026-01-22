<?php
// ========================================
// DATA PEMINJAMAN GEDUNG - PILLS DESIGN
// Sesuai gambar: Pills untuk fasilitas
// ========================================

// ================== PROSES HAPUS ==================
if(isset($_POST['hapus'])) {
    $id_pinjam = (int)$_POST['id_pinjam'];
    
    $stmt = $conn->prepare("DELETE FROM pinjambarang WHERE id_pinjam = ?");
    $stmt->bind_param("i", $id_pinjam);
    
    if($stmt->execute()) {
        echo "<script>alert('Peminjaman berhasil dibatalkan');</script>";
        echo "<script>window.location.href='?view=datapinjambarang';</script>";
        exit;
    }
    $stmt->close();
}

// ================== PROSES KEMBALIKAN ==================
elseif(isset($_POST['ubah'])) {
    $id_pinjam = (int)$_POST['id_pinjam'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $stmt = $conn->prepare("UPDATE pinjambarang SET status = ? WHERE id_pinjam = ?");
    $stmt->bind_param("si", $status, $id_pinjam);
    
    if($stmt->execute()) {
        echo "<script>alert('Berhasil Dikembalikan');</script>";
        echo "<script>window.location.href='?view=datapinjambarang';</script>";
        exit;
    }
    $stmt->close();
}

// ================== Helper Functions ==================
function fmt_tgl($ymd) {
    if(!$ymd || $ymd === '0000-00-00') return '-';
    $parts = explode('-', $ymd);
    if(count($parts) !== 3) return htmlspecialchars($ymd);
    [$y, $m, $d] = $parts;
    $bulan = ["", "Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
    $month_index = (int)$m;
    if($month_index < 1 || $month_index > 12) return htmlspecialchars($ymd);
    return sprintf("%d %s %s", (int)$d, $bulan[$month_index], $y);
}

function fmt_waktu($hms) {
    if(!$hms) return '-';
    return substr($hms, 0, 5);
}
?>

<style>
/* ========================================
   PILLS DESIGN - Sesuai Gambar
   ======================================== */

.page-gedung-pills {
  --primary: #0ea5e9;
  --success: #10b981;
  --warning: #f59e0b;
  --danger: #ef4444;
  --info: #06b6d4;
  --muted: #64748b;
  --txt: #1f2937;
  --bg: #f8fafc;
  --card: #ffffff;
  --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
  --shadow: 0 1px 3px rgba(0,0,0,0.1);
  --shadow-md: 0 4px 6px rgba(0,0,0,0.07);
  --border: #e2e8f0;
  --pill-bg: #e0f2fe;
  --pill-txt: #0369a1;
  --radius: 10px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

/* Toolbar */
.page-gedung-pills .toolbar {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 24px;
}

/* Card */
.page-gedung-pills .card {
  background: var(--card);
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border);
  overflow: hidden;
}

.page-gedung-pills .card-header {
  padding: 20px 24px;
  border-bottom: 1px solid var(--border);
  background: var(--bg);
}

.page-gedung-pills .card-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--txt);
  margin: 0;
}

.page-gedung-pills .card-body {
  padding: 0;
}

/* Table */
.page-gedung-pills .table-wrapper {
  overflow-x: auto;
}

.page-gedung-pills .table {
  width: 100%;
  border-collapse: collapse;
  margin: 0;
}

.page-gedung-pills .table thead {
  background: var(--bg);
}

.page-gedung-pills .table thead th {
  padding: 14px 16px;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--muted);
  border-bottom: 2px solid var(--border);
  white-space: nowrap;
}

.page-gedung-pills .table tbody td {
  padding: 16px;
  font-size: 0.875rem;
  color: var(--txt);
  border-bottom: 1px solid var(--border);
  vertical-align: middle;
}

.page-gedung-pills .table tbody tr {
  transition: background 0.15s ease;
}

.page-gedung-pills .table tbody tr:hover {
  background: var(--bg);
}

.page-gedung-pills .table tbody tr:last-child td {
  border-bottom: none;
}

/* Pills Container - Sesuai Gambar */
.page-gedung-pills .pills-container {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  max-width: 280px;
}

.page-gedung-pills .pill {
  display: inline-flex;
  align-items: center;
  padding: 6px 12px;
  background: var(--pill-bg);
  color: var(--pill-txt);
  border-radius: 999px;
  font-size: 0.813rem;
  font-weight: 500;
  white-space: nowrap;
  border: 1px solid rgba(3, 105, 161, 0.2);
}

/* Jadwal Format - Sesuai Gambar */
.page-gedung-pills .jadwal-text {
  font-size: 0.875rem;
  color: var(--txt);
  line-height: 1.6;
}

.page-gedung-pills .jadwal-date {
  font-weight: 600;
}

.page-gedung-pills .jadwal-time {
  color: var(--muted);
  font-size: 0.813rem;
}

/* Badge Status - Sesuai Gambar */
.page-gedung-pills .badge {
  display: inline-flex;
  align-items: center;
  padding: 6px 14px;
  border-radius: 6px;
  font-size: 0.813rem;
  font-weight: 600;
  letter-spacing: 0.025em;
}

.page-gedung-pills .badge-success {
  background: #d1fae5;
  color: #065f46;
}

.page-gedung-pills .badge-warning {
  background: #cf9a27ff;
  color: #9a3412;
}

.page-gedung-pills .badge-danger {
  background: #fee2e2;
  color: #991b1b;
}

.page-gedung-pills .badge-info {
  background: #cffafe;
  color: #0e7490;
}

/* Buttons - Sesuai Gambar */
.page-gedung-pills .btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 14px;
  border: none;
  border-radius: 6px;
  font-size: 0.813rem;
  font-weight: 500;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.15s ease;
  white-space: nowrap;
}

.page-gedung-pills .btn:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
  text-decoration: none;
}

.page-gedung-pills .btn-xs {
  padding: 7px 12px;
  font-size: 0.75rem;
}

.page-gedung-pills .btn-primary {
  background: var(--primary);
  color: white;
}

.page-gedung-pills .btn-primary:hover {
  background: #0284c7;
}

.page-gedung-pills .btn-icon {
  width: 32px;
  height: 32px;
  padding: 0;
  border-radius: 6px;
}

.page-gedung-pills .btn-success {
  background: var(--success);
  color: white;
}

.page-gedung-pills .btn-success:hover {
  background: #059669;
}

.page-gedung-pills .btn-danger {
  background: var(--danger);
  color: white;
}

.page-gedung-pills .btn-danger:hover {
  background: #dc2626;
}

/* Actions */
.page-gedung-pills .actions {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

/* Modal */
.page-gedung-pills .modal-content {
  border-radius: var(--radius);
  border: 1px solid var(--border);
  box-shadow: 0 20px 25px rgba(0,0,0,0.15);
}

.page-gedung-pills .modal-header {
  padding: 20px 24px;
  border-bottom: 1px solid var(--border);
  background: var(--bg);
}

.page-gedung-pills .modal-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--txt);
  margin: 0;
}

.page-gedung-pills .modal-body {
  padding: 24px;
}

.page-gedung-pills .modal-body h4 {
  font-size: 1rem;
  font-weight: 500;
  color: var(--txt);
  margin: 0;
}

.page-gedung-pills .modal-footer {
  padding: 16px 24px;
  border-top: 1px solid var(--border);
  background: var(--bg);
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

/* Footer */
.page-gedung-pills .footer {
  margin-top: 48px;
  padding-top: 24px;
  text-align: center;
  color: var(--muted);
  font-size: 0.813rem;
  border-top: 1px solid var(--border);
}

/* Empty State */
.page-gedung-pills .empty-state {
  text-align: center;
  padding: 64px 24px;
  color: var(--muted);
}

.page-gedung-pills .empty-state i {
  font-size: 4rem;
  opacity: 0.2;
  display: block;
  margin-bottom: 16px;
}

/* Dark Mode */
body[data-theme="dark"] .page-gedung-pills {
  --txt: #f1f5f9;
  --bg: #0f172a;
  --card: #1e293b;
  --border: #334155;
  --muted: #94a3b8;
  --pill-bg: rgba(14, 165, 233, 0.15);
  --pill-txt: #67e8f9;
}

body[data-theme="dark"] .page-gedung-pills .badge-success {
  background: rgba(16, 185, 129, 0.2);
  color: #6ee7b7;
}

body[data-theme="dark"] .page-gedung-pills .badge-danger {
  background: rgba(239, 68, 68, 0.2);
  color: #fca5a5;
}

body[data-theme="dark"] .page-gedung-pills .badge-warning {
  background: rgba(245, 158, 11, 0.2);
  color: #fbbf24;
}

/* Responsive */
@media (max-width: 768px) {
  .page-gedung-pills .card-header {
    padding: 16px;
  }
  
  .page-gedung-pills .toolbar {
    width: 100%;
  }
  
  .page-gedung-pills .btn {
    width: 100%;
    justify-content: center;
  }
  
  .page-gedung-pills .table thead {
    display: none;
  }
  
  .page-gedung-pills .table tbody tr {
    display: block;
    margin-bottom: 16px;
    border: 1px solid var(--border);
    border-radius: 8px;
    overflow: hidden;
  }
  
  .page-gedung-pills .table tbody td {
    display: block;
    padding: 12px 16px;
    border-bottom: 1px solid var(--border);
    text-align: left;
  }
  
  .page-gedung-pills .table tbody td:last-child {
    border-bottom: none;
  }
  
  .page-gedung-pills .table tbody td::before {
    content: attr(data-label);
    font-weight: 600;
    color: var(--muted);
    font-size: 0.75rem;
    text-transform: uppercase;
    display: block;
    margin-bottom: 6px;
  }
  
  .page-gedung-pills .pills-container {
    max-width: 100%;
  }
  
  .page-gedung-pills .actions {
    flex-direction: column;
  }
  
  .page-gedung-pills .btn-xs,
  .page-gedung-pills .btn-icon {
    width: 100%;
    justify-content: center;
  }
}
</style>

<div class="page-gedung-pills">
  <div class="row">
    <div class="col-md-12">
      
      <!-- Toolbar -->
      <div class="toolbar">
        <a href="?view=createpinjambarang" class="btn btn-primary">
          <i class="fa fa-plus"></i> Tambah Peminjaman
        </a>
      </div>

      <!-- Card -->
      <div class="card">
        <div class="card-header">
          <h4 class="card-title">Data Peminjaman Gedung</h4>
        </div>
        <div class="card-body">
          <div class="table-wrapper">
            <table class="table">
              <thead>
                <tr>
                  <th style="width: 50px;">No</th>
                  <th style="width: 150px;">Peminjam</th>
                  <th style="width: 180px;">Gedung</th>
                  <th style="width: 280px;">Perlengkapan</th>
                  <th style="width: 200px;">Jadwal</th>
                  <th style="width: 110px;">Status</th>
                  <th style="width: 100px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $no = 1;
                $user_id = $_SESSION['id'];
                
                $stmt = $conn->prepare("SELECT 
                    pb.*,
                    u.nama_lengkap,
                    u.username,
                    b.nama_barang
                  FROM pinjambarang pb
                  INNER JOIN user u ON u.id = pb.id_user
                  INNER JOIN barang b ON b.id = pb.id_barang
                  WHERE pb.id_user = ?
                  ORDER BY pb.id_pinjam DESC");

                
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if($result->num_rows > 0) {
                  while ($pinjambarang = $result->fetch_assoc()) {
                    // Format tanggal
                    $tgl_mulai = fmt_tgl($pinjambarang['tgl_mulai']);
                    $tgl_selesai = fmt_tgl($pinjambarang['tgl_selesai']);
                    $waktu_mulai = fmt_waktu($pinjambarang['waktu_mulai']);
                    $waktu_selesai = fmt_waktu($pinjambarang['waktu_selesai']);
                ?>
                <tr>
                  <td data-label="No"><strong><?php echo $no++; ?></strong></td>
                  
                  <td data-label="Peminjam">
                    <?php echo htmlspecialchars($pinjambarang['username'] ?? $pinjambarang['nama_lengkap']); ?>
                  </td>
                  
                  <td data-label="Gedung">
                    <strong><?php echo htmlspecialchars($pinjambarang['nama_barang']); ?></strong>
                  </td>
                  
                  <td data-label="Perlengkapan">
                    <div class="pills-container">
                      <?php 
                      // Meja
                      if(!empty($pinjambarang['meja']) && !empty($pinjambarang['jumlah_meja'])) {
                        echo '<span class="pill">Meja (' . htmlspecialchars($pinjambarang['jumlah_meja']) . ')</span>';
                      }
                      
                      // Kursi
                      if(!empty($pinjambarang['kursi']) && !empty($pinjambarang['jumlah_kursi'])) {
                        echo '<span class="pill">Kursi (' . htmlspecialchars($pinjambarang['jumlah_kursi']) . ')</span>';
                      }
                      
                      // Sound
                      if(!empty($pinjambarang['sound']) && $pinjambarang['sound'] !== '-') {
                        echo '<span class="pill">Sound</span>';
                      }
                      
                      // Proyektor
                      if(!empty($pinjambarang['proyektor']) && $pinjambarang['proyektor'] !== '-') {
                        echo '<span class="pill">Proyektor</span>';
                      }
                      ?>
                    </div>
                  </td>
                  
                  <td data-label="Jadwal">
                    <div class="jadwal-text">
                      <div class="jadwal-date">
                        <?php echo $tgl_mulai; ?> — <?php echo $tgl_selesai; ?>
                      </div>
                      <div class="jadwal-time">
                        <?php echo $waktu_mulai; ?> — <?php echo $waktu_selesai; ?>
                      </div>
                    </div>
                  </td>
                  
                  <td data-label="Status">
                    <?php if($pinjambarang['status'] == 'menunggu') { ?>
                      <span class="badge badge-warning">Menunggu</span>
                    <?php } elseif($pinjambarang['status'] == 'approve') { ?>
                      <span class="badge badge-success">Disetujui</span>
                    <?php } elseif($pinjambarang['status'] == 'selesai') { ?>
                      <span class="badge badge-info">Selesai</span>
                    <?php } else { ?>
                      <span class="badge badge-danger"><?php echo ucfirst(htmlspecialchars($pinjambarang['status'])); ?></span>
                    <?php } ?>
                  </td>
                  
                  <td data-label="Aksi">
                    <div class="actions">
                      <a href="?view=detailpinjambarang&id=<?php echo $pinjambarang['id_pinjam']; ?>" 
                         class="btn btn-icon btn-success"
                         title="Lihat Detail">
                        <i class="fa fa-eye"></i>
                      </a>
                      
                      <?php if($pinjambarang['status'] == 'menunggu') { ?>
                        <a href="#modalHapus<?php echo $pinjambarang['id_pinjam']; ?>" 
                           data-toggle="modal" 
                           class="btn btn-icon btn-danger"
                           title="Batalkan">
                          <i class="fa fa-times"></i>
                        </a>
                      <?php } elseif($pinjambarang['status'] == 'approve') { ?>
                        <a href="#modalKembalikan<?php echo $pinjambarang['id_pinjam']; ?>" 
                           data-toggle="modal" 
                           class="btn btn-icon btn-warning"
                           title="Selesai">
                          <i class="fa fa-check"></i>
                        </a>
                      <?php } ?>
                    </div>
                  </td>
                </tr>
                <?php 
                  }
                } else {
                ?>
                <tr>
                  <td colspan="7">
                    <div class="empty-state">
                      <i class="fa fa-inbox"></i>
                      <p>Belum ada data peminjaman gedung</p>
                    </div>
                  </td>
                </tr>
                <?php 
                }
                $stmt->close();
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- Footer -->
  <div class="footer">
    <strong>SIPINJAM</strong> &copy; 2025
  </div>
</div>

<!-- MODALS -->
<?php 
$stmt_modal = $conn->prepare("SELECT id_pinjam FROM pinjambarang WHERE id_user = ?");
$stmt_modal->bind_param("i", $user_id);
$stmt_modal->execute();
$result_modal = $stmt_modal->get_result();

while($row = $result_modal->fetch_assoc()) {
?>
<!-- Modal Hapus -->
<div class="modal fade" id="modalHapus<?php echo $row['id_pinjam']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fa fa-exclamation-circle"></i> Batalkan Peminjaman
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="POST" action="">
        <div class="modal-body">
          <input type="hidden" name="id_pinjam" value="<?php echo $row['id_pinjam']; ?>">
          <h4>Apakah Anda yakin ingin membatalkan peminjaman ini?</h4>
          <p style="color: var(--muted); font-size: 0.875rem; margin: 8px 0 0 0;">
            Tindakan ini tidak dapat dibatalkan.
          </p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
            Tidak
          </button>
          <button type="submit" name="hapus" class="btn btn-danger">
            <i class="fa fa-trash"></i> Ya, Batalkan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Kembalikan -->
<div class="modal fade" id="modalKembalikan<?php echo $row['id_pinjam']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fa fa-check-circle"></i> Selesaikan Peminjaman
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="POST" action="">
        <div class="modal-body">
          <input type="hidden" name="id_pinjam" value="<?php echo $row['id_pinjam']; ?>">
          <input type="hidden" name="status" value="selesai">
          <h4>Tandai peminjaman sebagai selesai?</h4>
          <p style="color: var(--muted); font-size: 0.875rem; margin: 8px 0 0 0;">
            Peminjaman akan diarsipkan dengan status selesai.
          </p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
            Tidak
          </button>
          <button type="submit" name="ubah" class="btn btn-success">
            <i class="fa fa-check"></i> Ya, Selesai
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php 
}
$stmt_modal->close();
?>
