<?php
/**
 * helpers.php — Shared utilities for SIPINJAM
 * Include via: require_once __DIR__ . '/helpers.php';
 *
 * All functions wrapped with function_exists() to prevent redeclaration
 * conflicts with inline helpers in legacy PHP files.
 */

/* ─── Format helpers ─── */
if (!function_exists('h')) { function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
if (!function_exists('parse_date')) { function parse_date($s) { if (!$s) return null; $s=trim($s); $fmt=null; if (preg_match('~^\d{4}-\d{2}-\d{2}$~',$s)) $fmt='Y-m-d'; if (preg_match('~^\d{2}/\d{2}/\d{4}$~',$s)) $fmt='d/m/Y'; if (!$fmt) return null; $dt=DateTime::createFromFormat($fmt,$s); return $dt?:null; } }
if (!function_exists('fmt_date')) { function fmt_date($s) { if (!$s) return ''; $dt=DateTime::createFromFormat('Y-m-d',$s); return $dt?$dt->format('d M Y'):h($s); } }
if (!function_exists('fmt_time')) { function fmt_time($s) { if (!$s) return ''; return preg_match('~^\d{2}:\d{2}~',$s)?substr($s,0,5):h($s); } }
if (!function_exists('durasi_jam')) { function durasi_jam($tgl_mulai,$wkt_mulai,$tgl_selesai,$wkt_selesai) { if (!$tgl_mulai||!$wkt_mulai||!$tgl_selesai||!$wkt_selesai) return ''; $start=DateTime::createFromFormat('Y-m-d H:i',"$tgl_mulai $wkt_mulai"); $end=DateTime::createFromFormat('Y-m-d H:i',"$tgl_selesai $wkt_selesai"); if (!$start||!$end||$end<$start) return ''; $diff=$start->diff($end); $label=[]; if($diff->days) $label[]=$diff->days.'h'; $label[]=$diff->h.'j'; if($diff->i) $label[]=$diff->i.'m'; return implode(' ',$label); } }
if (!function_exists('fmt_tgl')) { function fmt_tgl($ymd,$default='-') { if (!$ymd||$ymd==='0000-00-00') return $default; $p=explode('-',$ymd); return count($p)===3?sprintf("%02d-%02d-%s",(int)$p[2],(int)$p[1],$p[0]):$ymd; } }
if (!function_exists('fmt_waktu')) { function fmt_waktu($hms) { return $hms?substr($hms,0,5):'-'; } }
if (!function_exists('label_hari_recurring')) { function label_hari_recurring($s) { if (!$s) return ''; $map=[1=>'Sen',2=>'Sel',3=>'Rab',4=>'Kam',5=>'Jum',6=>'Sab',7=>'Min']; $labels=[]; foreach (array_filter(array_map('trim',explode(',',$s))) as $p) $labels[]=$map[(int)$p]??$p; return implode(', ',$labels); } }
if (!function_exists('yes')) { function yes($v) { return in_array(strtolower(trim((string)$v)),['ya','iya','yes','y','true','1'],true); } }
if (!function_exists('normalize_date_input')) { function normalize_date_input($v) { $v=trim((string)$v); return ($v!==''&&preg_match('/^\d{4}-\d{2}-\d{2}$/',$v))?$v:''; } }

/* ─── Safe SQL quote helpers ─── */
if (!function_exists('qstr')) { function qstr($conn,$s) { return "'".mysqli_real_escape_string($conn,(string)$s)."'"; } }
if (!function_exists('qint')) { function qint($v) { return (int)$v; } }
if (!function_exists('qlike')) { function qlike($conn,$s) { return "'%".mysqli_real_escape_string($conn,strtolower((string)$s))."%'"; } }

/* ─── Prepared statement wrappers ─── */
if (!function_exists('qselect')) { function qselect($conn,$sql,$params=[],$types='') { if (empty($params)) return mysqli_query($conn,$sql)?:false; $stmt=mysqli_prepare($conn,$sql); if (!$stmt) return false; if ($types==='') $types=str_repeat('s',count($params)); mysqli_stmt_bind_param($stmt,$types,...$params); mysqli_stmt_execute($stmt); return mysqli_stmt_get_result($stmt); } }
if (!function_exists('qexec')) { function qexec($conn,$sql,$params=[],$types='') { if (empty($params)) return mysqli_query($conn,$sql); $stmt=mysqli_prepare($conn,$sql); if (!$stmt) return false; if ($types==='') $types=str_repeat('s',count($params)); mysqli_stmt_bind_param($stmt,$types,...$params); return mysqli_stmt_execute($stmt); } }

/* ─── Export helpers ─── */
if (!function_exists('formatDate')) { function formatDate($ymd) { if (empty($ymd)||$ymd==='0000-00-00') return ''; $dt=DateTime::createFromFormat('Y-m-d',$ymd); return $dt?$dt->format('d-m-Y'):$ymd; } }
if (!function_exists('formatTime')) { function formatTime($s) { return $s?substr($s,0,5):''; } }
if (!function_exists('formatJadwalSmart')) { function formatJadwalSmart($tglMulai,$tglSelesai) { $f=function($t){$dt=DateTime::createFromFormat('Y-m-d',$t);return $dt?$dt->format('d-m-Y'):$t;}; return ($tglMulai===$tglSelesai)?$f($tglMulai):$f($tglMulai).' s/d '.$f($tglSelesai); } }
if (!function_exists('mapHariByNumber')) { function mapHariByNumber($nums) { $map=[1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',7=>'Minggu']; $parts=array_filter(array_map('trim',explode(',',(string)$nums))); $labels=[]; foreach ($parts as $p) $labels[]=$map[(int)$p]??$p; return implode(', ',$labels); } }
if (!function_exists('parseRecurringDays')) { function parseRecurringDays($str) { return array_filter(array_map('trim',explode(',',(string)$str))); } }
if (!function_exists('formatPerlengkapanUtama')) { function formatPerlengkapanUtama($d) { $parts=[]; if (isset($d['meja'])&&yes($d['meja'])) $parts[]='Meja '.((int)($d['jumlah_meja']??0)); if (isset($d['kursi'])&&yes($d['kursi'])) $parts[]='Kursi '.((int)($d['jumlah_kursi']??0)); if (isset($d['sound'])&&yes($d['sound'])) $parts[]='Sound'; if (isset($d['proyektor'])&&yes($d['proyektor'])) $parts[]='Proyektor'; return implode(', ',$parts); } }

/* ─── Conflict detection helpers ─── */
if (!function_exists('getOverlappingBookings')) { function getOverlappingBookings($conn,$table,$idCol,$idVal,$tglMulai,$wktMulai,$tglSelesai,$wktSelesai,$excludeId=null) {
    $sql="SELECT * FROM $table WHERE $idCol = ? AND LOWER(TRIM(status)) IN ('menunggu','approve','approved','pending') AND TIMESTAMP(tgl_mulai,waktu_mulai)<TIMESTAMP(?,?) AND TIMESTAMP(tgl_selesai,waktu_selesai)>TIMESTAMP(?,?)";
    $params=[$idVal,$tglSelesai,$wktSelesai,$tglMulai,$wktMulai]; $types=str_repeat('s',count($params));
    if ($excludeId) { $sql.=" AND id_pinjam != ?"; $params[]=$excludeId; $types.='i'; }
    $stmt=mysqli_prepare($conn,$sql); if (!$stmt) return [];
    mysqli_stmt_bind_param($stmt,$types,...$params); mysqli_stmt_execute($stmt);
    $r=mysqli_stmt_get_result($stmt); $result=[]; while($row=mysqli_fetch_assoc($r)) $result[]=$row;
    mysqli_stmt_close($stmt); return $result;
} }
if (!function_exists('cekProximityWaktu')) { function cekProximityWaktu($s1,$e1,$s2,$e2,$threshold=3600) { if (!($e1<=$s2||$s1>=$e2)) return 'overlap'; $gap=max($s1,$s2)-min($e1,$e2); if ($gap>=0&&$gap<$threshold) return 'interval'; return false; } }

/* ─── Build WHERE blocks ─── */
if (!function_exists('buildWhere')) { function buildWhere($alias,$filters,$conn) {
  $w=[]; $APPROVED=['approve','approved','acc','disetujui','setuju','selesai','active','dipinjam']; $PENDING=['menunggu','pending','submitted','waiting']; $status=$filters['status']??'';
  if ($status==='menunggu'||$status==='pending') { $in=array_map(function($s)use($conn){return qstr($conn,$s);},$PENDING); $w[]="LOWER({$alias}.status) IN (".implode(',',$in).")"; }
  elseif ($status==='approve'||$status==='selesai'||$status==='approved') { $in=array_map(function($s)use($conn){return qstr($conn,$s);},$APPROVED); $w[]="LOWER({$alias}.status) IN (".implode(',',$in).")"; }
  elseif ($status!==''&&$status!=='semua') $w[]="LOWER({$alias}.status) = ".qstr($conn,strtolower($status));
  if ($filters['dari_sql']&&$filters['sampai_sql']) $w[]="NOT ({$alias}.tgl_selesai<".qstr($conn,$filters['dari_sql'])." OR {$alias}.tgl_mulai>".qstr($conn,$filters['sampai_sql']).")";
  elseif ($filters['dari_sql']) $w[]="{$alias}.tgl_selesai >= ".qstr($conn,$filters['dari_sql']);
  elseif ($filters['sampai_sql']) $w[]="{$alias}.tgl_mulai <= ".qstr($conn,$filters['sampai_sql']);
  if (!empty($filters['peminjam'])) $w[]="LOWER(user.nama_lengkap) LIKE ".qlike($conn,$filters['peminjam']);
  return $w;
} }
