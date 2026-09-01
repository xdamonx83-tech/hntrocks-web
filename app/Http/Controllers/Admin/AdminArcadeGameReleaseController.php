<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ArcadeGameReleaseRequest;
use App\Models\Arcade\ArcadeGame;
use App\Models\Arcade\ArcadeGameRelease;
use App\Services\Arcade\ArcadeGameReleaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminArcadeGameReleaseController extends Controller
{
    public function __construct(private readonly ArcadeGameReleaseService $releases) {}

    public function store(ArcadeGameReleaseRequest $request, ArcadeGame $game): RedirectResponse
    {
        $this->releases->createDraft($game, $request->validated(), $request->user());

        return redirect()->route('admin.arcade-games.edit', $game)->with('status', 'Dynamic-Release als Draft angelegt.');
    }

    public function publish(Request $request, ArcadeGame $game, ArcadeGameRelease $release): RedirectResponse
    {
        $this->guardAdmin($request);
        $this->releases->publish($game, $release, $request->user());

        return redirect()->route('admin.arcade-games.edit', $game)->with('status', 'Dynamic-Release veröffentlicht. Der Spielstatus wurde nicht automatisch geändert.');
    }

    public function retire(Request $request, ArcadeGame $game, ArcadeGameRelease $release): RedirectResponse
    {
        $this->guardAdmin($request);
        $this->releases->retire($game, $release, $request->user());

        return redirect()->route('admin.arcade-games.edit', $game)->with('status', 'Dynamic-Release zurückgezogen.');
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
