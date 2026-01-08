<?php
session_start();
include '../koneksi.php';

// 1. CEK LOGIN & HOTEL (UPDATED)
if (!isset($_SESSION['idUser']) || $_SESSION['role'] != 'admHotel' || empty($_SESSION['idHotel'])) {
    header("Location: ../index.php");
    exit();
}

$idHotel = $_SESSION['idHotel'];

// 2. LOGIC HAPUS
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action_delete'])) {
    $idUlasan = intval($_POST['id_ulasan']);
    $stmt = $conn->prepare("DELETE FROM ulasan WHERE idUlasan = ?");
    $stmt->bind_param("i", $idUlasan);
    if ($stmt->execute()) { header("Location: ulasan.php?msg=deleted"); exit(); }
}

// 3. LOGIC STATISTIK (FILTER BY HOTEL)
// Tambahkan WHERE idHotel = $idHotel
$sqlStats = "SELECT COUNT(*) as total_ulasan, AVG(ratingSkor) as avg_rating FROM ulasan WHERE idHotel = '$idHotel'";
$stats = $conn->query($sqlStats)->fetch_assoc();
$avgRating = number_format($stats['avg_rating'], 1);
$totalUlasan = $stats['total_ulasan'];

// 4. LOGIC TAMPIL DATA (FILTER BY HOTEL)
// Tambahkan WHERE u.idHotel = $idHotel
$sql = "SELECT u.*, p.nama as namaTamu, r.idReservasi, h.nama as namaHotel 
        FROM ulasan u
        JOIN pelanggan p ON u.idUser = p.idUser
        JOIN reservasi r ON u.idReservasi = r.idReservasi
        JOIN hotel h ON u.idHotel = h.idHotel
        WHERE u.idHotel = '$idHotel'
        ORDER BY u.tanggalUlasan DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ulasan Tamu - NginapYuk!</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script> tailwind.config = { theme: { extend: { colors: { primary: '#1F12D4', accent: '#FCC900', 'gray-bg': '#F3F4F6' }, fontFamily: { sans: ['Outfit', 'sans-serif'] } } } } </script>
