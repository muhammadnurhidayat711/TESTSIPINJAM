<?php
// ========================================
// OPTIMIZED CODE - MODERN MINIMALIST THEME
// ========================================

// ================== PROSES AKSI ==================
if(isset($_POST['hapus'])) {
    $id_pinjamkolam = (int)$_POST['id_pinjamkolam'];
    
    // Prepared statement untuk keamanan
    $stmt = $conn->prepare("DELETE FROM pinjamkolam WHERE id_pinjamkolam = ?");
    $stmt->bind_param("i", $id_pinjamkolam);
    
    if($stmt->execute()) {
        echo "<script>alert('Data Berhasil Dihapus');</script>";
        echo "<script>window.location.href='?view=datapinjamkolam';</script>";
        exit;
    } else {
        echo "<script>alert('Gagal menghapus data');</script>";
    }
    $stmt->close();
}
elseif(isset($_POST['ubah'])) {
    $id_pinjam = (int)$_POST['id_pinjamkolam'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Prepared statement untuk keamanan
    $stmt = $conn->prepare("UPDATE pinjamkolam SET status = ? WHERE id_pinjamkolam = ?");
    $stmt->bind_param("si", $status, $id_pinjam);
    
    if($stmt->execute()) {
        echo "<script>alert('Berhasil Dikembalikan');</script>";
        echo "<script>window.location.href='?view=datapinjamkolam';</script>";
        exit;
    } else {
        echo "<script>alert('Gagal mengupdate status');</script>";
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
    return sprintf("%02d %s %s", (int)$d, $bulan[$month_index], $y);
}

function fmt_waktu($hms) {
    return $hms ? substr($hms, 0, 5) : '-';
}
?>

<style>
/* Modern Minimalist Theme - Clean & Elegant */
.page-pinjamkolam {
  --primary: #2563eb;
  --success: #10b981;
  --warning: #f59e0b;
  --danger: #ef4444;
  --muted: #64748b;
  --txt: #0f172a;
  --bg: #f8fafc;
  --card: #ffffff;
  --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
  --shadow: 0 4px 6px rgba(0,0,0,0.07);
  --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
  --border: #e2e8f0;
  --radius: 12px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

.page-pinjamkolam * {
  box-sizing: border-box;
}

/* Container */
.page-pinjamkolam .container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px;
}

/* Toolbar - Minimalist */
.page-pinjamkolam .toolbar {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 24px;
}

/* Button Styles - Simple & Clean */
.page-pinjamkolam .btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 9px 18px;
  border: none;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.15s ease;
  white-space: nowrap;
}

.page-pinjamkolam .btn:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow);
}

.page-pinjamkolam .btn:active {
  transform: translateY(0);
}

.page-pinjamkolam .btn-primary {
  background: var(--primary);
  color: white;
}

.page-pinjamkolam .btn-primary:hover {
  background: #1d4ed8;
}

.page-pinjamkolam .btn-success {
  background: var(--success);
  color: white;
}

.page-pinjamkolam .btn-success:hover {
  background: #059669;
}

.page-pinjamkolam .btn-warning {
  background: var(--warning);
  color: white;
}

.page-pinjamkolam .btn-warning:hover {
  background: #d97706;
}

.page-pinjamkolam .btn-danger {
  background: var(--danger);
  color: white;
}

.page-pinjamkolam .btn-danger:hover {
  background: #dc2626;
}

.page-pinjamkolam .btn-secondary {
  background: var(--muted);
  color: white;
}

.page-pinjamkolam .btn-secondary:hover {
  background: #475569;
}

.page-pinjamkolam .btn-sm {
  padding: 6px 12px;
  font-size: 0.813rem;
}

/* Table - Clean Minimalist Design */
.page-pinjamkolam .table-container {
  background: var(--card);
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.page-pinjamkolam .table-wrapper {
  overflow-x: auto;
}

.page-pinjamkolam .table {
  width: 100%;
  border-collapse: collapse;
  margin: 0;
}

.page-pinjamkolam .table thead {
  background: var(--bg);
}

.page-pinjamkolam .table thead th {
  padding: 16px 12px;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--muted);
  border-bottom: 1px solid var(--border);
}

.page-pinjamkolam .table thead th:first-child {
  padding-left: 24px;
}

.page-pinjamkolam .table thead th:last-child {
  padding-right: 24px;
}

.page-pinjamkolam .table tbody td {
  padding: 16px 12px;
  font-size: 0.875rem;
  color: var(--txt);
  border-bottom: 1px solid var(--border);
  vertical-align: middle;
}

