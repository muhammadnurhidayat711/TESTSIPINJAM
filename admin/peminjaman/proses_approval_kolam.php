<?php
session_start();
include '../../koneksi.php';
include '../../fcm_helper.php';

if (!isset($_SESSION['level']) || $_SESSION['level'] != 'admin') {
    header("location:../../index.php?alert=not_authorized");
    exit();
}

if (isset($_GET['id']) && isset($_GET['action'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    
    $query = "SELECT pk.*, u.id as user_id, u.nama_lengkap, k.namakolam 
              FROM pinjamkolam pk 
              JOIN user u ON pk.iduser = u.id 
              JOIN kolam k ON pk.idkolam = k.idkolam 
              WHERE pk.idpinjam = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    if ($action == 'approve') {
        $updateQuery = "UPDATE pinjamkolam SET statuspinjam = 'Disetujui' WHERE idpinjam = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $notifTitle = "✅ Peminjaman Kolam Disetujui";
            $notifBody = "Peminjaman " . $data['namakolam'] . " pada " . date('d/m/Y', strtotime($data['tglpinjam'])) . " disetujui.";
            
            sendFCMNotification($data['user_id'], $notifTitle, $notifBody, "http://localhost/TESTSIPINJAM/user/?view=databookingkolam");
            header("location:../index.php?view=datapinjamkolam&alert=approved");
        }
        
    } elseif ($action == 'reject') {
        $updateQuery = "UPDATE pinjamkolam SET statuspinjam = 'Ditolak' WHERE idpinjam = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $notifTitle = "❌ Peminjaman Kolam Ditolak";
            $notifBody = "Peminjaman " . $data['namakolam'] . " pada " . date('d/m/Y', strtotime($data['tglpinjam'])) . " ditolak.";
            
            sendFCMNotification($data['user_id'], $notifTitle, $notifBody, "http://localhost/TESTSIPINJAM/user/?view=databookingkolam");
            header("location:../index.php?view=datapinjamkolam&alert=rejected");
        }
    }
}
?>
