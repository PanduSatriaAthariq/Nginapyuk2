<?php
session_start();
include '../koneksi.php';

// 1. Cek Login & Validasi Hotel
if (!isset($_SESSION['idUser']) || $_SESSION['role'] != 'admHotel' || empty($_SESSION['idHotel'])) {
    header("Location: ../index.php");
    exit();
}

$idHotel = $_SESSION['idHotel']; // AMBIL ID HOTEL DARI SESSION

$year = date('Y');
$today = date('Y-m-d');

// 2. Query Data (FILTER WHERE h.idHotel = $idHotel)

// A. Pendapatan Bulan Ini
$sqlPendapatan = "SELECT SUM(r.totalHarga) as omset 
                  FROM reservasi r 
                  JOIN tipekamar t ON r.idTipeKamar = t.idtipeKamar
                  WHERE r.statusReservasi = 'Completed' 
                  AND t.idHotel = '$idHotel' 
                  AND MONTH(r.tanggalCheckin) = '" . date('m') . "' 
                  AND YEAR(r.tanggalCheckin) = '" . date('Y') . "'";
$pendapatanBulanIni = $conn->query($sqlPendapatan)->fetch_assoc()['omset'] ?? 0;

// B. Check-in Hari Ini
$sqlCheckIn = "SELECT COUNT(*) as total 
               FROM reservasi r
               JOIN tipekamar t ON r.idTipeKamar = t.idtipeKamar
               WHERE r.statusReservasi = 'Confirmed' 
               AND r.tanggalCheckin = '" . date('Y-m-d') . "'
               AND t.idHotel = '$idHotel'";
$checkInToday = $conn->query($sqlCheckIn)->fetch_assoc()['total'];

// C. Check-out Hari Ini
$sqlCheckOut = "SELECT COUNT(*) as total 
                FROM reservasi r
                JOIN tipekamar t ON r.idTipeKamar = t.idtipeKamar
                WHERE r.statusReservasi = 'Check In' 
                AND r.tanggalCheckout = '" . date('Y-m-d') . "'
                AND t.idHotel = '$idHotel'";
$checkOutToday = $conn->query($sqlCheckOut)->fetch_assoc()['total'];

// D. Kamar Tersedia (Simulasi: Total Kamar Hotel 50 - Kamar Terisi Sekarang)
$totalKamarHotel = 50; 
$sqlTerisi = "SELECT COUNT(*) as total FROM reservasi WHERE statusReservasi = 'Check In'";
$kamarTerisi = $conn->query($sqlTerisi)->fetch_assoc()['total'];
$kamarTersedia = $totalKamarHotel - $kamarTerisi;

// --- 3. DATA UNTUK CHART (PENDAPATAN PER BULAN) ---
// Kita buat array kosong isi 12 bulan (index 1-12)
$dataChart = array_fill(1, 12, 0); 

$sqlChart = "SELECT MONTH(tanggalCheckin) as bulan, SUM(totalHarga) as total 
             FROM reservasi 
             WHERE statusReservasi = 'Completed' AND YEAR(tanggalCheckin) = '$year'
             GROUP BY MONTH(tanggalCheckin)";
$resChart = $conn->query($sqlChart);

while($row = $resChart->fetch_assoc()) {
    $dataChart[$row['bulan']] = $row['total'];
}
// Konversi ke format string untuk JavaScript (contoh: "1000, 2000, 500...")
$chartDataString = implode(',', array_values($dataChart));


// --- 4. DATA TABEL (CHECK-IN HARI INI) ---
$sqlTamu = "SELECT r.*, t.namaTipeKamar 
            FROM reservasi r 
            JOIN tipekamar t ON r.idTipeKamar = t.idtipeKamar
            WHERE r.statusReservasi = 'Confirmed' AND r.tanggalCheckin = '$today'
            LIMIT 5";