.page-pinjamkolam .table tbody td:first-child {
  padding-left: 24px;
  font-weight: 600;
}

.page-pinjamkolam .table tbody td:last-child {
  padding-right: 24px;
}

.page-pinjamkolam .table tbody tr:last-child td {
  border-bottom: none;
}

.page-pinjamkolam .table tbody tr {
  transition: background 0.15s ease;
}

.page-pinjamkolam .table tbody tr:hover {
  background: var(--bg);
}

/* Badge - Minimalist Pills */
.page-pinjamkolam .badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 12px;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.025em;
}

.page-pinjamkolam .badge-success {
  background: #d1fae5;
  color: #065f46;
}

.page-pinjamkolam .badge-warning {
  background: #cf9a27ff;
  color: #92400e;
}

/* Actions - Inline Buttons */
.page-pinjamkolam .actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

/* Footer - Simple */
.page-pinjamkolam .footer {
  margin-top: 48px;
  padding-top: 24px;
  border-top: 1px solid var(--border);
  text-align: center;
  color: var(--muted);
  font-size: 0.813rem;
}

/* Modal - Clean & Modern */
.page-pinjamkolam .modal-content {
  border-radius: var(--radius);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-lg);
}

.page-pinjamkolam .modal-header {
  padding: 20px 24px;
  border-bottom: 1px solid var(--border);
}

.page-pinjamkolam .modal-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--txt);
  margin: 0;
}

.page-pinjamkolam .modal-body {
  padding: 24px;
}

.page-pinjamkolam .modal-body h4 {
  font-size: 1rem;
  font-weight: 500;
  color: var(--txt);
  margin: 0 0 8px 0;
}

.page-pinjamkolam .modal-body .text-muted {
  color: var(--muted);
  font-size: 0.875rem;
  margin: 0;
}

