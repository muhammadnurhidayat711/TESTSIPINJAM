<?php
/**
 * Cetak Detail Peminjaman — A4 Landscape (ukuran lebih besar)
 * Kiri: Informasi Umum + Fasilitas
 * Kanan: Peminjam + Detail
 * Footer bawah-tengah: Tanda tangan Admin
 */


// Aktifkan error reporting untuk debugging (hapus setelah production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Output buffering untuk mencegah "headers already sent"
ob_start();


require_once __DIR__ . '/../cek.php';


if (!isset($conn) || !($conn instanceof mysqli)) {
  ob_end_clean();
  http_response_code(500);
  exit('Koneksi database tidak tersedia.');
}


// Set charset dan error reporting untuk mysqli
if (!mysqli_set_charset($conn, 'utf8mb4')) {
  ob_end_clean();
  http_response_code(500);
  exit('Error loading character set utf8mb4: ' . mysqli_error($conn));
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


/* ========================= Helpers ========================= */
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function show($v){ $v = trim((string)$v); return $v === '' ? '-' : e($v); }


function fmtDateId($ymd){
  if (!$ymd || $ymd === '0000-00-00') return '-';
  static $bulan = [1=>"Jan","Feb","Mar","Apr","Mei","Jun","Jul","Agu","Sep","Okt","Nov","Des"];
  $t = strtotime($ymd);
  if ($t === false) return '-';
  return date('j', $t) . ' ' . $bulan[(int)date('n',$t)] . ' ' . date('Y',$t);
}


function fmtTime($hhmmss){
  if (!$hhmmss) return '-';
  $t = strlen($hhmmss) === 5 ? $hhmmss.':00' : $hhmmss;
  $p = explode(':', $t);
  return sprintf('%02d:%02d', (int)$p[0], (int)$p[1]);
}


function rangeTanggal($mulai, $selesai){
  $a = fmtDateId($mulai); $b = fmtDateId($selesai);
  if ($mulai && $selesai && $mulai!=='0000-00-00' && $selesai!=='0000-00-00'){
    return ($mulai===$selesai) ? $a : "$a s/d $b";
  }
  return $a;
}


function rangeWaktu($mulai, $selesai){
  $a = fmtTime($mulai); $b = fmtTime($selesai);
  if ($a!=='-' && $b!=='-') return "$a - $b";
  if ($a!=='-') return $a;
  return '-';
}


function yn($v){
  $truthy = ['1','ya','y','true','on','iya','ada'];
  return in_array(strtolower(trim((string)$v)), $truthy, true) ? 'Ya' : 'Tidak';
}


function statusBadgeHtml($raw){
  $s = strtolower(trim((string)$raw));
  $map = [
    'pending'    => ['text' => 'Pending',   'color' => '#8a6100', 'bg' => '#f9efd1'],
    'ditolak'    => ['text' => 'Ditolak',   'color' => '#8a1b1b', 'bg' => '#f6dcdc'],
    'rejected'   => ['text' => 'Ditolak',   'color' => '#8a1b1b', 'bg' => '#f6dcdc'],
    'disetujui'  => ['text' => 'Disetujui', 'color' => '#0c5a3a', 'bg' => '#dff5ea'],
    'approved'   => ['text' => 'Disetujui', 'color' => '#0c5a3a', 'bg' => '#dff5ea'],
    'dibatalkan' => ['text' => 'Dibatalkan','color' => '#374151', 'bg' => '#eceff3'],
    'canceled'   => ['text' => 'Dibatalkan','color' => '#374151', 'bg' => '#eceff3'],
    'selesai'    => ['text' => 'Selesai',   'color' => '#0b4e7a', 'bg' => '#e3effb'],
    'returned'   => ['text' => 'Selesai',   'color' => '#0b4e7a', 'bg' => '#e3effb'],
  ];
  $info = $map[$s] ?? ['text'=>ucfirst($s) ?: '-', 'color'=>'#374151', 'bg'=>'#eceff3'];
  return '<span class="status-badge" style="background:'.$info['bg'].';color:'.$info['color'].'">'.$info['text'].'</span>';
}


function phoneDigits($s){ return preg_replace('/[^0-9+]/', '', (string)$s); }


/* ========================= Parameter ========================= */
$jenisRaw = strtolower(trim($_GET['jenis'] ?? ''));
$mapJenis = ['gedung'=>'gedung','barang'=>'gedung','ruangan'=>'gedung','aula'=>'gedung','hall'=>'gedung','studio'=>'studio','lab'=>'studio','recording'=>'studio'];
$jenis = $mapJenis[$jenisRaw] ?? '';


$idRaw = $_GET['id'] ?? ($_GET['id_pinjam'] ?? ($_GET['id_pinjamstudio'] ?? ''));
if ($idRaw === '' || !ctype_digit((string)$idRaw)) { 
  ob_end_clean();
  http_response_code(400); 
  exit('ID tidak valid atau tidak diberikan.'); 
}
$id = (int)$idRaw;


/* Auto-detect jenis */
if ($jenis === '') {
  if (isset($_GET['id_pinjamstudio'])) {
    $jenis = 'studio';
  } elseif (isset($_GET['id_pinjam'])) {
    $jenis = 'gedung';
  } else {
    $stmt = mysqli_prepare($conn, "SELECT 1 FROM pinjamstudio WHERE id_pinjamstudio=? LIMIT 1");
    if ($stmt === false) {
      ob_end_clean();
      http_response_code(500);
      exit('Error preparing statement: ' . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "i", $id); 
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    if ($res && $res->num_rows) {
      $jenis = 'studio';
    } else {
      $stmt2 = mysqli_prepare($conn, "SELECT 1 FROM pinjambarang WHERE id_pinjam=? LIMIT 1");
      if ($stmt2 === false) {
        ob_end_clean();
        http_response_code(500);
        exit('Error preparing statement: ' . mysqli_error($conn));
      }
      mysqli_stmt_bind_param($stmt2, "i", $id); 
      mysqli_stmt_execute($stmt2);
      $res2 = mysqli_stmt_get_result($stmt2);
      
      if ($res2 && $res2->num_rows) {
        $jenis = 'gedung'; 
      } else { 
        ob_end_clean();
        http_response_code(404); 
        exit('Data tidak ditemukan.'); 
      }
      mysqli_stmt_close($stmt2);
    }
    mysqli_stmt_close($stmt);
  }
}


/* ========================= Fetch Data ========================= */
$data = []; 
$judul=''; 
$leftSections=[]; 
$rightSections=[]; 
$layoutExists=false; 
$layoutPath='';


if ($jenis === 'gedung') {
  $sql = "SELECT p.id_pinjam, u.nama_lengkap, 
                 p.nama AS pic_nama, p.nohp AS pic_wa,
                 b.nama_barang, p.meja, p.jumlah_meja, p.kursi, p.jumlah_kursi,
                 p.sound, p.proyektor, p.tgl_mulai, p.waktu_mulai, p.tgl_selesai,
                 p.waktu_selesai, p.status, p.tujuan_barang, p.ket AS keterangan, 
                 p.layout, p.created_at
          FROM pinjambarang p
          LEFT JOIN user u ON u.id=p.id_user
          LEFT JOIN barang b ON b.id=p.id_barang
          WHERE p.id_pinjam=? LIMIT 1";
  
  $stmt = mysqli_prepare($conn, $sql);
  if ($stmt === false) {
    ob_end_clean();
    http_response_code(500);
    exit('Error preparing statement: ' . mysqli_error($conn));
  }
  
  mysqli_stmt_bind_param($stmt, "i", $id); 
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  
  if (!$res || !$res->num_rows) { 
    mysqli_stmt_close($stmt);
    ob_end_clean();
    http_response_code(404); 
    exit('Data peminjaman gedung tidak ditemukan.'); 
  }
  $data = $res->fetch_assoc();
  mysqli_stmt_close($stmt);


  $judul = 'Detail Peminjaman Gedung';
  $tanggal = rangeTanggal($data['tgl_mulai'], $data['tgl_selesai']);
  $waktu   = rangeWaktu($data['waktu_mulai'], $data['waktu_selesai']);
  $statusHtml = statusBadgeHtml($data['status']);
  $picTel = phoneDigits($data['pic_wa'] ?? '');


  $leftSections = [
    'Informasi Umum' => [
      ['ID', '#' . show($data['id_pinjam'])],
      ['Status', $statusHtml],
      ['Gedung', '<strong>' . show($data['nama_barang'] ?? '-') . '</strong>'],
      ['Tanggal', '<strong>' . e($tanggal) . '</strong>'],
      ['Waktu', '<strong>' . e($waktu) . ' WIB</strong>'],
    ],
    'Fasilitas' => [
      ['Meja', yn($data['meja'] ?? '0') . (($data['jumlah_meja'] ?? '') ? ' (' . show($data['jumlah_meja']) . ')' : '')],
      ['Kursi', yn($data['kursi'] ?? '0') . (($data['jumlah_kursi'] ?? '') ? ' (' . show($data['jumlah_kursi']) . ')' : '')],
      ['Sound', yn($data['sound'] ?? '0')],
      ['Proyektor', yn($data['proyektor'] ?? '0')],
    ],
  ];
  
  $rightSections = [
    'Peminjam' => [
      ['Nama', show($data['nama_lengkap'] ?? '-')],
      ['PIC', show($data['pic_nama'] ?? '-')],
      ['Kontak', $picTel ? '<a class="contact-link" href="tel:'.e($picTel).'">'.e($data['pic_wa']).'</a>' : show($data['pic_wa'] ?? '-')],
    ],
    'Detail' => [
      ['Tujuan', nl2br(show($data['tujuan_barang'] ?? '-'))],
      ['Keterangan', nl2br(show($data['keterangan'] ?? '-'))],
    ],
  ];
  
  $layoutBase = basename((string)($data['layout'] ?? ''));
  $layoutPath = "../../user/peminjaman/barang/layout/" . $layoutBase;
  $layoutExists = $layoutBase !== '' && is_file($layoutPath);


} else { /* studio */
  $sql = "SELECT ps.id_pinjamstudio, u.nama_lengkap, 
                 ps.nama AS pic_nama, ps.no_hp AS pic_wa,
                 s.jenis_studio, ps.tgl_mulai, ps.waktu_mulai, ps.waktu_selesai,
                 ps.deskripsi_peminjaman, ps.status
          FROM pinjamstudio ps
          LEFT JOIN user u ON u.id=ps.id_user
          LEFT JOIN studio s ON s.id_studio=ps.id_studio
          WHERE ps.id_pinjamstudio=? LIMIT 1";
  
  $stmt = mysqli_prepare($conn, $sql);
  if ($stmt === false) {
    ob_end_clean();
    http_response_code(500);
    exit('Error preparing statement: ' . mysqli_error($conn));
  }
  
  mysqli_stmt_bind_param($stmt, "i", $id); 
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  
  if (!$res || !$res->num_rows) { 
    mysqli_stmt_close($stmt);
    ob_end_clean();
    http_response_code(404); 
    exit('Data peminjaman studio tidak ditemukan.'); 
  }
  $data = $res->fetch_assoc();
  mysqli_stmt_close($stmt);


  $judul = 'Detail Peminjaman Studio';
  $tanggal = fmtDateId($data['tgl_mulai']);
  $waktu   = rangeWaktu($data['waktu_mulai'], $data['waktu_selesai']);
  $statusHtml = statusBadgeHtml($data['status']);
  $picTel = phoneDigits($data['pic_wa'] ?? '');


  $leftSections = [
    'Informasi Umum' => [
      ['ID', '#' . show($data['id_pinjamstudio'])],
      ['Status', $statusHtml],
      ['Studio', '<strong>' . show($data['jenis_studio'] ?? '-') . '</strong>'],
      ['Tanggal', '<strong>' . e($tanggal) . '</strong>'],
      ['Waktu', '<strong>' . e($waktu) . ' WIB</strong>'],
    ],
  ];
  
  $rightSections = [
    'Peminjam' => [
      ['Nama', show($data['nama_lengkap'] ?? '-')],
      ['PIC', show($data['pic_nama'] ?? '-')],
      ['Kontak', $picTel ? '<a class="contact-link" href="tel:'.e($picTel).'">'.e($data['pic_wa']).'</a>' : show($data['pic_wa'] ?? '-')],
    ],
    'Detail' => [
      ['Deskripsi', nl2br(show($data['deskripsi_peminjaman'] ?? '-'))],
    ],
  ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title><?= e($judul) ?> - SIPINJAM</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    /* ========================= Variables & Base ========================= */
    :root{
      --primary:#2563eb; --primary-dark:#1e40af;
      --text:#0f172a; --muted:#6b7280; --gray:#475569;
      --border:#e2e8f0; --bg:#f8fafc; --white:#fff;
    }
    *{margin:0;padding:0;box-sizing:border-box}
    html,body{height:100%}
    body{
      font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;
      color:var(--text); background:var(--bg); font-size:13px; line-height:1.5;
    }


    /* ========================= PAGE: A4 landscape ========================= */
    @page{ size:A4 landscape; margin:12mm; }
    .page{
      width: 297mm; min-height: 210mm;
      margin:0 auto; background:var(--white); border-radius:8px; overflow:hidden;
      display:flex; flex-direction:column;
    }


    /* ========================= Header ========================= */
    .header{
      background:linear-gradient(135deg,var(--primary) 0%,var(--primary-dark) 100%);
      color:#fff; padding:14px 18px;
    }
    .header-row{display:flex;justify-content:space-between;align-items:center}
    .title{font-size:18px;font-weight:700;letter-spacing:-.2px}
    .subtitle{font-size:11px;opacity:.95}
    .date{font-size:11px;opacity:.95}


    /* ========================= Content ========================= */
    .content{padding:16px 18px 0 18px; flex:1; display:flex; flex-direction:column;}
    .grid{
      display:grid; grid-template-columns: 1fr 1fr; gap:20px;
    }
    .section{break-inside:avoid; margin-bottom:14px}
    .section-title{
      font-size:13px; font-weight:700; color:var(--primary);
      padding-bottom:5px; border-bottom:2px solid var(--primary); margin-bottom:10px;
    }
    .row{display:grid; grid-template-columns:110px auto; gap:10px; padding:4px 0; border-bottom:1px solid var(--border);}
    .row:last-child{border-bottom:none}
    .label{font-weight:600;color:var(--gray)}
    .value{color:var(--text)}
    .value strong{color:var(--primary-dark)}


    .status-badge{
      display:inline-block;padding:3px 10px;border-radius:12px;font-weight:700;font-size:11px;
      -webkit-print-color-adjust:exact; print-color-adjust:exact;
    }


    .layout{margin-top:16px;padding-top:12px;border-top:1px dashed var(--border)}
    .layout img{
      max-width:100%;
      max-height:280px;
      border:1px solid var(--border);
      border-radius:6px;
      object-fit:contain;
      display:block;
    }


    .contact-link{color:var(--primary);text-decoration:none}
    .contact-link:hover{text-decoration:underline}


    /* ========================= Footer ========================= */
    .footer{
      margin-top:auto;
      padding:12px 18px 16px 18px;
    }
    .ttd{display:flex;justify-content:center}
    .ttd-box{text-align:center;min-width:220px}
    .ttd-title{font-size:12px;font-weight:600;margin-bottom:42px}
    .ttd-name{display:inline-block;min-width:200px;border-top:2px solid #111;padding-top:6px;font-weight:700;font-size:12px}
    .ttd-role{font-size:10px;color:var(--muted);margin-top:3px}


    /* ========================= Screen helpers ========================= */
    @media screen {
      body{padding:12px}
    }
    
    /* ========================= Print tweaks ========================= */
    @media print{
      body{background:#fff;font-size:12px}
      .page{width:auto;min-height:auto;border-radius:0}
      a[href]:after{content:""}
      
      /* Perbesar gambar layout saat print */
      .layout img{
        max-height:320px;
        page-break-inside:avoid;
      }
    }
  </style>
</head>
<body>
  <div class="page">
    <div class="header">
      <div class="header-row">
        <div>
          <div class="title"><?= e($judul) ?></div>
          <div class="subtitle">Sistem Informasi Peminjaman</div>
        </div>
        <div class="date"><?= date('d/m/Y - H:i') ?> WIB</div>
      </div>
    </div>


    <div class="content">
      <div class="grid">
        <div>
          <?php foreach ($leftSections as $title=>$rows): ?>
            <div class="section">
              <div class="section-title"><?= e($title) ?></div>
              <?php foreach ($rows as [$l,$v]): ?>
                <div class="row">
                  <div class="label"><?= e($l) ?></div>
                  <div class="value"><?= $v ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
        </div>


        <div>
          <?php foreach ($rightSections as $title=>$rows): ?>
            <div class="section">
              <div class="section-title"><?= e($title) ?></div>
              <?php foreach ($rows as [$l,$v]): ?>
                <div class="row">
                  <div class="label"><?= e($l) ?></div>
                  <div class="value"><?= $v ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>


          <?php if ($jenis==='gedung' && $layoutExists): ?>
            <div class="layout">
              <div class="section-title">Layout Ruangan</div>
              <img src="<?= e($layoutPath) ?>" alt="Layout Ruangan">
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>


    <div class="footer">
      <div class="ttd">
        <div class="ttd-box">
          <div class="ttd-title">Admin</div>
          <div class="ttd-name">(............................)</div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
<?php
ob_end_flush();
?>
