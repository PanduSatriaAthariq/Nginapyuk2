<?php 
session_start();

include 'koneksi.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NginapYuk!</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    
</head>
<body>

    <section class="navHero">

        <?php include 'navbar.php'; ?>

        <header class="hero">
            <div class="heroWrapper">
                <div class="heroContent">
                    <h1 class="tagline">Cari Hotel, Yuk<span>!</span></h1>

                    <form action="search.php" method="GET" class="searchBar">
                        <div class="searchItem">
                            <label>Lokasi</label>
                            <div class="input">
                                <i class=""></i>
                                <input type="text" name="city" placeholder="Mau nginep di mana?" required>
                            </div>
                        </div>
                        
                        <div class="searchItem">
                            <label>Check-In</label>
                            <div class="input" style="position: relative;">
                                <input type="text" id="checkInText" placeholder="Tanggal check-in" readonly style="cursor: pointer;">
                                <input type="date" id="checkInDate" style="position: absolute; top: 50%; left: 0; opacity: 0; z-index: -1;">
                            </div>
                        </div>

                        <div class="searchItem">
                            <label>Check-Out</label>
                            <div class="input" style="position: relative;">
                                <input type="text" id="checkOutText" placeholder="Tanggal check-out" readonly style="cursor: pointer;">
                                <input type="date" id="checkOutDate" style="position: absolute; top: 50%; left: 0; opacity: 0; z-index: -1;">
                            </div>
                        </div>
                        
                        <button type="submit" class="searchBtn">
                            <i class="fa-solid fa-magnifying-glass" style="color: #ffffff;"></i>
                        </button>
                    </form>
                </div>
            </div>
        </header>
    </section>

    <section class="quickMenuSection">
        <div class="quickMenuContainer">
            
            <a href="history.php" class="menuItem">
                <div class="iconBox">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <span>Cek Pesanan</span>
            </a>

            <div class="menuDivider"></div>

            <a href="https://wa.me/6281218101544" target="_blank" class="menuItem">
                <div class="iconBox">
                    <i class="fa-solid fa-headset"></i>
                </div>
                <span>Bantuan</span>
            </a>

        </div>
    </section>

    <section class="popDest">
        <h1>Destinasi populer di Indonesia</h1>

        <div class="carouselWrap">

            <button id="prevBtn" class="navBtn leftBtn hidden">
            <i class="fa-solid fa-caret-left"></i>
            </button>

            <div class="cityCardContainer" id="cardContainer">
                <?php
                // Query ambil semua data kota
                $sqlKota = "SELECT * FROM kota";
                $resultKota = $conn->query($sqlKota);

                if ($resultKota->num_rows > 0) {
                    // Looping data kota
                    while($row = $resultKota->fetch_assoc()) {
                ?>
                        <a href="search.php?city=<?php echo urlencode($row['nama']); ?>" class="cityCard">
                            <img class="cityImage" src="image/cityImage/<?php echo $row['fotoKota']; ?>" alt="<?php echo $row['nama']; ?>">
                            <h2><?php echo $row['nama']; ?></h2>
                            <p>3.245 Hotel</p>
                        </a>
                <?php 
                    }
                } else {
                    echo "<p>Belum ada data destinasi.</p>";
                }
                ?>
            </div>

            <button id="nextBtn" class="navBtn rightBtn">
            <i class="fa-solid fa-caret-right"></i>
            </button>

        </div>
    </section>

    <section class="promo">
        <div class="promoWrap">
            <h1>Promo untuk kamu</h1>
            <div class="promoCardWrap">
                <img class="promoCard" src="image/promoImage/Diskon Awal.png" alt="">
                <img class="promoCard" src="image/promoImage/Harga Terbaik-1.png" alt="">
                <img class="promoCard" src="image/promoImage/Harga Terbaik-2.png" alt="">
                <img class="promoCard" src="image/promoImage/Harga Terbaik.png" alt="">
            </div>
        </div>
    </section>

    <section class="hotelRec">
        <h1>Rekomendasi hotel</h1>
        <div class="hotelCardContainer">
            <?php

            $sqlRec = "SELECT h.idHotel, h.nama, h.kelas, h.ratingAvg, h.alamatLengkap, f.urlFoto, MIN(t.harga) AS hargaTerendah
                       FROM hotel h
                       JOIN fotohotel f ON h.idHotel = f.idHotel
                       LEFT JOIN tipekamar t ON h.idHotel = t.idHotel
                       WHERE f.cover = 1
                       GROUP BY h.idHotel
                       LIMIT 8"; 

            $resultRec = $conn->query($sqlRec);

            if ($resultRec->num_rows > 0) {
                while($hotel = $resultRec->fetch_assoc()) {
                    $hargaRaw = $hotel['hargaTerendah'];
                    $hargaDisplay = $hargaRaw ? "Rp" . number_format($hargaRaw, 0, ',', '.') : "Cek Detail";
                    $bintang = intval($hotel['kelas']);
            ?>
            
            <a href="hotelPage.php?idHotel=<?php echo $hotel['idHotel']; ?>" class="hotelCard">
                <div class="cardHeader">
                    <img class="imgHotel" src="image/hotelImage/<?php echo $hotel['urlFoto']; ?>" alt="<?php echo $hotel['nama']; ?>">
                    <div class="ratingBadge"><?php echo $hotel['ratingAvg']; ?></div>
                </div>

                <div class="cardBody">
                    <h3 class="hotelName"><?php echo $hotel['nama']; ?></h3>
                    
                    <div class="starRating">
                        <?php
                        for ($i = 0; $i < $bintang; $i++) {
                            echo '<i class="fa-solid fa-star"></i>';
                        }
                        ?>
                    </div>
                    
                    <p class="address">
                        <?php echo $hotel['alamatLengkap']; ?>
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
                echo "<p style='padding:20px;'>Data hotel tidak tersedia saat ini.</p>";
            }
            ?>
        </div>
    </section>

    <section class="hotelbyCity">
        <h1>Cari hotel berdasarkan destinasimu</h1>

        <div class="cityTabs">
            <button class="tabBtn active" data-city="jakarta">Jakarta</button>
            <button class="tabBtn" data-city="bandung">Bandung</button>
            <button class="tabBtn" data-city="surabaya">Surabaya</button>
            <button class="tabBtn" data-city="jogjakarta">Jogjakarta</button>
            <button class="tabBtn" data-city="bali">Bali</button>
            <button class="tabBtn" data-city="semarang">Semarang</button>
            <button class="tabBtn" data-city="padang">Padang</button>
            <button class="tabBtn" data-city="medan">Medan</button>
            <button class="tabBtn" data-city="makassar">Makassar</button>
            <button class="tabBtn" data-city="bengkulu">Bengkulu</button>
        </div>

        <div class="hotelCardContainer" id="dynamicHotelGrid">
            </div>

        <div class="viewAllContainer">
            <a href="search.php?city=jakarta" target="_blank" id="viewAllBtn" class="btnPrimary">LIHAT SEMUA</a>
        </div>
    </section>

    <section class="trust">
        <h1 class="headline">
            Kenapa booking di<br>
            <span class="highlight">NginapYuk<span class="acsen">!</span></span>
            ?
        </h1>
        <div class="trustCardContainer">
            <div class="trustCard">
                <i class="fa-solid fa-credit-card"></i>
                <h3>Pembayaran Aman</h3>
            </div>
            <div class="trustCard">
                <i class="fa-solid fa-hand-holding-dollar"></i>
                <h3>Jaminan Harga Terbaik</h3>
            </div>
            <div class="trustCard">
                <i class="fa-solid fa-headset"></i>
                <h3>Layanan Pelanggan 24/7</h3>
            </div>
        </div>
    </section>

    <section class="sponsor">
        <div class="container">
            <h4>Partner Resmi</h4>
            <img class="sponsBanner" src="image/sponsor.png" alt="sponsor">
        </div>
    </section>

    <section class="mobile">
        <a href="https://youtu.be/xvFZjo5PgG0?si=cb4s62WkAHPCLfJS" target="_blank" style="display:contents;">
            <img class="mobileBanner" src="image/mobileBanner.png" alt="Download NginapYuk App">
        </a>
    </section>

    <?php include 'footer.php'; ?>


    <script src="js/scriptDate.js"></script>
    <script src="js/scriptCarousel.js"></script>
    <script src="js/scriptCity.js"></script>

</body> 
</html>