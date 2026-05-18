<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    private const PUBLIC_KEYS = [
        'platform_name', 'platform_tagline', 'logo_url', 'favicon_url',
        'primary_color', 'secondary_color', 'default_language',
        'available_languages', 'default_currency',
    ];

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::tags(['settings'])->remember("settings.key.{$key}", 3600, function () use ($key, $default) {
            $setting = Setting::where('key', $key)->first();

            return $setting?->value ?? $default;
        });
    }

    public function set(string $key, mixed $value, string $group = 'general'): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
        Cache::tags(['settings'])->flush();
    }

    public function setMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::tags(['settings'])->flush();
    }

    public function getGroup(string $group): array
    {
        return Setting::where('group', $group)
            ->get()
            ->pluck('value', 'key')
            ->toArray();
    }

    public function getAllGrouped(): array
    {
        return Cache::tags(['settings'])->remember('settings.all_grouped', 3600, function () {
            return Setting::all()
                ->groupBy('group')
                ->mapWithKeys(fn ($items, $group) => [
                    $group => $items->pluck('value', 'key')->toArray(),
                ])
                ->toArray();
        });
    }

    public function getPublicSettings(): array
    {
        return Cache::tags(['settings'])->remember('settings.public', 3600, function () {
            return Setting::whereIn('key', self::PUBLIC_KEYS)
                ->get()
                ->pluck('value', 'key')
                ->toArray();
        });
    }
}
