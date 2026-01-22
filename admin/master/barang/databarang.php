<?php
include '../koneksi.php';

// Helper function untuk XSS prevention
function safe($v) {
    return htmlspecialchars((string)$v ?? '', ENT_QUOTES, 'UTF-8');
}

// ========== PROSES CRUD dengan Prepared Statements ==========
if(isset($_POST['simpan'])) {
    $nama_barang = trim($_POST['nama_barang']);
    $stok = trim($_POST['stok']);
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
        
        // Validasi
        if(!in_array($file_type, $allowed_types) || !in_array($file_ext, $allowed_ext)) {
            echo "<script>alert('Format file tidak diizinkan! Hanya JPG, PNG, GIF.');</script>";
            echo "<meta http-equiv='refresh' content='0; URL=?view=databarang'>";
            exit;
        }
        
        if($file_size > $max_size) {
            echo "<script>alert('Ukuran file terlalu besar! Maksimal 5MB.');</script>";
            echo "<meta http-equiv='refresh' content='0; URL=?view=databarang'>";
            exit;
        }
        
        // Generate unique filename
        $foto = uniqid('gedung_', true) . '.' . $file_ext;
        $upload_path = 'master/barang/Fotobarang/' . $foto;
        
        if(!move_uploaded_file($file_tmp, $upload_path)) {
            echo "<script>alert('Gagal upload file!');</script>";
            echo "<meta http-equiv='refresh' content='0; URL=?view=databarang'>";
            exit;
        }
    }
    
    // Prepared statement INSERT
    $stmt = $conn->prepare("INSERT INTO barang (nama_barang, stok, deskripsi, foto) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nama_barang, $stok, $deskripsi, $foto);
    
    if($stmt->execute()) {
        echo "<script>alert('Data Berhasil Disimpan');</script>";
    } else {
        echo "<script>alert('Gagal menyimpan data!');</script>";
    }
    $stmt->close();
    echo "<meta http-equiv='refresh' content='0; URL=?view=databarang'>";
}

elseif(isset($_POST['ubah'])) {
    $id = (int)$_POST['id'];
    $nama_barang = trim($_POST['nama_barang']);
    $stok = trim($_POST['stok']);
    $deskripsi = trim($_POST['deskripsi']);
    
    // File upload (optional untuk edit)
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
            $foto = uniqid('gedung_', true) . '.' . $file_ext;
            $upload_path = 'master/barang/Fotobarang/' . $foto;
            
            if(move_uploaded_file($file_tmp, $upload_path)) {
                $update_foto = true;
                
                // Hapus foto lama
                $old_photo = $conn->prepare("SELECT foto FROM barang WHERE id = ?");
                $old_photo->bind_param("i", $id);
                $old_photo->execute();
                $result = $old_photo->get_result();
                if($row = $result->fetch_assoc()) {
                    $old_file = 'master/barang/Fotobarang/' . $row['foto'];
                    if(file_exists($old_file)) unlink($old_file);
                }
                $old_photo->close();
            }
        }
    }
    
    // Prepared statement UPDATE
    if($update_foto) {
        $stmt = $conn->prepare("UPDATE barang SET nama_barang=?, stok=?, deskripsi=?, foto=? WHERE id=?");
        $stmt->bind_param("ssssi", $nama_barang, $stok, $deskripsi, $foto, $id);
    } else {
        $stmt = $conn->prepare("UPDATE barang SET nama_barang=?, stok=?, deskripsi=? WHERE id=?");
        $stmt->bind_param("sssi", $nama_barang, $stok, $deskripsi, $id);
    }
    
    if($stmt->execute()) {
        echo "<script>alert('Data Berhasil Diubah');</script>";
    } else {
        echo "<script>alert('Gagal mengubah data!');</script>";
    }
    $stmt->close();
    echo "<meta http-equiv='refresh' content='0; URL=?view=databarang'>";
}

