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
    /* ===== MODERN CSS RESET ===== */
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    h1, h2, h3, h4, h5, h6, p, ul, ol, dl, figure, blockquote, dd {
      margin: 0;
      padding: 0;
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

    /* ===== DROPDOWN MENU ===== */
    .menu-dropdown {
      position: relative;
    }

    .menu-dropdown-toggle {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 15px;
      color: rgba(255,255,255,0.9);
      text-decoration: none;
      border-radius: 8px;
      transition: all 0.3s ease;
      font-size: 14px;
      cursor: pointer;
      background: none;
      border: none;
      width: 100%;
      text-align: left;
    }

    .menu-dropdown-toggle:hover {
      background: rgba(255,255,255,0.15);
      color: #fff;
    }

    .menu-dropdown-toggle.active {
      background: rgba(255,255,255,0.2);
      color: #fff;
      font-weight: 600;
    }

    .dropdown-arrow {
      transition: transform 0.3s ease;
      font-size: 12px;
    }

    .menu-dropdown.open .dropdown-arrow {
      transform: rotate(180deg);
    }

    .submenu {
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      padding-left: 15px;
    }

    .menu-dropdown.open .submenu {
      max-height: 500px;
    }

    .submenu-item {
      margin: 2px 0;
    }

    .submenu-link {
      display: flex;
      align-items: center;
      padding: 10px 15px;
      color: rgba(255,255,255,0.85);
      text-decoration: none;
      border-radius: 6px;
      transition: all 0.3s ease;
      font-size: 13px;
      position: relative;
    }

    .submenu-link:before {
      content: '';
      position: absolute;
      left: 0;
      top: 50%;
      transform: translateY(-50%);
      width: 3px;
      height: 0;
      background: #fff;
      transition: height 0.3s ease;
    }

    .submenu-link:hover {
      background: rgba(255,255,255,0.1);
      color: #fff;
      padding-left: 20px;
    }

    .submenu-link:hover:before {
      height: 70%;
    }

    .submenu-link.active {
      background: rgba(255,255,255,0.15);
      color: #fff;
      font-weight: 600;
      padding-left: 20px;
    }

    .submenu-link.active:before {
      height: 70%;
    }

    .submenu-icon {
      width: 16px;
      margin-right: 10px;
      font-size: 14px;
      text-align: center;
    }

    /* ===== SIDEBAR FOOTER ===== */
    .sidebar-footer {
      padding: 15px;
      border-top: 1px solid rgba(255,255,255,0.1);
      background: rgba(0,0,0,0.1);
      margin-top: auto;
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

    /* Logout Button */
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
      color: #ffffffff;
      cursor: pointer;
      padding: 8px;
      border-radius: 8px;
      transition: all 0.3s ease;
    }

    .header-toggle:hover {
      background: rgba(255,255,255,0.15);
    }

    .header-title {
      flex: 1;
      font-size: 20px;
      font-weight: 600;
      color: #ffffffff;
      margin-left: 20px;
    }

    .header-actions {
      display: flex;
      gap: 15px;
      align-items: center;
    }

    /* Date Time Display - Clean & Simple */
    .datetime-display {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 18px;
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(10px);
      border-radius: 10px;
      font-size: 13px;
      color: #ffffff;
      font-weight: 500;
      border: 1px solid rgba(255, 255, 255, 0.2);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
    }

    .datetime-display:hover {
      background: rgba(255, 255, 255, 0.2);
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .datetime-display i {
      color: #ffffff;
      font-size: 16px;
    }

    /* ===== CONTENT AREA ===== */
    .content {
      padding: 30px;
      width: 100%;
      min-height: calc(100vh - 70px);
    }

    .page-inner {
      width: 100%;
      max-width: 1400px;
      margin: 0 auto;
      padding: 0px;
    }

    .page-inner > *:first-child {
      margin-top: 0px;
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

      .header-title {
        font-size: 16px;
        margin-left: 10px;
      }

      .datetime-display {
        display: none;
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

    body[data-theme="dark"] .header-title {
      color: #e5e7eb;
    }

    body[data-theme="dark"] .modern-sidebar {
      background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    }

    body[data-theme="dark"] .content {
      color: #e5e7eb;
    }

    body[data-theme="dark"] .datetime-display {
      background: rgba(30, 41, 59, 0.6);
      border-color: rgba(255, 255, 255, 0.1);
      color: #e5e7eb;
    }

    body[data-theme="dark"] .datetime-display:hover {
      background: rgba(30, 41, 59, 0.8);
    }

    /* ===== UTILITY CLASSES ===== */
    .card {
      border-radius: 12px;
      box-shadow: 0 2px 15px rgba(0,0,0,0.08);
      border: none;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    }

    body[data-theme="dark"] .card {
      background: #1e293b;
      color: #e5e7eb;
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

        <!-- Data Master dengan Dropdown -->
        <div class="menu-section">
          <div class="menu-title">Data Master</div>
          <div class="menu-item menu-dropdown <?php echo (in_array(@$_GET['view'], ['databarang','dataruangan','datakolam','datastudio','datakelas'])) ? 'open' : ''; ?>">
            <button class="menu-dropdown-toggle <?php echo (in_array(@$_GET['view'], ['databarang','dataruangan','datakolam','datastudio','datakelas'])) ? 'active' : ''; ?>" onclick="toggleDropdown(this)">
              <div style="display: flex; align-items: center;">
                <i class="fas fa-database menu-icon"></i>
                <span class="menu-text">Data Master</span>
              </div>
              <i class="fas fa-chevron-down dropdown-arrow"></i>
            </button>
            <div class="submenu">
              <div class="submenu-item">
                <a href="?view=databarang" class="submenu-link <?php echo (@$_GET['view'] == 'databarang') ? 'active' : ''; ?>">
                  <i class="fas fa-box submenu-icon"></i>
                  <span>Data Gedung</span>
                </a>
              </div>
              <div class="submenu-item">
                <a href="?view=dataruangan" class="submenu-link <?php echo (@$_GET['view'] == 'dataruangan') ? 'active' : ''; ?>">
                  <i class="fas fa-car submenu-icon"></i>
                  <span>Data Kendaraan</span>
                </a>
              </div>
              <div class="submenu-item">
                <a href="?view=datakolam" class="submenu-link <?php echo (@$_GET['view'] == 'datakolam') ? 'active' : ''; ?>">
                  <i class="fas fa-swimming-pool submenu-icon"></i>
                  <span>Data Kolam</span>
                </a>
              </div>
              <div class="submenu-item">
                <a href="?view=datastudio" class="submenu-link <?php echo (@$_GET['view'] == 'datastudio') ? 'active' : ''; ?>">
                  <i class="fas fa-video submenu-icon"></i>
                  <span>Data Studio</span>
                </a>
              </div>
              <div class="submenu-item">
                <a href="?view=datakelas" class="submenu-link <?php echo (@$_GET['view'] == 'datakelas') ? 'active' : ''; ?>">
                  <i class="fas fa-chalkboard-teacher submenu-icon"></i>
                  <span>Data Kelas</span>
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Peminjaman -->
        <div class="menu-section">
          <div class="menu-title">Peminjaman</div>
          <div class="menu-item">
            <a href="?view=datapinjambarang" class="menu-link <?php echo (@$_GET['view'] == 'datapinjambarang' || @$_GET['view'] == 'detailpinjambarang') ? 'active' : ''; ?>">
              <i class="fas fa-box-open menu-icon"></i>
              <span class="menu-text">Pinjam Gedung</span>
            </a>
          </div>
          <div class="menu-item">
            <a href="?view=datapinjamruangan" class="menu-link <?php echo (@$_GET['view'] == 'datapinjamruangan' || @$_GET['view'] == 'detailpinjamkendaraan') ? 'active' : ''; ?>">
              <i class="fas fa-car menu-icon"></i>
              <span class="menu-text">Pinjam Kendaraan</span>
            </a>
          </div>
          <div class="menu-item">
            <a href="?view=datapinjamkolam" class="menu-link <?php echo (@$_GET['view'] == 'datapinjamkolam' || @$_GET['view'] == 'detailpinjamkolam') ? 'active' : ''; ?>">
              <i class="fas fa-swimming-pool menu-icon"></i>
              <span class="menu-text">Pinjam Kolam</span>
            </a>
          </div>
          <div class="menu-item">
            <a href="?view=datapinjamstudio" class="menu-link <?php echo (@$_GET['view'] == 'datapinjamstudio' || @$_GET['view'] == 'detailpinjamstudio') ? 'active' : ''; ?>">
              <i class="fas fa-music menu-icon"></i>
              <span class="menu-text">Pinjam Studio</span>
            </a>
          </div>
        </div>
      </div>

      <div class="sidebar-footer">
        <div class="theme-toggle" onclick="toggleTheme()">
          <span>
            <i class="fas fa-moon"></i>
            <span style="margin-left: 10px;">Dark Mode</span>
          </span>
          <i class="fas fa-toggle-off" id="themeIcon"></i>
        </div>
        
        <!-- Logout Button -->
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
          <?php
            $pageTitle = 'Dashboard';
            if(isset($_GET['view'])){
              switch($_GET['view']){
                case 'databarang': $pageTitle = 'Data Gedung'; break;
                case 'dataruangan': $pageTitle = 'Data Kendaraan'; break;
                case 'datakolam': $pageTitle = 'Data Kolam'; break;
                case 'datastudio': $pageTitle = 'Data Studio'; break;
                case 'datakelas': $pageTitle = 'Data Kelas'; break;
                case 'datapinjambarang': $pageTitle = 'Data Peminjaman Gedung'; break;
                case 'detailpinjambarang': $pageTitle = 'Detail Peminjaman Gedung'; break;
                case 'datapinjamruangan': $pageTitle = 'Data Peminjaman Kendaraan'; break;
                case 'detailpinjamkendaraan': $pageTitle = 'Detail Peminjaman Kendaraan'; break;
                case 'datapinjamkolam': $pageTitle = 'Data Peminjaman Kolam'; break;
                case 'detailpinjamkolam': $pageTitle = 'Detail Peminjaman Kolam'; break;
                case 'datapinjamstudio': $pageTitle = 'Data Peminjaman Studio'; break;
                case 'detailpinjamstudio': $pageTitle = 'Detail Peminjaman Studio'; break;
              }
            }
            echo $pageTitle;
          ?>
        </div>
        <div class="header-actions">
          <!-- Date Time Display -->
          <div class="datetime-display">
            <i class="fas fa-clock"></i>
            <span id="currentDateTime"></span>
          </div>
        </div>
      </div>

      <!-- Content -->
      <div class="content">
          <?php
            if (@$_GET['view'] == '' || $_GET['view'] == 'dashboard'){
              include_once 'dashboard.php';
            }
            elseif ($_GET['view'] == 'databarang'){
              include_once 'master/barang/databarang.php';
            }
            elseif ($_GET['view'] == 'dataruangan'){
              include_once 'master/ruangan/dataruangan.php';
            }
            elseif ($_GET['view'] == 'datakolam'){
              include_once 'master/kolam/datakolam.php';
            }
            elseif ($_GET['view'] == 'datastudio'){
              include_once 'master/studio/datastudio.php';
            }
            elseif ($_GET['view'] == 'datakelas'){
              include_once 'master/kelas/datakelas.php';
            }
            elseif ($_GET['view'] == 'datapinjambarang'){
              include_once 'peminjaman/datapinjambarang.php';
            }
            elseif ($_GET['view'] == 'detailpinjambarang'){
              include_once 'peminjaman/detailpinjambarang.php';
            }
            elseif ($_GET['view'] == 'datapinjamruangan'){
              include_once 'peminjaman/datapinjamruangan.php';
            }
            elseif ($_GET['view'] == 'detailpinjamkendaraan'){
              include_once '../user/peminjaman/kendaraan/detailpinjamkendaraan.php';
            }
            elseif ($_GET['view'] == 'datapinjamkolam'){
              include_once 'peminjaman/datapinjamkolam.php';
            }
            elseif ($_GET['view'] == 'detailpinjamkolam'){
              include_once '../user/peminjaman/kolam/detailpinjamkolam.php';
            }
            elseif ($_GET['view'] == 'datapinjamstudio'){
              include_once 'peminjaman/datapinjamstudio.php';
            }
            elseif ($_GET['view'] == 'detailpinjamstudio'){
              include_once '../user/peminjaman/studio/detailpinjamstudio.php';
            }
          ?>
      </div>
    </div>
  </div>

  <!-- Core JS -->
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
    $(function(){ $('#add-row').DataTable({}); });

    // Dropdown Toggle
    function toggleDropdown(btn) {
      const dropdown = btn.closest('.menu-dropdown');
      dropdown.classList.toggle('open');
    }

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

    // Dark mode
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

    // Notice
    // Notice - Diperbaiki
(function(){
  const audio = document.getElementById('noticeAudio');
  
  function notice(){
    $.ajax({
      url: 'notice.php',  // Relative path dari admin/index.php
      method: 'POST',
      timeout: 3000,  // Timeout 3 detik
      success: function(data){
        if(parseInt(data,10) === 1 && audio){
          audio.volume = 1;
          audio.play().catch(function(err){
            console.warn('🔇 Audio autoplay blocked:', err.message);
          });
        }
      },
      error: function(xhr, status, error){
        // Hanya log jika error bukan 404
        if(xhr.status !== 404){
          console.error('❌ Notice error:', status, xhr.status);
        }
      },
      complete: function(){
        // Jadwalkan polling berikutnya setelah request selesai
        setTimeout(notice, 5000);  // 5 detik
      }
    });
  }
  
  // Mulai polling setelah 2 detik (berikan waktu halaman load)
  setTimeout(notice, 2000);
})();

  </script>

  <!-- ✅ FIREBASE SDK -->
  <script src="https://www.gstatic.com/firebasejs/8.10.0/firebase-app.js"></script>
  <script src="https://www.gstatic.com/firebasejs/8.10.0/firebase-messaging.js"></script>
  
  <!-- ✅ FIREBASE.JS -->
  <script src="../assets/js/firebase.js"></script>
  
  <!-- ✅ INITIALIZE FCM - HANYA INI SATU-SATUNYA TEMPAT -->
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      // Ambil user ID dari session menggunakan key 'id'
      const userId = <?php echo isset($_SESSION['id']) ? json_encode($_SESSION['id']) : 'null'; ?>;
      const username = <?php echo isset($_SESSION['username']) ? json_encode($_SESSION['username']) : 'null'; ?>;

      console.log('👤 Admin Info:', { id: userId, username: username });

      if (userId) {
        console.log('✅ Initializing FCM...');
        
        if (typeof initFCM === 'function') {
          initFCM(userId)
            .then(() => console.log('✅✅ FCM Ready!'))
            .catch(err => console.error('❌ FCM Error:', err));
        } else {
          console.error('❌ initFCM not found');
        }
      } else {
        console.error('❌ Not logged in');
      }
    });
  </script>

</body>
</html>
