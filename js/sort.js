document.addEventListener("DOMContentLoaded", function () {
    
    // Handler Sort Dropdown
    const sortDropdown = document.getElementById("sortDropdown");
    if (sortDropdown) {
        sortDropdown.addEventListener("change", function() {
            updateParams('sort', this.value);
        });
    }

    // Handler Sort Mobile
    const mobileSortButtons = document.querySelectorAll(".sortOptions button");
    mobileSortButtons.forEach(btn => {
        btn.addEventListener("click", function() {
            const sortValue = this.getAttribute("data-value");
            updateParams('sort', sortValue);
        });
    });

    // Handler Tombol Terapkan Filter
    const filterBtns = document.querySelectorAll(".btnApplyFilter");
    filterBtns.forEach(btn => {
        btn.addEventListener("click", applyFilter);
    });
});

// LOGIKA MUTUAL EXCLUSION (Hanya boleh pilih 1 per kategori)
function selectOnlyThis(checkbox) {
    var checkboxes = document.getElementsByName(checkbox.name);
    checkboxes.forEach((item) => {
        if (item !== checkbox) item.checked = false;
    });
}

// FUNGSI UTAMA: FILTER
function applyFilter() {
    // 1. Ambil checkbox Bintang yang dicentang (Pasti cuma 1 atau 0 karena selectOnlyThis)
    const starCheck = document.querySelector('input[name="stars"]:checked');
    const starValue = starCheck ? starCheck.value : null;

    // 2. Ambil checkbox Skor yang dicentang
    const scoreCheck = document.querySelector('input[name="scores"]:checked');
    const scoreValue = scoreCheck ? scoreCheck.value : null;

    // 3. Update URL
    const currentUrl = new URL(window.location.href);
    
    // Set parameter 'stars'
    if (starValue) {
        currentUrl.searchParams.set('stars', starValue);
    } else {
        currentUrl.searchParams.delete('stars');
    }

    // Set parameter 'scores'
    if (scoreValue) {
        currentUrl.searchParams.set('scores', scoreValue);
    } else {
        currentUrl.searchParams.delete('scores');
    }

    // Reload
    window.location.href = currentUrl.toString();
}

// HELPER: UPDATE SATU PARAMETER
function updateParams(key, value) {
    const currentUrl = new URL(window.location.href);
    if (value) {
        currentUrl.searchParams.set(key, value);
    } else {
        currentUrl.searchParams.delete(key);
    }
    window.location.href = currentUrl.toString();
}

// UI HELPERS
function toggleFilter() {
    const sidebar = document.getElementById('searchSidebar');
    if(sidebar) sidebar.classList.toggle('active');
}

function toggleSort() {
    const overlay = document.getElementById('mobileSortOverlay');
    if(overlay) overlay.classList.toggle('active');
}