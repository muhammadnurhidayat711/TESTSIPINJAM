<?php
// Pastikan session & koneksi sudah tersedia di file induk
// session_start();
// include "koneksi.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Pastikan user login
if (empty($_SESSION['id'])) {
  echo "<script>alert('Sesi berakhir. Silakan login ulang.'); window.location.href='../index.php';</script>";
  exit;
}

// ===============================
// ✅ INCLUDE FCM FUNCTION YANG SUDAH ADA
// ===============================
// Jika fcm_send.php ada di folder yang sama
if (file_exists('fcm_send.php')) {
    include_once 'fcm_send.php';
} elseif (file_exists('../fcm_send.php')) {
    include_once '../fcm_send.php';
} elseif (file_exists('../../fcm_send.php')) {
    include_once '../../fcm_send.php';
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'] ?? '';

// ========================================
// ✅ PERBAIKAN: Tangkap parameter tanggal dari URL
// ========================================
$default_date = '';
$default_date_end = '';

if (isset($_GET['date']) && !empty($_GET['date'])) {
  $date_param = trim($_GET['date']);
  if (preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $date_param)) {
    $date_parts = explode('-', $date_param);
    if (count($date_parts) === 3 && checkdate((int) $date_parts[1], (int) $date_parts[2], (int) $date_parts[0])) {
      $default_date = htmlspecialchars($date_param, ENT_QUOTES, 'UTF-8');
      $default_date_end = $default_date;
    }
  }
}

// ==== OLD VALUE & ERROR HANDLING ====
$old = [
  'id_barang' => '',
  'lainn' => '',
  'lainnn' => '',
  'meja' => 'Tidak',
  'jumlah_meja' => '',
  'kursi' => 'Tidak',
  'jumlah_kursi' => '',
  'sound' => 'Tidak',
  'proyektor' => 'Tidak',
  'tgl_mulai' => $default_date,
  'waktu_mulai' => '',
  'tgl_selesai' => $default_date_end,
  'waktu_selesai' => '',
  'is_recurring' => 'no',
  'tujuan_barang' => '',
  'lain' => '',
  'recurring_days' => []
];

