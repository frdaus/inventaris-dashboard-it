<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function every_role_can_update_and_remove_only_its_own_profile_photo(): void
    {
        Storage::fake('local');
        foreach ([Admin::ROLE_ADMIN, Admin::ROLE_IT_STAFF, Admin::ROLE_GUEST] as $index => $role) {
            $user = Admin::query()->create([
                'username' => "profile-user-{$index}",
                'password_hash' => 'hashed-password',
                'role' => $role,
            ]);

            $this->actingAs($user)
                ->get(route('profile.edit'))
                ->assertOk()
                ->assertSee('Profil Saya')
                ->assertSee($user->username);

            $this->put(route('profile.photo.update'), [
                'photo' => UploadedFile::fake()->image('avatar.png', 96, 96),
            ])->assertRedirect(route('profile.edit'))
                ->assertSessionHas('success', 'Foto profil berhasil diperbarui.');

            $firstPhotoPath = $user->fresh()->profile_photo_path;
            $this->assertIsString($firstPhotoPath);
            $this->assertTrue(Storage::disk('local')->exists($firstPhotoPath));

            $this->put(route('profile.photo.update'), [
                'photo' => UploadedFile::fake()->image('replacement.png', 96, 96),
            ])->assertRedirect(route('profile.edit'));

            $photoPath = $user->fresh()->profile_photo_path;
            $this->assertNotSame($firstPhotoPath, $photoPath);
            $this->assertFalse(Storage::disk('local')->exists($firstPhotoPath));
            $this->get(route('profile.photo'))
                ->assertOk()
                ->assertHeader('Cache-Control', 'no-store, private');

            $this->delete(route('profile.photo.destroy'))
                ->assertRedirect(route('profile.edit'))
                ->assertSessionHas('success', 'Foto profil berhasil dihapus.');

            $this->assertNull($user->fresh()->profile_photo_path);
            $this->assertFalse(Storage::disk('local')->exists($photoPath));
            $this->get(route('profile.photo'))->assertNotFound();
        }
    }

    #[Test]
    public function profile_photos_reject_unsupported_and_oversized_files(): void
    {
        Storage::fake('local');
        $user = Admin::query()->create([
            'username' => 'it-staff',
            'password_hash' => 'hashed-password',
            'role' => Admin::ROLE_IT_STAFF,
        ]);

        $this->actingAs($user)
            ->put(route('profile.photo.update'), [
                'photo' => UploadedFile::fake()->createWithContent('avatar.svg', '<svg></svg>'),
            ])
            ->assertSessionHasErrors('photo');

        $this->put(route('profile.photo.update'), [
            'photo' => UploadedFile::fake()->image('large.png')->size(2049),
        ])->assertSessionHasErrors('photo');

        $this->assertNull($user->fresh()->profile_photo_path);
        $this->assertSame([], Storage::disk('local')->allFiles('profile-photos'));
    }

    #[Test]
    public function profile_photo_is_private_and_requires_an_active_authenticated_user(): void
    {
        $this->get(route('profile.photo'))->assertRedirect(route('login'));

        $inactiveUser = Admin::query()->create([
            'username' => 'inactive',
            'password_hash' => 'hashed-password',
            'role' => Admin::ROLE_GUEST,
            'is_active' => false,
        ]);

        $this->actingAs($inactiveUser)
            ->get(route('profile.edit'))
            ->assertRedirect(route('login'));
    }
}
