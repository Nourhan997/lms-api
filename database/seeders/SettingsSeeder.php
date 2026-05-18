<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['group' => 'general',      'key' => 'platform_name',              'value' => 'LMS Platform'],
            ['group' => 'general',      'key' => 'platform_tagline',           'value' => 'Learn at your own pace'],
            ['group' => 'general',      'key' => 'support_email',              'value' => 'support@lms.test'],
            ['group' => 'branding',     'key' => 'logo_url',                   'value' => null],
            ['group' => 'branding',     'key' => 'favicon_url',                'value' => null],
            ['group' => 'branding',     'key' => 'primary_color',              'value' => '#1A3A5C'],
            ['group' => 'branding',     'key' => 'secondary_color',            'value' => '#2E75B6'],
            ['group' => 'email',        'key' => 'from_name',                  'value' => 'LMS Platform'],
            ['group' => 'email',        'key' => 'from_address',               'value' => 'noreply@lms.test'],
            ['group' => 'email',        'key' => 'footer_text',                'value' => '© 2026 LMS Platform. All rights reserved.'],
            ['group' => 'localization', 'key' => 'default_language',           'value' => 'en'],
            ['group' => 'localization', 'key' => 'available_languages',        'value' => '["en","ar"]'],
            ['group' => 'localization', 'key' => 'default_currency',           'value' => 'OMR'],
            ['group' => 'enrollment',   'key' => 'allow_self_registration',    'value' => 'true'],
            ['group' => 'enrollment',   'key' => 'require_email_verification', 'value' => 'false'],
            ['group' => 'enrollment',   'key' => 'placement_test_required',    'value' => 'false'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'group' => $setting['group']],
            );
        }
    }
}
