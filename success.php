<?php 
// 1. LOGIKA PHP: Ambil data reservasi berdasarkan ID dari URL
include 'koneksi.php'; 

$detail = null;
if (isset($_GET['idReservasi'])) {
    $idRes = $conn->real_escape_string($_GET['idReservasi']);

    // Query untuk mengambil detail lengkap
    $sql = "SELECT r.*, h.nama as namaHotel, t.namaTipeKamar 
            FROM reservasi r
            JOIN tipekamar t ON r.idTipeKamar = t.idtipeKamar
            JOIN hotel h ON t.idHotel = h.idHotel
            WHERE r.idReservasi = '$idRes'";
            
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $detail = $result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Berhasil</title>

    <link rel="stylesheet" href="css/successStyle.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

    <main class="success">
        <div class="successWrap">
            <i class="fa-solid fa-check"></i>
            <h1>Pembayaran Berhasil!</h1>
            
            <p>Terima kasih! Pembayaran Anda telah terkonfirmasi. E-Voucher telah dikirim ke email 
               <b><?= $detail ? htmlspecialchars($detail['emailPemesan']) : 'Anda' ?></b> 
               dan tersedia di menu Cek Pesanan.
            </p>

            <div class="btnWrap">
                <button type="button" class="home" onclick="window.location.href='index.php'">
                    KEMBALI KE HALAMAN UTAMA
                </button>

                <?php if($detail): ?>
                <button type="button" class="detail" onclick="openModal()">
                    LIHAT DETAIL PESANAN
                </button>
                <?php else: ?>
                <button type="button" class="detail" onclick="window.location.href='history.php'">
                    CEK RIWAYAT PESANAN
                </button>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php if($detail): ?>
    <div class="modal-overlay" id="orderModal">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Detail Pesanan</h3>
                <button class="close-btn" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <div class="modal-body">
                <div class="info-group">
                    <label>Nama Pemesan</label>
                    <span><?= htmlspecialchars($detail['namaPemesan']) ?></span>
                </div>
                <div class="info-group">
                    <label>Hotel</label>
                    <span><?= htmlspecialchars($detail['namaHotel']) ?></span>
                </div>
                <div class="info-group">
                    <label>Tipe Kamar</label>
                    <span><?= htmlspecialchars($detail['namaTipeKamar']) ?> (<?= $detail['jumlahKamar'] ?> Unit)</span>
                </div>
                
                <div class="date-row">
                    <div class="info-group">
                        <label>Check-In</label>
                        <span><?= date('d M Y', strtotime($detail['tanggalCheckin'])) ?></span>
                    </div>
                    <div class="info-group text-right">
                        <label>Check-Out</label>
                        <span><?= date('d M Y', strtotime($detail['tanggalCheckout'])) ?></span>
                    </div>
                </div>

                <div class="total-box">
                    <span>Total Bayar</span>
                    <span class="amount">Rp <?= number_format($detail['totalHarga'], 0, ',', '.') ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        const modal = document.getElementById('orderModal');

        function openModal() {
            if(modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden'; // Cegah scroll background
            }
        }

        function closeModal() {
            if(modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }

        // Tutup jika klik di luar kartu
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>

</body>
</html>