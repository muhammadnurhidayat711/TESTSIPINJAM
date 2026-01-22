<?php
// ========= Pastikan koneksi $conn sudah ada =========
if (!isset($conn)) {
  die("Database connection not found!");
}

// ========= TANGKAP PARAMETER DATE DARI URL =========
$prefillDate = isset($_GET['date']) ? $_GET['date'] : '';

// ================== Helper Function untuk format tanggal & waktu ==================
function format_tanggal($date)
{
  $bulan = array(
    1 => 'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember'
  );
  $pecah = explode('-', $date);
  if (count($pecah) !== 3)
    return htmlspecialchars($date, ENT_QUOTES, 'UTF-8');
  return (int) $pecah[2] . ' ' . $bulan[(int) $pecah[1]] . ' ' . $pecah[0];
}

// ================== PROSES FORM SUBMIT ==================
if (isset($_POST['simpan'])) {
  $errors = [];

  if (empty($_POST['id_studio'])) {
    $errors[] = "Pilih studio terlebih dahulu";
  }

  if (empty($_POST['id_kelas'])) {
    $errors[] = "Pilih kelas terlebih dahulu";
  }

  if (empty($_POST['nama'])) {
    $errors[] = "Nama harus diisi";
  }

  if (empty($_POST['tgl_mulai'])) {
    $errors[] = "Tanggal mulai harus diisi";
  }

  if (empty($_POST['tgl_selesai'])) {
    $errors[] = "Tanggal selesai harus diisi";
  }

  if (empty($_POST['waktu_mulai'])) {
    $errors[] = "Waktu mulai harus diisi";
  }

  if (empty($_POST['waktu_selesai'])) {
    $errors[] = "Waktu selesai harus diisi";
  }

  if (!empty($_POST['tgl_mulai']) && !empty($_POST['tgl_selesai'])) {
    if ($_POST['tgl_selesai'] < $_POST['tgl_mulai']) {
      $errors[] = "Tanggal selesai tidak boleh lebih kecil dari tanggal mulai";
    }
  }

  if (
    !empty($_POST['waktu_mulai']) &&
    !empty($_POST['waktu_selesai']) &&
    !empty($_POST['tgl_mulai']) &&
    !empty($_POST['tgl_selesai']) &&
    $_POST['tgl_mulai'] === $_POST['tgl_selesai']
  ) {
    if ($_POST['waktu_selesai'] <= $_POST['waktu_mulai']) {
      $errors[] = "Waktu selesai harus lebih besar dari waktu mulai";
    }
  }

  if (empty($errors)) {
    $id_studio = mysqli_real_escape_string($conn, $_POST['id_studio']);
    $tgl_mulai = mysqli_real_escape_string($conn, $_POST['tgl_mulai']);
    $waktu_mulai = mysqli_real_escape_string($conn, $_POST['waktu_mulai']);
    $waktu_selesai = mysqli_real_escape_string($conn, $_POST['waktu_selesai']);

    $conflict_query = "
            SELECT 
                p.waktu_mulai, 
                p.waktu_selesai, 
                p.nama,
                p.status,
                s.jenis_studio
            FROM pinjamstudio p
            INNER JOIN studio s ON s.id_studio = p.id_studio
            WHERE p.id_studio = '$id_studio'
              AND p.tgl_mulai = '$tgl_mulai'
              AND (
                    (p.waktu_mulai < '$waktu_selesai' AND p.waktu_selesai > '$waktu_mulai')
                  )
              AND p.status IN ('menunggu','approve')
            LIMIT 1
        ";

    $conflict_result = mysqli_query($conn, $conflict_query);

    if (mysqli_num_rows($conflict_result) > 0) {
      $conflict_data = mysqli_fetch_assoc($conflict_result);
      $tanggal_formatted = format_tanggal($tgl_mulai);
      $studio_name = $conflict_data['jenis_studio'];
      $peminjam = $conflict_data['nama'];
      $jam_bentrok = substr($conflict_data['waktu_mulai'], 0, 5) . ' - ' . substr($conflict_data['waktu_selesai'], 0, 5);
      $status_booking = ucfirst($conflict_data['status']);

      echo "<script>
                alert('❌ JADWAL BENTROK!\\n\\n' +
                      '━━━━━━━━━━━━━━━━━━━━━━━━━━\\n' +
                      'Studio: $studio_name\\n' +
                      'Tanggal: $tanggal_formatted\\n' +
                      'Jam Bentrok: $jam_bentrok\\n' +
                      'Status: $status_booking\\n' +
                      'Dipinjam oleh: $peminjam\\n' +
                      '━━━━━━━━━━━━━━━━━━━━━━━━━━\\n\\n' +
                      '⚠️ Silakan pilih jam lain atau tanggal lain!');
            </script>";
      $errors[] = "Jadwal bentrok";
    }
  }

  if (!empty($errors) && !isset($conflict_data)) {
    echo "<script>alert('" . implode("\\n", $errors) . "');</script>";
  } else if (empty($errors)) {
    $id_user = mysqli_real_escape_string($conn, $_SESSION['id']);
    $id_studio = mysqli_real_escape_string($conn, $_POST['id_studio']);
    $id_kelas = mysqli_real_escape_string($conn, $_POST['id_kelas']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $tgl_mulai = mysqli_real_escape_string($conn, $_POST['tgl_mulai']);
    $tgl_selesai = mysqli_real_escape_string($conn, $_POST['tgl_selesai']);
    $waktu_mulai = mysqli_real_escape_string($conn, $_POST['waktu_mulai']);
    $waktu_selesai = mysqli_real_escape_string($conn, $_POST['waktu_selesai']);
    $deskripsi_peminjaman = isset($_POST['deskripsi_peminjaman']) ? mysqli_real_escape_string($conn, $_POST['deskripsi_peminjaman']) : '';
    $tujuan = isset($_POST['tujuan']) ? mysqli_real_escape_string($conn, $_POST['tujuan']) : '';
    $status = 'menunggu';

    $is_recurring = isset($_POST['is_recurring']) && $_POST['is_recurring'] === 'yes' ? 'yes' : 'no';
    $recurring_days = '';
    if (!empty($_POST['recurring_days']) && is_array($_POST['recurring_days'])) {
      $sel_days = array_map('intval', $_POST['recurring_days']);
      $sel_days = array_unique($sel_days);
      sort($sel_days);
      $recurring_days = implode(',', $sel_days);
    }

    $query = "INSERT INTO pinjamstudio (
            id_user,
            id_studio,
            id_kelas,
            nama,
            no_hp,
            tgl_mulai,
            tgl_selesai,
            waktu_mulai,
            waktu_selesai,
            is_recurring,
            recurring_days,
            tujuan,
            deskripsi_peminjaman,
            status
        ) VALUES (
            '$id_user',
            '$id_studio',
            '$id_kelas',
            '$nama',
            '$no_hp',
            '$tgl_mulai',
            '$tgl_selesai',
            '$waktu_mulai',
            '$waktu_selesai',
            '$is_recurring',
            '$recurring_days',
            '$tujuan',
            '$deskripsi_peminjaman',
            '$status'
        )";

    if (mysqli_query($conn, $query)) {
      echo "<script>
                alert('✅ PEMINJAMAN BERHASIL!\\n\\nPeminjaman studio berhasil diajukan.\\nSilakan tunggu konfirmasi dari admin.');
                window.location='?view=datapinjamstudio';
            </script>";
      exit;
    } else {
      $error_msg = mysqli_error($conn);
      echo "<script>
                alert('❌ Error: " . addslashes($error_msg) . "');
            </script>";
      error_log("Insert pinjamstudio error: " . $error_msg);
    }
  }
}