elseif(isset($_POST['hapus'])) {
    $id = (int)$_POST['id'];
    
    // Hapus foto dari server
    $get_foto = $conn->prepare("SELECT foto FROM barang WHERE id = ?");
    $get_foto->bind_param("i", $id);
    $get_foto->execute();
    $result = $get_foto->get_result();
    if($row = $result->fetch_assoc()) {
        $file_path = 'master/barang/Fotobarang/' . $row['foto'];
        if(file_exists($file_path)) unlink($file_path);
    }
    $get_foto->close();
    
    // Prepared statement DELETE
    $stmt = $conn->prepare("DELETE FROM barang WHERE id=?");
    $stmt->bind_param("i", $id);
    
    if($stmt->execute()) {
        echo "<script>alert('Data Berhasil Dihapus');</script>";
    } else {
        echo "<script>alert('Gagal menghapus data!');</script>";
    }
    $stmt->close();
    echo "<meta http-equiv='refresh' content='0; URL=?view=databarang'>";
}
?>

<!-- Reset CSS -->
<style>
.page-inner .gedung-content {
  all: revert !important;
}

.gedung-content,
.gedung-content * {
  box-sizing: border-box !important;
}

.gedung-content .page-header,
.gedung-content .card,
.gedung-content .card-header,
.gedung-content .card-body,
.gedung-content .row {
  margin: revert !important;
  padding: revert !important;
}

