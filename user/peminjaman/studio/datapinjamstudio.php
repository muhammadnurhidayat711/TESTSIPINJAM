<?php
// ========= Pastikan koneksi $conn (mysqli) sudah dibuat sebelum file ini =========

// ================== PROSES AKSI ==================
if (isset($_POST['hapus'])) {
    $id_pinjamstudio = $_POST['id_pinjamstudio'];
    mysqli_query($conn, "DELETE from pinjamstudio where id_pinjamstudio='$id_pinjamstudio'");
    echo "<script>alert ('Data Berhasil Dihapus') </script>";
    echo "<meta http-equiv='refresh' content=0; URL=?view=datapinjamstudio>";
} elseif (isset($_POST['ubah'])) {
    $id_pinjam = $_POST['id_pinjamstudio'];
    $status = $_POST['status'];
    mysqli_query($conn, "UPDATE pinjamstudio set status='$status' where id_pinjamstudio='$id_pinjam'");
    echo "<script>alert ('Berhasil Dikembalikan') </script>";
    echo "<meta http-equiv='refresh' content=0; URL=?view=datapinjamstudio>";
}

// ================== Helper Functions ==================
function fmt_tgl($ymd)
{
    if (!$ymd || $ymd === '0000-00-00')
        return '-';
    $parts = explode('-', $ymd);
    if (count($parts) !== 3)
        return $ymd;
    [$y, $m, $d] = $parts;
    return sprintf("%02d-%02d-%s", (int) $d, (int) $m, $y);
}

