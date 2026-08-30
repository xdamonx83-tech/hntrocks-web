<?php

namespace App\Http\Controllers\Api\V1\Arcade;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ArcadeGameResource;
use App\Services\Arcade\ArcadeGameCatalogService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ArcadeGameController extends Controller
{
    public function __construct(private readonly ArcadeGameCatalogService $catalog) {}
    public function index(): AnonymousResourceCollection { return ArcadeGameResource::collection($this->catalog->visibleGames()); }
    public function show(string $game): ArcadeGameResource { return new ArcadeGameResource($this->catalog->visibleGame($game)); }
}
