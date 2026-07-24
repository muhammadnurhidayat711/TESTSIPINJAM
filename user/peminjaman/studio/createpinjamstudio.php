<?php
// ========================================
// CREATE PEMINJAMAN STUDIO — SIPINJAM
// ========================================

require_once __DIR__ . '/../../../fcm_helper.php'; // ✅ Tambahkan FCM helper

// ========= TANGKAP PARAMETER DATE DARI URL =========
$prefillDate = isset($_GET['date']) ? $_GET['date'] : '';

$alert_script = ''; // ✅ Variable untuk SweetAlert

// ✅ INISIALISASI DATA FORM
$form_data = [
    'id_studio' => '',
    'id_kelas' => '',
    'nama' => isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : '',
    'no_hp' => '',
    'tgl_mulai' => $prefillDate,
    'tgl_selesai' => '',
    'waktu_mulai' => '',
    'waktu_selesai' => '',
    'tujuan' => '',
    'deskripsi_peminjaman' => '',
    'is_recurring' => 'no',
    'recurring_days' => []
];

// ================== PROSES INSERT DATA BARU ==================
if (isset($_POST['simpan'])) {
    $id_user       = $_SESSION['id'] ?? null;
    $id_studio     = mysqli_real_escape_string($conn, $_POST['id_studio'] ?? '');
    $id_kelas      = mysqli_real_escape_string($conn, $_POST['id_kelas'] ?? '');
    $nama          = mysqli_real_escape_string($conn, $_POST['nama'] ?? '');
    $no_hp         = mysqli_real_escape_string($conn, $_POST['no_hp'] ?? '');
    $tgl_mulai     = mysqli_real_escape_string($conn, $_POST['tgl_mulai'] ?? '');
    $tgl_selesai   = mysqli_real_escape_string($conn, $_POST['tgl_selesai'] ?? '');
    $waktu_mulai   = mysqli_real_escape_string($conn, $_POST['waktu_mulai'] ?? '');
    $waktu_selesai = mysqli_real_escape_string($conn, $_POST['waktu_selesai'] ?? '');
    $tujuan        = mysqli_real_escape_string($conn, $_POST['tujuan'] ?? '');
    $deskripsi_peminjaman = mysqli_real_escape_string($conn, $_POST['deskripsi_peminjaman'] ?? '');
    $status        = 'menunggu';

    $is_recurring = (isset($_POST['is_recurring']) && $_POST['is_recurring'] === 'yes') ? 'yes' : 'no';

    $recurring_days_arr = isset($_POST['recurring_days']) && is_array($_POST['recurring_days'])
        ? $_POST['recurring_days']
        : [];

    $clean_days = [];
    foreach ($recurring_days_arr as $d) {
        $d = (int)$d;
        if ($d >= 1 && $d <= 7) {
            $clean_days[] = $d;
        }
    }
    $clean_days = array_values(array_unique($clean_days));
    sort($clean_days, SORT_NUMERIC);
    $recurring_days = !empty($clean_days) ? implode(',', $clean_days) : null;

    if (empty($tgl_selesai)) {
        $tgl_selesai = $tgl_mulai;
    }

    // ✅ SIMPAN DATA FORM UNTUK DIPERTAHANKAN
    $form_data = [
        'id_studio' => $id_studio,
        'id_kelas' => $id_kelas,
        'nama' => $nama,
        'no_hp' => $no_hp,
        'tgl_mulai' => $tgl_mulai,
        'tgl_selesai' => $tgl_selesai,
        'waktu_mulai' => $waktu_mulai,
        'waktu_selesai' => $waktu_selesai,
        'tujuan' => $tujuan,
        'deskripsi_peminjaman' => $deskripsi_peminjaman,
        'is_recurring' => $is_recurring,
        'recurring_days' => $clean_days
    ];

    // ✅ VALIDASI
    $errors = [];

    if (empty($id_user)) {
        $errors[] = 'Session pengguna tidak ditemukan. Silakan login ulang.';
    }
    if (empty($id_studio)) {
        $errors[] = 'Studio harus dipilih.';
    }
    if (empty($id_kelas)) {
        $errors[] = 'Kelas harus dipilih.';
    }
    if (empty($nama)) {
        $errors[] = 'Nama peminjam harus diisi.';
    }
    if (empty($no_hp)) {
        $errors[] = 'Nomor HP harus diisi.';
    }
    if (empty($tgl_mulai)) {
        $errors[] = 'Tanggal mulai harus diisi.';
    }
    if (empty($tgl_selesai)) {
        $errors[] = 'Tanggal selesai harus diisi.';
    }
    if (!empty($tgl_mulai) && !empty($tgl_selesai) && $tgl_selesai < $tgl_mulai) {
        $errors[] = 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.';
    }
    if (empty($waktu_mulai) || empty($waktu_selesai)) {
        $errors[] = 'Waktu mulai dan selesai harus diisi.';
    }
    if (!empty($waktu_mulai) && !empty($waktu_selesai) && $tgl_mulai === $tgl_selesai && $waktu_selesai <= $waktu_mulai) {
        $errors[] = 'Waktu selesai harus lebih besar dari waktu mulai.';
    }
    if ($is_recurring === 'yes' && empty($recurring_days)) {
        $errors[] = 'Pilih minimal satu hari untuk jadwal rutin.';
    }

    if (!empty($errors)) {
        $error_list = '';
        foreach ($errors as $err) {
            $error_list .= '<li style="text-align:left;margin:4px 0;">' . htmlspecialchars($err) . '</li>';
        }
        $alert_script = "
            Swal.fire({
                icon: 'warning',
                title: 'Data Tidak Lengkap!',
                html: '<ul style=\"padding-left:20px;margin:10px 0;\">{$error_list}</ul>',
                confirmButtonColor: '#f59e0b'
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

        // Ambil info studio dan kelas yang dipilih
        $query_studio_dipilih = mysqli_query($conn, "SELECT * FROM studio WHERE id_studio = '$id_studio'");
        $studio_dipilih = mysqli_fetch_assoc($query_studio_dipilih);

        $query_kelas_dipilih = mysqli_query($conn, "SELECT * FROM kelas WHERE id_kelas = '$id_kelas'");
        $kelas_dipilih = mysqli_fetch_assoc($query_kelas_dipilih);

        // ✅ CEK JADWAL BENTROK
        $cek_jadwal = mysqli_query($conn, "
            SELECT ps.*, s.jenis_studio, k.nama_kelas, u.nama_lengkap
            FROM pinjamstudio ps
            LEFT JOIN studio s ON ps.id_studio = s.id_studio
            LEFT JOIN kelas k ON ps.id_kelas = k.id_kelas
            LEFT JOIN user u ON ps.id_user = u.id
            WHERE ps.id_studio = '$id_studio' 
            AND ps.status IN ('menunggu', 'approve')
            AND TIMESTAMP(ps.tgl_mulai, ps.waktu_mulai) < TIMESTAMP('$tgl_selesai', '$waktu_selesai')
            AND TIMESTAMP(ps.tgl_selesai, ps.waktu_selesai) > TIMESTAMP('$tgl_mulai', '$waktu_mulai')
        ");

        if (!$cek_jadwal) {
            die("Error Query: " . mysqli_error($conn));
        }

        if (mysqli_num_rows($cek_jadwal) > 0) {
            // ❌ JADWAL BENTROK
            $data_bentrok = mysqli_fetch_assoc($cek_jadwal);

            $tgl_mulai_bentrok = date('d-m-Y', strtotime($data_bentrok['tgl_mulai']));
            $tgl_selesai_bentrok = date('d-m-Y', strtotime($data_bentrok['tgl_selesai']));
            $waktu_mulai_bentrok = substr($data_bentrok['waktu_mulai'], 0, 5);
            $waktu_selesai_bentrok = substr($data_bentrok['waktu_selesai'], 0, 5);

            $info_studio = htmlspecialchars($data_bentrok['jenis_studio']);
            $info_kelas = htmlspecialchars($data_bentrok['nama_kelas']);
            $info_jadwal_bentrok = $tgl_mulai_bentrok . " " . $waktu_mulai_bentrok . " - " . $tgl_selesai_bentrok . " " . $waktu_selesai_bentrok;
            $info_jadwal_input = $format_mulai . " - " . $format_selesai;
            $info_peminjam = !empty($data_bentrok['nama_lengkap']) ? htmlspecialchars($data_bentrok['nama_lengkap']) : "Tidak diketahui";
            $info_status = ucfirst($data_bentrok['status']);
            $info_nama = !empty($data_bentrok['nama']) ? htmlspecialchars($data_bentrok['nama']) : "-";
            $info_no_hp = !empty($data_bentrok['no_hp']) ? htmlspecialchars($data_bentrok['no_hp']) : "-";
            $info_tujuan = !empty($data_bentrok['tujuan']) ? htmlspecialchars($data_bentrok['tujuan']) : "-";

            // Cari alternatif studio yang tersedia
            $query_alternatif = mysqli_query($conn, "
                SELECT s.* FROM studio s
                WHERE s.id_studio NOT IN (
                    SELECT ps.id_studio FROM pinjamstudio ps
                    WHERE ps.status IN ('menunggu', 'approve')
                    AND TIMESTAMP(ps.tgl_mulai, ps.waktu_mulai) < TIMESTAMP('$tgl_selesai', '$waktu_selesai')
                    AND TIMESTAMP(ps.tgl_selesai, ps.waktu_selesai) > TIMESTAMP('$tgl_mulai', '$waktu_mulai')
                )
                LIMIT 3
            ");

            $list_alternatif = "";
            if (mysqli_num_rows($query_alternatif) > 0) {
                $list_alternatif = "<hr><p><b>🎬 Studio Tersedia:</b></p><ul>";
                while ($alt = mysqli_fetch_assoc($query_alternatif)) {
                    $nama_alt = htmlspecialchars($alt['jenis_studio']);
                    $list_alternatif .= "<li>" . $nama_alt . "</li>";
                }
                $list_alternatif .= "</ul>";
            } else {
                $list_alternatif = "<hr><p style='color:#d33;'><b>⚠️ Semua studio tidak tersedia pada jadwal ini.</b></p>";
            }

            $html_content = '<div style="text-align: left;">'.
                        '<p><strong style="color:#d33;font-size:16px;">❌ JADWAL BENTROK!</strong></p>'.
                        '<p>Studio sudah dipinjam pada waktu tersebut.</p><hr>'.
                        '<table style="width:100%;border-collapse:collapse;">'.
                        '<tr><td style="padding:8px;background:#f5f5f5;width:35%;"><b>🎬 Studio</b></td><td style="padding:8px;">'.$info_studio.'</td></tr>'.
                        '<tr><td style="padding:8px;background:#f5f5f5;"><b>📚 Kelas</b></td><td style="padding:8px;">'.$info_kelas.'</td></tr>'.
                        '<tr><td style="padding:8px;background:#f5f5f5;"><b>Status</b></td><td style="padding:8px;color:#d33;"><b>'.$info_status.'</b></td></tr>'.
                        '<tr><td style="padding:8px;background:#f5f5f5;"><b>👤 Peminjam</b></td><td style="padding:8px;">'.$info_peminjam.'</td></tr>'.
                        '<tr><td style="padding:8px;background:#f5f5f5;"><b>👨‍💼 Nama</b></td><td style="padding:8px;">'.$info_nama.'</td></tr>'.
                        '<tr><td style="padding:8px;background:#f5f5f5;"><b>📱 No HP</b></td><td style="padding:8px;">'.$info_no_hp.'</td></tr>'.
                        '<tr><td style="padding:8px;background:#f5f5f5;"><b>🎯 Tujuan</b></td><td style="padding:8px;">'.$info_tujuan.'</td></tr>'.
                        '</table><hr>'.
                        '<p><b>📅 Perbandingan Jadwal:</b></p>'.
                        '<table style="width:100%;border-collapse:collapse;border:1px solid #ddd;">'.
                        '<tr style="background:#e8f5e9;"><td style="padding:8px;border:1px solid #ddd;width:35%;"><b>✅ Jadwal Anda</b></td><td style="padding:8px;border:1px solid #ddd;">'.$info_jadwal_input.'</td></tr>'.
                        '<tr style="background:#ffebee;"><td style="padding:8px;border:1px solid #ddd;"><b>❌ Jadwal Terpakai</b></td><td style="padding:8px;border:1px solid #ddd;color:#d33;"><b>'.$info_jadwal_bentrok.'</b></td></tr>'.
                        '</table>'.
                        $list_alternatif.
                        '<hr><p><b>💡 Solusi:</b></p>'.
                        '<ol style="margin:0;padding-left:20px;">'.
                        '<li>Pilih studio lain</li>'.
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
                    confirmButtonText: 'Ubah Jadwal/Studio',
                    width: '800px'
                });
            ";
        } else {
            // ✅ TIDAK ADA BENTROK - INSERT DATA
            $query = "INSERT INTO pinjamstudio 
                  (id_user, id_studio, id_kelas, nama, no_hp, tgl_mulai, tgl_selesai, waktu_mulai, waktu_selesai, tujuan, deskripsi_peminjaman, status, is_recurring, recurring_days) 
                  VALUES 
                  ('$id_user', '$id_studio', '$id_kelas', '$nama', '$no_hp', '$tgl_mulai', '$tgl_selesai', '$waktu_mulai', '$waktu_selesai', " . 
                  ($tujuan !== '' ? "'" . $tujuan . "'" : "NULL") . ", " . 
                  ($deskripsi_peminjaman !== '' ? "'" . $deskripsi_peminjaman . "'" : "NULL") . ", '$status', '$is_recurring', " . 
                  ($recurring_days !== null ? "'" . mysqli_real_escape_string($conn, $recurring_days) . "'" : "NULL") . ")";

            if (mysqli_query($conn, $query)) {
                $id_pinjam_studio = mysqli_insert_id($conn);
            
                // ===============================
                // ✅ NOTIFIKASI DATABASE + FCM
                // ===============================
            
                // 1. Insert notifikasi ke database untuk admin
                $loop_user = mysqli_query($conn, "SELECT `id` FROM `user` WHERE `level` = 'admin'");
            
                while ($lu = mysqli_fetch_assoc($loop_user)) {
                    mysqli_query($conn, "
                        INSERT INTO `notice4` 
                        SET `id_pinjamstudio` = $id_pinjam_studio, 
                            `id_user` = " . $lu['id'] . ", 
                            `waktu` = " . time() . ", 
                            `status` = 0
                    ");
                }
            
                // 2. Get data peminjam
                $query_peminjam = mysqli_query($conn, "SELECT nama_lengkap FROM user WHERE id = '$id_user' LIMIT 1");
                $data_peminjam = mysqli_fetch_assoc($query_peminjam);
                $nama_peminjam = $data_peminjam['nama_lengkap'] ?? 'User';
            
                // 3. Get data studio dan kelas
                $jenis_studio = $studio_dipilih['jenis_studio'] ?? 'Studio';
                $nama_kelas = $kelas_dipilih['nama_kelas'] ?? 'Kelas';
            
                // 4. Format data notifikasi
                $tgl_indo = date('d/m/Y', strtotime($tgl_mulai));
                $waktu_info = substr($waktu_mulai, 0, 5) . " - " . substr($waktu_selesai, 0, 5);
            
                $hari_rutin = '';
                if ($is_recurring === 'yes' && !empty($recurring_days)) {
                    $days_map = ['', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
                    $day_arr = explode(',', $recurring_days);
                    $day_names = array_map(function($d) use ($days_map) {
                        return $days_map[(int)$d] ?? '';
                    }, $day_arr);
                    $hari_rutin = implode(', ', $day_names);
                }
            
                // 5. Kirim FCM Push Notification
                $fcm_title = "🎬 Peminjaman Studio Baru!";
                $fcm_body = "$nama_peminjam mengajukan peminjaman $jenis_studio ($nama_kelas) pada $tgl_indo ($waktu_info)";
            
                $fcm_data = [
                    'booking_id' => (string)$id_pinjam_studio,
                    'type' => 'new_studio_booking',
                    'studio' => $jenis_studio,
                    'kelas' => $nama_kelas,
                    'tanggal' => $tgl_indo,
                    'waktu' => $waktu_info,
                    'peminjam' => $nama_peminjam,
                    'nama' => $nama,
                    'no_hp' => $no_hp,
                    'tujuan' => $tujuan ?: '-',
                    'is_recurring' => $is_recurring,
                    'recurring_days' => $hari_rutin
                ];
            
                $fcm_result = sendNotificationToAllAdmins($conn, $fcm_title, $fcm_body, $fcm_data);
            
                // 6. Hitung statistik
                $fcm_success_count = 0;
                $fcm_failed_count = 0;
            
                if ($fcm_result && is_array($fcm_result)) {
                    $fcm_success_count = $fcm_result['success'] ?? 0;
                    $fcm_failed_count = $fcm_result['failed'] ?? 0;
                }
            
                // 7. Hitung durasi
                $diff_seconds = strtotime($datetime_selesai) - strtotime($datetime_mulai);
                $diff_hours = floor($diff_seconds / 3600);
                $diff_minutes = floor(($diff_seconds % 3600) / 60);
            
                $durasi_text = "";
                if ($diff_hours > 0) {
                    $durasi_text = $diff_hours . " jam";
                    if ($diff_minutes > 0) {
                        $durasi_text .= " " . $diff_minutes . " menit";
                    }
                } else {
                    $durasi_text = $diff_minutes . " menit";
                }
            
                // ✅ Badge jadwal rutin
                $badge_rutin = '';
                if ($is_recurring === 'yes') {
                    $badge_rutin = '<div style="display:inline-block;background:#8b5cf6;color:white;padding:4px 12px;border-radius:6px;font-size:12px;font-weight:600;margin-top:8px;">'.
                                   '🔄 Jadwal Rutin: ' . htmlspecialchars($hari_rutin) . '</div>';
                }
            
                // ✅ HTML Success dengan FCM Stats
                $html_success = '<div style="text-align: left; line-height: 1.6;">'.
                        
                            // Detail Peminjaman
                            '<div style="padding: 16px; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-left: 4px solid #10b981; border-radius: 12px; margin-bottom: 16px;">'.
                            '<div style="font-size: 16px; font-weight: 700; color: #047857; margin-bottom: 12px;">📋 Detail Peminjaman Studio</div>'.
                            '<table style="width: 100%; font-size: 14px;">'.
                            '<tr><td style="padding: 6px 0; color: #065f46; font-weight: 600; width: 40%;">ID Booking</td><td style="padding: 6px 0;">: <code style="background: #fff; padding: 4px 8px; border-radius: 6px; font-weight: 700; color: #047857;">#'.$id_pinjam_studio.'</code></td></tr>'.
                            '<tr><td style="padding: 6px 0; color: #065f46; font-weight: 600;">🎬 Studio</td><td style="padding: 6px 0;">: <strong>'.htmlspecialchars($jenis_studio).'</strong></td></tr>'.
                            '<tr><td style="padding: 6px 0; color: #065f46; font-weight: 600;">📚 Kelas</td><td style="padding: 6px 0;">: <strong>'.htmlspecialchars($nama_kelas).'</strong></td></tr>'.
                            '<tr><td style="padding: 6px 0; color: #065f46; font-weight: 600;">👤 Peminjam</td><td style="padding: 6px 0;">: '.htmlspecialchars($nama_peminjam).'</td></tr>'.
                            '<tr><td style="padding: 6px 0; color: #065f46; font-weight: 600;">👨‍💼 Nama</td><td style="padding: 6px 0;">: '.htmlspecialchars($nama).'</td></tr>'.
                            '<tr><td style="padding: 6px 0; color: #065f46; font-weight: 600;">📱 No HP</td><td style="padding: 6px 0;">: '.htmlspecialchars($no_hp).'</td></tr>'.
                            '<tr><td style="padding: 6px 0; color: #065f46; font-weight: 600;">📅 Waktu</td><td style="padding: 6px 0;">: '.$format_mulai.' - '.$format_selesai.'</td></tr>'.
                            '<tr><td style="padding: 6px 0; color: #065f46; font-weight: 600;">⏱️ Durasi</td><td style="padding: 6px 0;">: '.$durasi_text.'</td></tr>'.
                            ($tujuan ? '<tr><td style="padding: 6px 0; color: #065f46; font-weight: 600;">🎯 Tujuan</td><td style="padding: 6px 0;">: '.htmlspecialchars($tujuan).'</td></tr>' : '').
                            '</table>'.
                            $badge_rutin.
                            '</div>'.
                                   
                $html_success = addslashes($html_success);
            
                $alert_script = "
                    // ✅ Log ke Console
                    console.group('🎬 PEMINJAMAN STUDIO BERHASIL');
                    console.log('%c✅ Booking ID: #$id_pinjam_studio', 'color: #10b981; font-weight: bold; font-size: 16px');
                    console.log('🎬 Studio:', ".json_encode($jenis_studio).");
                    console.log('📚 Kelas:', ".json_encode($nama_kelas).");
                    console.log('👤 Peminjam:', ".json_encode($nama_peminjam).");
                    console.log('👨‍💼 Nama:', ".json_encode($nama).");
                    console.log('📱 No HP:', ".json_encode($no_hp).");
                    console.log('📅 Tanggal:', ".json_encode($tgl_indo).");
                    console.log('⏰ Waktu:', ".json_encode($waktu_info).");
                    console.log('🔄 Jadwal Rutin:', ".json_encode($is_recurring).");
                    ".($is_recurring === 'yes' ? "console.log('📆 Hari:', ".json_encode($hari_rutin).");" : "")."
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
                        width: '700px',
                        allowOutsideClick: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = '?view=datapinjamstudio';
                        } else if (result.dismiss === Swal.DismissReason.cancel) {
                            window.location.href = '?view=createpinjamstudio';
                        }
                    });
                ";
            
                // ✅ RESET FORM SETELAH BERHASIL
                $form_data = [
                    'id_studio' => '',
                    'id_kelas' => '',
                    'nama' => '',
                    'no_hp' => '',
                    'tgl_mulai' => '',
                    'tgl_selesai' => '',
                    'waktu_mulai' => '',
                    'waktu_selesai' => '',
                    'tujuan' => '',
                    'deskripsi_peminjaman' => '',
                    'is_recurring' => 'no',
                    'recurring_days' => []
                ];
            
            } else {
                $error_message = htmlspecialchars(mysqli_error($conn));
                $alert_script = "
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        html: '<p>Error Database:</p><code style=\"background:#f5f5f5;padding:10px;display:block;border-radius:6px;\">".$error_message."</code>',
                        confirmButtonColor: '#d33'
                    });
                ";
            }
        } // End else tidak ada bentrok
    } // End else validasi sukses
}

