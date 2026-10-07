<?php

namespace Tests\Feature\Master;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BarangFotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dapat_menampilkan_foto_barang_dari_public_storage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('barang/contoh.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j0V8AAAAASUVORK5CYII='));

        $this->actingAs(User::factory()->create())
            ->get(route('barang.foto', ['path' => 'barang/contoh.png']))
            ->assertOk()->assertHeader('content-type', 'image/png')
            ->assertHeader('x-content-type-options', 'nosniff');
    }

    public function test_foto_barang_hanya_bisa_diakses_setelah_login(): void
    {
        $this->get(route('barang.foto', ['path' => 'barang/contoh.png']))
            ->assertRedirect(route('login'));
    }

    public function test_path_foto_yang_mencoba_keluar_dari_storage_ditolak(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/foto-barang/%2E%2E%2F.env')
            ->assertNotFound();
    }
}
