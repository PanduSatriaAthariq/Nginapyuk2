<?php 
include 'koneksi.php'; 

// 1. TANGKAP PARAMETER
$cityRequest = isset($_GET['city']) ? $_GET['city'] : 'Jakarta';
$cityDb = ucwords($cityRequest); 

$sortRequest = isset($_GET['sort']) ? $_GET['sort'] : '';
$starsRequest = isset($_GET['stars']) ? $_GET['stars'] : ''; // Cuma 1 angka (misal: "5")
$scoresRequest = isset($_GET['scores']) ? $_GET['scores'] : ''; // Cuma 1 angka (misal: "9")

// 2. BANGUN WHERE CLAUSE
$whereClauses = [];

// Filter Kota
$whereClauses[] = "k.nama = '$cityDb'";
$whereClauses[] = "f.cover = 1";

// Filter Bintang (Single Value)
if (!empty($starsRequest)) {
    $starVal = $conn->real_escape_string($starsRequest);
    // Menggunakan = karena cuma 1 pilihan
    $whereClauses[] = "h.kelas = '$starVal'";
}

// Filter Skor (Single Value)
if (!empty($scoresRequest)) {
    $scoreVal = $conn->real_escape_string($scoresRequest);
    $whereClauses[] = "h.ratingAvg >= $scoreVal";
}

$sqlWhere = implode(" AND ", $whereClauses);

// 3. SORTING
$orderClause = ""; 
switch ($sortRequest) {
    case 'price_asc': $orderClause = "ORDER BY hargaTerendah ASC"; break;
    case 'price_desc': $orderClause = "ORDER BY hargaTerendah DESC"; break;
    case 'class_desc': $orderClause = "ORDER BY h.kelas DESC"; break;
    default: $orderClause = ""; 
}

// 4. QUERY
$sql = "SELECT h.*, k.nama as namaKota, f.urlFoto, MIN(t.harga) as hargaTerendah
        FROM hotel h
        JOIN kota k ON h.idKota = k.idKota
        JOIN fotohotel f ON h.idHotel = f.idHotel
        LEFT JOIN tipekamar t ON h.idHotel = t.idHotel
        WHERE $sqlWhere
        GROUP BY h.idHotel
        $orderClause 
        LIMIT 10";

$result = $conn->query($sql);
$jumlahHotel = $result ? $result->num_rows : 0;

