<?php 
session_start();
include 'koneksi.php'; 

// 1. TANGKAP DATA
if (!isset($_GET['idHotel']) || !isset($_GET['idKamar'])) {
    echo "<script>alert('Data tidak valid!'); window.location.href='index.php';</script>";
    exit();
}

$idHotel = intval($_GET['idHotel']);
$idKamar = intval($_GET['idKamar']);
$checkIn = isset($_GET['checkin']) ? $_GET['checkin'] : date('Y-m-d');
$checkOut = isset($_GET['checkout']) ? $_GET['checkout'] : date('Y-m-d', strtotime('+1 day'));
$jmlKamar = isset($_GET['jumlah']) ? intval($_GET['jumlah']) : 1;
$opsiPaket = isset($_GET['opsi']) ? $_GET['opsi'] : 'basic'; 

// 2. HITUNG DURASI
$tgl1 = new DateTime($checkIn);
$tgl2 = new DateTime($checkOut);
$jarak = $tgl1->diff($tgl2);
$durasiMalam = $jarak->days;
if ($durasiMalam < 1) $durasiMalam = 1;

// 3. QUERY DATABASE (PERBAIKAN: AMBIL DUA JENIS FOTO SEKALIGUS)
$sql = "SELECT h.nama as namaHotel, h.alamatLengkap, 
               t.namaTipeKamar, t.harga, t.kapasitas, t.ukuranKamar,
               fh.urlFoto as fotoHotel,      -- Foto Hotel Utama
               fr.urlFoto as fotoKamar       -- Foto Tipe Kamar
        FROM hotel h
        JOIN tipekamar t ON h.idHotel = t.idHotel
        -- Join 1: Ambil Foto Hotel (Cover)
        LEFT JOIN fotohotel fh ON h.idHotel = fh.idHotel AND fh.cover = 1
        -- Join 2: Ambil Foto Kamar (Cover)
        LEFT JOIN fototipekamar fr ON t.idtipeKamar = fr.idTipeKamar AND fr.cover = 1
        WHERE h.idHotel = $idHotel AND t.idtipeKamar = $idKamar";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    
    // Variabel Display
    $namaHotel = $data['namaHotel'];
    $tipeKamar = $data['namaTipeKamar'];
    $alamatHotel = $data['alamatLengkap'];
    $kapasitas = $data['kapasitas'];
    $ukuranKamar = $data['ukuranKamar'];
    
    // LOGIKA GAMBAR (Pastikan tidak error jika kosong)
    // 1. Gambar Hotel (Untuk Card Kiri Atas)
    $gambarHotel = !empty($data['fotoHotel']) ? $data['fotoHotel'] : "placeholder_hotel.jpg";
    
    // 2. Gambar Kamar (Untuk Sidebar Kanan)
    // Jika foto kamar kosong, pakai foto hotel sebagai cadangan
    $gambarKamar = !empty($data['fotoKamar']) ? $data['fotoKamar'] : $gambarHotel;
    
    // 4. HITUNG HARGA
    $hargaDasar = $data['harga'];
    $biayaTambahan = ($opsiPaket == 'paket') ? 270000 : 0; 
    $hargaPerMalam = $hargaDasar + $biayaTambahan;
    
    $subTotal = $hargaPerMalam * $durasiMalam * $jmlKamar;
    $pajak = $subTotal * 0.10;
    $totalHarga = $subTotal + $pajak;
    
} else {
    die("Data tidak ditemukan.");
}