$query_studio = mysqli_query($conn, "SELECT * FROM studio ORDER BY jenis_studio ASC");
$query_kelas = mysqli_query($conn, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
?>

<!-- SweetAlert2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
.page-pinjamstudio {
    --primary: #6366f1;
    --primary-light: #eef2ff;
    --success: #10b981;
    --success-light: #d1fae5;
    --warning: #f59e0b;
    --warning-light: #fef3c7;
    --danger: #ef4444;
    --danger-light: #fee2e2;
    --muted: #6b7280;
    --muted-light: #f3f4f6;
    --txt: #111827;
    --txt-secondary: #4b5563;
    --border: #e5e7eb;
    --card: #ffffff;
    --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
    --shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    --radius: 12px;
    --radius-sm: 8px;
}

.page-pinjamstudio .card {
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}

.page-pinjamstudio .card-header {
    background: linear-gradient(135deg, var(--primary) 0%, #8b5cf6 100%);
    padding: 20px 24px;
    border-bottom: none;
}

.page-pinjamstudio .card-header .card-title {
    color: white;
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
    letter-spacing: -0.01em;
}

.page-pinjamstudio .card-body {
    padding: 24px;
}

/* Form Grid */
.page-pinjamstudio .form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px 16px;
}

.page-pinjamstudio .form-grid .full-width {
    grid-column: 1 / -1;
}

.page-pinjamstudio .form-group {
    margin-bottom: 0;
}

.page-pinjamstudio .form-group label {
    display: block;
    font-weight: 600;
    color: var(--txt-secondary);
    margin-bottom: 8px;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.page-pinjamstudio .form-group label .text-danger {
    color: var(--danger);
    margin-left: 4px;
}

.page-pinjamstudio .form-control {
    width: 100%;
    padding: 10px 14px;
    border: 2px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: 0.9rem;
    color: var(--txt);
    background: var(--card);
    transition: all 0.2s ease;
}

.page-pinjamstudio .form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px var(--primary-light);
}

.page-pinjamstudio .form-control::placeholder {
    color: #9ca3af;
}

.page-pinjamstudio .form-hint {
    display: block;
    margin-top: 6px;
    color: var(--muted);
    font-size: 0.75rem;
    line-height: 1.4;
}

.page-pinjamstudio textarea.form-control {
    resize: vertical;
    min-height: 80px;
    line-height: 1.5;
}

/* Section Divider */
.page-pinjamstudio .section-divider {
    margin: 24px 0 20px;
    border: 0;
    border-top: 2px solid var(--border);
}

.page-pinjamstudio .section-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--txt);
    margin: 0 0 16px 0;
    display: flex;
    align-items: center;
    gap: 8px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.page-pinjamstudio .section-title i {
    color: var(--primary);
    font-size: 1.1rem;
}