function fmt_waktu($hms)
{
    return $hms ? substr($hms, 0, 5) : '-';
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

    /* Card Styling */
    .page-pinjamstudio .card {
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
    }

    .page-pinjamstudio .card-body {
        padding: 0;
    }

    /* Toolbar di atas tabel */
    .page-pinjamstudio .toolbar {
        padding: 16px 20px;
        background: #fff;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: flex-end;
        align-items: center;
    }

    .page-pinjamstudio .toolbar .btn {
        background: var(--primary);
        color: white;
        font-weight: 600;
        border: none;
        padding: 10px 20px;
        border-radius: var(--radius-sm);
        transition: all 0.2s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .page-pinjamstudio .toolbar .btn:hover {
        background: #5558e3;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(99, 102, 241, 0.3);
        text-decoration: none;
    }

    /* Table Styling */
    .page-pinjamstudio .table-modern {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }

    .page-pinjamstudio .table-modern thead th {
        background: var(--muted-light);
        font-weight: 600;
        color: var(--txt-secondary);
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        padding: 14px 20px;
        text-align: center;
        border-bottom: 2px solid var(--border);
        white-space: nowrap;
    }

    .page-pinjamstudio .table-modern thead th:first-child {
        width: 60px;
    }

    .page-pinjamstudio .table-modern thead th:last-child {
        width: 120px; /* ✅ DIPERKECIL UNTUK TOMBOL VERTIKAL */
    }

    .page-pinjamstudio .table-modern tbody td {
        padding: 18px 20px;
        vertical-align: middle;
        color: var(--txt);
        font-size: 0.9rem;
        border-bottom: 1px solid var(--border);
        text-align: center;
        white-space: nowrap;
    }

    /* ✅ KETERANGAN BOLEH WRAP */
    .page-pinjamstudio .table-modern tbody td.col-keterangan {
        white-space: normal; /* ✅ BOLEH WRAP */
    }

    .page-pinjamstudio .table-modern tbody td:first-child {
        font-weight: 700;
        color: var(--muted);
        font-size: 0.85rem;
    }

    .page-pinjamstudio .table-modern tbody tr {
        background: var(--card);
        transition: all 0.2s ease;
    }

    .page-pinjamstudio .table-modern tbody tr:hover {
        background: #fafbfc;
        box-shadow: inset 4px 0 0 var(--primary);
    }

    .page-pinjamstudio .table-modern tbody tr:last-child td {
        border-bottom: none;
    }

    /* Studio Name */
    .page-pinjamstudio .studio-name {
        font-weight: 600;
        color: var(--txt);
        font-size: 0.95rem;
        white-space: nowrap;
    }

    /* Schedule Display */
    .page-pinjamstudio .schedule-box {
        display: flex;
        flex-direction: column;
        gap: 4px;
        align-items: center;
    }

    .page-pinjamstudio .schedule-item {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 0.85rem;
        white-space: nowrap;
    }

    .page-pinjamstudio .schedule-label {
        color: var(--muted);
        font-weight: 500;
        font-size: 0.7rem;
    }

    .page-pinjamstudio .schedule-value {
        color: var(--txt);
        font-weight: 600;
        white-space: nowrap;
    }

    .page-pinjamstudio .schedule-separator {
        color: var(--muted);
        margin: 0 2px;
    }

    /* Keterangan - ✅ BOLEH WRAP */
    .page-pinjamstudio .keterangan-box {
        max-width: 250px;
        font-size: 0.85rem;
        line-height: 1.5;
        color: var(--txt-secondary);
        white-space: normal; /* ✅ BOLEH WRAP */
        overflow: visible; /* ✅ VISIBLE */
        text-overflow: clip; /* ✅ NO ELLIPSIS */
        margin: 0 auto;
        text-align: left; /* ✅ TEXT ALIGN LEFT */
        display: -webkit-box;
        -webkit-line-clamp: 3; /* ✅ MAX 3 BARIS */
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Badge Status */
    .page-pinjamstudio .badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.8rem;
        border: none;
        white-space: nowrap;
    }

    .page-pinjamstudio .badge::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 50%;
        display: inline-block;
    }

    .page-pinjamstudio .badge-success {
        background: var(--success-light);
        color: #065f46;
    }

    .page-pinjamstudio .badge-success::before {
        background: var(--success);
    }

    .page-pinjamstudio .badge-danger {
        background: var(--danger-light);
        color: #991b1b;
    }

    .page-pinjamstudio .badge-danger::before {
        background: var(--danger);
    }

    .page-pinjamstudio .badge-warning {
        background: var(--warning-light);
        color: #92400e;
    }

    .page-pinjamstudio .badge-warning::before {
        background: var(--warning);
    }

    /* ✅ ACTION BUTTONS - VERTIKAL */
    .page-pinjamstudio .actions {
        display: flex;
        flex-direction: column; /* ✅ VERTIKAL */
        gap: 6px; /* ✅ GAP ANTAR TOMBOL */
        justify-content: center;
        align-items: center; /* ✅ RATA TENGAH */
    }

    .page-pinjamstudio .btn-xs {
        padding: 6px 12px; /* ✅ PADDING LEBIH KECIL */
        font-size: 0.75rem; /* ✅ FONT LEBIH KECIL */
        font-weight: 600;
        border-radius: var(--radius-sm);
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.2s ease;
        text-decoration: none;
        cursor: pointer;
        white-space: nowrap;
        min-width: 90px; /* ✅ MIN WIDTH UNTUK KONSISTENSI */
        justify-content: center;
    }

    .page-pinjamstudio .btn-success {
        background: var(--success);
        color: white;
    }

    .page-pinjamstudio .btn-success:hover {
        background: #059669;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(16, 185, 129, 0.3);
    }

    .page-pinjamstudio .btn-danger {
        background: var(--danger);
        color: white;
    }

    .page-pinjamstudio .btn-danger:hover {
        background: #dc2626;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(239, 68, 68, 0.3);
    }

    .page-pinjamstudio .btn-warning {
        background: var(--warning);
        color: white;
    }

    .page-pinjamstudio .btn-warning:hover {
        background: #d97706;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(245, 158, 11, 0.3);
    }

    /* Dark Mode Support */
    body[data-theme="dark"] .page-pinjamstudio {
        --txt: #f3f4f6;
        --txt-secondary: #d1d5db;
        --card: #1e293b;
        --border: #334155;
        --muted-light: #1e293b;
    }

    body[data-theme="dark"] .page-pinjamstudio .card {
        background: #0f172a;
        border-color: var(--border);
    }

    body[data-theme="dark"] .page-pinjamstudio .toolbar {
        background: #1e293b;
    }

    body[data-theme="dark"] .page-pinjamstudio .table-modern thead th {
        background: #1e293b;
        color: #94a3b8;
    }

    body[data-theme="dark"] .page-pinjamstudio .table-modern tbody tr {
        background: var(--card);
    }

    body[data-theme="dark"] .page-pinjamstudio .table-modern tbody tr:hover {
        background: #334155;
    }

    body[data-theme="dark"] .page-pinjamstudio .studio-name,
    body[data-theme="dark"] .page-pinjamstudio .schedule-value {
        color: var(--txt);
    }

    body[data-theme="dark"] .page-pinjamstudio .keterangan-box {
        color: var(--txt-secondary);
    }

    body[data-theme="dark"] .page-pinjamstudio .badge-success {
        background: rgba(16, 185, 129, 0.15);
        color: #6ee7b7;
    }

    body[data-theme="dark"] .page-pinjamstudio .badge-danger {
        background: rgba(239, 68, 68, 0.15);
        color: #fca5a5;
    }

    body[data-theme="dark"] .page-pinjamstudio .badge-warning {
        background: rgba(245, 158, 11, 0.15);
        color: #fbbf24;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .page-pinjamstudio .toolbar {
            padding: 12px 16px;
        }

        .page-pinjamstudio .toolbar .btn {
            width: 100%;
            justify-content: center;
        }

        .page-pinjamstudio .table-modern thead th,
        .page-pinjamstudio .table-modern tbody td {
            padding: 12px 10px;
            font-size: 0.8rem;
        }

        .page-pinjamstudio .btn-xs {
            width: 100%;
            min-width: unset;
        }

        .page-pinjamstudio .keterangan-box {
            max-width: 150px;
        }
    }
