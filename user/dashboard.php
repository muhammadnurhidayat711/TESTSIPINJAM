<?php
/**
 * Dashboard User SIPINJAM
 * Update: All users can see all bookings in calendar
 * Feature: Click empty date to add booking
 * Made by: Muhammad Nurhidayat
 * Using AI assistant: ChatGPT, Claude AI, Perplexity
 * 2025
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// -------- Helpers --------
function currentusername() {
    return isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : (isset($_SESSION['username']) ? $_SESSION['username'] : 'User');
}

function currentuserid() {
    return isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
}

function nf($n) {
    return number_format((int)$n, 0, ',', '.');
}

function hs($strings) {
    return htmlspecialchars($strings, ENT_QUOTES, 'UTF-8');
}

function scalar($conn, $sql, $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return 0;
    if ($params) {
        $types = '';
        foreach ($params as $p) {
            $types .= is_int($p) ? 'i' : 's';
        }
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $val);
    $val = 0;
    if (mysqli_stmt_fetch($stmt)) {
        mysqli_stmt_close($stmt);
        return (int)$val;
    }
    mysqli_stmt_close($stmt);
    return 0;
}

function pct($free, $total) {
    return $total > 0 ? round(($free / $total) * 100) : 0;
}

$username = currentusername();
$userid   = currentuserid();

// -------- Status Mapping --------
$APPROVED = ['approve', 'approved', 'disetujui', 'acc', 'setuju', 'active', 'dipinjam', 'selesai'];
$PENDINGSTRICT = ['menunggu', 'pending'];
$INAPPROVED = "'" . implode("','", $APPROVED) . "'";
$INPENDINGSTRICT = "'" . implode("','", $PENDINGSTRICT) . "'";

// -------- Statistik User --------
// Sesuaikan nama kolom user ID di setiap tabel (id_user)
$mytotalbarang    = scalar($conn, "SELECT COUNT(*) FROM pinjambarang   WHERE id_user = ?", [$userid]);
$mytotalkendaraan = scalar($conn, "SELECT COUNT(*) FROM pinjamkendaraan WHERE id_user = ?", [$userid]);
$mytotalkolam     = scalar($conn, "SELECT COUNT(*) FROM pinjamkolam    WHERE id_user = ?", [$userid]);
$mytotalstudio    = scalar($conn, "SELECT COUNT(*) FROM pinjamstudio   WHERE id_user = ?", [$userid]);

$mypendingbarang    = scalar($conn, "SELECT COUNT(*) FROM pinjambarang   WHERE id_user = ? AND LOWER(status) IN ($INPENDINGSTRICT)", [$userid]);
$mypendingkendaraan = scalar($conn, "SELECT COUNT(*) FROM pinjamkendaraan WHERE id_user = ? AND LOWER(status) IN ($INPENDINGSTRICT)", [$userid]);
$mypendingkolam     = scalar($conn, "SELECT COUNT(*) FROM pinjamkolam    WHERE id_user = ? AND LOWER(status) IN ($INPENDINGSTRICT)", [$userid]);
$mypendingstudio    = scalar($conn, "SELECT COUNT(*) FROM pinjamstudio   WHERE id_user = ? AND LOWER(status) IN ($INPENDINGSTRICT)", [$userid]);
$totalpendinguser   = $mypendingbarang + $mypendingkendaraan + $mypendingkolam + $mypendingstudio;

$myapprovedbarang    = scalar($conn, "SELECT COUNT(*) FROM pinjambarang   WHERE id_user = ? AND LOWER(status) IN ($INAPPROVED)", [$userid]);
$myapprovedkendaraan = scalar($conn, "SELECT COUNT(*) FROM pinjamkendaraan WHERE id_user = ? AND LOWER(status) IN ($INAPPROVED)", [$userid]);
$myapprovedkolam     = scalar($conn, "SELECT COUNT(*) FROM pinjamkolam    WHERE id_user = ? AND LOWER(status) IN ($INAPPROVED)", [$userid]);
$myapprovedstudio    = scalar($conn, "SELECT COUNT(*) FROM pinjamstudio   WHERE id_user = ? AND LOWER(status) IN ($INAPPROVED)", [$userid]);

// Ketersediaan global
$totalbarang    = scalar($conn, "SELECT COUNT(*) FROM barang");
$totalkendaraan = scalar($conn, "SELECT COUNT(*) FROM kendaraan");
$totalkolam     = scalar($conn, "SELECT COUNT(*) FROM kolam");
$totalstudio    = scalar($conn, "SELECT COUNT(*) FROM studio");

$usedbarang = scalar(
    $conn,
    "SELECT COUNT(DISTINCT id_barang) FROM pinjambarang 
     WHERE id_barang IS NOT NULL 
       AND LOWER(status) IN ($INAPPROVED) 
       AND tgl_mulai <= CURDATE() 
       AND tgl_selesai >= CURDATE()"
);

$usedkendaraan = scalar(
    $conn,
    "SELECT COUNT(DISTINCT id_kendaraan) FROM pinjamkendaraan 
     WHERE id_kendaraan IS NOT NULL 
       AND LOWER(status) IN ($INAPPROVED) 
       AND tgl_mulai <= CURDATE() 
       AND tgl_selesai >= CURDATE()"
);

$usedkolam = scalar(
    $conn,
    "SELECT COUNT(DISTINCT id_kolam) FROM pinjamkolam 
     WHERE id_kolam IS NOT NULL 
       AND LOWER(status) IN ($INAPPROVED) 
       AND DATE(tgl_mulai) = CURDATE()"
);

$usedstudio = scalar(
    $conn,
    "SELECT COUNT(DISTINCT id_studio) FROM pinjamstudio 
     WHERE id_studio IS NOT NULL 
       AND LOWER(status) IN ($INAPPROVED) 
       AND DATE(tgl_mulai) = CURDATE()"
);

$stats = [
    'barangtersedia'    => max(0, $totalbarang - $usedbarang),
    'barangtotal'       => $totalbarang,
    'kendaraantersedia' => max(0, $totalkendaraan - $usedkendaraan),
    'kendaraantotal'    => $totalkendaraan,
    'kolamtersedia'     => max(0, $totalkolam - $usedkolam),
    'kolamtotal'        => $totalkolam,
    'studiotersedia'    => max(0, $totalstudio - $usedstudio),
    'studiototal'       => $totalstudio,
];

// -------- Pending List User (My Bookings Only) --------
$pendinglistquery = "
    SELECT 
        'ruangan' AS type,
        'fas fa-building' AS icon,
        pb.id_pinjam AS id,
        pb.status,
        pb.tgl_mulai   AS tglmulai,
        pb.waktu_mulai AS waktumulai,
        pb.tgl_selesai AS tglselesai,
        pb.waktu_selesai AS waktuselesai,
        IFNULL(b.nama_barang, '-') AS itemname,
        pb.tujuan_barang AS activity
    FROM pinjambarang pb
    LEFT JOIN barang b ON b.id = pb.id_barang
    WHERE pb.id_user = $userid 
      AND pb.id_barang IS NOT NULL 
      AND LOWER(pb.status) IN ($INPENDINGSTRICT)

    UNION ALL

    SELECT 
        'kendaraan' AS type,
        'fas fa-car' AS icon,
        pk.id_pk AS id,
        pk.status,
        pk.tgl_mulai   AS tglmulai,
        pk.waktu_mulai AS waktumulai,
        pk.tgl_selesai AS tglselesai,
        pk.waktu_selesai AS waktuselesai,
        IFNULL(k.nama_kendaraan, '-') AS itemname,
        pk.tujuan AS activity
    FROM pinjamkendaraan pk
    LEFT JOIN kendaraan k ON k.id_kendaraan = pk.id_kendaraan
    WHERE pk.id_user = $userid 
      AND pk.id_kendaraan IS NOT NULL 
      AND LOWER(pk.status) IN ($INPENDINGSTRICT)

    UNION ALL

    SELECT 
        'kolam' AS type,
        'fas fa-water' AS icon,
        pkl.id_pinjamkolam AS id,
        pkl.status,
        pkl.tgl_mulai   AS tglmulai,
        pkl.waktu_mulai AS waktumulai,
        pkl.tgl_mulai   AS tglselesai,
        pkl.waktu_selesai AS waktuselesai,
        IFNULL(kl.jenis_kolam, '-') AS itemname,
        CONCAT('Peminjam ', pkl.nama) AS activity
    FROM pinjamkolam pkl
    LEFT JOIN kolam kl ON kl.id_kolam = pkl.id_kolam
    WHERE pkl.id_user = $userid 
      AND pkl.id_kolam IS NOT NULL 
      AND LOWER(pkl.status) IN ($INPENDINGSTRICT)

    UNION ALL

    SELECT 
        'studio' AS type,
        'fas fa-video' AS icon,
        ps.id_pinjamstudio AS id,
        ps.status,
        ps.tgl_mulai   AS tglmulai,
        ps.waktu_mulai AS waktumulai,
        ps.tgl_mulai   AS tglselesai,
        ps.waktu_selesai AS waktuselesai,
        IFNULL(s.jenis_studio, '-') AS itemname,
        IFNULL(ps.deskripsi_peminjaman, CONCAT('Peminjam ', ps.nama)) AS activity
    FROM pinjamstudio ps
    LEFT JOIN studio s ON s.id_studio = ps.id_studio
    WHERE ps.id_user = $userid 
      AND ps.id_studio IS NOT NULL 
      AND LOWER(ps.status) IN ($INPENDINGSTRICT)

    ORDER BY tglmulai ASC, waktumulai ASC
    LIMIT 10
";
$pendingresult = mysqli_query($conn, $pendinglistquery);
$pendinglist = [];
if ($pendingresult) {
    while ($row = mysqli_fetch_assoc($pendingresult)) {
        $pendinglist[] = $row;
    }
}

// -------- ALL BOOKINGS FOR CALENDAR (ALL USERS - NORMAL & RECURRING) --------
// Sekarang: semua tabel pakai is_recurring & recurring_days
$allbookingsquery = "
    SELECT 
        'ruangan' AS type,
        'fas fa-building' AS icon,
        pb.status,
        pb.tgl_mulai      AS tglmulai,
        pb.waktu_mulai    AS waktumulai,
        pb.tgl_selesai    AS tglselesai,
        pb.waktu_selesai  AS waktuselesai,
        IFNULL(b.nama_barang, '-')            AS itemname,
        IFNULL(u.nama_lengkap, 'Unknown')     AS peminjamnama,
        pb.tujuan_barang                      AS activity,
        IFNULL(pb.is_recurring,   'no')       AS isrecurring,
        IFNULL(pb.recurring_days, '')         AS recurringdays
    FROM pinjambarang pb
    LEFT JOIN barang b ON b.id = pb.id_barang
    LEFT JOIN user u   ON u.id = pb.id_user
    WHERE pb.id_barang IS NOT NULL

    UNION ALL

    SELECT
        'kendaraan' AS type,
        'fas fa-car' AS icon,
        pk.status,
        pk.tgl_mulai      AS tglmulai,
        pk.waktu_mulai    AS waktumulai,
        pk.tgl_selesai    AS tglselesai,
        pk.waktu_selesai  AS waktuselesai,
        IFNULL(k.nama_kendaraan, '-')         AS itemname,
        IFNULL(u.nama_lengkap, 'Unknown')     AS peminjamnama,
        pk.tujuan                             AS activity,
        IFNULL(pk.is_recurring,   'no')       AS isrecurring,
        IFNULL(pk.recurring_days, '')         AS recurringdays
    FROM pinjamkendaraan pk
    LEFT JOIN kendaraan k ON k.id_kendaraan = pk.id_kendaraan
    LEFT JOIN user u      ON u.id = pk.id_user
    WHERE pk.id_kendaraan IS NOT NULL

    UNION ALL

    SELECT
        'kolam' AS type,
        'fas fa-water' AS icon,
        pkl.status,
        pkl.tgl_mulai      AS tglmulai,
        pkl.waktu_mulai    AS waktumulai,
        pkl.tgl_selesai    AS tglselesai,
        pkl.waktu_selesai  AS waktuselesai,
        IFNULL(kl.jenis_kolam, '-')           AS itemname,
        IFNULL(u.nama_lengkap, 'Unknown')     AS peminjamnama,
        CONCAT('Peminjam ', pkl.nama)         AS activity,
        IFNULL(pkl.is_recurring,   'no')      AS isrecurring,
        IFNULL(pkl.recurring_days, '')        AS recurringdays
    FROM pinjamkolam pkl
    LEFT JOIN kolam kl ON kl.id_kolam = pkl.id_kolam
    LEFT JOIN user u   ON u.id = pkl.id_user
    WHERE pkl.id_kolam IS NOT NULL

    UNION ALL

    SELECT
        'studio' AS type,
        'fas fa-video' AS icon,
        ps.status,
        ps.tgl_mulai      AS tglmulai,
        ps.waktu_mulai    AS waktumulai,
        ps.tgl_selesai    AS tglselesai,
        ps.waktu_selesai  AS waktuselesai,
        IFNULL(s.jenis_studio, '-')           AS itemname,
        IFNULL(u.nama_lengkap, 'Unknown')     AS peminjamnama,
        IFNULL(ps.deskripsi_peminjaman, CONCAT('Peminjam ', ps.nama)) AS activity,
        IFNULL(ps.is_recurring,   'no')       AS isrecurring,
        IFNULL(ps.recurring_days, '')         AS recurringdays
    FROM pinjamstudio ps
    LEFT JOIN studio s ON s.id_studio = ps.id_studio
    LEFT JOIN user u   ON u.id = ps.id_user
    WHERE ps.id_studio IS NOT NULL
";


$allbookingsresult = mysqli_query($conn, $allbookingsquery);
$allbookings = [];
if ($allbookingsresult) {
    while ($row = mysqli_fetch_assoc($allbookingsresult)) {
        $isRecurringValue = strtolower(trim($row['isrecurring'] ?? 'no'));
        $isRecurring = in_array($isRecurringValue, ['yes', 'y', '1'], true);

        $allbookings[] = [
            "type"          => $row['type'],
            "icon"          => $row['icon'],
            "status"        => $row['status'],
            "tglmulai"      => $row['tglmulai'],
            "waktumulai"    => $row['waktumulai'],
            "tglselesai"    => $row['tglselesai'],
            "waktuselesai"  => $row['waktuselesai'],
            "itemname"      => $row['itemname'],
            "peminjamnama"  => $row['peminjamnama'],
            "activity"      => $row['activity'],
            "isrecurring"   => $isRecurring,
            "recurringdays" => $row['recurringdays'] ?? '',
        ];
    }
}

$allbookingsjson = json_encode($allbookings);
?>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* HERO - MODERN GRADIENT */
.user-dashboard {
    background: linear-gradient(135deg, #1b91ffff , #126b9eff, #0b639eff, #00579eff);
    min-height: 180px;
    border-radius: 24px;
    color: white;
    position: relative;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(102, 137, 234, 0.3);
    margin-bottom: 2rem;
}

.user-dashboard::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 20% 80%, rgba(255,255,255,0.1) 0%, transparent 50%), 
                radial-gradient(circle at 80% 20%, rgba(255,255,255,0.15) 0%, transparent 50%);
}

