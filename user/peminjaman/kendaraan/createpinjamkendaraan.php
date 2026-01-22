<?php
// ========================================
// CREATE PEMINJAMAN KENDARAAN — SIPINJAM
// ========================================

// ========================================
// ✅ PERBAIKAN: Tangkap parameter tanggal dari URL
// ========================================
$default_date = '';
$default_date_end = '';

if (isset($_GET['date']) && !empty($_GET['date'])) {
    $date_param = trim($_GET['date']);
    // Validasi format tanggal YYYY-MM-DD
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_param)) {
        // Validasi apakah tanggal valid
        $date_parts = explode('-', $date_param);
        if (count($date_parts) === 3 && checkdate((int)$date_parts[1], (int)$date_parts[2], (int)$date_parts[0])) {
            $default_date = htmlspecialchars($date_param, ENT_QUOTES, 'UTF-8');
            $default_date_end = $default_date;
        }
    }
}

// Inisialisasi variabel untuk menyimpan data form
$form_data = [
  'id_kendaraan' => '',
  'tujuan' => '',
  'bagian' => '',
  'tgl_mulai' => $default_date,      // ✅ PERBAIKAN: Set default dari URL
  'waktu_mulai' => '',
  'tgl_selesai' => $default_date_end, // ✅ PERBAIKAN: Set default dari URL
  'waktu_selesai' => ''
];

$alert_script = '';

