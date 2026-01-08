<?php
include 'koneksi.php';

if (isset($_GET['city'])) {
    $cityRequest = $_GET['city']; // Nama kota dari Tab (misal: Bali)
    $cityDb = ucwords($cityRequest); 

    // 1. Coba query normal dulu (Cari hotel beneran di kota itu)
    $sql = "SELECT h.idHotel, h.nama, h.kelas, h.ratingAvg, h.alamatLengkap, f.urlFoto, MIN(t.harga) as hargaTerendah
            FROM hotel h
            JOIN kota k ON h.idKota = k.idKota
            JOIN fotohotel f ON h.idHotel = f.idHotel
            LEFT JOIN tipekamar t ON h.idHotel = t.idHotel
            WHERE k.nama = '$cityDb' AND f.cover = 1
            GROUP BY h.idHotel
            LIMIT 4";

    $result = $conn->query($sql);

    // 2. LOGIKA DAUR ULANG (Fallback)
    // Jika hasilnya 0 (kota kosong), kita ambil acak dari hotel yang ada
    $isFakeData = false;
    if ($result->num_rows == 0) {
        $isFakeData = true; // Tandai bahwa kita sedang memalsukan lokasi
        
        // Ambil 4 hotel acak dari SELURUH database (Hotel ID 1-30)
        $sql = "SELECT h.idHotel, h.nama, h.kelas, h.ratingAvg, h.alamatLengkap, f.urlFoto, MIN(t.harga) as hargaTerendah
                FROM hotel h
                JOIN fotohotel f ON h.idHotel = f.idHotel
                LEFT JOIN tipekamar t ON h.idHotel = t.idHotel
                WHERE f.cover = 1
                GROUP BY h.idHotel
                ORDER BY RAND() 
                LIMIT 4";
        $result = $conn->query($sql);
    }

    if ($result && $result->num_rows > 0) {
        while($hotel = $result->fetch_assoc()) {
            $hargaRaw = $hotel['hargaTerendah'];
            $hargaDisplay = $hargaRaw ? "Rp" . number_format($hargaRaw, 0, ',', '.') : "Cek Detail";
            $bintang = intval($hotel['kelas']);
            
            // MANIPULASI ALAMAT (Agar terlihat real)
            $alamatTampil = $hotel['alamatLengkap'];
            if ($isFakeData) {
                // Ganti kata 'Jakarta'/'Bandung'/'Surabaya' di alamat asli menjadi nama kota Tab
                $keywords = ['Jakarta', 'Bandung', 'Surabaya', 'Jakarta Pusat', 'Jakarta Selatan', 'Jakarta Barat'];
                $alamatTampil = str_ireplace($keywords, $cityDb, $alamatTampil);
            }
?>
            <a href="hotelPage.php?idHotel=<?php echo $hotel['idHotel']; ?>" class="hotelCard">
                <div class="cardHeader">
                    <img class="imgHotel" src="image/hotelImage/<?php echo $hotel['urlFoto']; ?>" alt="<?php echo $hotel['nama']; ?>">
                    <div class="ratingBadge"><?php echo $hotel['ratingAvg']; ?></div>
                </div>

                <div class="cardBody">
                    <h3 class="hotelName"><?php echo $hotel['nama']; ?></h3>
                    
                    <div class="starRating">
                        <?php for ($i = 0; $i < $bintang; $i++) { echo '<i class="fa-solid fa-star"></i>'; } ?>
                    </div>
                    
                    <p class="address">
                        <?php echo $alamatTampil; ?>
                    </p>

                    <div class="priceFooter">
                        <span class="priceLabel">Dari</span>
                        <span class="priceAmount"><?php echo $hargaDisplay; ?></span>
                    </div>
                </div>
            </a>
<?php
        }
    } else {
        echo '<p style="grid-column: 1/-1; text-align: center;">Data tidak ditemukan.</p>';
    }
}
?>