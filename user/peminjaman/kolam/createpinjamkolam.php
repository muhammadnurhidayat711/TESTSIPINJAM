<?php
// ========= Pastikan koneksi $conn (mysqli) & session sudah tersedia =========

// ========= TANGKAP PARAMETER DATE DARI URL =========
$prefillDate = isset($_GET['date']) ? $_GET['date'] : '';

// ================== PROSES INSERT DATA BARU ==================
if (isset($_POST['simpan'])) {
    $id_user       = $_SESSION['id'] ?? null;
    $id_kolam      = mysqli_real_escape_string($conn, $_POST['id_kolam'] ?? '');
    $id_kelas      = mysqli_real_escape_string($conn, $_POST['id_kelas'] ?? '');
    $tgl_mulai     = mysqli_real_escape_string($conn, $_POST['tgl_mulai'] ?? '');
    $tgl_selesai   = mysqli_real_escape_string($conn, $_POST['tgl_selesai'] ?? '');
    $waktu_mulai   = mysqli_real_escape_string($conn, $_POST['waktu_mulai'] ?? '');
    $waktu_selesai = mysqli_real_escape_string($conn, $_POST['waktu_selesai'] ?? '');
    $nama          = mysqli_real_escape_string($conn, $_POST['nama'] ?? '');
    $no_hp         = mysqli_real_escape_string($conn, $_POST['no_hp'] ?? '');
    $tujuan        = mysqli_real_escape_string($conn, $_POST['tujuan'] ?? '');
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

    $errors = [];

    if (empty($id_user)) {
        $errors[] = 'Session pengguna tidak ditemukan. Silakan login ulang.';
    }
    if (empty($id_kolam)) {
        $errors[] = 'Kolam harus dipilih.';
    }
    if (empty($id_kelas)) {
        $errors[] = 'Kelas harus dipilih.';
    }
    if (empty($tgl_mulai)) {
        $errors[] = 'Tanggal mulai harus diisi.';
    }
    if (!empty($tgl_mulai) && !empty($tgl_selesai) && $tgl_selesai < $tgl_mulai) {
        $errors[] = 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.';
    }
    if (empty($waktu_mulai) || empty($waktu_selesai)) {
        $errors[] = 'Waktu mulai dan selesai harus diisi.';
    }
    if (!empty($waktu_mulai) && !empty($waktu_selesai) && $waktu_selesai <= $waktu_mulai) {
        $errors[] = 'Waktu selesai harus lebih besar dari waktu mulai.';
    }
    if (empty($nama)) {
        $errors[] = 'Nama penanggung jawab harus diisi.';
    }
    if (empty($no_hp)) {
        $errors[] = 'Nomor HP harus diisi.';
    }
    if ($is_recurring === 'yes' && empty($recurring_days)) {
        $errors[] = 'Pilih minimal satu hari untuk jadwal rutin.';
    }

    if (!empty($errors)) {
        $msg = implode("\\n", $errors);
        echo "<script>alert('Gagal menyimpan peminjaman:\\n{$msg}');</script>";
    } else {
        $query = "INSERT INTO pinjamkolam 
                  (id_user, id_kolam, id_kelas, tgl_mulai, tgl_selesai, waktu_mulai, waktu_selesai, nama, no_hp, tujuan, status, is_recurring, recurring_days) 
                  VALUES 
                  ('$id_user', '$id_kolam', '$id_kelas', '$tgl_mulai', '$tgl_selesai', '$waktu_mulai', '$waktu_selesai', '$nama', '$no_hp', " . 
                  ($tujuan !== '' ? "'" . $tujuan . "'" : "NULL") . ", '$status', '$is_recurring', " . 
                  ($recurring_days !== null ? "'" . mysqli_real_escape_string($conn, $recurring_days) . "'" : "NULL") . ")";

        if (mysqli_query($conn, $query)) {
            echo "<script>alert('Data Peminjaman Kolam Berhasil Ditambahkan');</script>";
            echo "<meta http-equiv='refresh' content='0; URL=?view=datapinjamkolam'>";
            exit;
        } else {
            $err = mysqli_error($conn);
            echo "<script>alert('Gagal menambahkan data: {$err}');</script>";
        }
    }
}
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

.page-pinjamstudio .card-header .btn {
    background: white;
    color: var(--primary);
    font-weight: 600;
    border: none;
    padding: 10px 20px;
    border-radius: var(--radius-sm);
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.page-pinjamstudio .card-header .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    background: #f8f9fa;
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
                <div class="card-body">
                    <form method="POST" action="">
                        
                        <div class="form-grid">
                            
                            <div class="form-group">
                                <label>
                                    Pilih Kolam <span class="text-danger">*</span>
                                </label>
                                <select name="id_kolam" class="form-control" required>
                                    <option value="">-- Pilih Kolam --</option>
                                    <?php
                                    $kolam_query = mysqli_query($conn, "SELECT * FROM kolam ORDER BY jenis_kolam ASC");
                                    while($kolam = mysqli_fetch_array($kolam_query)) {
                                        echo "<option value='{$kolam['id_kolam']}'>{$kolam['jenis_kolam']}</option>";
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
                                    $kelas_query = mysqli_query($conn, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
                                    while($kelas = mysqli_fetch_array($kelas_query)) {
                                        echo "<option value='{$kelas['id_kelas']}'>{$kelas['nama_kelas']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>
                                    Tanggal Mulai <span class="text-danger">*</span>
                                </label>
                                <input type="date" 
                                       id="tgl_mulai" 
                                       name="tgl_mulai" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($prefillDate, ENT_QUOTES, 'UTF-8'); ?>" 
                                       required 
                                       min="<?php echo date('Y-m-d'); ?>">
                            </div>

                            <div class="form-group">
                                <label>Tanggal Selesai</label>
                                <input type="date" 
                                       id="tgl_selesai" 
                                       name="tgl_selesai" 
                                       class="form-control" 
                                       min="<?php echo date('Y-m-d'); ?>">
                                <small class="form-hint">
                                    Kosongkan jika hanya 1 hari
                                </small>
                            </div>

                            <div class="form-group">
                                <label>
                                    Waktu Mulai <span class="text-danger">*</span>
                                </label>
                                <input type="time" name="waktu_mulai" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label>
                                    Waktu Selesai <span class="text-danger">*</span>
                                </label>
                                <input type="time" name="waktu_selesai" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label>
                                    Nama Penanggung Jawab <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="nama" class="form-control" placeholder="Masukkan nama lengkap" required>
                            </div>

                            <div class="form-group">
                                <label>
                                    Nomor HP <span class="text-danger">*</span>
                                </label>
                                <input type="tel" name="no_hp" class="form-control" placeholder="08xxxxxxxxxx" pattern="[0-9]{10,13}" required>
                                <small class="form-hint">
                                    Contoh: 081234567890
                                </small>
                            </div>

                            <div class="form-group full-width">
                                <label>
                                    Tujuan Peminjaman
                                </label>
                                <textarea name="tujuan" class="form-control" placeholder="Jelaskan tujuan peminjaman kolam..." rows="3"></textarea>
                                <small class="form-hint">
                                    Contoh: Latihan rutin ekstrakurikuler renang
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
                                <label>Pilih Hari Rutin <span class="text-danger">*</span></label>
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
                                <i class="fa fa-save"></i>
                                Simpan Peminjaman
                            </button>
                            <a href="?view=datapinjamkolam" class="btn-xs btn-secondary">
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
        
        // Optional: Auto-fill tgl_selesai dengan tanggal yang sama
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
</script>
