<div class="main-panel">
	<div class="content">
		<div class="page-inner">
			<div class="page-header">
				<h4 class="page-title">Data PIC Fasilitas</h4>
				<ul class="breadcrumbs">
					<li class="nav-home">
						<a href="#">
							<i class="flaticon-home"></i>
						</a>
					</li>
					<li class="separator">
						<i class="flaticon-right-arrow"></i>
					</li>
					<li class="nav-item">
						<a href="#">Data</a>
					</li>
					<li class="separator">
						<i class="flaticon-right-arrow"></i>
					</li>
					<li class="nav-item">
						<a href="#">PIC Fasilitas</a>
					</li>
				</ul>
			</div>
			<div class="row">
				<div class="col-md-12">
					<div class="card">
						<div class="card-header">
							<div class="d-flex align-items-center">
								<h4 class="card-title">Data PIC Fasilitas</h4>
								<button class="btn btn-primary btn-round ml-auto" data-toggle="modal" data-target="#modalAddPIC">
									<i class="fa fa-plus"></i>
									Tambah Data
								</button>
							</div>
						</div>
						<div class="card-body">
							<div class="table-responsive">
								<table id="add-row" class="display table table-striped table-hover">
									<thead>
										<tr>
											<th>No</th>
											<th>Nama PIC</th>
											<th>Jenis Fasilitas</th>
											<th>No. WhatsApp</th>
											<th>Status</th>
											<th>Action</th>
										</tr>
									</thead>
									
									<tbody>
										<?php
											$no = 1;
											$query = mysqli_query($conn,'SELECT * FROM pic_kontak ORDER BY jenis_pic ASC, nama_pic ASC');
											while ($pic = mysqli_fetch_array($query)) {
												// Badge untuk status
												$badgeClass = $pic['status'] == 'aktif' ? 'badge-success' : 'badge-secondary';
												$statusText = ucfirst($pic['status']);
												
												// Label untuk jenis PIC
												$jenisLabel = [
													'proyektor' => 'Proyektor',
													'meja' => 'Meja',
													'sound' => 'Sound System',
													'kursi' => 'Kursi'
												];
												$jenisDisplay = $jenisLabel[$pic['jenis_pic']] ?? ucfirst($pic['jenis_pic']);
										?>
										<tr>
											<td><?php echo $no++ ?></td>
											<td><?php echo htmlspecialchars($pic['nama_pic']) ?></td>
											<td><span class="badge badge-info"><?php echo $jenisDisplay ?></span></td>
											<td><?php echo htmlspecialchars($pic['no_whatsapp']) ?></td>
											<td><span class="badge <?php echo $badgeClass ?>"><?php echo $statusText ?></span></td>
											<td>
												<a href="#modalDetailPIC<?php echo $pic['id'] ?>" data-toggle="modal" title="Detail" class="btn btn-xs btn-success"><i class="fa fa-eye"></i></a>
												<a href="#modalEditPIC<?php echo $pic['id'] ?>" data-toggle="modal" title="Edit" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i></a>
												<a href="#modalHapusPIC<?php echo $pic['id'] ?>" data-toggle="modal" title="Hapus" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i></a>
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
	</div>
	<center><h6><b>&copy; Copyright@2025|SIPINJAM|</b></h6></center>
</div>

<!-- Modal Tambah PIC -->
<div class="modal fade" id="modalAddPIC" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header no-bd">
				<h5 class="modal-title">
					<span class="fw-mediumbold">Tambah</span> 
					<span class="fw-light">PIC Fasilitas</span>
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<form method="POST" action="">
				<div class="modal-body">
					<div class="form-group">
						<label>Nama PIC <span class="text-danger">*</span></label>
						<input type="text" name="nama_pic" class="form-control" placeholder="Masukkan nama PIC ..." required>
					</div>
					<div class="form-group">
						<label>Jenis Fasilitas <span class="text-danger">*</span></label>
						<select name="jenis_pic" class="form-control" required>
							<option value="">-- Pilih Jenis Fasilitas --</option>
							<option value="proyektor">Proyektor</option>
							<option value="meja">Meja</option>
							<option value="sound">Sound System</option>
							<option value="kursi">Kursi</option>
						</select>
					</div>
					<div class="form-group">
						<label>No. WhatsApp <span class="text-danger">*</span></label>
						<input type="text" name="no_whatsapp" class="form-control" placeholder="Contoh: 08123456789" required>
						<small class="form-text text-muted">Masukkan nomor WhatsApp aktif tanpa tanda + atau kode negara</small>
					</div>
					<div class="form-group">
						<label>Status <span class="text-danger">*</span></label>
						<select name="status" class="form-control" required>
							<option value="aktif">Aktif</option>
							<option value="nonaktif">Nonaktif</option>
						</select>
					</div>
				</div>
				<div class="modal-footer no-bd">
					<button type="submit" name="simpan" class="btn btn-primary"><i class="fa fa-save"></i> Simpan</button>
					<button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-undo"></i> Batal</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- Modal Edit PIC -->
<?php 
	$p = mysqli_query($conn,'SELECT * FROM pic_kontak');
	while($d = mysqli_fetch_array($p)) {
?>
<div class="modal fade" id="modalEditPIC<?php echo $d['id'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header no-bd">
				<h5 class="modal-title">
					<span class="fw-mediumbold">Edit</span> 
					<span class="fw-light">PIC Fasilitas</span>
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<form method="POST" action="">
				<div class="modal-body">
					<input type="hidden" name="id" value="<?php echo $d['id'] ?>">
					<div class="form-group">
						<label>Nama PIC <span class="text-danger">*</span></label>
						<input value="<?php echo htmlspecialchars($d['nama_pic']) ?>" type="text" name="nama_pic" class="form-control" placeholder="Masukkan nama PIC ..." required>
					</div>
					<div class="form-group">
						<label>Jenis Fasilitas <span class="text-danger">*</span></label>
						<select name="jenis_pic" class="form-control" required>
							<option value="">-- Pilih Jenis Fasilitas --</option>
							<option value="proyektor" <?php echo ($d['jenis_pic'] == 'proyektor') ? 'selected' : ''; ?>>Proyektor</option>
							<option value="meja" <?php echo ($d['jenis_pic'] == 'meja') ? 'selected' : ''; ?>>Meja</option>
							<option value="sound" <?php echo ($d['jenis_pic'] == 'sound') ? 'selected' : ''; ?>>Sound System</option>
							<option value="kursi" <?php echo ($d['jenis_pic'] == 'kursi') ? 'selected' : ''; ?>>Kursi</option>
						</select>
					</div>
					<div class="form-group">
						<label>No. WhatsApp <span class="text-danger">*</span></label>
						<input value="<?php echo htmlspecialchars($d['no_whatsapp']) ?>" type="text" name="no_whatsapp" class="form-control" placeholder="Contoh: 08123456789" required>
						<small class="form-text text-muted">Masukkan nomor WhatsApp aktif tanpa tanda + atau kode negara</small>
					</div>
					<div class="form-group">
						<label>Status <span class="text-danger">*</span></label>
						<select name="status" class="form-control" required>
							<option value="aktif" <?php echo ($d['status'] == 'aktif') ? 'selected' : ''; ?>>Aktif</option>
							<option value="nonaktif" <?php echo ($d['status'] == 'nonaktif') ? 'selected' : ''; ?>>Nonaktif</option>
						</select>
					</div>
				</div>
				<div class="modal-footer no-bd">
					<button type="submit" name="ubah" class="btn btn-primary"><i class="fa fa-save"></i> Simpan Perubahan</button>
					<button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-undo"></i> Batal</button>
				</div>
			</form>
		</div>
	</div>
</div>
<?php } ?>

<!-- Modal Hapus PIC -->
<?php 
	$c = mysqli_query($conn,'SELECT * FROM pic_kontak');
	while($row = mysqli_fetch_array($c)) {
?>
<div class="modal fade" id="modalHapusPIC<?php echo $row['id'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header no-bd">
				<h5 class="modal-title">
					<span class="fw-mediumbold">Hapus</span> 
					<span class="fw-light">PIC Fasilitas</span>
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<form method="POST" action="">
				<div class="modal-body">
					<input type="hidden" name="id" value="<?php echo $row['id'] ?>">
					<h4>Apakah Anda yakin ingin menghapus PIC ini?</h4>
					<p><strong>Nama:</strong> <?php echo htmlspecialchars($row['nama_pic']) ?></p>
					<p><strong>Jenis:</strong> <?php echo ucfirst($row['jenis_pic']) ?></p>
				</div>
				<div class="modal-footer no-bd">
					<button type="submit" name="hapus" class="btn btn-danger"><i class="fa fa-trash"></i> Hapus</button>
					<button type="button" class="btn btn-primary" data-dismiss="modal"><i class="fa fa-undo"></i> Batal</button>
				</div>
			</form>
		</div>
	</div>
</div>
<?php } ?>

<!-- Modal Detail PIC -->
<?php 
	$q = mysqli_query($conn,'SELECT * FROM pic_kontak');
	while($k = mysqli_fetch_array($q)) {
		$jenisLabel = [
			'proyektor' => 'Proyektor',
			'meja' => 'Meja',
			'sound' => 'Sound System',
			'kursi' => 'Kursi'
		];
		$jenisDisplay = $jenisLabel[$k['jenis_pic']] ?? ucfirst($k['jenis_pic']);
?>
<div class="modal fade" id="modalDetailPIC<?php echo $k['id'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header no-bd">
				<h5 class="modal-title">
					<span class="fw-mediumbold">Detail</span> 
					<span class="fw-light">PIC Fasilitas</span>
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<input type="hidden" name="id" value="<?php echo $k['id'] ?>">
				<div class="form-group">
					<label><strong>Nama PIC</strong></label>
					<input readonly value="<?php echo htmlspecialchars($k['nama_pic']) ?>" type="text" class="form-control">
				</div>
				<div class="form-group">
					<label><strong>Jenis Fasilitas</strong></label>
					<input readonly value="<?php echo $jenisDisplay ?>" type="text" class="form-control">
				</div>
				<div class="form-group">
					<label><strong>No. WhatsApp</strong></label>
					<div class="input-group">
						<input readonly value="<?php echo htmlspecialchars($k['no_whatsapp']) ?>" type="text" class="form-control" id="waNumber<?php echo $k['id'] ?>">
						<div class="input-group-append">
							<a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $k['no_whatsapp']) ?>" target="_blank" class="btn btn-success">
								<i class="fa fa-whatsapp"></i> Chat
							</a>
						</div>
					</div>
				</div>
				<div class="form-group">
					<label><strong>Status</strong></label>
					<br>
					<?php 
						$badgeClass = $k['status'] == 'aktif' ? 'badge-success' : 'badge-secondary';
						$statusText = ucfirst($k['status']);
					?>
					<span class="badge <?php echo $badgeClass ?> badge-lg"><?php echo $statusText ?></span>
				</div>
			</div>
			<div class="modal-footer no-bd">
				<button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-undo"></i> Tutup</button>
			</div>
		</div>
	</div>
</div>
<?php } ?>

