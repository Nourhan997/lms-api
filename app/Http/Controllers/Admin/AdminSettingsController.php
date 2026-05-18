<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Http\Requests\Admin\UploadFaviconRequest;
use App\Http\Requests\Admin\UploadLogoRequest;
use App\Services\Admin\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class AdminSettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settingsService,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->settingsService->getAllGrouped(),
            'message' => 'Settings retrieved successfully',
            'meta'    => [],
        ]);
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $this->settingsService->setMany($request->all());

        return response()->json([
            'success' => true,
            'data'    => $this->settingsService->getAllGrouped(),
            'message' => 'Settings updated successfully',
            'meta'    => [],
        ]);
    }

    public function uploadLogo(UploadLogoRequest $request): JsonResponse
    {
        $ext  = $request->file('logo')->getClientOriginalExtension();
        $path = $request->file('logo')->storeAs('branding', "logo.{$ext}", 'public');
        $url  = Storage::disk('public')->url($path);

        $this->settingsService->set('logo_url', $url, 'branding');

        return response()->json([
            'success' => true,
            'data'    => ['logo_url' => $url],
            'message' => 'Logo uploaded successfully',
            'meta'    => [],
        ]);
    }

    public function uploadFavicon(UploadFaviconRequest $request): JsonResponse
    {
        $ext  = $request->file('favicon')->getClientOriginalExtension();
        $path = $request->file('favicon')->storeAs('branding', "favicon.{$ext}", 'public');
        $url  = Storage::disk('public')->url($path);

        $this->settingsService->set('favicon_url', $url, 'branding');

        return response()->json([
            'success' => true,
            'data'    => ['favicon_url' => $url],
            'message' => 'Favicon uploaded successfully',
            'meta'    => [],
        ]);
    }
}
