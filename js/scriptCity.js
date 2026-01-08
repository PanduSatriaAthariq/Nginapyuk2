document.addEventListener("DOMContentLoaded", function () {
    const tabs = document.querySelectorAll(".tabBtn");
    const gridContainer = document.getElementById("dynamicHotelGrid");
    const viewAllBtn = document.getElementById("viewAllBtn");

    // Fungsi Fetch Data (AJAX)
    function loadHotels(cityName) {
        // Tampilkan efek loading sederhana
        gridContainer.innerHTML = '<p style="grid-column: 1/-1; text-align: center; padding: 40px;">Memuat data hotel...</p>';

        // Request ke PHP
        fetch(`hotelCity.php?city=${cityName}`)
            .then(response => response.text())
            .then(data => {
                // Masukkan hasil HTML dari PHP ke dalam Grid
                gridContainer.innerHTML = data;
                
                // Update link tombol "Lihat Semua" agar mengarah ke search page yang benar
                if(viewAllBtn) {
                    viewAllBtn.href = `search.php?city=${cityName}`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                gridContainer.innerHTML = '<p>Terjadi kesalahan memuat data.</p>';
            });
    }

    // Event Listener untuk setiap Tab
    tabs.forEach(tab => {
        tab.addEventListener("click", function () {
            // Hapus kelas active dari semua tab (reset warna biru)
            tabs.forEach(t => t.classList.remove("active"));
            
            // Tambah kelas active ke tab yang diklik
            this.classList.add("active");

            // Ambil nama kota dari atribut data-city HTML
            const city = this.getAttribute("data-city");
            
            // Panggil fungsi load
            loadHotels(city);
        });
    });

    // Load default: Jalankan otomatis untuk kota 'Jakarta' saat website pertama dibuka
    loadHotels('Jakarta');
});