$resTamu = $conn->query($sqlTamu);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Manager - NginapYuk!</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1F12D4',   // Biru NginapYuk
                        accent: '#FCC900',    // Kuning
                        dark: '#000000',
                        light: '#ffffff',
                        'gray-bg': '#F3F4F6'
                    }
                }
            }
        }
    </script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-gray-bg text-gray-800">

    <div class="flex h-screen overflow-hidden">
        
        <aside class="w-64 bg-white shadow-xl flex flex-col z-20 hidden md:flex">
            <div class="h-20 flex items-center justify-center border-b border-gray-100">
                <h1 class="text-2xl font-bold text-primary italic">NginapYuk!</h1>
            </div>
            
            <nav class="flex-1 py-6 px-4 space-y-2">
                <a href="dashboard.php" class="flex items-center px-4 py-3 bg-primary text-white rounded-xl shadow-md transition-all">
                    <i class="fa-solid fa-chart-pie w-6"></i>
                    <span class="font-medium">Dashboard</span>
                </a>

                <a href="reservasi.php" class="flex items-center px-4 py-3 text-gray-500 hover:bg-blue-50 hover:text-primary rounded-xl transition-all">
                    <i class="fa-solid fa-calendar-check w-6"></i>
                    <span class="font-medium">Reservasi</span>
                </a>

                <a href="kamar.php" class="flex items-center px-4 py-3 text-gray-500 hover:bg-blue-50 hover:text-primary rounded-xl transition-all">
                    <i class="fa-solid fa-bed w-6"></i>
                    <span class="font-medium">Kamar</span>
                </a>

                <a href="ulasan.php" class="flex items-center px-4 py-3 text-gray-500 hover:bg-blue-50 hover:text-primary rounded-xl transition-all">
                    <i class="fa-solid fa-star w-6"></i>
                    <span class="font-medium">Ulasan Tamu</span>
                </a>
            </nav>

            <div class="p-4 border-t border-gray-100">
                <a href="../logout.php" class="flex items-center px-4 py-3 text-red-500 hover:bg-red-50 rounded-xl transition-all">
                    <i class="fa-solid fa-right-from-bracket w-6"></i>
                    <span class="font-medium">Logout</span>
                </a>
            </div>
        </aside>

        <div class="flex-1 flex flex-col overflow-hidden relative">
            
            <header class="h-20 bg-white shadow-sm flex items-center justify-between px-8 z-10">
                <div class="flex items-center gap-4">
                    <button class="md:hidden text-gray-500 text-2xl"><i class="fa-solid fa-bars"></i></button>
                    <h2 class="text-xl font-bold text-gray-700">Overview Hotel</h2>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-bold text-gray-700"><?= $_SESSION['nama'] ?? 'Admin' ?></p>
                        <p class="text-xs text-gray-400">Manager Hotel</p>
                    </div>
                    <div class="h-10 w-10 bg-primary rounded-full flex items-center justify-center text-white font-bold shadow-lg ring-2 ring-blue-100">
                        MH
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-bg p-6 lg:p-8">
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border-l-4 border-primary">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-400 text-xs font-semibold uppercase tracking-wider">Pendapatan Bulan Ini</p>
                                <h3 class="text-2xl font-bold text-gray-800 mt-1">Rp <?= number_format($pendapatanBulanIni/1000000, 1, ',', '.') ?>jt</h3>
                            </div>
                            <div class="p-2 bg-blue-50 rounded-lg text-primary">
                                <i class="fa-solid fa-wallet text-xl"></i>
                            </div>
                        </div>
                        <p class="text-green-500 text-xs mt-4 font-medium flex items-center">
                            <i class="fa-solid fa-circle-check mr-1"></i> Data Realtime
                        </p>
                    </div>

                    <div class="bg-white p-6 rounded-2xl shadow-sm border-l-4 border-accent">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-400 text-xs font-semibold uppercase tracking-wider">Check-in Hari Ini</p>
                                <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= $checkInToday ?> Tamu</h3>
                            </div>
                            <div class="p-2 bg-yellow-50 rounded-lg text-yellow-600">
                                <i class="fa-solid fa-suitcase-rolling text-xl"></i>
                            </div>
                        </div>
                        <p class="text-gray-400 text-xs mt-4">Perlu disiapkan resepsionis</p>
                    </div>

                    <div class="bg-white p-6 rounded-2xl shadow-sm border-l-4 border-red-400">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-400 text-xs font-semibold uppercase tracking-wider">Check-out Hari Ini</p>
                                <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= $checkOutToday ?> Kamar</h3>
                            </div>
                            <div class="p-2 bg-red-50 rounded-lg text-red-500">
                                <i class="fa-solid fa-door-open text-xl"></i>
                            </div>
                        </div>
                        <p class="text-gray-400 text-xs mt-4">Perlu housekeeping segera</p>
                    </div>

                    <div class="bg-white p-6 rounded-2xl shadow-sm border-l-4 border-green-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-400 text-xs font-semibold uppercase tracking-wider">Kamar Tersedia</p>
                                <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= $kamarTersedia ?> Unit</h3>
                            </div>
                            <div class="p-2 bg-green-50 rounded-lg text-green-600">
                                <i class="fa-solid fa-key text-xl"></i>
                            </div>
                        </div>
                        <p class="text-blue-500 text-xs mt-4 font-medium">Dari Total 50 Kamar</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                    <div class="bg-white p-6 rounded-2xl shadow-sm lg:col-span-2">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="font-bold text-gray-800 text-lg">Analisis Pendapatan</h3>
                            <select class="bg-gray-50 border-none text-sm text-gray-500 rounded-lg p-2 focus:ring-0 cursor-pointer">
                                <option>Tahun <?= date('Y') ?></option>
                            </select>
                        </div>
                        <div class="relative h-72 w-full">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>

                    <div class="bg-primary text-white p-6 rounded-2xl shadow-lg relative overflow-hidden">
                        <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 bg-white opacity-10 rounded-full"></div>
                        <div class="absolute bottom-0 left-0 -ml-8 -mb-8 w-24 h-24 bg-accent opacity-20 rounded-full"></div>

                        <h3 class="font-bold text-xl mb-1 relative z-10">Target Bulanan</h3>
                        <p class="text-blue-200 text-sm mb-6 relative z-10"><?= date('F Y') ?></p>

                        <div class="mb-4 relative z-10">
                            <div class="flex justify-between text-sm mb-1">
                                <span>Progress</span>
                                <span class="font-bold">Automated</span>
                            </div>
                            <div class="w-full bg-blue-900 rounded-full h-2.5">
                                <div class="bg-accent h-2.5 rounded-full" style="width: 70%"></div>
                            </div>
                        </div>
                        
                        <div class="mt-8 relative z-10">
                            <p class="text-sm text-blue-200">Kamar Terfavorit</p>
                            <p class="text-2xl font-bold">Deluxe Room</p>
                            <p class="text-xs text-accent mt-1"><i class="fa-solid fa-trophy"></i> Berdasarkan Data</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                        <h3 class="font-bold text-gray-800 text-lg">Jadwal Check-in Hari Ini (<?= date('d M Y') ?>)</h3>
                        <a href="reservasi.php?status=Confirmed" class="text-primary text-sm font-medium hover:underline">Lihat Semua</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold">
                                <tr>
                                    <th class="px-6 py-4">ID Booking</th>
                                    <th class="px-6 py-4">Nama Tamu</th>
                                    <th class="px-6 py-4">Tipe Kamar</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                                <?php if ($resTamu->num_rows > 0): ?>
                                    <?php while($tamu = $resTamu->fetch_assoc()): ?>
                                    <tr class="hover:bg-blue-50 transition-colors">
                                        <td class="px-6 py-4 font-medium text-primary">#<?= $tamu['idReservasi'] ?></td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold mr-3 text-primary">
                                                    <?= substr($tamu['namaPemesan'], 0, 2) ?>
                                                </div>
                                                <div>
                                                    <p class="font-semibold"><?= htmlspecialchars($tamu['namaPemesan']) ?></p>
                                                    <p class="text-xs text-gray-400"><?= $tamu['nomorPemesan'] ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4"><?= $tamu['namaTipeKamar'] ?></td>
                                        <td class="px-6 py-4"><span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-600">Confirmed</span></td>
                                        <td class="px-6 py-4">
                                            <a href="reservasi.php?status=Confirmed" class="bg-primary hover:bg-blue-800 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-colors">
                                                Proses
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-400">Tidak ada tamu yang check-in hari ini.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(31, 18, 212, 0.2)'); 
        gradient.addColorStop(1, 'rgba(31, 18, 212, 0)');

        // DATA DARI PHP DILEMPAR KE SINI
        const monthlyData = [<?= $chartDataString ?>]; 

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                datasets: [{
                    label: 'Pendapatan (IDR)',
                    data: monthlyData, // Pakai data dinamis
                    borderColor: '#1F12D4',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    pointBackgroundColor: '#FCC900',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [5, 5], color: '#f0f0f0' } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
</body>
</html>