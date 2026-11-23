<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = Admin::query()->orderBy('username')->get();

        return view('users.index', compact('users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'username' => trim((string) $request->input('username', '')),
        ]);

        $data = $request->validate([
            'username' => ['required', 'string', 'max:50', Rule::unique('admin', 'username')],
            'role' => ['required', Rule::in(Admin::MANAGED_ROLES)],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        DB::transaction(function () use ($data): void {
            $user = Admin::query()->create([
                'username' => trim($data['username']),
                'password_hash' => Hash::make($data['password']),
                'role' => $data['role'],
                'is_active' => true,
            ]);

            $this->writeAuditLog(
                'CREATE',
                $user,
                'Akun dibuat dengan peran '.$this->roleLabel($user->role).'.',
            );
        });

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function update(Request $request, Admin $user): RedirectResponse
    {
        abort_if($user->role === Admin::ROLE_ADMIN, 403);

        $data = $request->validate([
            'role' => ['required', Rule::in(Admin::MANAGED_ROLES)],
            'is_active' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($data, $user): void {
            $lockedUser = Admin::query()->lockForUpdate()->findOrFail($user->id);
            abort_if($lockedUser->role === Admin::ROLE_ADMIN, 403);

            $oldRole = $lockedUser->role;
            $oldStatus = $lockedUser->is_active;
            $lockedUser->update([
                'role' => $data['role'],
                'is_active' => (bool) $data['is_active'],
            ]);

            $changes = [];
            if ($oldRole !== $lockedUser->role) {
                $changes[] = 'Peran: '.$this->roleLabel($oldRole)
                    .' menjadi '.$this->roleLabel($lockedUser->role).'.';
            }
            if ($oldStatus !== $lockedUser->is_active) {
                $changes[] = 'Status: '.($oldStatus ? 'Aktif' : 'Nonaktif')
                    .' menjadi '.($lockedUser->is_active ? 'Aktif' : 'Nonaktif').'.';
            }

            if ($changes !== []) {
                $this->writeAuditLog('UPDATE', $lockedUser, implode(' ', $changes));
            }
        });

        return redirect()->route('users.index')->with('success', 'Akses pengguna berhasil diperbarui.');
    }

    public function resetPassword(Request $request, Admin $user): RedirectResponse
    {
        abort_if($user->role === Admin::ROLE_ADMIN, 403);

        $data = $request->validate([
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        DB::transaction(function () use ($data, $user): void {
            $lockedUser = Admin::query()->lockForUpdate()->findOrFail($user->id);
            abort_if($lockedUser->role === Admin::ROLE_ADMIN, 403);

            $lockedUser->update([
                'password_hash' => Hash::make($data['password']),
            ]);
            $this->writeAuditLog('UPDATE', $lockedUser, 'Kata sandi pengguna direset.');
        });

        return redirect()->route('users.index')->with('success', 'Kata sandi berhasil direset.');
    }

    private function writeAuditLog(string $action, Admin $user, string $description): void
    {
        AuditLog::query()->create([
            'admin_id' => Auth::id(),
            'username' => Auth::user()?->username ?? 'unknown',
            'action' => $action,
            'entity_type' => 'user',
            'entity_id' => $user->id,
            'asset_name' => $user->username,
            'description' => $description,
        ]);
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            Admin::ROLE_ADMIN => 'Admin',
            Admin::ROLE_IT_STAFF => 'Petugas IT',
            Admin::ROLE_GUEST => 'Guest/User',
            default => $role,
        };
    }
}