.page-pinjamkolam .modal-footer {
  padding: 16px 24px;
  border-top: 1px solid var(--border);
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

/* Dark Mode Support */
body[data-theme="dark"] .page-pinjamkolam {
  --txt: #f1f5f9;
  --bg: #0f172a;
  --card: #1e293b;
  --border: #334155;
  --muted: #94a3b8;
}

body[data-theme="dark"] .page-pinjamkolam .badge-success {
  background: rgba(16, 185, 129, 0.2);
  color: #6ee7b7;
}

body[data-theme="dark"] .page-pinjamkolam .badge-warning {
  background: rgba(245, 158, 11, 0.2);
  color: #fbbf24;
}

body[data-theme="dark"] .page-pinjamkolam .modal-content {
  background: #1e293b;
  color: #f1f5f9;
}

/* Responsive Design */
@media (max-width: 768px) {
  .page-pinjamkolam .container {
    padding: 16px;
  }
  
  .page-pinjamkolam .table thead {
    display: none;
  }
  
  .page-pinjamkolam .table tbody tr {
    display: block;
    margin-bottom: 16px;
    border: 1px solid var(--border);
    border-radius: 8px;
    overflow: hidden;
  }
  
  .page-pinjamkolam .table tbody td {
    display: flex;
    justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 1px solid var(--border);
  }
  
  .page-pinjamkolam .table tbody td:first-child,
  .page-pinjamkolam .table tbody td:last-child {
    padding: 12px 16px;
  }
  
  .page-pinjamkolam .table tbody td::before {
    content: attr(data-label);
    font-weight: 600;
    color: var(--muted);
    font-size: 0.75rem;
    text-transform: uppercase;
  }
  
  .page-pinjamkolam .table tbody td:last-child {
    border-bottom: none;
  }
  
  .page-pinjamkolam .actions {
    flex-direction: column;
    width: 100%;
  }
  
  .page-pinjamkolam .btn-sm {
    width: 100%;
    justify-content: center;
  }
}

/* Print Styles */
@media print {
  .page-pinjamkolam .toolbar,
  .page-pinjamkolam .actions,
  .page-pinjamkolam .footer {
    display: none;
  }
}
</style>

<div class="page-pinjamkolam">
  <div class="container">
    
    <!-- Toolbar Minimalist -->
    <div class="toolbar">
      <a href="?view=createpinjamkolam" class="btn btn-primary">
        <i class="fa fa-plus"></i>
        Tambah Peminjaman
      </a>
    </div>

    <!-- Table Container -->
    <div class="table-container">
      <div class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>No</th>
              <th>Fasilitas</th>
              <th>Kelas</th>
              <th>Tanggal</th>
              <th>Waktu</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $no = 1;
              $user_id = $_SESSION['id'];
              
              // Prepared statement untuk keamanan
              $stmt = $conn->prepare("SELECT pk.*, u.nama_lengkap, k.jenis_kolam, kl.nama_kelas 
                                      FROM pinjamkolam pk
                                      INNER JOIN user u ON u.id = pk.id_user
                                      INNER JOIN kolam k ON k.id_kolam = pk.id_kolam
                                      INNER JOIN kelas kl ON kl.id_kelas = pk.id_kelas
                                      WHERE pk.id_user = ?
                                      ORDER BY pk.tgl_mulai DESC, pk.waktu_mulai DESC");
              $stmt->bind_param("i", $user_id);
              $stmt->execute();
              $result = $stmt->get_result();
              
              if($result->num_rows > 0) {
                while ($pinjamkolam = $result->fetch_assoc()) {
                  $tgl = fmt_tgl($pinjamkolam['tgl_mulai']);
                  $waktu_mulai = fmt_waktu($pinjamkolam['waktu_mulai']);
                  $waktu_selesai = fmt_waktu($pinjamkolam['waktu_selesai']);
                  $waktu_range = $waktu_mulai . ' - ' . $waktu_selesai;
            ?>
            <tr>
              <td data-label="No"><?php echo $no++; ?></td>
              <td data-label="Fasilitas"><?php echo htmlspecialchars($pinjamkolam['jenis_kolam']); ?></td>
              <td data-label="Kelas"><?php echo htmlspecialchars($pinjamkolam['nama_kelas']); ?></td>
              <td data-label="Tanggal"><?php echo $tgl; ?></td>
              <td data-label="Waktu"><?php echo $waktu_range; ?></td>
              <td data-label="Status">
                <?php if($pinjamkolam['status'] == 'menunggu') { ?>
                  <span class="badge badge-warning">Menunggu</span>
                <?php } else { ?>
                  <span class="badge badge-success"><?php echo ucfirst(htmlspecialchars($pinjamkolam['status'])); ?></span>
                <?php } ?>
              </td>
              <td data-label="Aksi">
                <div class="actions">
                  <a href="?view=detailpinjamkolam&id=<?php echo $pinjamkolam['id_pinjamkolam']; ?>" 
                     class="btn btn-sm btn-success">
                    <i class="fa fa-eye"></i> Detail
                  </a>
                  
                  <?php if($pinjamkolam['status'] == 'menunggu') { ?>
                    <a href="#modalHapus<?php echo $pinjamkolam['id_pinjamkolam']; ?>" 
                       data-toggle="modal" 
                       class="btn btn-sm btn-danger">
                      <i class="fa fa-times"></i> Batal
                    </a>
                  <?php } elseif($pinjamkolam['status'] == 'approve') { ?>
                    <a href="#modalKembalikan<?php echo $pinjamkolam['id_pinjamkolam']; ?>" 
                       data-toggle="modal" 
                       class="btn btn-sm btn-warning">
                      <i class="fa fa-check"></i> Selesai
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
              <td colspan="7" style="text-align: center; padding: 48px; color: var(--muted);">
                <i class="fa fa-inbox" style="font-size: 3rem; opacity: 0.3; display: block; margin-bottom: 16px;"></i>
                Belum ada data peminjaman
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

    <!-- Footer -->
    <div class="footer">
      <strong>SIPINJAM</strong> &copy; 2025
    </div>

  </div>
</div>

<!-- MODALS -->
<?php 
  $stmt_modal = $conn->prepare("SELECT id_pinjamkolam FROM pinjamkolam WHERE id_user = ?");
  $stmt_modal->bind_param("i", $user_id);
  $stmt_modal->execute();
  $result_modal = $stmt_modal->get_result();
  
  while($row = $result_modal->fetch_assoc()) {
?>
<!-- Modal Hapus -->
<div class="modal fade" id="modalHapus<?php echo $row['id_pinjamkolam']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
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
          <input type="hidden" name="id_pinjamkolam" value="<?php echo $row['id_pinjamkolam']; ?>">
          <h4>Apakah Anda yakin ingin membatalkan peminjaman ini?</h4>
          <p class="text-muted">Tindakan ini tidak dapat dibatalkan.</p>
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
<div class="modal fade" id="modalKembalikan<?php echo $row['id_pinjamkolam']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
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
          <input type="hidden" name="id_pinjamkolam" value="<?php echo $row['id_pinjamkolam']; ?>">
          <input type="hidden" name="status" value="selesai">
          <h4>Tandai peminjaman sebagai selesai?</h4>
          <p class="text-muted">Peminjaman akan diarsipkan dengan status selesai.</p>
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
