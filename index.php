<?php
// index.php — Login full-bleed tanpa header/footer
session_start();
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}



$alertParam = $_GET['alert'] ?? '';
$alertMsg = ''; $alertClass = '';
switch ($alertParam) {
    case 'empty_input':        $alertMsg='Harap isi username dan password!'; $alertClass='alert alert-danger'; break;
    case 'gagal':              $alertMsg='Username atau password salah!';    $alertClass='alert alert-danger'; break;
    case 'not_logged_in':      $alertMsg='Anda harus login terlebih dahulu!';$alertClass='alert alert-danger'; break;
    case 'unauthorized_access':$alertMsg='Anda tidak memiliki akses ke halaman ini!'; $alertClass='alert alert-danger'; break;
    case 'success_logout':     $alertMsg='Anda telah berhasil logout.';      $alertClass='alert alert-success'; break;
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login - SIPINJAM</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">



  <style>
  :root {
    --blue-main:   #3b82f6;
    --blue-dark:   #1e40af;
    --blue-soft:   #bfdbfe;
    --white-soft:  rgba(255,255,255,0.85);
    --glass-bg:    rgba(9, 87, 110, 0.07);
    --glass-border:rgba(255, 255, 255, 0.2);
  }



  * {
    margin: 0; padding: 0;
    box-sizing: border-box;
  }



  body {
    font-family: 'Segoe UI', Roboto, sans-serif;
  }



  /* ===== Background FOTO tetap dipertahankan ===== */
  .page-auth {
    min-height: 100vh;
    width: 100%;
    display: grid;
    place-items: center;
    padding: 20px;
    position: relative;
    overflow: hidden;
    background: url("assets/img/icon2.jpeg") center/cover no-repeat fixed;
  }



  /* Overlay biru-putih lembut */
  .page-auth::before {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(
      135deg,
      rgba(49, 187, 241, 0.62),
      rgba(255,255,255,0.35),
      rgba(49, 187, 241, 0.62)
    );
    backdrop-filter: blur(1px);
    z-index: 1;
  }



  /* ===== LIQUID GLASS MORPHISM EFFECT (STATIC - NO ANIMATION) ===== */
  .liquid-glass {
    position: absolute;
    inset: 0;
    z-index: 2;
    pointer-events: none;
    overflow: hidden;
  }



  /* Blob liquid (STATIC) */
  .liquid-blob {
    position: absolute;
    border-radius: 40% 60% 70% 30% / 40% 50% 60% 50%;
    filter: blur(80px);
    opacity: 0.7;
  }



  .liquid-blob:nth-child(1) {
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(59, 130, 246, 0.6), rgba(96, 165, 250, 0.3));
    top: -20%;
    left: -10%;
  }



  .liquid-blob:nth-child(2) {
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(147, 197, 253, 0.5), rgba(191, 219, 254, 0.3));
    bottom: -15%;
    right: -5%;
  }



  .liquid-blob:nth-child(3) {
    width: 450px;
    height: 450px;
    background: radial-gradient(circle, rgba(96, 165, 250, 0.6), rgba(59, 130, 246, 0.3));
    top: 40%;
    left: 50%;
  }



  /* Efek shimmer (REMOVED) */
  .liquid-glass::after {
    content: "";
    position: absolute;
    width: 150%;
    height: 150%;
    top: -25%;
    left: -25%;
    background: linear-gradient(
      45deg,
      transparent 30%,
      rgba(255, 255, 255, 0.15) 50%,
      transparent 70%
    );
  }



  /* ===== Card Glass (NO ANIMATION) ===== */
  .auth-card {
    width: 100%;
    max-width: 420px;
    z-index: 10;
    padding: 36px 32px 30px;
    border-radius: 22px;



    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    box-shadow: 0 15px 40px rgba(0,0,0,0.25);



    backdrop-filter: blur(22px) saturate(180%);
    -webkit-backdrop-filter: blur(22px) saturate(180%);
  }



  /* Logo */
  .logo-container {
    width: 88px;
    height: 88px;
    margin: 0 auto 20px;
    position: relative;
  }



  .auth-logo {
    width: 100%; height: 100%;
    object-fit: contain;
    border-radius: 50%;
    background: #ffffff;
    padding: 14px;
    box-shadow: 0 4px 25px rgba(59,130,246,0.5);
  }



  /* ===== ANIMASI WHAT'S NEW BADGE (SMOOTH & ATTRACTIVE) ===== */
  .whats-new-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.5);
    border: 3px solid white;
    z-index: 15;
    
    /* ANIMASI UTAMA - SMOOTH */
    animation: badgePulse 2s ease-in-out infinite, 
               badgeGlow 1.5s ease-in-out infinite;
    
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }



  .whats-new-badge:hover {
    transform: scale(1.2) rotate(15deg);
    box-shadow: 0 6px 25px rgba(239, 68, 68, 0.9);
    animation: badgeBounce 0.6s ease-in-out infinite;
  }



  .whats-new-badge:active {
    transform: scale(1.05);
  }



  /* Pulse - Membesar mengecil (SMOOTH) */
  @keyframes badgePulse {
    0%, 100% { 
      transform: scale(1); 
    }
    50% { 
      transform: scale(1.1); 
    }
  }



  /* Glow - Efek cahaya berkedip (SMOOTH) */
  @keyframes badgeGlow {
    0%, 100% { 
      box-shadow: 0 4px 15px rgba(239, 68, 68, 0.5); 
    }
    50% { 
      box-shadow: 0 4px 30px rgba(239, 68, 68, 0.9), 
                  0 0 35px rgba(239, 68, 68, 0.6); 
    }
  }



  /* Bounce - Melompat saat hover */
  @keyframes badgeBounce {
    0%, 100% { 
      transform: scale(1.2) rotate(15deg) translateY(0); 
    }
    50% { 
      transform: scale(1.2) rotate(15deg) translateY(-5px); 
    }
  }



  /* Icon bintang di dalam badge */
  .whats-new-badge i {
    animation: starTwinkle 1.2s ease-in-out infinite;
  }



  /* Twinkle - Efek berkedip pada icon (SUBTLE) */
  @keyframes starTwinkle {
    0%, 100% { 
      opacity: 1; 
      transform: scale(1); 
    }
    50% { 
      opacity: 0.8; 
      transform: scale(1.15); 
    }
  }



  /* Judul */
  .auth-title {
    text-align: center;
    font-size: 28px;
    font-weight: 700;
    background: linear-gradient(to right, #000000ff, #0060adff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 6px;
  }



  .auth-subtitle {
    text-align: center;
    color: #001d35ff;
    font-size: 13px;
    margin-bottom: 22px;
    text-shadow: 0 1px 3px rgba(0,0,0,0.25);
  }



  /* Alerts */
  .alert {
    padding: 12px 14px;
    border-radius: 10px;
    font-size: 14px;
    margin-bottom: 18px;
    backdrop-filter: blur(10px);
  }
  .alert-danger{
    background: rgba(239,68,68,0.25);
    color: #fff;
    border: 1px solid rgba(239,68,68,0.35);
  }
  .alert-success{
    background: rgba(34,197,94,0.25);
    color: #fff;
    border: 1px solid rgba(34,197,94,0.35);
  }



  /* Form */
  label {
    font-size: 13px;
    font-weight: 600;
    color: #00325aff;
    margin-bottom: 6px;
    display: block;
  }



  .form-control {
    width: 100%;
    padding: 12px 14px;
    border-radius: 12px;
    background: rgba(255,255,255,0.18);
    border: 1px solid rgba(255,255,255,0.35);
    color: #0060adff;
    outline: none;
    transition: 0.25s ease;
  }



  .form-control::placeholder { color: rgba(0, 110, 161, 0.55); }



  .form-control:focus {
    border-color: #0060adff;
    background: rgba(255,255,255,0.28);
    box-shadow: 0 0 0 4px rgba(59,130,246,0.25);
  }



  /* Button */
  .btn-login {
    width: 100%;
    padding: 14px 16px;
    border-radius: 12px;
    background: linear-gradient(135deg, #3b82f6, #60a5fa);
    border: none;
    color: #ffffffff;
    font-weight: 700;
    font-size: 15px;
    cursor: pointer;
    transition: 0.25s ease;
  }



  .btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 25px rgba(59,130,246,0.45);
  }



  /* Link */
  .auth-links {
    margin-top: 18px;
    text-align: center;
  }
  .auth-links a {
    font-weight: 600;
    color: #f8fafc;
    padding: 8px 14px;
    background: rgba(255,255,255,0.18);
    border-radius: 8px;
    backdrop-filter: blur(6px);
    text-decoration: none;
    transition: 0.25s;
  }
  .auth-links a:hover {
    background: rgba(255,255,255,0.28);
  }



  /* ===== MODAL WHAT'S NEW (NO ANIMATION) ===== */
  .modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(8px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }



  .modal-overlay.show {
    display: flex;
  }



  .modal-content {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 20px;
    max-width: 600px;
    width: 100%;
    max-height: 85vh;
    overflow-y: auto;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);
    position: relative;
  }



  .modal-header {
    background: linear-gradient(135deg, #3b82f6, #60a5fa);
    padding: 24px;
    border-radius: 20px 20px 0 0;
    position: relative;
    overflow: hidden;
  }



  .modal-header::before {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.2) 50%, transparent 70%);
  }



  .modal-header h2 {
    color: white;
    font-size: 24px;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
    z-index: 2;
  }



  .modal-close {
    position: absolute;
    top: 20px;
    right: 20px;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: 0.2s ease;
    z-index: 3;
  }



  .modal-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
  }



  .modal-body {
    padding: 24px;
  }



  .changelog-section {
    margin-bottom: 24px;
  }



  .changelog-version {
    background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
    border-left: 4px solid #3b82f6;
    border-radius: 12px;
    padding: 18px;
    margin-bottom: 20px;
  }



  .version-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
    flex-wrap: wrap;
    gap: 8px;
  }



  .version-number {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
  }



  .version-date {
    font-size: 11px;
    font-weight: 700;
    color: #3b82f6;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background: rgba(59, 130, 246, 0.1);
    padding: 4px 10px;
    border-radius: 6px;
  }



  .changelog-list {
    list-style: none;
    padding: 0;
    margin: 12px 0 0 0;
  }



  .changelog-list li {
    padding: 8px 0 8px 24px;
    position: relative;
    font-size: 13px;
    color: #475569;
    line-height: 1.6;
  }



  .changelog-list li::before {
    content: "✓";
    position: absolute;
    left: 0;
    color: #10b981;
    font-weight: 700;
    font-size: 16px;
  }



  .badge-category {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    margin-right: 8px;
  }



  .badge-new {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
  }



  .badge-fix {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
  }



  .badge-improve {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    color: white;
  }



  .footer-credits {
    background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
    padding: 20px 24px;
    border-radius: 0 0 20px 20px;
    border-top: 1px solid #e2e8f0;
  }



  .credits-content {
    text-align: center;
  }



  .credits-title {
    font-size: 12px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
  }



  .credits-team {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 6px;
  }



  .credits-copyright {
    font-size: 11px;
    color: #64748b;
    margin: 0;
  }



  /* Responsive */
  @media (max-width: 480px) {
    .modal-content {
      margin: 20px;
    }
    
    .modal-header h2 {
      font-size: 20px;
    }



    .version-number {
      font-size: 16px;
    }



    .changelog-list li {
      font-size: 12px;
    }
  }



  </style>
