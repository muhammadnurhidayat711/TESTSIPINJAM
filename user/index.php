<?php
include 'cek.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>Peminjaman Gedung dan Kendaraan</title>
  <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
  <link rel="icon" href="../assets/img/icon.ico" type="image/x-icon" />

  <!-- Fonts and icons -->
  <script src="../assets/js/plugin/webfont/webfont.min.js"></script>
  <script>
    WebFont.load({
      google: { families: ["Open+Sans:300,400,600,700"] },
      custom: {
        families: ["Flaticon","Font Awesome 5 Solid","Font Awesome 5 Regular","Font Awesome 5 Brands"],
        urls: ['../assets/css/fonts.css']
      },
      active: function(){ sessionStorage.fonts = true; }
    });
  </script>

  <!-- CSS Files -->
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/css/azzara.min.css">

  <style>
    /* ===== SCOPED CSS - Tidak mengganggu konten halaman ===== */

    /* Reset hanya untuk sidebar & header */
    .modern-sidebar *,
    .top-header * {
      box-sizing: border-box;
    }

    html {
      -webkit-text-size-adjust: 100%;
      -moz-text-size-adjust: 100%;
      text-size-adjust: 100%;
    }

    body {
      margin: 0;
      padding: 0;
      background: #f8f9fa;
      font-family: 'Open Sans', sans-serif;
      line-height: 1.5;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      overflow-x: auto;
      overflow-y: auto;
    }

    img,
    picture,
    video,
    canvas,
    svg {
      display: block;
      max-width: 100%;
      height: auto;
    }

    input,
    button,
    textarea,
    select {
      font: inherit;
    }

    p,
    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
      overflow-wrap: break-word;
    }

    /* ===== LAYOUT STRUCTURE ===== */
    .wrapper {
      display: flex;
      width: 100%;
    }

    /* ===== SIDEBAR ===== */
    .modern-sidebar {
      width: 260px;
      background: linear-gradient(135deg, #1b91ffff , #126b9eff, #0b639eff, #00579eff);
      position: fixed;
      left: 0;
      top: 0;
      height: 100vh;
      z-index: 1000;
      overflow-y: auto;
      overflow-x: hidden;
      transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 2px 0 20px rgba(0,0,0,0.1);
      display: flex;
      flex-direction: column;
    }

    .modern-sidebar::-webkit-scrollbar {
      width: 6px;
    }

    .modern-sidebar::-webkit-scrollbar-track {
      background: rgba(255,255,255,0.1);
    }

    .modern-sidebar::-webkit-scrollbar-thumb {
      background: rgba(255,255,255,0.3);
      border-radius: 3px;
    }

    .modern-sidebar::-webkit-scrollbar-thumb:hover {
      background: rgba(255,255,255,0.5);
    }

    .sidebar-header {
      padding: 25px 20px;
      text-align: center;
      border-bottom: 1px solid rgba(255,255,255,0.1);
      background: rgba(0,0,0,0.1);
    }

    .sidebar-logo {
      font-size: 24px;
      font-weight: 700;
      color: #fff;
      margin: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .sidebar-logo i {
      font-size: 28px;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.1); }
    }

    .sidebar-subtitle {
      color: rgba(255,255,255,0.8);
      font-size: 12px;
      margin-top: 5px;
    }

    .sidebar-menu {
      padding: 20px 0;
      flex: 1;
      overflow-y: auto;
    }

    .menu-section {
      margin-bottom: 25px;
    }

    .menu-title {
      color: rgba(255,255,255,0.6);
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 1px;
      padding: 0 20px 10px;
      font-weight: 600;
    }

    .menu-item {
      position: relative;
      margin: 2px 10px;
    }

    .menu-link {
      display: flex;
      align-items: center;
      padding: 12px 15px;
      color: rgba(255,255,255,0.9);
      text-decoration: none;
      border-radius: 8px;
      transition: all 0.3s ease;
      font-size: 14px;
      position: relative;
      overflow: hidden;
      cursor: pointer;
    }

    .menu-link:before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      width: 4px;
      height: 100%;
      background: #fff;
      transform: scaleY(0);
      transition: transform 0.3s ease;
    }

    .menu-link:hover {
      background: rgba(255,255,255,0.15);
      color: #fff;
      transform: translateX(5px);
    }

    .menu-link:hover:before {
      transform: scaleY(1);
    }

    .menu-link.active {
      background: rgba(255,255,255,0.2);
      color: #fff;
      font-weight: 600;
    }

    .menu-link.active:before {
      transform: scaleY(1);
    }

    .menu-icon {
      width: 20px;
      margin-right: 12px;
      font-size: 18px;
      text-align: center;
    }

    .menu-text {
      flex: 1;
    }

    /* ===== SIDEBAR FOOTER ===== */
    .sidebar-footer {
      padding: 15px;
      border-top: 1px solid rgba(255,255,255,0.1);
      background: rgba(0,0,0,0.1);
      margin-top: auto;
    }

    .user-info {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 15px;
      background: rgba(255,255,255,0.1);
      border-radius: 8px;
      color: #fff;
      margin-bottom: 10px;
    }

    .user-info i {
      font-size: 24px;
    }

    .user-details {
      flex: 1;
      min-width: 0;
    }

    .user-name {
      font-size: 14px;
      font-weight: 600;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .user-role {
      font-size: 11px;
      color: rgba(255,255,255,0.7);
    }

    .theme-toggle {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 10px 15px;
      background: rgba(255,255,255,0.1);
      border-radius: 8px;
      color: #fff;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-bottom: 10px;
    }

    .theme-toggle:hover {
      background: rgba(255,255,255,0.2);
    }

    .theme-toggle i {
      font-size: 18px;
    }

    .logout-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 12px 15px;
      background: linear-gradient(135deg, #ef4444, #dc2626);
      border: none;
      border-radius: 8px;
      color: #fff;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      width: 100%;
      text-decoration: none;
    }

    .logout-btn:hover {
      background: linear-gradient(135deg, #dc2626, #b91c1c);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
      color: #fff;
    }

    .logout-btn:active {
      transform: translateY(0);
    }

    .logout-btn i {
      font-size: 16px;
    }

    /* ===== MAIN PANEL ===== */
    .main-panel {
      margin-left: 260px;
      width: calc(100% - 260px);
      transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* ===== HEADER ===== */
    .top-header {
      background: linear-gradient(135deg, #126b9eff, #0b639eff, #00579eff);
      height: 70px;
      display: flex;
      align-items: center;
      padding: 0 30px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      position: sticky;
      top: 0;
      z-index: 999;
    }

    .header-toggle {
      display: none;
      background: none;
      border: none;
      font-size: 24px;
      color: #333;
      cursor: pointer;
      padding: 8px;
      border-radius: 8px;
      transition: all 0.3s ease;
    }

    .header-toggle:hover {
      background: #f0f0f0;
    }

    .header-title {
      flex: 1;
      margin-left: 20px;
    }

    .page-title {
      font-size: 20px;
      font-weight: 600;
      color: #ffffffff;
      margin: 0;
    }

    .page-subtitle {
      font-size: 12px;
      color: #ffffffff;
      margin: 0;
    }

    .header-actions {
      display: flex;
      gap: 15px;
      align-items: center;
    }

    .datetime-display {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 8px 15px;
      background: #f8f9fa;
      border-radius: 8px;
      font-size: 13px;
      color: #495057;
    }

    .datetime-display i {
      color: #667eea;
    }

    /* ===== CONTENT AREA - Preserve existing styles ===== */
    .content {
      padding: 30px;
      width: 100%;
      min-height: calc(10vh - 70px);
    }

    .page-inner {
      width: 100%;
      max-width: 1400px;
      margin: 0 auto;
    }

    .page-inner .card {
      border-radius: 12px;
      box-shadow: 0 2px 15px rgba(0,0,0,0.08);
      border: none;
    }

    .page-inner .card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }

    body[data-theme="dark"] .page-inner .card {
      background: #1e293b;
      color: #e5e7eb;
    }

    body[data-theme="dark"] .page-inner .table {
      color: #e5e7eb;
    }

    body[data-theme="dark"] .page-inner .table-bordered {
      border-color: #334155;
    }

    body[data-theme="dark"] .page-inner .table-bordered th,
    body[data-theme="dark"] .page-inner .table-bordered td {
      border-color: #334155;
    }

    body[data-theme="dark"] .page-inner .form-control {
      background-color: #334155;
      color: #e5e7eb;
      border-color: #475569;
    }

    body[data-theme="dark"] .page-inner .btn-primary {
      background-color: #667eea;
      border-color: #667eea;
    }

    body[data-theme="dark"] .page-inner .modal-content {
      background-color: #1e293b;
      color: #e5e7eb;
    }

    body[data-theme="dark"] .page-inner .modal-header {
      border-bottom-color: #334155;
    }

    body[data-theme="dark"] .page-inner .modal-footer {
      border-top-color: #334155;
    }

    /* ===== MOBILE RESPONSIVE ===== */
    @media (max-width: 768px) {
      .modern-sidebar {
        transform: translateX(-100%);
      }

      .modern-sidebar.active {
        transform: translateX(0);
      }

      .main-panel {
        margin-left: 0;
        width: 100%;
      }

      .header-toggle {
        display: block;
      }

      .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 999;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
      }

      .sidebar-overlay.active {
        opacity: 1;
        visibility: visible;
      }

      .content {
        padding: 15px;
      }

      .page-title {
        font-size: 16px;
      }

      .page-subtitle {
        font-size: 11px;
      }

      .datetime-display {
        display: none;
      }

      .header-title {
        margin-left: 10px;
      }
    }

    /* ===== DARK MODE ===== */
    body[data-theme="dark"] {
      background: #0f172a;
    }

    body[data-theme="dark"] .top-header {
      background: #1e293b;
      box-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }

    body[data-theme="dark"] .header-toggle {
      color: #e5e7eb;
    }

    body[data-theme="dark"] .header-toggle:hover {
      background: #334155;
    }

    body[data-theme="dark"] .page-title {
      color: #e5e7eb;
    }

    body[data-theme="dark"] .page-subtitle {
      color: #94a3b8;
    }

    body[data-theme="dark"] .datetime-display {
      background: #334155;
      color: #cbd5e1;
    }

    body[data-theme="dark"] .modern-sidebar {
      background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    }

    body[data-theme="dark"] .content {
      color: #e5e7eb;
    }

    body[data-theme="dark"] .page-inner h1,
    body[data-theme="dark"] .page-inner h2,
    body[data-theme="dark"] .page-inner h3,
    body[data-theme="dark"] .page-inner h4,
    body[data-theme="dark"] .page-inner h5,
    body[data-theme="dark"] .page-inner h6 {
      color: #e5e7eb;
    }

    body[data-theme="dark"] .page-inner p,
    body[data-theme="dark"] .page-inner span,
    body[data-theme="dark"] .page-inner label {
      color: #cbd5e1;
    }

    .page-inner > .row > [class*="col"] > .card {
      border-radius: 12px;
      box-shadow: 0 2px 15px rgba(0,0,0,0.08);
      border: none;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      margin-bottom: 20px;
    }

    .page-inner > .row > [class*="col"] > .card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }

    .page-inner .table {
      margin-bottom: 0;
    }

    .page-inner .btn {
      border-radius: 6px;
    }

    .page-inner .dataTables_wrapper {
      padding: 0;
    }
  </style>
</head>

<body>
  <div class="wrapper">
    <!-- Sidebar -->
    <nav class="modern-sidebar" id="sidebar">
      <div class="sidebar-header">
        <h3 class="sidebar-logo">
          <i class="fas fa-building"></i>
          <span>SiPinjam</span>
        </h3>
        <p class="sidebar-subtitle">Sistem Peminjaman</p>
      </div>

      <div class="sidebar-menu">
        <!-- Dashboard -->
        <div class="menu-section">
          <div class="menu-title">Main Menu</div>
          <div class="menu-item">
            <a href="?view=dashboard" class="menu-link <?php echo (!isset($_GET['view']) || $_GET['view'] == 'dashboard') ? 'active' : ''; ?>">
              <i class="fas fa-home menu-icon"></i>
              <span class="menu-text">Dashboard</span>
            </a>
          </div>
        </div>

        <!-- Peminjaman -->
        <div class="menu-section">
          <div class="menu-title">Peminjaman</div>
          <div class="menu-item">
            <a href="?view=datapinjambarang" class="menu-link <?php echo (in_array(@$_GET['view'], ['datapinjambarang', 'createpinjambarang', 'detailpinjambarang'])) ? 'active' : ''; ?>">
              <i class="fas fa-building menu-icon"></i>
              <span class="menu-text">Pinjam Gedung</span>
            </a>
          </div>
          <div class="menu-item">
            <a href="?view=datapinjamkendaraan" class="menu-link <?php echo (in_array(@$_GET['view'], ['datapinjamkendaraan', 'createpinjamkendaraan', 'detailpinjamkendaraan'])) ? 'active' : ''; ?>">
              <i class="fas fa-car menu-icon"></i>
              <span class="menu-text">Pinjam Kendaraan</span>
            </a>
          </div>
          <div class="menu-item">
            <a href="?view=datapinjamkolam" class="menu-link <?php echo (in_array(@$_GET['view'], ['datapinjamkolam', 'createpinjamkolam', 'detailpinjamkolam'])) ? 'active' : ''; ?>">
              <i class="fas fa-swimming-pool menu-icon"></i>
              <span class="menu-text">Pinjam Kolam</span>
            </a>
          </div>
          <div class="menu-item">
            <a href="?view=datapinjamstudio" class="menu-link <?php echo (in_array(@$_GET['view'], ['datapinjamstudio', 'createpinjamstudio', 'detailpinjamstudio'])) ? 'active' : ''; ?>">
              <i class="fas fa-music menu-icon"></i>
              <span class="menu-text">Pinjam Studio</span>
            </a>
          </div>
        </div>
      </div>

      <div class="sidebar-footer">
        <div class="user-info">
          <i class="fas fa-user-circle"></i>
          <div class="user-details">
            <div class="user-name"><?php echo $_SESSION['username'] ?? 'User'; ?></div>
            <div class="user-role">Member</div>
          </div>
        </div>

        <div class="theme-toggle" onclick="toggleTheme()">
          <span>
            <i class="fas fa-moon"></i>
            <span style="margin-left: 10px;">Dark Mode</span>
          </span>
          <i class="fas fa-toggle-off" id="themeIcon"></i>
        </div>

        <a href="../logout.php" class="logout-btn" onclick="return confirmLogout()">
          <i class="fas fa-sign-out-alt"></i>
          <span>Logout</span>
        </a>
      </div>
    </nav>

    <!-- Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Main Panel -->
    <div class="main-panel">
      <!-- Header -->
      <div class="top-header">
        <button class="header-toggle" id="sidebarToggle">
          <i class="fas fa-bars"></i>
        </button>
        <div class="header-title">
          <h1 class="page-title">
            <?php
              $pageTitle = 'Dashboard';
              if(isset($_GET['view'])){
                switch($_GET['view']){
                  case 'datapinjambarang': $pageTitle = 'Peminjaman Gedung'; break;
                  case 'createpinjambarang': $pageTitle = 'Ajukan Peminjaman Gedung'; break;
                  case 'detailpinjambarang': $pageTitle = 'Detail Peminjaman Gedung'; break;
                  case 'datapinjamkendaraan': $pageTitle = 'Peminjaman Kendaraan'; break;
                  case 'createpinjamkendaraan': $pageTitle = 'Ajukan Peminjaman Kendaraan'; break;
                  case 'detailpinjamkendaraan': $pageTitle = 'Detail Peminjaman Kendaraan'; break;
                  case 'datapinjamkolam': $pageTitle = 'Peminjaman Kolam'; break;
                  case 'createpinjamkolam': $pageTitle = 'Ajukan Peminjaman Kolam'; break;
                  case 'detailpinjamkolam': $pageTitle = 'Detail Peminjaman Kolam'; break;
                  case 'datapinjamstudio': $pageTitle = 'Peminjaman Studio'; break;
                  case 'createpinjamstudio': $pageTitle = 'Ajukan Peminjaman Studio'; break;
                  case 'detailpinjamstudio': $pageTitle = 'Detail Peminjaman Studio'; break;
                }
              }
              echo $pageTitle;
            ?>
          </h1>
          <p class="page-subtitle">Sistem Informasi Peminjaman Pelita Cemerlang School</p>
        </div>
        <div class="header-actions">
          <div class="datetime-display">
            <i class="fas fa-clock"></i>
            <span id="currentDateTime"></span>
          </div>
        </div>
      </div>

      <!-- Content -->
      <div class="content">
        <div class="page-inner">
          <?php
            if (@$_GET['view'] == '' || $_GET['view'] == 'dashboard'){
              include 'dashboard.php';
            }
            elseif ($_GET['view'] == 'datapinjambarang'){
              include 'peminjaman/barang/datapinjambarang.php';
            }
            elseif ($_GET['view'] == 'createpinjambarang'){
              include 'peminjaman/barang/createpinjambarang.php';
            }
            elseif ($_GET['view'] == 'detailpinjambarang'){
              include 'peminjaman/barang/detailpinjambarang.php';
            }
            elseif ($_GET['view'] == 'datapinjamkendaraan'){
              include 'peminjaman/kendaraan/datapinjamkendaraan.php';
            }
            elseif ($_GET['view'] == 'createpinjamkendaraan'){
              include 'peminjaman/kendaraan/createpinjamkendaraan.php';
            }
            elseif ($_GET['view'] == 'detailpinjamkendaraan'){
              include 'peminjaman/kendaraan/detailpinjamkendaraan.php';
            }
            elseif ($_GET['view'] == 'datapinjamkolam'){
              include 'peminjaman/kolam/datapinjamkolam.php';
            }
            elseif ($_GET['view'] == 'createpinjamkolam'){
              include 'peminjaman/kolam/createpinjamkolam.php';
            }
            elseif ($_GET['view'] == 'detailpinjamkolam'){
              include 'peminjaman/kolam/detailpinjamkolam.php';
            }
            elseif ($_GET['view'] == 'datapinjamstudio'){
              include 'peminjaman/studio/datapinjamstudio.php';
            }
            elseif ($_GET['view'] == 'createpinjamstudio'){
              include 'peminjaman/studio/createpinjamstudio.php';
            }
            elseif ($_GET['view'] == 'detailpinjamstudio'){
              include 'peminjaman/studio/detailpinjamstudio.php';
            }
          ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Core JS Files -->
  <script src="../assets/js/core/jquery.3.2.1.min.js"></script>
  <script src="../assets/js/core/popper.min.js"></script>
  <script src="../assets/js/core/bootstrap.min.js"></script>
  <script src="../assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
  <script src="../assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>
  <script src="../assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js"></script>
  <script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
  <script src="../assets/js/plugin/datatables/datatables.min.js"></script>
  <script src="../assets/js/ready.min.js"></script>
  <script src="../assets/js/setting-demo.js"></script>

  <script>
    // DataTable
    $(document).ready(function(){
      $('#add-row').DataTable({});
    });

    // Real-time DateTime (Indonesia)
    function updateDateTime() {
      const el = document.getElementById('currentDateTime');
      if (!el) return;
      const now = new Date();
      const optDate = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
      const optTime = { hour: '2-digit', minute: '2-digit' };
      el.textContent = now.toLocaleDateString('id-ID', optDate) + ' ' + now.toLocaleTimeString('id-ID', optTime);
    }
    updateDateTime();
    setInterval(updateDateTime, 1000);

    // Sidebar Toggle
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    function toggleSidebar() {
      sidebar.classList.toggle('active');
      sidebarOverlay.classList.toggle('active');
    }

    if (sidebarToggle) {
      sidebarToggle.addEventListener('click', toggleSidebar);
    }

    if (sidebarOverlay) {
      sidebarOverlay.addEventListener('click', toggleSidebar);
    }

    window.addEventListener('resize', function() {
      if (window.innerWidth > 768) {
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
      }
    });

    // Logout Confirmation
    function confirmLogout() {
      return confirm('Apakah Anda yakin ingin keluar dari sistem?');
    }

    // Dark mode toggle + persist
    (function(){
      const KEY = 'sipinjam_theme';
      const themeIcon = document.getElementById('themeIcon');

      function applyTheme(theme){
        if(theme==='dark'){
          document.body.setAttribute('data-theme','dark');
          if(themeIcon) {
            themeIcon.className = 'fas fa-toggle-on';
          }
        } else {
          document.body.removeAttribute('data-theme');
          if(themeIcon) {
            themeIcon.className = 'fas fa-toggle-off';
          }
        }
      }

      const saved = localStorage.getItem(KEY) || 'light';
      applyTheme(saved);

      window.toggleTheme = function(){
        const next = document.body.hasAttribute('data-theme') ? 'light' : 'dark';
        localStorage.setItem(KEY, next);
        applyTheme(next);
      };
    })();
  </script>

  <!-- ✅ FIREBASE SDK -->
  <script src="https://www.gstatic.com/firebasejs/8.10.0/firebase-app.js"></script>
  <script src="https://www.gstatic.com/firebasejs/8.10.0/firebase-messaging.js"></script>

  <!-- ✅ FIREBASE.JS (yang sudah diperbaiki dengan logging detail) -->
  <script src="../assets/js/firebase.js"></script>

  <!-- ✅ INITIALIZE FCM UNTUK USER dengan setTimeout dan logging lengkap -->
  <script>
    // ✅ Tunggu 2 detik agar jQuery error tidak mengganggu FCM
    setTimeout(function() {
      const userId = <?php echo isset($_SESSION['id']) ? json_encode($_SESSION['id']) : 'null'; ?>;
      const username = <?php echo isset($_SESSION['username']) ? json_encode($_SESSION['username']) : 'null'; ?>;

      console.log('═══════════════════════════════════');
      console.log('🚀 STARTING FCM INITIALIZATION');
      console.log('═══════════════════════════════════');
      console.log('👤 User Info:', { id: userId, username: username });

      if (userId) {
        // Reset flag jika sebelumnya gagal
        if (window.fcmInitialized && !window.fcmToken) {
          console.log('🔄 Resetting FCM flag (previous init incomplete)...');
          window.fcmInitialized = false;
        }

        console.log('✅ Calling initFCM...');

        if (typeof initFCM === 'function') {
          initFCM(userId)
            .then(() => {
              console.log('═══════════════════════════════════');
              console.log('✅✅ FCM READY FOR USER!');
              console.log('═══════════════════════════════════');
            })
            .catch(err => {
              console.log('═══════════════════════════════════');
              console.error('❌❌ FCM INITIALIZATION FAILED');
              console.log('═══════════════════════════════════');
              console.error('Error:', err);
            });
        } else {
          console.error('❌ initFCM function not found. Check if firebase.js loaded correctly.');
        }
      } else {
        console.error('❌ User not logged in (userId is null)');
      }
    }, 2000); // ✅ Delay 2 detik untuk bypass jQuery errors
  </script>

</body>
</html>