.gedung-content {
  --gd-primary: #3b82f6;
  --gd-success: #22c55e;
  --gd-danger: #ef4444;
  --gd-text: #1f2937;
  --gd-muted: #6b7280;
  --gd-border: #e5e7eb;
  --gd-card: #ffffff;
  --gd-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.gedung-content .page-header {
  display: flex !important;
  justify-content: space-between !important;
  align-items: flex-start !important;
  margin-bottom: 25px !important;
  padding-bottom: 15px !important;
  border-bottom: 2px solid var(--gd-border) !important;
}

.gedung-content .page-header .page-title {
  font-size: 1.75rem !important;
  font-weight: 700 !important;
  color: var(--gd-text) !important;
  margin: 0 !important;
}

.gedung-content .breadcrumbs {
  list-style: none !important;
  padding: 0 !important;
  margin: 0 !important;
  display: flex !important;
  gap: 8px !important;
  align-items: center !important;
  font-size: 0.9rem !important;
  color: var(--gd-muted) !important;
}

.gedung-content .breadcrumbs li {
  display: flex !important;
  align-items: center !important;
}

.gedung-content .breadcrumbs a {
  color: var(--gd-primary) !important;
  text-decoration: none !important;
}

.gedung-content .row {
  display: flex !important;
  flex-wrap: wrap !important;
  margin: 0 -15px !important;
}

.gedung-content .col-md-12 {
  flex: 0 0 100% !important;
  max-width: 100% !important;
  padding: 0 15px !important;
}

.gedung-content .card {
  background: var(--gd-card) !important;
  border: 1px solid var(--gd-border) !important;
  border-radius: 12px !important;
  box-shadow: var(--gd-shadow) !important;
  margin-bottom: 20px !important;
}

.gedung-content .card-header {
  padding: 20px !important;
  background: #f9fafb !important;
  border-bottom: 1px solid var(--gd-border) !important;
}

.gedung-content .card-header .card-title {
  font-size: 1.1rem !important;
  font-weight: 700 !important;
  margin: 0 !important;
  color: var(--gd-text) !important;
}

.gedung-content .card-body {
  padding: 20px !important;
}

.gedung-content .d-flex {
  display: flex !important;
}

.gedung-content .align-items-center {
  align-items: center !important;
}

.gedung-content .ml-auto {
  margin-left: auto !important;
}

.gedung-content .btn {
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

.gedung-content .btn-primary {
  background: var(--gd-primary) !important;
  color: white !important;
}

.gedung-content .btn-primary:hover {
  background: #2563eb !important;
  transform: translateY(-2px) !important;
}

.gedung-content .btn-success {
  background: var(--gd-success) !important;
  color: white !important;
}

.gedung-content .btn-success:hover {
  background: #16a34a !important;
}

.gedung-content .btn-danger {
  background: var(--gd-danger) !important;
  color: white !important;
}

.gedung-content .btn-danger:hover {
  background: #dc2626 !important;
}

.gedung-content .btn-xs {
  padding: 5px 10px !important;
  font-size: 0.8rem !important;
}

.gedung-content .btn-round {
  border-radius: 50px !important;
}

.gedung-content .table-responsive {
  overflow-x: auto !important;
  -webkit-overflow-scrolling: touch !important;
}

.gedung-content .table {
  width: 100% !important;
  border-collapse: collapse !important;
}

.gedung-content .table thead th {
  font-weight: 700 !important;
  color: var(--gd-muted) !important;
  font-size: 0.85rem !important;
  text-transform: uppercase !important;
  padding: 12px !important;
  text-align: center !important;
  border-bottom: 2px solid var(--gd-border) !important;
  background: #f9fafb !important;
}

.gedung-content .table tbody td {
  padding: 12px !important;
  vertical-align: middle !important;
  border-bottom: 1px solid var(--gd-border) !important;
  text-align: center !important;
}

.gedung-content .table-striped tbody tr:nth-of-type(odd) {
  background: rgba(0,0,0,0.02) !important;
}

.gedung-content .table-hover tbody tr:hover {
  background: rgba(59,130,246,0.05) !important;
}

.gedung-content center {
  margin-top: 30px !important;
  padding: 20px 0 !important;
  color: var(--gd-muted) !important;
  font-size: 0.9rem !important;
}

.gedung-content .form-group {
  margin-bottom: 16px !important;
}

.gedung-content .form-group label {
  display: block !important;
  margin-bottom: 6px !important;
  font-weight: 600 !important;
  color: var(--gd-text) !important;
  font-size: 0.9rem !important;
}

.gedung-content .form-control {
  width: 100% !important;
  padding: 10px 12px !important;
  border: 1px solid var(--gd-border) !important;
  border-radius: 6px !important;
  font-size: 0.9rem !important;
}

.gedung-content .form-control:focus {
  outline: none !important;
  border-color: var(--gd-primary) !important;
  box-shadow: 0 0 0 3px rgba(59,130,246,0.1) !important;
}

@media (max-width: 768px) {
  .gedung-content .page-header {
    flex-direction: column !important;
  }
  
  .gedung-content .btn-xs {
    padding: 4px 8px !important;
    font-size: 0.75rem !important;
  }
}
</style>

<!-- Wrapper -->
<div class="gedung-content">
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <div class="d-flex align-items-center">
            <button class="btn btn-primary btn-round ml-auto" data-toggle="modal" data-target="#modalAddBarang">
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
                  <th>Nama Ruangan</th>
                  <th>Nama Gedung</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  $no = 1;
                  $query = mysqli_query($conn,'SELECT * FROM barang ORDER BY id DESC');
                  while ($barang = mysqli_fetch_array($query)) {
                ?>
                <tr>
                  <td><?= $no++ ?></td>
                  <td><?= safe($barang['nama_barang']) ?></td>
                  <td><?= safe($barang['stok']) ?></td>
                  <td>
                    <a href="#modalDetailBarang<?= $barang['id'] ?>" data-toggle="modal" title="Detail" class="btn btn-xs btn-success">
                      <i class="fa fa-eye"></i>
                    </a>
                    <a href="#modalEditBarang<?= $barang['id'] ?>" data-toggle="modal" title="Edit" class="btn btn-xs btn-primary">
                      <i class="fa fa-edit"></i>
                    </a>
                    <a href="#modalHapusBarang<?= $barang['id'] ?>" data-toggle="modal" title="Hapus" class="btn btn-xs btn-danger">
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

  <center>
    <h6><b>&copy; Copyright@2025 | SIPINJAM |</b></h6>
  </center>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalAddBarang" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header no-bd">
        <h5 class="modal-title">
          <span class="fw-mediumbold">Tambah</span> 
          <span class="fw-light">Gedung</span>
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="POST" enctype="multipart/form-data" action="">
        <div class="modal-body">
          <div class="form-group">
            <label>Nama Ruangan</label>
            <input type="text" name="nama_barang" class="form-control" placeholder="Nama Ruangan..." required>
          </div>
          <div class="form-group">
            <label>Nama Gedung</label>
            <input type="text" name="stok" class="form-control" placeholder="Nama Gedung..." required>
          </div>
          <div class="form-group">
            <label>Deskripsi</label>
            <textarea placeholder="Deskripsi..." class="form-control" rows="5" name="deskripsi" required></textarea>
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

<!-- Modal Edit & Detail & Hapus (simplified for brevity - sama seperti sebelumnya tapi dengan safe() function) -->
<?php 
$p = mysqli_query($conn,'SELECT * FROM barang');
while($d = mysqli_fetch_array($p)) {
?>
<div class="modal fade" id="modalEditBarang<?= $d['id'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header no-bd">
        <h5 class="modal-title"><span class="fw-mediumbold">Edit</span> <span class="fw-light">Gedung</span></h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form method="POST" enctype="multipart/form-data" action="">
        <div class="modal-body">
          <input type="hidden" name="id" value="<?= $d['id'] ?>">
          <div class="form-group">
            <label>Nama Ruangan</label>
            <input value="<?= safe($d['nama_barang']) ?>" type="text" name="nama_barang" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Nama Gedung</label>
            <input value="<?= safe($d['stok']) ?>" type="text" name="stok" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Deskripsi</label>
            <textarea class="form-control" rows="5" name="deskripsi" required><?= safe($d['deskripsi']) ?></textarea>
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

<div class="modal fade" id="modalHapusBarang<?= $d['id'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header no-bd">
        <h5 class="modal-title"><span class="fw-mediumbold">Hapus</span> <span class="fw-light">Gedung</span></h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form method="POST" action="">
        <div class="modal-body">
          <input type="hidden" name="id" value="<?= $d['id'] ?>">
          <h4>Apakah Anda yakin ingin menghapus data ini?</h4>
          <p><strong><?= safe($d['nama_barang']) ?></strong></p>
        </div>
        <div class="modal-footer no-bd">
          <button type="submit" name="hapus" class="btn btn-danger"><i class="fa fa-trash"></i> Hapus</button>
          <button type="button" class="btn btn-primary" data-dismiss="modal"><i class="fa fa-undo"></i> Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalDetailBarang<?= $d['id'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header no-bd">
        <h5 class="modal-title"><span class="fw-mediumbold">Detail</span> <span class="fw-light">Gedung</span></h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Nama Ruangan</label>
          <input readonly value="<?= safe($d['nama_barang']) ?>" type="text" class="form-control">
        </div>
        <div class="form-group">
          <label>Nama Gedung</label>
          <input readonly value="<?= safe($d['stok']) ?>" type="text" class="form-control">
        </div>
        <div class="form-group">
          <label>Deskripsi</label>
          <textarea readonly class="form-control" rows="5"><?= safe($d['deskripsi']) ?></textarea>
        </div>
        <div class="form-group">
          <label>Foto</label>
          <img src="master/barang/Fotobarang/<?= safe($d['foto']) ?>" style="width:100%; max-height:300px; object-fit:cover; border-radius:8px;" alt="Foto Gedung">
        </div>
      </div>
      <div class="modal-footer no-bd">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-undo"></i> Tutup</button>
      </div>
    </div>
  </div>
</div>
<?php } ?>
