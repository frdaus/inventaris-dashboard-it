<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengguna &amp; Akses | IT Inventory</title>
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
        <a class="nav" href="{{ route('activity.index') }}">
            <span class="nav-icon" aria-hidden="true">◷</span>
            <span class="nav-label">Riwayat Aktivitas</span>
        </a>
        <a class="nav active" href="{{ route('users.index') }}" aria-current="page">
            <span class="nav-icon" aria-hidden="true">♙</span>
            <span class="nav-label">Pengguna &amp; Akses</span>
        </a>
        <div class="sidebar-note">
            <strong>Administrator</strong>
            <p>Kelola akun dan hak akses sistem.</p>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="topbar-brand">
                <div class="topbar-copy">
                    <p class="eyebrow">ADMINISTRASI</p>
                    <h1>Pengguna &amp; Akses</h1>
                    <p class="subtitle">Atur peran, status akun, dan kata sandi pengguna.</p>
                </div>
            </div>
            <div class="topbar-actions">
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
            <div class="notice success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="notice error">{{ $errors->first() }}</div>
        @endif

        <section class="panel user-create-panel">
            <div class="panel-heading">
                <div>
                    <h3>Tambah Pengguna</h3>
                    <p>Kata sandi minimal 12 karakter. Akun baru aktif setelah dibuat.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('users.store') }}" class="asset-form user-create-form">
                @csrf
                <label>Username *
                    <input name="username" maxlength="50" value="{{ old('username') }}"
                           autocomplete="off" required>
                </label>
                <label>Peran *
                    <select name="role" required>
                        <option value="it_staff" @selected(old('role', 'it_staff') === 'it_staff')>
                            Petugas IT
                        </option>
                        <option value="guest" @selected(old('role') === 'guest')>
                            Guest/User
                        </option>
                    </select>
                </label>
                <label>Kata sandi awal *
                    <input type="password" name="password" minlength="12"
                           autocomplete="new-password" required>
                </label>
                <label>Konfirmasi kata sandi *
                    <input type="password" name="password_confirmation" minlength="12"
                           autocomplete="new-password" required>
                </label>
                <button type="submit" class="primary-button">+ Buat Akun</button>
            </form>
        </section>

        <section class="panel table-panel user-table-panel">
            <div class="table-header">
                <div>
                    <h3>Daftar Pengguna</h3>
                    <p>{{ $users->count() }} akun terdaftar. Akun Admin dilindungi dari perubahan peran di halaman ini.</p>
                </div>
            </div>
            <div class="table-wrap">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Peran</th>
                            <th>Status</th>
                            <th>Pengaturan akses</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td><strong>{{ $user->username }}</strong></td>
                                <td>
                                    {{ match ($user->role) {
                                        'admin' => 'Admin',
                                        'it_staff' => 'Petugas IT',
                                        'guest' => 'Guest/User',
                                        default => $user->role,
                                    } }}
                                </td>
                                <td>
                                    <span class="user-status {{ $user->is_active ? 'is-active' : 'is-inactive' }}">
                                        {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="user-actions-cell">
                                    @if ($user->role === \App\Models\Admin::ROLE_ADMIN)
                                        <span class="protected-user-note">Akun administrator</span>
                                    @else
                                        <form method="POST" action="{{ route('users.update', $user) }}"
                                              class="user-access-form">
                                            @csrf
                                            @method('PATCH')
                                            <select name="role" aria-label="Peran {{ $user->username }}">
                                                <option value="it_staff" @selected($user->role === 'it_staff')>
                                                    Petugas IT
                                                </option>
                                                <option value="guest" @selected($user->role === 'guest')>
                                                    Guest/User
                                                </option>
                                            </select>
                                            <select name="is_active" aria-label="Status {{ $user->username }}">
                                                <option value="1" @selected($user->is_active)>Aktif</option>
                                                <option value="0" @selected(! $user->is_active)>Nonaktif</option>
                                            </select>
                                            <button type="submit" class="secondary-button">Simpan</button>
                                        </form>
                                        <details class="user-password-reset">
                                            <summary>Reset kata sandi</summary>
                                            <form method="POST"
                                                  action="{{ route('users.password', $user) }}"
                                                  class="user-password-form">
                                                @csrf
                                                <label>Kata sandi baru
                                                    <input type="password" name="password" minlength="12"
                                                           autocomplete="new-password" required>
                                                </label>
                                                <label>Konfirmasi kata sandi
                                                    <input type="password" name="password_confirmation"
                                                           minlength="12" autocomplete="new-password" required>
                                                </label>
                                                <button type="submit" class="secondary-button">
                                                    Simpan kata sandi
                                                </button>
                                            </form>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <footer>IT Inventory Dashboard · Laravel</footer>
    </main>

    <script src="{{ asset('assets/dashboard.js') }}?v={{ filemtime(public_path('assets/dashboard.js')) }}"></script>
</body>
</html>
