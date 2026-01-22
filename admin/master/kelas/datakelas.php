<?php
include '../koneksi.php';

// Helper function untuk XSS prevention
function safe($v) {
    return htmlspecialchars((string)$v ?? '', ENT_QUOTES, 'UTF-8');
}

// ========== PROSES CRUD dengan Prepared Statements ==========
if(isset($_POST['simpan'])) {
    $nama_kelas = trim($_POST['nama_kelas']);
    
    if(!empty($nama_kelas)) {
        $stmt = $conn->prepare("INSERT INTO kelas (nama_kelas) VALUES (?)");
        $stmt->bind_param("s", $nama_kelas);
        
        if($stmt->execute()) {
            echo "<script>alert('Data Berhasil Disimpan');</script>";
        } else {
            echo "<script>alert('Gagal menyimpan data!');</script>";
        }
        $stmt->close();
    }
    echo "<meta http-equiv='refresh' content='0; URL=?view=datakelas'>";
}

elseif(isset($_POST['ubah'])) {
    $id_kelas = (int)$_POST['id_kelas'];
    $nama_kelas = trim($_POST['nama_kelas']);
    
    if(!empty($nama_kelas) && $id_kelas > 0) {
        $stmt = $conn->prepare("UPDATE kelas SET nama_kelas=? WHERE id_kelas=?");
        $stmt->bind_param("si", $nama_kelas, $id_kelas);
        
        if($stmt->execute()) {
            echo "<script>alert('Data Berhasil Diubah');</script>";
        } else {
            echo "<script>alert('Gagal mengubah data!');</script>";
        }
        $stmt->close();
    }
    echo "<meta http-equiv='refresh' content='0; URL=?view=datakelas'>";
}

elseif(isset($_POST['hapus'])) {
    $id_kelas = (int)$_POST['id_kelas'];
    
    if($id_kelas > 0) {
        $stmt = $conn->prepare("DELETE FROM kelas WHERE id_kelas=?");
        $stmt->bind_param("i", $id_kelas);
        
        if($stmt->execute()) {
            echo "<script>alert('Data Berhasil Dihapus');</script>";
        } else {
            echo "<script>alert('Gagal menghapus data!');</script>";
        }
        $stmt->close();
    }
    echo "<meta http-equiv='refresh' content='0; URL=?view=datakelas'>";
}
?>

<!-- Reset CSS -->
<style>
.page-inner .kelas-content {
  all: revert !important;
}

.kelas-content,
.kelas-content * {
  box-sizing: border-box !important;
}

.kelas-content .page-header,
.kelas-content .card,
.kelas-content .card-header,
.kelas-content .card-body,
.kelas-content .row {
  margin: revert !important;
  padding: revert !important;
}

