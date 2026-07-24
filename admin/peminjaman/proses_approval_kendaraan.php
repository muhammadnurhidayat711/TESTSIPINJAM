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
    
    // Ambil data peminjaman
    $query = "SELECT pr.*, u.id as user_id, u.nama_lengkap, r.namaruangan 
              FROM pinjamruangan pr 
              JOIN user u ON pr.iduser = u.id 
              JOIN ruangan r ON pr.idruangan = r.idruangan 
              WHERE pr.idpinjam = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    if (!$data) {
        header("location:../index.php?view=datapinjamruangan&alert=not_found");
        exit();
    }
    
    if ($action == 'approve') {
        $updateQuery = "UPDATE pinjamruangan SET statuspinjam = 'Disetujui' WHERE idpinjam = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $notifTitle = "✅ Peminjaman Kendaraan Disetujui";
            $notifBody = "Peminjaman " . $data['namaruangan'] . " pada tanggal " . date('d/m/Y', strtotime($data['tglpinjam'])) . " telah disetujui.";
            $clickAction = base_url('user/?view=databookingkendaraan');
            
            sendFCMNotification($data['user_id'], $notifTitle, $notifBody, $clickAction);
            header("location:../index.php?view=datapinjamruangan&alert=approved");
        } else {
            header("location:../index.php?view=datapinjamruangan&alert=failed");
        }
        
    } elseif ($action == 'reject') {
        $updateQuery = "UPDATE pinjamruangan SET statuspinjam = 'Ditolak' WHERE idpinjam = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $notifTitle = "❌ Peminjaman Kendaraan Ditolak";
            $notifBody = "Maaf, peminjaman " . $data['namaruangan'] . " pada tanggal " . date('d/m/Y', strtotime($data['tglpinjam'])) . " ditolak.";
            $clickAction = base_url('user/?view=databookingkendaraan');
            
            sendFCMNotification($data['user_id'], $notifTitle, $notifBody, $clickAction);
            header("location:../index.php?view=datapinjamruangan&alert=rejected");
        } else {
            header("location:../index.php?view=datapinjamruangan&alert=failed");
        }
    }
}
?>