.user-dashboard::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,.05) 50%, transparent 70%);
    transform: translateX(-100%);
    animation: shimmer 3s infinite;
}

@keyframes shimmer {
    100% { transform: translateX(100%); }
}

.user-dashboard .content {
    position: relative;
    z-index: 2;
}

.welcome-text {
    font-size: 2.25rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
    letter-spacing: -0.02em;
}

.welcome-subtitle {
    opacity: .9;
    font-size: 1rem;
    font-weight: 400;
}

.section-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #0f172a;
    margin: 2rem 0 1rem 0;
    letter-spacing: -0.01em;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-title::before {
    content: '';
    width: 4px;
    height: 24px;
    background: linear-gradient(135deg, #1b91ffff , #126b9eff, #0b639eff, #00579eff);
    border-radius: 2px;
}

/* TWO COLUMN LAYOUT */
.main-content-grid {
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    gap: 20px;
    margin-bottom: 2rem;
}

/* LEFT: MY PENDING LIST */
.my-pending-section {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.5);
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    max-height: 700px;
    overflow-y: auto;
}

.my-pending-section h3 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.pending-item {
    background: #f8fafc;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 12px;
    border-left: 4px solid #f59e0b;
    transition: all 0.2s ease;
    cursor: pointer;
}

.pending-item:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

