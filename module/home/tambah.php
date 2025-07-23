
<?php
    $pemilik = $_POST['pemilik'];
    $nomor = $_POST['nomor'];
    $link_webhook = $_POST['link_webhook'];
    $sql = "insert into device (pemilik, nomor, link_webhook) values ('$pemilik', '$nomor', '$link_webhook ')";

    $result = $conn->query($sql);
    if ($result) {
        echo "Device berhasil ditambahkan";
    } else {
        echo "Device gagal ditambahkan";
    }
?>