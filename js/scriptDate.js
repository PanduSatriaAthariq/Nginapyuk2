function setupDateTrigger(textId, dateId) {
    const textInput = document.getElementById(textId);
    const dateInput = document.getElementById(dateId);

    // 1. Saat TEKS diklik, paksa DATE untuk buka kalender
    textInput.addEventListener('click', () => {
        try {
            dateInput.showPicker(); // Fitur ajaib browser modern
        } catch (error) {
            // Fallback untuk browser jadul (jarang terjadi di 2025)
            console.error("Browser ini tidak mendukung showPicker, atau elemen tersembunyi/disabled.");
            // Opsi darurat: klik langsung date input
            dateInput.click(); 
        }
    });

    // 2. Saat user memilih tanggal di kalender
    dateInput.addEventListener('change', () => {
        const dateValue = dateInput.value;
        if (dateValue) {
            // Ubah format YYYY-MM-DD ke DD/MM/YYYY
            const [year, month, day] = dateValue.split('-');
            textInput.value = `${day}/${month}/${year}`;
        }
    });
}

// Jalankan fungsi
setupDateTrigger('checkInText', 'checkInDate');
setupDateTrigger('checkOutText', 'checkOutDate');