// Helper Date
function namaHari($tgl) {
    $hari = date('D', strtotime($tgl));
    $list = ['Sun'=>'Minggu','Mon'=>'Senin','Tue'=>'Selasa','Wed'=>'Rabu','Thu'=>'Kamis','Fri'=>'Jumat','Sat'=>'Sabtu'];
    return $list[$hari];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran</title>
    <link rel="stylesheet" href="css/formStyle.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

</head>
<body>
    
    <?php include 'navbar.php'; ?>

    <main class="bookingPageContainer">

        <form action="bookingProcess.php" method="POST" style="display: contents;">
        
        <input type="hidden" name="idHotel" value="<?= $idHotel ?>">
        <input type="hidden" name="idKamar" value="<?= $idKamar ?>">
        <input type="hidden" name="checkin" value="<?= $checkIn ?>">
        <input type="hidden" name="checkout" value="<?= $checkOut ?>">
        <input type="hidden" name="jumlahKamar" value="<?= $jmlKamar ?>">
        <input type="hidden" name="totalHarga" value="<?= $totalHarga ?>">

        <section class="formSection">
            
            <input type="hidden" name="totalHarga" value="<?= $totalHarga ?>">
            <input type="hidden" name="durasi" value="<?= $durasiMalam ?>">
            <input type="hidden" name="checkin" value="<?= $checkIn ?>">
            <input type="hidden" name="checkout" value="<?= $checkOut ?>">

            <div class="hotelInfoCard">
                <div class="info">
                    <h1><?php echo $namaHotel; ?></h1>
                    
                    <p>
                        <?php echo $data['alamatLengkap']; ?>
                    </p>

                    <div class="starCont">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                </div>
                
                <img src="image/hotelImage/<?php echo $gambarHotel; ?>" alt="<?php echo $namaHotel; ?>">
            </div>

            <div class="mainInfo">
                <h1>Data Pemesanan</h1>
                <div class="form">
                    <label for="namaLengkap" class="formLabel">Nama Lengkap <span>*</span></label>
                    <input type="text" id="namaLengkap" name="fullname" class="formInput" placeholder="Sesuai KTP/Paspor" required>
                </div>
                <div class="formGroup">
                    <div class="form">
                        <label for="email" class="formLabel">Email <span>*</span></label>
                        <input type="email" id="email" name="email" class="formInput" placeholder="email@contoh.com" required>
                    </div>
                    <div class="form">
                        <label for="telp" class="formLabel">Nomor Telepon <span>*</span></label>
                        <input type="tel" id="telp" name="phone" class="formInput" placeholder="0812..." required>
                    </div>
                </div>
            </div>

            <div class="request">
                <h1>Permintaan Khusus (Opsional)</h1>
                <p>Pihak properti akan berusaha memenuhi permintaan, namun tidak dapat menjamin seluruh permintaan dapat dipenuhi.</p>
                <div class="checkboxList">
                    
                    <label class="customCheck">
                        <input type="checkbox" name="req_checkin" value="1">
                        <span class="checkmark"></span>
                        <span class="text">Kamar Dekat Lift</span>
                    </label>

                    <label class="customCheck">
                        <input type="checkbox" name="req_checkout" value="1">
                        <span class="checkmark"></span>
                        <span class="text">Kamar di Lantai Tinggi</span>
                    </label>

                    <label class="customCheck" id="toggleOtherReq">
                        <input type="checkbox" name="req_other" value="1">
                        <span class="checkmark"></span>
                        <span class="text">Permintaan Lainnya</span>
                    </label>
                </div>

                <div class="conditionalInput" id="otherReqInput">
                    <textarea name="other_request_text" placeholder="Tulis permintaan kamu di sini..."></textarea>
                </div>
            </div>

            <div class="payment">
                <h1>Metode Pembayaran</h1>
                
                <div class="paymentGroupsContainer">

                    <div class="paymentGroupCard">
                        <h3>Pembayaran Instan</h3>
                        <div class="paymentOptionsGrid">
                            
                            <label class="payOption">
                                <input type="radio" name="payment_method" value="gopay">
                                <div class="payCard">
                                    <img src="image/payment/Gopay.png" alt="GoPay">
                                    <span class="checkBadge"><i class="fa-solid fa-check"></i></span>
                                </div>
                            </label>

                            <label class="payOption">
                                <input type="radio" name="payment_method" value="qris">
                                <div class="payCard">
                                    <img src="image/payment/Qris.png" alt="qris">
                                    <span class="checkBadge"><i class="fa-solid fa-check"></i></span>
                                </div>
                            </label>

                        </div>
                    </div>

                    <div class="paymentGroupCard">
                        <h3>Transfer Bank</h3>
                        <div class="paymentOptionsGrid">
                            
                            <label class="payOption">
                                <input type="radio" name="payment_method" value="bca">
                                <div class="payCard">
                                    <img src="image/payment/BCA.png" alt="BCA">
                                    <span class="checkBadge"><i class="fa-solid fa-check"></i></span>
                                </div>
                            </label>

                            <label class="payOption">
                                <input type="radio" name="payment_method" value="mandiri">
                                <div class="payCard">
                                    <img src="image/payment/Mandiri.png" alt="Mandiri">
                                    <span class="checkBadge"><i class="fa-solid fa-check"></i></span>
                                </div>
                            </label>

                            <label class="payOption">
                                <input type="radio" name="payment_method" value="BRI">
                                <div class="payCard">
                                    <img src="image/payment/BRI.png" alt="BRI">
                                    <span class="checkBadge"><i class="fa-solid fa-check"></i></span>
                                </div>
                            </label>

                        </div>
                    </div>

                    <div class="paymentGroupCard">
                        <h3>Akun Virtual</h3>
                        <div class="paymentOptionsGrid">
                            
                            <label class="payOption">
                                <input type="radio" name="payment_method" value="va_bca">
                                <div class="payCard">
                                    <img src="image/payment/BCA.png" alt="BCA VA">
                                    <span class="checkBadge"><i class="fa-solid fa-check"></i></span>
                                </div>
                            </label>

                        </div>
                    </div>

                    <div class="paymentGroupCard">
                        <h3>Kartu Kredit</h3>
                        <div class="paymentOptionsGrid">
                            
                            <label class="payOption">
                                <input type="radio" name="payment_method" value="credit_card">
                                <div class="payCard">
                                    <img src="image/payment/Master Card.png" alt="CC">
                                    <span class="checkBadge"><i class="fa-solid fa-check"></i></span>
                                </div>
                            </label>

                            <label class="payOption">
                                <input type="radio" name="payment_method" value="visa">
                                <div class="payCard">
                                    <img src="image/payment/Visa.png" alt="Visa">
                                    <span class="checkBadge"><i class="fa-solid fa-check"></i></span>
                                </div>
                            </label>

                        </div>
                    </div>

                </div>
            </div>

            <div class="loginAlert">
                <i class="fa-solid fa-circle-info"></i>
                <span>Login terlebih dahulu untuk menyimpan riwayat pemesanan.</span>
            </div>

            <button class="payBtn">BAYAR</button>

        </section></form>

        <aside  class="summary">

            <div class="wrap">

                <div class="roomCard">
                    <h1><?= htmlspecialchars($tipeKamar); ?></h1>
                    
                    <div class="roomCardWrap">
                        <img src="image/roomImage/<?= $gambarKamar; ?>" alt="<?= htmlspecialchars($tipeKamar); ?>" 
                             style="height:120px; object-fit:cover; width:100px; border-radius:12px;"
                             onerror="this.src='https://placehold.co/150x150?text=No+Image'">
                        
                        <div class="roomDetailsContainer">
    
                            <div class="roomSpecs">
                                <div class="specItem">
                                    <i class="fa-solid fa-ruler-combined"></i> 
                                    <span><?= htmlspecialchars($ukuranKamar) ?></span>
                                </div>
                                <div class="specItem">
                                    <i class="fa-solid fa-user-group"></i> 
                                    <span><?= $kapasitas ?> Tamu</span>
                                </div>
                            </div>

                            <hr class="divider"> 
                            
                            <div class="roomPerks">
                                <?php if($opsiPaket == 'paket'): ?>
                                    <div class="badge badge--success">
                                        <i class="fa-solid fa-utensils"></i>
                                        <span>Sarapan</span>
                                    </div>
                                    <div class="badge badge--success">
                                        <i class="fa-solid fa-shield-halved"></i>
                                        <span>Refundable</span>
                                    </div>
                                <?php else: ?>
                                    <div class="badge badge--neutral">
                                        <i class="fa-solid fa-ban"></i>
                                        <span>No Breakfast</span>
                                    </div>
                                    <div class="badge badge--danger">
                                        <i class="fa-solid fa-circle-exclamation"></i>
                                        <span>No Refund</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="schedule">
                    <div class="widgetHeader">
                        <a href="hotelPage.php?idHotel=<?= $idHotel ?>&checkin=<?= $checkIn ?>&checkout=<?= $checkOut ?>&kamar=<?= $jmlKamar ?>" class="editLink">Ubah Jadwal</a>
                    </div>

                    <div class="dateCard">
                        <div class="dateGroup text-left">
                            <time datetime="<?= $checkIn ?>">
                                <span class="dayDate"><?= namaHari($checkIn) ?>, <?= date('d', strtotime($checkIn)) ?></span>
                                <span class="monthYear"><?= date('F Y', strtotime($checkIn)) ?></span>
                            </time>
                            <span class="timeInfo">14.00 WIB</span>
                        </div>

                        <div class="durationGroup">
                            <i class="fa-solid fa-arrow-right-long"></i>
                            <span class="nightCount"><?= $durasiMalam ?> malam</span>
                        </div>

                        <div class="dateGroup text-right">
                            <time datetime="<?= $checkOut ?>">
                                <span class="dayDate"><?= namaHari($checkOut) ?>, <?= date('d', strtotime($checkOut)) ?></span>
                                <span class="monthYear"><?= date('F Y', strtotime($checkOut)) ?></span>
                            </time>
                            <span class="timeInfo">12.00 WIB</span>
                        </div>

                    </div>
                </div>

                <div class="priceSummaryCard">
                    
                    <h3 class="summaryTitle">Rincian Harga</h3>

                    <div class="billList">
                        
                        <div class="billItem">
                            <div class="itemLabel">
                                <span class="mainText">Harga Sewa</span>
                                <span class="subText"><?= $tipeKamar ?> (x<?= $jmlKamar ?> Kamar)</span>
                                <span class="subText" style="font-size:12px; color:#666;"><?= $durasiMalam ?> Malam</span>
                            </div>
                            <div class="itemPrice">Rp<?= number_format($subTotal, 0, ',', '.'); ?></div>
                        </div>

                        <div class="billItem">
                            <div class="itemLabel">
                                <span class="mainText">Pajak & Layanan (10%)</span>
                            </div>
                            <div class="itemPrice">Rp<?= number_format($pajak, 0, ',', '.'); ?></div>
                        </div>

                    </div>

                    <div class="totalFooter">
                        <div class="totalLabel">
                            <span class="totalText">Total</span>
                            <span class="totalSubText"><?php echo ($opsiPaket == 'paket') ? 'Termasuk Sarapan' : 'Room Only'; ?></span>
                        </div>
                        <div class="totalPrice">Rp<?= number_format($totalHarga, 0, ',', '.'); ?></div>
                    </div>

                </div>

            </div>

        </aside>

    </main>

    <script src="js/toggle.js"></script>

</body>
</html>