.pending-item.ruangan { border-left-color: #10b981; }
.pending-item.kendaraan { border-left-color: #3b82f6; }
.pending-item.kolam { border-left-color: #06b6d4; }
.pending-item.studio { border-left-color: #a855f7; }

.pending-item-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 10px;
}

.pending-item-title {
    font-weight: 700;
    font-size: 1rem;
    color: #0f172a;
}

.pending-badge {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 4px;
}

.pending-item-info {
    display: flex;
    gap: 12px;
    font-size: 0.813rem;
    color: #64748b;
    margin-top: 8px;
}

.pending-item-info i {
    width: 14px;
    text-align: center;
}

/* RIGHT: MINI CALENDAR */
.calendar-mini-section {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.5);
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
}

.calendar-mini-section h3 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.calendar-subtitle {
    font-size: 0.813rem;
    color: #64748b;
    margin-bottom: 16px;
    font-weight: 400;
}

.mini-calendar-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.mini-calendar-controls button {
    background: linear-gradient(135deg, #1b91ffff , #126b9eff, #0b639eff, #00579eff);
    color: white;
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 700;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.mini-calendar-controls button:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(102,126,234,0.4);
}

.mini-calendar-month {
    font-weight: 700;
    font-size: 1.1rem;
    color: #0f172a;
}

.mini-calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 6px;
}

.mini-calendar-day-header {
    text-align: center;
    font-weight: 700;
    color: #64748b;
    padding: 8px 4px;
    font-size: 0.75rem;
    text-transform: uppercase;
}

.mini-calendar-day {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    color: #0f172a;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.mini-calendar-day:hover:not(.other-month) {
    background: #f1f5f9;
    transform: scale(1.05);
}

.mini-calendar-day.other-month {
    color: #cbd5e1;
    cursor: default;
}

.mini-calendar-day.today {
    background: linear-gradient(135deg, #1b91ffff , #126b9eff, #0b639eff, #00579eff);
    color: white;
    font-weight: 700;
}

.mini-calendar-day.has-booking {
    cursor: pointer;
    background: rgba(239, 68, 68, 0.1);
    border-radius: 8px;
    font-weight: 600;
}

.mini-calendar-day.has-booking:hover {
    background: rgba(239, 68, 68, 0.15);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    transform: scale(1.08);
}

.mini-calendar-day.has-recurring {
    background: rgba(168, 85, 247, 0.15);
    border: 2px dashed #a855f7;
}

.mini-calendar-day.has-recurring:hover {
    background: rgba(168, 85, 247, 0.25);
    box-shadow: 0 4px 10px rgba(168, 85, 247, 0.4);
}

.mini-calendar-day.has-recurring::before {
    content: '🔄';
    position: absolute;
    top: 1px;
    left: 1px;
    font-size: 0.6rem;
    z-index: 2;
}

/* EMPTY DAY HOVER EFFECT */
.mini-calendar-day.empty-day:hover {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    transform: scale(1.08);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

.mini-calendar-day.empty-day:hover::after {
    content: '+';
    position: absolute;
    font-size: 1.5rem;
    font-weight: 700;
    opacity: 0.8;
}

.booking-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 800;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.5);
    border: 2.5px solid #fff;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.mini-calendar-day.today .booking-badge {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    box-shadow: 0 2px 8px rgba(251, 191, 36, 0.5);
}

.mini-calendar-day.has-recurring .booking-badge {
    background: linear-gradient(135deg, #a855f7, #9333ea);
}

/* Calendar Legend */
.calendar-legend {
    display: flex;
    gap: 12px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #e2e8f0;
    font-size: 0.75rem;
    color: #64748b;
    flex-wrap: wrap;
}

.calendar-legend span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.legend-box {
    width: 18px;
    height: 18px;
    border-radius: 4px;
    flex-shrink: 0;
}

.legend-box.has-booking {
    background: rgba(239, 68, 68, 0.1);
    border: 2px solid #ef4444;
}

.legend-box.has-recurring {
    background: rgba(168, 85, 247, 0.15);
    border: 2px dashed #a855f7;
}

.legend-box.empty-day {
    background: rgba(16, 185, 129, 0.1);
    border: 2px solid #10b981;
}

/* KETERSEDIAAN */
.availability-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 2rem;
}

.availability-item {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.5);
    border-radius: 16px;
    padding: 1.25rem;
    text-align: center;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.availability-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(135deg, #1b91ffff , #126b9eff, #0b639eff, #00579eff);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.availability-item:hover::before {
    transform: scaleX(1);
}

.availability-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.08);
    border-color: rgba(102, 126, 234, 0.3);
}

