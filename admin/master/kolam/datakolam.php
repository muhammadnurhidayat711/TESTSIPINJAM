<?php
include '../koneksi.php';

// Helper function
function safe($v) {
    return htmlspecialchars((string)$v ?? '', ENT_QUOTES, 'UTF-8');
}

// ========== PROSES CRUD ==========
if(isset($_POST['simpan'])) {
    $jenis_kolam = trim($_POST['jenis_kolam']);
    $deskripsi = trim($_POST['deskripsi']);
    
    // File upload validation
    $foto = '';
    if(isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        $file_type = $_FILES['foto']['type'];
        $file_size = $_FILES['foto']['size'];
        $file_tmp = $_FILES['foto']['tmp_name'];
        $file_name = $_FILES['foto']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if(!in_array($file_type, $allowed_types) || !in_array($file_ext, $allowed_ext)) {
            echo "<script>alert('Format file tidak diizinkan! Hanya JPG, PNG, GIF.');</script>";
            echo "<meta http-equiv='refresh' content='0; URL=?view=datakolam'>";
            exit;
        }
        
        if($file_size > $max_size) {
            echo "<script>alert('Ukuran file terlalu besar! Maksimal 5MB.');</script>";
            echo "<meta http-equiv='refresh' content='0; URL=?view=datakolam'>";
            exit;
        }
        
        $foto = uniqid('kolam_', true) . '.' . $file_ext;
        $upload_path = 'master/kolam/Fotokolam/' . $foto;
        
        if(!move_uploaded_file($file_tmp, $upload_path)) {
            echo "<script>alert('Gagal upload file!');</script>";
            echo "<meta http-equiv='refresh' content='0; URL=?view=datakolam'>";
            exit;
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO kolam (jenis_kolam, deskripsi, foto) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $jenis_kolam, $deskripsi, $foto);
    
    if($stmt->execute()) {
        echo "<script>alert('Data Berhasil Disimpan');</script>";
    } else {
        echo "<script>alert('Gagal menyimpan data!');</script>";
    }
    $stmt->close();
    echo "<meta http-equiv='refresh' content='0; URL=?view=datakolam'>";
}

elseif(isset($_POST['ubah'])) {
    $id_kolam = (int)$_POST['id_kolam'];
    $jenis_kolam = trim($_POST['jenis_kolam']);
    $deskripsi = trim($_POST['deskripsi']);
    
    $foto = '';
    $update_foto = false;
    
    if(isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $max_size = 5 * 1024 * 1024;
        
        $file_type = $_FILES['foto']['type'];
        $file_size = $_FILES['foto']['size'];
        $file_tmp = $_FILES['foto']['tmp_name'];
        $file_name = $_FILES['foto']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if(in_array($file_type, $allowed_types) && in_array($file_ext, $allowed_ext) && $file_size <= $max_size) {
            $foto = uniqid('kolam_', true) . '.' . $file_ext;
            $upload_path = 'master/kolam/Fotokolam/' . $foto;
            
            if(move_uploaded_file($file_tmp, $upload_path)) {
                $update_foto = true;
                
                // Hapus foto lama
                $old_photo = $conn->prepare("SELECT foto FROM kolam WHERE id_kolam = ?");
                $old_photo->bind_param("i", $id_kolam);
                $old_photo->execute();
                $result = $old_photo->get_result();
                if($row = $result->fetch_assoc()) {
                    $old_file = 'master/kolam/Fotokolam/' . $row['foto'];
                    if(file_exists($old_file)) unlink($old_file);
                }
                $old_photo->close();
            }
        }
    }
    
    if($update_foto) {
        $stmt = $conn->prepare("UPDATE kolam SET jenis_kolam=?, deskripsi=?, foto=? WHERE id_kolam=?");
        $stmt->bind_param("sssi", $jenis_kolam, $deskripsi, $foto, $id_kolam);
    } else {
        $stmt = $conn->prepare("UPDATE kolam SET jenis_kolam=?, deskripsi=? WHERE id_kolam=?");
        $stmt->bind_param("ssi", $jenis_kolam, $deskripsi, $id_kolam);
    }
    
    if($stmt->execute()) {
        echo "<script>alert('Data Berhasil Diubah');</script>";
    } else {
        echo "<script>alert('Gagal mengubah data!');</script>";
    }
    $stmt->close();
    echo "<meta http-equiv='refresh' content='0; URL=?view=datakolam'>";
}

elseif(isset($_POST['hapus'])) {
    $id_kolam = (int)$_POST['id_kolam'];
    
    // Hapus foto
    $get_foto = $conn->prepare("SELECT foto FROM kolam WHERE id_kolam = ?");
    $get_foto->bind_param("i", $id_kolam);
    $get_foto->execute();
    $result = $get_foto->get_result();
    if($row = $result->fetch_assoc()) {
        $file_path = 'master/kolam/Fotokolam/' . $row['foto'];
        if(file_exists($file_path)) unlink($file_path);
    }
    $get_foto->close();
    
    $stmt = $conn->prepare("DELETE FROM kolam WHERE id_kolam=?");
    $stmt->bind_param("i", $id_kolam);
    
    if($stmt->execute()) {
        echo "<script>alert('Data Berhasil Dihapus');</script>";
    } else {
        echo "<script>alert('Gagal menghapus data!');</script>";
    }
    $stmt->close();
    echo "<meta http-equiv='refresh' content='0; URL=?view=datakolam'>";
}
?>

<style>
.page-inner .kolam-content {
  all: revert !important;
}

.kolam-content,
.kolam-content * {
  box-sizing: border-box !important;
}

.kolam-content .page-header,
.kolam-content .card,
.kolam-content .card-header,
.kolam-content .card-body,
.kolam-content .row {
  margin: revert !important;
  padding: revert !important;
}

.kolam-content {
  --kol-primary: #3b82f6;
  --kol-success: #22c55e;
  --kol-danger: #ef4444;
  --kol-text: #1f2937;
  --kol-muted: #6b7280;
  --kol-border: #e5e7eb;
  --kol-card: #ffffff;
  --kol-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.kolam-content .page-header {
  display: flex !important;
  justify-content: space-between !important;
  align-items: flex-start !important;
  margin-bottom: 25px !important;
  padding-bottom: 15px !important;
  border-bottom: 2px solid var(--kol-border) !important;
}

.kolam-content .page-header .page-title {
  font-size: 1.75rem !important;
  font-weight: 700 !important;
  color: var(--kol-text) !important;
  margin: 0 !important;
}

.kolam-content .breadcrumbs {
  list-style: none !important;
  padding: 0 !important;
  margin: 0 !important;
  display: flex !important;
  gap: 8px !important;
  align-items: center !important;
  font-size: 0.9rem !important;
  color: var(--kol-muted) !important;
}

.kolam-content .breadcrumbs li {
  display: flex !important;
  align-items: center !important;
}

.kolam-content .breadcrumbs a {
  color: var(--kol-primary) !important;
  text-decoration: none !important;
}

.kolam-content .row {
  display: flex !important;
  flex-wrap: wrap !important;
  margin: 0 -15px !important;
}

.kolam-content .col-md-12 {
  flex: 0 0 100% !important;
  max-width: 100% !important;
  padding: 0 15px !important;
}

.kolam-content .card {
  background: var(--kol-card) !important;
  border: 1px solid var(--kol-border) !important;
  border-radius: 12px !important;
  box-shadow: var(--kol-shadow) !important;
  margin-bottom: 20px !important;
}

.kolam-content .card-header {
  padding: 20px !important;
  background: #f9fafb !important;
  border-bottom: 1px solid var(--kol-border) !important;
}

.kolam-content .card-header .card-title {
  font-size: 1.1rem !important;
  font-weight: 700 !important;
  margin: 0 !important;
  color: var(--kol-text) !important;
}

.kolam-content .card-body {
  padding: 20px !important;
}

.kolam-content .d-flex {
  display: flex !important;
}

.kolam-content .align-items-center {
  align-items: center !important;
}

.kolam-content .ml-auto {
  margin-left: auto !important;
}

.kolam-content .btn {
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

.kolam-content .btn-primary {
  background: var(--kol-primary) !important;
  color: white !important;
}

.kolam-content .btn-primary:hover {
  background: #2563eb !important;
  transform: translateY(-2px) !important;
}

.kolam-content .btn-success {
  background: var(--kol-success) !important;
  color: white !important;
}

.kolam-content .btn-success:hover {
  background: #16a34a !important;
}

.kolam-content .btn-danger {
  background: var(--kol-danger) !important;
  color: white !important;
}

.kolam-content .btn-danger:hover {
  background: #dc2626 !important;
}

.kolam-content .btn-xs {
  padding: 5px 10px !important;
  font-size: 0.8rem !important;
}

.kolam-content .btn-round {
  border-radius: 50px !important;
}

.kolam-content .table-responsive {
  overflow-x: auto !important;
  -webkit-overflow-scrolling: touch !important;
}

.kolam-content .table {
  width: 100% !important;
  border-collapse: collapse !important;
}

.kolam-content .table thead th {
  font-weight: 700 !important;
  color: var(--kol-muted) !important;
  font-size: 0.85rem !important;
  text-transform: uppercase !important;
  padding: 12px !important;
  text-align: center !important;
  border-bottom: 2px solid var(--kol-border) !important;
  background: #f9fafb !important;
}

.kolam-content .table tbody td {
  padding: 12px !important;
  vertical-align: middle !important;
  border-bottom: 1px solid var(--kol-border) !important;
  text-align: center !important;
}

.kolam-content .table-striped tbody tr:nth-of-type(odd) {
  background: rgba(0,0,0,0.02) !important;
}

.kolam-content .table-hover tbody tr:hover {
  background: rgba(59,130,246,0.05) !important;
}

.kolam-content .form-group {
  margin-bottom: 16px !important;
}

.kolam-content .form-group label {
  display: block !important;
  margin-bottom: 6px !important;
  font-weight: 600 !important;
  color: var(--kol-text) !important;
  font-size: 0.9rem !important;
}

.kolam-content .form-control {
  width: 100% !important;
  padding: 10px 12px !important;
  border: 1px solid var(--kol-border) !important;
  border-radius: 6px !important;
  font-size: 0.9rem !important;
}

.kolam-content .form-control:focus {
  outline: none !important;
  border-color: var(--kol-primary) !important;
  box-shadow: 0 0 0 3px rgba(59,130,246,0.1) !important;
}

.kolam-content .form-control:read-only {
  background: #f9fafb !important;
  cursor: not-allowed !important;
}

.kolam-content textarea.form-control {
  resize: vertical !important;
  min-height: 80px !important;
}

@media (max-width: 768px) {
  .kolam-content .page-header {
    flex-direction: column !important;
    gap: 10px !important;
  }
  
  .kolam-content .btn-xs {
    padding: 4px 8px !important;
    font-size: 0.75rem !important;
  }
}
</style>

<div class="kolam-content">
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <div class="d-flex align-items-center">
            <button class="btn btn-primary btn-round ml-auto" data-toggle="modal" data-target="#modalAddKolam">
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
                  <th>Fasilitas</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  $no = 1;
                  $query = mysqli_query($conn,'SELECT * FROM kolam ORDER BY id_kolam DESC');
                  while ($kolam = mysqli_fetch_array($query)) {
                ?>
                <tr>
                  <td><?= $no++ ?></td>
                  <td><?= safe($kolam['jenis_kolam']) ?></td>
                  <td>
                    <a href="#modalDetailKolam<?= $kolam['id_kolam'] ?>" data-toggle="modal" title="Detail" class="btn btn-xs btn-success">
                      <i class="fa fa-eye"></i>
                    </a>
                    <a href="#modalEditKolam<?= $kolam['id_kolam'] ?>" data-toggle="modal" title="Edit" class="btn btn-xs btn-primary">
                      <i class="fa fa-edit"></i>
                    </a>
                    <a href="#modalHapusKolam<?= $kolam['id_kolam'] ?>" data-toggle="modal" title="Hapus" class="btn btn-xs btn-danger">
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
<div class="modal fade" id="modalAddKolam" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header no-bd">
        <h5 class="modal-title">
          <span class="fw-mediumbold">Tambah</span> 
          <span class="fw-light">Kolam</span>
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="POST" enctype="multipart/form-data" action="">
        <div class="modal-body">
          <div class="form-group">
            <label>Nama Kolam</label>
            <input type="text" name="jenis_kolam" class="form-control" placeholder="Contoh: Kolam Renang, Kolam Ikan..." required maxlength="100">
          </div>
          <div class="form-group">
            <label>Deskripsi</label>
            <textarea placeholder="Deskripsi kolam..." class="form-control" rows="4" name="deskripsi" required></textarea>
          </div>
          <div class="form-group">
            <label>Foto (Max 5MB, Format: JPG, PNG, GIF)</label>
            <input type="file" name="foto" class="form-control" accept="image/jpeg,image/png,image/gif" required>
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

<!-- Modals (Edit, Detail, Hapus) -->
<?php 
$p = mysqli_query($conn,'SELECT * FROM kolam ORDER BY id_kolam DESC');
while($d = mysqli_fetch_array($p)) {
?>

<div class="modal fade" id="modalEditKolam<?= $d['id_kolam'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header no-bd">
        <h5 class="modal-title"><span class="fw-mediumbold">Edit</span> <span class="fw-light">Kolam</span></h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form method="POST" enctype="multipart/form-data" action="">
        <div class="modal-body">
          <input type="hidden" name="id_kolam" value="<?= $d['id_kolam'] ?>">
          <div class="form-group">
            <label>Jenis Kolam</label>
            <input value="<?= safe($d['jenis_kolam']) ?>" type="text" name="jenis_kolam" class="form-control" required maxlength="100">
          </div>
          <div class="form-group">
            <label>Deskripsi</label>
            <textarea class="form-control" rows="4" name="deskripsi" required><?= safe($d['deskripsi']) ?></textarea>
          </div>
          <div class="form-group">
            <label>Foto Baru (Optional, Max 5MB)</label>
            <input type="file" name="foto" class="form-control" accept="image/jpeg,image/png,image/gif">
            <small>Biarkan kosong jika tidak ingin mengubah foto</small>
          </div>
        </div>
        <div class="modal-footer no-bd">
          <button type="submit" name="ubah" class="btn btn-primary"><i class="fa fa-save"></i> Update</button>
          <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-undo"></i> Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalHapusKolam<?= $d['id_kolam'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header no-bd">
        <h5 class="modal-title"><span class="fw-mediumbold">Hapus</span> <span class="fw-light">Kolam</span></h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form method="POST" action="">
        <div class="modal-body">
          <input type="hidden" name="id_kolam" value="<?= $d['id_kolam'] ?>">
          <h4>Apakah Anda yakin ingin menghapus data ini?</h4>
          <p style="margin-top:12px; padding:12px; background:#fef2f2; border:1px solid #fca5a5; border-radius:8px; color:#991b1b;">
            <strong><?= safe($d['jenis_kolam']) ?></strong><br>
            <small>Foto juga akan dihapus dari server.</small>
          </p>
        </div>
        <div class="modal-footer no-bd">
          <button type="submit" name="hapus" class="btn btn-danger"><i class="fa fa-trash"></i> Ya, Hapus</button>
          <button type="button" class="btn btn-primary" data-dismiss="modal"><i class="fa fa-undo"></i> Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalDetailKolam<?= $d['id_kolam'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header no-bd">
        <h5 class="modal-title"><span class="fw-mediumbold">Detail</span> <span class="fw-light">Kolam</span></h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Jenis Kolam</label>
          <input readonly value="<?= safe($d['jenis_kolam']) ?>" type="text" class="form-control">
        </div>
        <div class="form-group">
          <label>Deskripsi</label>
          <textarea readonly class="form-control" rows="4"><?= safe($d['deskripsi']) ?></textarea>
        </div>
        <div class="form-group">
          <label>Foto</label>
          <img src="master/kolam/Fotokolam/<?= safe($d['foto']) ?>" style="width:100%; max-height:300px; object-fit:cover; border-radius:8px;" alt="Foto Kolam">
        </div>
      </div>
      <div class="modal-footer no-bd">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-undo"></i> Tutup</button>
      </div>
    </div>
  </div>
</div>
<?php } ?>
