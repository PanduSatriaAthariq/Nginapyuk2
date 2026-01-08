<?php
session_start(); // Wajib start session untuk fitur login/ulasan

// KONEKSI DATABASE
include 'koneksi.php'; 

// 1. AMBIL ID HOTEL DARI URL
$idHotel = isset($_GET['idHotel']) ? (int)$_GET['idHotel'] : 1;

// 2. QUERY INFO HOTEL UTAMA
$sqlHotel = "SELECT * FROM hotel WHERE idHotel = $idHotel";
$resultHotel = $conn->query($sqlHotel);
if ($resultHotel->num_rows == 0) {
    die("Hotel tidak ditemukan.");
}
$hotel = $resultHotel->fetch_assoc();

// 3. QUERY FOTO HOTEL
$sqlFotoHotel = "SELECT * FROM fotohotel WHERE idHotel = $idHotel ORDER BY sort ASC";
$resFoto = $conn->query($sqlFotoHotel);
$fotoCover = "https://placehold.co/800x600/00AEEF/ffffff?text=No+Image"; 
$fotoGaleri = [];

while ($row = $resFoto->fetch_assoc()) {
    if ($row['cover'] == 1) {
        $fotoCover = $row['urlfoto']; 
    } else {
        $fotoGaleri[] = $row['urlfoto'];
    }
}
if ($fotoCover == "https://placehold.co/800x600/00AEEF/ffffff?text=No+Image" && !empty($fotoGaleri)) {
    $fotoCover = $fotoGaleri[0];
    array_shift($fotoGaleri); 
}

// PATH GAMBAR
$pathHotel = "image/hotelImage/"; 
$pathKamar = "image/roomImage/"; 
$fasilitasHotelArr = explode(',', $hotel['fasilitas']);
$defaultCheckIn = date('Y-m-d');
$defaultCheckOut = date('Y-m-d', strtotime('+1 day'));

// Ambil dari URL jika user sudah memilih
$checkIn = isset($_GET['checkin']) ? $_GET['checkin'] : $defaultCheckIn;
$checkOut = isset($_GET['checkout']) ? $_GET['checkout'] : $defaultCheckOut;
$jumlahKamar = isset($_GET['kamar']) ? (int)$_GET['kamar'] : 1;

// Validasi sederhana: Checkout tidak boleh sebelum Checkin
if ($checkOut <= $checkIn) {
    $checkOut = date('Y-m-d', strtotime($checkIn . ' +1 day'));
}

// ==========================================
// LOGIC KHUSUS ULASAN (CRUD & STATISTIK)
// ==========================================