<?php
// Proses Simpan Data
if(isset($_POST['simpan'])) {
	$nama_pic = mysqli_real_escape_string($conn, $_POST['nama_pic']);
	$jenis_pic = mysqli_real_escape_string($conn, $_POST['jenis_pic']);
	$no_whatsapp = mysqli_real_escape_string($conn, $_POST['no_whatsapp']);
	$status = mysqli_real_escape_string($conn, $_POST['status']);
	
	// Validasi jenis_pic
	$valid_jenis = ['proyektor', 'meja', 'sound', 'kursi'];
	if (!in_array($jenis_pic, $valid_jenis)) {
		echo "<script>alert('Jenis fasilitas tidak valid!')</script>";
		echo "<meta http-equiv='refresh' content='0; URL=?view=datapic'>";
		exit;
	}
	
	// Validasi status
	$valid_status = ['aktif', 'nonaktif'];
	if (!in_array($status, $valid_status)) {
		echo "<script>alert('Status tidak valid!')</script>";
		echo "<meta http-equiv='refresh' content='0; URL=?view=datapic'>";
		exit;
	}
	
	$query = "INSERT INTO pic_kontak (nama_pic, jenis_pic, no_whatsapp, status) 
	          VALUES ('$nama_pic', '$jenis_pic', '$no_whatsapp', '$status')";
	
	if(mysqli_query($conn, $query)) {
		echo "<script>alert('Data PIC berhasil ditambahkan!')</script>";
	} else {
		echo "<script>alert('Gagal menambahkan data: " . mysqli_error($conn) . "')</script>";
	}
	echo "<meta http-equiv='refresh' content='0; URL=?view=datapic'>";
}

