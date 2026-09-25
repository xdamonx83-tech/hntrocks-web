<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AppConfig\WebAppearanceService;
use Illuminate\Http\JsonResponse;

class WebAppearanceController extends Controller
{
    public function __invoke(WebAppearanceService $appearance): JsonResponse
    {
        return response()->json(['backgrounds' => $appearance->publicBackgrounds()]);
    }
}
