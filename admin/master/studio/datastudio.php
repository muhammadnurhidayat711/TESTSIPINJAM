<?php
include '../koneksi.php';

function safe($v) {
    return htmlspecialchars((string)$v ?? '', ENT_QUOTES, 'UTF-8');
}

// ========== PROSES CRUD ==========
if(isset($_POST['simpan'])) {
    $jenis_studio = trim($_POST['jenis_studio']);
    $deskripsi = trim($_POST['deskripsi']);
    
    $foto = '';
    if(isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $max_size = 5 * 1024 * 1024;
        
        $file_type = $_FILES['foto']['type'];
        $file_size = $_FILES['foto']['size'];
        $file_tmp = $_FILES['foto']['tmp_name'];
        $file_name = $_FILES['foto']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if(!in_array($file_type, $allowed_types) || !in_array($file_ext, $allowed_ext)) {
            echo "<script>alert('Format file tidak diizinkan! Hanya JPG, PNG, GIF.');</script>";
            echo "<meta http-equiv='refresh' content='0; URL=?view=datastudio'>";
            exit;
        }
        
        if($file_size > $max_size) {
            echo "<script>alert('Ukuran file terlalu besar! Maksimal 5MB.');</script>";
            echo "<meta http-equiv='refresh' content='0; URL=?view=datastudio'>";
            exit;
        }
        
        $foto = uniqid('studio_', true) . '.' . $file_ext;
        $upload_path = 'master/studio/Fotostudio/' . $foto;
        
        if(!move_uploaded_file($file_tmp, $upload_path)) {
            echo "<script>alert('Gagal upload file!');</script>";
            echo "<meta http-equiv='refresh' content='0; URL=?view=datastudio'>";
            exit;
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO studio (jenis_studio, deskripsi, foto) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $jenis_studio, $deskripsi, $foto);
    
    if($stmt->execute()) {
        echo "<script>alert('Data Berhasil Disimpan');</script>";
    } else {
        echo "<script>alert('Gagal menyimpan data!');</script>";
    }
    $stmt->close();
    echo "<meta http-equiv='refresh' content='0; URL=?view=datastudio'>";
}

elseif(isset($_POST['ubah'])) {
    $id_studio = (int)$_POST['id_studio'];
    $jenis_studio = trim($_POST['jenis_studio']);
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
            $foto = uniqid('studio_', true) . '.' . $file_ext;
            $upload_path = 'master/studio/Fotostudio/' . $foto;
            
            if(move_uploaded_file($file_tmp, $upload_path)) {
                $update_foto = true;
                
                $old_photo = $conn->prepare("SELECT foto FROM studio WHERE id_studio = ?");
                $old_photo->bind_param("i", $id_studio);
                $old_photo->execute();
                $result = $old_photo->get_result();
                if($row = $result->fetch_assoc()) {
                    $old_file = 'master/studio/Fotostudio/' . $row['foto'];
                    if(file_exists($old_file)) unlink($old_file);
                }
                $old_photo->close();
            }
        }
    }
    
    if($update_foto) {
        $stmt = $conn->prepare("UPDATE studio SET jenis_studio=?, deskripsi=?, foto=? WHERE id_studio=?");
        $stmt->bind_param("sssi", $jenis_studio, $deskripsi, $foto, $id_studio);
    } else {
        $stmt = $conn->prepare("UPDATE studio SET jenis_studio=?, deskripsi=? WHERE id_studio=?");
        $stmt->bind_param("ssi", $jenis_studio, $deskripsi, $id_studio);
    }
    
    if($stmt->execute()) {
        echo "<script>alert('Data Berhasil Diubah');</script>";
    } else {
        echo "<script>alert('Gagal mengubah data!');</script>";
    }
    $stmt->close();
    echo "<meta http-equiv='refresh' content='0; URL=?view=datastudio'>";
}

elseif(isset($_POST['hapus'])) {
    $id_studio = (int)$_POST['id_studio'];
    
    $get_foto = $conn->prepare("SELECT foto FROM studio WHERE id_studio = ?");
    $get_foto->bind_param("i", $id_studio);
    $get_foto->execute();
    $result = $get_foto->get_result();
    if($row = $result->fetch_assoc()) {
        $file_path = 'master/studio/Fotostudio/' . $row['foto'];
        if(file_exists($file_path)) unlink($file_path);
    }
    $get_foto->close();
    
    $stmt = $conn->prepare("DELETE FROM studio WHERE id_studio=?");
    $stmt->bind_param("i", $id_studio);
    
    if($stmt->execute()) {
        echo "<script>alert('Data Berhasil Dihapus');</script>";
    } else {
        echo "<script>alert('Gagal menghapus data!');</script>";
    }
    $stmt->close();
    echo "<meta http-equiv='refresh' content='0; URL=?view=datastudio'>";
}
?>