.availability-item a {
    text-decoration: none;
    color: inherit;
    display: block;
}

.availability-emoji {
    font-size: 2rem;
    margin-bottom: 0.75rem;
    filter: grayscale(0.2);
}

.availability-label {
    font-size: 0.813rem;
    color: #64748b;
    font-weight: 600;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.availability-number {
    font-size: 2rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1;
}

.availability-subtext {
    font-size: 0.75rem;
    color: #94a3b8;
    margin-top: 0.5rem;
}

.availability-bar {
    width: 100%;
    height: 6px;
    background: #e2e8f0;
    border-radius: 3px;
    margin-top: 0.75rem;
    overflow: hidden;
}

.availability-bar-fill {
    height: 100%;
    background: linear-gradient(135deg, #1b91ffff , #126b9eff, #0b639eff, #00579eff);
    border-radius: 3px;
    transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

/* STATISTIK */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 12px;
    margin-bottom: 2rem;
}

.stat-card {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1rem;
    text-align: center;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.stat-card::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle, rgba(102,126,234,0.1) 0%, transparent 70%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.stat-card:hover::after {
    opacity: 1;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    border-color: #cbd5e1;
}

.stat-label {
    font-size: 0.688rem;
    color: #64748b;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 1.75rem;
    font-weight: 800;
    background: linear-gradient(135deg, #1b91ffff , #126b9eff, #0b639eff, #00579eff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* MODAL */
.modal-calendar {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    backdrop-filter: blur(4px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 20px;
    overflow-y: auto;
}

.modal-calendar.show {
    display: flex;
}

.modal-content-cal {
    background: #fff;
    border-radius: 24px;
    padding: 30px;
    max-width: 700px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.modal-header-cal {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 15px;
}

.modal-header-cal h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #0f172a;
}

.modal-close-cal {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #94a3b8;
    transition: all 0.2s ease;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-close-cal:hover {
    color: #0f172a;
    background: #f1f5f9;
}

.booking-detail-card {
    background: #f8fafc;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 12px;
    border-left: 4px solid #667eea;
    transition: all 0.2s ease;
}

.booking-detail-card:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.booking-detail-card.ruangan { border-left-color: #10b981; }
.booking-detail-card.kendaraan { border-left-color: #3b82f6; }
.booking-detail-card.kolam { border-left-color: #06b6d4; }
.booking-detail-card.studio { border-left-color: #a855f7; }

.booking-detail-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 10px;
    flex-wrap: wrap;
    gap: 8px;
}

.booking-detail-title {
    font-weight: 700;
    font-size: 1rem;
    color: #0f172a;
    margin-bottom: 4px;
}

.booking-status-badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    white-space: nowrap;
}

.booking-status-badge.approve {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.booking-status-badge.menunggu {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.recurring-badge-inline {
    background: linear-gradient(135deg, #a855f7, #9333ea);
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.65rem;
    margin-left: 6px;
}

/* MODAL UNTUK PILIHAN PEMINJAMAN */
.modal-add-booking {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    backdrop-filter: blur(4px);
    z-index: 1001;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-add-booking.show {
    display: flex;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-add-content {
    background: #fff;
    border-radius: 24px;
    padding: 30px;
    max-width: 500px;
    width: 100%;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from { transform: translateY(30px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.modal-add-header {
    text-align: center;
    margin-bottom: 24px;
}

.modal-add-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 8px;
}

.modal-add-header p {
    font-size: 0.875rem;
    color: #64748b;
}

.modal-add-date {
    background: linear-gradient(135deg, #1b91ffff , #126b9eff, #0b639eff, #00579eff);
    color: white;
    padding: 12px;
    border-radius: 12px;
    text-align: center;
    font-weight: 700;
    font-size: 1.1rem;
    margin-bottom: 24px;
}

.booking-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 20px;
}

.booking-option-btn {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: center;
}

.booking-option-btn:hover {
    background: #f1f5f9;
    border-color: #1b91ffff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(27, 145, 255, 0.2);
}

.booking-option-btn i {
    font-size: 2rem;
    display: block;
    margin-bottom: 8px;
}

.booking-option-btn.ruangan i { color: #10b981; }
.booking-option-btn.kendaraan i { color: #3b82f6; }
.booking-option-btn.kolam i { color: #06b6d4; }
.booking-option-btn.studio i { color: #a855f7; }

.booking-option-btn .label {
    font-weight: 700;
    color: #0f172a;
    font-size: 0.95rem;
}

.modal-add-footer {
    display: flex;
    justify-content: center;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

.modal-cancel-btn {
    background: #e2e8f0;
    color: #64748b;
    border: none;
    padding: 12px 32px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 700;
    transition: all 0.2s ease;
}

.modal-cancel-btn:hover {
    background: #cbd5e1;
    color: #0f172a;
}

.app-footer-mini {
    text-align: center;
    padding: 1.5rem 0.5rem;
    opacity: .7;
    font-size: 0.875rem;
}

@media (max-width: 992px) {
    .main-content-grid {
        grid-template-columns: 1fr;
    }
    .availability-grid, .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    }
    .user-dashboard {
        min-height: 140px;
    }
    .welcome-text {
        font-size: 1.5rem;
    }
    .booking-options {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="page-inner">
    <!-- HERO SECTION -->
    <div class="user-dashboard mb-4">
        <div class="content p-4">
            <div class="row align-items-center">
                <div class="col-md-9">
                    <h1 class="welcome-text">Halo, <?php echo hs($username) ?>!</h1>
                    <p class="welcome-subtitle">Selamat datang di SIPINJAM, Have A Nice Day</p>
                </div>
            </div>
        </div>
    </div>

    <!-- TWO COLUMN LAYOUT: MY PENDING + CALENDAR -->
    <div class="main-content-grid">
        <!-- RIGHT: MINI CALENDAR -->
        <div class="calendar-mini-section">
            <h3><i class="fas fa-calendar-alt"></i> Kalender Semua Peminjaman</h3>
            <p class="calendar-subtitle">Klik tanggal kosong untuk menambah peminjaman baru</p>
            <div class="mini-calendar-controls">
                <button onclick="prevMonth()"><i class="fas fa-chevron-left"></i></button>
                <span class="mini-calendar-month" id="miniMonth">November 2025</span>
                <button onclick="nextMonth()"><i class="fas fa-chevron-right"></i></button>
            </div>
            <div class="mini-calendar-grid" id="miniCalendar"></div>
            
            <!-- LEGENDA -->
            <div class="calendar-legend">
                <span><div class="legend-box has-booking"></div> Jadwal Biasa</span>
                <span><div class="legend-box has-recurring"></div> Jadwal Rutin</span>
                <span><div class="legend-box empty-day"></div> Tambah Peminjaman</span>
            </div>
        </div>

        <!-- LEFT: MY PENDING LIST -->
        <div class="my-pending-section">
            <h3><i class="fas fa-hourglass-half"></i> Peminjaman Saya - Menunggu (<?php echo nf($totalpendinguser) ?>)</h3>
            <div>
                <?php if (empty($pendinglist)) { ?>
                    <div style="text-align: center; padding: 40px 20px; color: #94a3b8;">
                        <i class="fas fa-check-circle" style="font-size: 3rem; margin-bottom: 12px; opacity: 0.3;"></i>
                        <p style="color: #64748b; font-size: 0.9rem;">Tidak ada peminjaman yang menunggu persetujuan</p>
                    </div>
                <?php } else { ?>
                    <?php foreach($pendinglist as $item) { ?>
                        <div class="pending-item <?php echo hs($item['type']) ?>">
                            <div class="pending-item-header">
                                <div class="pending-item-title">
                                    <i class="<?php echo hs($item['icon']) ?>"></i> <?php echo hs($item['itemname']) ?>
                                </div>
                                <span class="pending-badge">
                                    <i class="fas fa-hourglass-half"></i> Pending
                                </span>
                            </div>
                            <div class="pending-item-info">
                                <span><i class="fas fa-calendar"></i> <?php echo hs($item['tglmulai']) ?></span>
                                <span><i class="fas fa-clock"></i> <?php echo hs($item['waktumulai']) ?></span>
                            </div>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- KETERSEDIAAN SAAT INI -->
    <h3 class="section-title">Ketersediaan Fasilitas Real-time</h3>
    <div class="availability-grid">
        <?php
        $avail = [
            ['emoji'=>'🏢','label'=>'Ruangan','free'=>$stats['barangtersedia'],'total'=>$stats['barangtotal'],'link'=>'?view=datapinjambarang'],
            ['emoji'=>'🚗','label'=>'Kendaraan','free'=>$stats['kendaraantersedia'],'total'=>$stats['kendaraantotal'],'link'=>'?view=datapinjamkendaraan'],
            ['emoji'=>'🏊','label'=>'Kolam','free'=>$stats['kolamtersedia'],'total'=>$stats['kolamtotal'],'link'=>'?view=datapinjamkolam'],
            ['emoji'=>'🎥','label'=>'Studio','free'=>$stats['studiotersedia'],'total'=>$stats['studiototal'],'link'=>'?view=datapinjamstudio'],
        ];
        foreach ($avail as $a) {
            $p = pct($a['free'], $a['total']);
        ?>
            <a href="<?php echo hs($a['link']) ?>">
                <div class="availability-item">
                    <div class="availability-emoji"><?php echo $a['emoji'] ?></div>
                    <div class="availability-label"><?php echo hs($a['label']) ?></div>
                    <div class="availability-number"><?php echo nf($a['free']) ?></div>
                    <div class="availability-subtext">dari <?php echo nf($a['total']) ?> tersedia</div>
                    <div class="availability-bar">
                        <div class="availability-bar-fill" style="width: <?php echo $p ?>%"></div>
                    </div>
                </div>
            </a>
        <?php } ?>
    </div>

    <!-- STATISTIK PEMINJAMAN SAYA -->
    <h3 class="section-title">Statistik Peminjaman Saya</h3>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Ruangan</div>
            <div class="stat-value"><?php echo nf($mytotalbarang) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Kendaraan</div>
            <div class="stat-value"><?php echo nf($mytotalkendaraan) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Kolam</div>
            <div class="stat-value"><?php echo nf($mytotalkolam) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Studio</div>
            <div class="stat-value"><?php echo nf($mytotalstudio) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Pending</div>
            <div class="stat-value"><?php echo nf($totalpendinguser) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Disetujui Ruangan</div>
            <div class="stat-value"><?php echo nf($myapprovedbarang) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Disetujui Kendaraan</div>
            <div class="stat-value"><?php echo nf($myapprovedkendaraan) ?></div>
        </div>
    </div>

    <div class="app-footer-mini">
        <p style="margin:0;"><b>&copy; 2025 SIPINJAM</b> - Sistem Informasi Peminjaman Pelita Cemerlang School</p>
    </div>
</div>

<!-- MODAL: DETAIL BOOKINGS -->
<div class="modal-calendar" id="bookingModal">
    <div class="modal-content-cal">
        <div class="modal-header-cal">
            <h2 id="modalTitle">Detail Peminjaman</h2>
            <button class="modal-close-cal" onclick="closeModal()">×</button>
        </div>
        <div id="modalBody"></div>
    </div>
</div>

<!-- MODAL: PILIHAN TAMBAH PEMINJAMAN -->
<div class="modal-add-booking" id="addBookingModal">
    <div class="modal-add-content">
        <div class="modal-add-header">
            <h2><i class="fas fa-plus-circle" style="color: #10b981;"></i> Tambah Peminjaman</h2>
            <p>Pilih jenis peminjaman yang ingin Anda buat</p>
        </div>
        <div class="modal-add-date" id="selectedDateDisplay"></div>
        <div class="booking-options">
            <div class="booking-option-btn ruangan" onclick="redirectToForm('ruangan')">
                <i class="fas fa-building"></i>
                <div class="label">Ruangan</div>
            </div>
            <div class="booking-option-btn kendaraan" onclick="redirectToForm('kendaraan')">
                <i class="fas fa-car"></i>
                <div class="label">Kendaraan</div>
            </div>
            <div class="booking-option-btn kolam" onclick="redirectToForm('kolam')">
                <i class="fas fa-swimming-pool"></i>
                <div class="label">Kolam</div>
            </div>
            <div class="booking-option-btn studio" onclick="redirectToForm('studio')">
                <i class="fas fa-video"></i>
                <div class="label">Studio</div>
            </div>
        </div>
        <div class="modal-add-footer">
            <button class="modal-cancel-btn" onclick="closeAddModal()">Batal</button>
        </div>
    </div>
</div>

<script>
const bookings = <?php echo $allbookingsjson ?>;
let currentDate = new Date();
let selectedDate = null;

const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const dayNames   = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
const dayNameMap = {
    1: 'Senin',
    2: 'Selasa',
    3: 'Rabu',
    4: 'Kamis',
    5: 'Jumat',
    6: 'Sabtu',
    7: 'Minggu'
};

function jsToDayNumber(jsDay) {
    // JS: Minggu = 0 ... Sabtu = 6
    // Kita pakai: Senin = 1 ... Minggu = 7
    return jsDay === 0 ? 7 : jsDay;
}

console.log('📊 Total Bookings (User):', bookings.length);
console.log('🔄 Recurring Bookings (User):', bookings.filter(b => b.isrecurring).length);

/**
 * ============================================
 *  LOGIKA BACA KALENDER (SAMA DENGAN ADMIN)
 *  - Semua booking (rutin & biasa) dibatasi
 *    oleh range tglmulai - tglselesai
 *  - Rutin hanya muncul di hari yang sesuai
 *    recurringdays (1–7)
 * ============================================
 */
function getBookingsForDate(dateStr, dateObj) {
    const checkDate = new Date(dateStr);
    const dayNumber = jsToDayNumber(dateObj.getDay());

    return bookings.filter(b => {
        if (!b.tglmulai) return false;

        const start = new Date(b.tglmulai.split(' ')[0]);
        const end   = b.tglselesai ? new Date(b.tglselesai.split(' ')[0]) : start;
        const inRange = checkDate >= start && checkDate <= end;

        // Kalau bukan rutin → cukup cek di range tanggal saja
        if (!b.isrecurring) return inRange;

        // Kalau rutin → harus di dalam range + cocok hari-nya
        if (!inRange) return false;
        if (!b.recurringdays) {
            console.warn('Recurring tanpa recurringdays:', b.itemname);
            return false;
        }

        const allowedDays = b.recurringdays
            .split(',')
            .map(d => parseInt(d.trim()))
            .filter(n => !isNaN(n));

        return allowedDays.includes(dayNumber);
    });
}


function renderMiniCalendar() {
    const year  = currentDate.getFullYear();
    const month = currentDate.getMonth();
    document.getElementById('miniMonth').textContent = `${monthNames[month]} ${year}`;

    const firstDay       = new Date(year, month, 1).getDay();
    const daysInMonth    = new Date(year, month + 1, 0).getDate();
    const daysInPrevMonth= new Date(year, month, 0).getDate();

    const grid = document.getElementById('miniCalendar');
    grid.innerHTML = '';

    // Header hari
    dayNames.forEach(day => {
        const header = document.createElement('div');
        header.className = 'mini-calendar-day-header';
        header.textContent = day;
        grid.appendChild(header);
    });

    // Hari bulan sebelumnya (abu-abu)
    for (let i = firstDay - 1; i >= 0; i--) {
        const day = document.createElement('div');
        day.className = 'mini-calendar-day other-month';
        day.textContent = daysInPrevMonth - i;
        grid.appendChild(day);
    }

    const today = new Date();

    // Hari bulan ini
    for (let i = 1; i <= daysInMonth; i++) {
        const day = document.createElement('div');
        day.className = 'mini-calendar-day';

        const isToday = (
            i === today.getDate() &&
            month === today.getMonth() &&
            year === today.getFullYear()
        );
        if (isToday) day.classList.add('today');

        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
        const date    = new Date(year, month, i);
        const dayBookings = getBookingsForDate(dateStr, date);

        const normalBookings    = dayBookings.filter(b => !b.isrecurring);
        const recurringBookings = dayBookings.filter(b =>  b.isrecurring);

        if (normalBookings.length > 0)  day.classList.add('has-booking');
        if (recurringBookings.length > 0) day.classList.add('has-recurring');

        if (dayBookings.length > 0) {
            // Ada booking (biasa/rutin) → tampilkan angka + badge jumlah booking
            const dayContent = document.createElement('div');
            dayContent.style.position = 'relative';
            dayContent.style.zIndex = '1';
            dayContent.textContent = i;
            day.appendChild(dayContent);

            const badge = document.createElement('div');
            badge.className = 'booking-badge';
            badge.textContent = dayBookings.length;
            day.appendChild(badge);

            day.onclick = () => showModal(dateStr, dayBookings);
        } else {
            // Tidak ada booking → bisa tambah peminjaman baru
            day.classList.add('empty-day');
            day.textContent = i;
            day.onclick = () => showAddBookingModal(dateStr);
        }

        grid.appendChild(day);
    }

    // Hari bulan berikutnya (untuk melengkapi grid)
    const totalCells   = 7 + firstDay + daysInMonth;
    const remainingDays= Math.ceil(totalCells / 7) * 7 - totalCells;
    for (let i = 1; i <= remainingDays; i++) {
        const day = document.createElement('div');
        day.className = 'mini-calendar-day other-month';
        day.textContent = i;
        grid.appendChild(day);
    }
}

function showModal(dateStr, bookingsForDay) {
    const modal  = document.getElementById('bookingModal');
    const title  = document.getElementById('modalTitle');
    const body   = document.getElementById('modalBody');
    const date   = new Date(dateStr);
    const formattedDate = `${date.getDate()} ${monthNames[date.getMonth()]} ${date.getFullYear()}`;

    title.textContent = `Peminjaman pada ${formattedDate}`;

    if (bookingsForDay.length === 0) {
        body.innerHTML = '<p style="text-align: center; color: #94a3b8; padding: 30px;">Tidak ada peminjaman</p>';
    } else {
        body.innerHTML = bookingsForDay.map(b => {
            const statusLower = (b.status || '').toLowerCase();
            const statusClass = (statusLower === 'menunggu' || statusLower === 'pending') ? 'menunggu' : 'approve';
            const recurringInfo = b.isrecurring && b.recurringdays
                ? `<div style="font-size: 0.75rem; color: #a855f7; margin-top: 6px;">
                        <i class="fas fa-redo"></i> Setiap ${formatRecurringDays(b.recurringdays)}
                   </div>`
                : '';

            return `
                <div class="booking-detail-card ${b.type}">
                    <div class="booking-detail-header">
                        <div>
                            <div class="booking-detail-title">
                                <i class="${b.icon}"></i> ${b.itemname}
                                ${b.isrecurring ? '<span class="recurring-badge-inline">🔄 Rutin</span>' : ''}
                            </div>
                            <div style="font-size: 0.75rem; color: #8b5cf6; font-weight: 600; margin-top: 4px;">
                                <i class="fas fa-user"></i> Peminjam: ${b.peminjamnama}
                            </div>
                            <div style="font-size: 0.813rem; color: #64748b; margin-top: 4px;">${b.activity || ''}</div>
                        </div>
                        <div>
                            <span class="booking-status-badge ${statusClass}">${b.status}</span>
                        </div>
                    </div>
                    <div style="font-size: 0.813rem; color: #64748b; margin-top: 8px;">
                        <i class="fas fa-clock"></i>
                        ${(b.waktumulai   || '00:00').slice(0,5)} - ${(b.waktuselesai || '23:59').slice(0,5)}
                    </div>
                    ${recurringInfo}
                </div>
            `;
        }).join('');
    }

    modal.classList.add('show');
}

function showAddBookingModal(dateStr) {
    selectedDate = dateStr;
    const modal       = document.getElementById('addBookingModal');
    const dateDisplay = document.getElementById('selectedDateDisplay');
    const date        = new Date(dateStr);
    const formattedDate = `${date.getDate()} ${monthNames[date.getMonth()]} ${date.getFullYear()}`;

    dateDisplay.textContent = formattedDate;
    modal.classList.add('show');
}

function closeAddModal() {
    document.getElementById('addBookingModal').classList.remove('show');
    selectedDate = null;
}

function redirectToForm(type) {
    if (!selectedDate) {
        alert('Tanggal tidak valid');
        return;
    }

    const formUrls = {
        'ruangan':  '?view=createpinjambarang',
        'kendaraan':'?view=createpinjamkendaraan',
        'kolam':    '?view=createpinjamkolam',
        'studio':   '?view=createpinjamstudio'
    };

    const url = formUrls[type];
    if (url) {
        window.location.href = `${url}&date=${selectedDate}`;
    } else {
        alert('Jenis peminjaman tidak valid');
    }
}

function formatRecurringDays(daysStr) {
    if (!daysStr) return '-';
    return daysStr
        .split(',')
        .map(d => dayNameMap[parseInt(d.trim(), 10)] || d)
        .join(', ');
}

function closeModal() {
    document.getElementById('bookingModal').classList.remove('show');
}

function prevMonth() {
    currentDate.setMonth(currentDate.getMonth() - 1);
    renderMiniCalendar();
}

function nextMonth() {
    currentDate.setMonth(currentDate.getMonth() + 1);
    renderMiniCalendar();
}

// Close modal jika klik overlay
document.getElementById('bookingModal').onclick = (e) => {
    if (e.target.id === 'bookingModal') closeModal();
};

document.getElementById('addBookingModal').onclick = (e) => {
    if (e.target.id === 'addBookingModal') closeAddModal();
};

// ESC untuk tutup modal
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeModal();
        closeAddModal();
    }
});

// Initial render
renderMiniCalendar();
</script>

