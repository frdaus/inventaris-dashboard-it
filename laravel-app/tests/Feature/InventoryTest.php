<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Asset;
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
