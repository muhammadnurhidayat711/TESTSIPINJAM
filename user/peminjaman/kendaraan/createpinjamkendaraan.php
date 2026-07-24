<?php
// ========================================
// CREATE PEMINJAMAN KENDARAAN — SIPINJAM
// ========================================

// ========================================
// ✅ PERBAIKAN: Tangkap parameter tanggal dari URL
// ========================================

require_once __DIR__ . '/../../../fcm_helper.php';

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
  $id_kendaraan = (int) ($_POST['id_kendaraan'] ?? 0);
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
      $query_kendaraan_dipilih = mysqli_query($conn, "SELECT * FROM kendaraan WHERE id_kendaraan = $id_kendaraan");
      $kendaraan_dipilih = mysqli_fetch_assoc($query_kendaraan_dipilih);
      
      // CEK JADWAL BENTROK
      $stmt_jadwal = $conn->prepare("
        SELECT pk.*, k.nama_kendaraan, k.deskripsi, u.nama_lengkap
        FROM pinjamkendaraan pk
        LEFT JOIN kendaraan k ON pk.id_kendaraan = k.id_kendaraan
        LEFT JOIN user u ON pk.id_user = u.id
        WHERE pk.id_kendaraan = ?
        AND pk.status IN ('menunggu', 'disetujui', 'dipinjam')
        AND TIMESTAMP(pk.tgl_mulai, pk.waktu_mulai) < TIMESTAMP(?, ?)
        AND TIMESTAMP(pk.tgl_selesai, pk.waktu_selesai) > TIMESTAMP(?, ?)
      ");
      $stmt_jadwal->bind_param("issss", $id_kendaraan, $tgl_selesai, $waktu_selesai, $tgl_mulai, $waktu_mulai);
      $stmt_jadwal->execute();
      $cek_jadwal = $stmt_jadwal->get_result();

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
          
          // ===============================
          // ✅ NOTIFIKASI DATABASE + FCM
          // ===============================

          // 1. Notifikasi Database (seperti biasa)
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

          // 2. Get data peminjam
          $query_peminjam = mysqli_query($conn, "SELECT nama_lengkap FROM user WHERE id = '$id_user' LIMIT 1");
          $data_peminjam = mysqli_fetch_assoc($query_peminjam);
          $nama_peminjam = $data_peminjam['nama_lengkap'] ?? 'User';

          // 3. Format data notifikasi
          $nama_kendaraan = $kendaraan_dipilih['nama_kendaraan'] ?? 'Kendaraan';
          $tgl_indo = date('d/m/Y', strtotime($tgl_mulai));
          $waktu_info = substr($waktu_mulai, 0, 5) . " - " . substr($waktu_selesai, 0, 5);

          // 4. Kirim FCM Push Notification ke semua admin
          $fcm_title = "🚗 Peminjaman Kendaraan Baru!";
          $fcm_body = "$nama_peminjam mengajukan peminjaman $nama_kendaraan pada $tgl_indo ($waktu_info)";
          $fcm_data = [
              'booking_id' => (string)$id_pinjam_insert,
              'type' => 'new_vehicle_booking',
              'kendaraan' => $nama_kendaraan,
              'tanggal' => $tgl_indo,
              'waktu' => $waktu_info,
              'peminjam' => $nama_peminjam,
              'tujuan' => $tujuan,
              'bagian' => $bagian
          ];

          $fcm_result = sendNotificationToAllAdmins($conn, $fcm_title, $fcm_body, $fcm_data);

          // 5. Hitung statistik pengiriman
          $fcm_success_count = 0;
          $fcm_failed_count = 0;
          $fcm_results = [];

          if ($fcm_result && is_array($fcm_result)) {
              $fcm_success_count = $fcm_result['success'] ?? 0;
              $fcm_failed_count = $fcm_result['failed'] ?? 0;
              $fcm_results = $fcm_result['details'] ?? [];
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

          // ✅ Format HTML Success dengan FCM Stats
          $html_success = '<div style="text-align: left; line-height: 1.6;">'.
                    
                    // Detail Peminjaman
                    '<div style="padding: 16px; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-left: 4px solid #10b981; border-radius: 12px; margin-bottom: 16px;">'.
                    '<div style="font-size: 16px; font-weight: 700; color: #047857; margin-bottom: 12px;">📋 Detail Peminjaman</div>'.
                    '<table style="width: 100%; font-size: 14px;">'.
                    '<tr><td style="padding: 6px 0; color: #065f46; font-weight: 600; width: 35%;">ID Booking</td><td style="padding: 6px 0;">: <code style="background: #fff; padding: 4px 8px; border-radius: 6px; font-weight: 700; color: #047857;">#'.$id_pinjam_insert.'</code></td></tr>'.
                    '<tr><td style="padding: 6px 0; color: #065f46; font-weight: 600;">🚗 Kendaraan</td><td style="padding: 6px 0;">: <strong>'.htmlspecialchars($kendaraan_dipilih['nama_kendaraan']).'</strong></td></tr>'.
                    '<tr><td style="padding: 6px 0; color: #065f46; font-weight: 600;">👤 Peminjam</td><td style="padding: 6px 0;">: '.htmlspecialchars($nama_peminjam).'</td></tr>'.
                    '<tr><td style="padding: 6px 0; color: #065f46; font-weight: 600;">🏢 Bagian</td><td style="padding: 6px 0;">: '.htmlspecialchars($bagian).'</td></tr>'.
                    '<tr><td style="padding: 6px 0; color: #065f46; font-weight: 600;">📅 Waktu</td><td style="padding: 6px 0;">: '.$format_mulai.' - '.$format_selesai.'</td></tr>'.
                    '<tr><td style="padding: 6px 0; color: #065f46; font-weight: 600;">⏱️ Durasi</td><td style="padding: 6px 0;">: '.$durasi_text.'</td></tr>'.
                    '</table>'.
                    '</div>'.
                    
                    // Status Notifikasi FCM
                    '<div style="padding: 16px; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 4px solid #3b82f6; border-radius: 12px; margin-bottom: 16px;">'.
                    '<div style="font-size: 15px; font-weight: 700; color: #1e40af; margin-bottom: 10px;">🔔 Status Notifikasi</div>'.
                    '<div style="display: flex; gap: 20px; margin-bottom: 8px;">'.
                    '<div style="flex: 1;">'.
                    '<div style="font-size: 12px; color: #1e40af; margin-bottom: 4px;">Berhasil Terkirim</div>'.
                    '<div style="font-size: 24px; font-weight: 800; color: #10b981;">'.$fcm_success_count.' <span style="font-size: 14px; font-weight: 600;">admin</span></div>'.
                    '</div>'.
                    '<div style="flex: 1;">'.
                    '<div style="font-size: 12px; color: #1e40af; margin-bottom: 4px;">Gagal</div>'.
                    '<div style="font-size: 24px; font-weight: 800; color: #ef4444;">'.$fcm_failed_count.'</div>'.
                    '</div>'.
                    '</div>'.
                    '<div style="font-size: 12px; color: #1e40af; background: rgba(255, 255, 255, 0.6); padding: 8px 10px; border-radius: 6px; margin-top: 8px;">'.
                    '💡 <strong>Tips:</strong> Tekan <kbd style="background: #fff; padding: 2px 6px; border-radius: 4px; border: 1px solid #cbd5e1;">F12</kbd> untuk melihat detail di Console'.
                    '</div>'.
                    '</div>'.
                    
                    // Status Approval
                    '<div style="padding: 14px; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 4px solid #f59e0b; border-radius: 12px;">'.
                    '<div style="display: flex; align-items: start; gap: 12px;">'.
                    '<div style="font-size: 28px; line-height: 1;">⏳</div>'.
                    '<div>'.
                    '<div style="font-weight: 700; color: #92400e; margin-bottom: 4px; font-size: 15px;">Menunggu Persetujuan Admin</div>'.
                    '<div style="font-size: 13px; color: #78350f; line-height: 1.5;">Peminjaman akan diproses oleh admin. Anda akan menerima notifikasi setelah direview.</div>'.
                    '</div>'.
                    '</div>'.
                    '</div>'.
                    
                    '</div>';

          $html_success = addslashes($html_success);
          $alert_script = "
            // ✅ Log ke Console
            console.group('🚗 PEMINJAMAN KENDARAAN BERHASIL');
            console.log('%c✅ Booking ID: #$id_pinjam_insert', 'color: #10b981; font-weight: bold; font-size: 16px');
            console.log('🚗 Kendaraan:', ".json_encode($nama_kendaraan).");
            console.log('👤 Peminjam:', ".json_encode($nama_peminjam).");
            console.log('🏢 Bagian:', ".json_encode($bagian).");
            console.log('📅 Tanggal:', ".json_encode($tgl_indo).");
            console.log('⏰ Waktu:', ".json_encode($waktu_info).");
            console.log('🔔 FCM Terkirim:', $fcm_success_count, 'admin');
            console.log('❌ FCM Gagal:', $fcm_failed_count);
            console.groupEnd();
            
            // SweetAlert
            Swal.fire({
              icon: 'success',
              title: '🎉 Berhasil!',
              html: '{$html_success}',
              confirmButtonColor: '#10b981',
              confirmButtonText: '✅ Lihat Daftar Peminjaman',
              showCancelButton: true,
              cancelButtonText: '📝 Buat Peminjaman Baru',
              cancelButtonColor: '#3b82f6',
              width: '650px',
              allowOutsideClick: false
            }).then((result) => {
              if (result.isConfirmed) {
                window.location.href = '?view=datapinjamkendaraan';
              } else if (result.dismiss === Swal.DismissReason.cancel) {
                window.location.href = '?view=createpinjamkendaraan';
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
