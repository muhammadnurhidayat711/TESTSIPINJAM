<?php

include 'cek.php';

$bunyi = false;
$id_user = $_SESSION['id'];

$notice1 = mysqli_query($conn, "SELECT `id_notice1` FROM `notice1` WHERE `id_user` = $id_user AND `status` = 0");
while ($n1 = mysqli_fetch_assoc($notice1)) {
	mysqli_query($conn, "UPDATE `notice1` SET `status` = 1 WHERE `id_notice1` = " . $n1['id_notice1']);
	$bunyi = true;
}

$notice2 = mysqli_query($conn, "SELECT `id_notice2` FROM `notice2` WHERE `id_user` = $id_user AND `status` = 0");
while ($n2 = mysqli_fetch_assoc($notice2)) {
	mysqli_query($conn, "UPDATE `notice2` SET `status` = 1 WHERE `id_notice2` = " . $n2['id_notice2']);
	$bunyi = true;
}


$notice3 = mysqli_query($conn, "SELECT `id_notice3` FROM `notice3` WHERE `id_user` = $id_user AND `status` = 0");
while ($n3 = mysqli_fetch_assoc($notice3)) {
	mysqli_query($conn, "UPDATE `notice3` SET `status` = 1 WHERE `id_notice3` = " . $n3['id_notice3']);
	$bunyi = true;
}

$notice4 = mysqli_query($conn, "SELECT `id_notice4` FROM `notice4` WHERE `id_user` = $id_user AND `status` = 0");
while ($n4 = mysqli_fetch_assoc($notice3)) {
	mysqli_query($conn, "UPDATE `notice4` SET `status` = 1 WHERE `id_notice4` = " . $n4['id_notice4']);
	$bunyi = true;
}

echo $bunyi;