$query_studio = mysqli_query($conn, "SELECT * FROM studio ORDER BY jenis_studio ASC");
$query_kelas = mysqli_query($conn, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
?>

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
    --radius: 8px;
    --radius-sm: 6px;
    font-size: 13px;
}

.page-pinjamstudio .card {
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    max-width: 900px;
    margin: 0 auto;
}

.page-pinjamstudio .card-header {
    background: linear-gradient(135deg, var(--primary) 0%, #8b5cf6 100%);
    padding: 14px 18px;
    border-bottom: none;
}

.page-pinjamstudio .card-header .card-title {
    color: white;
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0;
    letter-spacing: -0.01em;
}

.page-pinjamstudio .card-header .btn {
    background: white;
    color: var(--primary);
    font-weight: 600;
    border: none;
    padding: 7px 14px;
    border-radius: var(--radius-sm);
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    font-size: 0.8rem;
}

.page-pinjamstudio .card-header .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    background: #f8f9fa;
}

.page-pinjamstudio .card-body {
    padding: 18px;
}

/* Form Grid */
.page-pinjamstudio .form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px 12px;
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
    margin-bottom: 6px;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.page-pinjamstudio .form-group label .text-danger {
    color: var(--danger);
    margin-left: 4px;
}

.page-pinjamstudio .form-control {
    width: 100%;
    padding: 7px 10px;
    border: 2px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: 0.85rem;
    color: var(--txt);
    background: var(--card);
    transition: all 0.2s ease;
}

.page-pinjamstudio .form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 2px var(--primary-light);
}

