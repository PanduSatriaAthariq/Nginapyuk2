// Ambil elemen
const otherCheckbox = document.querySelector('input[name="req_other"]');
const otherInputContainer = document.getElementById('otherReqInput');

// Pasang pendengar event
otherCheckbox.addEventListener('change', function() {
    if (this.checked) {
        // Jika dicentang, tambah class 'show'
        otherInputContainer.classList.add('show');
        // Opsional: Langsung fokus ke textarea biar user enak
        otherInputContainer.querySelector('textarea').focus();
    } else {
        // Jika tidak, hapus class 'show'
        otherInputContainer.classList.remove('show');
    }
});