</style>

<div class="page-pinjamstudio">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="toolbar">
                    <a href="?view=createpinjamstudio" class="btn">
                        <i class="fa fa-plus"></i>
                        Tambah Data
                    </a>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Studio</th>
                                    <th>Kelas</th>
                                    <th>Jadwal</th>
                                    <th>Keterangan</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                $query = mysqli_query($conn, 'SELECT * FROM pinjamstudio INNER JOIN user ON user.id = pinjamstudio.id_user INNER JOIN studio ON studio.id_studio = pinjamstudio.id_studio INNER JOIN kelas ON kelas.id_kelas = pinjamstudio.id_kelas ORDER BY pinjamstudio.tgl_mulai DESC, pinjamstudio.waktu_mulai DESC;');
                                while ($pinjamstudio = mysqli_fetch_array($query)) {
                                    if ($_SESSION['id'] == $pinjamstudio['id_user']) {
                                        $tgl_mulai = fmt_tgl($pinjamstudio['tgl_mulai']);
                                        $tgl_selesai = isset($pinjamstudio['tgl_selesai']) ? fmt_tgl($pinjamstudio['tgl_selesai']) : $tgl_mulai;
                                        $waktu_mulai = fmt_waktu($pinjamstudio['waktu_mulai']);
                                        $waktu_selesai = fmt_waktu($pinjamstudio['waktu_selesai']);
                                        $keterangan = htmlspecialchars($pinjamstudio['deskripsi_peminjaman'] ?? '-');
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td>
                                        <span class="studio-name"><?php echo htmlspecialchars($pinjamstudio['jenis_studio']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($pinjamstudio['nama_kelas']); ?></td>
                                    <td>
                                        <div class="schedule-box">
                                            <div class="schedule-item">
                                                <span class="schedule-label">TGL</span>
                                                <span class="schedule-value"><?php echo $tgl_mulai; ?></span>
                                                <span class="schedule-separator">→</span>
                                                <span class="schedule-value"><?php echo $tgl_selesai; ?></span>
                                            </div>
                                            <div class="schedule-item">
                                                <span class="schedule-label">JAM</span>
                                                <span class="schedule-value"><?php echo $waktu_mulai; ?></span>
                                                <span class="schedule-separator">→</span>
                                                <span class="schedule-value"><?php echo $waktu_selesai; ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="col-keterangan">
                                        <div class="keterangan-box" title="<?php echo $keterangan; ?>">
                                            <?php echo $keterangan; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($pinjamstudio['status'] == 'menunggu') { ?>
                                            <span class="badge badge-danger">Menunggu</span>
                                        <?php } elseif ($pinjamstudio['status'] == 'approve') { ?>
                                            <span class="badge badge-success">Approved</span>
                                        <?php } else { ?>
                                            <span class="badge badge-warning"><?php echo ucfirst($pinjamstudio['status']); ?></span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <!-- ✅ TOMBOL VERTIKAL -->
                                        <div class="actions">
                                            <a href="?view=detailpinjamstudio&id=<?php echo $pinjamstudio['id_pinjamstudio']; ?>" 
                                               title="Detail" class="btn btn-xs btn-success">
                                                <i class="fa fa-eye"></i> Detail
                                            </a>
                                            <?php if ($pinjamstudio['status'] == 'menunggu') { ?>
                                                <a href="#modalHapusPinjamStudio<?php echo $pinjamstudio['id_pinjamstudio']; ?>" 
                                                   data-toggle="modal" title="Batal Pinjam" class="btn btn-xs btn-danger">
                                                    <i class="fa fa-trash"></i> Batal
                                                </a>
                                            <?php } elseif ($pinjamstudio['status'] == 'approve') { ?>
                                                <a href="#modalKembalikanPinjamStudio<?php echo $pinjamstudio['id_pinjamstudio']; ?>" 
                                                   data-toggle="modal" title="Kembalikan" class="btn btn-xs btn-warning">
                                                    <i class="fa fa-undo"></i> Kembalikan
                                                </a>
                                            <?php } ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                    }
                                } 
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODALS (tetap sama) -->
<?php
$c = mysqli_query($conn, 'SELECT pinjamstudio.id_pinjamstudio from pinjamstudio join user on user.id=pinjamstudio.id_user');
while ($row = mysqli_fetch_array($c)) {
?>
<div class="modal fade" id="modalHapusPinjamStudio<?php echo $row['id_pinjamstudio']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-trash"></i> Batalkan Peminjaman
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="id_pinjamstudio" value="<?php echo $row['id_pinjamstudio']; ?>">
                    <h4>Apakah Anda yakin ingin membatalkan peminjaman studio ini?</h4>
                    <p class="text-muted">Tindakan ini tidak dapat dibatalkan.</p>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="hapus" class="btn btn-danger">
                        <i class="fa fa-trash"></i> Ya, Batalkan
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fa fa-times"></i> Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php } ?>

<?php
$c = mysqli_query($conn, 'SELECT * from pinjamstudio inner join user on user.id=pinjamstudio.id_user');
while ($row = mysqli_fetch_array($c)) {
?>
<div class="modal fade" id="modalKembalikanPinjamStudio<?php echo $row['id_pinjamstudio']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-undo"></i> Kembalikan Peminjaman
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="id_pinjamstudio" value="<?php echo $row['id_pinjamstudio']; ?>">
                    <input type="hidden" name="status" value="selesai">
                    <h4>Apakah Anda ingin mengembalikan peminjaman studio ini?</h4>
                    <p class="text-muted">Peminjaman akan ditandai sebagai selesai.</p>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="ubah" class="btn btn-warning">
                        <i class="fa fa-undo"></i> Ya, Kembalikan
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fa fa-times"></i> Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php } ?>