.page-pinjamstudio .form-control::placeholder {
    color: #9ca3af;
    font-size: 0.8rem;
}

.page-pinjamstudio .form-hint {
    display: block;
    margin-top: 4px;
    color: var(--muted);
    font-size: 0.68rem;
    line-height: 1.3;
}

.page-pinjamstudio textarea.form-control {
    resize: vertical;
    min-height: 65px;
    line-height: 1.4;
}

/* Section Divider */
.page-pinjamstudio .section-divider {
    margin: 18px 0 14px;
    border: 0;
    border-top: 2px solid var(--border);
}

.page-pinjamstudio .section-title {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--txt);
    margin: 0 0 12px 0;
    display: flex;
    align-items: center;
    gap: 6px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.page-pinjamstudio .section-title i {
    color: var(--primary);
    font-size: 0.95rem;
}

/* Checkbox Group */
.page-pinjamstudio .checkbox-group {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
    gap: 8px 10px;
    padding: 12px;
    background: var(--muted-light);
    border: 2px dashed var(--border);
    border-radius: var(--radius-sm);
}

.page-pinjamstudio .checkbox-label {
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    user-select: none;
    font-weight: 500;
    color: var(--txt);
    font-size: 0.8rem;
    transition: color 0.2s;
}

.page-pinjamstudio .checkbox-label:hover {
    color: var(--primary);
}

.page-pinjamstudio .checkbox-label input[type="checkbox"] {
    width: 15px;
    height: 15px;
    cursor: pointer;
    accent-color: var(--primary);
}

/* Buttons */
.page-pinjamstudio .form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
    padding-top: 16px;
    border-top: 2px solid var(--border);
}

.page-pinjamstudio .btn-xs {
    padding: 8px 16px;
    font-size: 0.82rem;
    font-weight: 600;
    border-radius: var(--radius-sm);
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s ease;
    text-decoration: none;
    cursor: pointer;
    white-space: nowrap;
}

.page-pinjamstudio .btn-xs i {
    font-size: 0.85rem;
}

.page-pinjamstudio .btn-success {
    background: var(--success);
    color: white;
}

