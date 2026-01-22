<?php
include '../../cek.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<title>Peminjaman Barang dan Kendaraan</title>
	<meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
	<link rel="icon" href="../../../assets/img/icon.ico" type="image/x-icon" />

	<!-- Fonts and icons -->
	<script src="../../../assets/js/plugin/webfont/webfont.min.js"></script>
	<script>
		WebFont.load({
			google: {
				"families": ["Open+Sans:300,400,600,700"]
			},
			custom: {
				"families": ["Flaticon", "Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands"],
				urls: ['../assets/css/fonts.css']
			},
			active: function() {
				sessionStorage.fonts = true;
			}
		});
	</script>

	<!-- CSS Files -->
	<link rel="stylesheet" href="../../../assets/css/bootstrap.min.css">
	<link rel="stylesheet" href="../../../assets/css/azzara.min.css">
	<!-- CSS Just for demo purpose, don't include it in your project -->
	<link rel="stylesheet" href="../../../assets/css/demo.css">
</head>

<body>
	<?php
	$query = mysqli_query($conn, "SELECT*from pinjambarang inner join user on user.id=pinjambarang.id_user inner join barang on barang.id=pinjambarang.id_barang and pinjambarang.id_pinjam='$_GET[id]'");
	$d = mysqli_fetch_array($query);
	?>

	<div class="content">
		<div class="page-inner">
			<div class="row">
				<div class="col-md-12">
					<div class="card">
						<div class="card-header">
							<div class="d-flex align-items-center">
								<h4 class="card-title">Detail Pinjam Barang</h4>
							</div>
						</div>
						<div class="card-body">
							<div class="table-responsive">
								<table class="table">
									<tr>
										<td>Nama Peminjam</td>
										<td>:</td>
										<td><?php echo $d['nama_lengkap'] ?></td>
									</tr>
									<tr>
										<td>Nama PIC</td>
										<td>:</td>
										<td><?php echo $d['nama'] ?></td>
									</tr>
									<tr>
										<td>No. WA PIC</td>
										<td>:</td>
										<td><?php echo $d['nohp'] ?></td>
									</tr>
									<tr>
										<td>Nama Gedung</td>
										<td>:</td>
										<td><?php echo $d['nama_barang'] ?></td>
									</tr>
									<tr>
										<td>Meja</td>
										<td>:</td>
										<td><?php echo $d['meja'] ?></td>
									</tr>
									<tr>
										<td>Jumlah Meja</td>
										<td>:</td>
										<td><?php echo $d['jumlah_meja'] ?></td>
									</tr>
									<tr>
										<td>Kursi</td>
										<td>:</td>
										<td><?php echo $d['kursi'] ?></td>
									</tr>
									<tr>
										<td>Jumlah Kursi</td>
										<td>:</td>
										<td><?php echo $d['jumlah_kursi'] ?></td>
									</tr>
									<tr>
										<td>Sound System</td>
										<td>:</td>
										<td><?php echo $d['sound'] ?></td>
									</tr>
									<tr>
										<td>Proyektor</td>
										<td>:</td>
										<td><?php echo $d['proyektor'] ?></td>
									</tr>
									<tr>
										<td>Tgl Mulai</td>
										<td>:</td>
										<td><?php echo $d['tgl_mulai'] ?></td>
									</tr>
									<tr>
										<td>Waktu Mulai</td>
										<td>:</td>
										<td><?php echo $d['waktu_mulai'] ?></td>
									</tr>
									<tr>
										<td>Tgl Selesai</td>
										<td>:</td>
										<td><?php echo $d['tgl_selesai'] ?></td>
									</tr>
									<tr>
										<td>Waktu Selesai</td>
										<td>:</td>
										<td><?php echo $d['waktu_selesai'] ?></td>
									</tr>
									<tr>
										<td>Status</td>
										<td>:</td>
										<td><?php echo $d['status'] ?></td>
									</tr>
									<tr>
										<td>Tujuan Pemakaian</td>
										<td>:</td>
										<td><?php echo $d['tujuan_barang'] ?></td>
									</tr>

									<tr>
										<td>Layout</td>
										<td>:</td>
										<td><img src="layout/<?php echo $d['layout'] ?>" width="100%" height="100%"></td>
									</tr>
									<tr>
										<td>Keterangan</td>
										<td>:</td>
										<td><?php echo $d['ket'] ?></td>
									</tr>


								</table>
							</div>

						</div>

					</div>
					<center>
						<h6><b>&copy; Copyright@2025|SIPINJAM|</b></h6>
					</center>
				</div>

			</div>
		</div>
	</div>

	<script>
		window.print();
	</script>


</body>