if (isset($_POST['simpan'])) {
  // Simpan data form ke variabel
  $form_data['id_kendaraan'] = $_POST['id_kendaraan'];
  $form_data['tujuan'] = $_POST['tujuan'];
  $form_data['bagian'] = $_POST['bagian'];
  $form_data['tgl_mulai'] = $_POST['tgl_mulai'];
  $form_data['waktu_mulai'] = $_POST['waktu_mulai'];
  $form_data['tgl_selesai'] = $_POST['tgl_selesai'];
  $form_data['waktu_selesai'] = $_POST['waktu_selesai'];
  
  $tgl_mulai = $_POST['tgl_mulai'];
  $waktu_mulai = $_POST['waktu_mulai'];
  $tgl_selesai = $_POST['tgl_selesai'];
  $waktu_selesai = $_POST['waktu_selesai'];
  $id_user = $_POST['id_user'];
  $id_kendaraan = $_POST['id_kendaraan'];
  $tujuan = mysqli_real_escape_string($conn, $_POST['tujuan']);
  $bagian = mysqli_real_escape_string($conn, $_POST['bagian']);
  $status = $_POST['status'];

  // Validasi field tidak boleh kosong
  if (empty($id_kendaraan)) {
    $alert_script = "
      Swal.fire({
        icon: 'warning',
        title: 'Data Tidak Lengkap!',
        text: 'Pilih kendaraan terlebih dahulu.',
        confirmButtonColor: '#ffc107'
      });
    ";
  } elseif (empty($tgl_mulai) || empty($waktu_mulai) || empty($tgl_selesai) || empty($waktu_selesai)) {
    $alert_script = "
      Swal.fire({
        icon: 'warning',
        title: 'Data Tidak Lengkap!',
        text: 'Isi semua tanggal dan waktu peminjaman.',
        confirmButtonColor: '#ffc107'
      });
    ";
  } else {
    // Format waktu dengan benar
    if (strlen($waktu_mulai) == 5) {
      $waktu_mulai .= ':00';
    }
    if (strlen($waktu_selesai) == 5) {
      $waktu_selesai .= ':00';
    }

    // Gabungkan tanggal dan waktu
    $datetime_mulai = $tgl_mulai . ' ' . $waktu_mulai;
    $datetime_selesai = $tgl_selesai . ' ' . $waktu_selesai;

    // Format untuk ditampilkan
    $format_mulai = date('d-m-Y H:i', strtotime($datetime_mulai));
    $format_selesai = date('d-m-Y H:i', strtotime($datetime_selesai));
    
    // Hitung selisih waktu
    $diff_seconds = strtotime($datetime_selesai) - strtotime($datetime_mulai);
    $diff_hours = floor($diff_seconds / 3600);
    $diff_minutes = floor(($diff_seconds % 3600) / 60);

    // Validasi waktu
    if (strtotime($datetime_selesai) <= strtotime($datetime_mulai)) {
      $alert_script = "
        Swal.fire({
          icon: 'error',
          title: 'Waktu Tidak Valid!',
          text: 'Waktu selesai harus setelah waktu mulai!',
          confirmButtonColor: '#d33'
        });
      ";
    } else {
      // Ambil info kendaraan
      $query_kendaraan_dipilih = mysqli_query($conn, "SELECT * FROM kendaraan WHERE id_kendaraan = '$id_kendaraan'");
      $kendaraan_dipilih = mysqli_fetch_assoc($query_kendaraan_dipilih);
      
      // CEK JADWAL BENTROK
      $cek_jadwal = mysqli_query($conn, "
        SELECT pk.*, k.nama_kendaraan, k.deskripsi, u.nama_lengkap
        FROM pinjamkendaraan pk
        LEFT JOIN kendaraan k ON pk.id_kendaraan = k.id_kendaraan
        LEFT JOIN user u ON pk.id_user = u.id
        WHERE pk.id_kendaraan = '$id_kendaraan' 
        AND pk.status IN ('menunggu', 'disetujui', 'dipinjam')
        AND TIMESTAMP(pk.tgl_mulai, pk.waktu_mulai) < TIMESTAMP('$tgl_selesai', '$waktu_selesai')
        AND TIMESTAMP(pk.tgl_selesai, pk.waktu_selesai) > TIMESTAMP('$tgl_mulai', '$waktu_mulai')
      ");

      if (!$cek_jadwal) {
        die("Error Query: " . mysqli_error($conn));
      }

      if (mysqli_num_rows($cek_jadwal) > 0) {
        // JADWAL BENTROK
        $data_bentrok = mysqli_fetch_assoc($cek_jadwal);
        
        $tgl_mulai_bentrok = date('d-m-Y', strtotime($data_bentrok['tgl_mulai']));
        $tgl_selesai_bentrok = date('d-m-Y', strtotime($data_bentrok['tgl_selesai']));
        $waktu_mulai_bentrok = substr($data_bentrok['waktu_mulai'], 0, 5);
        $waktu_selesai_bentrok = substr($data_bentrok['waktu_selesai'], 0, 5);
        
        $info_kendaraan = htmlspecialchars($data_bentrok['nama_kendaraan']);
        $info_deskripsi = !empty($data_bentrok['deskripsi']) ? htmlspecialchars($data_bentrok['deskripsi']) : "-";
        $info_jadwal_bentrok = $tgl_mulai_bentrok . " " . $waktu_mulai_bentrok . " - " . $tgl_selesai_bentrok . " " . $waktu_selesai_bentrok;
        $info_jadwal_input = $format_mulai . " - " . $format_selesai;
        $info_peminjam = !empty($data_bentrok['nama_lengkap']) ? htmlspecialchars($data_bentrok['nama_lengkap']) : "Tidak diketahui";
        $info_status = ucfirst($data_bentrok['status']);
        $info_bagian = !empty($data_bentrok['bagian']) ? htmlspecialchars($data_bentrok['bagian']) : "-";
        $info_tujuan = htmlspecialchars($data_bentrok['tujuan']);
        
        // Cari alternatif
        $query_alternatif = mysqli_query($conn, "
          SELECT k.* FROM kendaraan k
          WHERE k.id_kendaraan NOT IN (
            SELECT pk.id_kendaraan FROM pinjamkendaraan pk
            WHERE pk.status IN ('menunggu', 'disetujui', 'dipinjam')
            AND TIMESTAMP(pk.tgl_mulai, pk.waktu_mulai) < TIMESTAMP('$tgl_selesai', '$waktu_selesai')
            AND TIMESTAMP(pk.tgl_selesai, pk.waktu_selesai) > TIMESTAMP('$tgl_mulai', '$waktu_mulai')
          )
          LIMIT 3
        ");
        
        $list_alternatif = "";
        if (mysqli_num_rows($query_alternatif) > 0) {
          $list_alternatif = "<hr><p><b>🚗 Kendaraan Tersedia:</b></p><ul>";
          while ($alt = mysqli_fetch_assoc($query_alternatif)) {
            $nama_alt = htmlspecialchars($alt['nama_kendaraan']);
            $desk_alt = !empty($alt['deskripsi']) ? " - " . htmlspecialchars($alt['deskripsi']) : "";
            $list_alternatif .= "<li>" . $nama_alt . $desk_alt . "</li>";
          }
          $list_alternatif .= "</ul>";
        } else {
          $list_alternatif = "<hr><p style='color:#d33;'><b>⚠️ Semua kendaraan tidak tersedia.</b></p>";
        }
        
        $html_content = '<div style="text-align: left;">'.
                  '<p><strong style="color:#d33;font-size:16px;">❌ JADWAL BENTROK!</strong></p>'.
                  '<p>Kendaraan sudah dipinjam pada waktu tersebut.</p><hr>'.
                  '<table style="width:100%;border-collapse:collapse;">'.
                  '<tr><td style="padding:8px;background:#f5f5f5;width:35%;"><b>📌 Kendaraan</b></td><td style="padding:8px;">'.$info_kendaraan.'</td></tr>'.
                  '<tr><td style="padding:8px;background:#f5f5f5;"><b>Deskripsi</b></td><td style="padding:8px;">'.$info_deskripsi.'</td></tr>'.
                  '<tr><td style="padding:8px;background:#f5f5f5;"><b>Status</b></td><td style="padding:8px;color:#d33;"><b>'.$info_status.'</b></td></tr>'.
                  '<tr><td style="padding:8px;background:#f5f5f5;"><b>👤 Peminjam</b></td><td style="padding:8px;">'.$info_peminjam.'</td></tr>'.
                  '<tr><td style="padding:8px;background:#f5f5f5;"><b>Bagian</b></td><td style="padding:8px;">'.$info_bagian.'</td></tr>'.
                  '<tr><td style="padding:8px;background:#f5f5f5;"><b>Tujuan</b></td><td style="padding:8px;">'.$info_tujuan.'</td></tr>'.
                  '</table><hr>'.
                  '<p><b>📅 Perbandingan Jadwal:</b></p>'.
                  '<table style="width:100%;border-collapse:collapse;border:1px solid #ddd;">'.
                  '<tr style="background:#e8f5e9;"><td style="padding:8px;border:1px solid #ddd;width:35%;"><b>✅ Jadwal Anda</b></td><td style="padding:8px;border:1px solid #ddd;">'.$info_jadwal_input.'</td></tr>'.
                  '<tr style="background:#ffebee;"><td style="padding:8px;border:1px solid #ddd;"><b>❌ Jadwal Terpakai</b></td><td style="padding:8px;border:1px solid #ddd;color:#d33;"><b>'.$info_jadwal_bentrok.'</b></td></tr>'.
                  '</table>'.
                  $list_alternatif.
                  '<hr><p><b>💡 Solusi:</b></p>'.
                  '<ol style="margin:0;padding-left:20px;">'.
                  '<li>Pilih kendaraan lain</li>'.
                  '<li>Ubah jadwal</li>'.
                  '<li>Hubungi admin</li>'.
                  '</ol></div>';
        
        $html_content = addslashes($html_content);
        
        $alert_script = "
          Swal.fire({
            icon: 'error',
            title: 'Jadwal Bentrok!',
            html: '{$html_content}',
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ubah Jadwal/Kendaraan',
            width: '800px'
          });
        ";
      } else {
        // TIDAK ADA BENTROK - INSERT
        $insert = mysqli_query($conn, "
          INSERT INTO pinjamkendaraan 
          (id_kendaraan, id_user, tgl_mulai, waktu_mulai, tgl_selesai, waktu_selesai, tujuan, bagian, pengemudi, status) 
          VALUES 
          ('$id_kendaraan', '$id_user', '$tgl_mulai', '$waktu_mulai', '$tgl_selesai', '$waktu_selesai', '$tujuan', '$bagian', '', '$status')
        ");

        if ($insert) {
          $id_pinjam_insert = mysqli_insert_id($conn);
          
          // Kirim notifikasi
          $loop_user = mysqli_query($conn, "SELECT `id` FROM `user` WHERE `level` = 'admin'");
          $jumlah_admin = mysqli_num_rows($loop_user);
          
          while ($lu = mysqli_fetch_assoc($loop_user)) {
            mysqli_query($conn, "
              INSERT INTO `notice2` 
              SET `id_pinjamkendaraan` = $id_pinjam_insert, 
                  `id_user` = " . $lu['id'] . ", 
                  `waktu` = " . time() . ", 
                  `status` = 0
            ");
          }

          $durasi_text = "";
          if ($diff_hours > 0) {
            $durasi_text = $diff_hours . " jam";
            if ($diff_minutes > 0) {
              $durasi_text .= " " . $diff_minutes . " menit";
            }
          } else {
            $durasi_text = $diff_minutes . " menit";
          }

          $html_success = '<div style="text-align: left;">'.
                    '<p><strong style="color:#28a745;font-size:16px;">✅ Peminjaman Berhasil!</strong></p><hr>'.
                    '<table style="width:100%;border-collapse:collapse;">'.
                    '<tr><td style="padding:8px;background:#f5f5f5;width:35%;"><b>🚗 Kendaraan</b></td><td style="padding:8px;">'.htmlspecialchars($kendaraan_dipilih['nama_kendaraan']).'</td></tr>'.
                    '<tr><td style="padding:8px;background:#f5f5f5;"><b>📅 Waktu</b></td><td style="padding:8px;">'.$format_mulai.' - '.$format_selesai.'</td></tr>'.
                    '<tr><td style="padding:8px;background:#f5f5f5;"><b>⏱️ Durasi</b></td><td style="padding:8px;">'.$durasi_text.'</td></tr>'.
                    '<tr><td style="padding:8px;background:#f5f5f5;"><b>📊 Status</b></td><td style="padding:8px;"><span style="color:#ffc107;font-weight:bold;">⏳ Menunggu Persetujuan</span></td></tr>'.
                    '</table><hr>'.
                    '<p style="margin:10px 0;"><b>📧 Notifikasi telah dikirim ke '.$jumlah_admin.' admin.</b></p>'.
                    '</div>';
          
          $html_success = addslashes($html_success);

          $alert_script = "
            Swal.fire({
              icon: 'success',
              title: 'Berhasil!',
              html: '{$html_success}',
              confirmButtonColor: '#28a745',
              confirmButtonText: 'Lihat Daftar Peminjaman',
              width: '700px'
            }).then((result) => {
              if (result.isConfirmed) {
                window.location.href = '?view=datapinjamkendaraan';
              }
            });
          ";
          
          // Reset form
          $form_data = [
            'id_kendaraan' => '',
            'tujuan' => '',
            'bagian' => '',
            'tgl_mulai' => '',
            'waktu_mulai' => '',
            'tgl_selesai' => '',
            'waktu_selesai' => ''
          ];
        } else {
          $error_message = htmlspecialchars(mysqli_error($conn));
          $alert_script = "
            Swal.fire({
              icon: 'error',
              title: 'Gagal!',
              html: '<p>Error:</p><code style=\"background:#f5f5f5;padding:10px;display:block;\">".$error_message."</code>',
              confirmButtonColor: '#d33'
            });
          ";
        }
      }
    }
  }
}
?>

<!-- SweetAlert2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- ✅ CSS Animation -->
<style>
@keyframes slideInDown {
  from { opacity: 0; transform: translateY(-30px); }
  to { opacity: 1; transform: translateY(0); }
}
.date-info-box a:hover {
  background: #10b981 !important;
  color: white !important;
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
}
#tgl_mulai[value]:not([value=""]),
#tgl_selesai[value]:not([value=""]) {
  border: 2px solid #10b981;
  background: linear-gradient(to right, #dcfce7, #ffffff);
  font-weight: 600;
}
@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.02); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0.2); }
}
</style>

