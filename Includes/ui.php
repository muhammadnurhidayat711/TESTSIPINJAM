<?php
// ===========================================
// Reusable UI helpers for SIPINJAM
// ===========================================

// ---------- Formatter ----------
function ui_fmt_tgl($ymd){
  if(!$ymd || $ymd==='0000-00-00') return '-';
  [$y,$m,$d] = explode('-',$ymd);
  $bulan = ["","Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
  return sprintf("%02d %s %s",(int)$d,$bulan[(int)$m],$y);
}
function ui_yes($v){
  $v = strtolower(trim((string)$v));
  return in_array($v, ['ya','iya','yes','y','true','1'], true);
}

// ---------- Badge Status ----------
function ui_badge_status(string $status): string {
  $s = strtolower(trim($status));
  if ($s === 'approve') {
    return '<span class="ui-badge ui-badge--ok">approve</span>';
  }
  return '<span class="ui-badge ui-badge--pending">menunggu</span>';
}

// ---------- Chips ----------
function ui_chip(string $label, ?int $qty=null): string {
  $q = ($qty!==null && $qty>0) ? ' <span class="qty">('.$qty.')</span>' : '';
  return '<span class="ui-chip">'.$label.$q.'</span>';
}
function ui_chips(array $items): string {
  // $items = [['label'=>'Meja: Iya','qty'=>10], ...]
  $html = '<div class="ui-chips">';
  foreach($items as $it){
    $label = $it['label'] ?? '';
    $qty   = isset($it['qty']) ? (int)$it['qty'] : null;
    $html .= ui_chip(htmlspecialchars($label, ENT_QUOTES, 'UTF-8'), $qty);
  }
  $html .= '</div>';
  return $html;
}

// ---------- Jadwal (dua baris: tanggal & jam) ----------
function ui_jadwal($tglMulai,$tglSelesai,$wm,$ws): string {
  $tgl1 = ui_fmt_tgl($tglMulai);
  $tgl2 = ui_fmt_tgl($tglSelesai);
  $jam1 = $wm ? substr($wm,0,5) : '-';
  $jam2 = $ws ? substr($ws,0,5) : '-';

  return '
  <div class="ui-jadwal">
    <div class="line"><span>'.$tgl1.'</span><span class="arrow">➜</span><span>'.$tgl2.'</span></div>
    <div class="line"><span>'.$jam1.'</span><span class="arrow">➜</span><span>'.$jam2.'</span></div>
  </div>';
}

// ---------- Row class by status ----------
function ui_row_class(string $status): string {
  $s = strtolower(trim($status));
  return $s==='approve' ? 'row-approve' : 'row-menunggu';
}

// ---------- Action buttons (detail, hapus, approve/cancel) ----------
function ui_actions(array $pin): string {
  $status_lc = strtolower(trim($pin['status'] ?? ''));
  $id = urlencode($pin['id_pinjam']);
  $btns = [];

  $btns[] = '<a href="?view=detailpinjambarang&id='.$id.'" class="btn btn--xs btn--success" title="Detail"><i class="fa fa-eye"></i></a>';
  $btns[] = '<a href="#modalHapusPinjamBarang'.$pin['id_pinjam'].'" data-toggle="modal" class="btn btn--xs btn--danger" title="Hapus"><i class="fa fa-trash"></i></a>';

  if ($status_lc==='menunggu') {
    $btns[] = '<a href="?view=datapinjambarang&id_approve='.$id.'" class="btn btn--xs btn--primary" title="Approve"><i class="fa fa-check-circle"></i></a>';
  } else {
    $btns[] = '<a href="#modalCancelApprove'.$pin['id_pinjam'].'" data-toggle="modal" class="btn btn--xs btn--warning" title="Batalkan Approve"><i class="fa fa-times-circle"></i></a>';
  }

  return '<div class="ui-actions">'.implode('', $btns).'</div>';
}

// ---------- Modal hapus ----------
function ui_modal_delete(string $id_pinjam): string {
  $id = htmlspecialchars($id_pinjam, ENT_QUOTES, 'UTF-8');
  return '
  <div class="modal fade" id="modalHapusPinjamBarang'.$id.'" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document"><div class="modal-content">
      <div class="modal-header no-bd">
        <h5 class="modal-title"><span class="fw-mediumbold">Hapus</span> <span class="fw-light">Gedung</span></h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form method="POST" action="">
        <div class="modal-body">
          <input type="hidden" name="id_pinjam" value="'.$id.'">
          <h4>Apakah Anda ingin menghapus data ini?</h4>
        </div>
        <div class="modal-footer no-bd">
          <button type="submit" name="hapus" class="btn btn--danger"><i class="fa fa-trash"></i> Hapus</button>
          <button type="button" class="btn btn--secondary" data-dismiss="modal"><i class="fa fa-undo"></i> Tutup</button>
        </div>
      </form>
    </div></div>
  </div>';
}

// ---------- Modal cancel approve ----------
function ui_modal_cancel(string $id_pinjam): string {
  $id = htmlspecialchars($id_pinjam, ENT_QUOTES, 'UTF-8');
  return '
  <div class="modal fade" id="modalCancelApprove'.$id.'" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document"><div class="modal-content">
      <div class="modal-header no-bd">
        <h5 class="modal-title"><span class="fw-mediumbold">Batalkan Approve</span> <span class="fw-light">Peminjaman</span></h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form method="POST" action="">
        <div class="modal-body">
          <input type="hidden" name="id_cancel" value="'.$id.'">
          <p>Status akan dikembalikan ke <b>menunggu</b>. Lanjutkan?</p>
        </div>
        <div class="modal-footer no-bd">
          <button type="submit" name="cancel_approve" class="btn btn--warning"><i class="fa fa-times-circle"></i> Ya, Batalkan</button>
          <button type="button" class="btn btn--secondary" data-dismiss="modal"><i class="fa fa-undo"></i> Tidak</button>
        </div>
      </form>
    </div></div>
  </div>';
}