// A. HANDLE SUBMIT ULASAN (CREATE)
$pesanUlasan = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review'])) {
    if (!isset($_SESSION['idUser'])) {
        $pesanUlasan = "<div class='alert-box alert-error'>Silakan login terlebih dahulu untuk memberikan ulasan.</div>";
    } else {
        $uid = $_SESSION['idUser'];
        $ratingInput = $_POST['rating'];
        $komentarInput = $conn->real_escape_string($_POST['komentar']);
        
        // Cek apakah user punya reservasi di hotel ini (Validasi sederhana)
        // Kita ambil reservasi terakhir user di hotel ini untuk dikaitkan
        $cekReservasi = "SELECT idReservasi FROM reservasi 
                         JOIN tipekamar ON reservasi.idTipeKamar = tipekamar.idtipeKamar
                         WHERE reservasi.idUser = $uid AND tipekamar.idHotel = $idHotel 
                         ORDER BY tanggalCheckin DESC LIMIT 1";
        $resCek = $conn->query($cekReservasi);

        if($resCek->num_rows > 0) {
            $rowRes = $resCek->fetch_assoc();
            $idReservasi = $rowRes['idReservasi'];

            // Cek apakah sudah pernah review untuk reservasi ini?
            $cekDuplikat = "SELECT idUlasan FROM ulasan WHERE idReservasi = $idReservasi";
            if($conn->query($cekDuplikat)->num_rows == 0){
                $sqlInsert = "INSERT INTO ulasan (idUser, idHotel, idReservasi, ratingSkor, komentar, tanggalUlasan) 
                              VALUES ($uid, $idHotel, $idReservasi, '$ratingInput', '$komentarInput', NOW())";
                if ($conn->query($sqlInsert) === TRUE) {
                    $pesanUlasan = "<div class='alert-box alert-success'>Terima kasih! Ulasan Anda berhasil dikirim.</div>";
                    // Update Rating Avg Hotel (Opsional, biar realtime)
                    $conn->query("UPDATE hotel SET ratingAvg = (SELECT AVG(ratingSkor) FROM ulasan WHERE idHotel=$idHotel) WHERE idHotel=$idHotel");
                    $hotel['ratingAvg'] = ($hotel['ratingAvg'] * 0 + $ratingInput) / 1; // Dummy update variable view
                    header("Refresh:0"); // Refresh page agar data muncul
                } else {
                    $pesanUlasan = "<div class='alert-box alert-error'>Error: " . $conn->error . "</div>";
                }
            } else {
                $pesanUlasan = "<div class='alert-box alert-error'>Anda sudah memberikan ulasan untuk kunjungan terakhir Anda.</div>";
            }
        } else {
            $pesanUlasan = "<div class='alert-box alert-error'>Anda belum memiliki riwayat reservasi di hotel ini.</div>";
        }
    }
}

// B. STATISTIK ULASAN (READ STATS)
// Hitung total ulasan & distribusi bintang
$sqlStats = "SELECT 
                COUNT(*) as total,
                AVG(ratingSkor) as rata_rata,
                SUM(CASE WHEN ratingSkor >= 9 THEN 1 ELSE 0 END) as star9,
                SUM(CASE WHEN ratingSkor >= 8 AND ratingSkor < 9 THEN 1 ELSE 0 END) as star8,
                SUM(CASE WHEN ratingSkor >= 7 AND ratingSkor < 8 THEN 1 ELSE 0 END) as star7,
                SUM(CASE WHEN ratingSkor >= 6 AND ratingSkor < 7 THEN 1 ELSE 0 END) as star6,
                SUM(CASE WHEN ratingSkor < 6 THEN 1 ELSE 0 END) as star5
             FROM ulasan WHERE idHotel = $idHotel";
$stats = $conn->query($sqlStats)->fetch_assoc();
$totalUlasan = $stats['total'];
$avgRating = number_format($stats['rata_rata'] ?? 0, 1);

// Helper function persentase bar
function calcPercent($count, $total) {
    if($total == 0) return 0;
    return ($count / $total) * 100;
}

// C. DAFTAR KOMENTAR DENGAN PAGINATION (READ LIST)
$limit = 3; // Sesuai request: 3 komen per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Query ambil ulasan + nama user (Join Pelanggan)
$sqlReviews = "SELECT u.*, p.nama 
               FROM ulasan u 
               JOIN pelanggan p ON u.idUser = p.idUser 
               WHERE u.idHotel = $idHotel 
               ORDER BY u.tanggalUlasan DESC 
               LIMIT $limit OFFSET $offset";
$resReviews = $conn->query($sqlReviews);