<div class="page-inner">
  <form method="POST" action="" enctype="multipart/form-data" id="formPinjam">
    
    <!-- ✅ Info Box Tanggal -->
    <?php if (!empty($default_date) && isset($_GET['date'])): ?>
    <div class="row">
      <div class="col-md-12">
        <div class="date-info-box" style="background: linear-gradient(135deg, #dcfce7, #bbf7d0); padding: 18px 24px; border-radius: 14px; margin-bottom: 24px; border-left: 6px solid #10b981; display: flex; align-items: center; gap: 16px; box-shadow: 0 4px 16px rgba(16, 185, 129, 0.25); animation: slideInDown 0.5s ease-out;">
          <div style="background: linear-gradient(135deg, #10b981, #059669); color: white; width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);">
            <i class="fas fa-calendar-check"></i>
          </div>
          <div style="flex: 1;">
            <div style="font-size: 0.85rem; color: #065f46; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
              🚗 Tanggal Dipilih dari Kalender
            </div>
            <div style="font-size: 1.15rem; color: #047857; font-weight: 700;">
              <?php 
                $indo_date = date('d F Y', strtotime($default_date));
                $bulan_indo = [
                  'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
                  'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
                  'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
                  'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
                ];
                echo str_replace(array_keys($bulan_indo), array_values($bulan_indo), $indo_date);
                
                $hari_indo = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                $hari = $hari_indo[date('w', strtotime($default_date))];
                echo " <span style='color: #10b981;'>($hari)</span>";
              ?>
            </div>
          </div>
          <a href="?view=dashboard" style="background: white; color: #10b981; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.85rem; border: 2px solid #10b981; transition: all 0.3s ease;">
            <i class="fas fa-calendar-alt"></i> Pilih Tanggal Lain
          </a>
        </div>
      </div>
    </div>
    <?php endif; ?>
    
    <div class="row">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <div class="card-title">Create Peminjaman Kendaraan</div>
          </div>
          <div class="card-body">
            <div class="form-group">
              <label>Kendaraan <span style="color: red;">*</span></label>
              <select name="id_kendaraan" class="form-control" required="">
                <option value="">-- Pilih Kendaraan --</option>
                <?php
                $query_kendaraan = mysqli_query($conn, "SELECT * FROM kendaraan ORDER BY nama_kendaraan ASC");
                while ($k = mysqli_fetch_assoc($query_kendaraan)) {
                  $selected = ($form_data['id_kendaraan'] == $k['id_kendaraan']) ? 'selected' : '';
                  $deskripsi = !empty($k['deskripsi']) ? " - " . $k['deskripsi'] : "";
                  echo "<option value='" . $k['id_kendaraan'] . "' $selected>" . htmlspecialchars($k['nama_kendaraan']) . htmlspecialchars($deskripsi) . "</option>";
                }
                ?>
              </select>
            </div>
            <div class="form-group">
              <label>Tujuan <span style="color: red;">*</span></label>
              <textarea style="min-height: 146px;" name="tujuan" placeholder="Tujuan..." class="form-control" required=""><?php echo htmlspecialchars($form_data['tujuan']); ?></textarea>
            </div>
            <div class="form-group">
              <label>Bagian <span style="color: red;">*</span></label>
              <input type="text" name="bagian" placeholder="Bagian..." class="form-control" required="" value="<?php echo htmlspecialchars($form_data['bagian']); ?>">
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <div class="card-title">Data Peminjam</div>
          </div>
          <div class="card-body row">
            <div class="form-group col-6">
              <label>Tgl Mulai <span style="color: red;">*</span></label>
              <input type="date" name="tgl_mulai" id="tgl_mulai" class="form-control" required="" min="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($form_data['tgl_mulai']); ?>">
            </div>
            <div class="form-group col-6">
              <label>Waktu Mulai <span style="color: red;">*</span></label>
              <input type="time" name="waktu_mulai" id="waktu_mulai" class="form-control" required="" value="<?php echo htmlspecialchars($form_data['waktu_mulai']); ?>">
            </div>
            <div class="form-group col-6">
              <label>Tgl Selesai <span style="color: red;">*</span></label>
              <input type="date" name="tgl_selesai" id="tgl_selesai" class="form-control" required="" min="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($form_data['tgl_selesai']); ?>">
            </div>
            <div class="form-group col-6">
              <label>Waktu Selesai <span style="color: red;">*</span></label>
              <input type="time" name="waktu_selesai" id="waktu_selesai" class="form-control" required="" value="<?php echo htmlspecialchars($form_data['waktu_selesai']); ?>">
            </div>
            <input type="hidden" name="id_user" value="<?php echo $_SESSION['id'] ?>">
            <input type="hidden" name="status" value="menunggu">
          </div>
          <div class="card-action">
            <button type="submit" name="simpan" class="btn btn-success"><i class="fa fa-save"></i> Save</button>
            <a href="?view=datapinjamkendaraan" class="btn btn-danger"><i class="fa fa-undo"></i> Cancel</a>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<?php if (!empty($alert_script)): ?>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    <?php echo $alert_script; ?>
  });
