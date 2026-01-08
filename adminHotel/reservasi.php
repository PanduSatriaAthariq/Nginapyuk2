<?php
session_start();
include '../koneksi.php';

// 1. CEK LOGIN & HOTEL (UPDATED)
if (!isset($_SESSION['idUser']) || $_SESSION['role'] != 'admHotel' || empty($_SESSION['idHotel'])) {
    header("Location: ../index.php");
    exit();
}

$idHotel = $_SESSION['idHotel']; // Ambil ID Hotel Admin

// 2. LOGIC UPDATE STATUS
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action_status'])) {
    $idRes = intval($_POST['id_reservasi']);
    $statusBaru = $_POST['status_baru'];
    
    $stmt = $conn->prepare("UPDATE reservasi SET statusReservasi = ? WHERE idReservasi = ?");
    $stmt->bind_param("si", $statusBaru, $idRes);
    
    if ($stmt->execute()) {
        header("Location: reservasi.php?msg=updated");
        exit();
    }
}

// 3. LOGIC TAMPIL DATA (FILTER BY HOTEL)
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';

// Query Join dengan filter idHotel
$sql = "SELECT r.*, t.namaTipeKamar 
        FROM reservasi r 
        JOIN tipekamar t ON r.idTipeKamar = t.idtipeKamar 
        WHERE t.idHotel = '$idHotel'";

if ($filterStatus != 'all') {
    $sql .= " AND r.statusReservasi = '$filterStatus'";
}

$sql .= " ORDER BY r.tanggalCheckin DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Reservasi - NginapYuk!</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: '#1F12D4', accent: '#FCC900', 'gray-bg': '#F3F4F6' }, fontFamily: { sans: ['Outfit', 'sans-serif'] } } } }
    </script>
</head>
<body class="bg-gray-bg text-gray-800 font-sans">

