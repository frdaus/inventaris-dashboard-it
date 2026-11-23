<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Aktivitas | IT Inventory</title>
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
        <a class="nav active" href="{{ route('activity.index') }}" aria-current="page">
            <span class="nav-icon" aria-hidden="true">◷</span>
            <span class="nav-label">Riwayat Aktivitas</span>
        </a>
        <div class="sidebar-note">
            <strong>Audit Aktivitas</strong>
            <p>Catatan perubahan aset dan pemeliharaan.</p>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="topbar-brand">
                <div class="topbar-copy">
                    <p class="eyebrow">AUDIT AKTIVITAS</p>
                    <h1>Riwayat Aktivitas</h1>
                    <p class="subtitle">Pantau perubahan aset dan catatan pemeliharaan.</p>
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

        <section class="panel table-panel activity-panel">
            <div class="table-header">
                <div>
                    <h3>Catatan Audit</h3>
                    <p>{{ $activities->total() }} aktivitas tercatat, terbaru ditampilkan lebih dahulu.</p>
                </div>
            </div>

            <div class="table-wrap">
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Pengguna</th>
                            <th>Aktivitas</th>
                            <th>Aset</th>
                            <th>Rincian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activities as $activity)
                            <tr>
                                <td class="activity-time">
                                    {{ $activity->created_at?->format('d/m/Y H:i:s') ?? '—' }}
                                </td>
                                <td>{{ $activity->username }}</td>
                                <td>
                                    <span class="activity-badge action-{{ strtolower($activity->action) }}">
                                        {{ $activity->action_label }}
                                    </span>
                                    <small class="activity-kind">{{ $activity->entity_label }}</small>
                                </td>
                                <td>
                                    <strong>{{ $activity->asset_code ?: '—' }}</strong>
                                    @if ($activity->asset_name)
                                        <br><small>{{ $activity->asset_name }}</small>
                                    @endif
                                </td>
                                <td class="activity-detail">
                                    @if ($activity->audit_changes !== [])
                                        <details>
                                            <summary>{{ $activity->audit_summary }}
                                                ({{ count($activity->audit_changes) }} rincian)</summary>
                                            <ul>
                                                @foreach ($activity->audit_changes as $change)
                                                    <li>
                                                        <strong>{{ $change['label'] }}:</strong>
                                                        {{ $change['before'] }} &rarr; {{ $change['after'] }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    @else
                                        {{ $activity->audit_summary }}
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="empty-state">
                                    Belum ada aktivitas yang tercatat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="table-footer pagination-footer">
                <p>Menampilkan {{ $activities->firstItem() ?? 0 }}–{{ $activities->lastItem() ?? 0 }}
                    dari {{ $activities->total() }} aktivitas</p>
                <nav class="pagination" aria-label="Navigasi riwayat aktivitas">
                    @if ($activities->onFirstPage())
                        <span class="page-link disabled">‹ Sebelumnya</span>
                    @else
                        <a class="page-link" href="{{ $activities->previousPageUrl() }}">‹ Sebelumnya</a>
                    @endif
                    <span class="page-indicator">
                        Halaman {{ $activities->currentPage() }} dari {{ $activities->lastPage() }}
                    </span>
                    @if ($activities->hasMorePages())
                        <a class="page-link" href="{{ $activities->nextPageUrl() }}">Berikutnya ›</a>
                    @else
                        <span class="page-link disabled">Berikutnya ›</span>
                    @endif
                </nav>
            </div>
        </section>

        <footer>IT Inventory Dashboard · Laravel</footer>
    </main>

    <script src="{{ asset('assets/dashboard.js') }}?v={{ filemtime(public_path('assets/dashboard.js')) }}"></script>
</body>
</html>