// Helper function check single value
function isChecked($val, $requestVal) {
    if ($val == $requestVal) echo 'checked';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel di <?php echo $cityDb; ?> - NginapYuk!</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/searchStyle.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="searchHeader">
        <form action="search.php" method="GET" class="searchBar">
            <div class="searchItem">
                <label>Lokasi</label>
                <div class="input">
                    <i class=""></i>
                    <input type="text" name="city" placeholder="Mau nginep di mana?" value="<?php echo htmlspecialchars($cityRequest); ?>" required>
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
    
    <main class="searchPageContainer">

        <aside class="searchSidebar" id="searchSidebar">
            <div class="mobileHeader">
                <button class="btnBack" onclick="toggleFilter()">
                    <i class="fa-solid fa-arrow-left"></i> Kembali
                </button>
                <h3>Filter</h3>
            </div>

            <div class="promoBanner">
                <img src="image/searchBanner.png" alt="Promo Diskon">
            </div>

            <div class="filterContainer">
                <h3>Kelas Hotel</h3>
                <div class="filterGroup">
                    <label class="customCheck">
                        <input type="checkbox" name="stars" value="5" onclick="selectOnlyThis(this)" <?php isChecked('5', $starsRequest); ?>>
                        <span class="checkmark"></span>
                        <div class="starRow">
                            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                        </div>
                    </label>
                    <label class="customCheck">
                        <input type="checkbox" name="stars" value="4" onclick="selectOnlyThis(this)" <?php isChecked('4', $starsRequest); ?>>
                        <span class="checkmark"></span>
                        <div class="starRow">
                            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                        </div>
                    </label>
                    <label class="customCheck">
                        <input type="checkbox" name="stars" value="3" onclick="selectOnlyThis(this)" <?php isChecked('3', $starsRequest); ?>>
                        <span class="checkmark"></span>
                        <div class="starRow">
                            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                        </div>
                    </label>
                </div>
                
                <hr class="divider">

                <h3>Skor Pengguna</h3>
                <div class="filterGroup">
                    <label class="customCheck">
                        <input type="checkbox" name="scores" value="9" onclick="selectOnlyThis(this)" <?php isChecked('9', $scoresRequest); ?>>
                        <span class="checkmark"></span>
                        <span class="scoreText"><strong>9+</strong> Luar Biasa</span>
                    </label>
                    <label class="customCheck">
                        <input type="checkbox" name="scores" value="8" onclick="selectOnlyThis(this)" <?php isChecked('8', $scoresRequest); ?>>
                        <span class="checkmark"></span>
                        <span class="scoreText"><strong>8+</strong> Sangat Baik</span>
                    </label>
                    <label class="customCheck">
                        <input type="checkbox" name="scores" value="7" onclick="selectOnlyThis(this)" <?php isChecked('7', $scoresRequest); ?>>
                        <span class="checkmark"></span>
                        <span class="scoreText"><strong>7+</strong> Baik</span>
                    </label>
                </div>

                <div class="filterAction">
                    <button class="btnApplyFilter">Terapkan Filter</button>
                </div>
            </div>
        </aside>

        <section class="searchResult">
            <div class="resultHeader">
                <p>Menampilkan <strong><?php echo $jumlahHotel; ?></strong> hotel di <?php echo $cityDb; ?></p>
                <div class="sortDropdown">
                    <span>Urutkan:</span>
                    <select id="sortDropdown">
                        <option value="" <?php if($sortRequest == '') echo 'selected'; ?>>Paling Relevan</option>
                        <option value="price_asc" <?php if($sortRequest == 'price_asc') echo 'selected'; ?>>Harga Terendah</option>
                        <option value="price_desc" <?php if($sortRequest == 'price_desc') echo 'selected'; ?>>Harga Tertinggi</option>
                        <option value="class_desc" <?php if($sortRequest == 'class_desc') echo 'selected'; ?>>Kelas Hotel (Tinggi ke Rendah)</option>
                    </select>
                </div>
            </div>

            <div class="hotelListContainer">
                <?php
                if ($jumlahHotel > 0) {
                    while($row = $result->fetch_assoc()) {
                        $hargaRaw = $row['hargaTerendah'];
                        $hargaDisplay = $hargaRaw ? "Rp" . number_format($hargaRaw, 0, ',', '.') : "Cek Detail";
                        $bintang = intval($row['kelas']);
                        $fasilitasRaw = $row['fasilitas'];
                        $fasilitasArray = array_slice(explode(',', $fasilitasRaw), 0, 4); 
                ?>
                <a href="hotelPage.php?idHotel=<?php echo $row['idHotel']; ?>" class="hotelListCard">
                    <div class="cardImage">
                        <img src="image/hotelImage/<?php echo $row['urlFoto']; ?>" alt="<?php echo $row['nama']; ?>">
                    </div>
                    <div class="cardInfo">
                        <div class="infoTop">
                            <div class="titleWrap">
                                <h2 class="hotelNameS"><?php echo $row['nama']; ?></h2>
                                <div class="starRow">
                                    <?php for($i=0; $i<$bintang; $i++){ echo '<i class="fa-solid fa-star"></i>'; } ?>
                                </div>
                            </div>
                            <div class="ratingBadgeS"><?php echo $row['ratingAvg']; ?></div>
                        </div>
                        <p class="location"><i class="fa-solid fa-location-dot"></i> <?php echo $row['alamatUmum']; ?></p>
                        <p class="addressS"><?php echo $row['alamatLengkap']; ?></p>
                        <div class="facilitiesPills">
                            <?php 
                            foreach($fasilitasArray as $fas) {
                                $icon = 'fa-check'; 
                                if(stripos($fas, 'WiFi') !== false) $icon = 'fa-wifi';
                                if(stripos($fas, 'AC') !== false) $icon = 'fa-snowflake';
                                if(stripos($fas, 'Parkir') !== false) $icon = 'fa-square-parking';
                                if(stripos($fas, 'Renang') !== false) $icon = 'fa-water';
                                if(stripos($fas, 'Restoran') !== false) $icon = 'fa-utensils';
                                if(stripos($fas, 'Gym') !== false || stripos($fas, 'Fitness') !== false) $icon = 'fa-dumbbell';
                                echo '<span class="pill"><i class="fa-solid '.$icon.'"></i> '.trim($fas).'</span>';
                            }
                            ?>
                        </div>
                        <hr class="cardDivider">
                        <div class="cardFooter">
                            <div class="priceBlock">
                                <span class="labelDari">Dari</span>
                                <span class="priceAmount"><?php echo $hargaDisplay; ?></span>
                            </div>
                        </div>
                    </div>
                </a>
                <?php 
                    } 
                } else {
                    echo '<div style="text-align:center; padding:50px;">
                            <h3>Tidak ada hotel yang cocok.</h3>
                          </div>';
                }
                ?>
            </div>
        </section>

    </main>

    <div class="mobileSortOverlay" id="mobileSortOverlay">
        <div class="mobileSortModal">
            <div class="modalHeader">
                <h3>Urutkan</h3>
                <button onclick="toggleSort()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="sortOptions">
                <button data-value="price_asc">Harga Terendah</button>
                <button data-value="price_desc">Harga Tertinggi</button>
                <button data-value="class_desc">Kelas Hotel (Tinggi ke Rendah)</button>
            </div>
        </div>
    </div>

    <div class="mobileFilterBar">
        <button onclick="toggleFilter()"><i class="fa-solid fa-filter"></i> Filter</button>
        <button onclick="toggleSort()"><i class="fa-solid fa-sort"></i> Urutkan</button>
    </div>

    <?php include 'footer.php'; ?>

    <script src="js/sort.js"></script>
    <script src="js/scriptDate.js"></script>

</body>
</html>