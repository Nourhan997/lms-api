<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Admin\SettingsService;
use Illuminate\Http\JsonResponse;

class PublicSettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settingsService,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->settingsService->getPublicSettings(),
            'message' => 'Settings retrieved successfully',
            'meta'    => [],
        ]);
    }
}
