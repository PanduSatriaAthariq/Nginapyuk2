<?php
session_start();
include '../koneksi.php';

// 1. CEK LOGIN & HOTEL (UPDATED)
if (!isset($_SESSION['idUser']) || $_SESSION['role'] != 'admHotel' || empty($_SESSION['idHotel'])) {
    header("Location: ../index.php");
    exit();
}

$idHotel = $_SESSION['idHotel'];

// 2. LOGIC UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action_edit'])) {
    $idTipe = intval($_POST['id_tipe']);
    $hargaBaru = $_POST['harga'];
    $fasilitasBaru = $_POST['fasilitas'];
    $stmt = $conn->prepare("UPDATE tipekamar SET harga = ?, fasilitas = ? WHERE idtipeKamar = ?");
    $stmt->bind_param("dsi", $hargaBaru, $fasilitasBaru, $idTipe);
    if ($stmt->execute()) { header("Location: kamar.php?msg=updated"); exit(); }
}

// 3. LOGIC TAMPIL DATA (FILTER BY HOTEL)
// Tambahkan WHERE t.idHotel = $idHotel
$sql = "SELECT t.*, h.nama as namaHotel, 
        (SELECT COUNT(*) FROM kamar k WHERE k.idTipeKamar = t.idtipeKamar) as jumlahUnit
        FROM tipekamar t 
        JOIN hotel h ON t.idHotel = h.idHotel 
        WHERE t.idHotel = '$idHotel' 
        ORDER BY t.harga ASC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kamar - NginapYuk!</title>
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
            <a href="kamar.php" class="flex items-center px-4 py-3 bg-primary text-white rounded-xl shadow-md transition-all"><i class="fa-solid fa-bed w-6"></i> <span class="font-medium">Kamar</span></a>
            <a href="ulasan.php" class="flex items-center px-4 py-3 text-gray-500 hover:bg-blue-50 hover:text-primary rounded-xl transition-all"><i class="fa-solid fa-star w-6"></i> <span class="font-medium">Ulasan Tamu</span></a>
        </nav>
        <div class="p-4 border-t border-gray-100"><a href="../logout.php" class="flex items-center px-4 py-3 text-red-500 hover:bg-red-50 rounded-xl transition-all"><i class="fa-solid fa-right-from-bracket w-6"></i> <span class="font-medium">Logout</span></a></div>
    </aside>
    <div class="flex-1 flex flex-col overflow-hidden relative">
        <header class="h-20 bg-white shadow-sm flex items-center justify-between px-8 z-10">
            <h2 class="text-xl font-bold text-gray-700">Inventory & Harga Kamar</h2>
            <div class="flex items-center gap-4"><div class="text-right hidden sm:block"><p class="text-sm font-bold text-gray-700"><?= $_SESSION['nama'] ?? 'Admin' ?></p><p class="text-xs text-gray-400">Manager Hotel</p></div><div class="h-10 w-10 bg-primary rounded-full flex items-center justify-center text-white font-bold">MH</div></div>
        </header>
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-bg p-6 lg:p-8">
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r shadow-sm"><p class="font-bold">Sukses!</p><p>Data tipe kamar berhasil diperbarui.</p></div>
            <?php endif; ?>
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold border-b border-gray-100">
                            <tr><th class="px-6 py-4">Nama Kamar</th><th class="px-6 py-4">Spesifikasi</th><th class="px-6 py-4">Fasilitas</th><th class="px-6 py-4 text-right">Harga</th><th class="px-6 py-4 text-center">Aksi</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                            <?php if ($result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-blue-50 transition-colors">
                                <td class="px-6 py-4"><div class="font-medium text-gray-800 text-lg"><?= htmlspecialchars($row['namaTipeKamar']) ?></div><div class="text-xs text-gray-400">ID: #<?= $row['idtipeKamar'] ?></div></td>
                                <td class="px-6 py-4 text-xs"><div class="flex flex-col gap-1"><span><i class="fa-solid fa-user w-4"></i> <?= $row['kapasitas'] ?> Org</span><span><i class="fa-solid fa-door-closed w-4"></i> <?= $row['jumlahUnit'] ?> Unit</span></div></td>
                                <td class="px-6 py-4"><p class="text-xs text-gray-500 line-clamp-2"><?= htmlspecialchars($row['fasilitas']) ?></p></td>
                                <td class="px-6 py-4 text-right"><span class="text-lg font-bold text-accent">Rp <?= number_format($row['harga'], 0, ',', '.') ?></span></td>
                                <td class="px-6 py-4 text-center"><button onclick="openEditModal('<?= $row['idtipeKamar'] ?>','<?= htmlspecialchars($row['namaTipeKamar']) ?>','<?= $row['harga'] ?>',`<?= htmlspecialchars($row['fasilitas']) ?>`)" class="bg-white border border-gray-200 hover:bg-primary hover:text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-all">Edit</button></td>
                            </tr>
                            <?php endwhile; else: ?><tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">Belum ada data.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
<div id="editModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all scale-95 opacity-0" id="modalContent">
        <form action="" method="POST">
            <div class="bg-primary p-4 flex justify-between items-center text-white"><h3 class="font-bold text-lg">Edit Kamar</h3><button type="button" onclick="closeModal()"><i class="fa-solid fa-xmark text-xl"></i></button></div>
            <div class="p-6 space-y-4">
                <input type="hidden" name="action_edit" value="1"><input type="hidden" id="editId" name="id_tipe">
                <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nama Tipe</label><input type="text" id="editNama" class="w-full bg-gray-100 border border-gray-200 rounded-lg px-4 py-2 text-gray-500 font-bold" readonly></div>
                <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1">Harga (IDR)</label><input type="number" id="editHarga" name="harga" required class="w-full border border-gray-300 rounded-lg px-4 py-2 font-bold"></div>
                <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1">Fasilitas</label><textarea id="editFasilitas" name="fasilitas" rows="4" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm"></textarea></div>
            </div>
            <div class="bg-gray-50 p-4 flex justify-end gap-3"><button type="button" onclick="closeModal()" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm">Batal</button><button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm">Simpan</button></div>
        </form>
    </div>
</div>
<script>
    const modal = document.getElementById('editModal'), modalContent = document.getElementById('modalContent');
    function openEditModal(id, nama, harga, fasilitas) {
        document.getElementById('editId').value = id; document.getElementById('editNama').value = nama;
        document.getElementById('editHarga').value = harga; document.getElementById('editFasilitas').value = fasilitas;
        modal.classList.remove('hidden'); setTimeout(() => { modalContent.classList.remove('scale-95', 'opacity-0'); modalContent.classList.add('scale-100', 'opacity-100'); }, 10);
    }
    function closeModal() {
        modalContent.classList.remove('scale-100', 'opacity-100'); modalContent.classList.add('scale-95', 'opacity-0'); setTimeout(() => { modal.classList.add('hidden'); }, 300);
    }
</script>
</body>
</html>