</head>
<body class="bg-gray-bg text-gray-800 font-sans">
<div class="flex h-screen overflow-hidden">
    <aside class="w-64 bg-white shadow-xl flex flex-col z-20 hidden md:flex">
        <div class="h-20 flex items-center justify-center border-b border-gray-100"><h1 class="text-2xl font-bold text-primary italic">NginapYuk!</h1></div>
        <nav class="flex-1 py-6 px-4 space-y-2">
            <a href="dashboard.php" class="flex items-center px-4 py-3 text-gray-500 hover:bg-blue-50 hover:text-primary rounded-xl transition-all"><i class="fa-solid fa-chart-pie w-6"></i> <span class="font-medium">Dashboard</span></a>
            <a href="reservasi.php" class="flex items-center px-4 py-3 text-gray-500 hover:bg-blue-50 hover:text-primary rounded-xl transition-all"><i class="fa-solid fa-calendar-check w-6"></i> <span class="font-medium">Reservasi</span></a>
            <a href="kamar.php" class="flex items-center px-4 py-3 text-gray-500 hover:bg-blue-50 hover:text-primary rounded-xl transition-all"><i class="fa-solid fa-bed w-6"></i> <span class="font-medium">Kamar</span></a>
            <a href="ulasan.php" class="flex items-center px-4 py-3 bg-primary text-white rounded-xl shadow-md transition-all"><i class="fa-solid fa-star w-6"></i> <span class="font-medium">Ulasan Tamu</span></a>
        </nav>
        <div class="p-4 border-t border-gray-100"><a href="../logout.php" class="flex items-center px-4 py-3 text-red-500 hover:bg-red-50 rounded-xl transition-all"><i class="fa-solid fa-right-from-bracket w-6"></i> <span class="font-medium">Logout</span></a></div>
    </aside>
    <div class="flex-1 flex flex-col overflow-hidden relative">
        <header class="h-20 bg-white shadow-sm flex items-center justify-between px-8 z-10">
            <h2 class="text-xl font-bold text-gray-700">Feedback & Reputasi</h2>
            <div class="flex items-center gap-4"><div class="text-right hidden sm:block"><p class="text-sm font-bold text-gray-700"><?= $_SESSION['nama'] ?? 'Admin' ?></p><p class="text-xs text-gray-400">Manager Hotel</p></div><div class="h-10 w-10 bg-primary rounded-full flex items-center justify-center text-white font-bold">MH</div></div>
        </header>
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-bg p-6 lg:p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-primary text-white p-6 rounded-2xl shadow-lg flex items-center justify-between relative overflow-hidden">
                    <div class="relative z-10"><p class="text-blue-200 text-xs font-semibold uppercase tracking-wider">Rata-rata Rating</p><div class="flex items-end gap-2"><h3 class="text-4xl font-bold mt-1"><?= $avgRating ?></h3><span class="text-sm mb-1 opacity-80">/ 10.0</span></div></div>
                    <div class="h-12 w-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center text-accent relative z-10"><i class="fa-solid fa-star text-2xl"></i></div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border-l-4 border-accent flex items-center justify-between">
                    <div><p class="text-gray-400 text-xs font-semibold uppercase tracking-wider">Total Ulasan Masuk</p><h3 class="text-3xl font-bold text-gray-800 mt-1"><?= $totalUlasan ?> <span class="text-sm font-normal text-gray-500">Komentar</span></h3></div>
                    <div class="h-12 w-12 bg-yellow-50 rounded-full flex items-center justify-center text-accent"><i class="fa-solid fa-comments text-xl"></i></div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
                <div class="p-6 border-b border-gray-100"><h3 class="font-bold text-gray-800 text-lg">Komentar Terbaru</h3></div>
                <div class="divide-y divide-gray-100">
                    <?php if ($result->num_rows > 0): while($row = $result->fetch_assoc()): $score = $row['ratingSkor']; $scoreColor = 'text-red-500'; if($score >= 7) $scoreColor = 'text-yellow-500'; if($score >= 9) $scoreColor = 'text-green-500'; ?>
                    <div class="p-6 hover:bg-gray-50 transition-colors flex flex-col md:flex-row gap-6">
                        <div class="md:w-1/4 flex items-start gap-4">
                            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center font-bold text-gray-500 flex-shrink-0"><?= substr($row['namaTamu'], 0, 1) ?></div>
                            <div><p class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($row['namaTamu']) ?></p><p class="text-xs text-gray-400 mt-0.5"><?= date('d M Y', strtotime($row['tanggalUlasan'])) ?></p><div class="mt-2 text-xs bg-blue-50 text-blue-600 px-2 py-1 rounded inline-block">ID Booking: #<?= $row['idReservasi'] ?></div></div>
                        </div>
                        <div class="md:w-2/4">
                            <div class="flex items-center gap-2 mb-2"><div class="flex text-xs <?= $scoreColor ?>"><?php for($i=0; $i<5; $i++): ?><i class="fa-solid fa-star <?= ($i < ($score/2)) ? '' : 'text-gray-200' ?>"></i><?php endfor; ?></div><span class="font-bold text-sm <?= $scoreColor ?>"><?= $score ?>/10</span></div>
                            <p class="text-gray-600 text-sm leading-relaxed">"<?= htmlspecialchars($row['komentar']) ?>"</p>
                        </div>
                        <div class="md:w-1/4 flex md:flex-col items-center md:items-end justify-center gap-2">
                            <form method="POST" onsubmit="return confirm('Hapus ulasan?');"><input type="hidden" name="id_ulasan" value="<?= $row['idUlasan'] ?>"><input type="hidden" name="action_delete" value="1"><button type="submit" class="text-red-400 hover:text-red-600 hover:bg-red-50 px-3 py-2 rounded-lg text-xs font-medium transition-colors flex items-center gap-2"><i class="fa-solid fa-trash"></i> Hapus</button></form>
                        </div>
                    </div>
                    <?php endwhile; else: ?><div class="p-12 text-center text-gray-400">Belum ada ulasan.</div><?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>