<style>
.page-inner .studio-content {
  all: revert !important;
}

.studio-content,
.studio-content * {
  box-sizing: border-box !important;
}

.studio-content .page-header,
.studio-content .card,
.studio-content .card-header,
.studio-content .card-body,
.studio-content .row {
  margin: revert !important;
  padding: revert !important;
}

.studio-content {
  --st-primary: #3b82f6;
  --st-success: #22c55e;
  --st-danger: #ef4444;
  --st-text: #1f2937;
  --st-muted: #6b7280;
  --st-border: #e5e7eb;
  --st-card: #ffffff;
  --st-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.studio-content .page-header {
  display: flex !important;
  justify-content: space-between !important;
  align-items: flex-start !important;
  margin-bottom: 25px !important;
  padding-bottom: 15px !important;
  border-bottom: 2px solid var(--st-border) !important;
}

.studio-content .page-header .page-title {
  font-size: 1.75rem !important;
  font-weight: 700 !important;
  color: var(--st-text) !important;
  margin: 0 !important;
}

.studio-content .breadcrumbs {
  list-style: none !important;
  padding: 0 !important;
  margin: 0 !important;
  display: flex !important;
  gap: 8px !important;
  align-items: center !important;
  font-size: 0.9rem !important;
  color: var(--st-muted) !important;
}

.studio-content .breadcrumbs li {
  display: flex !important;
  align-items: center !important;
}

.studio-content .breadcrumbs a {
  color: var(--st-primary) !important;
  text-decoration: none !important;
}

.studio-content .row {
  display: flex !important;
  flex-wrap: wrap !important;
  margin: 0 -15px !important;
}

.studio-content .col-md-12 {
  flex: 0 0 100% !important;
  max-width: 100% !important;
  padding: 0 15px !important;
}

.studio-content .card {
  background: var(--st-card) !important;
  border: 1px solid var(--st-border) !important;
  border-radius: 12px !important;
  box-shadow: var(--st-shadow) !important;
  margin-bottom: 20px !important;
}

.studio-content .card-header {
  padding: 20px !important;
  background: #f9fafb !important;
  border-bottom: 1px solid var(--st-border) !important;
}

.studio-content .card-header .card-title {
  font-size: 1.1rem !important;
  font-weight: 700 !important;
  margin: 0 !important;
  color: var(--st-text) !important;
}

.studio-content .card-body {
  padding: 20px !important;
}

.studio-content .d-flex {
  display: flex !important;
}

.studio-content .align-items-center {
  align-items: center !important;
}

.studio-content .ml-auto {
  margin-left: auto !important;
}

.studio-content .btn {
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

.studio-content .btn-primary {
  background: var(--st-primary) !important;
  color: white !important;
}

.studio-content .btn-primary:hover {
  background: #2563eb !important;
  transform: translateY(-2px) !important;
}

.studio-content .btn-success {
  background: var(--st-success) !important;
  color: white !important;
}

.studio-content .btn-success:hover {
  background: #16a34a !important;
}

.studio-content .btn-danger {
  background: var(--st-danger) !important;
  color: white !important;
}

.studio-content .btn-danger:hover {
  background: #dc2626 !important;
}

.studio-content .btn-xs {
  padding: 5px 10px !important;
  font-size: 0.8rem !important;
}

.studio-content .btn-round {
  border-radius: 50px !important;
}

.studio-content .table-responsive {
  overflow-x: auto !important;
  -webkit-overflow-scrolling: touch !important;
}

.studio-content .table {
  width: 100% !important;
  border-collapse: collapse !important;
}

.studio-content .table thead th {
  font-weight: 700 !important;
  color: var(--st-muted) !important;
  font-size: 0.85rem !important;
  text-transform: uppercase !important;
  padding: 12px !important;
  text-align: center !important;
  border-bottom: 2px solid var(--st-border) !important;
  background: #f9fafb !important;
}

.studio-content .table tbody td {
  padding: 12px !important;
  vertical-align: middle !important;
  border-bottom: 1px solid var(--st-border) !important;
  text-align: center !important;
}

.studio-content .table-striped tbody tr:nth-of-type(odd) {
  background: rgba(0,0,0,0.02) !important;
}

.studio-content .table-hover tbody tr:hover {
  background: rgba(59,130,246,0.05) !important;
}

.studio-content .form-group {
  margin-bottom: 16px !important;
}

.studio-content .form-group label {
  display: block !important;
  margin-bottom: 6px !important;
  font-weight: 600 !important;
  color: var(--st-text) !important;
  font-size: 0.9rem !important;
}

.studio-content .form-control {
  width: 100% !important;
  padding: 10px 12px !important;
  border: 1px solid var(--st-border) !important;
  border-radius: 6px !important;
  font-size: 0.9rem !important;
}

.studio-content .form-control:focus {
  outline: none !important;
  border-color: var(--st-primary) !important;
  box-shadow: 0 0 0 3px rgba(59,130,246,0.1) !important;
}

.studio-content .form-control:read-only {
  background: #f9fafb !important;
  cursor: not-allowed !important;
}

.studio-content textarea.form-control {
  resize: vertical !important;
  min-height: 80px !important;
}

@media (max-width: 768px) {
  .studio-content .page-header {
    flex-direction: column !important;
    gap: 10px !important;
  }
  
  .studio-content .btn-xs {
    padding: 4px 8px !important;
    font-size: 0.75rem !important;
  }
}
</style>

<div class="studio-content">
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <div class="d-flex align-items-center">
            <button class="btn btn-primary btn-round ml-auto" data-toggle="modal" data-target="#modalAddStudio">
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
                  $query = mysqli_query($conn,'SELECT * FROM studio ORDER BY id_studio DESC');
                  while ($studio = mysqli_fetch_array($query)) {
                ?>
                <tr>
                  <td><?= $no++ ?></td>
                  <td><?= safe($studio['jenis_studio']) ?></td>
                  <td>
                    <a href="#modalDetailStudio<?= $studio['id_studio'] ?>" data-toggle="modal" title="Detail" class="btn btn-xs btn-success">
                      <i class="fa fa-eye"></i>
                    </a>
                    <a href="#modalEditStudio<?= $studio['id_studio'] ?>" data-toggle="modal" title="Edit" class="btn btn-xs btn-primary">
                      <i class="fa fa-edit"></i>
                    </a>
                    <a href="#modalHapusStudio<?= $studio['id_studio'] ?>" data-toggle="modal" title="Hapus" class="btn btn-xs btn-danger">
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
<div class="modal fade" id="modalAddStudio" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header no-bd">
        <h5 class="modal-title">
          <span class="fw-mediumbold">Tambah</span> 
          <span class="fw-light">Studio</span>
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="POST" enctype="multipart/form-data" action="">
        <div class="modal-body">
          <div class="form-group">
            <label>Nama Studio</label>
            <input type="text" name="jenis_studio" class="form-control" placeholder="Contoh: Studio Musik, Studio Foto..." required maxlength="100">
          </div>
          <div class="form-group">
            <label>Deskripsi</label>
            <textarea placeholder="Deskripsi studio..." class="form-control" rows="4" name="deskripsi" required></textarea>
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

<!-- Modals -->
<?php 
$p = mysqli_query($conn,'SELECT * FROM studio ORDER BY id_studio DESC');
while($d = mysqli_fetch_array($p)) {
?>

<div class="modal fade" id="modalEditStudio<?= $d['id_studio'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header no-bd">
        <h5 class="modal-title"><span class="fw-mediumbold">Edit</span> <span class="fw-light">Studio</span></h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form method="POST" enctype="multipart/form-data" action="">
        <div class="modal-body">
          <input type="hidden" name="id_studio" value="<?= $d['id_studio'] ?>">
          <div class="form-group">
            <label>Jenis Studio</label>
            <input value="<?= safe($d['jenis_studio']) ?>" type="text" name="jenis_studio" class="form-control" required maxlength="100">
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

<div class="modal fade" id="modalHapusStudio<?= $d['id_studio'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header no-bd">
        <h5 class="modal-title"><span class="fw-mediumbold">Hapus</span> <span class="fw-light">Studio</span></h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form method="POST" action="">
        <div class="modal-body">
          <input type="hidden" name="id_studio" value="<?= $d['id_studio'] ?>">
          <h4>Apakah Anda yakin ingin menghapus data ini?</h4>
          <p style="margin-top:12px; padding:12px; background:#fef2f2; border:1px solid #fca5a5; border-radius:8px; color:#991b1b;">
            <strong><?= safe($d['jenis_studio']) ?></strong><br>
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

<div class="modal fade" id="modalDetailStudio<?= $d['id_studio'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header no-bd">
        <h5 class="modal-title"><span class="fw-mediumbold">Detail</span> <span class="fw-light">Studio</span></h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Jenis Studio</label>
          <input readonly value="<?= safe($d['jenis_studio']) ?>" type="text" class="form-control">
        </div>
        <div class="form-group">
          <label>Deskripsi</label>
          <textarea readonly class="form-control" rows="4"><?= safe($d['deskripsi']) ?></textarea>
        </div>
        <div class="form-group">
          <label>Foto</label>
          <img src="master/studio/Fotostudio/<?= safe($d['foto']) ?>" style="width:100%; max-height:300px; object-fit:cover; border-radius:8px;" alt="Foto Studio">
        </div>
      </div>
      <div class="modal-footer no-bd">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-undo"></i> Tutup</button>
      </div>
    </div>
  </div>
</div>
<?php } ?>
