<?php
session_start();
include 'koneksi.php';

// Cek Status Login
$isLoggedIn = isset($_SESSION['idUser']);
$reservasiUser = [];

if ($isLoggedIn) {
    $idUser = $_SESSION['idUser'];
    // Query Reservasi + Detail Hotel + Foto
    $sql = "SELECT r.*, h.nama as namaHotel, t.namaTipeKamar, f.urlFoto 
            FROM reservasi r
            JOIN tipekamar t ON r.idTipeKamar = t.idtipeKamar
            JOIN hotel h ON t.idHotel = h.idHotel
            LEFT JOIN fotohotel f ON h.idHotel = f.idHotel AND f.cover = 1
            WHERE r.idUser = $idUser
            ORDER BY r.tanggalReservasi DESC";
            
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $reservasiUser[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan - NginapYuk!</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        /* CSS INLINE KHUSUS HALAMAN RIWAYAT AGAR RAPI */
        .history-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
            min-height: 60vh;
        }
        .page-title { font-size: 32px; font-weight: 800; margin-bottom: 24px; color: #1F12D4; }
        
        /* Style untuk Pesan Belum Login */
        .alert-login {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            font-size: 18px;
            font-weight: 600;
        }

        /* Card Style */
        .history-card {
            background: white;
            border: 1px solid #eee;
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .history-card:hover { transform: translateY(-3px); }
        
        .h-img { width: 180px; height: 180px; object-fit: cover; }
        
        .h-content { padding: 20px; flex: 1; display: flex; flex-direction: column; justify-content: center; }
        .h-hotel { font-size: 20px; font-weight: 700; color: #1A1A1A; margin-bottom: 4px; }
        .h-room { font-size: 14px; color: #666; margin-bottom: 12px; }
        
        .h-details { display: flex; gap: 20px; margin-bottom: 12px; font-size: 14px; }
        .h-item i { color: #1F12D4; margin-right: 6px; }
        
        .h-footer { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f0f0f0; padding-top: 12px; margin-top: auto; }
        .h-status { 
            padding: 6px 12px; border-radius: 50px; font-size: 12px; font-weight: 700; text-transform: uppercase; 
        }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        
        .h-price { font-size: 18px; font-weight: 800; color: #1F12D4; }
        
        @media (max-width: 768px) {
            .history-card { flex-direction: column; }
            .h-img { width: 100%; height: 150px; }
        }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <main class="history-container">
        <h1 class="page-title">Riwayat Pemesanan</h1>

        <?php if (!$isLoggedIn): ?>
            <div class="alert-login">
                <i class="fa-solid fa-lock" style="margin-bottom:10px; font-size:30px; display:block;"></i>
                Silahkan login terlebih dahulu untuk melihat riwayat pemesanan.
                <br>
                <a href="login.php" style="font-size:14px; color:#1F12D4; text-decoration:underline; margin-top:10px; display:inline-block;">Klik disini untuk Login</a>
            </div>

        <?php else: ?>
            <?php if (empty($reservasiUser)): ?>
                <div style="text-align:center; padding:40px; color:#666;">
                    <i class="fa-regular fa-folder-open" style="font-size:40px; margin-bottom:10px;"></i>
                    <p>Belum ada riwayat pemesanan.</p>
                </div>
            <?php else: ?>
                
                <?php foreach($reservasiUser as $res): ?>
                <div class="history-card">
                    <img src="image/hotelImage/<?= !empty($res['urlFoto']) ? $res['urlFoto'] : 'placeholder.jpg' ?>" 
                         alt="Hotel" class="h-img" onerror="this.src='https://placehold.co/200x200?text=No+Image'">
                    
                    <div class="h-content">
                        <div class="h-hotel"><?= htmlspecialchars($res['namaHotel']) ?></div>
                        <div class="h-room"><?= htmlspecialchars($res['namaTipeKamar']) ?> (<?= $res['jumlahKamar'] ?> Kamar)</div>
                        
                        <div class="h-details">
                            <div class="h-item">
                                <i class="fa-solid fa-calendar-check"></i> 
                                Check-in: <?= date('d M Y', strtotime($res['tanggalCheckin'])) ?>
                            </div>
                            <div class="h-item">
                                <i class="fa-solid fa-calendar-xmark"></i> 
                                Check-out: <?= date('d M Y', strtotime($res['tanggalCheckout'])) ?>
                            </div>
                        </div>

                        <div class="h-footer">
                            <span class="h-status status-confirmed">
                                <?= htmlspecialchars($res['statusReservasi']) ?>
                            </span>
                            <span class="h-price">
                                Rp <?= number_format($res['totalHarga'], 0, ',', '.') ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

            <?php endif; ?>

        <?php endif; ?>
    </main>

    </body>
</html>