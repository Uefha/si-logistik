<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_profile_page(): void
    {
        $this->get('/profil')->assertRedirect(route('login'));
    }

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/profil')->assertOk()->assertSee($user->email);
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch('/profil', [
            'name' => 'Petugas Baru',
            'email' => 'petugas.baru@logistik.local',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertSame('Petugas Baru', $user->name);
        $this->assertSame('petugas.baru@logistik.local', $user->email);
    }

    public function test_email_must_be_unique(): void
    {
        $lain = User::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->from('/profil')->patch('/profil', [
            'name' => $user->name,
            'email' => $lain->email,
        ])->assertSessionHasErrors('email')->assertRedirect('/profil');
    }

    public function test_user_can_keep_own_email_when_updating_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profil', [
            'name' => 'Nama Diubah',
            'email' => $user->email,
        ])->assertSessionHasNoErrors();

        $this->assertSame('Nama Diubah', $user->refresh()->name);
    }

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/profil')->put('/password', [
            'current_password' => 'password',
            'password' => 'kata-sandi-baru-123',
            'password_confirmation' => 'kata-sandi-baru-123',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/profil');
        $this->assertTrue(Hash::check('kata-sandi-baru-123', $user->refresh()->password));
    }

    public function test_correct_current_password_is_required_to_update_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/profil')->put('/password', [
            'current_password' => 'salah',
            'password' => 'kata-sandi-baru-123',
            'password_confirmation' => 'kata-sandi-baru-123',
        ])->assertSessionHasErrorsIn('updatePassword', 'current_password')->assertRedirect('/profil');

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_new_password_must_be_confirmed_and_at_least_eight_characters(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/profil')->put('/password', [
            'current_password' => 'password',
            'password' => 'pendek',
            'password_confirmation' => 'pendek',
        ])->assertSessionHasErrorsIn('updatePassword', 'password');

        $this->actingAs($user)->from('/profil')->put('/password', [
            'current_password' => 'password',
            'password' => 'kata-sandi-baru-123',
            'password_confirmation' => 'tidak-sama-123',
        ])->assertSessionHasErrorsIn('updatePassword', 'password');
    }
}