.page-pinjamstudio .btn-success:hover {
    background: #059669;
    transform: translateY(-1px);
    box-shadow: 0 3px 6px rgba(16, 185, 129, 0.3);
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

.page-pinjamstudio .alert {
    padding: 10px 14px;
    border-radius: var(--radius-sm);
    margin-bottom: 16px;
    border-left: 3px solid;
    background: var(--primary-light);
    border-left-color: var(--primary);
    color: #1e40af;
    font-size: 0.8rem;
    line-height: 1.4;
}

.page-pinjamstudio .alert i {
    font-size: 0.85rem;
}

.page-pinjamstudio .required {
    color: var(--danger);
    font-weight: 700;
}

/* Responsive */
@media (max-width: 768px) {
    .page-pinjamstudio .card-header {
        padding: 12px 16px;
    }

    .page-pinjamstudio .card-body {
        padding: 16px 14px;
    }

    .page-pinjamstudio .form-grid {
        grid-template-columns: 1fr;
        gap: 14px;
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
                <div class="card-body">
                    <div class="alert">
                        <i class="fa fa-info-circle"></i>
                        Lengkapi formulir di bawah ini untuk mengajukan peminjaman studio.
                        Semua field bertanda <span class="required">*</span> wajib diisi.
                    </div>

                    <form method="POST" action="">
                        
                        <div class="form-grid">
                            
                            <div class="form-group">
                                <label>
                                    Pilih Studio <span class="required">*</span>
                                </label>
                                <select name="id_studio" class="form-control" required>
                                    <option value="">-- Pilih Studio --</option>
                                    <?php while ($studio = mysqli_fetch_array($query_studio)) { ?>
                                        <option value="<?php echo $studio['id_studio']; ?>">
                                            <?php echo htmlspecialchars($studio['jenis_studio']); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>
                                    Pilih Kelas <span class="required">*</span>
                                </label>
                                <select name="id_kelas" class="form-control" required>
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php 
                                    mysqli_data_seek($query_kelas, 0);
                                    while ($kelas = mysqli_fetch_array($query_kelas)) { ?>
                                        <option value="<?php echo $kelas['id_kelas']; ?>">
                                            <?php echo htmlspecialchars($kelas['nama_kelas']); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>
                                    Nama Peminjam <span class="required">*</span>
                                </label>
                                <input type="text" name="nama" class="form-control" placeholder="Masukkan nama peminjam"
                                    value="<?php echo isset($_SESSION['nama_lengkap']) ? htmlspecialchars($_SESSION['nama_lengkap']) : ''; ?>"
                                    required>
                            </div>

                            <div class="form-group">
                                <label>
                                    No. HP / WhatsApp <span class="required">*</span>
                                </label>
                                <input type="tel" name="no_hp" class="form-control" placeholder="08xxxxxxxxxx"
                                    pattern="[0-9]{10,13}" required>
                                <small class="form-hint">
                                    Contoh: 081234567890
                                </small>
                            </div>

                            <div class="form-group">
                                <label>
                                    Tanggal Mulai <span class="required">*</span>
                                </label>
                                <input type="date" 
                                       name="tgl_mulai" 
                                       id="tgl_mulai" 
                                       class="form-control"
                                       value="<?php echo htmlspecialchars($prefillDate, ENT_QUOTES, 'UTF-8'); ?>"
                                       min="<?php echo date('Y-m-d'); ?>" 
                                       required>
                            </div>

                            <div class="form-group">
                                <label>
                                    Tanggal Selesai <span class="required">*</span>
                                </label>
                                <input type="date" 
                                       name="tgl_selesai" 
                                       id="tgl_selesai" 
                                       class="form-control"
                                       min="<?php echo date('Y-m-d'); ?>" 
                                       required>
                                <small class="form-hint">
                                    Jika hanya 1 hari, pilih tanggal yang sama
                                </small>
                            </div>

                            <div class="form-group">
                                <label>
                                    Waktu Mulai <span class="required">*</span>
                                </label>
                                <input type="time" name="waktu_mulai" id="waktu_mulai" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label>
                                    Waktu Selesai <span class="required">*</span>
                                </label>
                                <input type="time" name="waktu_selesai" id="waktu_selesai" class="form-control" required>
                            </div>

                            <div class="form-group full-width">
                                <label>
                                    Tujuan Peminjaman
                                </label>
                                <textarea name="tujuan" class="form-control" placeholder="Jelaskan tujuan peminjaman studio..." rows="2"></textarea>
                                <small class="form-hint">
                                    Contoh: Shooting video untuk tugas akhir
                                </small>
                            </div>

                            <div class="form-group full-width">
                                <label>
                                    Keterangan Tambahan
                                </label>
                                <textarea name="deskripsi_peminjaman" class="form-control" placeholder="Keterangan tambahan (opsional)..." rows="2"></textarea>
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
                                    <option value="no">Tidak (Sekali Saja)</option>
                                    <option value="yes">Ya, Jadwal Rutin</option>
                                </select>
                                <small class="form-hint">
                                    Pilih "Ya" jika peminjaman berlangsung berulang setiap minggu
                                </small>
                            </div>

                            <div class="form-group full-width" id="hariRutinWrapper" style="display: none;">
                                <label>Pilih Hari Rutin <span class="required">*</span></label>
                                <div class="checkbox-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="recurring_days[]" value="1">
                                        Senin
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="recurring_days[]" value="2">
                                        Selasa
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="recurring_days[]" value="3">
                                        Rabu
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="recurring_days[]" value="4">
                                        Kamis
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="recurring_days[]" value="5">
                                        Jumat
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="recurring_days[]" value="6">
                                        Sabtu
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="recurring_days[]" value="7">
                                        Minggu
                                    </label>
                                </div>
                                <small class="form-hint">
                                    Pilih hari-hari yang akan menjadi jadwal rutin setiap minggu
                                </small>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="simpan" class="btn-xs btn-success">
                                <i class="fa fa-check"></i>
                                Ajukan Peminjaman
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
        
        // Auto-fill tgl_selesai dengan tanggal yang sama
        const tglSelesaiInput = document.getElementById('tgl_selesai');
        if (tglSelesaiInput && !tglSelesaiInput.value) {
            tglSelesaiInput.value = dateParam;
        }
    }
});

function toggleHariRutin() {
    const select = document.getElementById('selectRutin');
    const wrapper = document.getElementById('hariRutinWrapper');
    
    if (select.value === 'yes') {
        wrapper.style.display = 'block';
    } else {
        wrapper.style.display = 'none';
        const checkboxes = wrapper.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(cb => cb.checked = false);
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
            alert('⚠️ Waktu selesai harus lebih besar dari waktu mulai untuk tanggal yang sama!');
            waktuSelesai.value = '';
        }
    }
});

tglSelesai.addEventListener('change', function () {
    if (tglMulai.value && tglSelesai.value) {
        if (tglSelesai.value < tglMulai.value) {
            alert('⚠️ Tanggal selesai tidak boleh lebih kecil dari tanggal mulai!');
            tglSelesai.value = '';
        }
    }
});
</script>
