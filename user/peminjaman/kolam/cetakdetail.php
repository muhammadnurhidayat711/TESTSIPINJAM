<?php
include '../../cek.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<title>Peminjaman Kolam</title>
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
	$query = mysqli_query($conn, "SELECT*from pinjamkolam inner join kolam on kolam.id_kolam=pinjamkolam.id_kolam inner join user on user.id=pinjamkolam.id_user and pinjamkolam.id_pinjamkolam='$_GET[id]'");
	$d = mysqli_fetch_array($query);
	?>

	<div class="content">
		<div class="page-inner">
			<div class="row">
				<div class="col-md-12">
					<div class="card">
						<div class="card-header">
							<div class="d-flex align-items-center">
								<h4 class="card-title">Detail Pinjam Kolam</h4>
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
										<td><?php echo $d['no_hp'] ?></td>
									</tr>
									<tr>
										<td>Fasilitas</td>
										<td>:</td>
										<td><?php echo $d['jenis_kolam'] ?></td>
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
										<td>Waktu Selesai</td>
										<td>:</td>
										<td><?php echo $d['waktu_selesai'] ?></td>
									</tr>
									<tr>
										<td>Status</td>
										<td>:</td>
										<td><?php echo $d['status'] ?></td>
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