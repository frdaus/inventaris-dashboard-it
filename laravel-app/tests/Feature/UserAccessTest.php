<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Asset;
use App\Models\MaintenanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserAccessTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::query()->create([
            'username' => 'admin',
            'password_hash' => Hash::make('test-password'),
        ]);
    }

    #[Test]
    public function administrators_can_create_manage_and_reset_non_admin_accounts(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('users.update', $this->admin), [
                'role' => Admin::ROLE_GUEST,
                'is_active' => '0',
            ])
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'username' => 'technician',
                'role' => Admin::ROLE_IT_STAFF,
                'password' => 'Strong-test-password-1',
                'password_confirmation' => 'Strong-test-password-1',
            ])
            ->assertRedirect(route('users.index'));

        $user = Admin::query()->where('username', 'technician')->firstOrFail();
        $this->assertSame(Admin::ROLE_IT_STAFF, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'user',
            'entity_id' => $user->id,
            'action' => 'CREATE',
            'username' => 'admin',
        ]);

        $this->patch(route('users.update', $user), [
            'role' => Admin::ROLE_GUEST,
            'is_active' => '0',
        ])->assertRedirect(route('users.index'));

        $this->assertSame(Admin::ROLE_GUEST, $user->fresh()->role);
        $this->assertFalse($user->fresh()->is_active);

        $this->post(route('users.password', $user), [
            'password' => 'New-strong-password-2',
            'password_confirmation' => 'New-strong-password-2',
        ])->assertRedirect(route('users.index'));

        $this->assertTrue(Hash::check('New-strong-password-2', $user->fresh()->password_hash));
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'user',
            'entity_id' => $user->id,
            'description' => 'Kata sandi pengguna direset.',
        ]);
        $this->get(route('users.index'))
            ->assertOk()
            ->assertSee('Pengguna &amp; Akses', false)
            ->assertSee('technician')
            ->assertSee('Guest/User');
        $this->get(route('activity.index'))
            ->assertOk()
            ->assertSee('Pengguna')
            ->assertSee('technician');
    }

    #[Test]
    public function it_staff_can_manage_assets_and_maintenance_but_not_admin_features(): void
    {
        $staff = Admin::query()->create([
            'username' => 'staff',
            'password_hash' => Hash::make('staff-password'),
            'role' => Admin::ROLE_IT_STAFF,
        ]);
        $asset = $this->createAsset('STAFF-001');
        $record = MaintenanceRecord::query()->create([
            'asset_id' => $asset->id,
            'asset_code' => $asset->kode_aset,
            'asset_name' => $asset->nama_barang,
            'maintenance_date' => '2026-11-20',
            'description' => 'Pemeriksaan rutin',
            'status' => 'Dijadwalkan',
        ]);

        $this->actingAs($staff)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Tambah Aset')
            ->assertSee('Export Excel')
            ->assertDontSee('Riwayat Aktivitas')
            ->assertDontSee('Pengguna &amp; Akses', false);

        $this->get(route('maintenance.index'))
            ->assertOk()
            ->assertSee('Catat Pemeliharaan')
            ->assertSee('Perbarui Status');

        $this->get(route('asset.label', $asset))
            ->assertOk();

        $this->post(route('maintenance.store'), [
            'asset_id' => $asset->id,
            'maintenance_date' => '2026-11-22',
            'description' => 'Pengujian hak akses petugas',
            'status' => 'Dijadwalkan',
        ])->assertRedirect(route('maintenance.index'));

        $this->get(route('activity.index'))->assertForbidden();
        $this->get(route('users.index'))->assertForbidden();
        $this->post(route('hapus_aset', $asset->id))->assertForbidden();

        $this->post(route('tambah_aset'), [
            'kode_aset' => 'STAFF-002',
            'nama_barang' => 'Laptop',
            'kategori' => 'Komputer',
            'jumlah' => 1,
            'kondisi' => 'Baik',
        ])->assertRedirect(route('dashboard'));

        $this->patch(route('maintenance.status', $record), [
            'status' => 'Berlangsung',
        ])->assertRedirect(route('maintenance.index'));
    }

    #[Test]
    public function guest_users_can_only_view_dashboard_and_maintenance(): void
    {
        $guest = Admin::query()->create([
            'username' => 'viewer',
            'password_hash' => Hash::make('viewer-password'),
            'role' => Admin::ROLE_GUEST,
        ]);
        $asset = $this->createAsset('GUEST-001');
        $record = MaintenanceRecord::query()->create([
            'asset_id' => $asset->id,
            'asset_code' => $asset->kode_aset,
            'asset_name' => $asset->nama_barang,
            'maintenance_date' => '2026-11-20',
            'description' => 'Pemeriksaan rutin',
            'status' => 'Dijadwalkan',
        ]);

        $this->actingAs($guest)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Tambah Aset')
            ->assertDontSee('Export Excel')
            ->assertDontSee('Riwayat Aktivitas')
            ->assertDontSee('Pengguna &amp; Akses', false)
            ->assertDontSee('Cetak label');

        $this->get(route('maintenance.index'))
            ->assertOk()
            ->assertSee('Riwayat Pemeliharaan')
            ->assertDontSee('Catat Pemeliharaan')
            ->assertDontSee('Perbarui Status');

        $this->get(route('activity.index'))->assertForbidden();
        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('asset.label', $asset))->assertForbidden();
        $this->get(route('export_excel'))->assertForbidden();
        $this->post(route('tambah_aset'), [
            'kode_aset' => 'GUEST-002',
            'nama_barang' => 'Laptop',
            'kategori' => 'Komputer',
            'jumlah' => 1,
            'kondisi' => 'Baik',
        ])->assertForbidden();
        $this->patch(route('maintenance.status', $record), [
            'status' => 'Selesai',
        ])->assertForbidden();
        $this->post(route('maintenance.store'), [
            'asset_id' => $asset->id,
            'maintenance_date' => '2026-11-22',
            'description' => 'Tidak boleh dibuat guest',
            'status' => 'Dijadwalkan',
        ])->assertForbidden();
    }

    #[Test]
    public function inactive_users_cannot_log_in_or_continue_an_existing_session(): void
    {
        $inactive = Admin::query()->create([
            'username' => 'inactive',
            'password_hash' => Hash::make('inactive-password'),
            'role' => Admin::ROLE_IT_STAFF,
            'is_active' => false,
        ]);

        $this->from(route('login'))->post(route('login.submit'), [
            'username' => 'inactive',
            'password' => 'inactive-password',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors(['login']);

        $this->actingAs($inactive)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['login']);

        $this->assertGuest();
    }

    private function createAsset(string $code): Asset
    {
        return Asset::query()->create([
            'kode_aset' => $code,
            'nama_barang' => 'Laptop Uji',
            'kategori' => 'Komputer',
            'jumlah' => 1,
            'kondisi' => 'Baik',
        ]);
    }
}