// Hitung total pages
$totalPages = ceil($totalUlasan / $limit);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($hotel['nama']) ?> - NginapYuk!</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/hotelStyle.css">
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="main-wrapper">
        
        <div class="content-card">
            <div class="hotel-header">
                <div class="header-left">
                    <h1 class="hotel-title">
                        <?= htmlspecialchars($hotel['nama']) ?> 
                        <span class="stars"><?php for($i=0; $i<$hotel['kelas']; $i++) echo '★'; ?></span>
                    </h1>
                    <p class="hotel-address"><?= htmlspecialchars($hotel['alamatLengkap']) ?></p>
                </div>
                <div class="header-right">
                    <div class="price-container">
                        <?php 
                        $sqlMinPrice = "SELECT MIN(harga) as min_harga FROM tipekamar WHERE idHotel = $idHotel";
                        $minPrice = $conn->query($sqlMinPrice)->fetch_assoc()['min_harga'];
                        ?>
                        <span class="price-final">Rp <?= number_format($minPrice, 0, ',', '.') ?></span>
                    </div>
                    <button class="btn-pilih" onclick="document.getElementById('pilihan-kamar').scrollIntoView({behavior: 'smooth'})">Pilih Kamar</button>
                </div>
            </div>

            <div class="gallery-grid">
                <div class="gallery-main">
                    <img src="<?= $pathHotel . $fotoCover ?>" alt="Main View" onerror="this.src='https://placehold.co/800x600/00AEEF/ffffff?text=Image+Not+Found'">
                </div>
                <div class="gallery-sub">
                    <?php 
                    $count = 0;
                    foreach ($fotoGaleri as $foto) {
                        if ($count >= 6) break;
                        echo '<img src="' . $pathHotel . $foto . '" alt="Sub View" onerror="this.src=\'https://placehold.co/400x300/1F12D4/ffffff?text=Sub+Image\'">';
                        $count++;
                    }
                    while ($count < 6) {
                         echo '<img src="https://placehold.co/400x300/E0E0E0/ffffff?text=NginapYuk" alt="Placeholder">';
                         $count++;
                    }
                    ?>
                </div>
            </div>

            <div class="details-grid">
                <div class="details-left">
                    <div class="info-box">
                        <h3>Kenapa Nginap Disini?</h3>
                        <div class="reasons-container">
                            <div class="reason-item"><div class="reason-icon-box"><i class="fa-solid fa-square-parking"></i></div><span>Parkir Pribadi</span></div>
                            <div class="reason-item"><div class="reason-icon-box"><i class="fa-solid fa-location-dot"></i></div><span>Lokasi Ideal</span></div>
                            <div class="reason-item"><div class="reason-icon-box"><i class="fa-solid fa-person-swimming"></i></div><span>Kolam Renang</span></div>
                            <div class="reason-item"><div class="reason-icon-box"><i class="fa-solid fa-wifi"></i></div><span>WiFi Kencang</span></div>
                        </div>
                    </div>

                    <div class="info-box">
                        <h3>Fasilitas Unggulan</h3>
                        <div class="facilities-list">
                            <?php foreach ($fasilitasHotelArr as $fasilitas) : ?>
                                <div class="facility-item"><i class="fa-solid fa-check"></i> <?= trim($fasilitas) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="description-box">
                        <h3>Deskripsi Hotel</h3>
                        <p><?= substr($hotel['deskripsi'], 0, 200) ?>...</p>
                        <a href="#deskripsi-lengkap" class="link-blue">Lihat semua</a>
                    </div>
                </div>

                <div class="details-right">
                    <div class="info-box rating-box">
                        <div class="rating-header">
                            <div class="score-box"><?= $avgRating ?><span class="score-total">/10</span></div>
                            <div class="score-label">
                                <?php 
                                    if($avgRating >= 9) echo "Luar Biasa";
                                    elseif($avgRating >= 8) echo "Sangat Baik";
                                    elseif($avgRating >= 7) echo "Baik";
                                    else echo "Cukup";
                                ?>
                            </div>
                        </div>
                        <p class="review-snippet">Berdasarkan <?= $totalUlasan ?> ulasan tamu yang terverifikasi.</p>
                        <a href="#ulasan-hotel" class="link-blue">Lihat semua ulasan</a>
                    </div>
                </div>
            </div>
        </div> 

        <div class="booking-filter-card">
            <div class="booking-header">
                <i class="fa-solid fa-calendar-days"></i> Atur Jadwal Menginap
            </div>
            
            <form class="booking-form" method="GET" action="hotelPage.php">
                <input type="hidden" name="idHotel" value="<?= $idHotel ?>">

                <div class="bf-group">
                    <label class="bf-label">Check-in</label>
                    <input type="date" name="checkin" class="bf-input" 
                           value="<?= $checkIn ?>" min="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="bf-group">
                    <label class="bf-label">Check-out</label>
                    <input type="date" name="checkout" class="bf-input" 
                           value="<?= $checkOut ?>" min="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="bf-group">
                    <label class="bf-label">Jumlah Kamar</label>
                    <input type="number" name="kamar" class="bf-input" 
                           value="<?= $jumlahKamar ?>" min="1" max="10" required>
                </div>

                <button type="submit" class="btn-update-date">Set Jadwal</button>
            </form>
        </div>

        <div id="pilihan-kamar">
            <?php
            $sqlKamar = "SELECT * FROM tipekamar WHERE idHotel = $idHotel";
            $resKamar = $conn->query($sqlKamar);

            if ($resKamar->num_rows > 0) {
                while ($kamar = $resKamar->fetch_assoc()) {
                    $idTipeKamar = $kamar['idtipeKamar'];
                    $sqlFotoKamar = "SELECT * FROM fototipekamar WHERE idTipeKamar = $idTipeKamar";
                    $resFotoKamar = $conn->query($sqlFotoKamar);
                    
                    $kamarCover = "https://placehold.co/400x300/e0e0e0/888?text=No+Image";
                    $kamarSubs = [];
                    while($fk = $resFotoKamar->fetch_assoc()){
                        if($fk['cover'] == 1) $kamarCover = $fk['urlFoto'];
                        else $kamarSubs[] = $fk['urlFoto'];
                    }
                    if (strpos($kamarCover, 'placehold') !== false && !empty($kamarSubs)) $kamarCover = $kamarSubs[0];

                    $fasilitasKamarArr = explode(',', $kamar['fasilitas']);
                    $hargaDasar = $kamar['harga'];
                    $hargaPaket = $hargaDasar + 250000 + 20000; 
            ?>
            <div class="content-card room-card">
                <h2 class="room-name"><?= htmlspecialchars($kamar['namaTipeKamar']) ?></h2>
                <div class="room-layout">
                    <div class="room-left">
                        <div class="room-gallery-grid">
                            <div class="room-main-img"><img src="<?= $pathKamar . $kamarCover ?>" alt="Main" onerror="this.src='https://placehold.co/400x300/e0e0e0/888?text=Room+Image'"></div>
                            <div class="room-sub-imgs">
                                <?php 
                                $subCount = 0;
                                foreach($kamarSubs as $sub) {
                                    if($subCount >= 2) break;
                                    echo '<img src="' . $pathKamar . $sub . '" alt="Sub" onerror="this.src=\'https://placehold.co/200x150/e0e0e0/888?text=Sub\'">';
                                    $subCount++;
                                }
                                ?>
                            </div>
                        </div>
                        <div class="room-facilities">
                            <div class="rf-item"><i class="fa-solid fa-user-group"></i> Kapasitas: <?= $kamar['kapasitas'] ?> Orang</div>
                            <div class="rf-item"><i class="fa-solid fa-ruler-combined"></i> <?= $kamar['ukuranKamar'] ?></div>
                            <?php foreach($fasilitasKamarArr as $f) : ?>
                                <div class="rf-item"><i class="fa-solid fa-check"></i> <?= trim($f) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="room-right">
                        <div class="room-table">
                            <div class="rt-header">
                                <div class="col-ringkasan">Ringkasan</div>
                                <div class="col-tamu">Jumlah Tamu</div>
                                <div class="col-harga">Harga</div>
                            </div>
                            
                            <div class="rt-row">
                                <div class="col-ringkasan">
                                    <ul class="benefit-list">
                                        <li><i class="fa-solid fa-xmark" style="color:red"></i> Tidak Termasuk Sarapan</li>
                                        <li><i class="fa-solid fa-circle-exclamation" style="color:orange"></i> Tidak bisa refund</li>
                                        <li><i class="fa-solid fa-check"></i> Bayar di Hotel</li>
                                    </ul>
                                </div>
                                <div class="col-tamu centered"><i class="fa-solid fa-user-group user-icon"></i></div>
                                <div class="col-harga centered-column">
                                    <span class="price-red">Rp <?= number_format($hargaDasar, 0, ',', '.') ?></span>
                                    
                                    <a href="bookingForm.php?idHotel=<?= $idHotel ?>&idKamar=<?= $idTipeKamar ?>&checkin=<?= $checkIn ?>&checkout=<?= $checkOut ?>&jumlah=<?= $jumlahKamar ?>&opsi=basic" 
                                        class="btn-pesan" style="text-decoration:none; text-align:center;">
                                        Pesan Kamar
                                    </a>
                                </div>
                            </div>

                            <div class="rt-row no-border">
                                <div class="col-ringkasan">
                                    <ul class="benefit-list">
                                        <li><i class="fa-solid fa-utensils" style="color:green"></i> Sarapan tersedia</li>
                                        <li><i class="fa-solid fa-check" style="color:green"></i> Bisa refund (Asuransi)</li>
                                    </ul>
                                </div>
                                <div class="col-tamu centered"><i class="fa-solid fa-user-group user-icon"></i></div>
                                <div class="col-harga centered-column">
                                    <span class="price-red">Rp <?= number_format($hargaPaket, 0, ',', '.') ?></span>
                                    
                                    <a href="bookingForm.php?idHotel=<?= $idHotel ?>&idKamar=<?= $idTipeKamar ?>&checkin=<?= $checkIn ?>&checkout=<?= $checkOut ?>&jumlah=<?= $jumlahKamar ?>&opsi=paket" 
                                        class="btn-pesan" style="text-decoration:none; text-align:center;">
                                        Pesan Kamar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
                } 
            } else {
                echo "<p style='text-align:center;'>Belum ada tipe kamar.</p>";
            }
            ?>
        </div>

        <div class="content-card" id="ulasan-hotel">
            <h2 class="room-name">Ulasan Hotel</h2>
            
            <div class="review-section-grid">
                
                <div class="review-summary-col">
                    <div class="review-card-box">
                        <div class="rc-header">
                            <div class="rc-score"><?= $avgRating ?><span class="rc-total">/10</span></div>
                            <div class="rc-label">
                                <div>
                                    <?php 
                                        if($avgRating >= 9) echo "Luar Biasa";
                                        elseif($avgRating >= 8) echo "Sangat Baik";
                                        elseif($avgRating >= 7) echo "Baik";
                                        else echo "Cukup";
                                    ?>
                                </div>
                                <div class="rc-count"><?= $totalUlasan ?> Ulasan</div>
                            </div>
                        </div>
                        <div class="rc-bars">
                            <div class="bar-row">
                                <span class="bar-label">9+</span>
                                <div class="progress-track"><div class="progress-fill" style="width: <?= calcPercent($stats['star9'], $totalUlasan) ?>%;"></div></div>
                            </div>
                            <div class="bar-row">
                                <span class="bar-label">8+</span>
                                <div class="progress-track"><div class="progress-fill" style="width: <?= calcPercent($stats['star8'], $totalUlasan) ?>%;"></div></div>
                            </div>
                            <div class="bar-row">
                                <span class="bar-label">7+</span>
                                <div class="progress-track"><div class="progress-fill" style="width: <?= calcPercent($stats['star7'], $totalUlasan) ?>%;"></div></div>
                            </div>
                            <div class="bar-row">
                                <span class="bar-label">6+</span>
                                <div class="progress-track"><div class="progress-fill" style="width: <?= calcPercent($stats['star6'], $totalUlasan) ?>%;"></div></div>
                            </div>
                            <div class="bar-row">
                                <span class="bar-label">5-</span>
                                <div class="progress-track"><div class="progress-fill" style="width: <?= calcPercent($stats['star5'], $totalUlasan) ?>%;"></div></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="review-list-col">
                    
                    <?= $pesanUlasan ?>

                    <div class="review-form-box">
                        <h4>Tulis Ulasan Anda</h4>
                        <form method="POST" action="hotelPage.php?idHotel=<?= $idHotel ?>#ulasan-hotel">
                            <div class="form-group">
                                <label>Berikan Rating (1-10)</label>
                                <select name="rating" class="form-control" required>
                                    <option value="" disabled selected>Pilih Skor...</option>
                                    <option value="10">10 - Luar Biasa</option>
                                    <option value="9">9 - Sangat Baik</option>
                                    <option value="8">8 - Baik</option>
                                    <option value="7">7 - Cukup Baik</option>
                                    <option value="6">6 - Cukup</option>
                                    <option value="5">5 - Biasa Saja</option>
                                    <option value="4">4 - Kurang</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Komentar</label>
                                <textarea name="komentar" class="form-control" rows="3" placeholder="Ceritakan pengalaman menginap Anda..." required></textarea>
                            </div>
                            <button type="submit" name="submit_review" class="btn-submit-review">Kirim Ulasan</button>
                        </form>
                    </div>

                    <?php 
                    if($resReviews->num_rows > 0) {
                        while($rev = $resReviews->fetch_assoc()) {
                            // Initials untuk avatar
                            $initials = strtoupper(substr($rev['nama'], 0, 2));
                            $tglIndo = date('d M Y', strtotime($rev['tanggalUlasan']));
                    ?>
                    <div class="review-item">
                        <div class="review-user">
                            <div class="user-avatar"><?= $initials ?></div>
                            <div class="user-info">
                                <div class="u-name"><?= htmlspecialchars($rev['nama']) ?></div>
                                <div class="u-date"><?= $tglIndo ?></div>
                            </div>
                            <div class="review-badge"><?= $rev['ratingSkor'] ?>/10</div>
                        </div>
                        <p class="review-text"><?= htmlspecialchars($rev['komentar']) ?></p>
                    </div>
                    <?php 
                        } 
                    } else {
                        echo "<p>Belum ada ulasan untuk hotel ini.</p>";
                    }
                    ?>

                    <?php if ($totalPages > 1): ?>
                    <div class="pagination-container">
                        <span class="page-label">Halaman: </span>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?idHotel=<?= $idHotel ?>&page=<?= $i ?>#ulasan-hotel" 
                               class="page-link <?= ($i == $page) ? 'active' : '' ?>">
                               <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if($page < $totalPages): ?>
                            <a href="?idHotel=<?= $idHotel ?>&page=<?= $page+1 ?>#ulasan-hotel" class="page-link next">
                                Berikutnya
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <div class="content-card" id="lokasi-hotel">
            <h2 class="room-name">Lokasi Hotel</h2>
            <div class="map-container">
                <iframe 
                    src="https://maps.google.com/maps?q=<?= urlencode($hotel['alamatLengkap']) ?>&t=&z=15&ie=UTF8&iwloc=&output=embed" 
                    width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy">
                </iframe>
            </div>
        </div>

        <div class="content-card" id="deskripsi-lengkap">
            <h2 class="room-name">Deskripsi Hotel</h2>
            <div class="desc-meta-data">
                <div class="meta-row"><strong>Diresmikan:</strong> <?= $hotel['tahunPeresmian'] ?></div>
                <div class="meta-row"><strong>Telepon:</strong> <?= htmlspecialchars($hotel['telepon']) ?></div>
                <div class="meta-row"><strong>Email:</strong> <?= htmlspecialchars($hotel['email']) ?></div>
            </div>
            <p class="desc-paragraph"><?= nl2br(htmlspecialchars($hotel['deskripsi'])) ?></p>
        </div>

    </div>

    <script src="js/hotel.js"></script>
</body>
</html>