<?php
session_start();
include 'koneksi.php';

// --- LOGIKA UTAMA: GUEST CHECKOUT VS MEMBER ---
// Kita tidak lagi memaksa user login.
// Jika login, ambil ID-nya. Jika tidak, biarkan NULL.

$idUser = null; // Default NULL (Tamu)

if (isset($_SESSION['idUser'])) {
    $idUser = $_SESSION['idUser'];
}

// 2. CEK METHOD POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- TANGKAP DATA DARI FORM ---
    $idTipeKamar    = intval($_POST['idKamar']);
    
    $checkIn        = $_POST['checkin'];
    $checkOut       = $_POST['checkout'];
    $jumlahKamar    = intval($_POST['jumlahKamar']);
    $totalHarga     = floatval($_POST['totalHarga']); 
    
    // Data Pemesan
    $namaPemesan    = $conn->real_escape_string($_POST['fullname']);
    $emailPemesan   = $conn->real_escape_string($_POST['email']);
    $nomorPemesan   = $conn->real_escape_string($_POST['phone']);

    // --- PROSES PERMINTAAN KHUSUS ---
    $reqArray = [];
    
    if (isset($_POST['req_checkin'])) {
        $reqArray[] = "Kamar Dekat Lift";
    }
    if (isset($_POST['req_checkout'])) {
        $reqArray[] = "Kamar di Lantai Tinggi";
    }
    
    if (!empty($_POST['other_request_text'])) {
        $reqArray[] = $conn->real_escape_string($_POST['other_request_text']);
    }

    $permintaanKhusus = !empty($reqArray) ? implode(", ", $reqArray) : NULL;

    // --- INSERT KE DATABASE ---
    
    $sql = "INSERT INTO reservasi 
            (idUser, idTipeKamar, tanggalCheckin, tanggalCheckout, jumlahKamar, totalHarga, 
             namaPemesan, nomorPemesan, emailPemesan, permintaanKhusus, statusReservasi) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Confirmed')";

    $stmt = $conn->prepare($sql);
    
    // PENTING: Handling NULL untuk bind_param
    // Jika $idUser NULL, bind_param kadang butuh trik khusus, tapi di PHP modern:
    // Tipe 'i' (integer) menerima NULL jika variabelnya bernilai null.
    
    $stmt->bind_param("iissidssss", 
        $idUser, 
        $idTipeKamar, 
        $checkIn, 
        $checkOut, 
        $jumlahKamar, 
        $totalHarga, 
        $namaPemesan, 
        $nomorPemesan, 
        $emailPemesan, 
        $permintaanKhusus
    );

    if ($stmt->execute()) {
        // --- BERHASIL ---
        $last_id = $conn->insert_id;
        
        // LEMPAR KE SUCCESS.PHP DENGAN MEMBAWA ID
        header("Location: success.php?idReservasi=" . $last_id);
        exit();
    } else {
        // --- GAGAL ---
        // Tampilkan error biar ketahuan kalau lupa ALTER TABLE
        echo "Error Transaction: " . $stmt->error;
        echo "<br>Hint: Pastikan kolom idUser di database sudah diubah menjadi NULLABLE (Boleh Kosong).";
    }

    $stmt->close();
    $conn->close();

} else {
    // Jika akses langsung
    header("Location: index.php");
    exit();
}
?>