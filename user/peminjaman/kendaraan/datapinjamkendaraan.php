<?php
// ========= Helper Functions =========
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

// ========= Form Processing with Security =========
if (isset($_POST['hapus']) && isset($_POST['id_pk']) && isset($_POST['id_kendaraan'])) {
  $id_pk = (int)($_POST['id_pk'] ?? 0);
  $id_kendaraan = (int)($_POST['id_kendaraan'] ?? 0);

  $selSto = mysqli_query($conn, "SELECT * FROM kendaraan WHERE id_kendaraan = $id_kendaraan");
  $sto = mysqli_fetch_array($selSto);
  $sisa = 'menunggu';

  mysqli_query($conn, "DELETE FROM pinjamkendaraan WHERE id_pk = $id_pk");


  exit;

} elseif (isset($_POST['ubah']) && isset($_POST['id_pk']) && isset($_POST['id_kendaraan'])) {
  $id_pk = (int)($_POST['id_pk'] ?? 0);
  $id_kendaraan = (int)($_POST['id_kendaraan'] ?? 0);

  $selSto = mysqli_query($conn, "SELECT * FROM kendaraan WHERE id_kendaraan = $id_kendaraan");
  $sto = mysqli_fetch_array($selSto);
  $sisa = 'menunggu';
  $status = 'selesai';

  mysqli_query($conn, "UPDATE kendaraan SET status='$sisa' WHERE id_kendaraan = $id_kendaraan");
  mysqli_query($conn, "UPDATE pinjamkendaraan SET status='$status' WHERE id_pk = $id_pk");

  header("Location: ?view=datapinjamkendaraan");
  exit;
}
?>

<style>
  /* Scoped styling untuk halaman ini saja */
  .page-pinjamkendaraan {
    --ok: #22c55e;
    --pending: #f59e0b;
    --ok-soft: #e9f8ef;
    --pending-soft: #fff7e6;
    --muted: #6b7280;
    --txt: #1f2937;
    --card: #fff;
    --shadow: 0 6px 16px rgba(0, 0, 0, .06);
    --border: #e5e7eb;
    --info: #3b82f6;
  }

  .page-pinjamkendaraan .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid var(--border);
  }

  .page-pinjamkendaraan .page-header .page-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--txt);
    margin: 0;
  }

  .page-pinjamkendaraan .btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
    white-space: nowrap;
  }

  .page-pinjamkendaraan .btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow);
    text-decoration: none;
  }

  .page-pinjamkendaraan .btn-primary {
    background: var(--info);
    color: white;
  }

  .page-pinjamkendaraan .btn-primary:hover {
    background: #2563eb;
    color: white;
  }

  .page-pinjamkendaraan .btn-success {
    background: var(--ok);
    color: white;
  }

  .page-pinjamkendaraan .btn-success:hover {
    background: #16a34a;
    color: white;
  }

  .page-pinjamkendaraan .btn-danger {
    background: #ef4444;
    color: white;
  }

  .page-pinjamkendaraan .btn-danger:hover {
    background: #dc2626;
    color: white;
  }

  .page-pinjamkendaraan .btn-warning {
    background: #f59e0b;
    color: white;
  }

  .page-pinjamkendaraan .btn-warning:hover {
    background: #d97706;
    color: white;
  }

  .page-pinjamkendaraan .btn-xs {
    padding: 6px 10px;
    font-size: 0.8rem;
  }

  .page-pinjamkendaraan .ml-auto {
    margin-left: auto;
  }

  .page-pinjamkendaraan .table-modern {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 10px;
  }

  .page-pinjamkendaraan .table-modern thead th {
    font-weight: 700;
    color: var(--muted);
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 12px 12px;
    text-align: left;
    vertical-align: middle;
    border-bottom: 2px solid var(--border);
  }

  .page-pinjamkendaraan .table-modern tbody td {
    padding: 14px 16px;
    vertical-align: middle;
    color: var(--txt);
    font-size: 0.95rem;
  }

  .page-pinjamkendaraan .table-modern tbody tr {
    background: var(--card);
    box-shadow: var(--shadow);
    transition: all 0.2s ease;
  }

  .page-pinjamkendaraan .table-modern tbody tr:hover {
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
  }

  .page-pinjamkendaraan .badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 999px;
    font-weight: 700;
    font-size: 0.82rem;
    border: none;
  }

  .page-pinjamkendaraan .badge-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
  }

  .page-pinjamkendaraan .badge-warning {
    background: #cf9a27ff;
    color: #b45309;
    border: 1px solid #fbbf24;
  }

  .page-pinjamkendaraan .vehicle-info {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }

  .page-pinjamkendaraan .vehicle-name {
    font-weight: 600;
    color: var(--txt);
    font-size: 1rem;
  }

  .page-pinjamkendaraan .vehicle-desc {
    font-size: 0.85rem;
    color: var(--muted);
  }

  .page-pinjamkendaraan .schedule-display {
    display: flex;
    flex-direction: column;
    gap: 6px;
    font-size: 0.9rem;
  }

  .page-pinjamkendaraan .schedule-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }

  .page-pinjamkendaraan .schedule-label {
    color: var(--muted);
    font-size: 0.75rem;
    text-transform: uppercase;
    font-weight: 600;
    min-width: 35px;
  }

  .page-pinjamkendaraan .schedule-content {
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 500;
  }

  .page-pinjamkendaraan .schedule-arrow {
    color: var(--muted);
    font-weight: bold;
  }

  .page-pinjamkendaraan .tujuan-badge {
    display: inline-block;
    background: #e0f2fe;
    color: #0369a1;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 0.85rem;
    border-left: 3px solid #0369a1;
    font-weight: 500;
  }

  .page-pinjamkendaraan .actions {
    display: flex;
    gap: 6px;
    justify-content: center;
    flex-wrap: wrap;
  }

  .page-pinjamkendaraan .copyright {
    margin-top: 24px;
    color: var(--muted);
    font-size: 0.9rem;
    text-align: center;
  }

  /* Dark Mode Support */
  @media (max-width: 768px) {
    .page-pinjamkendaraan .actions {
      flex-direction: column;
    }
    .page-pinjamkendaraan .btn-xs {
      width: 100%;
    }
  }
