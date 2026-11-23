<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | IT Inventory</title>
    <link rel="stylesheet" href="{{ asset('assets/login.css') }}">
</head>
<body>
    <main class="login-page">
        <section class="login-card">
            <div class="brand-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                    <rect x="3" y="3" width="8" height="8" rx="2"/>
                    <rect x="13" y="3" width="8" height="5" rx="2"/>
                    <rect x="13" y="10" width="8" height="11" rx="2"/>
                    <rect x="3" y="13" width="8" height="8" rx="2"/>
                </svg>
            </div>

            <p class="eyebrow">USER PORTAL</p>
            <h1>Selamat Datang</h1>
            <p class="subtitle">
                Masuk untuk mengakses inventaris IT sesuai peran Anda.
            </p>

            @if ($errors->any())
                <div class="error-message" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.submit') }}">
                @csrf
                <div class="form-group">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="{{ old('username') }}"
                        placeholder="Masukkan username"
                        autocomplete="username"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Masukkan password"
                            autocomplete="current-password"
                            required
                        >
                        <button
                            type="button"
                            class="toggle-password"
                            id="togglePassword"
                            aria-label="Tampilkan password"
                            aria-pressed="false"
                        >
                            <svg id="eyeIcon"
                                 viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="1.8"
                                 stroke-linecap="round"
                                 stroke-linejoin="round"
                                 aria-hidden="true">
                                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="login-button">
                    Masuk ke Dashboard
                    <span aria-hidden="true">→</span>
                </button>
            </form>

            <div class="security-note">
                <span aria-hidden="true">●</span>
                Akses hanya untuk pengguna terdaftar
            </div>
        </section>

        <footer>
            IT Inventory Dashboard &copy; {{ now()->year }}
        </footer>
    </main>

    <script>
        const passwordInput = document.getElementById("password");
        const togglePassword = document.getElementById("togglePassword");
        const eyeIcon = document.getElementById("eyeIcon");

        togglePassword.addEventListener("click", function () {
            const isHidden = passwordInput.type === "password";
            passwordInput.type = isHidden ? "text" : "password";
            togglePassword.setAttribute(
                "aria-label",
                isHidden ? "Sembunyikan password" : "Tampilkan password"
            );
            togglePassword.setAttribute("aria-pressed", String(isHidden));
            eyeIcon.innerHTML = isHidden
                ? '<path d="M3 3l18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a16 16 0 0 1-3.1 3.8"/><path d="M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7a10 10 0 0 0 4-.8"/>'
                : '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>';
        });
    </script>
</body>
</html>