</head>
<body>



<div class="page-auth">



  <!-- Liquid Glass Morphism Effect (STATIC) -->
  <div class="liquid-glass">
    <div class="liquid-blob"></div>
    <div class="liquid-blob"></div>
    <div class="liquid-blob"></div>
  </div>



  <!-- Card -->
  <div class="auth-card">
    <div class="logo-container">
      <img src="assets/img/icon.png" alt="Logo" class="auth-logo">
      <!-- What's New Badge -->
      <div class="whats-new-badge" onclick="openWhatsNew()" title="Lihat pembaruan terbaru">
        <i class="fas fa-star"></i>
      </div>
    </div>



    <h1 class="auth-title">SIPINJAM</h1>
    <p class="auth-subtitle">Sistem Informasi Peminjaman Pelita Cemerlang School</p>



    <?php if($alertMsg): ?>
      <div class="<?= $alertClass ?>"><?= htmlspecialchars($alertMsg) ?></div>
    <?php endif; ?>



    <form method="POST" action="cek_login.php" onsubmit="return validateForm()">
      <label>Username</label>
      <input type="text" name="username" maxlength="15" class="form-control" placeholder="Masukkan username" required>



      <label style="margin-top: 16px;">Password</label>
      <input type="password" name="password" maxlength="15" class="form-control" placeholder="Masukkan password" required>



      <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">



      <button class="btn-login" type="submit" style="margin-top: 20px;">Login</button>
    </form>



    <div class="auth-links">
      <a href="data.php">Data Peminjaman</a>
    </div>
  </div>



