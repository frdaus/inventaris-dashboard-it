<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya | IT Inventory</title>
    <link rel="stylesheet"
          href="{{ asset('assets/style.css') }}?v={{ filemtime(public_path('assets/style.css')) }}">
</head>
<body>
    <aside class="sidebar">
        <div class="brand"><span>IT Inventaris</span></div>
        <p class="menu-label">MENU UTAMA</p>
        <a class="nav" href="{{ route('dashboard') }}">
            <span class="nav-icon" aria-hidden="true">▦</span>
            <span class="nav-label">Dashboard</span>
        </a>
        <a class="nav" href="{{ route('maintenance.index') }}">
            <span class="nav-icon" aria-hidden="true">🔧</span>
            <span class="nav-label">Kelola Pemeliharaan</span>
        </a>
        @if (auth()->user()->role === \App\Models\Admin::ROLE_ADMIN)
            <a class="nav" href="{{ route('activity.index') }}">
                <span class="nav-icon" aria-hidden="true">◷</span>
                <span class="nav-label">Riwayat Aktivitas</span>
            </a>
            <a class="nav" href="{{ route('users.index') }}">
                <span class="nav-icon" aria-hidden="true">♙</span>
                <span class="nav-label">Pengguna &amp; Akses</span>
            </a>
        @endif
        <div class="sidebar-note">
            <strong>Profil Pengguna</strong>
            <p>Kelola foto profil akun Anda.</p>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="topbar-brand">
                <div class="topbar-copy">
                    <p class="eyebrow">AKUN SAYA</p>
                    <h1>Profil Saya</h1>
                    <p class="subtitle">Perbarui foto yang tampil di dashboard.</p>
                </div>
            </div>
            <div class="topbar-actions">
                @include('partials.profile-link')
                <button type="button" id="theme-toggle"
                        class="secondary-button" aria-pressed="false">
                    🌙 Mode Gelap
                </button>
                <form class="logout-form" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="secondary-button">Logout</button>
                </form>
            </div>
        </header>

        @if (session('success'))
            <div class="notice success" role="status">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="notice error" role="alert">{{ $errors->first() }}</div>
        @endif

        <section class="panel profile-panel">
            <div class="panel-heading">
                <div>
                    <h3>Foto Profil</h3>
                    <p>Foto hanya dapat dikelola oleh pemilik akun dan terlihat setelah login.</p>
                </div>
            </div>

            <div class="profile-content">
                <div class="profile-photo-preview" aria-label="Pratinjau foto profil">
                    <img id="profile-photo-preview"
                         src="{{ auth()->user()->profile_photo_path ? route('profile.photo') : '' }}"
                         alt=""
                         @if (! auth()->user()->profile_photo_path) hidden @endif>
                    <span id="profile-photo-initial"
                          @if (auth()->user()->profile_photo_path) hidden @endif>
                        {{ strtoupper(substr(auth()->user()->username, 0, 1)) }}
                    </span>
                </div>

                <div class="profile-details">
                    <p class="profile-username">{{ auth()->user()->username }}</p>
                    <p class="profile-role">
                        {{ match (auth()->user()->role) {
                            \App\Models\Admin::ROLE_ADMIN => 'Admin',
                            \App\Models\Admin::ROLE_IT_STAFF => 'Petugas IT',
                            \App\Models\Admin::ROLE_GUEST => 'Guest/User',
                            default => 'Pengguna',
                        } }}
                    </p>
                    <form method="POST" action="{{ route('profile.photo.update') }}"
                          enctype="multipart/form-data" class="profile-photo-form">
                        @csrf
                        @method('PUT')
                        <input id="profile-photo-input" type="file" name="photo"
                               accept="image/jpeg,image/png,image/webp" required>
                        <label for="profile-photo-input" class="secondary-button">
                            Pilih Foto
                        </label>
                        <p>Format JPG, PNG, atau WebP. Maksimal 2 MB dan 2048 × 2048 piksel.</p>
                        <button type="submit" class="primary-button">Simpan Foto</button>
                    </form>

                    @if (auth()->user()->profile_photo_path)
                        <form method="POST" action="{{ route('profile.photo.destroy') }}"
                              class="profile-remove-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="secondary-button">
                                Hapus Foto
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </section>

        <footer>IT Inventory Dashboard · Laravel</footer>
    </main>

    <script>
        const photoInput = document.getElementById("profile-photo-input");
        const photoPreview = document.getElementById("profile-photo-preview");
        const photoInitial = document.getElementById("profile-photo-initial");

        photoInput.addEventListener("change", function () {
            const file = photoInput.files[0];
            if (!file) return;

            const previewUrl = URL.createObjectURL(file);
            photoPreview.onload = () => URL.revokeObjectURL(previewUrl);
            photoPreview.onerror = () => URL.revokeObjectURL(previewUrl);
            photoPreview.src = previewUrl;
            photoPreview.hidden = false;
            photoInitial.hidden = true;
        });
    </script>
    <script src="{{ asset('assets/dashboard.js') }}?v={{ filemtime(public_path('assets/dashboard.js')) }}"></script>
</body>
</html>