$error_title = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
  foreach ($old as $key => $default) {
    if ($key === 'recurring_days') {
      $old['recurring_days'] = isset($_POST['recurring_days']) && is_array($_POST['recurring_days'])
        ? $_POST['recurring_days']
        : [];
    } else {
      $old[$key] = $_POST[$key] ?? $default;
    }
  }

  // ===== CSRF CHECK =====
  $posted_token = $_POST['csrf_token'] ?? '';
  if (!$posted_token || !$csrf_token || !hash_equals($csrf_token, $posted_token)) {
    $error_title = 'Akses Ditolak';
    $error_message = 'Token keamanan tidak valid. Muat ulang halaman dan coba lagi.';
  } else {
    $id_user = (int) ($_SESSION['id'] ?? 0);
    if ($id_user <= 0) {
      $error_title = 'Sesi Berakhir';
      $error_message = 'Silakan login ulang.';
    } else {
      function clean_str($conn, $key)
      {
        return mysqli_real_escape_string($conn, trim($_POST[$key] ?? ''));
      }

      $id_barang = (int) ($_POST['id_barang'] ?? 0);
      $meja = clean_str($conn, 'meja');
      $jumlah_meja_in = trim($_POST['jumlah_meja'] ?? '');
      $kursi = clean_str($conn, 'kursi');
      $jumlah_kursi_in = trim($_POST['jumlah_kursi'] ?? '');
      $sound = clean_str($conn, 'sound');
      $proyektor = clean_str($conn, 'proyektor');
      $tgl_mulai = clean_str($conn, 'tgl_mulai');
      $waktu_mulai = clean_str($conn, 'waktu_mulai');
      $tgl_selesai = clean_str($conn, 'tgl_selesai');
      $waktu_selesai = clean_str($conn, 'waktu_selesai');
      $tujuan_barang = clean_str($conn, 'tujuan_barang');
      $lain = clean_str($conn, 'lain');
      $lainn = clean_str($conn, 'lainn');
      $lainnn = clean_str($conn, 'lainnn');

      $can_proceed = true;

      $allowed_yesno = ['Iya', 'Tidak'];
      if (!in_array($meja, $allowed_yesno, true))
        $meja = 'Tidak';
      if (!in_array($kursi, $allowed_yesno, true))
        $kursi = 'Tidak';
      if (!in_array($sound, $allowed_yesno, true))
        $sound = 'Tidak';
      if (!in_array($proyektor, $allowed_yesno, true))
        $proyektor = 'Tidak';

      if ($id_barang <= 0 || !$tgl_mulai || !$waktu_mulai || !$tgl_selesai || !$waktu_selesai || !$tujuan_barang || !$lainn || !$lainnn) {
        $error_title = 'Data Belum Lengkap';
        $error_message = "Pastikan semua field wajib (barang, PIC, tanggal dan waktu, tujuan) sudah diisi.";
        $can_proceed = false;
      }

      if ($can_proceed && $meja === 'Iya' && $jumlah_meja_in === '') {
        $error_title = 'Jumlah Meja Kosong';
        $error_message = 'Anda memilih butuh meja, namun jumlah meja belum diisi.';
        $can_proceed = false;
      }
      if ($can_proceed && $kursi === 'Iya' && $jumlah_kursi_in === '') {
        $error_title = 'Jumlah Kursi Kosong';
        $error_message = 'Anda memilih butuh kursi, namun jumlah kursi belum diisi.';
        $can_proceed = false;
      }

      if ($meja !== 'Iya')
        $jumlah_meja_in = '';
      if ($kursi !== 'Iya')
        $jumlah_kursi_in = '';

      $jumlah_meja = mysqli_real_escape_string($conn, $jumlah_meja_in);
      $jumlah_kursi = mysqli_real_escape_string($conn, $jumlah_kursi_in);

      $is_recurring = isset($_POST['is_recurring']) ? clean_str($conn, 'is_recurring') : 'no';
      $is_recurring = ($is_recurring === 'yes') ? 'yes' : 'no';
      $recurring_days = '';
      if ($is_recurring === 'yes' && isset($_POST['recurring_days']) && is_array($_POST['recurring_days'])) {
        $days_int = array_map('intval', $_POST['recurring_days']);
        $days_int = array_filter($days_int, function ($d) {
          return $d >= 1 && $d <= 7;
        });
        $recurring_days = implode(',', $days_int);

        if ($can_proceed && empty($recurring_days)) {
          $error_title = 'Gagal';
          $error_message = 'Pilih minimal 1 hari untuk peminjaman rutin.';
          $can_proceed = false;
        }
      } else {
        $is_recurring = 'no';
      }

      $datetime_mulai_baru = $tgl_mulai . ' ' . $waktu_mulai;
      $datetime_selesai_baru = $tgl_selesai . ' ' . $waktu_selesai;

      if ($can_proceed && strtotime($datetime_selesai_baru) <= strtotime($datetime_mulai_baru)) {
        $error_title = 'Waktu Tidak Valid';
        $error_message = 'Waktu selesai harus lebih besar dari waktu mulai.';
        $can_proceed = false;
      }

      $data_barang = ['nama_barang' => ''];
      if ($can_proceed) {
        $query_barang = mysqli_query($conn, "SELECT nama_barang FROM barang WHERE id = '$id_barang' LIMIT 1");
        $data_barang = mysqli_fetch_assoc($query_barang) ?: ['nama_barang' => ''];
      }

      // ===== CEK KONFLIK JADWAL =====
      if ($can_proceed) {
        $query_cek = "
                    SELECT p.*, u.nama_lengkap AS nama_peminjam, u.email AS email_peminjam
                    FROM pinjambarang p
                    JOIN user u ON u.id = p.id_user
                    WHERE p.id_barang = '$id_barang'
                      AND TRIM(LOWER(p.status)) IN ('menunggu','approve')
                ";
        $result_cek = mysqli_query($conn, $query_cek);
        if (!$result_cek) {
          $error_title = 'Gagal';
          $error_message = 'Error database saat cek jadwal: ' . mysqli_error($conn);
          $can_proceed = false;
        } else {
          $ada_konflik = false;
          $info_konflik_detail = '';
          $id_booking_konflik = 0;
          $data_peminjam_konflik = null;

          while ($existing = mysqli_fetch_assoc($result_cek)) {
            $existing_start = strtotime($existing['tgl_mulai'] . ' ' . $existing['waktu_mulai']);
            $existing_end = strtotime($existing['tgl_selesai'] . ' ' . $existing['waktu_selesai']);
            $new_start = strtotime($datetime_mulai_baru);
            $new_end = strtotime($datetime_selesai_baru);

            if ($new_end <= $existing_start || $new_start >= $existing_end) {
              continue;
            }

            if ($existing['is_recurring'] === 'yes' && !empty($existing['recurring_days'])) {
              $existing_days = explode(',', $existing['recurring_days']);

              if ($is_recurring === 'yes' && !empty($recurring_days)) {
                $new_days = explode(',', $recurring_days);
                $overlap_days = array_intersect($existing_days, $new_days);

                if (!empty($overlap_days)) {
                  $existing_time_start = strtotime('1970-01-01 ' . $existing['waktu_mulai']);
                  $existing_time_end = strtotime('1970-01-01 ' . $existing['waktu_selesai']);
                  $new_time_start = strtotime('1970-01-01 ' . $waktu_mulai);
                  $new_time_end = strtotime('1970-01-01 ' . $waktu_selesai);

                  if (!($new_time_end <= $existing_time_start || $new_time_start >= $existing_time_end)) {
                    $ada_konflik = true;
                    $id_booking_konflik = $existing['id_pinjam'];
                    $data_peminjam_konflik = $existing;
                    $day_names = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
                    $overlap_day_names = array_map(function ($d) use ($day_names) {
                      return $day_names[$d] ?? $d;
                    }, $overlap_days);
                    $info_konflik_detail = "Peminjaman rutin setiap " . implode(', ', $overlap_day_names) . " dari " . date('d-m-Y', strtotime($existing['tgl_mulai'])) . " sampai " . date('d-m-Y', strtotime($existing['tgl_selesai'])) . " jam " . $existing['waktu_mulai'] . " - " . $existing['waktu_selesai'];
                    break;
                  }
                }
              } else {
                $current_date = strtotime($tgl_mulai);
                $end_date = strtotime($tgl_selesai);

                while ($current_date <= $end_date) {
                  $day_of_week = date('N', $current_date);
                  if (in_array($day_of_week, $existing_days)) {
                    $current_date_str = date('Y-m-d', $current_date);
                    $new_time_start = strtotime($current_date_str . ' ' . $waktu_mulai);
                    $new_time_end = strtotime($current_date_str . ' ' . $waktu_selesai);
                    $existing_time_start = strtotime($current_date_str . ' ' . $existing['waktu_mulai']);
                    $existing_time_end = strtotime($current_date_str . ' ' . $existing['waktu_selesai']);

                    if (!($new_time_end <= $existing_time_start || $new_time_start >= $existing_time_end)) {
                      $ada_konflik = true;
                      $id_booking_konflik = $existing['id_pinjam'];
                      $data_peminjam_konflik = $existing;
                      $day_names = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
                      $info_konflik_detail = "Tanggal " . date('d-m-Y', $current_date) . " (" . $day_names[$day_of_week] . ") jam " . $existing['waktu_mulai'] . " - " . $existing['waktu_selesai'] . " (Peminjaman rutin)";
                      break 2;
                    }
                  }
                  $current_date = strtotime('+1 day', $current_date);
                }
              }
            } else {
              if ($is_recurring === 'yes' && !empty($recurring_days)) {
                $new_days = explode(',', $recurring_days);
                $current_date = $existing_start;

                while ($current_date <= $existing_end) {
                  $day_of_week = date('N', $current_date);

                  if (in_array($day_of_week, $new_days)) {
                    $current_date_str = date('Y-m-d', $current_date);
                    $new_time_start = strtotime($current_date_str . ' ' . $waktu_mulai);
                    $new_time_end = strtotime($current_date_str . ' ' . $waktu_selesai);
                    $existing_time_start = strtotime($current_date_str . ' ' . $existing['waktu_mulai']);
                    $existing_time_end = strtotime($current_date_str . ' ' . $existing['waktu_selesai']);

                    if (!($new_time_end <= $existing_time_start || $new_time_start >= $existing_time_end)) {
                      $ada_konflik = true;
                      $id_booking_konflik = $existing['id_pinjam'];
                      $data_peminjam_konflik = $existing;
                      $day_names = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
                      $info_konflik_detail = "Tanggal " . date('d-m-Y', $current_date) . " (" . $day_names[$day_of_week] . ") jam " . $existing['waktu_mulai'] . " - " . $existing['waktu_selesai'];
                      break 2;
                    }
                  }
                  $current_date = strtotime('+1 day', $current_date);
                }
              } else {
                if (!($new_end <= $existing_start || $new_start >= $existing_end)) {
                  $ada_konflik = true;
                  $id_booking_konflik = $existing['id_pinjam'];
                  $data_peminjam_konflik = $existing;
                  $info_konflik_detail = "Tanggal " . date('d-m-Y', strtotime($existing['tgl_mulai'])) . " jam " . $existing['waktu_mulai'] . " s/d " . date('d-m-Y', strtotime($existing['tgl_selesai'])) . " jam " . $existing['waktu_selesai'] . " (Status: " . strtoupper($existing['status']) . ")";
                  break;
                }
              }
            }
          }

          if ($ada_konflik) {
            $periode_gagal = date('d-m-Y H:i', strtotime($datetime_mulai_baru)) . " s/d " . date('d-m-Y H:i', strtotime($datetime_selesai_baru));
            if ($is_recurring === 'yes' && !empty($recurring_days)) {
              $day_names = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
              $selected_days = explode(',', $recurring_days);
              $day_labels = array_map(function ($day) use ($day_names) {
                return $day_names[$day] ?? $day;
              }, $selected_days);
              $periode_gagal .= " (Rutin setiap: " . implode(', ', $day_labels) . ")";
            }

            mysqli_query($conn, "INSERT INTO notice1 (id_pinjambarang, id_user, waktu, status) VALUES ($id_booking_konflik, $id_user, " . time() . ", 0)");
            $loop_admin = mysqli_query($conn, "SELECT `id` FROM `user` WHERE `level` = 'admin'");
            while ($admin = mysqli_fetch_assoc($loop_admin)) {
              mysqli_query($conn, "INSERT INTO notice1 (id_pinjambarang, id_user, waktu, status) VALUES ($id_booking_konflik, {$admin['id']}, " . time() . ", 0)");
            }

            $alert_text = "Ruangan: " . ($data_barang['nama_barang'] ?? '') . "\\n\\n" .
              "Sudah dipinjam pada:\\n• " . $info_konflik_detail . "\\n" .
              "• Dipinjam oleh: " . $data_peminjam_konflik['nama_peminjam'] . "\\n\\n" .
              "Periode Anda:\\n• " . $periode_gagal . "\\n\\n" .
              "Solusi:\\n" .
              "1. Pilih waktu berbeda di hari yang sama\\n" .
              "2. Pilih tanggal lain\\n" .
              "3. Pilih hari lain (untuk recurring)\\n" .
              "4. Pilih ruangan lain";

            $error_title = 'Booking Gagal - Konflik Jadwal';
            $error_message = $alert_text;
            $can_proceed = false;
          }
        }
      }

      // ================== TIDAK ADA KONFLIK → INSERT ==================
      if ($can_proceed) {
        $status = 'menunggu';

        $layout = '';
        if (isset($_FILES['layout']) && $_FILES['layout']['error'] === UPLOAD_ERR_OK) {
          $layout_name = $_FILES['layout']['name'];
          $file_tmp = $_FILES['layout']['tmp_name'];
          $file_ext = strtolower(pathinfo($layout_name, PATHINFO_EXTENSION));
          $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];

          if (in_array($file_ext, $allowed_ext, true)) {
            $new_filename = time() . '_' . preg_replace('/[^A-Za-z0-9_\\.-]/', '_', $layout_name);
            $upload_path = 'peminjaman/barang/layout/';
            if (!file_exists($upload_path)) {
              mkdir($upload_path, 0777, true);
            }
            if (move_uploaded_file($file_tmp, $upload_path . $new_filename)) {
              $layout = mysqli_real_escape_string($conn, $new_filename);
            }
          }
        }

        $query_insert = "INSERT INTO pinjambarang (
                    id_user, id_barang, meja, jumlah_meja, kursi, jumlah_kursi, sound, proyektor,
                    tgl_mulai, waktu_mulai, tgl_selesai, waktu_selesai, tujuan_barang, layout,
                    ket, nama, nohp, status, is_recurring, recurring_days
                ) VALUES (
                    '$id_user', '$id_barang', '$meja', '$jumlah_meja', '$kursi', '$jumlah_kursi',
                    '$sound', '$proyektor', '$tgl_mulai', '$waktu_mulai', '$tgl_selesai', '$waktu_selesai',
                    '$tujuan_barang', '$layout', '$lain', '$lainn', '$lainnn', '$status',
                    '$is_recurring', '$recurring_days'
                )";

        if (mysqli_query($conn, $query_insert)) {
          $id_pinjam_insert = mysqli_insert_id($conn);

          // Notif ke admin (database)
          $loop_admin = mysqli_query($conn, "SELECT `id` FROM `user` WHERE `level` = 'admin'");
          while ($admin = mysqli_fetch_assoc($loop_admin)) {
            mysqli_query($conn, "INSERT INTO notice1 (id_pinjambarang, id_user, waktu, status) VALUES ($id_pinjam_insert, {$admin['id']}, " . time() . ", 0)");
          }
          mysqli_query($conn, "INSERT INTO notice1 (id_pinjambarang, id_user, waktu, status) VALUES ($id_pinjam_insert, $id_user, " . time() . ", 0)");

          // ===============================
          // ✅ KIRIM NOTIFIKASI FCM MENGGUNAKAN FUNCTION DARI fcm_send.php
          // ===============================
          
          $query_user = mysqli_query($conn, "SELECT nama_lengkap FROM user WHERE id = '$id_user' LIMIT 1");
          $data_user = mysqli_fetch_assoc($query_user);
          $nama_peminjam = $data_user['nama_lengkap'] ?? 'User';
          
          $nama_gedung = $data_barang['nama_barang'] ?? 'Gedung';
          $tgl_indo = date('d/m/Y', strtotime($tgl_mulai));
          $waktu_info = "$waktu_mulai - $waktu_selesai";
          
          // Siapkan array untuk tracking hasil
          $fcm_results = [];
          $fcm_success_count = 0;
          $fcm_failed_count = 0;
          
          // Query semua admin
          $query_admins = mysqli_query($conn, "SELECT id, username, nama_lengkap FROM user WHERE level = 'admin'");
          
          if ($query_admins && mysqli_num_rows($query_admins) > 0) {
              while($admin = mysqli_fetch_assoc($query_admins)) {
                  // Gunakan function dari fcm_send.php
                  if (function_exists('sendFCMNotification')) {
                      $fcm_result = sendFCMNotification(
                          $admin['id'], // User ID admin
                          "🔔 Peminjaman Baru!", // Title
                          "$nama_peminjam mengajukan peminjaman $nama_gedung pada $tgl_indo ($waktu_info)", // Body
                          'https://sipinjam.sika.web.id/admin/?view=datapinjambarang', // Click action
                          '' // Image URL (optional)
                      );
                      
                      // Tracking hasil
                      $is_success = $fcm_result['success'] ?? false;
                      $error_msg = $fcm_result['message'] ?? 'Unknown error';
                      
                      $fcm_results[] = [
                          'admin' => $admin['username'],
                          'nama' => $admin['nama_lengkap'],
                          'success' => $is_success,
                          'http_code' => $is_success ? 200 : 400,
                          'token_preview' => 'Check fcm_tokens table',
                          'error_message' => $is_success ? '' : $error_msg
                      ];
                      
                      if ($is_success) {
                          $fcm_success_count++;
                          error_log("✅ FCM SUCCESS for admin: {$admin['username']}");
                      } else {
                          $fcm_failed_count++;
                          error_log("❌ FCM FAILED for admin: {$admin['username']} | Error: $error_msg");
                      }
                  } else {
                      // Jika function tidak tersedia
                      $fcm_results[] = [
                          'admin' => $admin['username'],
                          'nama' => $admin['nama_lengkap'],
                          'success' => false,
                          'http_code' => 0,
                          'token_preview' => 'N/A',
                          'error_message' => 'fcm_send.php tidak di-include atau function tidak ditemukan'
                      ];
                      $fcm_failed_count++;
                      error_log("❌ FCM function not found for admin: {$admin['username']}");
                  }
              }
          } else {
              error_log("⚠️ No admin found in database");
          }
          
          // ✅ PERBAIKAN 4: Format info recurring
          $recurring_info = '';
          if ($is_recurring === 'yes') {
              $day_names = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
              $selected_days = explode(',', $recurring_days);
              $day_labels = array_map(function($day) use ($day_names) {
                  return $day_names[$day] ?? $day;
              }, $selected_days);
              $recurring_info = '<div style="margin-top:10px; padding:8px; background:#eff6ff; border-left:3px solid #3b82f6; border-radius:4px;">
                  <strong>📅 Peminjaman Rutin:</strong><br>
                  Setiap ' . implode(', ', $day_labels) . '<br>
                  Periode: ' . date('d/m/Y', strtotime($tgl_mulai)) . ' - ' . date('d/m/Y', strtotime($tgl_selesai)) . '
              </div>';
          }
          
          // ✅ PERBAIKAN 5: SUCCESS dengan detail FCM di SweetAlert + Console Log
          // Escape semua variabel untuk JavaScript
          $nama_gedung_js = addslashes($nama_gedung);
          $nama_peminjam_js = addslashes($nama_peminjam);
          $lainn_js = addslashes($lainn);
          $lainnn_js = addslashes($lainnn);
          $tgl_indo_js = addslashes($tgl_indo);
          $waktu_info_js = addslashes($waktu_info);
          $recurring_info_js = addslashes($recurring_info);
          
          echo "<script>\n";
          echo "// FCM NOTIFICATION RESULTS\n";
          echo "console.group('🔔 FCM NOTIFICATION RESULTS');\n";
          echo "console.log('%c📊 Summary', 'color: #3b82f6; font-weight: bold; font-size: 14px');\n";
          echo "console.log('✅ Success: {$fcm_success_count}');\n";
          echo "console.log('❌ Failed: {$fcm_failed_count}');\n";
          echo "console.log('📝 Booking ID: {$id_pinjam_insert}');\n";
          echo "console.log('🏛️ Gedung: {$nama_gedung_js}');\n";
          echo "console.log('📅 Tanggal: {$tgl_indo_js}');\n";
          echo "console.log('⏰ Waktu: {$waktu_info_js}');\n";
          echo "console.log('');\n";
          echo "console.log('%c📤 Detail Pengiriman', 'color: #10b981; font-weight: bold; font-size: 14px');\n";
          
          // Output detail per admin
          foreach($fcm_results as $idx => $res) {
              $status_icon = $res['success'] ? '✅' : '❌';
              $status_text = $res['success'] ? 'SUCCESS' : 'FAILED';
              $color = $res['success'] ? '#10b981' : '#ef4444';
              $admin_name = addslashes($res['nama']);
              $admin_username = addslashes($res['admin']);
              $error_msg = isset($res['error_message']) ? addslashes($res['error_message']) : '';
              
              $num = $idx + 1;
              echo "console.log('%c{$num}. {$admin_name} (@{$admin_username})', 'color: {$color}; font-weight: bold');\n";
              echo "console.log('   Status: {$status_icon} {$status_text}');\n";
              echo "console.log('   HTTP Code: {$res['http_code']}');\n";
              echo "console.log('   Token: {$res['token_preview']}');\n";
              
              if (!$res['success'] && !empty($error_msg)) {
                  echo "console.log('   ⚠️ Error: {$error_msg}');\n";
              }
              
              echo "console.log('');\n";
          }
          
          echo "console.groupEnd();\n";
          echo "console.log('%c════════════════════════════════════', 'color: #cbd5e1');\n\n";
          
          echo "// TAMPILKAN SWEETALERT\n";
          echo "if (window.Swal) {\n";
          echo "    Swal.fire({\n";
          echo "        icon: 'success',\n";
          echo "        title: '✅ Peminjaman Berhasil Disimpan!',\n";
          echo "        html: `<div style=\"text-align:left; font-size:14px;\">\n";
          echo "            <div style=\"padding:10px; background:#f0fdf4; border-left:3px solid #10b981; border-radius:4px; margin-bottom:12px;\">\n";
          echo "                <strong>📋 Detail Peminjaman:</strong><br>\n";
          echo "                • ID Booking: <code>#{$id_pinjam_insert}</code><br>\n";
          echo "                • Gedung: <strong>{$nama_gedung_js}</strong><br>\n";
          echo "                • PIC: {$lainn_js} ({$lainnn_js})<br>\n";
          echo "                • Tanggal: {$tgl_indo_js}<br>\n";
          echo "                • Waktu: {$waktu_info_js}\n";
          echo "            </div>\n";
          
          if (!empty($recurring_info)) {
              echo "            {$recurring_info_js}\n";
          }
          
          echo "            <div style=\"padding:10px; background:#eff6ff; border-left:3px solid #3b82f6; border-radius:4px; margin-top:12px;\">\n";
          echo "                <strong>🔔 Status Notifikasi FCM:</strong><br>\n";
          echo "                • ✅ Berhasil: <span style=\"color:#10b981; font-weight:bold;\">{$fcm_success_count} admin</span><br>\n";
          echo "                • ❌ Gagal: <span style=\"color:#ef4444; font-weight:bold;\">{$fcm_failed_count}</span><br>\n";
          echo "                <small style=\"color:#6b7280;\">💡 Cek browser console (F12) untuk detail lengkap</small>\n";
          echo "            </div>\n";
          echo "            <div style=\"margin-top:12px; padding:8px; background:#fef3c7; border-left:3px solid #f59e0b; border-radius:4px;\">\n";
          echo "                ⏳ <strong>Menunggu Persetujuan Admin</strong><br>\n";
          echo "                <small>Anda akan mendapat notifikasi setelah admin memproses peminjaman ini.</small>\n";
          echo "            </div>\n";
          echo "        </div>`,\n";
          echo "        confirmButtonText: 'OK, Lihat Data Peminjaman',\n";
          echo "        confirmButtonColor: '#3b82f6',\n";
          echo "        width: '600px'\n";
          echo "    }).then(() => {\n";
          echo "        window.location.href = '?view=datapinjambarang';\n";
          echo "    });\n";
          echo "} else {\n";
          echo "    alert('Peminjaman berhasil disimpan!\\n\\nID: {$id_pinjam_insert}\\nGedung: {$nama_gedung_js}\\n\\nFCM Terkirim: {$fcm_success_count} admin');\n";
          echo "    window.location.href = '?view=datapinjambarang';\n";
          echo "}\n";
          echo "</script>\n";
          
          exit;
        } else {
          $error_title = 'Gagal Menyimpan';
          $error_message = 'Terjadi kesalahan database: ' . mysqli_error($conn);
        }
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <title>Create Peminjaman Gedung</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    :root {
      --bg: #e5edff;
      --card-bg: #ffffff;
      --border-subtle: #e5e7eb;
      --accent: #2563eb;
      --accent-soft: #eff6ff;
      --accent-hover: #1d4ed8;
      --danger: #ef4444;
      --danger-soft: #fee2e2;
      --text: #111827;
      --text-muted: #6b7280;
      --radius-lg: 18px;
      --radius-md: 10px;
      --shadow-soft: 0 16px 35px rgba(148, 163, 184, 0.28);
      --input-bg: #ffffff;
      --input-border: #d1d5db;
      --input-border-focus: #2563eb;
    }

    *,
    *::before,
    *::after {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      min-height: 100vh;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "SF Pro Text",
        "Inter", "Segoe UI", sans-serif;
      background: radial-gradient(circle at top, #dbeafe 0, #e5edff 40%, #eef2ff 100%);
      color: var(--text);
      -webkit-font-smoothing: antialiased;
    }

    .page-inner {
      padding: 24px 16px 40px;
      max-width: 1120px;
      margin: 0 auto;
    }

    .row {
      display: flex;
      flex-wrap: wrap;
      gap: 18px;
    }

    .col-md-6 {
      flex: 1 1 360px;
      min-width: 0;
    }

    .card {
      background: var(--card-bg);
      border-radius: var(--radius-lg);
      border: 1px solid var(--border-subtle);
      box-shadow: var(--shadow-soft);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      min-height: 100%;
    }

    .card-header {
      padding: 14px 18px 6px;
      border-bottom: 1px solid #e5e7eb;
      background: linear-gradient(135deg, #eff6ff, #ffffff);
    }

    .card-title {
      font-size: 0.92rem;
      font-weight: 600;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      color: #1f2937;
    }

    .card-caption {
      margin-top: 4px;
      font-size: 0.8rem;
      color: var(--text-muted);
    }

    .card-body {
      padding: 16px 18px 14px;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .card-action {
      margin-top: 4px;
      padding: 13px 18px 15px;
      border-top: 1px solid #e5e7eb;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      background: #f9fafb;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 4px;
      margin-bottom: 4px;
    }

    .form-label {
      font-size: 0.78rem;
      font-weight: 500;
      color: #4b5563;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 6px;
    }

    .form-label span.badge-soft {
      font-size: 0.68rem;
      padding: 2px 7px;
      border-radius: 999px;
      background: #f3f4f6;
      border: 1px solid #e5e7eb;
      color: var(--text-muted);
    }

    .form-hint {
      font-size: 0.72rem;
      color: var(--text-muted);
      margin-top: 1px;
    }

    .form-row-inline {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    input.form-control,
    select.form-control,
    textarea.form-control {
      width: 100%;
      padding: 8px 10px;
      font-size: 0.86rem;
      border-radius: var(--radius-md);
      border: 1px solid var(--input-border);
      background-color: var(--input-bg);
      color: var(--text);
      outline: none;
      transition: border-color 0.16s ease, box-shadow 0.16s ease, background 0.16s ease;
    }

    input.form-control::placeholder,
    textarea.form-control::placeholder {
      color: #9ca3af;
      font-size: 0.8rem;
    }

    input.form-control:focus,
    select.form-control:focus,
    textarea.form-control:focus {
      border-color: var(--input-border-focus);
      box-shadow: 0 0 0 1px rgba(37, 99, 235, 0.25);
      background-color: #ffffff;
    }

    textarea.form-control {
      resize: vertical;
      min-height: 42px;
      max-height: 220px;
    }

    select.form-control {
      cursor: pointer;
    }

    .checkbox-inline {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      margin-right: 8px;
      font-size: 0.78rem;
      color: var(--text-muted);
      padding: 4px 8px;
      border-radius: 999px;
      border: 1px solid #e5e7eb;
      background: #f9fafb;
    }

    .checkbox-inline input[type="checkbox"] {
      accent-color: var(--accent);
      cursor: pointer;
    }

    .recur-group {
      padding: 8px 10px 9px;
      border-radius: var(--radius-md);
      background: #eff6ff;
      border: 1px dashed rgba(37, 99, 235, 0.6);
    }

    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      border-radius: 999px;
      padding: 8px 15px;
      font-size: 0.82rem;
      font-weight: 600;
      border: 1px solid transparent;
      cursor: pointer;
      text-decoration: none;
      transition: transform 0.08s ease, box-shadow 0.16s ease, background 0.16s ease, border-color 0.16s ease;
    }

    .btn:active {
      transform: translateY(1px) scale(0.99);
      box-shadow: none !important;
    }

    .btn-success {
      background: linear-gradient(135deg, #3b82f6, #2563eb);
      color: white;
      box-shadow: 0 8px 18px rgba(37, 99, 235, 0.35);
    }

    .btn-success:hover {
      background: linear-gradient(135deg, #2563eb, #1d4ed8);
      box-shadow: 0 10px 22px rgba(37, 99, 235, 0.45);
    }

    .btn-danger {
      background: #ffffff;
      color: #b91c1c;
      border-color: #fecaca;
      box-shadow: 0 6px 15px rgba(248, 113, 113, 0.25);
    }

    .btn-danger:hover {
      background: #fee2e2;
    }

    .btn-icon {
      width: 18px;
      height: 18px;
      border-radius: 999px;
      border: 1px solid #e5e7eb;
      background: #f9fafb;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 0.8rem;
    }

    footer {
      text-align: center;
      font-size: 0.78rem;
      color: var(--text-muted);
      padding-bottom: 20px;
      margin-top: 10px;
    }

    footer b {
      color: #111827;
    }

    @media (max-width: 768px) {
      .page-inner {
        padding-inline: 12px;
      }

      .card {
        border-radius: 16px;
      }
    }

    @keyframes slideInDown {
      from {
        opacity: 0;
        transform: translateY(-30px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .date-info-box a:hover {
      background: #0ea5e9 !important;
      color: white !important;
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(14, 165, 233, 0.4);
    }

    #tgl_mulai[value]:not([value=""]),
    #tgl_selesai[value]:not([value=""]) {
      border: 2px solid #0ea5e9;
      background: linear-gradient(to right, #e0f2fe, #ffffff);
      font-weight: 600;
    }
  </style>
</head>

<body>

  <div class="page-inner">
    <form id="formPeminjaman" method="POST" action="" enctype="multipart/form-data" novalidate autocomplete="off">
      <input type="hidden" name="csrf_token"
        value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
      
      <?php if (!empty($default_date) && isset($_GET['date'])): ?>
        <div class="date-info-box"
          style="background: linear-gradient(135deg, #e0f2fe, #dbeafe); padding: 18px 24px; border-radius: 14px; margin-bottom: 28px; border-left: 6px solid #0ea5e9; display: flex; align-items: center; gap: 16px; box-shadow: 0 4px 16px rgba(14, 165, 233, 0.25); animation: slideInDown 0.5s ease-out;">
          <div
            style="background: linear-gradient(135deg, #0ea5e9, #0284c7); color: white; width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 12px rgba(14, 165, 233, 0.4);">
            <i class="fas fa-calendar-check"></i>
          </div>
          <div style="flex: 1;">
            <div
              style="font-size: 0.85rem; color: #0369a1; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
              📅 Tanggal Dipilih dari Kalender
            </div>
            <div style="font-size: 1.15rem; color: #075985; font-weight: 700;">
              <?php
              $indo_date = date('d F Y', strtotime($default_date));
              $bulan_indo = [
                'January' => 'Januari',
                'February' => 'Februari',
                'March' => 'Maret',
                'April' => 'April',
                'May' => 'Mei',
                'June' => 'Juni',
                'July' => 'Juli',
                'August' => 'Agustus',
                'September' => 'September',
                'October' => 'Oktober',
                'November' => 'November',
                'December' => 'Desember'
              ];
              echo str_replace(array_keys($bulan_indo), array_values($bulan_indo), $indo_date);

              $hari_indo = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
              $hari = $hari_indo[date('w', strtotime($default_date))];
              echo " <span style='color: #0ea5e9;'>($hari)</span>";
              ?>
            </div>
          </div>
          <a href="?view=dashboard"
            style="background: white; color: #0ea5e9; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.85rem; border: 2px solid #0ea5e9; transition: all 0.3s ease;">
            <i class="fas fa-calendar-alt"></i> Pilih Tanggal Lain
          </a>
        </div>
      <?php endif; ?>

      <div class="row">
        <!-- Kolom kiri -->
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <div class="card-title">Detail Ruangan & Fasilitas</div>
              <div class="card-caption">
                Pilih gedung dan fasilitas yang ingin digunakan.
              </div>
            </div>
            <div class="card-body">
              <!-- Nama Barang -->
              <div class="form-group">
                <label class="form-label">
                  <span>Nama Gedung / Barang</span>
                  <span class="badge-soft">Wajib</span>
                </label>
                <select class="form-control" id="id_barang" name="id_barang" required>
                  <option value="" hidden>— Pilih Barang —</option>
                  <?php
                  $query = mysqli_query($conn, "SELECT * FROM barang ORDER BY nama_barang ASC");
                  while ($row = mysqli_fetch_assoc($query)) {
                    $selected = ((string) $old['id_barang'] === (string) $row['id']) ? 'selected' : '';
                    echo '<option value="' . (int) $row['id'] . '" ' . $selected . '>' . htmlspecialchars($row['nama_barang']) . '</option>';
                  }
                  ?>
                </select>
                <div class="form-hint">Hanya barang yang tersedia yang dapat dipinjam.</div>
              </div>

              <!-- PIC -->
              <div class="form-group">
                <label class="form-label">
                  <span>Nama PIC</span>
                  <span class="badge-soft">Wajib</span>
                </label>
                <input type="text" class="form-control" name="lainn" maxlength="100"
                  placeholder="Nama penanggung jawab (PIC)"
                  value="<?php echo htmlspecialchars($old['lainn'], ENT_QUOTES, 'UTF-8'); ?>" required />
              </div>

              <!-- No WA PIC -->
              <div class="form-group">
                <label class="form-label">
                  <span>No. WA PIC</span>
                  <span class="badge-soft">Wajib</span>
                </label>
                <input type="tel" class="form-control" name="lainnn" maxlength="20" placeholder="Contoh: 0812xxxxxxx"
                  value="<?php echo htmlspecialchars($old['lainnn'], ENT_QUOTES, 'UTF-8'); ?>" required />
                <div class="form-hint">Pastikan nomor aktif untuk keperluan konfirmasi.</div>
              </div>

              <!-- Meja -->
              <div class="form-group">
                <label class="form-label">
                  <span>Butuh Meja?</span>
                  <span class="badge-soft">Default: Tidak</span>
                </label>
                <select class="form-control" id="meja" name="meja" required>
                  <option value="Tidak" <?php echo ($old['meja'] === 'Tidak' ? 'selected' : ''); ?>>Tidak</option>
                  <option value="Iya" <?php echo ($old['meja'] === 'Iya' ? 'selected' : ''); ?>>Iya</option>
                </select>
              </div>

              <!-- Jumlah Meja -->
              <div class="form-group" id="group_jumlah_meja" style="display:none;">
                <label class="form-label">
                  <span>Jumlah Meja</span>
                  <span class="badge-soft">Muncul jika pilih "Iya"</span>
                </label>
                <input type="text" class="form-control" name="jumlah_meja" id="jumlah_meja"
                  placeholder="Contoh: 10 (atau 10 meja siswa)"
                  value="<?php echo htmlspecialchars($old['jumlah_meja'], ENT_QUOTES, 'UTF-8'); ?>" />
              </div>

              <!-- Kursi -->
              <div class="form-group">
                <label class="form-label">
                  <span>Butuh Kursi?</span>
                  <span class="badge-soft">Default: Tidak</span>
                </label>
                <select class="form-control" id="kursi" name="kursi" required>
                  <option value="Tidak" <?php echo ($old['kursi'] === 'Tidak' ? 'selected' : ''); ?>>Tidak</option>
                  <option value="Iya" <?php echo ($old['kursi'] === 'Iya' ? 'selected' : ''); ?>>Iya</option>
                </select>
              </div>

              <!-- Jumlah Kursi -->
              <div class="form-group" id="group_jumlah_kursi" style="display:none;">
                <label class="form-label">
                  <span>Jumlah Kursi</span>
                  <span class="badge-soft">Muncul jika pilih "Iya"</span>
                </label>
                <input type="text" class="form-control" name="jumlah_kursi" id="jumlah_kursi"
                  placeholder="Contoh: 40 (atau 40 kursi siswa)"
                  value="<?php echo htmlspecialchars($old['jumlah_kursi'], ENT_QUOTES, 'UTF-8'); ?>" />
              </div>

              <!-- Sound -->
              <div class="form-group">
                <label class="form-label">
                  <span>Sound System</span>
                  <span class="badge-soft">Opsional</span>
                </label>
                <select class="form-control" id="sound" name="sound" required>
                  <option value="Tidak" <?php echo ($old['sound'] === 'Tidak' ? 'selected' : ''); ?>>Tidak</option>
                  <option value="Iya" <?php echo ($old['sound'] === 'Iya' ? 'selected' : ''); ?>>Iya</option>
                </select>
              </div>

              <!-- Proyektor -->
              <div class="form-group">
                <label class="form-label">
                  <span>Proyektor</span>
                  <span class="badge-soft">Opsional</span>
                </label>
                <select class="form-control" id="proyektor" name="proyektor" required>
                  <option value="Tidak" <?php echo ($old['proyektor'] === 'Tidak' ? 'selected' : ''); ?>>Tidak</option>
                  <option value="Iya" <?php echo ($old['proyektor'] === 'Iya' ? 'selected' : ''); ?>>Iya</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Kolom kanan -->
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <div class="card-title">Data Peminjaman</div>
              <div class="card-caption">
                Tentukan periode pemakaian dan jenis peminjaman.
              </div>
            </div>
            <div class="card-body">
              <!-- Tanggal & Waktu -->
              <div class="form-row-inline">
                <div class="form-group" style="flex: 1;">
                  <label class="form-label">
                    <span>Tgl Mulai</span>
                    <span class="badge-soft">Wajib</span>
                  </label>
                  <input type="date" name="tgl_mulai" id="tgl_mulai" class="form-control"
                    value="<?php echo htmlspecialchars($old['tgl_mulai'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="form-group" style="flex: 1;">
                  <label class="form-label">
                    <span>Waktu Mulai</span>
                    <span class="badge-soft">Wajib</span>
                  </label>
                  <input type="time" name="waktu_mulai" id="waktu_mulai" class="form-control"
                    value="<?php echo htmlspecialchars($old['waktu_mulai'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
              </div>

              <div class="form-row-inline">
                <div class="form-group" style="flex: 1;">
                  <label class="form-label">
                    <span>Tgl Selesai</span>
                    <span class="badge-soft">Wajib</span>
                  </label>
                  <input type="date" name="tgl_selesai" id="tgl_selesai" class="form-control"
                    value="<?php echo htmlspecialchars($old['tgl_selesai'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="form-group" style="flex: 1;">
                  <label class="form-label">
                    <span>Waktu Selesai</span>
                    <span class="badge-soft">Wajib</span>
                  </label>
                  <input type="time" name="waktu_selesai" id="waktu_selesai" class="form-control"
                    value="<?php echo htmlspecialchars($old['waktu_selesai'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
              </div>

              <!-- Peminjaman rutin -->
              <div class="form-group">
                <label class="form-label">
                  <span>Peminjaman Rutin?</span>
                  <span class="badge-soft">Opsional</span>
                </label>
                <select class="form-control" id="is_recurring" name="is_recurring">
                  <option value="no" <?php echo ($old['is_recurring'] === 'no' ? 'selected' : ''); ?>>Tidak</option>
                  <option value="yes" <?php echo ($old['is_recurring'] === 'yes' ? 'selected' : ''); ?>>Ya (pilih hari
                    tertentu)</option>
                </select>
              </div>

              <div class="form-group" id="recurring_days_group" style="display:none;">
                <label class="form-label">
                  <span>Hari dalam Seminggu</span>
                  <span class="badge-soft">Untuk peminjaman rutin</span>
                </label>
                <div class="recur-group">
                  <?php
                  $days = [
                    1 => 'Senin',
                    2 => 'Selasa',
                    3 => 'Rabu',
                    4 => 'Kamis',
                    5 => 'Jumat',
                    6 => 'Sabtu',
                    7 => 'Minggu'
                  ];
                  foreach ($days as $val => $label) {
                    $checked = in_array((string) $val, array_map('strval', $old['recurring_days'] ?? []), true) ? 'checked' : '';
                    echo '<label class="checkbox-inline"><input type="checkbox" name="recurring_days[]" value="' . $val . '" ' . $checked . '> ' . $label . '</label>';
                  }
                  ?>
                </div>
                <div class="form-hint">
                  Pilih minimal 1 hari jika peminjaman di-set sebagai rutin.
                </div>
              </div>

              <!-- Tujuan & Layout -->
              <div class="form-group">
                <label class="form-label">
                  <span>Tujuan Pemakaian</span>
                  <span class="badge-soft">Wajib</span>
                </label>
                <textarea class="form-control" name="tujuan_barang" rows="2"
                  placeholder="Contoh: Rapat guru, acara wisuda, pelatihan, dsb."
                  required><?php echo htmlspecialchars($old['tujuan_barang'], ENT_QUOTES, 'UTF-8'); ?></textarea>
              </div>

              <div class="form-group">
                <label class="form-label">
                  <span>Layout (opsional)</span>
                  <span class="badge-soft">JPG, PNG, PDF, DOC</span>
                </label>
                <input type="file" name="layout" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                <div class="form-hint">
                  Upload file layout jika ada (posisi kursi, meja, panggung, dll).
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">
                  <span>Catatan Tambahan</span>
                  <span class="badge-soft">Opsional</span>
                </label>
                <textarea class="form-control" name="lain" rows="3"
                  placeholder="Kebutuhan teknis lain, request khusus, dsb."><?php echo htmlspecialchars($old['lain'], ENT_QUOTES, 'UTF-8'); ?></textarea>
              </div>
            </div>

            <div class="card-action">
              <button type="submit" name="simpan" class="btn btn-success">
                <span>Simpan Peminjaman</span>
              </button>
              <a href="?view=datapinjambarang" class="btn btn-danger">
                <span>Batal</span>
              </a>
            </div>
          </div>
        </div>
      </div>
    </form>

    <footer>
      <b>&copy; 2025 SIPINJAM</b> &mdash; Sistem Informasi Peminjaman Pelita Cemerlang School
    </footer>
  </div>

  <script>
    function swalError(title, text) {
      if (window.Swal) {
        Swal.fire({
          icon: 'error',
          title: title,
          html: '<div style="text-align:left; white-space:pre-line;">' + text + '</div>'
        });
      } else {
        alert(title + ":\n" + text);
      }
    }

    document.addEventListener('DOMContentLoaded', function () {
      var form = document.getElementById('formPeminjaman');

      var mejaSelect = document.getElementById('meja');
      var kursiSelect = document.getElementById('kursi');
      var groupMeja = document.getElementById('group_jumlah_meja');
      var groupKursi = document.getElementById('group_jumlah_kursi');
      var inputJumlahMeja = document.getElementById('jumlah_meja');
      var inputJumlahKursi = document.getElementById('jumlah_kursi');

      function syncGroup(selectEl, groupEl, inputEl) {
        if (!selectEl || !groupEl || !inputEl) return;
        if (selectEl.value === 'Iya') {
          groupEl.style.display = 'block';
          inputEl.required = true;
        } else {
          groupEl.style.display = 'none';
          inputEl.required = false;
        }
      }

      if (mejaSelect && kursiSelect) {
        syncGroup(mejaSelect, groupMeja, inputJumlahMeja);
        syncGroup(kursiSelect, groupKursi, inputJumlahKursi);

        mejaSelect.addEventListener('change', function () {
          syncGroup(mejaSelect, groupMeja, inputJumlahMeja);
        });
        kursiSelect.addEventListener('change', function () {
          syncGroup(kursiSelect, groupKursi, inputJumlahKursi);
        });
      }

      var isRecurringSelect = document.getElementById('is_recurring');
      var recurringGroup = document.getElementById('recurring_days_group');
      function toggleRecurringDays() {
        recurringGroup.style.display = (isRecurringSelect.value === 'yes') ? 'block' : 'none';
      }
      if (isRecurringSelect && recurringGroup) {
        toggleRecurringDays();
        isRecurringSelect.addEventListener('change', toggleRecurringDays);
      }

      form.addEventListener('submit', function (e) {
        var tokenInput = form.querySelector('input[name="csrf_token"]');
        if (!tokenInput || !tokenInput.value) {
          e.preventDefault();
          swalError('Sesi Tidak Valid', 'Token keamanan tidak ditemukan. Muat ulang halaman dan coba lagi.');
          return false;
        }

        var isRecurring = isRecurringSelect.value;
        if (isRecurring === 'yes') {
          var checked = document.querySelectorAll('input[name="recurring_days[]"]:checked');
          if (checked.length === 0) {
            e.preventDefault();
            swalError('Gagal Memilih Jadwal', 'Anda memilih peminjaman rutin, namun belum memilih hari. Silakan pilih minimal satu hari.');
            return false;
          }
        }

        var tMulai = document.getElementById('tgl_mulai').value;
        var wMulai = document.getElementById('waktu_mulai').value;
        var tSelesai = document.getElementById('tgl_selesai').value;
        var wSelesai = document.getElementById('waktu_selesai').value;

        if (tMulai && wMulai && tSelesai && wSelesai) {
          var start = new Date(tMulai + 'T' + wMulai + ':00');
          var end = new Date(tSelesai + 'T' + wSelesai + ':00');
          if (!(end > start)) {
            e.preventDefault();
            swalError('Gagal Memilih Jadwal', 'Tanggal/Waktu selesai harus lebih besar dari Tanggal/Waktu mulai.');
            return false;
          }
        }

        if (mejaSelect && mejaSelect.value === 'Iya' && inputJumlahMeja && !inputJumlahMeja.value.trim()) {
          e.preventDefault();
          swalError('Jumlah Meja Kosong', 'Anda memilih butuh meja. Mohon isi jumlah meja.');
          inputJumlahMeja.focus();
          return false;
        }
        if (kursiSelect && kursiSelect.value === 'Iya' && inputJumlahKursi && !inputJumlahKursi.value.trim()) {
          e.preventDefault();
          swalError('Jumlah Kursi Kosong', 'Anda memilih butuh kursi. Mohon isi jumlah kursi.');
          inputJumlahKursi.focus();
          return false;
        }
      });

      <?php if ($error_title && $error_message): ?>
        swalError(
          <?php echo json_encode($error_title); ?>,
          <?php echo json_encode($error_message); ?>
        );
      <?php endif; ?>
    });

    window.addEventListener('DOMContentLoaded', function () {
      const urlParams = new URLSearchParams(window.location.search);
      const dateParam = urlParams.get('date');

      if (dateParam) {
        const tglMulai = document.getElementById('tgl_mulai');
        const tglSelesai = document.getElementById('tgl_selesai');

        if (tglMulai && !tglMulai.value) {
          tglMulai.value = dateParam;
          console.log('✅ Tanggal Mulai auto-filled:', dateParam);
          tglMulai.style.animation = 'pulse 0.6s ease-in-out';
        }
        if (tglSelesai && !tglSelesai.value) {
          tglSelesai.value = dateParam;
          console.log('✅ Tanggal Selesai auto-filled:', dateParam);
          tglSelesai.style.animation = 'pulse 0.6s ease-in-out';
        }

        setTimeout(() => {
          const dateSection = document.querySelector('[for="tgl_mulai"]');
          if (dateSection) {
            dateSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
        }, 300);
      }

      const tglMulaiInput = document.getElementById('tgl_mulai');
      const tglSelesaiInput = document.getElementById('tgl_selesai');

      if (tglMulaiInput && tglSelesaiInput) {
        tglMulaiInput.addEventListener('change', function () {
          tglSelesaiInput.min = this.value;
          if (tglSelesaiInput.value && tglSelesaiInput.value < this.value) {
            tglSelesaiInput.value = this.value;

            const toast = document.createElement('div');
            toast.style.cssText = 'position: fixed; top: 20px; right: 20px; background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 16px 24px; border-radius: 12px; box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4); z-index: 10000; font-weight: 600; animation: slideInRight 0.4s ease-out;';
            toast.innerHTML = '<i class="fas fa-info-circle"></i> Tanggal selesai disesuaikan dengan tanggal mulai';
            document.body.appendChild(toast);

            setTimeout(() => {
              toast.style.animation = 'slideOutRight 0.4s ease-in';
              setTimeout(() => toast.remove(), 400);
            }, 3000);
          }
        });
      }
    });

    const style = document.createElement('style');
    style.textContent = `
      @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.02); box-shadow: 0 0 0 8px rgba(14, 165, 233, 0.2); }
      }
      @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
      }
      @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
      }
    `;
    document.head.appendChild(style);

  </script>

</body>

</html>