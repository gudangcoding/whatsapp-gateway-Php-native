<?php
    $conn = new mysqli("localhost", "root", "", "wa-gateway");
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }
?>