.kelas-content {
  --kc-primary: #3b82f6;
  --kc-success: #22c55e;
  --kc-danger: #ef4444;
  --kc-text: #1f2937;
  --kc-muted: #6b7280;
  --kc-border: #e5e7eb;
  --kc-card: #ffffff;
  --kc-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.kelas-content .page-header {
  display: flex !important;
  justify-content: space-between !important;
  align-items: flex-start !important;
  margin-bottom: 25px !important;
  padding-bottom: 15px !important;
  border-bottom: 2px solid var(--kc-border) !important;
}

.kelas-content .page-header .page-title {
  font-size: 1.75rem !important;
  font-weight: 700 !important;
  color: var(--kc-text) !important;
  margin: 0 !important;
}

.kelas-content .breadcrumbs {
  list-style: none !important;
  padding: 0 !important;
  margin: 0 !important;
  display: flex !important;
  gap: 8px !important;
  align-items: center !important;
  font-size: 0.9rem !important;
  color: var(--kc-muted) !important;
}

.kelas-content .breadcrumbs li {
  display: flex !important;
  align-items: center !important;
}

.kelas-content .breadcrumbs a {
  color: var(--kc-primary) !important;
  text-decoration: none !important;
}

.kelas-content .breadcrumbs a:hover {
  text-decoration: underline !important;
}

.kelas-content .row {
  display: flex !important;
  flex-wrap: wrap !important;
  margin: 0 -15px !important;
}

.kelas-content .col-md-12 {
  flex: 0 0 100% !important;
  max-width: 100% !important;
  padding: 0 15px !important;
}

.kelas-content .card {
  background: var(--kc-card) !important;
  border: 1px solid var(--kc-border) !important;
  border-radius: 12px !important;
  box-shadow: var(--kc-shadow) !important;
  margin-bottom: 20px !important;
}

.kelas-content .card-header {
  padding: 20px !important;
  background: #f9fafb !important;
  border-bottom: 1px solid var(--kc-border) !important;
}

.kelas-content .card-header .card-title {
  font-size: 1.1rem !important;
  font-weight: 700 !important;
  margin: 0 !important;
  color: var(--kc-text) !important;
}

.kelas-content .card-body {
  padding: 20px !important;
}

.kelas-content .d-flex {
  display: flex !important;
}

.kelas-content .align-items-center {
  align-items: center !important;
}

.kelas-content .ml-auto {
  margin-left: auto !important;
}

.kelas-content .btn {
  display: inline-flex !important;
  align-items: center !important;
  gap: 6px !important;
  padding: 8px 16px !important;
  font-size: 0.9rem !important;
  font-weight: 600 !important;
  border: none !important;
  border-radius: 8px !important;
  cursor: pointer !important;
  text-decoration: none !important;
  transition: all 0.2s ease !important;
}

.kelas-content .btn-primary {
  background: var(--kc-primary) !important;
  color: white !important;
}

.kelas-content .btn-primary:hover {
  background: #2563eb !important;
  transform: translateY(-2px) !important;
}

.kelas-content .btn-success {
  background: var(--kc-success) !important;
  color: white !important;
}

.kelas-content .btn-success:hover {
  background: #16a34a !important;
}

.kelas-content .btn-danger {
  background: var(--kc-danger) !important;
  color: white !important;
}

.kelas-content .btn-danger:hover {
  background: #dc2626 !important;
}

.kelas-content .btn-xs {
  padding: 5px 10px !important;
  font-size: 0.8rem !important;
}

.kelas-content .btn-round {
  border-radius: 50px !important;
}

.kelas-content .table-responsive {
  overflow-x: auto !important;
  -webkit-overflow-scrolling: touch !important;
}

.kelas-content .table {
  width: 100% !important;
  border-collapse: collapse !important;
}

.kelas-content .table thead th {
  font-weight: 700 !important;
  color: var(--kc-muted) !important;
  font-size: 0.85rem !important;
  text-transform: uppercase !important;
  padding: 12px !important;
  text-align: center !important;
  border-bottom: 2px solid var(--kc-border) !important;
  background: #f9fafb !important;
}

.kelas-content .table tbody td {
  padding: 12px !important;
  vertical-align: middle !important;
  border-bottom: 1px solid var(--kc-border) !important;
  text-align: center !important;
  font-size: 0.9rem !important;
}

.kelas-content .table-striped tbody tr:nth-of-type(odd) {
  background: rgba(0,0,0,0.02) !important;
}

.kelas-content .table-hover tbody tr:hover {
  background: rgba(59,130,246,0.05) !important;
}

.kelas-content .form-group {
  margin-bottom: 16px !important;
}

.kelas-content .form-group label {
  display: block !important;
  margin-bottom: 6px !important;
  font-weight: 600 !important;
  color: var(--kc-text) !important;
  font-size: 0.9rem !important;
}

.kelas-content .form-control {
  width: 100% !important;
  padding: 10px 12px !important;
  border: 1px solid var(--kc-border) !important;
  border-radius: 6px !important;
  font-size: 0.9rem !important;
}

.kelas-content .form-control:focus {
  outline: none !important;
  border-color: var(--kc-primary) !important;
  box-shadow: 0 0 0 3px rgba(59,130,246,0.1) !important;
}

.kelas-content .form-control:read-only {
  background: #f9fafb !important;
  cursor: not-allowed !important;
}

@media (max-width: 768px) {
  .kelas-content .page-header {
    flex-direction: column !important;
    gap: 10px !important;
  }
  
  .kelas-content .btn-xs {
    padding: 4px 8px !important;
    font-size: 0.75rem !important;
  }
}
</style>

<!-- Wrapper -->
<div class="kelas-content">
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <div class="d-flex align-items-center">
            <button class="btn btn-primary btn-round ml-auto" data-toggle="modal" data-target="#modalAddKelas">
              <i class="fa fa-plus"></i> Tambah Data
            </button>
          </div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="add-row" class="display table table-striped table-hover">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Nama Kelas</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  $no = 1;
                  $query = mysqli_query($conn,'SELECT * FROM kelas ORDER BY id_kelas ASC');
                  while ($kelas = mysqli_fetch_array($query)) {
                ?>
                <tr>
                  <td><?= $no++ ?></td>
                  <td><?= safe($kelas['nama_kelas']) ?></td>
                  <td>
                    <a href="#modalDetailKelas<?= $kelas['id_kelas'] ?>" data-toggle="modal" title="Detail" class="btn btn-xs btn-success">
                      <i class="fa fa-eye"></i>
                    </a>
                    <a href="#modalEditKelas<?= $kelas['id_kelas'] ?>" data-toggle="modal" title="Edit" class="btn btn-xs btn-primary">
                      <i class="fa fa-edit"></i>
                    </a>
                    <a href="#modalHapusKelas<?= $kelas['id_kelas'] ?>" data-toggle="modal" title="Hapus" class="btn btn-xs btn-danger">
                      <i class="fa fa-trash"></i>
                    </a>
                  </td>
                </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalAddKelas" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header no-bd">
        <h5 class="modal-title">
          <span class="fw-mediumbold">Tambah</span> 
          <span class="fw-light">Kelas</span>
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="POST" action="">
        <div class="modal-body">
          <div class="form-group">
            <label>Nama Kelas</label>
            <input type="text" name="nama_kelas" class="form-control" placeholder="Contoh: X RPL 1, XI TKJ 2, XII MM 3..." required maxlength="50">
          </div>
        </div>
        <div class="modal-footer no-bd">
          <button type="submit" name="simpan" class="btn btn-primary">
            <i class="fa fa-save"></i> Simpan
          </button>
          <button type="button" class="btn btn-danger" data-dismiss="modal">
            <i class="fa fa-undo"></i> Batal
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Edit, Detail, Hapus -->
<?php 
$p = mysqli_query($conn,'SELECT * FROM kelas ORDER BY id_kelas ASC');
while($d = mysqli_fetch_array($p)) {
?>

<!-- Modal Edit -->
<div class="modal fade" id="modalEditKelas<?= $d['id_kelas'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header no-bd">
        <h5 class="modal-title">
          <span class="fw-mediumbold">Edit</span> 
          <span class="fw-light">Kelas</span>
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="POST" action="">
        <div class="modal-body">
          <input type="hidden" name="id_kelas" value="<?= $d['id_kelas'] ?>">
          <div class="form-group">
            <label>Nama Kelas</label>
            <input value="<?= safe($d['nama_kelas']) ?>" type="text" name="nama_kelas" class="form-control" placeholder="Nama Kelas..." required maxlength="50">
          </div>
        </div>
        <div class="modal-footer no-bd">
          <button type="submit" name="ubah" class="btn btn-primary">
            <i class="fa fa-save"></i> Update
          </button>
          <button type="button" class="btn btn-danger" data-dismiss="modal">
            <i class="fa fa-undo"></i> Batal
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Hapus -->
<div class="modal fade" id="modalHapusKelas<?= $d['id_kelas'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header no-bd">
        <h5 class="modal-title">
          <span class="fw-mediumbold">Hapus</span> 
          <span class="fw-light">Kelas</span>
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="POST" action="">
        <div class="modal-body">
          <input type="hidden" name="id_kelas" value="<?= $d['id_kelas'] ?>">
          <h4>Apakah Anda yakin ingin menghapus data ini?</h4>
          <p style="margin-top:12px; padding:12px; background:#fef2f2; border:1px solid #fca5a5; border-radius:8px; color:#991b1b;">
            <strong>Kelas: <?= safe($d['nama_kelas']) ?></strong><br>
            <small>Data yang sudah dihapus tidak dapat dikembalikan.</small>
          </p>
        </div>
        <div class="modal-footer no-bd">
          <button type="submit" name="hapus" class="btn btn-danger">
            <i class="fa fa-trash"></i> Ya, Hapus
          </button>
          <button type="button" class="btn btn-primary" data-dismiss="modal">
            <i class="fa fa-undo"></i> Batal
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="modalDetailKelas<?= $d['id_kelas'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header no-bd">
        <h5 class="modal-title">
          <span class="fw-mediumbold">Detail</span> 
          <span class="fw-light">Kelas</span>
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>ID Kelas</label>
          <input readonly value="<?= safe($d['id_kelas']) ?>" type="text" class="form-control">
        </div>
        <div class="form-group">
          <label>Nama Kelas</label>
          <input readonly value="<?= safe($d['nama_kelas']) ?>" type="text" class="form-control">
        </div>
      </div>
      <div class="modal-footer no-bd">
        <button type="button" class="btn btn-danger" data-dismiss="modal">
          <i class="fa fa-undo"></i> Tutup
        </button>
      </div>
    </div>
  </div>
</div>

<?php } ?>