</script>
<?php endif; ?>

<!-- ✅ JavaScript Auto-fill -->
<script>
window.addEventListener('DOMContentLoaded', function() {
  const urlParams = new URLSearchParams(window.location.search);
  const dateParam = urlParams.get('date');
  
  if (dateParam) {
    const tglMulai = document.querySelector('input[name="tgl_mulai"]');
    const tglSelesai = document.querySelector('input[name="tgl_selesai"]');
    
    if (tglMulai && !tglMulai.value) {
      tglMulai.value = dateParam;
      tglMulai.style.animation = 'pulse 0.6s ease-in-out';
      console.log('✅ Tanggal auto-filled:', dateParam);
    }
    if (tglSelesai && !tglSelesai.value) {
      tglSelesai.value = dateParam;
      tglSelesai.style.animation = 'pulse 0.6s ease-in-out';
    }
    
    setTimeout(() => {
      if (tglMulai) {
        tglMulai.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    }, 300);
  }
  
  // Validasi
  const tglMulaiInput = document.querySelector('input[name="tgl_mulai"]');
  const tglSelesaiInput = document.querySelector('input[name="tgl_selesai"]');
  
  if (tglMulaiInput && tglSelesaiInput) {
    tglMulaiInput.addEventListener('change', function() {
      tglSelesaiInput.min = this.value;
      if (tglSelesaiInput.value && tglSelesaiInput.value < this.value) {
        tglSelesaiInput.value = this.value;
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'info',
          title: 'Tanggal disesuaikan',
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true
        });
      }
    });
  }
});
</script>
