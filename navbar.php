<nav class="navbar">
    <div class="navbarCont">
        <a href="index.php" class="logo">NginapYuk<span>!</span></a>
        
        <div class="loginBtn">
            <?php 
            if (isset($_SESSION['nama'])) { 
                // --- KONDISI SUDAH LOGIN ---
                $namaDisplay = explode(' ', $_SESSION['nama'])[0]; 
            ?>
                <a href="#" class="masukBtn" style="border:none; cursor:default;">
                    <i class="fa-solid fa-user"></i> Halo, <?= htmlspecialchars($namaDisplay) ?>
                </a>
                
                <a href="logout.php" class="daftarBtn" style="background-color: #dc3545; border-color: #dc3545;">
                    Keluar
                </a>

            <?php } else { ?>
                
                <a href="#" class="masukBtn" onclick="openAuthModal('login'); return false;">Masuk</a>
                <a href="#" class="daftarBtn" onclick="openAuthModal('register'); return false;">Daftar</a>
            
            <?php } ?>
        </div>
    </div>
</nav>

<?php if(!isset($_SESSION['nama'])): ?>

<div class="auth-overlay" id="authOverlay">
    <div class="auth-card">
        <button class="close-auth" onclick="closeAuthModal()"><i class="fa-solid fa-xmark"></i></button>
        
        <div class="auth-tabs">
            <button type="button" class="auth-tab-btn active" id="tabLogin" onclick="switchTab('login')">Masuk</button>
            <button type="button" class="auth-tab-btn" id="tabRegister" onclick="switchTab('register')">Daftar</button>
        </div>

        <form action="authProcess.php" method="POST" class="auth-form active" id="formLogin">
            <input type="hidden" name="action" value="login">
            
            <?php if(isset($_GET['error']) && $_GET['error'] == 'loginfailed'): ?>
                <div class="alert-mini alert-red">Email atau Password salah!</div>
            <?php endif; ?>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-input" placeholder="Contoh: user@email.com" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-input" placeholder="Masukkan password" required>
            </div>
            
            <button type="submit" class="btn-auth-action">Masuk Sekarang</button>
            
            <div class="auth-footer">
                Belum punya akun? <span class="auth-link" onclick="switchTab('register')">Daftar disini</span>
            </div>
        </form>

        <form action="authProcess.php" method="POST" class="auth-form" id="formRegister">
            <input type="hidden" name="action" value="register">

            <?php if(isset($_GET['error']) && $_GET['error'] == 'emailtaken'): ?>
                <div class="alert-mini alert-red">Email sudah terdaftar! Gunakan email lain.</div>
            <?php endif; ?>

            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama" class="form-input" placeholder="Nama sesuai KTP" required>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-input" placeholder="Alamat email aktif" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-input" placeholder="Minimal 6 karakter" minlength="6" required>
            </div>

            <button type="submit" class="btn-auth-action">Daftar Akun</button>

            <div class="auth-footer">
                Sudah punya akun? <span class="auth-link" onclick="switchTab('login')">Masuk disini</span>
            </div>
        </form>
    </div>
</div>

<script>
    // SCRIPT MODAL
    const overlay = document.getElementById('authOverlay');
    const tabLogin = document.getElementById('tabLogin');
    const tabRegister = document.getElementById('tabRegister');
    const formLogin = document.getElementById('formLogin');
    const formRegister = document.getElementById('formRegister');

    function openAuthModal(mode) {
        overlay.style.display = 'flex';
        switchTab(mode);
    }

    function closeAuthModal() {
        overlay.style.display = 'none';
        const url = new URL(window.location);
        url.searchParams.delete('error');
        window.history.replaceState({}, document.title, url);
    }

    function switchTab(mode) {
        if (mode === 'login') {
            tabLogin.classList.add('active');
            tabRegister.classList.remove('active');
            formLogin.classList.add('active');
            formRegister.classList.remove('active');
        } else {
            tabLogin.classList.remove('active');
            tabRegister.classList.add('active');
            formLogin.classList.remove('active');
            formRegister.classList.add('active');
        }
    }

    window.onclick = function(e) {
        if (e.target == overlay) {
            closeAuthModal();
        }
    }

    // Auto-open jika ada error (misal password salah)
    <?php if(isset($_GET['error'])): ?>
        openAuthModal('<?php echo ($_GET['error'] == 'emailtaken') ? 'register' : 'login'; ?>');
    <?php endif; ?>
</script>
<?php endif; ?>