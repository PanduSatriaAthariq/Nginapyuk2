const container = document.getElementById('cardContainer');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');

// Fungsi Scroll Button
// Kita geser sejauh (Lebar Kartu 200px + Gap 20px) = 220px
nextBtn.addEventListener('click', () => {
    container.scrollBy({ left: 220, behavior: 'smooth' });
});

prevBtn.addEventListener('click', () => {
    container.scrollBy({ left: -220, behavior: 'smooth' });
});

// Logika Menghilangkan Tombol
function updateButtons() {
    // 1. Cek apakah sudah mentok kiri?
    if (container.scrollLeft <= 0) {
        prevBtn.classList.add('hidden');
    } else {
        prevBtn.classList.remove('hidden');
    }

    // 2. Cek apakah sudah mentok kanan?
    // (ScrollLeft + Lebar Layar >= Total Lebar Konten - Toleransi 5px)
    const maxScrollLeft = container.scrollWidth - container.clientWidth;
    
    if (container.scrollLeft >= maxScrollLeft - 5) {
        nextBtn.classList.add('hidden');
    } else {
        nextBtn.classList.remove('hidden');
    }
}

// Pasang pendengar event saat di-scroll
container.addEventListener('scroll', updateButtons);

// Cek sekali saat halaman dimuat (untuk menyembunyikan tombol kiri di awal)
updateButtons();