</style>

<div class="page-pinjamkendaraan">
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <div class="d-flex align-items-center">
            <h4 class="card-title">Data Peminjaman Kendaraan</h4>
            <a href="?view=createpinjamkendaraan" class="btn btn-primary btn-round ml-auto">
              <i class="fa fa-plus"></i>
              Tambah Data
            </a>
          </div>
        </div>

        <div class="card-body">
          <div class="table-responsive">
            <table class="table-modern">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Kendaraan</th>
                  <th>Jadwal</th>
                  <th>Tujuan</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $no = 1;
                $query = mysqli_query($conn, "SELECT * FROM pinjamkendaraan 
                  INNER JOIN user ON user.id = pinjamkendaraan.id_user 
                  WHERE pinjamkendaraan.id_user = '" . $_SESSION['id'] . "'
                  AND LOWER(pinjamkendaraan.status) IN ('menunggu', 'approve', 'selesai')
                  ORDER BY pinjamkendaraan.tgl_mulai DESC, pinjamkendaraan.waktu_mulai DESC");

                while ($pinjamkendaraan = mysqli_fetch_array($query)) {
                  if ($_SESSION['id'] == $pinjamkendaraan['id_user']) {
                    $get_nama_kendaraan = 'Belum Diatur';
                    $get_deskripsi = 'Belum Diatur';

                    if (null !== $pinjamkendaraan['id_kendaraan'] && !empty($pinjamkendaraan['id_kendaraan'])) {
                      $get_kendaraan = mysqli_query($conn, "SELECT * FROM `kendaraan` WHERE `id_kendaraan` = " . intval($pinjamkendaraan['id_kendaraan']));
                      if (0 < mysqli_num_rows($get_kendaraan)) {
                        $kendaraan = mysqli_fetch_assoc($get_kendaraan);
                        $get_nama_kendaraan = htmlspecialchars($kendaraan['nama_kendaraan']);
                        $get_deskripsi = htmlspecialchars($kendaraan['deskripsi']);
                      }
                    }

                    $status_lc = strtolower(trim($pinjamkendaraan['status']));
                    $tgl_mulai = fmt_tgl($pinjamkendaraan['tgl_mulai']);
                    $tgl_selesai = fmt_tgl($pinjamkendaraan['tgl_selesai']);
                    $waktu_mulai = fmt_waktu($pinjamkendaraan['waktu_mulai']);
                    $waktu_selesai = fmt_waktu($pinjamkendaraan['waktu_selesai']);
                    $tujuan = htmlspecialchars($pinjamkendaraan['tujuan'] ?? '-');
                    ?>
                    <tr>
                      <td><strong><?php echo $no++; ?></strong></td>
                      <td>
                        <div class="vehicle-info">
                          <div class="vehicle-name"><?php echo $get_nama_kendaraan; ?></div>
                          <div class="vehicle-desc"><?php echo $get_deskripsi; ?></div>
                        </div>
                      </td>
                      <td>
                        <div class="schedule-display">
                          <div class="schedule-row">
                            <span class="schedule-label">Tgl:</span>
                            <span class="schedule-content">
                              <?php echo $tgl_mulai; ?>
                              <span class="schedule-arrow">→</span>
                              <?php echo $tgl_selesai; ?>
                            </span>
                          </div>
                          <div class="schedule-row">
                            <span class="schedule-label">Jam:</span>
                            <span class="schedule-content">
                              <?php echo $waktu_mulai; ?>
                              <span class="schedule-arrow">→</span>
                              <?php echo $waktu_selesai; ?>
                            </span>
                          </div>
                        </div>
                      </td>
                      <td>
                        <?php if ($tujuan !== '-'): ?>
                          <span class="tujuan-badge">
                            <i class="fa fa-map-marker"></i> <?php echo $tujuan; ?>
                          </span>
                        <?php else: ?>
                          <span style="color: var(--muted);">-</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php
                        if ($status_lc === 'menunggu') {
                          $status_class = 'badge-warning';
                          $status_label = 'Menunggu';
                        } elseif ($status_lc === 'approve') {
                          $status_class = 'badge-success';
                          $status_label = 'Disetujui';
                        } else {
                          $status_class = 'badge-danger';
                          $status_label = ucfirst($status_lc);
                        }
                        ?>
                        <span class="badge <?php echo $status_class; ?>">
                          <?php echo $status_label; ?>
                        </span>
                      </td>
                      <td>
                        <div class="actions">
                          <a href="?view=detailpinjamkendaraan&id=<?php echo htmlspecialchars($pinjamkendaraan['id_pk']); ?>"
                             class="btn btn-xs btn-success" title="Detail">
                            <i class="fa fa-eye"></i>
                          </a>

                          <?php if ($status_lc === 'menunggu') { ?>
                            <!-- HAPUS LANGSUNG - TANPA MODAL -->
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin hapus peminjaman ini?');">
                              <input type="hidden" name="id_pk" value="<?php echo htmlspecialchars($pinjamkendaraan['id_pk']); ?>">
                              <input type="hidden" name="id_kendaraan" value="<?php echo htmlspecialchars($pinjamkendaraan['id_kendaraan']); ?>">
                              <button type="submit" name="hapus" class="btn btn-xs btn-danger" title="Hapus">
                                <i class="fa fa-trash"></i>
                              </button>
                            </form>
                          <?php } elseif ($status_lc === 'approve') { ?>
                            <a href="#modalKembalikan<?php echo $pinjamkendaraan['id_pk']; ?>" data-toggle="modal"
                               class="btn btn-xs btn-warning" title="Kembalikan">
                              <i class="fa fa-undo"></i>
                            </a>
                          <?php } ?>
                        </div>
                      </td>
                    </tr>

                    <!-- Modal Kembalikan (Tetap ada) -->
                    <?php if ($status_lc === 'approve') { ?>
                    <div class="modal fade" id="modalKembalikan<?php echo $pinjamkendaraan['id_pk']; ?>" tabindex="-1"
                         role="dialog" aria-hidden="true">
                      <div class="modal-dialog" role="document">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title">
                              <i class="fa fa-undo"></i> Kembalikan Kendaraan
                            </h5>
                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                          </div>
                          <form method="POST" action="">
                            <div class="modal-body">
                              <input type="hidden" name="id_pk"
                                     value="<?php echo htmlspecialchars($pinjamkendaraan['id_pk']); ?>">
                              <input type="hidden" name="id_kendaraan"
                                     value="<?php echo htmlspecialchars($pinjamkendaraan['id_kendaraan']); ?>">
                              <h4>Apakah Anda ingin mengembalikan kendaraan ini?</h4>
                              <p><strong><?php echo $get_nama_kendaraan; ?></strong></p>
                              <p class="text-muted">Kendaraan akan tersedia untuk peminjaman kembali.</p>
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

  <h6 class="copyright"><i class="fa fa-copyright"></i> Copyright@2025 | <strong>SIPINJAM</strong></h6>
</div>