/* Checkbox Group */
.page-pinjamstudio .checkbox-group {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    gap: 10px 14px;
    padding: 16px;
    background: var(--muted-light);
    border: 2px dashed var(--border);
    border-radius: var(--radius-sm);
}

.page-pinjamstudio .checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    user-select: none;
    font-weight: 500;
    color: var(--txt);
    font-size: 0.85rem;
    transition: color 0.2s;
}

.page-pinjamstudio .checkbox-label:hover {
    color: var(--primary);
}

.page-pinjamstudio .checkbox-label input[type="checkbox"] {
    width: 17px;
    height: 17px;
    cursor: pointer;
    accent-color: var(--primary);
}

/* Buttons */
.page-pinjamstudio .form-actions {
    display: flex;
    gap: 12px;
    margin-top: 28px;
    padding-top: 20px;
    border-top: 2px solid var(--border);
}

.page-pinjamstudio .btn-xs {
    padding: 10px 20px;
    font-size: 0.9rem;
    font-weight: 600;
    border-radius: var(--radius-sm);
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    text-decoration: none;
    cursor: pointer;
    white-space: nowrap;
}

.page-pinjamstudio .btn-success {
    background: var(--success);
    color: white;
}

.page-pinjamstudio .btn-success:hover {
    background: #059669;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3);
}

