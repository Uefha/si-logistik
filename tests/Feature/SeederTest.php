<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'logistik.admin.email' => 'admin@logistik.test',
            'logistik.admin.password' => 'rahasia-seeder-123',
        ]);
    }

    public function test_seeder_creates_admin_and_master_data(): void
    {
        $this->seed();

        $this->assertSame(1, User::query()->count());
        $admin = User::query()->where('email', 'admin@logistik.test')->first();
        $this->assertNotNull($admin);
        $this->assertTrue(Hash::check('rahasia-seeder-123', $admin->password));

        $this->assertSame(10, DB::table('kategori_barang')->count());
        $this->assertSame(12, DB::table('satuan')->count());
        $this->assertSame(10, DB::table('lokasi')->count());
        $this->assertSame(3, DB::table('pengaturan')->count());
        $this->assertDatabaseHas('kategori_barang', ['nama' => 'ATK']);
        $this->assertDatabaseHas('satuan', ['nama' => 'Rim']);
        $this->assertDatabaseHas('lokasi', ['nama' => 'Gudang Utama']);
        $this->assertDatabaseHas('pengaturan', ['key' => 'nama_instansi', 'value' => 'Bagian Logistik']);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed();
        $this->seed();
        $this->seed();

        $this->assertSame(1, User::query()->count());
        $this->assertSame(10, DB::table('kategori_barang')->count());
        $this->assertSame(12, DB::table('satuan')->count());
        $this->assertSame(10, DB::table('lokasi')->count());
        $this->assertSame(3, DB::table('pengaturan')->count());
    }

    public function test_reseeding_does_not_overwrite_admin_password(): void
    {
        $this->seed();

        User::query()->where('email', 'admin@logistik.test')->first()->update(['password' => 'sudah-diganti-456']);

        $this->seed();

        $admin = User::query()->where('email', 'admin@logistik.test')->first();
        $this->assertTrue(Hash::check('sudah-diganti-456', $admin->password));
        $this->assertFalse(Hash::check('rahasia-seeder-123', $admin->password));
    }

    public function test_reseeding_does_not_overwrite_settings_changed_by_admin(): void
    {
        $this->seed();

        DB::table('pengaturan')->where('key', 'nama_instansi')->update(['value' => 'Logistik Asrama']);

        $this->seed();

        $this->assertDatabaseHas('pengaturan', ['key' => 'nama_instansi', 'value' => 'Logistik Asrama']);
    }

    public function test_reseeding_does_not_resurrect_soft_deleted_master_rows(): void
    {
        $this->seed();

        DB::table('kategori_barang')->where('nama', 'ATK')->update(['deleted_at' => now()]);

        $this->seed();

        $this->assertSame(10, DB::table('kategori_barang')->count());
        $this->assertNotNull(DB::table('kategori_barang')->where('nama', 'ATK')->value('deleted_at'));
    }

    public function test_random_password_is_generated_when_none_configured(): void
    {
        config(['logistik.admin.password' => null]);

        $this->seed(AdminSeeder::class);

        $admin = User::query()->where('email', 'admin@logistik.test')->first();
        $this->assertNotNull($admin);
        $this->assertNotEmpty($admin->password);
        $this->assertFalse(Hash::check('', $admin->password));
    }
}
