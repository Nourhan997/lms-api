<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private function adminWithToken(): array
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('auth-token')->plainTextToken;

        return [$admin, $token];
    }

    public function test_admin_can_get_all_settings(): void
    {
        $this->seed(SettingsSeeder::class);
        [, $token] = $this->adminWithToken();

        $this->withToken($token)
            ->getJson('/api/v1/admin/settings')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'general'      => ['platform_name', 'platform_tagline', 'support_email'],
                    'branding'     => ['logo_url', 'favicon_url', 'primary_color', 'secondary_color'],
                    'email'        => ['from_name', 'from_address', 'footer_text'],
                    'localization' => ['default_language', 'available_languages', 'default_currency'],
                    'enrollment'   => ['allow_self_registration', 'require_email_verification', 'placement_test_required'],
                ],
            ]);
    }

    public function test_admin_can_update_settings(): void
    {
        $this->seed(SettingsSeeder::class);
        [, $token] = $this->adminWithToken();

        $this->withToken($token)
            ->putJson('/api/v1/admin/settings', [
                'platform_name' => 'English Academy',
                'primary_color' => '#1A3A5C',
            ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.general.platform_name', 'English Academy');

        $this->assertDatabaseHas('settings', [
            'key'   => 'platform_name',
            'value' => 'English Academy',
        ]);
    }

    public function test_admin_can_upload_logo(): void
    {
        Storage::fake('public');
        $this->seed(SettingsSeeder::class);
        [, $token] = $this->adminWithToken();

        $file = UploadedFile::fake()->image('logo.png', 200, 200);

        $this->withToken($token)
            ->post('/api/v1/admin/settings/logo', ['logo' => $file])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['logo_url']]);

        Storage::disk('public')->assertExists('branding/logo.png');
    }

    public function test_color_validation_rejects_invalid_hex(): void
    {
        $this->seed(SettingsSeeder::class);
        [, $token] = $this->adminWithToken();

        $this->withToken($token)
            ->putJson('/api/v1/admin/settings', [
                'primary_color' => 'not-a-color',
            ])
            ->assertStatus(422);
    }

    public function test_public_can_get_public_settings(): void
    {
        $this->seed(SettingsSeeder::class);

        $this->getJson('/api/v1/public/settings')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'platform_name', 'platform_tagline',
                    'primary_color', 'secondary_color',
                    'default_language', 'available_languages', 'default_currency',
                ],
            ]);
    }

    public function test_public_settings_do_not_expose_sensitive_keys(): void
    {
        $this->seed(SettingsSeeder::class);

        $data = $this->getJson('/api/v1/public/settings')
            ->assertStatus(200)
            ->json('data');

        $this->assertArrayNotHasKey('from_address', $data);
        $this->assertArrayNotHasKey('support_email', $data);
        $this->assertArrayNotHasKey('footer_text', $data);
        $this->assertArrayNotHasKey('from_name', $data);
    }

    public function test_settings_are_cached(): void
    {
        $this->seed(SettingsSeeder::class);
        [, $token] = $this->adminWithToken();

        // Warm the cache
        $this->withToken($token)->getJson('/api/v1/admin/settings')->assertStatus(200);

        // Directly modify DB — bypasses service, so cache is NOT cleared
        Setting::where('key', 'platform_name')->update(['value' => 'Changed Directly']);

        // Should still return the cached value
        $this->withToken($token)
            ->getJson('/api/v1/admin/settings')
            ->assertStatus(200)
            ->assertJsonPath('data.general.platform_name', 'LMS Platform');
    }
}