.page-pinjamstudio .btn-secondary {
    background: white;
    color: var(--muted);
    border: 2px solid var(--border);
}

.page-pinjamstudio .btn-secondary:hover {
    background: var(--muted-light);
    border-color: var(--muted);
}

/* Responsive */
@media (max-width: 768px) {
    .page-pinjamstudio .card-header {
        padding: 16px 20px;
    }

    .page-pinjamstudio .card-body {
        padding: 20px 16px;
    }

    .page-pinjamstudio .form-grid {
        grid-template-columns: 1fr;
        gap: 18px;
    }

    .page-pinjamstudio .checkbox-group {
        grid-template-columns: repeat(2, 1fr);
    }

    .page-pinjamstudio .form-actions {
        flex-direction: column;
    }

    .page-pinjamstudio .btn-xs {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .page-pinjamstudio .checkbox-group {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="page-pinjamstudio">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">🎬 Create Peminjaman Studio</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        
                        <div class="form-grid">
                            
                            <div class="form-group">
                                <label>
                                    Pilih Studio <span class="text-danger">*</span>
                                </label>
                                <select name="id_studio" class="form-control" required>
                                    <option value="">-- Pilih Studio --</option>
                                    <?php
                                    mysqli_data_seek($query_studio, 0);
                                    while($studio = mysqli_fetch_array($query_studio)) {
                                        $selected = ($form_data['id_studio'] == $studio['id_studio']) ? 'selected' : '';
                                        echo "<option value='{$studio['id_studio']}' $selected>{$studio['jenis_studio']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>
                                    Pilih Kelas <span class="text-danger">*</span>
                                </label>
                                <select name="id_kelas" class="form-control" required>
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php
                                    mysqli_data_seek($query_kelas, 0);
                                    while($kelas = mysqli_fetch_array($query_kelas)) {
                                        $selected = ($form_data['id_kelas'] == $kelas['id_kelas']) ? 'selected' : '';
                                        echo "<option value='{$kelas['id_kelas']}' $selected>{$kelas['nama_kelas']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>
                                    Nama Peminjam <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       name="nama" 
                                       class="form-control" 
                                       placeholder="Masukkan nama peminjam" 
                                       value="<?php echo htmlspecialchars($form_data['nama'], ENT_QUOTES, 'UTF-8'); ?>" 
                                       required>
                            </div>

                            <div class="form-group">
                                <label>
                                    No. HP / WhatsApp <span class="text-danger">*</span>
                                </label>
                                <input type="tel" 
                                       name="no_hp" 
                                       class="form-control" 
                                       placeholder="08xxxxxxxxxx" 
                                       value="<?php echo htmlspecialchars($form_data['no_hp'], ENT_QUOTES, 'UTF-8'); ?>" 
                                       pattern="[0-9]{10,13}" 
                                       required>
                                <small class="form-hint">
                                    Contoh: 081234567890
                                </small>
                            </div>

                            <div class="form-group">
                                <label>
                                    Tanggal Mulai <span class="text-danger">*</span>
                                </label>
                                <input type="date" 
                                       name="tgl_mulai" 
                                       id="tgl_mulai" 
                                       class="form-control"
                                       value="<?php echo htmlspecialchars($form_data['tgl_mulai'], ENT_QUOTES, 'UTF-8'); ?>"
                                       min="<?php echo date('Y-m-d'); ?>" 
                                       required>
                            </div>

                            <div class="form-group">
                                <label>
                                    Tanggal Selesai <span class="text-danger">*</span>
                                </label>
                                <input type="date" 
                                       name="tgl_selesai" 
                                       id="tgl_selesai" 
                                       class="form-control"
                                       value="<?php echo htmlspecialchars($form_data['tgl_selesai'], ENT_QUOTES, 'UTF-8'); ?>"
                                       min="<?php echo date('Y-m-d'); ?>" 
                                       required>
                                <small class="form-hint">
                                    Jika hanya 1 hari, pilih tanggal yang sama
                                </small>
                            </div>

                            <div class="form-group">
                                <label>
                                    Waktu Mulai <span class="text-danger">*</span>
                                </label>
                                <input type="time" 
                                       name="waktu_mulai" 
                                       id="waktu_mulai" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($form_data['waktu_mulai'], ENT_QUOTES, 'UTF-8'); ?>" 
                                       required>
                            </div>

                            <div class="form-group">
                                <label>
                                    Waktu Selesai <span class="text-danger">*</span>
                                </label>
                                <input type="time" 
                                       name="waktu_selesai" 
                                       id="waktu_selesai" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($form_data['waktu_selesai'], ENT_QUOTES, 'UTF-8'); ?>" 
                                       required>
                            </div>

                            <div class="form-group full-width">
                                <label>
                                    Tujuan Peminjaman
                                </label>
                                <textarea name="tujuan" 
                                          class="form-control" 
                                          placeholder="Jelaskan tujuan peminjaman studio..." 
                                          rows="2"><?php echo htmlspecialchars($form_data['tujuan'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                                <small class="form-hint">
                                    Contoh: Shooting video untuk tugas akhir
                                </small>
                            </div>

                            <div class="form-group full-width">
                                <label>
                                    Keterangan Tambahan
                                </label>
                                <textarea name="deskripsi_peminjaman" 
                                          class="form-control" 
                                          placeholder="Keterangan tambahan (opsional)..." 
                                          rows="2"><?php echo htmlspecialchars($form_data['deskripsi_peminjaman'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                                <small class="form-hint">
                                    Contoh: Membutuhkan peralatan lighting tambahan
                                </small>
                            </div>

                        </div>

                        <hr class="section-divider">
                        
                        <div class="section-title">
                            <i class="fa fa-repeat"></i>
                            Jadwal Rutin (Opsional)
                        </div>

                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label>Apakah Jadwal Rutin?</label>
                                <select name="is_recurring" class="form-control" id="selectRutin" onchange="toggleHariRutin()">
                                    <option value="no" <?php echo ($form_data['is_recurring'] === 'no') ? 'selected' : ''; ?>>Tidak (Sekali Saja)</option>
                                    <option value="yes" <?php echo ($form_data['is_recurring'] === 'yes') ? 'selected' : ''; ?>>Ya, Jadwal Rutin</option>
                                </select>
                                <small class="form-hint">
                                    Pilih "Ya" jika peminjaman berlangsung berulang setiap minggu
                                </small>
                            </div>

                            <div class="form-group full-width" id="hariRutinWrapper" style="display: <?php echo ($form_data['is_recurring'] === 'yes') ? 'block' : 'none'; ?>;">
                                <label>Pilih Hari Rutin <span class="text-danger">*</span></label>
                                <div class="checkbox-group">
                                    <?php
                                    $hari_labels = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
                                    foreach ($hari_labels as $val => $label) {
                                        $checked = in_array($val, $form_data['recurring_days']) ? 'checked' : '';
                                        echo '<label class="checkbox-label">';
                                        echo '<input type="checkbox" name="recurring_days[]" value="'.$val.'" '.$checked.'>';
                                        echo $label;
                                        echo '</label>';
                                    }
                                    ?>
                                </div>
                                <small class="form-hint">
                                    Pilih hari-hari yang akan menjadi jadwal rutin setiap minggu
                                </small>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="simpan" class="btn-xs btn-success">
                                <i class="fa fa-save"></i>
                                Simpan Peminjaman
                            </button>
                            <a href="?view=datapinjamstudio" class="btn-xs btn-secondary">
                                <i class="fa fa-arrow-left"></i>
                                Kembali
                            </a>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($alert_script)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php echo $alert_script; ?>
    });
</script>
<?php endif; ?>

<script>
// Auto-fill tanggal dari parameter URL
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const dateParam = urlParams.get('date');
    
    if (dateParam) {
        const tglMulaiInput = document.getElementById('tgl_mulai');
        if (tglMulaiInput && !tglMulaiInput.value) {
            tglMulaiInput.value = dateParam;
        }
        
        const tglSelesaiInput = document.getElementById('tgl_selesai');
        if (tglSelesaiInput && !tglSelesaiInput.value) {
            tglSelesaiInput.value = dateParam;
        }
    }
    
    // ✅ AUTO-SHOW HARI RUTIN JIKA SUDAH DIPILIH
    toggleHariRutin();
});

function toggleHariRutin() {
    const select = document.getElementById('selectRutin');
    const wrapper = document.getElementById('hariRutinWrapper');
    
    if (select.value === 'yes') {
        wrapper.style.display = 'block';
    } else {
        wrapper.style.display = 'none';
    }
}

// Validasi waktu dan tanggal
const waktuMulai = document.getElementById('waktu_mulai');
const waktuSelesai = document.getElementById('waktu_selesai');
const tglMulai = document.getElementById('tgl_mulai');
const tglSelesai = document.getElementById('tgl_selesai');

waktuSelesai.addEventListener('change', function () {
    if (waktuMulai.value && waktuSelesai.value && tglMulai.value && tglSelesai.value && tglMulai.value === tglSelesai.value) {
        if (waktuSelesai.value <= waktuMulai.value) {
            Swal.fire({
                icon: 'warning',
                title: 'Waktu Tidak Valid!',
                text: 'Waktu selesai harus lebih besar dari waktu mulai untuk tanggal yang sama!',
                confirmButtonColor: '#f59e0b'
            });
            waktuSelesai.value = '';
        }
    }
});

tglSelesai.addEventListener('change', function () {
    if (tglMulai.value && tglSelesai.value) {
        if (tglSelesai.value < tglMulai.value) {
            Swal.fire({
                icon: 'warning',
                title: 'Tanggal Tidak Valid!',
                text: 'Tanggal selesai tidak boleh lebih kecil dari tanggal mulai!',
                confirmButtonColor: '#f59e0b'
            });
            tglSelesai.value = '';
        }
    }
});
</script>