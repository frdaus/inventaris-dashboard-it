<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Asset;
use App\Models\MaintenanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'username' => 'admin',
            'password_hash' => Hash::make('test-password'),
        ]);
    }

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->get(route('dashboard.statistics'))
            ->assertRedirect(route('login'));

        $this->get(route('maintenance.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function administrators_can_record_and_update_maintenance_without_changing_asset_condition(): void
    {
        $this->actingAs($this->admin);
        $asset = Asset::create([
            'kode_aset' => 'AST-MAINT-001',
            'nama_barang' => 'Laptop',
            'kategori' => 'Komputer',
            'jumlah' => 1,
            'kondisi' => 'Rusak',
        ]);

        $this->get(route('maintenance.index'))
            ->assertOk()
            ->assertSee('Catat Pemeliharaan')
            ->assertSee('AST-MAINT-001');

        $this->post(route('maintenance.store'), [
            'asset_id' => $asset->id,
            'maintenance_date' => '2026-11-20',
            'description' => 'Mengganti baterai',
            'technician' => 'Tim IT',
            'cost' => '125000.00',
            'status' => 'Berlangsung',
            'notes' => 'Menunggu uji daya',
        ])->assertRedirect(route('maintenance.index'));

        $record = MaintenanceRecord::firstOrFail();
        $this->assertDatabaseHas('maintenance_records', [
            'id' => $record->id,
            'asset_id' => $asset->id,
            'asset_code' => 'AST-MAINT-001',
            'asset_name' => 'Laptop',
            'status' => 'Berlangsung',
        ]);

        $this->patch(route('maintenance.status', $record), [
            'status' => 'Selesai',
        ])->assertRedirect(route('maintenance.index'));

        $this->assertDatabaseHas('maintenance_records', [
            'id' => $record->id,
            'status' => 'Selesai',
        ]);
        $this->assertDatabaseHas('aset', [
            'id' => $asset->id,
            'kondisi' => 'Rusak',
        ]);
    }

    #[Test]
    public function maintenance_history_keeps_asset_snapshot_when_asset_is_deleted(): void
    {
        $this->actingAs($this->admin);
        $asset = Asset::create([
            'kode_aset' => 'AST-MAINT-002',
            'nama_barang' => 'Switch',
            'kategori' => 'Network',
            'jumlah' => 1,
            'kondisi' => 'Baik',
        ]);
        $record = MaintenanceRecord::create([
            'asset_id' => $asset->id,
            'asset_code' => $asset->kode_aset,
            'asset_name' => $asset->nama_barang,
            'maintenance_date' => '2026-11-21',
            'description' => 'Membersihkan perangkat',
            'status' => 'Selesai',
        ]);

        $asset->delete();

        $this->assertDatabaseHas('maintenance_records', [
            'id' => $record->id,
            'asset_id' => null,
            'asset_code' => 'AST-MAINT-002',
            'asset_name' => 'Switch',
        ]);
    }

    #[Test]
    public function authenticated_statistics_endpoint_returns_current_unit_totals(): void
    {
        $this->actingAs($this->admin);

        Asset::create([
            'kode_aset' => 'AST-001',
            'nama_barang' => 'Laptop',
            'kategori' => 'Komputer',
            'jumlah' => 3,
            'kondisi' => 'Baik',
        ]);
        Asset::create([
            'kode_aset' => 'AST-002',
            'nama_barang' => 'Printer',
            'kategori' => 'Periferal',
            'jumlah' => 2,
            'kondisi' => 'Maintenance',
        ]);
        Asset::create([
            'kode_aset' => 'AST-003',
            'nama_barang' => 'Switch',
            'kategori' => 'Network',
            'jumlah' => 1,
            'kondisi' => 'Rusak',
        ]);

        $this->getJson(route('dashboard.statistics'))
            ->assertOk()
            ->assertJsonPath('total_unit', 6)
            ->assertJsonPath('total_jenis', 3)
            ->assertJsonPath('unit_baik', 3)
            ->assertJsonPath('unit_maintenance', 2)
            ->assertJsonPath('unit_rusak', 1);
    }

    #[Test]
    public function administrators_can_log_in_and_view_inventory_statistics(): void
    {
        Asset::create([
            'kode_aset' => 'AST-001',
            'nama_barang' => 'Laptop',
            'kategori' => 'Komputer',
            'jumlah' => 3,
            'kondisi' => 'Baik',
        ]);

        $this->post(route('login.submit'), [
            'username' => ' admin ',
            'password' => 'test-password',
        ])->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Inventaris')
            ->assertSee('AST-001')
            ->assertSee('Nomor Seri')
            ->assertSee('Garansi Sampai')
            ->assertSee('Komputer');
    }

    #[Test]
    public function legacy_admin_passwords_are_rehashed_after_a_successful_login(): void
    {
        $this->admin->password_hash = 'scrypt:1024:8:16$NaCl$fdbabe1c9d3472007856e7190d01e9fe'
            .'7c6ad7cbc8237830e77376634b3731622eaf30d92e22a3886ff109279d9830da'
            .'c727afb94a83ee6d8360cbdfa2cc0640';
        $this->admin->save();

        $this->post(route('login.submit'), [
            'username' => 'admin',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertStringStartsWith('$2y$', $this->admin->fresh()->password_hash);
    }

    #[Test]
    public function hybrid_tracking_preserves_bulk_counts_and_validates_individual_units(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('tambah_aset'), [
            'tracking_mode' => 'bulk',
            'kode_aset' => 'BULK-001',
            'nama_barang' => 'Kabel HDMI',
            'kategori' => 'Aksesori',
            'jumlah' => 5,
            'kondisi' => 'Baik',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('aset', [
            'kode_aset' => 'BULK-001',
            'jumlah' => 5,
            'tracking_mode' => 'bulk',
        ]);

        $this->post(route('tambah_aset'), [
            'tracking_mode' => 'individual',
            'kode_aset' => 'LAP-001',
            'nama_barang' => 'Laptop',
            'kategori' => 'Komputer',
            'merek' => 'Dell',
            'model' => 'Latitude',
            'nomor_seri' => 'SERIAL-001',
            'nama_pemegang' => 'Ayu',
            'departemen' => 'Finance',
            'jumlah' => 1,
            'kondisi' => 'Baik',
            'tanggal_pembelian' => '2026-01-10',
            'garansi_sampai' => '2028-01-10',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('aset', [
            'kode_aset' => 'LAP-001',
            'tracking_mode' => 'individual',
            'nomor_seri' => 'SERIAL-001',
            'nama_pemegang' => 'Ayu',
            'departemen' => 'Finance',
        ]);

        $asset = Asset::where('kode_aset', 'LAP-001')->firstOrFail();
        $this->get(route('edit_aset', $asset->id))
            ->assertOk()
            ->assertSee('SERIAL-001')
            ->assertSee('Garansi Sampai');

        $this->get(route('dashboard', ['q' => 'SERIAL-001']))
            ->assertOk()
            ->assertSee('SERIAL-001</td>', false);

        $this->get(route('dashboard', ['tracking_mode' => 'individual']))
            ->assertOk()
            ->assertSee('LAP-001</td>', false)
            ->assertDontSee('BULK-001</td>', false);

        $this->from(route('dashboard'))
            ->post(route('tambah_aset'), [
                'tracking_mode' => 'individual',
                'kode_aset' => 'LAP-002',
                'nama_barang' => 'Laptop',
                'kategori' => 'Komputer',
                'nomor_seri' => 'SERIAL-001',
                'jumlah' => 2,
                'kondisi' => 'Baik',
                'tanggal_pembelian' => '2026-01-10',
                'garansi_sampai' => '2025-01-10',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors(['jumlah', 'nomor_seri', 'garansi_sampai']);

        $this->assertDatabaseMissing('aset', ['kode_aset' => 'LAP-002']);
    }

    #[Test]
    public function inventory_search_filters_and_pagination_are_preserved(): void
    {
        $this->actingAs($this->admin);

        for ($number = 1; $number <= 11; $number++) {
            Asset::create([
                'kode_aset' => sprintf('AST-%03d', $number),
                'nama_barang' => 'Aset '.$number,
                'kategori' => $number === 3 ? 'Network' : 'Komputer',
                'jumlah' => 1,
                'kondisi' => $number === 3 ? 'Rusak' : 'Baik',
            ]);
        }

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('AST-011</td>', false)
            ->assertDontSee('AST-001</td>', false)
            ->assertSee('Halaman 1 dari 2');

        $this->get(route('dashboard', ['page' => 2]))
            ->assertOk()
            ->assertSee('AST-001</td>', false)
            ->assertSee('Halaman 2 dari 2');

        $this->get(route('dashboard', [
            'q' => 'AST-003',
            'kategori' => 'Network',
            'kondisi' => 'Rusak',
        ]))
            ->assertOk()
            ->assertSee('AST-003</td>', false)
            ->assertDontSee('AST-011</td>', false);
    }

    #[Test]
    public function asset_changes_are_validated_and_written_to_the_audit_log(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('tambah_aset'), [
            'kode_aset' => 'AST-002',
            'nama_barang' => 'Switch',
            'kategori' => 'Network',
            'jumlah' => 2,
            'kondisi' => 'Maintenance',
        ])->assertRedirect(route('dashboard'));

        $asset = Asset::where('kode_aset', 'AST-002')->firstOrFail();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'CREATE',
            'asset_id' => $asset->id,
        ]);

        $this->put(route('update_aset', $asset->id), [
            'kode_aset' => 'AST-002',
            'nama_barang' => 'Managed Switch',
            'kategori' => 'Network',
            'jumlah' => 4,
            'kondisi' => 'Baik',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('aset', [
            'id' => $asset->id,
            'nama_barang' => 'Managed Switch',
            'jumlah' => 4,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'UPDATE',
            'asset_id' => $asset->id,
        ]);

        $this->post(route('hapus_aset', $asset->id))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('aset', ['id' => $asset->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'DELETE',
            'asset_id' => $asset->id,
        ]);
    }

    #[Test]
    public function excel_export_returns_a_spreadsheet(): void
    {
        $this->actingAs($this->admin);

        $this->get(route('export_excel'))
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            );
    }
}