<div class="flex h-screen overflow-hidden">
    <aside class="w-64 bg-white shadow-xl flex flex-col z-20 hidden md:flex">
        <div class="h-20 flex items-center justify-center border-b border-gray-100">
            <h1 class="text-2xl font-bold text-primary italic">NginapYuk!</h1>
        </div>
        <nav class="flex-1 py-6 px-4 space-y-2">
            <a href="dashboard.php" class="flex items-center px-4 py-3 text-gray-500 hover:bg-blue-50 hover:text-primary rounded-xl transition-all">
                <i class="fa-solid fa-chart-pie w-6"></i> <span class="font-medium">Dashboard</span>
            </a>
            <a href="reservasi.php" class="flex items-center px-4 py-3 bg-primary text-white rounded-xl shadow-md transition-all">
                <i class="fa-solid fa-calendar-check w-6"></i> <span class="font-medium">Reservasi</span>
            </a>
            <a href="kamar.php" class="flex items-center px-4 py-3 text-gray-500 hover:bg-blue-50 hover:text-primary rounded-xl transition-all">
                <i class="fa-solid fa-bed w-6"></i> <span class="font-medium">Kamar</span>
            </a>
            <a href="ulasan.php" class="flex items-center px-4 py-3 text-gray-500 hover:bg-blue-50 hover:text-primary rounded-xl transition-all">
                <i class="fa-solid fa-star w-6"></i> <span class="font-medium">Ulasan Tamu</span>
            </a>
        </nav>
        <div class="p-4 border-t border-gray-100">
            <a href="../logout.php" class="flex items-center px-4 py-3 text-red-500 hover:bg-red-50 rounded-xl transition-all">
                <i class="fa-solid fa-right-from-bracket w-6"></i> <span class="font-medium">Logout</span>
            </a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden relative">
        <header class="h-20 bg-white shadow-sm flex items-center justify-between px-8 z-10">
            <h2 class="text-xl font-bold text-gray-700">Data Reservasi Tamu</h2>
            <div class="flex items-center gap-4">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-gray-700"><?= $_SESSION['nama'] ?? 'Admin' ?></p>
                    <p class="text-xs text-gray-400">Manager Hotel</p>
                </div>
                <div class="h-10 w-10 bg-primary rounded-full flex items-center justify-center text-white font-bold">MH</div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-bg p-6 lg:p-8">
            <div class="flex flex-wrap gap-2 mb-6">
                <a href="?status=all" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $filterStatus == 'all' ? 'bg-primary text-white shadow-lg' : 'bg-white text-gray-500 hover:bg-gray-100' ?>">Semua</a>
                <a href="?status=Confirmed" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $filterStatus == 'Confirmed' ? 'bg-blue-500 text-white shadow-lg' : 'bg-white text-gray-500 hover:bg-gray-100' ?>">Confirmed</a>
                <a href="?status=Check In" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $filterStatus == 'Check In' ? 'bg-green-500 text-white shadow-lg' : 'bg-white text-gray-500 hover:bg-gray-100' ?>">Check In</a>
                <a href="?status=Completed" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $filterStatus == 'Completed' ? 'bg-gray-600 text-white shadow-lg' : 'bg-white text-gray-500 hover:bg-gray-100' ?>">Completed</a>
                <a href="?status=Cancelled" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $filterStatus == 'Cancelled' ? 'bg-red-500 text-white shadow-lg' : 'bg-white text-gray-500 hover:bg-gray-100' ?>">Cancelled</a>
            </div>

            <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-4">ID & Tamu</th>
                                <th class="px-6 py-4">Kamar & Durasi</th>
                                <th class="px-6 py-4">Jadwal</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                            <?php if ($result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): 
                                    $checkin = date('d M Y', strtotime($row['tanggalCheckin']));
                                    $checkout = date('d M Y', strtotime($row['tanggalCheckout']));
                                    $status = $row['statusReservasi'];
                                    $badgeColor = 'bg-gray-100 text-gray-600';
                                    if($status == 'Confirmed') $badgeColor = 'bg-blue-100 text-blue-700';
                                    if($status == 'Check In') $badgeColor = 'bg-green-100 text-green-700';
                                    if($status == 'Cancelled') $badgeColor = 'bg-red-100 text-red-700';
                                ?>
                                <tr class="hover:bg-blue-50 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-primary">#<?= $row['idReservasi'] ?></div>
                                        <div class="font-medium text-gray-800"><?= htmlspecialchars($row['namaPemesan']) ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-medium"><?= htmlspecialchars($row['namaTipeKamar']) ?></div>
                                        <div class="text-xs font-bold text-accent">Rp <?= number_format($row['totalHarga'],0,',','.') ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col gap-1">
                                            <span class="text-xs text-gray-500">In: <b class="text-gray-700"><?= $checkin ?></b></span>
                                            <span class="text-xs text-gray-500">Out: <b class="text-gray-700"><?= $checkout ?></b></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 rounded-full text-xs font-bold <?= $badgeColor ?>"><?= $status ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button onclick="openModal('<?= $row['idReservasi'] ?>', '<?= htmlspecialchars($row['namaPemesan']) ?>', '<?= htmlspecialchars($row['permintaanKhusus'] ?? '') ?>', '<?= $row['emailPemesan'] ?>')" class="p-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg transition-colors"><i class="fa-solid fa-eye"></i></button>
                                            
                                            <form method="POST" onsubmit="return confirm('Ubah status?');">
                                                <input type="hidden" name="id_reservasi" value="<?= $row['idReservasi'] ?>">
                                                <input type="hidden" name="action_status" value="1">
                                                <?php if($status == 'Confirmed'): ?>
                                                    <button type="submit" name="status_baru" value="Check In" class="p-2 bg-green-500 hover:bg-green-600 text-white rounded-lg"><i class="fa-solid fa-check"></i></button>
                                                    <button type="submit" name="status_baru" value="Cancelled" class="p-2 bg-red-100 hover:bg-red-200 text-red-500 rounded-lg"><i class="fa-solid fa-xmark"></i></button>
                                                <?php elseif($status == 'Check In'): ?>
                                                    <button type="submit" name="status_baru" value="Completed" class="p-2 bg-primary hover:bg-blue-800 text-white rounded-lg"><i class="fa-solid fa-right-from-bracket"></i> Out</button>
                                                <?php endif; ?>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="px-6 py-12 text-center text-gray-400">Tidak ada data reservasi.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<div id="detailModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all scale-95 opacity-0" id="modalContent">
        <div class="bg-primary p-4 flex justify-between items-center text-white">
            <h3 class="font-bold text-lg">Detail Reservasi</h3>
            <button onclick="closeModal()"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        <div class="p-6 space-y-4">
            <div><label class="text-xs font-bold text-gray-400 uppercase">ID Reservasi</label><p id="modalId" class="text-lg font-bold text-primary">#---</p></div>
            <div><label class="text-xs font-bold text-gray-400 uppercase">Nama Tamu</label><p id="modalNama">---</p></div>
            <div><label class="text-xs font-bold text-gray-400 uppercase">Email</label><p id="modalEmail">---</p></div>
            <div class="bg-yellow-50 p-4 rounded-lg"><label class="text-xs font-bold text-yellow-600 uppercase">Permintaan Khusus</label><p id="modalRequest" class="text-sm mt-1 italic">"---"</p></div>
        </div>
        <div class="bg-gray-50 p-4 text-right"><button onclick="closeModal()" class="px-4 py-2 bg-gray-200 rounded-lg text-sm font-medium">Tutup</button></div>
    </div>
</div>

<script>
    const modal = document.getElementById('detailModal'), modalContent = document.getElementById('modalContent');
    function openModal(id, nama, request, email) {
        document.getElementById('modalId').innerText = '#' + id;
        document.getElementById('modalNama').innerText = nama;
        document.getElementById('modalRequest').innerText = '"' + request + '"';
        document.getElementById('modalEmail').innerText = email;
        modal.classList.remove('hidden');
        setTimeout(() => { modalContent.classList.remove('scale-95', 'opacity-0'); modalContent.classList.add('scale-100', 'opacity-100'); }, 10);
    }
    function closeModal() {
        modalContent.classList.remove('scale-100', 'opacity-100'); modalContent.classList.add('scale-95', 'opacity-0');
        setTimeout(() => { modal.classList.add('hidden'); }, 300);
    }
</script>
</body>
</html>