<?php
session_start();
include '../../koneksi.php';
include '../../fcm_helper.php';

// Cek login admin
if (!isset($_SESSION['level']) || $_SESSION['level'] != 'admin') {
    header("location:../../index.php?alert=not_authorized");
    exit();
}

if (isset($_GET['id']) && isset($_GET['action'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    
    // Ambil data peminjaman
    $query = "SELECT pb.*, u.id as user_id, u.nama_lengkap, u.email, b.namabarang 
              FROM pinjambarang pb 
              JOIN user u ON pb.iduser = u.id 
              JOIN barang b ON pb.idbarang = b.idbarang 
              WHERE pb.idpinjam = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    if (!$data) {
        header("location:../index.php?view=datapinjambarang&alert=not_found");
        exit();
    }
    
    if ($action == 'approve') {
        // Update status menjadi disetujui
        $updateQuery = "UPDATE pinjambarang SET statuspinjam = 'Disetujui' WHERE idpinjam = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            // Kirim notifikasi ke user
            $notifTitle = "✅ Peminjaman Gedung Disetujui";
            $notifBody = "Peminjaman " . $data['namabarang'] . " pada tanggal " . date('d/m/Y', strtotime($data['tglpinjam'])) . " telah disetujui oleh admin.";
            $clickAction = "http://localhost/TESTSIPINJAM/user/?view=databooking";
            
            $notifResult = sendFCMNotification($data['user_id'], $notifTitle, $notifBody, $clickAction);
            
            if ($notifResult['success']) {
                header("location:../index.php?view=datapinjambarang&alert=approved_with_notif");
            } else {
                header("location:../index.php?view=datapinjambarang&alert=approved_no_notif");
            }
        } else {
            header("location:../index.php?view=datapinjambarang&alert=failed");
        }
        
    } elseif ($action == 'reject') {
        // Update status menjadi ditolak
        $updateQuery = "UPDATE pinjambarang SET statuspinjam = 'Ditolak' WHERE idpinjam = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            // Kirim notifikasi ke user
            $notifTitle = "❌ Peminjaman Gedung Ditolak";
            $notifBody = "Maaf, peminjaman " . $data['namabarang'] . " pada tanggal " . date('d/m/Y', strtotime($data['tglpinjam'])) . " ditolak oleh admin.";
            $clickAction = "http://localhost/TESTSIPINJAM/user/?view=databooking";
            
            $notifResult = sendFCMNotification($data['user_id'], $notifTitle, $notifBody, $clickAction);
            
            header("location:../index.php?view=datapinjambarang&alert=rejected");
        } else {
            header("location:../index.php?view=datapinjambarang&alert=failed");
        }
    }
    
} else {
    header("location:../index.php?view=datapinjambarang&alert=invalid_request");
}
?>