// Proses Edit Data
elseif(isset($_POST['ubah'])) {
	$id = mysqli_real_escape_string($conn, $_POST['id']);
	$nama_pic = mysqli_real_escape_string($conn, $_POST['nama_pic']);
	$jenis_pic = mysqli_real_escape_string($conn, $_POST['jenis_pic']);
	$no_whatsapp = mysqli_real_escape_string($conn, $_POST['no_whatsapp']);
	$status = mysqli_real_escape_string($conn, $_POST['status']);
	
	// Validasi jenis_pic
	$valid_jenis = ['proyektor', 'meja', 'sound', 'kursi'];
	if (!in_array($jenis_pic, $valid_jenis)) {
		echo "<script>alert('Jenis fasilitas tidak valid!')</script>";
		echo "<meta http-equiv='refresh' content='0; URL=?view=datapic'>";
		exit;
	}
	
	// Validasi status
	$valid_status = ['aktif', 'nonaktif'];
	if (!in_array($status, $valid_status)) {
		echo "<script>alert('Status tidak valid!')</script>";
		echo "<meta http-equiv='refresh' content='0; URL=?view=datapic'>";
		exit;
	}
	
	$query = "UPDATE pic_kontak SET 
	          nama_pic='$nama_pic', 
	          jenis_pic='$jenis_pic', 
	          no_whatsapp='$no_whatsapp', 
	          status='$status' 
	          WHERE id='$id'";
	
	if(mysqli_query($conn, $query)) {
		echo "<script>alert('Data PIC berhasil diubah!')</script>";
	} else {
		echo "<script>alert('Gagal mengubah data: " . mysqli_error($conn) . "')</script>";
	}
	echo "<meta http-equiv='refresh' content='0; URL=?view=datapic'>";
}

// Proses Hapus Data
elseif(isset($_POST['hapus'])) {
	$id = mysqli_real_escape_string($conn, $_POST['id']);
	
	$query = "DELETE FROM pic_kontak WHERE id='$id'";
	
	if(mysqli_query($conn, $query)) {
		echo "<script>alert('Data PIC berhasil dihapus!')</script>";
	} else {
		echo "<script>alert('Gagal menghapus data: " . mysqli_error($conn) . "')</script>";
	}
	echo "<meta http-equiv='refresh' content='0; URL=?view=datapic'>";
}
?>
