<?php
date_default_timezone_set('Asia/Jakarta');
$conn = mysqli_connect('localhost','root','','testsipinjam');
if ($conn && !headers_sent()) {
    mysqli_set_charset($conn, 'utf8mb4');
}