</div>



<!-- Modal What's New -->
<div class="modal-overlay" id="whatsNewModal" onclick="closeWhatsNew(event)">
  <div class="modal-content" onclick="event.stopPropagation()">
    <div class="modal-header">
      <h2>
        <i class="fas fa-rocket"></i>
        Changelog & Update SIPINJAM
      </h2>
      <button class="modal-close" onclick="closeWhatsNew()">
        <i class="fas fa-times"></i>
      </button>
    </div>
    
    <div class="modal-body">
      
      <!-- Version 2.0 - November 2025 -->
      <div class="changelog-section">
        <div class="changelog-version">
          <div class="version-header">
            <div class="version-number">🚀 SIPINJAM v2.0</div>
            <div class="version-date">30 Okt - 28 Nov 2025</div>
          </div>
          
          <ul class="changelog-list">
            <li>
              <span class="badge-category badge-new">New</span>
              <strong>Kalender Interaktif Dashboard</strong> - Klik tanggal kosong untuk langsung membuat peminjaman baru dengan auto-fill tanggal
            </li>
            <li>
              <span class="badge-category badge-new">New</span>
              <strong>Jadwal Rutin (Recurring Schedule)</strong> - Mendukung peminjaman berulang mingguan dengan pilihan hari tertentu
            </li>
            <li>
              <span class="badge-category badge-fix">Fix</span>
              <strong>Anti Double Booking</strong> - Sistem dapat melakukan booking di hari yang sama namun di waktu yang berbeda
            </li>
            <li>
              <span class="badge-category badge-fix">Fix</span>
              <strong>Peminjaman Kendaraan</strong> - Sistem langsung menolak ketika ada jadwal yang bentrok
            </li>
            <li>
              <span class="badge-category badge-improve">Improve</span>
              <strong>Kalender Universal</strong> - Sinkronisasi kalender antara user dan admin untuk semua pengguna
            </li>
            <li>
              <span class="badge-category badge-improve">Improve</span>
              <strong>Optimasi Logika Peminjaman</strong> - Jadwal tidak bentrok, tidak tumpang tindih, booking tanggal sama jam berbeda
            </li>
            <li>
              <span class="badge-category badge-fix">Fix</span>
              <strong>Keamanan Form</strong> - Form jumlah kursi hanya tampil ketika pengguna memilih opsi yang relevan
            </li>
            <li>
              <span class="badge-category badge-new">New</span>
              <strong>Database Tujuan Peminjaman Studio</strong> - Menambahkan field tujuan pada pinjam studio
            </li>
            <li>
              <span class="badge-category badge-improve">Improve</span>
              <strong>Tampilan Jadwal Rutin</strong> - Penanda khusus untuk jadwal rutin dengan tampilan hari yang lebih jelas
            </li>
            <li>
              <span class="badge-category badge-new">New</span>
              <strong>Informasi Peminjaman di Kalender</strong> - Menampilkan detail peminjaman langsung di kalender dengan modal popup
            </li>
          </ul>
        </div>
      </div>



      <!-- Version 1.5 - Oktober 2025 -->
      <div class="changelog-section">
        <div class="changelog-version">
          <div class="version-header">
            <div class="version-number">✨ SIPINJAM v1.5</div>
            <div class="version-date">Oktober 2025</div>
          </div>
          
          <ul class="changelog-list">
            <li>
              <span class="badge-category badge-new">New</span>
              <strong>UI/UX Modern</strong> - Tampilan glass morphism dengan gradient biru-putih yang elegan
            </li>
            <li>
              <span class="badge-category badge-improve">Improve</span>
              <strong>Responsive Design</strong> - Optimasi tampilan untuk mobile, tablet, dan desktop
            </li>
            <li>
              <span class="badge-category badge-fix">Fix</span>
              <strong>Deteksi Bentrok Jadwal</strong> - Pesan error lebih informatif dengan detail peminjam dan waktu bentrok
            </li>
            <li>
              <span class="badge-category badge-new">New</span>
              <strong>Statistik Real-time</strong> - Dashboard menampilkan ketersediaan fasilitas dengan progress bar visual
            </li>
          </ul>
        </div>
      </div>



    </div>



    <!-- Footer Credits -->
    <div class="footer-credits">
      <div class="credits-content">
        <div class="credits-title">💻 Developed By</div>
        <div class="credits-team">Tim IT Pelita Cemerlang School</div>
        <p class="credits-copyright">
          © 2024 Pelita Cemerlang School. All Rights Reserved.
        </p>
      </div>
    </div>



  </div>
</div>



<script>
function validateForm() {
  let u = document.querySelector("[name='username']").value.trim();
  let p = document.querySelector("[name='password']").value.trim();
  if(!u || !p){
    alert("Harap isi semua kolom!");
    return false;
  }
  return true;
}



function openWhatsNew() {
  document.getElementById('whatsNewModal').classList.add('show');
  document.body.style.overflow = 'hidden';
}



function closeWhatsNew(event) {
  if (!event || event.target.id === 'whatsNewModal') {
    document.getElementById('whatsNewModal').classList.remove('show');
    document.body.style.overflow = 'auto';
  }
}



// Close with ESC key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeWhatsNew();
  }
});
</script>

<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-messaging-compat.js"></script>

<script src="/testsipinjam/assets/js/firebase.js"></script>




</body>
</html>
