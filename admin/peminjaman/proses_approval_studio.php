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
    
    $query = "SELECT ps.*, u.id as user_id, u.nama_lengkap, s.namastudio 
              FROM pinjamstudio ps 
              JOIN user u ON ps.iduser = u.id 
              JOIN studio s ON ps.idstudio = s.idstudio 
              WHERE ps.idpinjam = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    if ($action == 'approve') {
        $updateQuery = "UPDATE pinjamstudio SET statuspinjam = 'Disetujui' WHERE idpinjam = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $notifTitle = "✅ Peminjaman Studio Disetujui";
            $notifBody = "Peminjaman " . $data['namastudio'] . " pada " . date('d/m/Y', strtotime($data['tglpinjam'])) . " disetujui.";
            
            sendFCMNotification($data['user_id'], $notifTitle, $notifBody, base_url('user/?view=databookingstudio'));
            header("location:../index.php?view=datapinjamstudio&alert=approved");
        }
        
    } elseif ($action == 'reject') {
        $updateQuery = "UPDATE pinjamstudio SET statuspinjam = 'Ditolak' WHERE idpinjam = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $notifTitle = "❌ Peminjaman Studio Ditolak";
            $notifBody = "Peminjaman " . $data['namastudio'] . " pada " . date('d/m/Y', strtotime($data['tglpinjam'])) . " ditolak.";
            
            sendFCMNotification($data['user_id'], $notifTitle, $notifBody, base_url('user/?view=databookingstudio'));
            header("location:../index.php?view=datapinjamstudio&alert=rejected");
        }
    }
}
?>
